<?php
/**
 * GET /ticket.php?ref=GV-2026-XXXXX
 *
 * Shows a printable/screenshot-able ticket for a PAID reservation. This is
 * the page a real payment provider's success-return URL should point to
 * (see includes/payment_provider.php), and where payment-stub-checkout.php
 * redirects locally after simulating a successful payment.
 *
 * Deliberately does NOT show anything for a reservation that isn't paid
 * yet — this page's whole point is proof of a completed passport purchase,
 * and the reference itself is a random 5-character code (see
 * includes/http.php's generate_reference()), not a guessable sequential
 * id, so this is safe to be reachable without a login.
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

$isPaid = $reservation && $reservation['payment_status'] === 'paid';

$passportLabels = [
    'standard' => 'Passeport Normal',
    'pro' => 'Passeport Pro',
    'diplomatique' => 'Passeport Diplomatique',
    'vip' => 'VIP Gold',
];
$passportLabel = $isPaid ? ($passportLabels[$reservation['passport_type']] ?? $reservation['passport_type']) : '';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="robots" content="noindex, nofollow">
  <title><?= $isPaid ? 'Votre Passeport — ' . htmlspecialchars($reservation['reference']) : 'Billet introuvable' ?> — SFFF 2026</title>
  <style>
    :root {
      --bg: #070708;
      --panel: #141416;
      --gold: #D4AF37;
      --gold-hi: #F3E5AB;
      --border: rgba(212, 175, 55, 0.35);
    }

    * { box-sizing: border-box; }

    body {
      margin: 0;
      min-height: 100vh;
      background: var(--bg);
      color: #fff;
      font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 2rem 1rem;
    }

    .ticket {
      width: 100%;
      max-width: 420px;
      background: linear-gradient(160deg, #141416 0%, #0a0a0b 100%);
      border: 1px solid var(--border);
      border-radius: 20px;
      overflow: hidden;
      box-shadow: 0 20px 60px rgba(0, 0, 0, 0.6), 0 0 0 1px rgba(212, 175, 55, 0.08);
    }

    .ticket-head {
      background: linear-gradient(135deg, #E5C158, var(--gold));
      color: #0a0a0a;
      padding: 1.5rem 1.5rem 1.2rem;
      text-align: center;
    }

    .ticket-head .brand {
      font-size: 0.7rem;
      font-weight: 800;
      letter-spacing: 0.25em;
      text-transform: uppercase;
      opacity: 0.75;
      margin-bottom: 0.3rem;
    }

    .ticket-head h1 {
      margin: 0;
      font-size: 1.4rem;
      font-weight: 800;
    }

    .ticket-body {
      padding: 1.6rem 1.5rem;
    }

    .status-badge {
      display: inline-flex;
      align-items: center;
      gap: 0.4rem;
      background: rgba(74, 222, 128, 0.12);
      color: #4ade80;
      border: 1px solid rgba(74, 222, 128, 0.35);
      padding: 0.3rem 0.7rem;
      border-radius: 999px;
      font-size: 0.72rem;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.05em;
      margin-bottom: 1.2rem;
    }

    .field {
      display: flex;
      justify-content: space-between;
      align-items: baseline;
      padding: 0.6rem 0;
      border-bottom: 1px dashed rgba(255, 255, 255, 0.08);
      font-size: 0.9rem;
    }

    .field:last-of-type { border-bottom: none; }

    .field .label {
      color: #9CA3AF;
      font-size: 0.75rem;
      text-transform: uppercase;
      letter-spacing: 0.05em;
    }

    .field .value {
      font-weight: 700;
      text-align: right;
    }

    .reference-block {
      margin-top: 1.4rem;
      padding: 1.2rem;
      background: rgba(212, 175, 55, 0.06);
      border: 1px solid var(--border);
      border-radius: 14px;
      text-align: center;
    }

    .reference-block .ref-label {
      font-size: 0.68rem;
      color: #9CA3AF;
      text-transform: uppercase;
      letter-spacing: 0.15em;
      margin-bottom: 0.4rem;
    }

    .reference-block .ref-value {
      font-family: "SFMono-Regular", Consolas, monospace;
      font-size: 1.3rem;
      font-weight: 800;
      color: var(--gold-hi);
      letter-spacing: 0.05em;
      margin-bottom: 1rem;
    }

    #qrcode {
      display: flex;
      justify-content: center;
      margin-bottom: 0.6rem;
    }

    #qrcode img, #qrcode canvas {
      border-radius: 10px;
      background: #fff;
      padding: 10px;
    }

    .hint {
      font-size: 0.72rem;
      color: #6b7280;
      text-align: center;
      margin-top: 1rem;
      line-height: 1.5;
    }

    .actions {
      display: flex;
      gap: 0.6rem;
      margin-top: 1.4rem;
    }

    .actions button, .actions a {
      flex: 1;
      text-align: center;
      padding: 0.75rem;
      border-radius: 999px;
      font-weight: 700;
      font-size: 0.82rem;
      text-decoration: none;
      cursor: pointer;
      border: none;
    }

    .btn-gold {
      background: linear-gradient(135deg, #E5C158, var(--gold));
      color: #0a0a0a;
    }

    .btn-ghost {
      background: transparent;
      border: 1px solid rgba(255, 255, 255, 0.15) !important;
      color: #fff;
    }

    .not-found {
      text-align: center;
      padding: 2rem 1.5rem;
    }

    .not-found p { color: #9CA3AF; font-size: 0.9rem; line-height: 1.6; }

    @media print {
      body { background: #fff; padding: 0; }
      .actions { display: none; }
      .ticket { box-shadow: none; border: 1px solid #ccc; }
    }
  </style>
</head>
<body>
  <div class="ticket">
    <div class="ticket-head">
      <div class="brand">SFFF 2026 · Global Village Tunisia</div>
      <h1><?= $isPaid ? 'Passeport Confirmé' : 'Billet Introuvable' ?></h1>
    </div>
    <div class="ticket-body">
      <?php if (!$isPaid): ?>
        <div class="not-found">
          <p>
            Aucun passeport payé ne correspond à cette référence.<br>
            Si vous venez de payer, patientez quelques instants et rafraîchissez
            cette page — la confirmation peut prendre un court instant.
          </p>
          <div class="actions">
            <a href="index.html" class="btn-ghost">Retour au site</a>
          </div>
        </div>
      <?php else: ?>
        <span class="status-badge">✓ Paiement confirmé</span>

        <div class="field">
          <span class="label">Titulaire</span>
          <span class="value"><?= htmlspecialchars($reservation['full_name']) ?></span>
        </div>
        <div class="field">
          <span class="label">Type de passeport</span>
          <span class="value"><?= htmlspecialchars($passportLabel) ?></span>
        </div>
        <div class="field">
          <span class="label">Quantité</span>
          <span class="value"><?= (int)$reservation['quantity'] ?></span>
        </div>
        <div class="field">
          <span class="label">Montant payé</span>
          <span class="value"><?= htmlspecialchars(number_format((float)$reservation['amount_due'], 2)) ?> <?= htmlspecialchars($reservation['currency']) ?></span>
        </div>

        <div class="reference-block">
          <div class="ref-label">Référence de réservation</div>
          <div class="ref-value"><?= htmlspecialchars($reservation['reference']) ?></div>
          <div id="qrcode" data-ref="<?= htmlspecialchars($reservation['reference']) ?>"></div>
        </div>

        <p class="hint">
          Présentez ce billet (écran ou impression) à l'entrée du festival.
          Conservez une capture d'écran par sécurité.
        </p>

        <div class="actions">
          <button type="button" class="btn-gold" onclick="window.print()">Imprimer / Enregistrer en PDF</button>
          <a href="index.html" class="btn-ghost">Retour au site</a>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <?php if ($isPaid): ?>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
  <script>
    (function () {
      var el = document.getElementById('qrcode');
      if (!el || typeof QRCode === 'undefined') return;
      new QRCode(el, {
        text: el.dataset.ref,
        width: 160,
        height: 160,
        colorDark: '#0a0a0b',
        colorLight: '#ffffff',
        correctLevel: QRCode.CorrectLevel.M
      });
    })();
  </script>
  <?php endif; ?>
</body>
</html>
