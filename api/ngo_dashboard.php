<?php
declare(strict_types=1);

require __DIR__ . '/session.php';

$ngo = requireNgoLogin();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    try {
        $statement = db()->prepare(
            "SELECT id, donor_name, donor_phone, food_type, servings, pickup_deadline,
                    pickup_location, food_notes, image_path, status, matched_ngo_id, created_at
             FROM donations
             WHERE status IN ('pending', 'matched')
               AND (matched_ngo_id IS NULL OR matched_ngo_id = :ngo_id)
             ORDER BY pickup_deadline ASC
             LIMIT 50"
        );
        $statement->execute([':ngo_id' => $ngo['id']]);
        jsonResponse(200, ['success' => true, 'ngo' => $ngo, 'donations' => $statement->fetchAll()]);
    } catch (Throwable $exception) {
        error_log('FoodBridge NGO dashboard error: ' . $exception->getMessage());
        jsonResponse(500, ['success' => false, 'message' => 'Unable to load donations.']);
    }
}

if ($method === 'POST') {
    $raw = file_get_contents('php://input');
    $input = json_decode($raw, true);
    if (!is_array($input)) {
        $input = $_POST;
    }

    $donationId = filter_var($input['donation_id'] ?? null, FILTER_VALIDATE_INT);
    if (!$donationId) {
        jsonResponse(422, ['success' => false, 'message' => 'A valid donation_id is required.']);
    }

    try {
        $pdo = db();
        $statement = $pdo->prepare(
            "UPDATE donations
             SET status = 'accepted', matched_ngo_id = :ngo_id
             WHERE id = :id
               AND status IN ('pending', 'matched')
               AND (matched_ngo_id IS NULL OR matched_ngo_id = :ngo_id_check)"
        );
        $statement->execute([
            ':ngo_id' => $ngo['id'],
            ':id' => $donationId,
            ':ngo_id_check' => $ngo['id'],
        ]);

        if ($statement->rowCount() === 0) {
            jsonResponse(409, ['success' => false, 'message' => 'This donation was already claimed or no longer available.']);
        }

        jsonResponse(200, ['success' => true]);
    } catch (Throwable $exception) {
        error_log('FoodBridge NGO accept error: ' . $exception->getMessage());
        jsonResponse(500, ['success' => false, 'message' => 'Unable to accept this donation.']);
    }
}

methodNotAllowed('GET, POST');
