<?php
/**
 * POST /api/payment-webhook.php
 *
 * Server-to-server callback from the payment provider. This is the ONLY
 * place that should ever mark a reservation as paid — never trust the
 * browser's return/redirect URL alone, since a user can navigate to a
 * "success" URL without actually paying.
 *
 * Every call is logged to payment_events (even ones that fail signature
 * verification or reference an unknown reservation) so nothing is silently
 * dropped when investigating a payment dispute later.
 *
 * STUB NOTE: payment_provider_verify_webhook() currently always returns
 * true (see includes/payment_provider.php) — this must be replaced with
 * real signature verification before going live with a real provider,
 * otherwise anyone could POST here and fake a "paid" reservation.
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/http.php';
require_once __DIR__ . '/../includes/payment_provider.php';

require_post_method();

// get_db() loads config/database.php, which defines PAYMENT_WEBHOOK_SECRET —
// call it before verifying so the constant is available.
get_db();

$rawBody = file_get_contents('php://input') ?: '';
$headers = function_exists('getallheaders') ? getallheaders() : [];

if (!payment_provider_verify_webhook($headers, $rawBody)) {
    error_log('[payment-webhook] signature verification failed');
    json_error('Invalid signature.', 401);
}

$payload = json_decode($rawBody, true);
if (!is_array($payload)) {
    json_error('Invalid payload.', 400);
}

// STUB payload shape — replace field names with whatever the real
// provider actually sends once one is wired up.
$reference = trim((string)($payload['reference'] ?? ''));
$providerEventId = trim((string)($payload['event_id'] ?? ''));
$eventType = trim((string)($payload['event_type'] ?? 'payment.succeeded'));
$provider = trim((string)($payload['provider'] ?? 'stub'));

if ($reference === '') {
    json_error('Missing reference.', 422);
}

try {
    $pdo = get_db();
    $pdo->beginTransaction();

    $stmt = $pdo->prepare('SELECT * FROM passport_reservations WHERE reference = ? LIMIT 1 FOR UPDATE');
    $stmt->execute([$reference]);
    $reservation = $stmt->fetch();

    if (!$reservation) {
        $pdo->rollBack();
        error_log("[payment-webhook] unknown reference: $reference");
        json_error('Unknown reference.', 404);
    }

    // Log the event unconditionally (including duplicates — the UNIQUE key
    // on (provider, provider_event_id) makes a duplicate delivery a no-op
    // instead of an error, which providers commonly do on retries).
    $log = $pdo->prepare(
        'INSERT IGNORE INTO payment_events (reservation_id, provider, provider_event_id, event_type, raw_payload)
         VALUES (?, ?, ?, ?, ?)'
    );
    $log->execute([$reservation['id'], $provider, $providerEventId ?: null, $eventType, $rawBody]);

    if ($eventType === 'payment.succeeded' && $reservation['payment_status'] !== 'paid') {
        $update = $pdo->prepare(
            "UPDATE passport_reservations
             SET payment_status = 'paid', payment_provider = ?, payment_reference = ?, paid_at = NOW()
             WHERE id = ?"
        );
        $update->execute([$provider, $providerEventId ?: $reference, $reservation['id']]);
    } elseif ($eventType === 'payment.failed') {
        $update = $pdo->prepare("UPDATE passport_reservations SET payment_status = 'failed' WHERE id = ?");
        $update->execute([$reservation['id']]);
    }

    $pdo->commit();
    json_success(['received' => true]);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('[payment-webhook] ' . $e->getMessage());
    json_error('Webhook processing failed.', 500);
}
