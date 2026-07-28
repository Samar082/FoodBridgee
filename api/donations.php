<?php
declare(strict_types=1);

require __DIR__ . '/db.php';
require __DIR__ . '/sms.php';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $statement = db()->query(
            'SELECT id, food_type, servings, pickup_location, pickup_deadline, status, created_at
             FROM donations
             ORDER BY created_at DESC
             LIMIT 20'
        );
        jsonResponse(200, ['success' => true, 'donations' => $statement->fetchAll()]);
    } catch (Throwable $exception) {
        error_log('FoodBridge donation list error: ' . $exception->getMessage());
        jsonResponse(500, ['success' => false, 'message' => 'Unable to load donations.']);
    }
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    methodNotAllowed('GET, POST');
}

function postString(string $key): string
{
    return trim((string) ($_POST[$key] ?? ''));
}

function validationError(array $errors): never
{
    jsonResponse(422, ['success' => false, 'message' => 'Please correct the highlighted details.', 'errors' => $errors]);
}

$donorName = postString('donor_name');
$donorPhone = postString('donor_phone');
$foodType = postString('food_type');
$servings = filter_input(INPUT_POST, 'servings', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 100000]]);
$pickupDeadlineInput = postString('pickup_deadline');
$pickupLocation = postString('pickup_location');
$foodNotes = postString('food_notes');
$errors = [];

if ($donorName === '' || mb_strlen($donorName) > 100) {
    $errors['donor_name'] = 'Enter a name of up to 100 characters.';
}
if ($donorPhone === '' || mb_strlen($donorPhone) > 30) {
    $errors['donor_phone'] = 'Enter a contact number of up to 30 characters.';
}
if (!in_array($foodType, ['Vegetarian meals', 'Non-vegetarian meals', 'Packaged food', 'Dry rations'], true)) {
    $errors['food_type'] = 'Choose a valid food type.';
}
if ($servings === false || $servings === null) {
    $errors['servings'] = 'Enter a number of servings between 1 and 100000.';
}
if ($pickupLocation === '' || mb_strlen($pickupLocation) > 255) {
    $errors['pickup_location'] = 'Enter a pickup location of up to 255 characters.';
}
if (mb_strlen($foodNotes) > 1000) {
    $errors['food_notes'] = 'Notes must be 1000 characters or fewer.';
}

$pickupDeadline = DateTime::createFromFormat('Y-m-d\TH:i', $pickupDeadlineInput);
if (!$pickupDeadline || $pickupDeadline->format('Y-m-d\TH:i') !== $pickupDeadlineInput) {
    $errors['pickup_deadline'] = 'Enter a valid pickup deadline.';
} elseif ($pickupDeadline <= new DateTime()) {
    $errors['pickup_deadline'] = 'The pickup deadline must be in the future.';
}

if ($errors !== []) {
    validationError($errors);
}

$imagePath = null;
try {
    if (isset($_FILES['food_photo']) && $_FILES['food_photo']['error'] !== UPLOAD_ERR_NO_FILE) {
        $upload = $_FILES['food_photo'];
        if ($upload['error'] !== UPLOAD_ERR_OK) {
            validationError(['food_photo' => 'The photo upload did not complete. Please try another image.']);
        }
        if ($upload['size'] > $config['max_upload_bytes']) {
            validationError(['food_photo' => 'The photo must be 5 MB or smaller.']);
        }

        $mimeType = (new finfo(FILEINFO_MIME_TYPE))->file($upload['tmp_name']);
        $extensions = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
        if (!isset($extensions[$mimeType])) {
            validationError(['food_photo' => 'Upload a JPG, PNG, or WEBP image.']);
        }

        if (!is_dir($config['uploads_dir']) && !mkdir($config['uploads_dir'], 0755, true) && !is_dir($config['uploads_dir'])) {
            throw new RuntimeException('Upload directory could not be created.');
        }
        $fileName = bin2hex(random_bytes(16)) . '.' . $extensions[$mimeType];
        $destination = $config['uploads_dir'] . DIRECTORY_SEPARATOR . $fileName;
        if (!move_uploaded_file($upload['tmp_name'], $destination)) {
            throw new RuntimeException('Uploaded file could not be moved.');
        }
        $imagePath = 'uploads/' . $fileName;
    }

    $pdo = db();
    $ngoStatement = $pdo->query(
        'SELECT id, name, city, contact_phone
         FROM ngos
         WHERE is_verified = 1 AND is_active = 1
         ORDER BY id ASC
         LIMIT 1'
    );
    $matchedNgo = $ngoStatement->fetch();

    $statement = $pdo->prepare(
        'INSERT INTO donations
         (donor_name, donor_phone, food_type, servings, pickup_deadline, pickup_location, food_notes, image_path, matched_ngo_id, status)
         VALUES (:donor_name, :donor_phone, :food_type, :servings, :pickup_deadline, :pickup_location, :food_notes, :image_path, :matched_ngo_id, :status)'
    );
    $statement->execute([
        ':donor_name' => $donorName,
        ':donor_phone' => $donorPhone,
        ':food_type' => $foodType,
        ':servings' => $servings,
        ':pickup_deadline' => $pickupDeadline->format('Y-m-d H:i:s'),
        ':pickup_location' => $pickupLocation,
        ':food_notes' => $foodNotes !== '' ? $foodNotes : null,
        ':image_path' => $imagePath,
        ':matched_ngo_id' => $matchedNgo['id'] ?? null,
        ':status' => $matchedNgo ? 'matched' : 'pending',
    ]);

    $donationId = (int) $pdo->lastInsertId();

    notifyNgosOfDonation($pdo, $donationId, $matchedNgo, $foodType, $servings, $pickupLocation, $pickupDeadline);

    jsonResponse(201, [
        'success' => true,
        'donation_id' => $donationId,
        'status' => $matchedNgo ? 'matched' : 'pending',
        'matched_ngo' => $matchedNgo ? [
            'name' => $matchedNgo['name'],
            'city' => $matchedNgo['city'],
            'contact_phone' => $matchedNgo['contact_phone'],
        ] : null,
    ]);
} catch (Throwable $exception) {
    if ($imagePath !== null) {
        $savedFile = dirname(__DIR__) . DIRECTORY_SEPARATOR . $imagePath;
        if (is_file($savedFile)) {
            unlink($savedFile);
        }
    }
    error_log('FoodBridge donation create error: ' . $exception->getMessage());
    jsonResponse(500, ['success' => false, 'message' => 'Unable to save the donation. Check the database setup and uploads folder permissions.']);
}

/**
 * Texts the NGO that got auto-matched, or - if no NGO matched - every
 * verified, active NGO with a phone number, so someone can claim it from
 * the dashboard. Never lets an SMS failure affect the donation save itself.
 */
function notifyNgosOfDonation(
    PDO $pdo,
    int $donationId,
    ?array $matchedNgo,
    string $foodType,
    int $servings,
    string $pickupLocation,
    DateTime $pickupDeadline
): void {
    $deadlineText = $pickupDeadline->format('g:i A, M j');
    $message = "FoodBridge: {$servings} servings of {$foodType} available at {$pickupLocation}, pickup by {$deadlineText}. Donation #{$donationId}.";

    try {
        if ($matchedNgo) {
            sendNotificationSms($matchedNgo['contact_phone'], $message . ' Log in to your NGO dashboard to accept.');
            return;
        }

        $statement = $pdo->query(
            'SELECT contact_phone FROM ngos WHERE is_verified = 1 AND is_active = 1 AND contact_phone IS NOT NULL'
        );
        foreach ($statement->fetchAll() as $row) {
            sendNotificationSms($row['contact_phone'], $message . ' First to accept on the dashboard gets it.');
        }
    } catch (Throwable $exception) {
        error_log('FoodBridge SMS notification error: ' . $exception->getMessage());
    }
}
