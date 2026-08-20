<?php
/**
 * POST /api/passport-reservation.php
 * Body (JSON): { fullName, email, phone, quantity, passportType }
 *
 * Stores a passport reservation with payment_status='unpaid' and the
 * server-computed amount due. Returns a checkout URL the frontend should
 * redirect to next — see api/payment-init.php for what starts that.
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/http.php';
require_once __DIR__ . '/../includes/pricing.php';

require_post_method();
check_sec_fetch_site();

// Valid passport type slugs come from includes/pricing.php (the single
// source of truth for prices) — must match the `slug` field on each entry
// in index.html's ticketsList arrays (FR/EN/AR).
$validPassportTypes = array_keys(PASSPORT_PRICES_TND);

$body = read_json_body();

$fullName = trim((string)($body['fullName'] ?? ''));
$email = trim((string)($body['email'] ?? ''));

// Rate-limited by email+IP together (not IP alone), so a rotated-IP attack
// against one target email is still throttled, and a shared/proxied IP
// doesn't collapse unrelated visitors into one bucket.
api_rate_limit_check('reservation:' . strtolower($email) . '|' . ($_SERVER['REMOTE_ADDR'] ?? ''), 10, 900);
$phone = trim((string)($body['phone'] ?? ''));
$quantityRaw = $body['quantity'] ?? null;
$passportType = trim((string)($body['passportType'] ?? ''));

$errors = [];

if ($fullName === '' || mb_strlen($fullName) > 200) {
    $errors[] = 'full name is required';
}
if ($email === '' || !is_valid_email($email) || mb_strlen($email) > 255) {
    $errors[] = 'a valid email is required';
}
if ($phone === '' || mb_strlen($phone) > 30) {
    $errors[] = 'phone is required';
}
if (!is_numeric($quantityRaw) || (int)$quantityRaw != $quantityRaw || (int)$quantityRaw < 1 || (int)$quantityRaw > 20) {
    $errors[] = 'quantity must be a positive integer (max 20)';
}
if (!in_array($passportType, $validPassportTypes, true)) {
    $errors[] = 'passport type must be one of: ' . implode(', ', $validPassportTypes);
}

if ($errors) {
    json_error(implode('; ', $errors), 422);
}

$quantity = (int)$quantityRaw;
$amountDue = round(passport_unit_price($passportType) * $quantity, 2);

try {
    $pdo = get_db();
    $reference = generate_reference($pdo, 'passport_reservations', 'GV-2026-');

    $stmt = $pdo->prepare(
        'INSERT INTO passport_reservations
            (reference, full_name, email, phone, quantity, passport_type, status, amount_due, currency, payment_status)
         VALUES (?, ?, ?, ?, ?, ?, \'pending\', ?, \'TND\', \'unpaid\')'
    );
    $stmt->execute([$reference, $fullName, $email, $phone, $quantity, $passportType, $amountDue]);

    json_success(['reference' => $reference, 'amountDue' => $amountDue, 'currency' => 'TND'], 201);
} catch (Throwable $e) {
    error_log('[passport-reservation] ' . $e->getMessage());
    json_error('Something went wrong while saving your reservation. Please try again.', 500);
}
