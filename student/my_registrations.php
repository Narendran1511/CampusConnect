<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/_data.php';
$student = require_role('student');
$regs = student_registrations((int)$student['user_id']);
$layout = 'student'; $activeNav = 'registrations'; $pageTitle = 'My registrations';
require __DIR__ . '/../includes/header.php';
$badge = ['confirmed' => 'success', 'waitlisted' => 'warning', 'cancelled' => 'danger'];
?>
<div class="section-head"><h1>My registrations</h1></div>
<div class="card">
<?php if (!$regs): ?><p class="empty">You have not registered for any events yet. <a href="<?= e(url('student/explore_events.php')) ?>">Explore events</a>.</p><?php else: ?>
<div class="table-wrap"><table class="table">
  <thead><tr><th>Registration ID</th><th>Event</th><th>Event date</th><th>Registered on</th><th>Status</th><th>Attendance</th><th>Actions</th></tr></thead><tbody>
  <?php foreach ($regs as $r): $rid = (int)$r['registration_id']; ?>
    <tr>
      <td><code><?= e($r['registration_code']) ?></code></td>
      <td><a href="<?= e(url('student/event_details.php?id=' . $r['event_id'])) ?>"><?= e($r['title']) ?></a></td>
      <td><?= e(fmt_date($r['event_date'])) ?></td>
      <td><?= e(fmt_dt($r['registration_date'])) ?></td>
      <td><span class="badge badge-<?= $badge[$r['registration_status']] ?>"><?= e(ucfirst($r['registration_status'])) ?></span></td>
      <td><?= $r['registration_status'] === 'confirmed' ? e(ucfirst($r['attendance_status'])) : '-' ?></td>
      <td class="actions">
        <button type="button" class="btn btn-sm btn-outline" data-toggle-row="detail-<?= $rid ?>" aria-expanded="false">Details</button>
        <?php if ($r['can_cancel']): ?>
        <form method="post" action="<?= e(url('actions/cancel_registration.php')) ?>" class="inline">
          <?= csrf_field() ?><input type="hidden" name="registration_id" value="<?= $rid ?>"><input type="hidden" name="return" value="list">
          <button class="btn btn-sm btn-danger" type="submit" data-confirm="Cancel your registration for &quot;<?= e($r['title']) ?>&quot;?">Cancel</button>
        </form>
        <?php endif; ?>
      </td>
    </tr>
    <tr id="detail-<?= $rid ?>" class="detail-row" hidden><td colspan="7">
      <div class="detail-grid">
        <div><b>Time</b><br><?= e(fmt_time($r['start_time'])) ?> - <?= e(fmt_time($r['end_time'])) ?></div>
        <div><b>Venue</b><br><?= e($r['venue']) ?></div>
        <div><b>Organized by</b><br><?= e($r['event_dept']) ?></div>
        <div><b>Category</b><br><?= e($r['category']) ?></div>
        <div><b>Event status</b><br><?= status_badge($r['effective']) ?></div>
        <div><b>Cancellation</b><br><?= $r['allow_cancellation'] ? 'Allowed before the event starts' : 'Not allowed for this event' ?></div>
      </div>
    </td></tr>
  <?php endforeach; ?></tbody></table></div>
<?php endif; ?>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
