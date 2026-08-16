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
<div class="login-shell">
  <div class="login-card">
    <div class="login-header">
      <p class="eyebrow">Accès Restreint</p>
      <h1>SFFF 2026 — Admin Access</h1>
      <div class="login-divider"></div>
    </div>
    <?php if ($error): ?>
      <div class="error-box"><?= h($error) ?></div>
    <?php endif; ?>
    <form method="post" class="login-form">
      <?= csrf_field() ?>
      <div class="login-field">
        <label>E-mail</label>
        <input type="email" name="email" required autocomplete="username">
      </div>
      <div class="login-field">
        <label>Mot de passe</label>
        <input type="password" name="password" required autocomplete="current-password">
      </div>
      <button type="submit" class="login-submit">Se connecter</button>
    </form>
  </div>
</div>
<?php require __DIR__ . '/../includes/admin_layout_foot.php'; ?>
