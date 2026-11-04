<?php
/** includes/auth.php - session bootstrap, login state and role-based access control. Include first on every page. */
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_name('CAMPUSCONNECTSESS');
    session_set_cookie_params([
        'lifetime' => 0, 'path' => '/', 'httponly' => true, 'samesite' => 'Lax',
        'secure' => !empty($_SERVER['HTTPS']),
    ]);
    session_start();
}
header('X-Frame-Options: SAMEORIGIN');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: same-origin');

const SESSION_IDLE_SECONDS = 7200; // log out after 2 hours of inactivity
if (!empty($_SESSION['user_id'])) {
    if (time() - ($_SESSION['last_active'] ?? time()) > SESSION_IDLE_SECONDS) {
        $_SESSION = [];
        session_regenerate_id(true);
        flash('warning', 'You were logged out because of inactivity.');
    } else {
        $_SESSION['last_active'] = time();
    }
}

/** Fresh user row from the database (so role / status changes apply immediately). */
function current_user(): ?array {
    static $user = false;
    if ($user === false) {
        $user = null;
        if (!empty($_SESSION['user_id'])) {
            $st = db()->prepare('SELECT * FROM users WHERE user_id = ? AND account_status = "active"');
            $st->execute([(int)$_SESSION['user_id']]);
            $user = $st->fetch() ?: null;
            if (!$user) { $_SESSION = []; }
        }
    }
    return $user;
}

/** Log the user in: regenerates the session id to prevent fixation. */
function login_user(array $user): void {
    session_regenerate_id(true);
    $_SESSION = [
        'user_id' => (int)$user['user_id'], 'role' => $user['role'],
        'last_active' => time(), 'csrf' => bin2hex(random_bytes(32)),
    ];
}

function logout_user(): void {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

/** Guard for protected pages. Verifies the role against the DATABASE, not just the session. */
function require_role(string $role): array {
    $u = current_user();
    if (!$u) {
        flash('warning', 'Please log in to continue.');
        redirect($role === 'admin' ? 'admin_login.php' : 'student_login.php');
    }
    if ($u['role'] !== $role) {
        http_response_code(403);
        flash('error', 'You do not have permission to open that page.');
        redirect($u['role'] === 'admin' ? 'admin/dashboard.php' : 'student/dashboard.php');
    }
    return $u;
}

/** For login/register pages: send logged-in people to their own dashboard. */
function redirect_if_logged_in(): void {
    $u = current_user();
    if ($u) redirect($u['role'] === 'admin' ? 'admin/dashboard.php' : 'student/dashboard.php');
}

/** Simple brute-force throttle: 5 failures per session -> 5 minute lock. */
function login_throttled(): bool {
    return ($_SESSION['lock_until'] ?? 0) > time();
}
function login_failed(): void {
    $_SESSION['fails'] = ($_SESSION['fails'] ?? 0) + 1;
    if ($_SESSION['fails'] >= 5) { $_SESSION['lock_until'] = time() + 300; $_SESSION['fails'] = 0; }
}
