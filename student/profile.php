<?php
require_once __DIR__ . '/../includes/auth.php';
$student = require_role('student');
$layout = 'student'; $activeNav = 'profile'; $pageTitle = 'My profile';
require __DIR__ . '/../includes/header.php';
?>
<div class="section-head"><h1>My profile</h1></div>
<div class="grid-main">
  <section class="card">
    <div class="card-head"><h2>Personal details</h2></div>
    <form method="post" action="<?= e(url('actions/update_profile.php')) ?>" data-validate novalidate>
      <?= csrf_field() ?><input type="hidden" name="form" value="profile">
      <div class="grid-2">
        <div class="field"><label for="name">Full name</label><input id="name" name="name" required maxlength="100" value="<?= e($student['name']) ?>"></div>
        <div class="field"><label for="phone">Phone</label><input id="phone" name="phone" pattern="[0-9+\-\s]{7,20}" value="<?= e($student['phone']) ?>"></div>
        <div class="field"><label for="academic_year">Academic year</label>
          <select id="academic_year" name="academic_year" required><?php foreach (YEARS as $y): ?><option value="<?= $y ?>" <?= (int)$student['academic_year'] === $y ? 'selected' : '' ?>>Year <?= $y ?></option><?php endforeach; ?></select></div>
        <div class="field"><label for="ro_dept">Department</label><input id="ro_dept" value="<?= e($student['department']) ?>" readonly></div>
        <div class="field"><label for="ro_sid">Student ID</label><input id="ro_sid" value="<?= e($student['student_id']) ?>" readonly></div>
        <div class="field"><label for="ro_email">Email</label><input id="ro_email" value="<?= e($student['email']) ?>" readonly></div>
      </div>
      <p class="hint">Student ID, email and department are verified by the college office. Contact the office to change them.</p>
      <button class="btn btn-primary" type="submit">Save profile</button>
    </form>
  </section>
  <section class="card">
    <div class="card-head"><h2>Change password</h2></div>
    <form method="post" action="<?= e(url('actions/update_profile.php')) ?>" data-validate novalidate>
      <?= csrf_field() ?><input type="hidden" name="form" value="password">
      <div class="field"><label for="current_password">Current password</label><input type="password" id="current_password" name="current_password" required autocomplete="current-password"></div>
      <div class="field"><label for="new_password">New password</label>
        <div class="pw-wrap"><input type="password" id="new_password" name="new_password" required minlength="8" autocomplete="new-password">
        <button type="button" class="pw-toggle" data-toggle-password="new_password" aria-label="Show password">Show</button></div></div>
      <div class="field"><label for="confirm_password">Confirm new password</label><input type="password" id="confirm_password" name="confirm_password" required data-match="new_password" autocomplete="new-password"></div>
      <button class="btn btn-secondary" type="submit">Change password</button>
    </form>
  </section>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
