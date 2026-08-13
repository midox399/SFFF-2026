<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

require_admin_auth();

$pdo = get_db();
$flash = null;

const APPLICATION_STATUSES = ['pending', 'reviewing', 'approved', 'rejected', 'completed'];

// ---- POST actions: update status / delete ----
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    require_valid_csrf();

    $action = (string)($_POST['action'] ?? '');
    $id = (int)($_POST['id'] ?? 0);

    if ($id > 0 && $action === 'update_status') {
        $status = (string)($_POST['status'] ?? '');
        if (in_array($status, APPLICATION_STATUSES, true)) {
            $stmt = $pdo->prepare('UPDATE applications SET status = ? WHERE id = ?');
            $stmt->execute([$status, $id]);
            $flash = 'Statut mis à jour.';
        } else {
            $flash = 'Statut invalide.';
        }
    } elseif ($id > 0 && $action === 'delete') {
        $stmt = $pdo->prepare('DELETE FROM applications WHERE id = ?');
        $stmt->execute([$id]);
        $flash = 'Candidature supprimée.';
    }
}

// ---- GET filters ----
$search = trim((string)($_GET['q'] ?? ''));
$statusFilter = (string)($_GET['status'] ?? '');
if (!in_array($statusFilter, APPLICATION_STATUSES, true)) {
    $statusFilter = '';
}

$where = [];
$params = [];
if ($search !== '') {
    $where[] = '(full_name LIKE ? OR email LIKE ? OR phone LIKE ? OR application_type LIKE ?)';
    $like = '%' . $search . '%';
    array_push($params, $like, $like, $like, $like);
}
if ($statusFilter !== '') {
    $where[] = 'status = ?';
    $params[] = $statusFilter;
}
$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

$stmt = $pdo->prepare("SELECT * FROM applications $whereSql ORDER BY created_at DESC LIMIT 200");
$stmt->execute($params);
$rows = $stmt->fetchAll();

$pageTitle = 'Candidatures';
require __DIR__ . '/../includes/admin_layout_head.php';
?>
<h1>Candidatures (Espace Professionnels)</h1>

<?php if ($flash): ?>
  <div class="error-box" style="background:rgba(74,222,128,0.1);border-color:rgba(74,222,128,0.4);color:#4ade80;"><?= h($flash) ?></div>
<?php endif; ?>

<form method="get" class="toolbar">
  <input type="search" name="q" placeholder="Rechercher (nom, email, téléphone, type)" value="<?= h($search) ?>" style="min-width:280px;">
  <select name="status">
    <option value="">Tous les statuts</option>
    <?php foreach (APPLICATION_STATUSES as $s): ?>
      <option value="<?= h($s) ?>" <?= $s === $statusFilter ? 'selected' : '' ?>><?= h($s) ?></option>
    <?php endforeach; ?>
  </select>
  <button type="submit" class="secondary">Filtrer</button>
  <?php if ($search !== '' || $statusFilter !== ''): ?>
    <a href="applications.php" class="btn secondary">Réinitialiser</a>
  <?php endif; ?>
</form>

<?php if (!$rows): ?>
  <div class="empty-state">Aucune candidature ne correspond.</div>
<?php else: ?>
  <div class="table-scroll">
  <table>
    <thead>
      <tr>
        <th>Nom</th>
        <th>E-mail</th>
        <th>Téléphone</th>
        <th>Type</th>
        <th>Message</th>
        <th>Statut</th>
        <th>Date</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($rows as $a): ?>
        <tr>
          <td><?= h($a['full_name']) ?></td>
          <td><?= h($a['email']) ?></td>
          <td><?= h($a['phone'] ?? '—') ?></td>
          <td><?= h($a['application_type']) ?></td>
          <td style="max-width:220px;white-space:pre-wrap;"><?= h($a['message'] ?? '') ?></td>
          <td><span class="badge badge-<?= h($a['status']) ?>"><?= h($a['status']) ?></span></td>
          <td><?= h($a['created_at']) ?></td>
          <td style="white-space:nowrap;">
            <form method="post" style="display:inline-flex;gap:0.3rem;align-items:center;">
              <?= csrf_field() ?>
              <input type="hidden" name="action" value="update_status">
              <input type="hidden" name="id" value="<?= (int)$a['id'] ?>">
              <select name="status" onchange="this.form.submit()">
                <?php foreach (APPLICATION_STATUSES as $s): ?>
                  <option value="<?= h($s) ?>" <?= $s === $a['status'] ? 'selected' : '' ?>><?= h($s) ?></option>
                <?php endforeach; ?>
              </select>
            </form>
            <form method="post" style="display:inline;" onsubmit="return confirm('Supprimer définitivement cette candidature ?');">
              <?= csrf_field() ?>
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="id" value="<?= (int)$a['id'] ?>">
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
