<?php
/**
 * includes/header.php
 * Set before including:  $pageTitle, $layout ('admin' | 'student' | 'public'), $activeNav (string)
 */
$layout    = $layout ?? 'public';
$pageTitle = $pageTitle ?? 'CampusConnect';
$activeNav = $activeNav ?? '';
$__user    = current_user();
$nav = function (string $key, string $href, string $label, string $icon) use ($activeNav) {
    $cls = $activeNav === $key ? ' class="active"' : '';
    return '<a' . $cls . ' href="' . e(url($href)) . '"><span class="ico" aria-hidden="true">' . $icon . '</span>' . e($label) . '</a>';
};
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($pageTitle) ?> | CampusConnect</title>
<link rel="stylesheet" href="<?= e(url('assets/css/style.css')) ?>">
</head>
<body class="layout-<?= e($layout) ?>" data-base="<?= e(BASE_URL) ?>">
<?php if ($layout === 'admin' && $__user): ?>
<div class="admin-shell">
  <aside class="sidebar" id="sidebar">
    <div class="brand"><span class="logo">C</span> CampusConnect <small>Admin</small></div>
    <nav>
      <?= $nav('dashboard', 'admin/dashboard.php', 'Dashboard', '▦') ?>
      <?= $nav('create', 'admin/create_event.php', 'Create event', '＋') ?>
      <?= $nav('manage', 'admin/manage_events.php', 'Manage events', '☰') ?>
      <?= $nav('registrations', 'admin/view_registrations.php', 'Registrations', '✎') ?>
      <?= $nav('reports', 'admin/reports.php', 'Reports', '▤') ?>
    </nav>
    <div class="sidebar-foot">
      <div class="who"><?= e($__user['name']) ?><br><small><?= e($__user['email']) ?></small></div>
      <a class="btn btn-outline-light btn-sm" href="<?= e(url('logout.php')) ?>">Log out</a>
    </div>
  </aside>
  <div class="admin-main">
    <header class="topbar">
      <button class="menu-btn" type="button" id="menuBtn" aria-label="Toggle menu" aria-controls="sidebar">☰</button>
      <h1><?= e($pageTitle) ?></h1>
    </header>
    <main class="content">
<?php elseif ($layout === 'student' && $__user): ?>
<header class="student-nav">
  <div class="nav-inner">
    <a class="brand" href="<?= e(url('student/dashboard.php')) ?>"><span class="logo">C</span> CampusConnect</a>
    <button class="menu-btn" type="button" id="menuBtn" aria-label="Toggle menu" aria-controls="studentLinks">☰</button>
    <nav class="links" id="studentLinks">
      <?= $nav('dashboard', 'student/dashboard.php', 'Dashboard', '') ?>
      <?= $nav('explore', 'student/explore_events.php', 'Explore events', '') ?>
      <?= $nav('registrations', 'student/my_registrations.php', 'My registrations', '') ?>
      <?= $nav('history', 'student/event_history.php', 'Event history', '') ?>
      <?php if (CERTIFICATES_ENABLED) echo $nav('certificates', 'student/certificates.php', 'My certificates', ''); ?>
      <?= $nav('profile', 'student/profile.php', 'My profile', '') ?>
      <a class="logout" href="<?= e(url('logout.php')) ?>">Logout</a>
    </nav>
  </div>
</header>
<main class="content student-content">
<?php else: ?>
<main class="public-main">
<?php endif; ?>
<?= flash_render() ?>
