<?php
declare(strict_types=1);

require __DIR__ . '/session.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $ngo = currentNgo();
    jsonResponse(200, ['success' => true, 'ngo' => $ngo]);
}

if ($method === 'POST') {
    $raw = file_get_contents('php://input');
    $input = json_decode($raw, true);
    if (!is_array($input)) {
        $input = $_POST;
    }

    $action = (string) ($input['action'] ?? 'login');

    if ($action === 'logout') {
        $_SESSION = [];
        session_destroy();
        jsonResponse(200, ['success' => true]);
    }

    $phone = trim((string) ($input['phone'] ?? ''));
    $password = (string) ($input['password'] ?? '');

    if ($phone === '' || $password === '') {
        jsonResponse(422, ['success' => false, 'message' => 'Enter your phone number and password.']);
    }

    $normalizedPhone = preg_replace('/\D+/', '', $phone) ?? '';

    $statement = db()->prepare(
        'SELECT id, name, contact_phone, password_hash
         FROM ngos
         WHERE REPLACE(REPLACE(REPLACE(contact_phone, " ", ""), "-", ""), "+", "") LIKE :phone
           AND is_verified = 1 AND is_active = 1'
    );
    $statement->execute([':phone' => '%' . $normalizedPhone]);
    $ngo = $statement->fetch();

    if (!$ngo || $ngo['password_hash'] === null || !password_verify($password, $ngo['password_hash'])) {
        jsonResponse(401, ['success' => false, 'message' => 'Incorrect phone number or password.']);
    }

    session_regenerate_id(true);
    $_SESSION['ngo_id'] = $ngo['id'];

    jsonResponse(200, [
        'success' => true,
        'ngo' => ['id' => $ngo['id'], 'name' => $ngo['name'], 'contact_phone' => $ngo['contact_phone']],
    ]);
}

methodNotAllowed('GET, POST');
