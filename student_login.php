<?php
require_once __DIR__ . '/includes/auth.php';
redirect_if_logged_in();
$layout = 'public'; $pageTitle = 'Student login';
require __DIR__ . '/includes/header.php';
?>
<div class="auth-card">
  <div class="auth-head"><span class="logo">C</span><h1>Student login</h1><p>Sign in with your student ID or email.</p></div>
  <form method="post" action="<?= e(url('actions/login.php')) ?>" data-validate novalidate>
    <?= csrf_field() ?>
    <input type="hidden" name="role" value="student">
    <div class="field">
      <label for="identifier">Student ID or email</label>
      <input type="text" id="identifier" name="identifier" required autocomplete="username" value="<?= e(old('identifier')) ?>">
    </div>
    <div class="field">
      <label for="password">Password</label>
      <div class="pw-wrap">
        <input type="password" id="password" name="password" required autocomplete="current-password">
        <button type="button" class="pw-toggle" data-toggle-password="password" aria-label="Show password">Show</button>
      </div>
    </div>
    <button class="btn btn-primary btn-block" type="submit">Log in</button>
  </form>
  <p class="auth-links"><a href="<?= e(url('forgot_password.php')) ?>">Forgot password?</a></p>
  <p class="auth-links">New here? <a href="<?= e(url('register.php')) ?>">Create a student account</a></p>
  <p class="auth-links"><a href="<?= e(url('admin_login.php')) ?>">Administrator login</a></p>
</div>
<?php clear_old(); require __DIR__ . '/includes/footer.php'; ?>
