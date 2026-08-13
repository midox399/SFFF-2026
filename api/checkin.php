<?php
/**
 * POST /api/checkin.php
 * Body (JSON): { reference }
 *
 * Records a door scan for a reservation. Admin-authenticated (staff must
 * be logged in) — this is not a public endpoint, unlike the reservation/
 * application/webhook ones. Requires payment_status='paid'; does NOT block
 * re-entry, since a paid passport is expected to be scanned on multiple
 * different nights across the 15-night festival — every successful scan
 * is logged to checkin_log so there's a full history per passport.
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/http.php';
require_once __DIR__ . '/../includes/auth.php';

require_admin_auth_json();
require_post_method();

$body = read_json_body();
$reference = trim((string)($body['reference'] ?? ''));

if ($reference === '') {
    json_error('Reference is required.', 422);
}

try {
    $pdo = get_db();

    $stmt = $pdo->prepare('SELECT * FROM passport_reservations WHERE reference = ? LIMIT 1');
    $stmt->execute([$reference]);
    $reservation = $stmt->fetch();

    if (!$reservation) {
        json_error('Aucune réservation ne correspond à cette référence.', 404);
    }
    if ($reservation['payment_status'] !== 'paid') {
        json_error('Ce passeport n\'a pas été payé (statut: ' . $reservation['payment_status'] . ').', 409);
    }

    $adminId = current_admin_id();
    $log = $pdo->prepare(
        'INSERT INTO checkin_log (reservation_id, scanned_by_admin_id) VALUES (?, ?)'
    );
    $log->execute([$reservation['id'], $adminId]);

    $countStmt = $pdo->prepare('SELECT COUNT(*) FROM checkin_log WHERE reservation_id = ?');
    $countStmt->execute([$reservation['id']]);
    $scanCount = (int)$countStmt->fetchColumn();

    $passportLabels = [
        'standard' => 'Passeport Normal',
        'pro' => 'Passeport Pro',
        'diplomatique' => 'Passeport Diplomatique',
        'vip' => 'VIP Gold',
    ];

    json_success([
        'reference' => $reservation['reference'],
        'fullName' => $reservation['full_name'],
        'passportType' => $passportLabels[$reservation['passport_type']] ?? $reservation['passport_type'],
        'quantity' => (int)$reservation['quantity'],
        'scanCount' => $scanCount,
    ]);
} catch (Throwable $e) {
    error_log('[checkin] ' . $e->getMessage());
    json_error('Something went wrong while recording the check-in.', 500);
}
