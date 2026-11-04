<?php
/** Admin one-click actions from the events table: publish, unpublish, close, reopen, cancel, archive, restore, delete. */
require_once __DIR__ . '/../includes/auth.php';
$admin = require_role('admin');
require_post_csrf('admin/manage_events.php');

$id = (int)($_POST['event_id'] ?? 0);
$do = $_POST['do'] ?? '';
$st = db()->prepare('SELECT * FROM events WHERE event_id = ?'); $st->execute([$id]);
$ev = $st->fetch();
if (!$ev) { flash('error', 'Event not found.'); redirect('admin/manage_events.php'); }

$map = [
    'publish'   => ['published', 'Event published. It is now visible to students.'],
    'unpublish' => ['draft', 'Event moved back to draft and hidden from students.'],
    'close'     => ['registration_closed', 'Registration closed for this event.'],
    'reopen'    => ['published', 'Registration reopened.'],
    'cancel'    => ['cancelled', 'Event cancelled. Students will see it marked as cancelled.'],
    'archive'   => ['archived', 'Event archived and hidden from students.'],
    'restore'   => ['draft', 'Event restored as a draft.'],
];
if ($do === 'delete') {
    db()->prepare('DELETE FROM events WHERE event_id = ?')->execute([$id]); // registrations cascade
    delete_poster($ev['poster']);
    flash('success', 'Event and its registrations were permanently deleted.');
} elseif (isset($map[$do])) {
    db()->prepare('UPDATE events SET event_status = ? WHERE event_id = ?')->execute([$map[$do][0], $id]);
    flash('success', $map[$do][1]);
} else {
    flash('error', 'Unknown action.');
}
redirect('admin/manage_events.php');
