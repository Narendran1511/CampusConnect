<?php
require_once __DIR__ . '/includes/auth.php';
redirect_if_logged_in();

$resetLink = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_verify()) {
    $idf = post('identifier');
    $st = db()->prepare('SELECT user_id FROM users WHERE (email = ? OR student_id = ?) AND role = "student" AND account_status = "active" LIMIT 1');
    $st->execute([$idf, $idf]);
    if ($uid = $st->fetchColumn()) {
        $token = bin2hex(random_bytes(32));
        db()->prepare('INSERT INTO password_resets (user_id, token_hash, expires_at) VALUES (?,?,?)')
            ->execute([$uid, hash('sha256', $token), date('Y-m-d H:i:s', time() + 3600)]);
        // DEMO MODE: there is no mail server on a local XAMPP install, so the link is shown on screen.
        // In production, e-mail this link to the student instead and never display it.
        $resetLink = url('reset_password.php?token=' . $token);
    }
    flash('success', 'If an account matches, a password reset link has been generated.');
}
$layout = 'public'; $pageTitle = 'Forgot password';
require __DIR__ . '/includes/header.php';
?>
<div class="auth-card">
  <div class="auth-head"><span class="logo">C</span><h1>Reset your password</h1><p>Enter your student ID or email.</p></div>
  <form method="post" data-validate novalidate>
    <?= csrf_field() ?>
    <div class="field"><label for="identifier">Student ID or email</label><input id="identifier" name="identifier" required></div>
    <button class="btn btn-primary btn-block" type="submit">Generate reset link</button>
  </form>
  <?php if ($resetLink): ?>
    <div class="alert alert-info">Demo mode (no mail server): <a href="<?= e($resetLink) ?>">open your reset link</a>. It expires in 1 hour.</div>
  <?php endif; ?>
  <p class="auth-links"><a href="<?= e(url('student_login.php')) ?>">Back to login</a></p>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
