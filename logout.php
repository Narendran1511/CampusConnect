<?php
require_once __DIR__ . '/includes/auth.php';
$wasAdmin = ($_SESSION['role'] ?? '') === 'admin';
logout_user();
session_start();
flash('success', 'You have been logged out.');
redirect($wasAdmin ? 'admin_login.php' : 'student_login.php');
