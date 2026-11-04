<?php
require_once __DIR__ . '/../includes/auth.php';
$student = require_role('student');
$back = $_POST['return'] ?? '';
$back = in_array($back, ['details', 'list'], true) ? $back : 'list';
$regId = (int)($_POST['registration_id'] ?? 0);
$dest = fn($eid) => $back === 'details' ? 'student/event_details.php?id=' . $eid : 'student/my_registrations.php';
require_post_csrf('student/my_registrations.php');

$pdo = db();
$st = $pdo->prepare('SELECT event_id FROM registrations WHERE registration_id = ? AND student_id = ?');
$st->execute([$regId, $student['user_id']]);
$eventId = (int)$st->fetchColumn();
if (!$eventId) { flash('error', 'Registration not found.'); redirect('student/my_registrations.php'); }

try {
    $pdo->beginTransaction();
    $st = $pdo->prepare('SELECT * FROM events WHERE event_id = ? FOR UPDATE'); $st->execute([$eventId]); $ev = $st->fetch();
    $st = $pdo->prepare('SELECT * FROM registrations WHERE registration_id = ? AND student_id = ? FOR UPDATE'); $st->execute([$regId, $student['user_id']]); $reg = $st->fetch();

    if (!$reg || !in_array($reg['registration_status'], ['confirmed', 'waitlisted'], true)) {
        $pdo->rollBack(); flash('error', 'This registration cannot be cancelled.'); redirect($dest($eventId));
    }
    if (!$ev['allow_cancellation']) { $pdo->rollBack(); flash('error', 'This event does not allow cancellations.'); redirect($dest($eventId)); }
    if (event_start_ts($ev) <= time()) { $pdo->rollBack(); flash('error', 'You cannot cancel after the event has started.'); redirect($dest($eventId)); }

    $pdo->prepare('UPDATE registrations SET registration_status = "cancelled" WHERE registration_id = ?')->execute([$regId]);

    // A confirmed seat was freed: promote the longest-waiting student.
    if ($reg['registration_status'] === 'confirmed') {
        $st = $pdo->prepare('SELECT registration_id FROM registrations WHERE event_id = ? AND registration_status = "waitlisted" ORDER BY registration_date ASC, registration_id ASC LIMIT 1');
        $st->execute([$eventId]);
        if ($next = $st->fetchColumn()) {
            $pdo->prepare('UPDATE registrations SET registration_status = "confirmed" WHERE registration_id = ?')->execute([$next]);
        }
    }
    $pdo->commit();
} catch (PDOException $ex) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    flash('error', 'Could not cancel the registration. Please try again.');
    redirect($dest($eventId));
}
flash('success', 'Your registration has been cancelled.');
redirect($dest($eventId));
