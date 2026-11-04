<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/_data.php';
$student = require_role('student');
$regs = student_registrations((int)$student['user_id']);

$active = array_filter($regs, fn($r) => $r['registration_status'] !== 'cancelled');
$total = count($active);
$upcoming = count(array_filter($active, fn($r) => !$r['finished'] && $r['effective'] !== 'cancelled'));
$completed = count(array_filter($active, fn($r) => $r['finished'] && $r['registration_status'] === 'confirmed'));
$certs = CERTIFICATES_ENABLED ? count(array_filter($active, fn($r) => $r['finished'] && $r['attendance_status'] === 'present')) : 0;

$layout = 'student'; $activeNav = 'dashboard'; $pageTitle = 'Dashboard';
require __DIR__ . '/../includes/header.php';
?>
<section class="welcome">
  <div>
    <h1>Welcome, <?= e($student['name']) ?></h1>
    <p><?= e($student['student_id']) ?> &middot; <?= e($student['department']) ?> &middot; Year <?= (int)$student['academic_year'] ?></p>
  </div>
  <a class="btn btn-secondary" href="<?= e(url('student/explore_events.php')) ?>">Explore events</a>
</section>

<div class="stat-grid">
  <div class="stat"><div class="stat-num"><?= $total ?></div><div class="stat-label">Registered events</div></div>
  <div class="stat"><div class="stat-num"><?= $upcoming ?></div><div class="stat-label">Upcoming registered</div></div>
  <div class="stat"><div class="stat-num"><?= $completed ?></div><div class="stat-label">Completed events</div></div>
  <?php if (CERTIFICATES_ENABLED): ?><div class="stat"><div class="stat-num"><?= $certs ?></div><div class="stat-label">Certificates available</div></div><?php endif; ?>
</div>

<div class="section-head"><h2>Upcoming events</h2><a href="<?= e(url('student/explore_events.php')) ?>">See all events</a></div>
<div id="event-grid" class="event-grid" data-feed="<?= e(url('actions/events_feed.php')) ?>" data-details="<?= e(url('student/event_details.php')) ?>"
     data-mode="dashboard" data-limit="6" aria-live="polite"><p class="empty">Loading events...</p></div>
<noscript><p class="alert alert-warning">Please enable JavaScript to see the latest events.</p></noscript>
<?php require __DIR__ . '/../includes/footer.php'; ?>
