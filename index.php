<?php
require_once __DIR__ . '/includes/auth.php';
$u = current_user();
if ($u) redirect($u['role'] === 'admin' ? 'admin/dashboard.php' : 'student/dashboard.php');

$layout = 'public'; $pageTitle = 'Welcome';
require __DIR__ . '/includes/header.php';
?>
<section class="landing">
  <div class="landing-brand"><span class="logo">C</span> CampusConnect</div>
  <h1>Every college event, in one place.</h1>
  <p>Browse workshops, hackathons, symposiums and cultural events, then register in a few clicks. Organizers publish events and see who is coming.</p>
  <div class="landing-actions">
    <a class="btn btn-primary" href="<?= e(url('student_login.php')) ?>">Student login</a>
    <a class="btn btn-secondary" href="<?= e(url('register.php')) ?>">Create student account</a>
    <a class="btn btn-ghost" href="<?= e(url('admin_login.php')) ?>">Administrator login</a>
  </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
