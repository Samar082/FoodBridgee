<?php
declare(strict_types=1);

require __DIR__ . '/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    methodNotAllowed('GET');
}

try {
    $statement = db()->query(
        'SELECT id, name, description, city
         FROM ngos
         WHERE is_verified = 1 AND is_active = 1
         ORDER BY name ASC
         LIMIT 12'
    );

    jsonResponse(200, ['success' => true, 'ngos' => $statement->fetchAll()]);
} catch (Throwable $exception) {
    error_log('FoodBridge NGO API error: ' . $exception->getMessage());
    jsonResponse(500, ['success' => false, 'message' => 'Unable to load NGO partners. Check the database configuration.']);
}
