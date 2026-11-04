<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/_data.php';
$student = require_role('student');
$rid = (int)($_GET['id'] ?? 0);
$cert = null;
foreach (student_registrations((int)$student['user_id']) as $r) {
    if ((int)$r['registration_id'] === $rid && $r['finished'] && $r['registration_status'] === 'confirmed' && $r['attendance_status'] === 'present') $cert = $r;
}
if (!CERTIFICATES_ENABLED || !$cert) { flash('error', 'Certificate not available.'); redirect('student/certificates.php'); }
?>
<!DOCTYPE html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Certificate - <?= e($cert['title']) ?></title>
<link rel="stylesheet" href="<?= e(url('assets/css/style.css')) ?>"></head>
<body class="cert-page">
<div class="cert">
  <div class="cert-brand">CampusConnect</div>
  <h1>Certificate of Participation</h1>
  <p>This is to certify that</p>
  <h2><?= e($student['name']) ?></h2>
  <p><?= e($student['student_id']) ?> &middot; <?= e($student['department']) ?></p>
  <p>attended <strong><?= e($cert['title']) ?></strong><br>organized by <?= e($cert['event_dept']) ?><br>on <?= e(fmt_date($cert['event_date'])) ?> at <?= e($cert['venue']) ?></p>
  <p class="cert-id">Registration ID: <?= e($cert['registration_code']) ?></p>
</div>
<p class="no-print center"><button class="btn btn-primary" onclick="window.print()">Print or save as PDF</button>
<a class="btn btn-ghost" href="<?= e(url('student/certificates.php')) ?>">Back</a></p>
</body></html>
