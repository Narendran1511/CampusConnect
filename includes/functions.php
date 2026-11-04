<?php
/** includes/functions.php - shared helpers (escaping, flash, CSRF, events, uploads). */

const CATEGORIES = ['Workshops','Hackathons','Technical Symposiums','Seminars','Coding Competitions','Paper Presentations','Cultural Events','Other College Events'];
const DEPARTMENTS = ['Computer Science & Engineering','Information Technology','Electronics & Communication','Electrical & Electronics','Mechanical Engineering','Civil Engineering','Artificial Intelligence & Data Science','Business Administration','Arts & Science'];
const ORGANIZERS_EXTRA = ['Student Council','Training & Placement Cell','Library','Sports Department'];
const YEARS = [1, 2, 3, 4];
const EVENT_STATUSES = ['draft','published','registration_closed','completed','cancelled','archived'];

function e($v): string { return htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function url(string $path = ''): string { return BASE_URL . '/' . ltrim($path, '/'); }
function redirect(string $path): void { header('Location: ' . url($path)); exit; }

/* ---------- flash messages ---------- */
function flash(string $type, string $msg): void { $_SESSION['flash'][] = ['type' => $type, 'msg' => $msg]; }
function flash_render(): string {
    $out = '';
    foreach ($_SESSION['flash'] ?? [] as $f) {
        $out .= '<div class="alert alert-' . e($f['type']) . '" role="alert">' . e($f['msg']) . '</div>';
    }
    unset($_SESSION['flash']);
    return $out;
}

/* ---------- CSRF ---------- */
function csrf_token(): string {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}
function csrf_field(): string { return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">'; }
function csrf_verify(): bool {
    $t = $_POST['csrf_token'] ?? '';
    return is_string($t) && !empty($_SESSION['csrf']) && hash_equals($_SESSION['csrf'], $t);
}
/** Call at the top of every POST action. */
function require_post_csrf(string $back): void {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') { redirect($back); }
    if (!csrf_verify()) { flash('error', 'Your session expired or the request was invalid. Please try again.'); redirect($back); }
}

/* ---------- input helpers ---------- */
function post(string $k, string $default = ''): string { return trim((string)($_POST[$k] ?? $default)); }
function old(string $k, $default = '') { return $_SESSION['old'][$k] ?? $default; }
function clear_old(): void { unset($_SESSION['old']); }

/* ---------- formatting ---------- */
function fmt_date(?string $d): string { return $d ? date('D, d M Y', strtotime($d)) : '-'; }
function fmt_time(?string $t): string { return $t ? date('g:i A', strtotime($t)) : '-'; }
function fmt_dt(?string $d): string { return $d ? date('d M Y, g:i A', strtotime($d)) : '-'; }

/* ---------- event status logic ---------- */
function event_end_ts(array $ev): int { return strtotime($ev['event_date'] . ' ' . $ev['end_time']); }
function event_start_ts(array $ev): int { return strtotime($ev['event_date'] . ' ' . $ev['start_time']); }

/** The status students actually see: stored status adjusted by the clock. */
function effective_status(array $ev): string {
    $s = $ev['event_status'];
    if (in_array($s, ['draft','cancelled','archived','completed'], true)) return $s;
    if (event_end_ts($ev) < time()) return 'completed';
    if ($s === 'registration_closed') return 'registration_closed';
    if (strtotime($ev['registration_deadline']) < time()) return 'registration_closed';
    return 'published';
}
function status_label(string $s): string {
    return ['draft'=>'Draft','published'=>'Published','registration_closed'=>'Registration Closed','completed'=>'Completed','cancelled'=>'Cancelled','archived'=>'Archived'][$s] ?? ucfirst($s);
}
function status_badge(string $s): string {
    $cls = ['draft'=>'muted','published'=>'success','registration_closed'=>'warning','completed'=>'info','cancelled'=>'danger','archived'=>'muted'][$s] ?? 'muted';
    return '<span class="badge badge-' . $cls . '">' . e(status_label($s)) . '</span>';
}
function poster_url(?string $poster): string {
    if ($poster && is_file(ROOT_PATH . '/uploads/event_posters/' . basename($poster))) return url('uploads/event_posters/' . rawurlencode(basename($poster)));
    return url('assets/images/default_poster.svg');
}

/** Number of confirmed seats taken for an event. */
function seats_taken(int $eventId): int {
    $st = db()->prepare("SELECT COUNT(*) FROM registrations WHERE event_id = ? AND registration_status = 'confirmed'");
    $st->execute([$eventId]);
    return (int)$st->fetchColumn();
}

/**
 * Decide whether $student may register for $ev.
 * Returns ['ok'=>bool, 'reason'=>string, 'full'=>bool, 'code'=>string]
 * code: ok | not_visible | not_open | closed | cancelled | completed | ineligible | full | registered
 */
function registration_check(array $ev, array $student, int $taken, ?array $existing): array {
    $res = fn($ok, $code, $reason, $full = false) => ['ok' => $ok, 'code' => $code, 'reason' => $reason, 'full' => $full];
    if (in_array($ev['event_status'], ['draft','archived'], true)) return $res(false, 'not_visible', 'This event is not available.');
    if ($existing && in_array($existing['registration_status'], ['confirmed','waitlisted'], true)) return $res(false, 'registered', 'You have already registered for this event.');
    $eff = effective_status($ev);
    if ($eff === 'cancelled') return $res(false, 'cancelled', 'This event has been cancelled.');
    if ($eff === 'completed') return $res(false, 'completed', 'This event has already finished.');
    if ($eff === 'registration_closed') return $res(false, 'closed', 'Registration for this event is closed.');
    if (strtotime($ev['registration_open']) > time()) return $res(false, 'not_open', 'Registration opens on ' . fmt_dt($ev['registration_open']) . '.');
    if ($ev['eligible_department'] !== 'All' && $ev['eligible_department'] !== $student['department']) return $res(false, 'ineligible', 'This event is only open to ' . $ev['eligible_department'] . ' students.');
    if ($ev['eligible_year'] !== 'All' && (string)$ev['eligible_year'] !== (string)$student['academic_year']) return $res(false, 'ineligible', 'This event is only open to year ' . $ev['eligible_year'] . ' students.');
    if ($taken >= (int)$ev['capacity']) return $res(false, 'full', 'Registration Closed – Maximum Participant Capacity Reached.', true);
    return $res(true, 'ok', 'You can register for this event.');
}

function generate_registration_code(): string {
    return 'CC' . date('ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));
}

/* ---------- poster upload ---------- */
/** Returns [filename|null, error|null]. Validates MIME with finfo, size and extension; renames randomly. */
function handle_poster_upload(string $field = 'poster'): array {
    if (empty($_FILES[$field]) || $_FILES[$field]['error'] === UPLOAD_ERR_NO_FILE) return [null, null];
    $f = $_FILES[$field];
    if ($f['error'] !== UPLOAD_ERR_OK) return [null, 'Poster upload failed. Please try again.'];
    if ($f['size'] > MAX_POSTER_BYTES) return [null, 'Poster must be 2 MB or smaller.'];
    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);
    if (!isset($allowed[$mime])) return [null, 'Poster must be a JPG, PNG or WebP image.'];
    if (@getimagesize($f['tmp_name']) === false) return [null, 'The uploaded file is not a valid image.'];
    $dir = ROOT_PATH . '/uploads/event_posters';
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    $name = bin2hex(random_bytes(12)) . '.' . $allowed[$mime];
    if (!move_uploaded_file($f['tmp_name'], $dir . '/' . $name)) return [null, 'Could not save the poster. Check folder permissions on uploads/event_posters.'];
    return [$name, null];
}
function delete_poster(?string $name): void {
    if ($name) { $p = ROOT_PATH . '/uploads/event_posters/' . basename($name); if (is_file($p)) @unlink($p); }
}

/* ---------- event form validation (shared by create + update) ---------- */
/** Returns [errors[], data[]] */
function validate_event_input(): array {
    $err = [];
    $d = [
        'title' => post('title'), 'description' => post('description'), 'category' => post('category'),
        'event_date' => post('event_date'), 'start_time' => post('start_time'), 'end_time' => post('end_time'),
        'venue' => post('venue'), 'department' => post('department'),
        'registration_open' => str_replace('T', ' ', post('registration_open')),
        'registration_deadline' => str_replace('T', ' ', post('registration_deadline')),
        'capacity' => post('capacity'), 'eligibility' => post('eligibility'),
        'eligible_department' => post('eligible_department', 'All'), 'eligible_year' => post('eligible_year', 'All'),
        'contact_email' => post('contact_email'), 'contact_phone' => post('contact_phone'),
        'allow_cancellation' => isset($_POST['allow_cancellation']) ? 1 : 0,
        'event_status' => post('event_status', 'draft'),
    ];
    $validDept = array_merge(DEPARTMENTS, ORGANIZERS_EXTRA);
    if ($d['title'] === '' || mb_strlen($d['title']) > 200) $err[] = 'Event title is required (max 200 characters).';
    if ($d['description'] === '') $err[] = 'Event description is required.';
    if (!in_array($d['category'], CATEGORIES, true)) $err[] = 'Select a valid event category.';
    $dateOk = (bool)preg_match('/^\d{4}-\d{2}-\d{2}$/', $d['event_date']) && strtotime($d['event_date']) !== false;
    if (!$dateOk) $err[] = 'Enter a valid event date.';
    $timeRe = '/^\d{2}:\d{2}(:\d{2})?$/';
    if (!preg_match($timeRe, $d['start_time']) || !preg_match($timeRe, $d['end_time'])) $err[] = 'Enter valid start and end times.';
    elseif (strtotime($d['end_time']) <= strtotime($d['start_time'])) $err[] = 'End time must be after the start time.';
    if ($d['venue'] === '') $err[] = 'Venue is required.';
    if (!in_array($d['department'], $validDept, true)) $err[] = 'Select a valid organizing department.';
    $o = strtotime($d['registration_open']); $dl = strtotime($d['registration_deadline']);
    if (!$o || !$dl) $err[] = 'Enter valid registration opening and deadline date/times.';
    else {
        if ($dl <= $o) $err[] = 'Registration deadline must be after registration opens.';
        if ($dateOk && $dl > strtotime($d['event_date'] . ' ' . $d['end_time'])) $err[] = 'Registration deadline cannot be after the event ends.';
    }
    if (!ctype_digit($d['capacity']) || (int)$d['capacity'] < 1 || (int)$d['capacity'] > 100000) $err[] = 'Capacity must be a whole number of at least 1.';
    if ($d['eligible_department'] !== 'All' && !in_array($d['eligible_department'], DEPARTMENTS, true)) $err[] = 'Invalid eligible department.';
    if ($d['eligible_year'] !== 'All' && !in_array((int)$d['eligible_year'], YEARS, true)) $err[] = 'Invalid eligible year.';
    if ($d['contact_email'] !== '' && !filter_var($d['contact_email'], FILTER_VALIDATE_EMAIL)) $err[] = 'Contact email is not valid.';
    if ($d['contact_phone'] !== '' && !preg_match('/^[0-9+\-\s]{7,20}$/', $d['contact_phone'])) $err[] = 'Contact phone is not valid.';
    if (!in_array($d['event_status'], ['draft','published','registration_closed','cancelled'], true)) $err[] = 'Invalid event status.';
    return [$err, $d];
}

/* ---------- CSV ---------- */
function csv_cell($v): string {
    $v = (string)$v;
    if ($v !== '' && strpbrk($v[0], "=+-@\t\r") !== false) $v = "'" . $v; // block spreadsheet formula injection
    return $v;
}
function csv_download(string $filename, array $header, array $rows): void {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF");
    fputcsv($out, $header);
    foreach ($rows as $r) fputcsv($out, array_map('csv_cell', $r));
    fclose($out);
    exit;
}
