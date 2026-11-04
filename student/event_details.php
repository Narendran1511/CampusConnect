<?php
require_once __DIR__ . '/../includes/auth.php';
$student = require_role('student');
$id = (int)($_GET['id'] ?? 0);

$st = db()->prepare('SELECT * FROM events WHERE event_id = ? AND event_status IN ("published","registration_closed","completed","cancelled")');
$st->execute([$id]);
$ev = $st->fetch();
if (!$ev) { flash('error', 'That event is not available.'); redirect('student/explore_events.php'); }

$taken = seats_taken($id);
$st = db()->prepare('SELECT * FROM registrations WHERE event_id = ? AND student_id = ?');
$st->execute([$id, $student['user_id']]);
$mine = $st->fetch() ?: null;
$chk = registration_check($ev, $student, $taken, $mine);
$eff = effective_status($ev);
$avail = max(0, (int)$ev['capacity'] - $taken);
$canCancel = $mine && in_array($mine['registration_status'], ['confirmed', 'waitlisted'], true) && $ev['allow_cancellation']
    && !in_array($eff, ['completed', 'cancelled'], true) && event_start_ts($ev) > time();

$layout = 'student'; $activeNav = 'explore'; $pageTitle = $ev['title'];
require __DIR__ . '/../includes/header.php';
?>
<p><a href="<?= e(url('student/explore_events.php')) ?>">&larr; Back to events</a></p>
<article class="details">
  <div class="details-poster"><img src="<?= e(poster_url($ev['poster'])) ?>" alt="Poster for <?= e($ev['title']) ?>"></div>
  <div class="details-body">
    <div class="tags"><span class="chip-static"><?= e($ev['category']) ?></span> <?= status_badge($eff) ?></div>
    <h1><?= e($ev['title']) ?></h1>
    <div class="desc"><?= nl2br(e($ev['description'])) ?></div>

    <dl class="facts">
      <div><dt>Date</dt><dd><?= e(fmt_date($ev['event_date'])) ?></dd></div>
      <div><dt>Time</dt><dd><?= e(fmt_time($ev['start_time'])) ?> - <?= e(fmt_time($ev['end_time'])) ?></dd></div>
      <div><dt>Venue</dt><dd><?= e($ev['venue']) ?></dd></div>
      <div><dt>Organized by</dt><dd><?= e($ev['department']) ?></dd></div>
      <div><dt>Registration opens</dt><dd><?= e(fmt_dt($ev['registration_open'])) ?></dd></div>
      <div><dt>Registration deadline</dt><dd><?= e(fmt_dt($ev['registration_deadline'])) ?></dd></div>
      <div><dt>Maximum participants</dt><dd><?= (int)$ev['capacity'] ?></dd></div>
      <div><dt>Registered so far</dt><dd><?= $taken ?></dd></div>
      <div><dt>Seats available</dt><dd><strong><?= $avail ?></strong></dd></div>
      <div><dt>Eligibility</dt><dd><?= $ev['eligibility'] ? nl2br(e($ev['eligibility'])) : 'Open to all students'
          ?><?php if ($ev['eligible_department'] !== 'All' || $ev['eligible_year'] !== 'All'): ?><br><small>Restricted to: <?= e($ev['eligible_department'] === 'All' ? 'all departments' : $ev['eligible_department']) ?>, <?= $ev['eligible_year'] === 'All' ? 'all years' : 'year ' . e($ev['eligible_year']) ?></small><?php endif; ?></dd></div>
      <?php if ($ev['contact_email'] || $ev['contact_phone']): ?>
      <div><dt>Organizer contact</dt><dd><?= $ev['contact_email'] ? '<a href="mailto:' . e($ev['contact_email']) . '">' . e($ev['contact_email']) . '</a>' : '' ?><?= $ev['contact_email'] && $ev['contact_phone'] ? '<br>' : '' ?><?= e($ev['contact_phone'] ?? '') ?></dd></div>
      <?php endif; ?>
    </dl>

    <div class="register-box">
    <?php if ($mine && $mine['registration_status'] === 'confirmed'): ?>
      <div class="alert alert-success">You are registered. Registration ID: <strong><?= e($mine['registration_code']) ?></strong></div>
    <?php elseif ($mine && $mine['registration_status'] === 'waitlisted'): ?>
      <div class="alert alert-warning">You are on the waitlist. Registration ID: <strong><?= e($mine['registration_code']) ?></strong></div>
    <?php endif; ?>

    <?php if ($canCancel): ?>
      <form method="post" action="<?= e(url('actions/cancel_registration.php')) ?>">
        <?= csrf_field() ?><input type="hidden" name="registration_id" value="<?= (int)$mine['registration_id'] ?>"><input type="hidden" name="return" value="details">
        <button class="btn btn-danger" type="submit" data-confirm="Cancel your registration for this event?">Cancel registration</button>
      </form>
    <?php elseif ($mine && in_array($mine['registration_status'], ['confirmed', 'waitlisted'], true) && !$ev['allow_cancellation'] && !in_array($eff, ['completed', 'cancelled'], true)): ?>
      <p class="muted-text">This event does not allow cancellations.</p>
    <?php elseif ($chk['ok']): ?>
      <form method="post" action="<?= e(url('actions/register_event.php')) ?>">
        <?= csrf_field() ?><input type="hidden" name="event_id" value="<?= $id ?>">
        <button class="btn btn-primary btn-lg" type="submit">REGISTER NOW</button>
      </form>
    <?php elseif ($chk['full']): ?>
      <div class="alert alert-error"><?= e($chk['reason']) ?></div>
      <form method="post" action="<?= e(url('actions/register_event.php')) ?>">
        <?= csrf_field() ?><input type="hidden" name="event_id" value="<?= $id ?>"><input type="hidden" name="waitlist" value="1">
        <button class="btn btn-secondary" type="submit">Join waitlist</button>
      </form>
    <?php elseif (!$mine || $mine['registration_status'] === 'cancelled'): ?>
      <button class="btn btn-primary btn-lg" type="button" disabled>REGISTER NOW</button>
      <p class="muted-text"><?= e($chk['reason']) ?></p>
    <?php endif; ?>
    </div>
  </div>
</article>
<?php require __DIR__ . '/../includes/footer.php'; ?>
