<?php
require_once __DIR__ . '/../includes/auth.php';
$admin = require_role('admin');
$id = (int)($_POST['event_id'] ?? 0);
require_post_csrf('admin/edit_event.php?id=' . $id);

$st = db()->prepare('SELECT * FROM events WHERE event_id = ?'); $st->execute([$id]);
$existing = $st->fetch();
if (!$existing) { flash('error', 'Event not found.'); redirect('admin/manage_events.php'); }

[$errors, $d] = validate_event_input();
// Capacity may not drop below the number of confirmed seats.
if (!$errors && (int)$d['capacity'] < seats_taken($id)) {
    $errors[] = 'Capacity cannot be lower than the ' . seats_taken($id) . ' students already registered.';
}
[$poster, $upErr] = handle_poster_upload('poster');
if ($upErr) $errors[] = $upErr;

if ($errors) {
    if ($poster) delete_poster($poster);
    $_SESSION['form_errors'] = $errors;
    $_SESSION['old'] = $d;
    redirect('admin/edit_event.php?id=' . $id);
}

$newPoster = $poster ?: $existing['poster'];
$st = db()->prepare('UPDATE events SET title=?,description=?,category=?,poster=?,event_date=?,start_time=?,end_time=?,venue=?,department=?,
  registration_open=?,registration_deadline=?,capacity=?,eligibility=?,eligible_department=?,eligible_year=?,contact_email=?,contact_phone=?,
  allow_cancellation=?,event_status=? WHERE event_id=?');
$st->execute([
    $d['title'], $d['description'], $d['category'], $newPoster, $d['event_date'], $d['start_time'], $d['end_time'],
    $d['venue'], $d['department'], $d['registration_open'], $d['registration_deadline'], (int)$d['capacity'],
    $d['eligibility'] ?: null, $d['eligible_department'], $d['eligible_year'], $d['contact_email'] ?: null,
    $d['contact_phone'] ?: null, $d['allow_cancellation'], $d['event_status'], $id,
]);
if ($poster && $existing['poster']) delete_poster($existing['poster']);
clear_old();
flash('success', 'Event updated.');
redirect('admin/manage_events.php');
