<?php
require_once __DIR__ . '/includes/auth.php';
redirect_if_logged_in();

$token = (string)($_GET['token'] ?? $_POST['token'] ?? '');
$row = null;
if (preg_match('/^[a-f0-9]{64}$/', $token)) {
    $st = db()->prepare('SELECT * FROM password_resets WHERE token_hash = ? AND used = 0 AND expires_at > ? LIMIT 1');
    $st->execute([hash('sha256', $token), date('Y-m-d H:i:s')]);
    $row = $st->fetch();
}
$errors = [];
if ($row && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $pw = (string)($_POST['password'] ?? ''); $pw2 = (string)($_POST['confirm_password'] ?? '');
    if (!csrf_verify()) $errors[] = 'Your session expired. Please try again.';
    if (strlen($pw) < 8 || !preg_match('/[A-Za-z]/', $pw) || !preg_match('/\d/', $pw)) $errors[] = 'Password must be at least 8 characters and include a letter and a number.';
    if ($pw !== $pw2) $errors[] = 'Passwords do not match.';
    if (!$errors) {
        $pdo = db(); $pdo->beginTransaction();
        $pdo->prepare('UPDATE users SET password_hash = ? WHERE user_id = ? AND role = "student"')->execute([password_hash($pw, PASSWORD_DEFAULT), $row['user_id']]);
        $pdo->prepare('UPDATE password_resets SET used = 1 WHERE user_id = ?')->execute([$row['user_id']]);
        $pdo->commit();
        flash('success', 'Password updated. Please log in with your new password.');
        redirect('student_login.php');
    }
}
$layout = 'public'; $pageTitle = 'Set a new password';
require __DIR__ . '/includes/header.php';
foreach ($errors as $er) echo '<div class="alert alert-error">' . e($er) . '</div>';
?>
<div class="auth-card">
  <div class="auth-head"><span class="logo">C</span><h1>Set a new password</h1></div>
<?php if (!$row): ?>
  <div class="alert alert-error">This reset link is invalid or has expired.</div>
  <p class="auth-links"><a href="<?= e(url('forgot_password.php')) ?>">Request a new link</a></p>
<?php else: ?>
  <form method="post" data-validate novalidate>
    <?= csrf_field() ?><input type="hidden" name="token" value="<?= e($token) ?>">
    <div class="field"><label for="password">New password</label>
      <div class="pw-wrap"><input type="password" id="password" name="password" required minlength="8" autocomplete="new-password">
      <button type="button" class="pw-toggle" data-toggle-password="password" aria-label="Show password">Show</button></div></div>
    <div class="field"><label for="confirm_password">Confirm new password</label>
      <input type="password" id="confirm_password" name="confirm_password" required data-match="password" autocomplete="new-password"></div>
    <button class="btn btn-primary btn-block" type="submit">Update password</button>
  </form>
<?php endif; ?>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
