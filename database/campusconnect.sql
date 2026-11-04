-- CampusConnect database schema + seed accounts
-- Import with phpMyAdmin (Import tab) or:  mysql -u root < database/campusconnect.sql

CREATE DATABASE IF NOT EXISTS campusconnect_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE campusconnect_db;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS password_resets, registrations, events, users;
SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE users (
  user_id        INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name           VARCHAR(100) NOT NULL,
  email          VARCHAR(150) NOT NULL,
  student_id     VARCHAR(30)  NULL,
  password_hash  VARCHAR(255) NOT NULL,
  department     VARCHAR(100) NULL,
  academic_year  TINYINT UNSIGNED NULL,
  phone          VARCHAR(20)  NULL,
  role           ENUM('admin','student') NOT NULL DEFAULT 'student',
  account_status ENUM('active','inactive') NOT NULL DEFAULT 'active',
  created_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_users_email (email),
  UNIQUE KEY uq_users_student_id (student_id),
  KEY idx_users_role (role)
) ENGINE=InnoDB;

CREATE TABLE events (
  event_id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  title                 VARCHAR(200) NOT NULL,
  description           TEXT NOT NULL,
  category              VARCHAR(50)  NOT NULL,
  poster                VARCHAR(255) NULL,
  event_date            DATE NOT NULL,
  start_time            TIME NOT NULL,
  end_time              TIME NOT NULL,
  venue                 VARCHAR(200) NOT NULL,
  department            VARCHAR(100) NOT NULL,
  registration_open     DATETIME NOT NULL,
  registration_deadline DATETIME NOT NULL,
  capacity              INT UNSIGNED NOT NULL,
  eligibility           TEXT NULL,
  eligible_department   VARCHAR(100) NOT NULL DEFAULT 'All',
  eligible_year         VARCHAR(10)  NOT NULL DEFAULT 'All',
  contact_email         VARCHAR(150) NULL,
  contact_phone         VARCHAR(20)  NULL,
  allow_cancellation    TINYINT(1) NOT NULL DEFAULT 1,
  event_status          ENUM('draft','published','registration_closed','completed','cancelled','archived') NOT NULL DEFAULT 'draft',
  created_by            INT UNSIGNED NOT NULL,
  created_at            TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at            TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_events_status_date (event_status, event_date),
  KEY idx_events_category (category),
  CONSTRAINT fk_events_admin FOREIGN KEY (created_by) REFERENCES users(user_id) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- registrations.student_id references users.user_id (the internal id of the student account)
CREATE TABLE registrations (
  registration_id     INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  registration_code   VARCHAR(30) NOT NULL,
  event_id            INT UNSIGNED NOT NULL,
  student_id          INT UNSIGNED NOT NULL,
  registration_date   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  registration_status ENUM('confirmed','waitlisted','cancelled') NOT NULL DEFAULT 'confirmed',
  attendance_status   ENUM('pending','present','absent') NOT NULL DEFAULT 'pending',
  UNIQUE KEY uq_registration_code (registration_code),
  UNIQUE KEY uq_event_student (event_id, student_id),
  KEY idx_reg_student (student_id),
  KEY idx_reg_event_status (event_id, registration_status),
  CONSTRAINT fk_reg_event   FOREIGN KEY (event_id)   REFERENCES events(event_id) ON DELETE CASCADE,
  CONSTRAINT fk_reg_student FOREIGN KEY (student_id) REFERENCES users(user_id)   ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE password_resets (
  reset_id   INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id    INT UNSIGNED NOT NULL,
  token_hash CHAR(64) NOT NULL,
  expires_at DATETIME NOT NULL,
  used       TINYINT(1) NOT NULL DEFAULT 0,
  KEY idx_reset_token (token_hash),
  CONSTRAINT fk_reset_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Seed accounts
--   Admin   : admin@campusconnect.edu / Admin@123
--   Students: S2024001, S2024002, S2024003 (or their emails) / Student@123
INSERT INTO users (name,email,student_id,password_hash,department,academic_year,phone,role) VALUES
('College Administrator','admin@campusconnect.edu',NULL,'$2y$10$G8CCN2DLO7OiuUdk/AotXu4UUokuTyHBlNMpljFrveqdZOocfsiAK','Administration',NULL,NULL,'admin'),
('Aarav Kumar','aarav@campusconnect.edu','S2024001','$2y$10$RTwVONPpgKlb6yotJ8YqQ.7Gg6Vuzti.wmperdlxL6iDIbv1f9QWu','Computer Science & Engineering',3,'9876500001','student'),
('Meera Nair','meera@campusconnect.edu','S2024002','$2y$10$RTwVONPpgKlb6yotJ8YqQ.7Gg6Vuzti.wmperdlxL6iDIbv1f9QWu','Information Technology',2,'9876500002','student'),
('Rahul Verma','rahul@campusconnect.edu','S2024003','$2y$10$RTwVONPpgKlb6yotJ8YqQ.7Gg6Vuzti.wmperdlxL6iDIbv1f9QWu','Mechanical Engineering',4,'9876500003','student');
