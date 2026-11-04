<?php
require_once __DIR__ . '/../includes/auth.php';
$student = require_role('student');
$layout = 'student'; $activeNav = 'explore'; $pageTitle = 'Explore events';
require __DIR__ . '/../includes/header.php';
?>
<div class="section-head"><h1>Explore events</h1><span id="eventCount" class="muted-text"></span></div>

<div class="chips" id="categoryChips" role="group" aria-label="Event categories">
  <button type="button" class="chip active" data-cat="">All</button>
  <?php foreach (CATEGORIES as $c): ?><button type="button" class="chip" data-cat="<?= e($c) ?>"><?= e($c) ?></button><?php endforeach; ?>
</div>

<div class="filters card">
  <input type="search" id="fq" placeholder="Search events by title" aria-label="Search events by title">
  <select id="fdept" aria-label="Filter by department"><option value="">All departments</option></select>
  <input type="date" id="fdate" aria-label="Filter by date">
  <select id="fview" aria-label="Show">
    <option value="upcoming">Upcoming events</option><option value="all">All events</option><option value="completed">Completed events</option>
  </select>
  <select id="fsort" aria-label="Sort by"><option value="soonest">Soonest first</option><option value="newest">Newest added</option></select>
  <button type="button" class="btn btn-ghost" id="freset">Reset</button>
</div>

<div id="event-grid" class="event-grid" data-feed="<?= e(url('actions/events_feed.php')) ?>" data-details="<?= e(url('student/event_details.php')) ?>"
     data-mode="explore" aria-live="polite"><p class="empty">Loading events...</p></div>
<noscript><p class="alert alert-warning">Please enable JavaScript to browse events.</p></noscript>
<?php require __DIR__ . '/../includes/footer.php'; ?>
