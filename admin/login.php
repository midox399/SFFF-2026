<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

start_secure_session();

// Already logged in — go straight to the dashboard.
if (current_admin_id() !== null) {
    header('Location: index.php');
    exit;
}

$error = null;

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    require_valid_csrf();

    $email = trim((string)($_POST['email'] ?? ''));
    $password = (string)($_POST['password'] ?? '');

    if ($email === '' || $password === '') {
        $error = 'Veuillez renseigner votre e-mail et votre mot de passe.';
    } else {
        login_rate_limit_check($email);

        try {
            $pdo = get_db();
            $stmt = $pdo->prepare('SELECT id, password_hash FROM admins WHERE email = ? LIMIT 1');
            $stmt->execute([$email]);
            $admin = $stmt->fetch();

            if ($admin && password_verify($password, $admin['password_hash'])) {
                login_rate_limit_reset($email);
                session_regenerate_id(true);
                $_SESSION['admin_id'] = (int)$admin['id'];

                $update = $pdo->prepare('UPDATE admins SET last_login_at = NOW() WHERE id = ?');
                $update->execute([$admin['id']]);

                header('Location: index.php');
                exit;
            }

            // Deliberately identical message whether the email doesn't exist
            // or the password is wrong — never reveal which one it was.
            $error = 'E-mail ou mot de passe incorrect.';
        } catch (Throwable $e) {
            error_log('[admin/login] ' . $e->getMessage());
            $error = 'Une erreur est survenue. Veuillez réessayer.';
        }
    }
}

$pageTitle = 'Connexion';
require __DIR__ . '/../includes/admin_layout_head.php';
?>
<div style="max-width:380px;margin:4rem auto;">
  <h1>Connexion Admin</h1>
  <?php if ($error): ?>
    <div class="error-box"><?= h($error) ?></div>
  <?php endif; ?>
  <form method="post" style="display:flex;flex-direction:column;gap:0.9rem;">
    <?= csrf_field() ?>
    <div>
      <label style="display:block;font-size:0.75rem;color:var(--muted);margin-bottom:0.3rem;">E-mail</label>
      <input type="email" name="email" required autocomplete="username" style="width:100%;">
    </div>
    <div>
      <label style="display:block;font-size:0.75rem;color:var(--muted);margin-bottom:0.3rem;">Mot de passe</label>
      <input type="password" name="password" required autocomplete="current-password" style="width:100%;">
    </div>
    <button type="submit">Se connecter</button>
  </form>
</div>
<?php require __DIR__ . '/../includes/admin_layout_foot.php'; ?>
