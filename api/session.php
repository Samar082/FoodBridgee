<?php
declare(strict_types=1);

require __DIR__ . '/db.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_name('foodbridge_ngo_session');
    session_start();
}

/**
 * Returns the logged-in NGO's basic info, or null if no one is logged in.
 */
function currentNgo(): ?array
{
    if (empty($_SESSION['ngo_id'])) {
        return null;
    }

    static $cached = null;
    if ($cached !== null) {
        return $cached;
    }

    $statement = db()->prepare('SELECT id, name, city, contact_phone FROM ngos WHERE id = :id AND is_active = 1');
    $statement->execute([':id' => $_SESSION['ngo_id']]);
    $ngo = $statement->fetch();

    if (!$ngo) {
        unset($_SESSION['ngo_id']);
        return null;
    }

    $cached = $ngo;
    return $ngo;
}

/**
 * Ends the request with a 401 JSON response if no NGO is logged in.
 * Returns the current NGO's info if they are.
 */
function requireNgoLogin(): array
{
    $ngo = currentNgo();
    if ($ngo === null) {
        jsonResponse(401, ['success' => false, 'message' => 'Please log in as an NGO first.']);
    }
    return $ngo;
}
