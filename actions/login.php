<?php
require_once __DIR__ . '/../includes/auth.php';

$role = ($_POST['role'] ?? '') === 'admin' ? 'admin' : 'student';
$back = $role === 'admin' ? 'admin_login.php' : 'student_login.php';
require_post_csrf($back);

$identifier = post('identifier');
$password   = (string)($_POST['password'] ?? '');
$_SESSION['old']['identifier'] = $identifier;

if (login_throttled()) {
    flash('error', 'Too many failed attempts. Please wait 5 minutes and try again.');
    redirect($back);
}
if ($identifier === '' || $password === '') {
    flash('error', 'Enter your ' . ($role === 'admin' ? 'email' : 'student ID or email') . ' and password.');
    redirect($back);
}

// Role is part of the query: a student account can never log in through the admin form and vice versa.
if ($role === 'admin') {
    $st = db()->prepare('SELECT * FROM users WHERE email = ? AND role = "admin" LIMIT 1');
    $st->execute([$identifier]);
} else {
    $st = db()->prepare('SELECT * FROM users WHERE (email = ? OR student_id = ?) AND role = "student" LIMIT 1');
    $st->execute([$identifier, $identifier]);
}
$user = $st->fetch();

// Always run password_verify (against a dummy hash if no user) to keep timing similar.
$hash = $user['password_hash'] ?? '$2y$10$usesomesillystringforsaltandhashingXXXXXXXXXXXXXXXXXXXXXXXXXX';
$ok = password_verify($password, $hash) && $user;

if (!$ok) {
    login_failed();
    flash('error', 'Invalid credentials. Please check your details and try again.');
    redirect($back);
}
if ($user['account_status'] !== 'active') {
    flash('error', 'This account is inactive. Please contact the college office.');
    redirect($back);
}
if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
    db()->prepare('UPDATE users SET password_hash = ? WHERE user_id = ?')->execute([password_hash($password, PASSWORD_DEFAULT), $user['user_id']]);
}

login_user($user);
redirect($role === 'admin' ? 'admin/dashboard.php' : 'student/dashboard.php');
