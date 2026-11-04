<?php
require_once __DIR__ . '/../includes/auth.php';
$student = require_role('student');
$eventId = (int)($_POST['event_id'] ?? 0);
$back = 'student/event_details.php?id=' . $eventId;
require_post_csrf($back);
$wantsWaitlist = isset($_POST['waitlist']);

$pdo = db();
try {
    $pdo->beginTransaction();
    // Lock the event row: concurrent registrations for the same event are serialized, so seats can never be oversold.
    $st = $pdo->prepare('SELECT * FROM events WHERE event_id = ? FOR UPDATE');
    $st->execute([$eventId]);
    $ev = $st->fetch();
    if (!$ev || in_array($ev['event_status'], ['draft', 'archived'], true)) {
        $pdo->rollBack(); flash('error', 'This event is not available.'); redirect('student/explore_events.php');
    }
    $st = $pdo->prepare('SELECT * FROM registrations WHERE event_id = ? AND student_id = ? FOR UPDATE');
    $st->execute([$eventId, $student['user_id']]);
    $existing = $st->fetch() ?: null;

    $st = $pdo->prepare('SELECT COUNT(*) FROM registrations WHERE event_id = ? AND registration_status = "confirmed"');
    $st->execute([$eventId]);
    $taken = (int)$st->fetchColumn();

    $chk = registration_check($ev, $student, $taken, $existing);
    $status = 'confirmed';
    if (!$chk['ok']) {
        if ($chk['code'] === 'full' && $wantsWaitlist) { $status = 'waitlisted'; }
        else { $pdo->rollBack(); flash('error', $chk['reason']); redirect($back); }
    }

    $code = generate_registration_code();
    if ($existing) { // previously cancelled: reuse the row (unique event+student constraint)
        $pdo->prepare('UPDATE registrations SET registration_code=?, registration_status=?, attendance_status="pending", registration_date=? WHERE registration_id=?')
            ->execute([$code, $status, date('Y-m-d H:i:s'), $existing['registration_id']]);
    } else {
        $pdo->prepare('INSERT INTO registrations (registration_code, event_id, student_id, registration_date, registration_status) VALUES (?,?,?,?,?)')
            ->execute([$code, $eventId, $student['user_id'], date('Y-m-d H:i:s'), $status]);
    }
    $pdo->commit();
} catch (PDOException $ex) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    flash('error', $ex->getCode() === '23000' ? 'You have already registered for this event.' : 'Registration failed. Please try again.');
    redirect($back);
}

flash('success', $status === 'confirmed'
    ? 'Registration successful! Your registration ID is ' . $code . '.'
    : 'The event is full. You have been added to the waitlist (ID ' . $code . '). We will confirm you if a seat opens up.');
redirect($back);
