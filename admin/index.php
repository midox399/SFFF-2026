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
} catch (Throwable $e) {
    error_log('[admin/index] ' . $e->getMessage());
    $recentPassports = [];
    $recentApplications = [];
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

<h1 style="font-size:1.1rem;">Réservations récentes</h1>
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

<h1 style="font-size:1.1rem;margin-top:2rem;">Candidatures récentes</h1>
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
