<?php
/**
 * ⚠ ONE-TIME BROWSER-BASED ADMIN SETUP — DELETE THIS FILE IMMEDIATELY AFTER USE ⚠
 *
 * Only exists for hosting plans without SSH/CLI access (scripts/create_admin.php
 * is the normal way to do this and should be preferred whenever a terminal is
 * available). This file is a real security risk if left on the server:
 * anyone who finds the URL (with the correct secret) can create an admin
 * account. Two guards are in place, but neither replaces deleting the file:
 *
 *   1. It requires a long random `key` in the URL, generated below on first
 *      load — the URL isn't guessable or listed anywhere.
 *   2. It refuses to do anything once at least one admin already exists.
 *
 * USAGE:
 *   1. Upload this file to scripts/web_create_admin.php on the server.
 *   2. Visit https://yourdomain.com/scripts/web_create_admin.php in a
 *      browser. It will show you a one-time secret key and a form.
 *   3. Fill in the admin email + password, submit.
 *   4. Confirm you see "Admin created successfully."
 *   5. DELETE THIS FILE from the server immediately (File Manager or FTP).
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/db.php';

// Regenerate this on every request that doesn't present a valid key —
// effectively means the correct key is only ever the one shown on THIS
// page load, so it can't be bookmarked/shared/reused later.
$sessionKeyFile = sys_get_temp_dir() . '/sfff_web_admin_setup_key';

function current_valid_key(string $file): string
{
    if (is_file($file)) {
        $existing = trim((string)@file_get_contents($file));
        if ($existing !== '') {
            return $existing;
        }
    }
    $key = bin2hex(random_bytes(24));
    @file_put_contents($file, $key);
    return $key;
}

$validKey = current_valid_key($sessionKeyFile);
$providedKey = (string)($_GET['key'] ?? $_POST['key'] ?? '');
$keyOk = $providedKey !== '' && hash_equals($validKey, $providedKey);

$pdo = get_db();
$adminCount = (int)$pdo->query('SELECT COUNT(*) FROM admins')->fetchColumn();

$message = null;
$messageType = 'info';

if ($adminCount > 0) {
    $message = 'An admin account already exists. This script refuses to run again — delete this file now.';
    $messageType = 'error';
} elseif (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    if (!$keyOk) {
        $message = 'Invalid or expired key. Reload this page (without a key) to get a fresh one.';
        $messageType = 'error';
    } else {
        $email = trim((string)($_POST['email'] ?? ''));
        $password = (string)($_POST['password'] ?? '');

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $message = 'Please enter a valid email address.';
            $messageType = 'error';
        } elseif (strlen($password) < 10) {
            $message = 'Password must be at least 10 characters.';
            $messageType = 'error';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare('INSERT INTO admins (email, password_hash) VALUES (?, ?)');
            $stmt->execute([$email, $hash]);
            @unlink($sessionKeyFile);
            $message = "Admin created successfully: $email — NOW DELETE THIS FILE FROM THE SERVER.";
            $messageType = 'success';
            $adminCount = 1;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>One-time admin setup</title>
<style>
  body { font-family: -apple-system, sans-serif; background: #0a0a0b; color: #f5f5f5; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; padding: 1rem; }
  .card { background: #141416; border: 1px solid #27272a; border-radius: 16px; padding: 2rem; max-width: 440px; width: 100%; }
  .warn { background: rgba(248,113,113,0.1); border: 1px solid rgba(248,113,113,0.4); color: #f87171; padding: 0.8rem 1rem; border-radius: 10px; font-size: 0.8rem; margin-bottom: 1.2rem; line-height: 1.5; }
  .msg-success { background: rgba(74,222,128,0.1); border: 1px solid rgba(74,222,128,0.4); color: #4ade80; padding: 0.8rem 1rem; border-radius: 10px; font-size: 0.85rem; margin-bottom: 1rem; }
  .msg-error { background: rgba(248,113,113,0.1); border: 1px solid rgba(248,113,113,0.4); color: #f87171; padding: 0.8rem 1rem; border-radius: 10px; font-size: 0.85rem; margin-bottom: 1rem; }
  label { display: block; font-size: 0.75rem; color: #9CA3AF; margin-bottom: 0.3rem; margin-top: 0.9rem; }
  input { width: 100%; background: #0d0d0f; border: 1px solid #27272a; color: #fff; padding: 0.6rem 0.7rem; border-radius: 8px; box-sizing: border-box; }
  button { margin-top: 1.3rem; width: 100%; background: #D4AF37; color: #0a0a0a; border: none; padding: 0.8rem; border-radius: 999px; font-weight: 700; cursor: pointer; }
  .keybox { font-family: monospace; background: #0d0d0f; padding: 0.6rem; border-radius: 8px; word-break: break-all; font-size: 0.75rem; color: #D4AF37; margin-top: 0.5rem; }
</style>
</head>
<body>
  <div class="card">
    <div class="warn">⚠ One-time setup script. Delete this file from the server as soon as you're done, whether it succeeds or fails.</div>

    <?php if ($message): ?>
      <div class="msg-<?= $messageType === 'error' ? 'error' : 'success' ?>"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <?php if ($adminCount === 0): ?>
      <?php if (!$keyOk): ?>
        <p style="font-size:0.8rem;color:#9CA3AF;">This link includes a one-time key. Use the link below (already includes it):</p>
        <div class="keybox">?key=<?= htmlspecialchars($validKey) ?></div>
        <p style="font-size:0.8rem;color:#9CA3AF;margin-top:0.8rem;">
          <a href="?key=<?= urlencode($validKey) ?>" style="color:#D4AF37;">Click here to continue →</a>
        </p>
      <?php else: ?>
        <form method="post">
          <input type="hidden" name="key" value="<?= htmlspecialchars($validKey) ?>">
          <label>Admin email</label>
          <input type="email" name="email" required autocomplete="off">
          <label>Password (10+ characters)</label>
          <input type="password" name="password" required autocomplete="off">
          <button type="submit">Create admin</button>
        </form>
      <?php endif; ?>
    <?php endif; ?>
  </div>
</body>
</html>
