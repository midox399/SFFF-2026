<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

require_admin_auth();

$pdo = get_db();
$flash = null;

const PASSPORT_STATUSES = ['pending', 'confirmed', 'cancelled', 'completed'];

// ---- POST actions: update status / delete ----
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    require_valid_csrf();

    $action = (string)($_POST['action'] ?? '');
    $id = (int)($_POST['id'] ?? 0);

    if ($id > 0 && $action === 'update_status') {
        $status = (string)($_POST['status'] ?? '');
        if (in_array($status, PASSPORT_STATUSES, true)) {
            $stmt = $pdo->prepare('UPDATE passport_reservations SET status = ? WHERE id = ?');
            $stmt->execute([$status, $id]);
            $flash = 'Statut mis à jour.';
        } else {
            $flash = 'Statut invalide.';
        }
    } elseif ($id > 0 && $action === 'delete') {
        $stmt = $pdo->prepare('DELETE FROM passport_reservations WHERE id = ?');
        $stmt->execute([$id]);
        $flash = 'Réservation supprimée.';
    }
}

// ---- GET filters ----
$search = trim((string)($_GET['q'] ?? ''));
$statusFilter = (string)($_GET['status'] ?? '');
if (!in_array($statusFilter, PASSPORT_STATUSES, true)) {
    $statusFilter = '';
}

$where = [];
$params = [];
if ($search !== '') {
    $where[] = '(reference LIKE ? OR full_name LIKE ? OR email LIKE ? OR phone LIKE ?)';
    $like = '%' . $search . '%';
    array_push($params, $like, $like, $like, $like);
}
if ($statusFilter !== '') {
    $where[] = 'status = ?';
    $params[] = $statusFilter;
}
$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

$stmt = $pdo->prepare("SELECT * FROM passport_reservations $whereSql ORDER BY created_at DESC LIMIT 200");
$stmt->execute($params);
$rows = $stmt->fetchAll();

$pageTitle = 'Réservations Passeport';
require __DIR__ . '/../includes/admin_layout_head.php';
?>
<h1>Réservations Passeport</h1>

<?php if ($flash): ?>
  <div class="error-box" style="background:rgba(74,222,128,0.1);border-color:rgba(74,222,128,0.4);color:#4ade80;"><?= h($flash) ?></div>
<?php endif; ?>

<form method="get" class="toolbar">
  <input type="search" name="q" placeholder="Rechercher (nom, email, téléphone, référence)" value="<?= h($search) ?>" style="min-width:280px;">
  <select name="status">
    <option value="">Tous les statuts</option>
    <?php foreach (PASSPORT_STATUSES as $s): ?>
      <option value="<?= h($s) ?>" <?= $s === $statusFilter ? 'selected' : '' ?>><?= h($s) ?></option>
    <?php endforeach; ?>
  </select>
  <button type="submit" class="secondary">Filtrer</button>
  <?php if ($search !== '' || $statusFilter !== ''): ?>
    <a href="passports.php" class="btn secondary">Réinitialiser</a>
  <?php endif; ?>
</form>

<?php if (!$rows): ?>
  <div class="empty-state">Aucune réservation ne correspond.</div>
<?php else: ?>
  <div class="table-scroll">
  <table>
    <thead>
      <tr>
        <th>Référence</th>
        <th>Nom</th>
        <th>E-mail</th>
        <th>Téléphone</th>
        <th>Qté</th>
        <th>Type</th>
        <th>Montant</th>
        <th>Paiement</th>
        <th>Statut</th>
        <th>Date</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td><?= h($r['reference']) ?></td>
          <td><?= h($r['full_name']) ?></td>
          <td><?= h($r['email']) ?></td>
          <td><?= h($r['phone']) ?></td>
          <td><?= (int)$r['quantity'] ?></td>
          <td><?= h($r['passport_type']) ?></td>
          <td><?= h(number_format((float)$r['amount_due'], 2)) ?> <?= h($r['currency']) ?></td>
          <td><span class="badge badge-<?= h($r['payment_status']) ?>"><?= h($r['payment_status']) ?></span></td>
          <td><span class="badge badge-<?= h($r['status']) ?>"><?= h($r['status']) ?></span></td>
          <td><?= h($r['created_at']) ?></td>
          <td style="white-space:nowrap;">
            <form method="post" style="display:inline-flex;gap:0.3rem;align-items:center;">
              <?= csrf_field() ?>
              <input type="hidden" name="action" value="update_status">
              <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
              <select name="status" onchange="this.form.submit()">
                <?php foreach (PASSPORT_STATUSES as $s): ?>
                  <option value="<?= h($s) ?>" <?= $s === $r['status'] ? 'selected' : '' ?>><?= h($s) ?></option>
                <?php endforeach; ?>
              </select>
            </form>
            <form method="post" style="display:inline;" onsubmit="return confirm('Supprimer définitivement cette réservation ?');">
              <?= csrf_field() ?>
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
              <button type="submit" class="danger">Suppr.</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  </div>
<?php endif; ?>

<?php require __DIR__ . '/../includes/admin_layout_foot.php'; ?>
