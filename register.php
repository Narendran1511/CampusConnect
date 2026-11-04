<?php
require_once __DIR__ . '/includes/auth.php';
redirect_if_logged_in();

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) { $errors[] = 'Your session expired. Please try again.'; }
    else {
        $name = post('name'); $email = strtolower(post('email')); $sid = strtoupper(post('student_id'));
        $dept = post('department'); $year = post('academic_year'); $phone = post('phone');
        $pw = (string)($_POST['password'] ?? ''); $pw2 = (string)($_POST['confirm_password'] ?? '');
        $_SESSION['old'] = compact('name', 'email', 'sid', 'dept', 'year', 'phone');

        if (mb_strlen($name) < 2 || mb_strlen($name) > 100) $errors[] = 'Enter your full name.';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Enter a valid email address.';
        if (!preg_match('/^[A-Z0-9\-]{4,30}$/', $sid)) $errors[] = 'Student ID must be 4-30 letters, numbers or hyphens.';
        if (!in_array($dept, DEPARTMENTS, true)) $errors[] = 'Select your department.';
        if (!in_array((int)$year, YEARS, true)) $errors[] = 'Select your academic year.';
        if ($phone !== '' && !preg_match('/^[0-9+\-\s]{7,20}$/', $phone)) $errors[] = 'Enter a valid phone number.';
        if (strlen($pw) < 8 || !preg_match('/[A-Za-z]/', $pw) || !preg_match('/\d/', $pw)) $errors[] = 'Password must be at least 8 characters and include a letter and a number.';
        if ($pw !== $pw2) $errors[] = 'Passwords do not match.';

        if (!$errors) {
            $chk = db()->prepare('SELECT COUNT(*) FROM users WHERE email = ? OR student_id = ?');
            $chk->execute([$email, $sid]);
            if ($chk->fetchColumn() > 0) $errors[] = 'An account with this email or student ID already exists.';
        }
        if (!$errors) {
            // role is hard-coded: public sign-up can only ever create students
            db()->prepare('INSERT INTO users (name,email,student_id,password_hash,department,academic_year,phone,role) VALUES (?,?,?,?,?,?,?,"student")')
                ->execute([$name, $email, $sid, password_hash($pw, PASSWORD_DEFAULT), $dept, (int)$year, $phone ?: null]);
            clear_old();
            flash('success', 'Account created. You can now log in.');
            redirect('student_login.php');
        }
    }
}

$layout = 'public'; $pageTitle = 'Create student account';
require __DIR__ . '/includes/header.php';
foreach ($errors as $er) echo '<div class="alert alert-error">' . e($er) . '</div>';
?>
<div class="auth-card auth-wide">
  <div class="auth-head"><span class="logo">C</span><h1>Create student account</h1><p>Use your official student details.</p></div>
  <form method="post" data-validate novalidate>
    <?= csrf_field() ?>
    <div class="grid-2">
      <div class="field"><label for="name">Full name</label><input id="name" name="name" required maxlength="100" value="<?= e(old('name')) ?>"></div>
      <div class="field"><label for="student_id">Student ID</label><input id="student_id" name="student_id" required maxlength="30" value="<?= e(old('sid')) ?>"></div>
      <div class="field"><label for="email">Email</label><input type="email" id="email" name="email" required value="<?= e(old('email')) ?>"></div>
      <div class="field"><label for="phone">Phone (optional)</label><input id="phone" name="phone" pattern="[0-9+\-\s]{7,20}" value="<?= e(old('phone')) ?>"></div>
      <div class="field"><label for="department">Department</label>
        <select id="department" name="department" required><option value="">Select department</option>
        <?php foreach (DEPARTMENTS as $d): ?><option <?= old('dept') === $d ? 'selected' : '' ?>><?= e($d) ?></option><?php endforeach; ?></select></div>
      <div class="field"><label for="academic_year">Academic year</label>
        <select id="academic_year" name="academic_year" required><option value="">Select year</option>
        <?php foreach (YEARS as $y): ?><option value="<?= $y ?>" <?= (string)old('year') === (string)$y ? 'selected' : '' ?>>Year <?= $y ?></option><?php endforeach; ?></select></div>
      <div class="field"><label for="password">Password</label>
        <div class="pw-wrap"><input type="password" id="password" name="password" required minlength="8" autocomplete="new-password">
        <button type="button" class="pw-toggle" data-toggle-password="password" aria-label="Show password">Show</button></div>
        <small class="hint">At least 8 characters with a letter and a number.</small></div>
      <div class="field"><label for="confirm_password">Confirm password</label>
        <input type="password" id="confirm_password" name="confirm_password" required data-match="password" autocomplete="new-password"></div>
    </div>
    <button class="btn btn-primary btn-block" type="submit">Create account</button>
  </form>
  <p class="auth-links">Already registered? <a href="<?= e(url('student_login.php')) ?>">Log in</a></p>
</div>
<?php clear_old(); require __DIR__ . '/includes/footer.php'; ?>
