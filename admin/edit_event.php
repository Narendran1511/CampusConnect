<?php
require_once __DIR__ . '/../includes/auth.php';
$admin = require_role('admin');
$id = (int)($_GET['id'] ?? 0);
$st = db()->prepare('SELECT * FROM events WHERE event_id = ?'); $st->execute([$id]);
$ev = $st->fetch();
if (!$ev) { flash('error', 'Event not found.'); redirect('admin/manage_events.php'); }
$layout = 'admin'; $activeNav = 'manage'; $pageTitle = 'Edit event';
$formAction = url('actions/update_event.php');
require __DIR__ . '/../includes/header.php';
if (!empty($_SESSION['form_errors'])) {
    echo '<div class="alert alert-error"><strong>Please fix the following:</strong><ul>';
    foreach ($_SESSION['form_errors'] as $er) echo '<li>' . e($er) . '</li>';
    echo '</ul></div>'; unset($_SESSION['form_errors']);
}
require __DIR__ . '/../includes/event_form.php';
clear_old();
require __DIR__ . '/../includes/footer.php';
