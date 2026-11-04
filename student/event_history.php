<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/_data.php';
$student = require_role('student');
$hist = array_filter(student_registrations((int)$student['user_id']), fn($r) => $r['finished'] && $r['registration_status'] !== 'cancelled');
$layout = 'student'; $activeNav = 'history'; $pageTitle = 'Event history';
require __DIR__ . '/../includes/header.php';
?>
<div class="section-head"><h1>Event history</h1></div>
<div class="card">
<?php if (!$hist): ?><p class="empty">Events you attend will appear here once they are completed.</p><?php else: ?>
<div class="table-wrap"><table class="table">
  <thead><tr><th>Event</th><th>Category</th><th>Date</th><th>Venue</th><th>Registration ID</th><th>Attendance</th><?php if (CERTIFICATES_ENABLED): ?><th>Certificate</th><?php endif; ?></tr></thead><tbody>
  <?php foreach ($hist as $r): ?>
    <tr><td><a href="<?= e(url('student/event_details.php?id=' . $r['event_id'])) ?>"><?= e($r['title']) ?></a></td><td><?= e($r['category']) ?></td>
      <td><?= e(fmt_date($r['event_date'])) ?></td><td><?= e($r['venue']) ?></td><td><code><?= e($r['registration_code']) ?></code></td>
      <td><?= e(ucfirst($r['attendance_status'])) ?></td>
      <?php if (CERTIFICATES_ENABLED): ?><td><?= $r['attendance_status'] === 'present' && $r['registration_status'] === 'confirmed'
          ? '<a class="btn btn-sm btn-outline" href="' . e(url('student/certificate.php?id=' . $r['registration_id'])) . '">View</a>' : '-' ?></td><?php endif; ?></tr>
  <?php endforeach; ?></tbody></table></div>
<?php endif; ?>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
