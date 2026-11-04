# CampusConnect – Event Management & Student Portal

Vanilla **HTML5 / CSS3 / JavaScript** front end with a **PHP 8 (PDO) + MySQL** back end. No frameworks.

**Core workflow:** an administrator creates and publishes an event → it is saved in MySQL → it appears on every logged-in student's dashboard automatically (the student pages read published events from MySQL through `actions/events_feed.php` and refresh every 30 seconds, so nobody has to reload or ask an administrator).

---

## 1. Set up with XAMPP (Phases 1–2)

1. Install XAMPP (PHP 8.0+). Start **Apache** and **MySQL** in the XAMPP Control Panel.
2. Copy this whole `campusconnect` folder to `C:\xampp\htdocs\campusconnect` (macOS: `/Applications/XAMPP/htdocs/`, Linux: `/opt/lampp/htdocs/`).
3. Open **http://localhost/phpmyadmin** → **Import** → choose `database/campusconnect.sql` → **Import**.
   This creates `campusconnect_db`, all tables, and the demo accounts.
4. *(Optional)* import `database/sample_events.sql` for four example events (three published, one draft).
5. If your MySQL root password is not empty, edit `includes/db.php` (`DB_USER`, `DB_PASS`).
6. Make sure `uploads/event_posters/` is writable (automatic on Windows; on Linux/macOS: `chmod 775 uploads/event_posters`).
7. Open **http://localhost/campusconnect/**. The URL prefix is detected automatically, so the folder can have any name.

> `fileinfo` and `pdo_mysql` PHP extensions must be enabled (they are by default in XAMPP).

### Demo accounts

| Role | Login | Password |
|---|---|---|
| Administrator | `admin@campusconnect.edu` (at `/admin_login.php`) | `Admin@123` |
| Student | `S2024001` or `aarav@campusconnect.edu` (at `/student_login.php`) | `Student@123` |
| Student | `S2024002` / `S2024003` | `Student@123` |

New students can also sign up at `/register.php`. **Change the admin password before any real use.**

---

## 2. Project structure

```
campusconnect/
├── index.php  admin_login.php  student_login.php  register.php  logout.php
├── forgot_password.php  reset_password.php
├── admin/      dashboard · create_event · manage_events · edit_event · view_registrations · reports
├── student/    dashboard · explore_events · event_details · my_registrations · event_history
│               certificates · certificate · profile · _data (helper)
├── actions/    login · create_event · update_event · event_status · attendance
│               register_event · cancel_registration · update_profile · events_feed (JSON)
├── includes/   db · auth · functions · header · footer · event_form
├── assets/     css/style.css · js/script.js · images/default_poster.svg
├── uploads/event_posters/      (.htaccess blocks script execution)
├── database/   campusconnect.sql · sample_events.sql
└── README.md
```

| Phase | Where it lives |
|---|---|
| 1 Setup, 2 Database | section 1 above, `database/campusconnect.sql` |
| 3 Authentication | `admin_login.php`, `student_login.php`, `actions/login.php`, `includes/auth.php`, `register.php`, `forgot_password.php` |
| 4 Admin dashboard | `admin/dashboard.php` |
| 5 Event create / edit / publish / cancel | `admin/create_event.php`, `edit_event.php`, `manage_events.php`, `actions/create_event.php`, `update_event.php`, `event_status.php` |
| 6–7 Student dashboard + live events | `student/dashboard.php`, `actions/events_feed.php`, `assets/js/script.js` |
| 8 Details + search/filter | `student/event_details.php`, `student/explore_events.php` |
| 9 Registration + capacity | `actions/register_event.php`, `actions/cancel_registration.php` |
| 10 My Registrations | `student/my_registrations.php` |

---

## 3. Test the full workflow (Phase 11)

1. Log in as **admin** → Dashboard shows statistics.
2. **Create event** → fill the form, upload a poster (JPG/PNG/WebP ≤ 2 MB), click **Publish event** (or **Save as draft**).
3. In a second browser (or private window) log in as **student S2024001**. The event is on the **Dashboard** and **Explore events**; a draft is not. Keep that page open and publish another event as admin – it appears within 30 seconds without a reload.
4. Search by title, click a category chip, filter by department/date, switch to *All* / *Completed*, change the sort order.
5. Open **View Details** → **REGISTER NOW** → you get a registration ID. Click it again → "already registered".
6. Create an event with capacity 1. Register as S2024001, then as S2024002 → *"Registration Closed – Maximum Participant Capacity Reached."* with a **Join waitlist** button. Cancel S2024001's registration → the waitlisted student is promoted automatically.
7. **My registrations** lists everything with Details / Cancel (cancel is hidden when the event forbids it or has started).
8. Admin → **Registrations** → pick the event: search, filter, see available seats, mark attendance, **Export CSV**.
9. Edit an event's status (Cancel / Close registration / Archive / Delete) from **Manage events** and watch the student view change.
10. For a certificate: set an event date in the past (Edit), mark the student *present*, then open **My certificates**.
11. Log in as a student and open `/admin/dashboard.php` → you are redirected away (role check runs on every request, against the database).

### Event statuses

`draft` (hidden) · `published` · `registration_closed` · `completed` · `cancelled` · `archived` (hidden).
Status shown to students is time-aware: a published event becomes *Registration Closed* after its deadline and *Completed* after its end time automatically.

---

## 4. Security measures implemented

* `password_hash()` / `password_verify()` (+ rehash on login); sessions with HttpOnly, SameSite cookies; `session_regenerate_id()` on login; idle timeout (2 h); proper logout that destroys the session.
* Role-based access: every admin/student page calls `require_role()`, which re-reads the user's role from the database. Admin and student logins use separate forms, and each form only authenticates its own role. Public sign-up can only create `student` accounts.
* All SQL uses PDO prepared statements (no emulation). All output is escaped with `htmlspecialchars` (`e()`); the JavaScript renderer uses `textContent`, never `innerHTML`.
* CSRF token on every state-changing POST; login throttling (5 failed attempts → 5-minute lock).
* Uploads: MIME check with `finfo`, extension from MIME (not from the file name), size limit, `getimagesize()`, random file name, `.htaccess` that denies script execution in `uploads/`.
* Registration runs in a **transaction with `SELECT … FOR UPDATE` on the event row** and a `UNIQUE (event_id, student_id)` constraint, so seats cannot be oversold and duplicates are impossible (tested with parallel requests).
* Server-side validation for every form; JavaScript validation is only a convenience.
* CSV export neutralizes spreadsheet-formula injection.

## 5. Notes and limitations

* **Forgot password:** XAMPP has no mail server, so the reset link is shown on screen ("demo mode"). For production, e-mail the link instead and stop displaying it (see comment in `forgot_password.php`).
* `registrations.student_id` stores the student's internal `users.user_id`; the human-readable Student ID is in `users.student_id`. Each registration also gets a unique public **registration code** (e.g. `CC261101-6228C2`).
* Extra tables/columns beyond the brief: `password_resets`; `users.phone`; `events.eligible_department`, `eligible_year`, `contact_email`, `contact_phone`, `allow_cancellation`, `updated_at`.
* Time zone is `Asia/Kolkata` (change `APP_TIMEZONE` in `includes/db.php`).
* Screenshots (Phase 12) must be taken on your machine after setup; none are bundled.
