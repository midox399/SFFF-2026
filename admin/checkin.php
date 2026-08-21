<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

require_admin_auth();

$pdo = get_db();

// Last 10 scans, for quick visual confirmation staff didn't just double-scan
// the same passport by mistake.
$recentScans = $pdo->query(
    "SELECT c.scanned_at, r.reference, r.full_name, r.passport_type
     FROM checkin_log c
     JOIN passport_reservations r ON r.id = c.reservation_id
     ORDER BY c.scanned_at DESC LIMIT 10"
)->fetchAll();

$pageTitle = 'Scan Entrée';
require __DIR__ . '/../includes/admin_layout_head.php';
?>
<h1>Scan Entrée</h1>
<p style="color:var(--muted);font-size:0.85rem;margin-top:-1rem;margin-bottom:1.5rem;">
  Scannez le QR code du billet du client, ou saisissez la référence manuellement.
  Confirme que le passeport a bien été payé.
</p>

<div style="max-width:480px;">
  <div id="scanner-container" style="background:var(--panel);border:1px solid var(--border);border-radius:14px;padding:1rem;margin-bottom:1rem;">
    <div id="qr-reader" style="width:100%;"></div>
    <p id="scanner-status" style="font-size:0.78rem;color:var(--muted);text-align:center;margin:0.6rem 0 0;">Initialisation de la caméra…</p>
  </div>

  <form id="manualForm" class="toolbar" style="margin-bottom:1.5rem;">
    <input type="text" id="manualRef" placeholder="Ou saisir la référence (GV-2026-XXXXX)" autocomplete="off" style="flex:1;">
    <button type="submit">Vérifier</button>
  </form>

  <div id="result"></div>
</div>

<h1 class="section-heading">Derniers scans</h1>
<?php if (!$recentScans): ?>
  <div class="empty-state">Aucun scan pour le moment.</div>
<?php else: ?>
  <div class="table-scroll">
  <table>
    <thead>
      <tr>
        <th>Référence</th>
        <th>Nom</th>
        <th>Type</th>
        <th>Heure</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($recentScans as $s): ?>
        <tr>
          <td><?= h($s['reference']) ?></td>
          <td><?= h($s['full_name']) ?></td>
          <td><?= h($s['passport_type']) ?></td>
          <td><?= h($s['scanned_at']) ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  </div>
<?php endif; ?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/html5-qrcode/2.3.8/html5-qrcode.min.js"
  integrity="sha384-c9d8RFSL+u3exBOJ4Yp3HUJXS4znl9f+z66d1y54ig+ea249SpqR+w1wyvXz/lk+"
  crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<script>
  const resultEl = document.getElementById('result');
  const statusEl = document.getElementById('scanner-status');

  function renderResult(ok, data) {
    if (ok) {
      resultEl.innerHTML = `
        <div style="background:rgba(74,222,128,0.1);border:1px solid rgba(74,222,128,0.4);border-radius:12px;padding:1rem;">
          <div style="color:#4ade80;font-weight:800;font-size:0.95rem;margin-bottom:0.4rem;">✓ Accès autorisé</div>
          <div style="font-size:0.85rem;"><strong>${data.fullName}</strong></div>
          <div style="font-size:0.8rem;color:var(--muted);">${data.passportType} · Qté ${data.quantity} · Réf ${data.reference}</div>
          <div style="font-size:0.75rem;color:var(--muted);margin-top:0.4rem;">Scan n°${data.scanCount} pour ce passeport</div>
        </div>`;
    } else {
      resultEl.innerHTML = `
        <div style="background:rgba(248,113,113,0.1);border:1px solid rgba(248,113,113,0.4);border-radius:12px;padding:1rem;">
          <div style="color:#f87171;font-weight:800;font-size:0.95rem;margin-bottom:0.4rem;">✗ Accès refusé</div>
          <div style="font-size:0.85rem;">${data}</div>
        </div>`;
    }
  }

  async function checkReference(reference) {
    if (!reference) return;
    resultEl.innerHTML = '<p style="color:var(--muted);font-size:0.85rem;">Vérification…</p>';
    try {
      const res = await fetch('../api/checkin.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ reference })
      });
      const data = await res.json();
      if (res.ok && data.success) {
        renderResult(true, data);
        setTimeout(() => window.location.reload(), 2500);
      } else {
        renderResult(false, data.error || 'Erreur inconnue.');
      }
    } catch (err) {
      renderResult(false, 'Erreur réseau.');
    }
  }

  document.getElementById('manualForm').addEventListener('submit', (e) => {
    e.preventDefault();
    const val = document.getElementById('manualRef').value.trim();
    checkReference(val);
  });

  // Camera scanner — degrades gracefully if the device/browser has no
  // camera or denies permission (manual reference entry still works).
  if (typeof Html5Qrcode !== 'undefined') {
    const scanner = new Html5Qrcode('qr-reader');
    let lastScanned = '';
    let lastScanTime = 0;

    scanner.start(
      { facingMode: 'environment' },
      { fps: 10, qrbox: { width: 220, height: 220 } },
      (decodedText) => {
        const now = Date.now();
        // Debounce: ignore the same code re-firing for 3s (the camera
        // keeps scanning continuously while the code is in frame).
        if (decodedText === lastScanned && now - lastScanTime < 3000) return;
        lastScanned = decodedText;
        lastScanTime = now;
        checkReference(decodedText.trim());
      },
      () => { /* per-frame "no QR found" noise — ignore */ }
    ).then(() => {
      statusEl.textContent = 'Caméra active — visez le QR code du billet.';
    }).catch((err) => {
      statusEl.textContent = 'Caméra indisponible — utilisez la saisie manuelle ci-dessous.';
    });
  } else {
    statusEl.textContent = 'Scanner indisponible — utilisez la saisie manuelle ci-dessous.';
  }
</script>

<?php require __DIR__ . '/../includes/admin_layout_foot.php'; ?>
