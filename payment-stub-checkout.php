<?php
/**
 * FOR LOCAL TESTING ONLY — simulates a payment provider's hosted checkout
 * page. Delete this file once a real gateway (Konnect/Paymee) is wired up
 * in includes/payment_provider.php — no real provider works this way; a
 * real one redirects to *their* domain, not back into this codebase.
 *
 * Clicking "Simulate successful payment" here POSTs a fake webhook to
 * api/payment-webhook.php exactly like a real provider would server-to-
 * server, then redirects back to the site.
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/db.php';

$reference = trim((string)($_GET['ref'] ?? ''));

// This endpoint performs the actual "webhook call" server-side, so
// PAYMENT_WEBHOOK_SECRET never has to be sent to (or read from) the
// browser. The page's own JS below just POSTs here with no secret
// attached; this block does the real server-to-server call and relays
// the result back as JSON. Never echo PAYMENT_WEBHOOK_SECRET into any
// HTML/JS output — this is the one place it's used, entirely server-side.
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && ($_GET['action'] ?? '') === 'simulate') {
    header('Content-Type: application/json; charset=utf-8');

    if ($reference === '') {
        http_response_code(422);
        echo json_encode(['success' => false, 'error' => 'Missing reference.']);
        exit;
    }

    $pdo = get_db(); // loads config/database.php, defining PAYMENT_WEBHOOK_SECRET
    if (!defined('PAYMENT_WEBHOOK_SECRET')) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Webhook secret is not configured.']);
        exit;
    }

    $payload = json_encode([
        'reference' => $reference,
        'event_id' => 'stub-' . time() . '-' . bin2hex(random_bytes(4)),
        'event_type' => 'payment.succeeded',
        'provider' => 'stub',
    ]);

    $selfUrl = (($_SERVER['HTTPS'] ?? '') !== '' && ($_SERVER['HTTPS'] ?? 'off') !== 'off' ? 'https://' : 'http://')
        . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']) . '/api/payment-webhook.php';

    $ch = curl_init($selfUrl);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'X-Webhook-Secret: ' . PAYMENT_WEBHOOK_SECRET,
        ],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    http_response_code($httpCode ?: 502);
    echo $response !== false ? $response : json_encode(['success' => false, 'error' => 'Webhook relay failed.']);
    exit;
}

$reservation = null;

if ($reference !== '') {
    $pdo = get_db();
    $stmt = $pdo->prepare('SELECT * FROM passport_reservations WHERE reference = ? LIMIT 1');
    $stmt->execute([$reference]);
    $reservation = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Paiement (simulation locale)</title>
  <style>
    body { font-family: -apple-system, sans-serif; background: #0a0a0b; color: #f5f5f5; display: flex; align-items: center; justify-content: center; height: 100vh; margin: 0; }
    .card { background: #141416; border: 1px solid #27272a; border-radius: 16px; padding: 2rem; max-width: 420px; text-align: center; }
    .warn { background: rgba(212,175,55,0.1); border: 1px solid rgba(212,175,55,0.4); color: #D4AF37; padding: 0.6rem 1rem; border-radius: 8px; font-size: 0.8rem; margin-bottom: 1.2rem; }
    button { background: #D4AF37; color: #0a0a0a; border: none; padding: 0.8rem 1.5rem; border-radius: 999px; font-weight: 700; cursor: pointer; font-size: 0.9rem; }
    .amount { font-size: 1.8rem; font-weight: 800; color: #D4AF37; margin: 1rem 0; }
  </style>
</head>
<body>
  <div class="card">
    <div class="warn">⚠ Simulation locale — pas un vrai paiement</div>
    <?php if (!$reservation): ?>
      <p>Réservation introuvable.</p>
    <?php else: ?>
      <p>Réservation <strong><?= htmlspecialchars($reservation['reference']) ?></strong></p>
      <div class="amount"><?= htmlspecialchars((string)$reservation['amount_due']) ?> <?= htmlspecialchars($reservation['currency']) ?></div>
      <form id="payForm">
        <button type="submit">Simuler un paiement réussi</button>
      </form>
      <p id="status" style="font-size:0.8rem;color:#9CA3AF;margin-top:1rem;"></p>
    <?php endif; ?>
  </div>

  <script>
    const form = document.getElementById('payForm');
    if (form) {
      form.addEventListener('submit', async (e) => {
        e.preventDefault();
        document.getElementById('status').textContent = 'Envoi du webhook…';
        try {
          // Relayed server-side (see the PHP block at the top of this file)
          // so the real webhook secret never has to reach the browser.
          const res = await fetch('payment-stub-checkout.php?action=simulate&ref=' + encodeURIComponent(<?= json_encode($reference) ?>), {
            method: 'POST'
          });
          const data = await res.json();
          if (data.success) {
            document.getElementById('status').textContent = 'Paiement confirmé, redirection…';
            setTimeout(() => { window.location.href = 'ticket.php?ref=' + encodeURIComponent(<?= json_encode($reference) ?>); }, 1200);
          } else {
            document.getElementById('status').textContent = 'Erreur: ' + (data.error || 'inconnue');
          }
        } catch (err) {
          document.getElementById('status').textContent = 'Erreur réseau.';
        }
      });
    }
  </script>
</body>
</html>
