<?php
require_once __DIR__ . '/../includes/auth.php';
$admin = require_role('admin');

$q = trim($_GET['q'] ?? ''); $fs = $_GET['status'] ?? ''; $fc = $_GET['category'] ?? '';
$sql = 'SELECT e.*, (SELECT COUNT(*) FROM registrations r WHERE r.event_id = e.event_id AND r.registration_status = "confirmed") AS taken
        FROM events e WHERE 1=1'; $p = [];
if ($q !== '') { $sql .= ' AND (e.title LIKE ? OR e.venue LIKE ?)'; $p[] = "%$q%"; $p[] = "%$q%"; }
if ($fc !== '' && in_array($fc, CATEGORIES, true)) { $sql .= ' AND e.category = ?'; $p[] = $fc; }
if ($fs === '') { $sql .= ' AND e.event_status <> "archived"'; }
$sql .= ' ORDER BY e.event_date DESC, e.event_id DESC';
$st = db()->prepare($sql); $st->execute($p);
$rows = $st->fetchAll();
// status filter uses the effective (time-aware) status, so it is applied after the query
if ($fs !== '' && in_array($fs, EVENT_STATUSES, true)) {
    $rows = array_values(array_filter($rows, fn($r) => $fs === 'archived' ? $r['event_status'] === 'archived' : effective_status($r) === $fs));
}

$layout = 'admin'; $activeNav = 'manage'; $pageTitle = 'Manage events';
require __DIR__ . '/../includes/header.php';

function action_btn(int $id, string $do, string $label, string $cls = 'btn-outline', ?string $confirm = null): string {
    return '<form method="post" action="' . e(url('actions/event_status.php')) . '" class="inline">' . csrf_field()
        . '<input type="hidden" name="event_id" value="' . $id . '"><input type="hidden" name="do" value="' . e($do) . '">'
        . '<button type="submit" class="btn btn-sm ' . $cls . '"' . ($confirm ? ' data-confirm="' . e($confirm) . '"' : '') . '>' . e($label) . '</button></form>';
}
?>
<form method="get" class="toolbar card">
  <input type="search" name="q" placeholder="Search title or venue" value="<?= e($q) ?>" aria-label="Search events">
  <select name="category" aria-label="Category"><option value="">All categories</option>
    <?php foreach (CATEGORIES as $c): ?><option <?= $fc === $c ? 'selected' : '' ?>><?= e($c) ?></option><?php endforeach; ?></select>
  <select name="status" aria-label="Status"><option value="">All (except archived)</option>
    <?php foreach (EVENT_STATUSES as $s): ?><option value="<?= $s ?>" <?= $fs === $s ? 'selected' : '' ?>><?= e(status_label($s)) ?></option><?php endforeach; ?></select>
  <button class="btn btn-primary" type="submit">Filter</button>
  <a class="btn btn-ghost" href="<?= e(url('admin/manage_events.php')) ?>">Reset</a>
  <a class="btn btn-secondary push-right" href="<?= e(url('admin/create_event.php')) ?>">Create event</a>
</form>

<div class="card">
<?php if (!$rows): ?><p class="empty">No events match. <a href="<?= e(url('admin/create_event.php')) ?>">Create an event</a>.</p><?php else: ?>
<div class="table-wrap"><table class="table">
  <thead><tr><th>Event</th><th>Category</th><th>Date</th><th>Venue</th><th>Seats</th><th>Status</th><th>Actions</th></tr></thead>
  <tbody>
  <?php foreach ($rows as $r): $eff = effective_status($r); $id = (int)$r['event_id']; $stored = $r['event_status']; ?>
    <tr>
      <td><strong><?= e($r['title']) ?></strong><br><small><?= e($r['department']) ?></small></td>
      <td><?= e($r['category']) ?></td>
      <td><?= e(fmt_date($r['event_date'])) ?><br><small><?= e(fmt_time($r['start_time'])) ?> - <?= e(fmt_time($r['end_time'])) ?></small></td>
      <td><?= e($r['venue']) ?></td>
      <td><?= (int)$r['taken'] ?>/<?= (int)$r['capacity'] ?></td>
      <td><?= status_badge($eff) ?></td>
      <td class="actions">
        <a class="btn btn-sm btn-outline" href="<?= e(url('admin/view_registrations.php?event_id=' . $id)) ?>">View</a>
        <a class="btn btn-sm btn-outline" href="<?= e(url('admin/edit_event.php?id=' . $id)) ?>">Edit</a>
        <?php if ($stored === 'draft'): ?><?= action_btn($id, 'publish', 'Publish', 'btn-success') ?>
        <?php elseif ($stored === 'published'): ?><?= action_btn($id, 'unpublish', 'Unpublish', 'btn-outline', 'Hide this event from students and move it back to draft?') ?>
        <?php elseif ($stored === 'registration_closed'): ?><?= action_btn($id, 'reopen', 'Reopen', 'btn-outline') ?>
        <?php elseif ($stored === 'cancelled'): ?><?= action_btn($id, 'publish', 'Publish', 'btn-success') ?>
        <?php endif; ?>
        <?php if ($stored === 'published'): ?><?= action_btn($id, 'close', 'Close reg.', 'btn-outline') ?><?php endif; ?>
        <?php if (in_array($stored, ['published', 'registration_closed'], true)): ?><?= action_btn($id, 'cancel', 'Cancel', 'btn-warning', 'Cancel this event? Students will see it as cancelled.') ?><?php endif; ?>
        <?php if ($stored === 'archived'): ?><?= action_btn($id, 'restore', 'Restore', 'btn-outline') ?>
        <?php else: ?><?= action_btn($id, 'archive', 'Archive', 'btn-outline', 'Archive this event? It will be hidden from students.') ?><?php endif; ?>
        <?= action_btn($id, 'delete', 'Delete', 'btn-danger', 'Permanently delete this event and ALL of its registrations? This cannot be undone.') ?>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody></table></div>
<?php endif; ?>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
