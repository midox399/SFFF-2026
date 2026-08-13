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
          const res = await fetch('api/payment-webhook.php', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'X-Webhook-Secret': <?= json_encode(defined('PAYMENT_WEBHOOK_SECRET') ? PAYMENT_WEBHOOK_SECRET : '') ?>
            },
            body: JSON.stringify({
              reference: <?= json_encode($reference) ?>,
              event_id: 'stub-' + Date.now(),
              event_type: 'payment.succeeded',
              provider: 'stub'
            })
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
