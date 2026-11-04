<?php
require_once __DIR__ . '/../includes/auth.php';
$student = require_role('student');
require_post_csrf('student/profile.php');
$pdo = db();

if (($_POST['form'] ?? '') === 'password') {
    $cur = (string)($_POST['current_password'] ?? ''); $new = (string)($_POST['new_password'] ?? ''); $new2 = (string)($_POST['confirm_password'] ?? '');
    if (!password_verify($cur, $student['password_hash'])) flash('error', 'Your current password is incorrect.');
    elseif (strlen($new) < 8 || !preg_match('/[A-Za-z]/', $new) || !preg_match('/\d/', $new)) flash('error', 'New password must be at least 8 characters and include a letter and a number.');
    elseif ($new !== $new2) flash('error', 'New passwords do not match.');
    else {
        $pdo->prepare('UPDATE users SET password_hash = ? WHERE user_id = ?')->execute([password_hash($new, PASSWORD_DEFAULT), $student['user_id']]);
        session_regenerate_id(true);
        flash('success', 'Password changed.');
    }
    redirect('student/profile.php');
}

// Only these fields are editable. student_id, email, department and role are never read from the form.
$name = post('name'); $phone = post('phone'); $year = post('academic_year');
$errors = [];
if (mb_strlen($name) < 2 || mb_strlen($name) > 100) $errors[] = 'Enter your full name.';
if ($phone !== '' && !preg_match('/^[0-9+\-\s]{7,20}$/', $phone)) $errors[] = 'Enter a valid phone number.';
if (!in_array((int)$year, YEARS, true)) $errors[] = 'Select a valid academic year.';
if ($errors) { foreach ($errors as $er) flash('error', $er); redirect('student/profile.php'); }

$pdo->prepare('UPDATE users SET name = ?, phone = ?, academic_year = ? WHERE user_id = ? AND role = "student"')
    ->execute([$name, $phone ?: null, (int)$year, $student['user_id']]);
flash('success', 'Profile updated.');
redirect('student/profile.php');
