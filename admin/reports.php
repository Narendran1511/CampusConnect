<?php
require_once __DIR__ . '/../includes/auth.php';
$admin = require_role('admin');
$pdo = db();

$perEvent = $pdo->query('SELECT e.event_id, e.title, e.category, e.event_date, e.capacity, e.event_status,
    SUM(r.registration_status = "confirmed") AS confirmed, SUM(r.registration_status = "waitlisted") AS waitlisted,
    SUM(r.registration_status = "cancelled") AS cancelled, SUM(r.attendance_status = "present") AS present
    FROM events e LEFT JOIN registrations r ON r.event_id = e.event_id
    WHERE e.event_status <> "draft" GROUP BY e.event_id ORDER BY e.event_date DESC')->fetchAll();

if (isset($_GET['export'])) {
    $rows = [];
    foreach ($perEvent as $r) $rows[] = [$r['title'], $r['category'], $r['event_date'], $r['capacity'], (int)$r['confirmed'], (int)$r['waitlisted'], (int)$r['cancelled'], (int)$r['present']];
    csv_download('event_report_' . date('Ymd') . '.csv', ['Event', 'Category', 'Date', 'Capacity', 'Confirmed', 'Waitlisted', 'Cancelled', 'Attended'], $rows);
}
$byCat = $pdo->query('SELECT e.category, COUNT(r.registration_id) AS n FROM events e LEFT JOIN registrations r ON r.event_id = e.event_id AND r.registration_status = "confirmed"
                      WHERE e.event_status <> "draft" GROUP BY e.category ORDER BY n DESC')->fetchAll();
$byDept = $pdo->query('SELECT u.department, COUNT(*) AS n FROM registrations r JOIN users u ON u.user_id = r.student_id
                       WHERE r.registration_status = "confirmed" GROUP BY u.department ORDER BY n DESC')->fetchAll();
$maxCat = max(1, ...array_map(fn($r) => (int)$r['n'], $byCat ?: [['n' => 1]]));
$maxDept = max(1, ...array_map(fn($r) => (int)$r['n'], $byDept ?: [['n' => 1]]));

$layout = 'admin'; $activeNav = 'reports'; $pageTitle = 'Reports';
require __DIR__ . '/../includes/header.php';
?>
<div class="card">
  <div class="card-head"><h2>Registrations by event</h2><a class="btn btn-secondary btn-sm" href="?export=1">Export CSV</a></div>
  <?php if (!$perEvent): ?><p class="empty">No published events yet.</p><?php else: ?>
  <div class="table-wrap"><table class="table"><thead><tr><th>Event</th><th>Category</th><th>Date</th><th>Seats filled</th><th>Waitlist</th><th>Cancelled</th><th>Attended</th></tr></thead><tbody>
  <?php foreach ($perEvent as $r): $pct = $r['capacity'] ? min(100, round(100 * (int)$r['confirmed'] / $r['capacity'])) : 0; ?>
    <tr><td><a href="<?= e(url('admin/view_registrations.php?event_id=' . $r['event_id'])) ?>"><?= e($r['title']) ?></a></td><td><?= e($r['category']) ?></td><td><?= e(fmt_date($r['event_date'])) ?></td>
      <td><div class="bar"><span style="width:<?= $pct ?>%"></span></div><small><?= (int)$r['confirmed'] ?>/<?= (int)$r['capacity'] ?> (<?= $pct ?>%)</small></td>
      <td><?= (int)$r['waitlisted'] ?></td><td><?= (int)$r['cancelled'] ?></td><td><?= (int)$r['present'] ?></td></tr>
  <?php endforeach; ?></tbody></table></div><?php endif; ?>
</div>
<div class="grid-main">
  <section class="card"><div class="card-head"><h2>Registrations by category</h2></div>
    <?php if (!$byCat): ?><p class="empty">No data yet.</p><?php endif; foreach ($byCat as $r): ?>
      <div class="bar-row"><span><?= e($r['category']) ?></span><div class="bar"><span style="width:<?= round(100 * $r['n'] / $maxCat) ?>%"></span></div><b><?= (int)$r['n'] ?></b></div>
    <?php endforeach; ?></section>
  <section class="card"><div class="card-head"><h2>Registrations by department</h2></div>
    <?php if (!$byDept): ?><p class="empty">No data yet.</p><?php endif; foreach ($byDept as $r): ?>
      <div class="bar-row"><span><?= e($r['department']) ?></span><div class="bar"><span style="width:<?= round(100 * $r['n'] / $maxDept) ?>%"></span></div><b><?= (int)$r['n'] ?></b></div>
    <?php endforeach; ?></section>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
