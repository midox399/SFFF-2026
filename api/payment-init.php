<?php
/**
 * POST /api/payment-init.php
 * Body (JSON): { reference }
 *
 * Starts a payment for an existing reservation and returns a checkout URL
 * to redirect the browser to. This is currently a STUB: there is no live
 * gateway account yet (see includes/payment_provider.php for exactly what
 * to fill in once Konnect/Paymee credentials exist). Until then it marks
 * the reservation payment_status='processing' and returns a local
 * "fake checkout" URL so the full flow (init -> pay -> webhook -> confirm)
 * can be tested end-to-end without a real provider.
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/http.php';
require_once __DIR__ . '/../includes/payment_provider.php';

require_post_method();

$body = read_json_body();
$reference = trim((string)($body['reference'] ?? ''));

if ($reference === '') {
    json_error('reference is required', 422);
}

try {
    $pdo = get_db();
    $stmt = $pdo->prepare('SELECT * FROM passport_reservations WHERE reference = ? LIMIT 1');
    $stmt->execute([$reference]);
    $reservation = $stmt->fetch();

    if (!$reservation) {
        json_error('Reservation not found.', 404);
    }
    if ($reservation['payment_status'] === 'paid') {
        json_error('This reservation has already been paid.', 409);
    }

    $checkoutUrl = payment_provider_init_checkout($pdo, $reservation);

    $update = $pdo->prepare(
        "UPDATE passport_reservations SET payment_status = 'processing' WHERE id = ?"
    );
    $update->execute([$reservation['id']]);

    json_success(['checkoutUrl' => $checkoutUrl]);
} catch (Throwable $e) {
    error_log('[payment-init] ' . $e->getMessage());
    json_error('Could not start payment. Please try again.', 500);
}
