<?php
declare(strict_types=1);

/**
 * Sends an SMS via the Fast2SMS API (https://www.fast2sms.com/dev-api).
 *
 * If $config['fast2sms']['enabled'] is false (the default), this just logs
 * the message instead of sending it, so the rest of the app keeps working
 * before you've set up a real Fast2SMS account.
 *
 * $phone should be a 10-digit Indian mobile number (no +91 / leading 0).
 * Returns true on success, false on failure. Never throws - a failed SMS
 * should not break the donation flow.
 */
function sendNotificationSms(string $phone, string $message): bool
{
    global $config;
    $fast2sms = $config['fast2sms'] ?? null;

    $digits = preg_replace('/\D+/', '', $phone) ?? '';
    // Strip a leading country code (91) if present, Fast2SMS expects a plain 10-digit number.
    if (strlen($digits) === 12 && str_starts_with($digits, '91')) {
        $digits = substr($digits, 2);
    }

    if (strlen($digits) !== 10) {
        error_log("FoodBridge SMS skipped (not a valid 10-digit number): {$phone}");
        return false;
    }

    if (!$fast2sms || empty($fast2sms['enabled'])) {
        error_log("FoodBridge SMS (Fast2SMS disabled, not sent) to {$digits}: {$message}");
        return false;
    }

    try {
        return fast2smsSend($fast2sms, $digits, $message);
    } catch (Throwable $exception) {
        error_log('FoodBridge SMS error: ' . $exception->getMessage());
        return false;
    }
}

function fast2smsSend(array $fast2sms, string $digits, string $message): bool
{
    $payload = [
        'route' => $fast2sms['route'] ?? 'q',
        'message' => $message,
        'language' => 'english',
        'flash' => 0,
        'numbers' => $digits,
    ];
    if (($fast2sms['route'] ?? 'q') === 'dlt' && !empty($fast2sms['sender_id'])) {
        $payload['sender_id'] = $fast2sms['sender_id'];
    }

    $ch = curl_init('https://www.fast2sms.com/dev/bulkV2');
    curl_setopt_array($ch, [
        CURLOPT_POSTFIELDS => http_build_query($payload),
        CURLOPT_HTTPHEADER => [
            'authorization: ' . $fast2sms['api_key'],
            'Content-Type: application/x-www-form-urlencoded',
        ],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
    ]);
    $body = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);

    if ($body === false) {
        throw new RuntimeException("Fast2SMS request failed: {$error}");
    }

    $result = json_decode($body, true);
    if (!is_array($result) || empty($result['return'])) {
        throw new RuntimeException('Fast2SMS rejected the message: ' . $body);
    }

    return true;
}
