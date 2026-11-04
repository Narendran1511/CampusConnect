<?php
/** student/_data.php - helper: all registrations of a student with event info and computed flags. */
function student_registrations(int $studentId): array {
    $st = db()->prepare('SELECT r.*, e.title, e.event_date, e.start_time, e.end_time, e.venue, e.department AS event_dept, e.category,
        e.event_status, e.registration_deadline, e.allow_cancellation, e.poster
        FROM registrations r JOIN events e ON e.event_id = r.event_id
        WHERE r.student_id = ? ORDER BY e.event_date DESC, e.start_time DESC');
    $st->execute([$studentId]);
    $rows = $st->fetchAll();
    foreach ($rows as &$r) {
        $r['effective'] = effective_status($r);
        $r['finished'] = $r['effective'] === 'completed';
        $r['can_cancel'] = in_array($r['registration_status'], ['confirmed', 'waitlisted'], true)
            && $r['allow_cancellation'] && !in_array($r['effective'], ['completed', 'cancelled'], true) && event_start_ts($r) > time();
    }
    return $rows;
}
