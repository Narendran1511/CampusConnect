<?php
/** JSON feed of events visible to the logged-in student. Polled by assets/js/script.js so new events appear automatically. */
require_once __DIR__ . '/../includes/auth.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
$u = current_user();
if (!$u || $u['role'] !== 'student') { http_response_code(401); echo json_encode(['error' => 'unauthorized']); exit; }

$st = db()->prepare('SELECT e.*,
    (SELECT COUNT(*) FROM registrations r WHERE r.event_id = e.event_id AND r.registration_status = "confirmed") AS taken,
    (SELECT r2.registration_status FROM registrations r2 WHERE r2.event_id = e.event_id AND r2.student_id = ?) AS my_status
    FROM events e WHERE e.event_status IN ("published","registration_closed","completed","cancelled")
    ORDER BY e.event_date ASC, e.start_time ASC');
$st->execute([$u['user_id']]);

$out = [];
foreach ($st->fetchAll() as $r) {
    $existing = $r['my_status'] ? ['registration_status' => $r['my_status']] : null;
    $chk = registration_check($r, $u, (int)$r['taken'], $existing);
    $out[] = [
        'id' => (int)$r['event_id'], 'title' => $r['title'], 'category' => $r['category'],
        'poster' => poster_url($r['poster']),
        'date_iso' => $r['event_date'], 'date' => fmt_date($r['event_date']),
        'time' => fmt_time($r['start_time']) . ' - ' . fmt_time($r['end_time']),
        'ts' => event_start_ts($r), 'created' => strtotime($r['created_at']),
        'venue' => $r['venue'], 'department' => $r['department'],
        'deadline' => fmt_dt($r['registration_deadline']),
        'capacity' => (int)$r['capacity'], 'taken' => (int)$r['taken'],
        'available' => max(0, (int)$r['capacity'] - (int)$r['taken']),
        'status' => effective_status($r),
        'my_status' => $r['my_status'],            // confirmed | waitlisted | cancelled | null
        'can_register' => $chk['ok'], 'reg_code' => $chk['code'],
    ];
}
echo json_encode(['events' => $out, 'server_time' => time()], JSON_UNESCAPED_UNICODE);
