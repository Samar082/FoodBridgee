<?php
declare(strict_types=1);

/*
 * Local development defaults for XAMPP/WAMP.
 * Change these values to match your MySQL server before running the project.
 */
return [
    'db_host' => '127.0.0.1',
    'db_port' => '3306',
    'db_name' => 'foodbridge',
    'db_user' => 'root',
    'db_pass' => '',
    'timezone' => 'Asia/Kolkata',
    'uploads_dir' => dirname(__DIR__) . DIRECTORY_SEPARATOR . 'uploads',
    'max_upload_bytes' => 5 * 1024 * 1024,

    /*
     * Fill these in to actually send NGO notification SMS via Fast2SMS.
     * 1. Sign up at fast2sms.com and grab your API key from the "Dev API" page.
     * 2. Paste it below and set 'enabled' to true.
     * Leave 'enabled' as false to skip sending and just log the message instead.
     */
    'fast2sms' => [
        'enabled' => false,
        'api_key' => '',
        // 'q' (Quick, transactional, no DLT template needed - good for testing)
        // or 'dlt' if you have a DLT-registered sender ID + template for production.
        'route' => 'q',
        'sender_id' => 'FSTSMS',
    ],
];
