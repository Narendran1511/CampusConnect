<?php
require_once __DIR__ . '/../includes/auth.php';
$admin = require_role('admin');
$pdo = db();

$eventId = (int)($_GET['event_id'] ?? 0);
$events = $pdo->query('SELECT e.event_id, e.title, e.event_date, e.capacity, e.event_status,
    (SELECT COUNT(*) FROM registrations r WHERE r.event_id = e.event_id AND r.registration_status = "confirmed") AS taken,
    (SELECT COUNT(*) FROM registrations r WHERE r.event_id = e.event_id AND r.registration_status = "waitlisted") AS waiting
    FROM events e ORDER BY e.event_date DESC')->fetchAll();

$ev = null; $regs = [];
$q = trim($_GET['q'] ?? ''); $fs = $_GET['status'] ?? ''; $fa = $_GET['att'] ?? '';
if ($eventId) {
    $st = $pdo->prepare('SELECT * FROM events WHERE event_id = ?'); $st->execute([$eventId]); $ev = $st->fetch();
    if (!$ev) { flash('error', 'Event not found.'); redirect('admin/view_registrations.php'); }
    $sql = 'SELECT r.*, u.name, u.email, u.student_id AS sid, u.department, u.academic_year, u.phone
            FROM registrations r JOIN users u ON u.user_id = r.student_id WHERE r.event_id = ?'; $p = [$eventId];
    if ($q !== '') { $sql .= ' AND (u.name LIKE ? OR u.student_id LIKE ? OR u.email LIKE ? OR r.registration_code LIKE ?)'; array_push($p, "%$q%", "%$q%", "%$q%", "%$q%"); }
    if (in_array($fs, ['confirmed', 'waitlisted', 'cancelled'], true)) { $sql .= ' AND r.registration_status = ?'; $p[] = $fs; }
    if (in_array($fa, ['pending', 'present', 'absent'], true)) { $sql .= ' AND r.attendance_status = ?'; $p[] = $fa; }
    $sql .= ' ORDER BY r.registration_date ASC, r.registration_id ASC';
    $st = $pdo->prepare($sql); $st->execute($p); $regs = $st->fetchAll();

    if (isset($_GET['export'])) {
        $rows = [];
        foreach ($regs as $r) $rows[] = [$r['registration_code'], $r['name'], $r['sid'], $r['email'], $r['department'], $r['academic_year'], $r['phone'], $r['registration_date'], $r['registration_status'], $r['attendance_status']];
        csv_download('participants_event_' . $eventId . '_' . date('Ymd') . '.csv',
            ['Registration ID', 'Name', 'Student ID', 'Email', 'Department', 'Year', 'Phone', 'Registered on', 'Status', 'Attendance'], $rows);
    }
    $taken = seats_taken($eventId);
    $waiting = (int)$pdo->query('SELECT COUNT(*) FROM registrations WHERE registration_status="waitlisted" AND event_id=' . $eventId)->fetchColumn();
}

$layout = 'admin'; $activeNav = 'registrations'; $pageTitle = $ev ? 'Registrations: ' . $ev['title'] : 'Registrations';
require __DIR__ . '/../includes/header.php';

if (!$ev): ?>
<div class="card">
  <div class="card-head"><h2>Choose an event</h2></div>
  <?php if (!$events): ?><p class="empty">No events yet.</p><?php else: ?>
  <div class="table-wrap"><table class="table"><thead><tr><th>Event</th><th>Date</th><th>Registered</th><th>Waitlist</th><th>Status</th><th></th></tr></thead><tbody>
  <?php foreach ($events as $r): ?>
    <tr><td><?= e($r['title']) ?></td><td><?= e(fmt_date($r['event_date'])) ?></td><td><?= (int)$r['taken'] ?>/<?= (int)$r['capacity'] ?></td><td><?= (int)$r['waiting'] ?></td>
        <td><?= status_badge(effective_status($r + ['end_time' => '23:59:59', 'registration_deadline' => '2999-01-01'])) ?></td>
        <td><a class="btn btn-sm btn-primary" href="?event_id=<?= (int)$r['event_id'] ?>">View participants</a></td></tr>
  <?php endforeach; ?></tbody></table></div>
  <?php endif; ?>
</div>
<?php else: $avail = max(0, (int)$ev['capacity'] - $taken); $qs = ['q' => $q, 'status' => $fs, 'att' => $fa]; ?>
<div class="card event-summary">
  <img src="<?= e(poster_url($ev['poster'])) ?>" alt="">
  <div>
    <h2><?= e($ev['title']) ?> <?= status_badge(effective_status($ev)) ?></h2>
    <p><?= e(fmt_date($ev['event_date'])) ?>, <?= e(fmt_time($ev['start_time'])) ?> - <?= e(fmt_time($ev['end_time'])) ?> &middot; <?= e($ev['venue']) ?> &middot; <?= e($ev['department']) ?></p>
    <div class="mini-stats">
      <span><strong><?= $taken ?></strong> registered</span>
      <span><strong><?= (int)$ev['capacity'] ?></strong> capacity</span>
      <span><strong><?= $avail ?></strong> seats available</span>
      <span><strong><?= $waiting ?></strong> on waitlist</span>
    </div>
  </div>
</div>

<form method="get" class="toolbar card">
  <input type="hidden" name="event_id" value="<?= $eventId ?>">
  <input type="search" name="q" id="tableSearch" placeholder="Search name, student ID, email or reg. ID" value="<?= e($q) ?>" data-table-search="regTable" aria-label="Search participants">
  <select name="status" aria-label="Registration status"><option value="">All statuses</option>
    <?php foreach (['confirmed','waitlisted','cancelled'] as $s): ?><option <?= $fs === $s ? 'selected' : '' ?>><?= e($s) ?></option><?php endforeach; ?></select>
  <select name="att" aria-label="Attendance"><option value="">Any attendance</option>
    <?php foreach (['pending','present','absent'] as $s): ?><option <?= $fa === $s ? 'selected' : '' ?>><?= e($s) ?></option><?php endforeach; ?></select>
  <button class="btn btn-primary" type="submit">Filter</button>
  <a class="btn btn-ghost" href="?event_id=<?= $eventId ?>">Reset</a>
  <a class="btn btn-secondary push-right" href="?<?= e(http_build_query(array_filter($qs) + ['event_id' => $eventId, 'export' => 1])) ?>">Export CSV</a>
</form>

<div class="card">
<?php if (!$regs): ?><p class="empty">No registrations found for this filter.</p><?php else: ?>
<div class="table-wrap"><table class="table" id="regTable">
  <thead><tr><th>Registration ID</th><th>Student</th><th>Student ID</th><th>Dept. / Year</th><th>Registered</th><th>Status</th><th>Attendance</th></tr></thead><tbody>
  <?php foreach ($regs as $r): ?>
    <tr>
      <td><code><?= e($r['registration_code']) ?></code></td>
      <td><strong><?= e($r['name']) ?></strong><br><small><?= e($r['email']) ?></small></td>
      <td><?= e($r['sid']) ?></td>
      <td><?= e($r['department']) ?><br><small>Year <?= (int)$r['academic_year'] ?></small></td>
      <td><?= e(fmt_dt($r['registration_date'])) ?></td>
      <td><span class="badge badge-<?= ['confirmed' => 'success', 'waitlisted' => 'warning', 'cancelled' => 'danger'][$r['registration_status']] ?>"><?= e(ucfirst($r['registration_status'])) ?></span></td>
      <td>
      <?php if ($r['registration_status'] === 'confirmed'): ?>
        <form method="post" action="<?= e(url('actions/attendance.php')) ?>" class="inline">
          <?= csrf_field() ?><input type="hidden" name="event_id" value="<?= $eventId ?>"><input type="hidden" name="registration_id" value="<?= (int)$r['registration_id'] ?>">
          <?php foreach ($qs as $k => $v): if ($v !== ''): ?><input type="hidden" name="qs[<?= e($k) ?>]" value="<?= e($v) ?>"><?php endif; endforeach; ?>
          <select name="attendance" data-autosubmit aria-label="Attendance for <?= e($r['name']) ?>">
            <?php foreach (['pending','present','absent'] as $a): ?><option <?= $r['attendance_status'] === $a ? 'selected' : '' ?>><?= $a ?></option><?php endforeach; ?>
          </select><noscript><button class="btn btn-sm">Save</button></noscript>
        </form>
      <?php else: ?>-<?php endif; ?>
      </td>
    </tr>
  <?php endforeach; ?></tbody></table></div>
<?php endif; ?>
</div>
<?php endif; require __DIR__ . '/../includes/footer.php'; ?>
