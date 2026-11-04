<?php
require_once __DIR__ . '/../includes/auth.php';
$admin = require_role('admin');
require_post_csrf('admin/create_event.php');

[$errors, $d] = validate_event_input();
if (!in_array($d['event_status'], ['draft', 'published'], true)) $d['event_status'] = 'draft';
[$poster, $upErr] = handle_poster_upload('poster');
if ($upErr) $errors[] = $upErr;

if ($errors) {
    if ($poster) delete_poster($poster);
    $_SESSION['form_errors'] = $errors;
    $_SESSION['old'] = $d;
    redirect('admin/create_event.php');
}

$st = db()->prepare('INSERT INTO events
  (title,description,category,poster,event_date,start_time,end_time,venue,department,registration_open,registration_deadline,
   capacity,eligibility,eligible_department,eligible_year,contact_email,contact_phone,allow_cancellation,event_status,created_by)
  VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
$st->execute([
    $d['title'], $d['description'], $d['category'], $poster, $d['event_date'], $d['start_time'], $d['end_time'],
    $d['venue'], $d['department'], $d['registration_open'], $d['registration_deadline'], (int)$d['capacity'],
    $d['eligibility'] ?: null, $d['eligible_department'], $d['eligible_year'], $d['contact_email'] ?: null,
    $d['contact_phone'] ?: null, $d['allow_cancellation'], $d['event_status'], $admin['user_id'],
]);
clear_old();
flash('success', $d['event_status'] === 'published'
    ? 'Event published. Students can now see it on their dashboard.'
    : 'Event saved as a draft. It is not visible to students yet.');
redirect('admin/manage_events.php');
