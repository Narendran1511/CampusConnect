<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/_data.php';
$student = require_role('student');
if (!CERTIFICATES_ENABLED) { redirect('student/dashboard.php'); }
$certs = array_filter(student_registrations((int)$student['user_id']), fn($r) => $r['finished'] && $r['registration_status'] === 'confirmed' && $r['attendance_status'] === 'present');
$layout = 'student'; $activeNav = 'certificates'; $pageTitle = 'My certificates';
require __DIR__ . '/../includes/header.php';
?>
<div class="section-head"><h1>My certificates</h1></div>
<div class="card">
<?php if (!$certs): ?><p class="empty">Certificates appear here after an event finishes and the organizer marks you as present.</p><?php else: ?>
<div class="table-wrap"><table class="table"><thead><tr><th>Event</th><th>Date</th><th>Organized by</th><th></th></tr></thead><tbody>
  <?php foreach ($certs as $r): ?>
    <tr><td><?= e($r['title']) ?></td><td><?= e(fmt_date($r['event_date'])) ?></td><td><?= e($r['event_dept']) ?></td>
      <td><a class="btn btn-sm btn-primary" href="<?= e(url('student/certificate.php?id=' . $r['registration_id'])) ?>">View certificate</a></td></tr>
  <?php endforeach; ?></tbody></table></div>
<?php endif; ?>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
