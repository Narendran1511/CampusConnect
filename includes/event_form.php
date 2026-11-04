<?php
/**
 * includes/event_form.php - shared create/edit form.
 * Expects: $formAction (url), $ev (array|null - existing event), $csrf via csrf_field()
 * Previously submitted (invalid) values are read from $_SESSION['old'].
 */
$ev = $ev ?? null;
$val = function (string $k, $default = '') use ($ev) {
    if (isset($_SESSION['old'][$k])) return $_SESSION['old'][$k];
    return $ev[$k] ?? $default;
};
$dt = function (string $k) use ($val) {
    $v = (string)$val($k, '');
    return $v ? str_replace(' ', 'T', substr($v, 0, 16)) : '';
};
$time = fn(string $k) => substr((string)$val($k, ''), 0, 5);
$allDepts = array_merge(DEPARTMENTS, ORGANIZERS_EXTRA);
$curStatus = (string)$val('event_status', 'draft');
?>
<form method="post" action="<?= e($formAction) ?>" enctype="multipart/form-data" class="card form-card" data-validate data-event-form novalidate>
  <?= csrf_field() ?>
  <?php if ($ev): ?><input type="hidden" name="event_id" value="<?= (int)$ev['event_id'] ?>"><?php endif; ?>

  <h2 class="section-title">Basic details</h2>
  <div class="field"><label for="title">Event title</label><input id="title" name="title" maxlength="200" required value="<?= e($val('title')) ?>"></div>
  <div class="field"><label for="description">Description</label><textarea id="description" name="description" rows="6" required><?= e($val('description')) ?></textarea></div>
  <div class="grid-2">
    <div class="field"><label for="category">Category</label>
      <select id="category" name="category" required><option value="">Select category</option>
      <?php foreach (CATEGORIES as $c): ?><option <?= $val('category') === $c ? 'selected' : '' ?>><?= e($c) ?></option><?php endforeach; ?></select></div>
    <div class="field"><label for="department">Organizing department</label>
      <select id="department" name="department" required><option value="">Select department</option>
      <?php foreach ($allDepts as $d): ?><option <?= $val('department') === $d ? 'selected' : '' ?>><?= e($d) ?></option><?php endforeach; ?></select></div>
  </div>

  <h2 class="section-title">Poster</h2>
  <div class="poster-field">
    <img id="posterPreview" src="<?= e(poster_url($ev['poster'] ?? null)) ?>" alt="Poster preview">
    <div class="field"><label for="poster">Event poster or banner</label>
      <input type="file" id="poster" name="poster" accept="image/jpeg,image/png,image/webp" data-max-bytes="<?= MAX_POSTER_BYTES ?>">
      <small class="hint">JPG, PNG or WebP, up to 2 MB.<?= $ev && $ev['poster'] ? ' Leave empty to keep the current poster.' : '' ?></small></div>
  </div>

  <h2 class="section-title">Schedule and venue</h2>
  <div class="grid-3">
    <div class="field"><label for="event_date">Event date</label><input type="date" id="event_date" name="event_date" required value="<?= e($val('event_date')) ?>"></div>
    <div class="field"><label for="start_time">Start time</label><input type="time" id="start_time" name="start_time" required value="<?= e($time('start_time')) ?>"></div>
    <div class="field"><label for="end_time">End time</label><input type="time" id="end_time" name="end_time" required value="<?= e($time('end_time')) ?>"></div>
  </div>
  <div class="field"><label for="venue">Venue</label><input id="venue" name="venue" maxlength="200" required value="<?= e($val('venue')) ?>"></div>

  <h2 class="section-title">Registration</h2>
  <div class="grid-3">
    <div class="field"><label for="registration_open">Registration opens</label><input type="datetime-local" id="registration_open" name="registration_open" required value="<?= e($dt('registration_open')) ?>"></div>
    <div class="field"><label for="registration_deadline">Registration deadline</label><input type="datetime-local" id="registration_deadline" name="registration_deadline" required value="<?= e($dt('registration_deadline')) ?>"></div>
    <div class="field"><label for="capacity">Maximum participants</label><input type="number" id="capacity" name="capacity" min="1" max="100000" required value="<?= e($val('capacity')) ?>"></div>
  </div>
  <div class="field"><label for="eligibility">Eligibility requirements</label><textarea id="eligibility" name="eligibility" rows="3" placeholder="e.g. Open to second and third year students"><?= e($val('eligibility')) ?></textarea></div>
  <div class="grid-2">
    <div class="field"><label for="eligible_department">Restrict to department</label>
      <select id="eligible_department" name="eligible_department"><option value="All">All departments</option>
      <?php foreach (DEPARTMENTS as $d): ?><option <?= $val('eligible_department', 'All') === $d ? 'selected' : '' ?>><?= e($d) ?></option><?php endforeach; ?></select></div>
    <div class="field"><label for="eligible_year">Restrict to academic year</label>
      <select id="eligible_year" name="eligible_year"><option value="All">All years</option>
      <?php foreach (YEARS as $y): ?><option value="<?= $y ?>" <?= (string)$val('eligible_year', 'All') === (string)$y ? 'selected' : '' ?>>Year <?= $y ?> only</option><?php endforeach; ?></select></div>
  </div>
  <label class="check"><input type="checkbox" name="allow_cancellation" value="1" <?= $val('allow_cancellation', 1) ? 'checked' : '' ?>> Students may cancel their registration before the event starts</label>

  <h2 class="section-title">Organizer contact (optional)</h2>
  <div class="grid-2">
    <div class="field"><label for="contact_email">Contact email</label><input type="email" id="contact_email" name="contact_email" value="<?= e($val('contact_email')) ?>"></div>
    <div class="field"><label for="contact_phone">Contact phone</label><input id="contact_phone" name="contact_phone" pattern="[0-9+\-\s]{7,20}" value="<?= e($val('contact_phone')) ?>"></div>
  </div>

  <h2 class="section-title">Visibility</h2>
  <?php if ($ev): ?>
  <div class="field"><label for="event_status">Status</label>
    <select id="event_status" name="event_status">
      <?php foreach (['draft'=>'Draft - hidden from students','published'=>'Published - visible to students','registration_closed'=>'Registration closed','cancelled'=>'Cancelled'] as $k => $l): ?>
      <option value="<?= $k ?>" <?= $curStatus === $k ? 'selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?>
    </select>
    <?php if (in_array($ev['event_status'], ['completed', 'archived'], true)): ?><small class="hint">This event is stored as "<?= e(status_label($ev['event_status'])) ?>"; saving will set the status chosen here.</small><?php endif; ?></div>
  <div class="form-actions">
    <button class="btn btn-primary" type="submit">Save changes</button>
    <a class="btn btn-ghost" href="<?= e(url('admin/manage_events.php')) ?>">Back to events</a>
  </div>
  <?php else: ?>
  <input type="hidden" name="event_status" id="event_status" value="draft">
  <div class="form-actions">
    <button class="btn btn-primary" type="submit" data-set-status="published">Publish event</button>
    <button class="btn btn-secondary" type="submit" data-set-status="draft">Save as draft</button>
    <a class="btn btn-ghost" href="<?= e(url('admin/manage_events.php')) ?>">Cancel</a>
  </div>
  <?php endif; ?>
</form>
