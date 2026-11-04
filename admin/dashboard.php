<?php
require_once __DIR__ . '/../includes/auth.php';
$admin = require_role('admin');
$pdo = db(); $now = date('Y-m-d H:i:s');

$one = function (string $sql, array $p = []) use ($pdo) { $s = $pdo->prepare($sql); $s->execute($p); return (int)$s->fetchColumn(); };
$stats = [
  'Total students'       => $one('SELECT COUNT(*) FROM users WHERE role = "student"'),
  'Published events'     => $one('SELECT COUNT(*) FROM events WHERE event_status IN ("published","registration_closed","completed")'),
  'Upcoming events'      => $one('SELECT COUNT(*) FROM events WHERE event_status IN ("published","registration_closed") AND TIMESTAMP(event_date,end_time) >= ?', [$now]),
  'Completed events'     => $one('SELECT COUNT(*) FROM events WHERE event_status = "completed" OR (event_status IN ("published","registration_closed") AND TIMESTAMP(event_date,end_time) < ?)', [$now]),
  'Event registrations'  => $one('SELECT COUNT(*) FROM registrations WHERE registration_status = "confirmed"'),
];
$drafts = $one('SELECT COUNT(*) FROM events WHERE event_status = "draft"');
$recent = $pdo->query('SELECT e.*, (SELECT COUNT(*) FROM registrations r WHERE r.event_id = e.event_id AND r.registration_status = "confirmed") AS taken
                       FROM events e WHERE e.event_status <> "archived" ORDER BY e.created_at DESC LIMIT 6')->fetchAll();
$latestRegs = $pdo->query('SELECT r.registration_code, r.registration_date, u.name, e.title FROM registrations r
                           JOIN users u ON u.user_id = r.student_id JOIN events e ON e.event_id = r.event_id
                           WHERE r.registration_status <> "cancelled" ORDER BY r.registration_date DESC LIMIT 6')->fetchAll();

$layout = 'admin'; $activeNav = 'dashboard'; $pageTitle = 'Dashboard';
require __DIR__ . '/../includes/header.php';
?>
<p class="lead">Welcome back, <?= e($admin['name']) ?>.<?= $drafts ? ' You have ' . $drafts . ' draft event' . ($drafts > 1 ? 's' : '') . ' waiting to be published.' : '' ?></p>
<div class="stat-grid">
<?php foreach ($stats as $label => $n): ?>
  <div class="stat"><div class="stat-num"><?= $n ?></div><div class="stat-label"><?= e($label) ?></div></div>
<?php endforeach; ?>
</div>

<div class="grid-main">
  <section class="card">
    <div class="card-head"><h2>Recently added events</h2><a class="btn btn-primary btn-sm" href="<?= e(url('admin/create_event.php')) ?>">Create event</a></div>
    <?php if (!$recent): ?><p class="empty">No events yet. Create your first event to get started.</p><?php else: ?>
    <div class="table-wrap"><table class="table">
      <thead><tr><th>Event</th><th>Date</th><th>Seats</th><th>Status</th></tr></thead><tbody>
      <?php foreach ($recent as $r): ?>
        <tr><td><a href="<?= e(url('admin/view_registrations.php?event_id=' . $r['event_id'])) ?>"><?= e($r['title']) ?></a></td>
            <td><?= e(fmt_date($r['event_date'])) ?></td><td><?= (int)$r['taken'] ?>/<?= (int)$r['capacity'] ?></td>
            <td><?= status_badge(effective_status($r)) ?></td></tr>
      <?php endforeach; ?></tbody></table></div>
    <?php endif; ?>
  </section>
  <section class="card">
    <div class="card-head"><h2>Latest registrations</h2></div>
    <?php if (!$latestRegs): ?><p class="empty">No registrations yet.</p><?php else: ?>
    <ul class="feed">
      <?php foreach ($latestRegs as $l): ?>
        <li><strong><?= e($l['name']) ?></strong> registered for <?= e($l['title']) ?><br><small><?= e($l['registration_code']) ?> &middot; <?= e(fmt_dt($l['registration_date'])) ?></small></li>
      <?php endforeach; ?></ul>
    <?php endif; ?>
  </section>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
