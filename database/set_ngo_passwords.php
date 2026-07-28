<?php
declare(strict_types=1);

/*
 * One-time helper: sets a starting password for any NGO that doesn't have one yet.
 * Open this file in your browser once (e.g. http://localhost/FoodBridges-main/database/set_ngo_passwords.php),
 * note down the printed credentials, then feel free to delete this file.
 */

require __DIR__ . '/../api/db.php';

$startingPassword = 'changeme123';
$hash = password_hash($startingPassword, PASSWORD_DEFAULT);

$pdo = db();
$ngos = $pdo->query('SELECT id, name, contact_phone, password_hash FROM ngos')->fetchAll();

$updated = [];
foreach ($ngos as $ngo) {
    if ($ngo['password_hash'] !== null) {
        continue;
    }
    if (empty($ngo['contact_phone'])) {
        continue;
    }
    $statement = $pdo->prepare('UPDATE ngos SET password_hash = :hash WHERE id = :id');
    $statement->execute([':hash' => $hash, ':id' => $ngo['id']]);
    $updated[] = $ngo;
}

header('Content-Type: text/plain; charset=utf-8');

if ($updated === []) {
    echo "No NGOs needed a password. Everyone already has one set.\n";
    exit;
}

echo "Starting password for each NGO below is: {$startingPassword}\n";
echo "(NGOs should change this after logging in.)\n\n";
foreach ($updated as $ngo) {
    echo "- {$ngo['name']}: login with {$ngo['contact_phone']}\n";
}
echo "\nYou can delete this file now (database/set_ngo_passwords.php) - it's not needed again\nunless a new NGO is added without a password.\n";
