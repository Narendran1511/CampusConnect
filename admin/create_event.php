<?php
require_once __DIR__ . '/../includes/auth.php';
$admin = require_role('admin');
$layout = 'admin'; $activeNav = 'create'; $pageTitle = 'Create event';
$ev = null; $formAction = url('actions/create_event.php');
require __DIR__ . '/../includes/header.php';
if (!empty($_SESSION['form_errors'])) {
    echo '<div class="alert alert-error"><strong>Please fix the following:</strong><ul>';
    foreach ($_SESSION['form_errors'] as $er) echo '<li>' . e($er) . '</li>';
    echo '</ul></div>'; unset($_SESSION['form_errors']);
}
require __DIR__ . '/../includes/event_form.php';
clear_old();
require __DIR__ . '/../includes/footer.php';
