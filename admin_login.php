<?php
require_once __DIR__ . '/includes/auth.php';
redirect_if_logged_in();
$layout = 'public'; $pageTitle = 'Administrator login';
require __DIR__ . '/includes/header.php';
?>
<div class="auth-card auth-admin">
  <div class="auth-head"><span class="logo">C</span><h1>Administrator login</h1><p>For authorized college staff only.</p></div>
  <form method="post" action="<?= e(url('actions/login.php')) ?>" data-validate novalidate>
    <?= csrf_field() ?>
    <input type="hidden" name="role" value="admin">
    <div class="field">
      <label for="identifier">Admin email</label>
      <input type="email" id="identifier" name="identifier" required autocomplete="username" value="<?= e(old('identifier')) ?>">
    </div>
    <div class="field">
      <label for="password">Password</label>
      <div class="pw-wrap">
        <input type="password" id="password" name="password" required autocomplete="current-password">
        <button type="button" class="pw-toggle" data-toggle-password="password" aria-label="Show password">Show</button>
      </div>
    </div>
    <button class="btn btn-primary btn-block" type="submit">Log in to dashboard</button>
  </form>
  <p class="auth-links"><a href="<?= e(url('student_login.php')) ?>">Student login</a></p>
</div>
<?php clear_old(); require __DIR__ . '/includes/footer.php'; ?>
