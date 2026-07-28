<?php
declare(strict_types=1);

require __DIR__ . '/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    methodNotAllowed('GET');
}

try {
    db()->query('SELECT 1');
    jsonResponse(200, ['success' => true, 'message' => 'FoodBridge API and database are connected.']);
} catch (Throwable $exception) {
    error_log('FoodBridge health check error: ' . $exception->getMessage());
    jsonResponse(500, ['success' => false, 'message' => 'Database connection failed. Check api/config.php and import the SQL file.']);
}
