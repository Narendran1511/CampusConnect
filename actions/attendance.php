<?php
require_once __DIR__ . '/../includes/auth.php';
$admin = require_role('admin');
$eventId = (int)($_POST['event_id'] ?? 0);
$back = 'admin/view_registrations.php?event_id=' . $eventId;
require_post_csrf($back);
$rid = (int)($_POST['registration_id'] ?? 0);
$att = $_POST['attendance'] ?? '';
if (!in_array($att, ['pending', 'present', 'absent'], true)) { flash('error', 'Invalid attendance value.'); redirect($back); }
db()->prepare('UPDATE registrations SET attendance_status = ? WHERE registration_id = ? AND event_id = ? AND registration_status = "confirmed"')
    ->execute([$att, $rid, $eventId]);
flash('success', 'Attendance updated.');
redirect($back . (isset($_POST['qs']) ? '&' . http_build_query(array_intersect_key($_POST['qs'], array_flip(['q','status','att']))) : ''));
