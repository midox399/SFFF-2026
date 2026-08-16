<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

require_admin_auth();

$pdo = get_db();

$stats = [
    'passports_total' => 0,
    'passports_pending' => 0,
    'passports_confirmed' => 0,
    'passports_quantity_total' => 0,
    'applications_total' => 0,
    'applications_pending' => 0,
];

try {
    $stats['passports_total'] = (int)$pdo->query('SELECT COUNT(*) FROM passport_reservations')->fetchColumn();
    $stats['passports_pending'] = (int)$pdo->query("SELECT COUNT(*) FROM passport_reservations WHERE status = 'pending'")->fetchColumn();
    $stats['passports_confirmed'] = (int)$pdo->query("SELECT COUNT(*) FROM passport_reservations WHERE status = 'confirmed'")->fetchColumn();
    $stats['passports_quantity_total'] = (int)$pdo->query('SELECT COALESCE(SUM(quantity), 0) FROM passport_reservations')->fetchColumn();
    $stats['applications_total'] = (int)$pdo->query('SELECT COUNT(*) FROM applications')->fetchColumn();
    $stats['applications_pending'] = (int)$pdo->query("SELECT COUNT(*) FROM applications WHERE status = 'pending'")->fetchColumn();

    $recentPassports = $pdo->query(
        'SELECT reference, full_name, email, phone, passport_type, quantity, status, created_at
         FROM passport_reservations ORDER BY created_at DESC LIMIT 5'
    )->fetchAll();

    $recentApplications = $pdo->query(
        'SELECT full_name, email, phone, application_type, status, created_at
         FROM applications ORDER BY created_at DESC LIMIT 5'
    )->fetchAll();

    // Last 14 days of reservation volume, zero-filled for days with no rows.
    $trendRaw = $pdo->query(
        "SELECT DATE(created_at) d, COUNT(*) c FROM passport_reservations
         WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 13 DAY)
         GROUP BY DATE(created_at)"
    )->fetchAll(PDO::FETCH_KEY_PAIR);
    $reservationTrend = [];
    for ($i = 13; $i >= 0; $i--) {
        $day = date('Y-m-d', strtotime("-$i day"));
        $reservationTrend[] = ['date' => $day, 'count' => (int)($trendRaw[$day] ?? 0)];
    }

    $passportTypeBreakdown = $pdo->query(
        'SELECT passport_type, COUNT(*) c FROM passport_reservations GROUP BY passport_type ORDER BY c DESC'
    )->fetchAll();
} catch (Throwable $e) {
    error_log('[admin/index] ' . $e->getMessage());
    $recentPassports = [];
    $recentApplications = [];
    $reservationTrend = [];
    $passportTypeBreakdown = [];
}

$pageTitle = 'Dashboard';
require __DIR__ . '/../includes/admin_layout_head.php';
?>
<h1>Dashboard</h1>

<div class="stat-grid">
  <div class="stat-card">
    <div class="val"><?= $stats['passports_total'] ?></div>
    <div class="label">Réservations Passeport</div>
  </div>
  <div class="stat-card">
    <div class="val"><?= $stats['passports_pending'] ?></div>
    <div class="label">En attente</div>
  </div>
  <div class="stat-card">
    <div class="val"><?= $stats['passports_confirmed'] ?></div>
    <div class="label">Confirmées</div>
  </div>
  <div class="stat-card">
    <div class="val"><?= $stats['passports_quantity_total'] ?></div>
    <div class="label">Passeports (quantité totale)</div>
  </div>
  <div class="stat-card">
    <div class="val"><?= $stats['applications_total'] ?></div>
    <div class="label">Candidatures</div>
  </div>
  <div class="stat-card">
    <div class="val"><?= $stats['applications_pending'] ?></div>
    <div class="label">Candidatures en attente</div>
  </div>
</div>

<h1 class="section-heading">Aperçu</h1>
<div class="chart-grid">
  <div class="chart-card">
    <div class="chart-card-head">
      <h2>Réservations — 14 derniers jours</h2>
    </div>
    <?php
      $trendMax = max(1, max(array_column($reservationTrend, 'count')));
      $trendW = 640; $trendH = 200; $padL = 8; $padR = 8; $padT = 16; $padB = 28;
      $plotW = $trendW - $padL - $padR;
      $plotH = $trendH - $padT - $padB;
      $n = count($reservationTrend);
      $stepX = $n > 1 ? $plotW / ($n - 1) : 0;
      $points = [];
      foreach ($reservationTrend as $i => $pt) {
        $x = $padL + $i * $stepX;
        $y = $padT + $plotH - ($pt['count'] / $trendMax) * $plotH;
        $points[] = [$x, $y, $pt];
      }
      $pathD = '';
      foreach ($points as $i => [$x, $y]) { $pathD .= ($i === 0 ? "M" : "L") . round($x, 1) . " " . round($y, 1) . " "; }
      $areaD = $pathD . "L " . round($points[$n - 1][0], 1) . " " . ($padT + $plotH) . " L " . round($points[0][0], 1) . " " . ($padT + $plotH) . " Z";
      $gridLines = [0, 0.5, 1];
    ?>
    <?php if (!array_sum(array_column($reservationTrend, 'count'))): ?>
      <div class="empty-state">Aucune réservation sur cette période.</div>
    <?php else: ?>
    <svg viewBox="0 0 <?= $trendW ?> <?= $trendH ?>" class="chart-svg" role="img" aria-label="Évolution des réservations sur 14 jours">
      <?php foreach ($gridLines as $g): $gy = $padT + $plotH * (1 - $g); ?>
        <line x1="<?= $padL ?>" y1="<?= $gy ?>" x2="<?= $trendW - $padR ?>" y2="<?= $gy ?>" class="chart-gridline" />
      <?php endforeach; ?>
      <path d="<?= $areaD ?>" class="chart-area" />
      <path d="<?= $pathD ?>" class="chart-line" />
      <?php foreach ($points as $i => [$x, $y, $pt]): ?>
        <circle cx="<?= round($x, 1) ?>" cy="<?= round($y, 1) ?>" r="4" class="chart-dot">
          <title><?= h($pt['date']) ?> — <?= $pt['count'] ?> réservation<?= $pt['count'] === 1 ? '' : 's' ?></title>
        </circle>
      <?php endforeach; ?>
      <?php [$lastX, $lastY, $lastPt] = end($points); ?>
      <text x="<?= round($lastX, 1) ?>" y="<?= round($lastY - 12, 1) ?>" class="chart-end-label" text-anchor="end"><?= $lastPt['count'] ?></text>
    </svg>
    <div class="chart-axis-labels">
      <span><?= h(date('d M', strtotime($reservationTrend[0]['date']))) ?></span>
      <span><?= h(date('d M', strtotime($reservationTrend[$n - 1]['date']))) ?></span>
    </div>
    <?php endif; ?>
  </div>

  <div class="chart-card">
    <div class="chart-card-head">
      <h2>Répartition par type de passeport</h2>
    </div>
    <?php if (!$passportTypeBreakdown): ?>
      <div class="empty-state">Aucune réservation pour le moment.</div>
    <?php else: ?>
      <?php $barMax = max(array_column($passportTypeBreakdown, 'c')); ?>
      <div class="bar-chart">
        <?php foreach ($passportTypeBreakdown as $row): ?>
          <?php $pct = $barMax > 0 ? round(($row['c'] / $barMax) * 100, 1) : 0; ?>
          <div class="bar-row">
            <span class="bar-row-label"><?= h($row['passport_type']) ?></span>
            <div class="bar-track">
              <div class="bar-fill" style="width: <?= $pct ?>%;"></div>
            </div>
            <span class="bar-row-value"><?= (int)$row['c'] ?></span>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</div>

<h1 class="section-heading">Réservations récentes</h1>
<?php if (!$recentPassports): ?>
  <div class="empty-state">Aucune réservation pour le moment.</div>
<?php else: ?>
  <div class="table-scroll">
  <table>
    <thead>
      <tr>
        <th>Référence</th>
        <th>Nom</th>
        <th>E-mail</th>
        <th>Téléphone</th>
        <th>Type</th>
        <th>Qté</th>
        <th>Statut</th>
        <th>Date</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($recentPassports as $r): ?>
        <tr>
          <td><?= h($r['reference']) ?></td>
          <td><?= h($r['full_name']) ?></td>
          <td><?= h($r['email']) ?></td>
          <td><?= h($r['phone']) ?></td>
          <td><?= h($r['passport_type']) ?></td>
          <td><?= (int)$r['quantity'] ?></td>
          <td><span class="badge badge-<?= h($r['status']) ?>"><?= h($r['status']) ?></span></td>
          <td><?= h($r['created_at']) ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  </div>
<?php endif; ?>

<h1 class="section-heading">Candidatures récentes</h1>
<?php if (!$recentApplications): ?>
  <div class="empty-state">Aucune candidature pour le moment.</div>
<?php else: ?>
  <div class="table-scroll">
  <table>
    <thead>
      <tr>
        <th>Nom</th>
        <th>E-mail</th>
        <th>Téléphone</th>
        <th>Type</th>
        <th>Statut</th>
        <th>Date</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($recentApplications as $a): ?>
        <tr>
          <td><?= h($a['full_name']) ?></td>
          <td><?= h($a['email']) ?></td>
          <td><?= h($a['phone'] ?? '—') ?></td>
          <td><?= h($a['application_type']) ?></td>
          <td><span class="badge badge-<?= h($a['status']) ?>"><?= h($a['status']) ?></span></td>
          <td><?= h($a['created_at']) ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  </div>
<?php endif; ?>

<?php require __DIR__ . '/../includes/admin_layout_foot.php'; ?>
