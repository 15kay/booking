-- WSU Booking System - MySQL Schema
CREATE DATABASE IF NOT EXISTS wsu_booking CHARACTER SET utf8 COLLATE utf8_general_ci;
USE wsu_booking;

CREATE TABLE IF NOT EXISTS faculties (
    faculty_id    INT AUTO_INCREMENT PRIMARY KEY,
    faculty_name  VARCHAR(100) NOT NULL UNIQUE,
    faculty_code  VARCHAR(10)  NOT NULL UNIQUE,
    dean_name     VARCHAR(100),
    contact_email VARCHAR(100),
    status        ENUM('active','inactive') NOT NULL DEFAULT 'active',
    created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS departments (
    department_id      INT AUTO_INCREMENT PRIMARY KEY,
    faculty_id         INT NOT NULL,
    department_name    VARCHAR(100) NOT NULL,
    department_code    VARCHAR(10)  NOT NULL,
    head_of_department VARCHAR(100),
    contact_email      VARCHAR(100),
    status             ENUM('active','inactive') NOT NULL DEFAULT 'active',
    FOREIGN KEY (faculty_id) REFERENCES faculties(faculty_id),
    UNIQUE KEY uq_dept (faculty_id, department_code)
);

CREATE TABLE IF NOT EXISTS admins (
    admin_id      INT AUTO_INCREMENT PRIMARY KEY,
    username      VARCHAR(50)  NOT NULL UNIQUE,
    full_name     VARCHAR(100) NOT NULL,
    email         VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role          ENUM('super_admin','admin') NOT NULL DEFAULT 'admin',
    status        ENUM('active','inactive') NOT NULL DEFAULT 'active',
    last_login    DATETIME NULL,
    created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS students (
    student_id    VARCHAR(20)  PRIMARY KEY,
    first_name    VARCHAR(50)  NOT NULL,
    last_name     VARCHAR(50)  NOT NULL,
    email         VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    phone         VARCHAR(15),
    faculty_id    INT,
    year_of_study TINYINT CHECK (year_of_study BETWEEN 1 AND 6),
    student_type  ENUM('undergraduate','postgraduate','honours','masters','phd') NOT NULL DEFAULT 'undergraduate',
    status        ENUM('active','inactive','suspended','graduated') NOT NULL DEFAULT 'active',
    last_login    DATETIME NULL,
    created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (faculty_id) REFERENCES faculties(faculty_id)
);

CREATE TABLE IF NOT EXISTS staff (
    staff_id        INT AUTO_INCREMENT PRIMARY KEY,
    staff_number    VARCHAR(20)  NOT NULL UNIQUE,
    first_name      VARCHAR(50)  NOT NULL,
    last_name       VARCHAR(50)  NOT NULL,
    email           VARCHAR(100) NOT NULL UNIQUE,
    password_hash   VARCHAR(255) NOT NULL,
    phone           VARCHAR(15),
    department_id   INT,
    role            ENUM('counsellor','academic_advisor','career_counsellor','financial_advisor','admin','tutor','pal','coordinator') NOT NULL,
    qualification   VARCHAR(100),
    specialization  VARCHAR(200),
    assigned_campus VARCHAR(100),
    status          ENUM('active','inactive','on_leave') NOT NULL DEFAULT 'active',
    last_login      DATETIME NULL,
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (department_id) REFERENCES departments(department_id)
);

CREATE TABLE IF NOT EXISTS service_categories (
    category_id   INT AUTO_INCREMENT PRIMARY KEY,
    category_name VARCHAR(100) NOT NULL UNIQUE,
    description   TEXT,
    icon          VARCHAR(50),
    display_order INT NOT NULL DEFAULT 0,
    status        ENUM('active','inactive') NOT NULL DEFAULT 'active',
    created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS services (
    service_id               INT AUTO_INCREMENT PRIMARY KEY,
    category_id              INT NOT NULL,
    service_name             VARCHAR(100) NOT NULL,
    service_code             VARCHAR(20)  NOT NULL UNIQUE,
    description              TEXT,
    duration_minutes         INT NOT NULL DEFAULT 30,
    buffer_time_minutes      INT NOT NULL DEFAULT 0,
    max_advance_booking_days INT NOT NULL DEFAULT 30,
    cancellation_hours       INT NOT NULL DEFAULT 24,
    requires_approval        TINYINT(1) NOT NULL DEFAULT 0,
    status                   ENUM('active','inactive') NOT NULL DEFAULT 'active',
    created_at               DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES service_categories(category_id)
);

CREATE TABLE IF NOT EXISTS staff_schedules (
    schedule_id           INT AUTO_INCREMENT PRIMARY KEY,
    staff_id              INT     NOT NULL,
    service_id            INT     NOT NULL,
    day_of_week           TINYINT NOT NULL CHECK (day_of_week BETWEEN 1 AND 7),
    start_time            TIME    NOT NULL,
    end_time              TIME    NOT NULL,
    slot_duration         INT     NOT NULL DEFAULT 30,
    max_bookings_per_slot INT     NOT NULL DEFAULT 1,
    location              VARCHAR(100),
    effective_from        DATE    NOT NULL,
    effective_to          DATE    NULL,
    status                ENUM('active','inactive') NOT NULL DEFAULT 'active',
    created_at            DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (staff_id)   REFERENCES staff(staff_id)   ON DELETE CASCADE,
    FOREIGN KEY (service_id) REFERENCES services(service_id) ON DELETE CASCADE,
    CHECK (end_time > start_time)
);

CREATE TABLE IF NOT EXISTS staff_unavailability (
    unavailability_id INT AUTO_INCREMENT PRIMARY KEY,
    staff_id          INT  NOT NULL,
    start_date        DATE NOT NULL,
    end_date          DATE NOT NULL,
    start_time        TIME NULL,
    end_time          TIME NULL,
    reason            ENUM('leave','sick','meeting','training','other') NOT NULL,
    notes             TEXT,
    created_at        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (staff_id) REFERENCES staff(staff_id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS bookings (
    booking_id          INT AUTO_INCREMENT PRIMARY KEY,
    booking_reference   VARCHAR(20)  NOT NULL UNIQUE,
    student_id          VARCHAR(20)  NOT NULL,
    service_id          INT          NOT NULL,
    staff_id            INT          NOT NULL,
    booking_date        DATE         NOT NULL,
    start_time          TIME         NOT NULL,
    end_time            TIME         NOT NULL,
    location            VARCHAR(100),
    status              ENUM('pending','confirmed','cancelled','completed','no_show','rescheduled') NOT NULL DEFAULT 'pending',
    notes               TEXT,
    cancellation_reason TEXT,
    cancelled_by        ENUM('student','staff','system') NULL,
    cancelled_at        DATETIME NULL,
    reminder_sent       TINYINT(1) NOT NULL DEFAULT 0,
    created_at          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(student_id),
    FOREIGN KEY (service_id) REFERENCES services(service_id),
    FOREIGN KEY (staff_id)   REFERENCES staff(staff_id)
);

CREATE TABLE IF NOT EXISTS booking_history (
    history_id INT AUTO_INCREMENT PRIMARY KEY,
    booking_id INT NOT NULL,
    action     ENUM('created','confirmed','cancelled','rescheduled','completed','no_show') NOT NULL,
    old_status VARCHAR(20),
    new_status VARCHAR(20),
    changed_by VARCHAR(50),
    notes      TEXT,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (booking_id) REFERENCES bookings(booking_id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS notifications (
    notification_id   INT AUTO_INCREMENT PRIMARY KEY,
    user_id           VARCHAR(50)  NOT NULL,
    user_type         ENUM('student','staff') NOT NULL,
    booking_id        INT NULL,
    notification_type ENUM('booking_confirmed','booking_reminder','booking_cancelled','booking_rescheduled','system') NOT NULL,
    title             VARCHAR(200) NOT NULL,
    message           TEXT NOT NULL,
    is_read           TINYINT(1) NOT NULL DEFAULT 0,
    read_at           DATETIME NULL,
    created_at        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (booking_id) REFERENCES bookings(booking_id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS feedback (
    feedback_id  INT AUTO_INCREMENT PRIMARY KEY,
    booking_id   INT NOT NULL UNIQUE,
    student_id   VARCHAR(20) NOT NULL,
    staff_id     INT NOT NULL,
    rating       TINYINT CHECK (rating BETWEEN 1 AND 5),
    comments     TEXT,
    is_anonymous TINYINT(1) NOT NULL DEFAULT 0,
    created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (booking_id)  REFERENCES bookings(booking_id) ON DELETE CASCADE,
    FOREIGN KEY (student_id)  REFERENCES students(student_id),
    FOREIGN KEY (staff_id)    REFERENCES staff(staff_id)
);

CREATE TABLE IF NOT EXISTS system_settings (
    setting_id    INT AUTO_INCREMENT PRIMARY KEY,
    setting_key   VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT,
    description   TEXT,
    updated_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- ============================================================
-- SEED DATA
-- ============================================================

INSERT IGNORE INTO faculties (faculty_name, faculty_code, dean_name, contact_email) VALUES
('Faculty of Science and Engineering',                     'FSE',   'Prof. Nomvula Dlamini', 'fse@wsu.ac.za'),
('Faculty of Business, Economics and Management Sciences', 'FBEMS', 'Prof. Thabo Mokoena',   'fbems@wsu.ac.za'),
('Faculty of Education',                                   'FED',   'Prof. Zanele Khumalo',  'fed@wsu.ac.za'),
('Faculty of Health Sciences',                             'FHS',   'Prof. Sipho Mthembu',   'fhs@wsu.ac.za'),
('Faculty of Law',                                         'FLAW',  'Prof. Lindiwe Nkosi',   'flaw@wsu.ac.za');

INSERT IGNORE INTO departments (faculty_id, department_name, department_code, head_of_department, contact_email) VALUES
(1, 'Computer Science',          'CS',   'Dr. John Smith',    'cs@wsu.ac.za'),
(1, 'Mathematics',               'MATH', 'Dr. Sarah Johnson', 'math@wsu.ac.za'),
(2, 'Business Management',       'BM',   'Dr. Peter Ndlovu',  'bm@wsu.ac.za'),
(3, 'Student Wellness Centre',   'SWC',  'Dr. Linda Khumalo', 'wellness@wsu.ac.za'),
(4, 'Career Development Centre', 'CDC',  'Ms. Thandi Mbeki',  'careers@wsu.ac.za');

INSERT IGNORE INTO service_categories (category_name, description, icon, display_order) VALUES
('Academic Advising',      'Guidance on course selection and program planning',    'fa-graduation-cap', 1),
('Career Guidance',        'Assistance in finding suitable career paths',          'fa-briefcase',      2),
('Healthcare Services',    'Access to medical services and health support',        'fa-heartbeat',      3),
('Student Life Activities','Opportunities for students to engage in activities',   'fa-users',          4),
('Support Services',       'Counseling and support for personal challenges',       'fa-hands-helping',  5);

INSERT IGNORE INTO services (category_id, service_name, service_code, description, duration_minutes, buffer_time_minutes, max_advance_booking_days, cancellation_hours) VALUES
(1,'Course Selection Consultation','ACAD-COURSE', 'Guidance on course selection and module planning',         30,10,30,24),
(1,'Program Planning Session',     'ACAD-PROGRAM','Academic program planning and degree requirements review', 45,15,30,24),
(1,'Study Skills Workshop',        'ACAD-STUDY',  'Learn effective study techniques and time management',     60, 0,30,48),
(1,'Academic Performance Review',  'ACAD-REVIEW', 'Review academic progress and improvement strategies',      45,15,21,24),
(2,'Career Path Consultation',     'CAREER-PATH', 'Explore suitable career paths and opportunities',          45,15,30,24),
(2,'Professional Skills Development','CAREER-SKILLS','Develop professional and workplace skills',             45,15,21,24),
(2,'CV and Cover Letter Review',   'CAREER-CV',   'Professional CV and cover letter feedback',                30,10,21,24),
(2,'Interview Preparation',        'CAREER-INTV', 'Mock interviews and preparation coaching',                 45,15,14,24),
(3,'General Medical Consultation', 'HEALTH-MED',  'General health checkup and medical consultation',          30,10,14,24),
(3,'Mental Health Counselling',    'HEALTH-MENTAL','Mental health support and counselling',                   45,15,14,24),
(3,'Wellness Checkup',             'HEALTH-WELL', 'Overall wellness and health assessment',                   30,10,21,24),
(4,'Club Registration',            'LIFE-CLUB',   'Register for student clubs and societies',                 15, 5,30,24),
(4,'Event Planning Consultation',  'LIFE-EVENT',  'Plan and organize student events',                         30,10,21,24),
(4,'Sports Activities Booking',    'LIFE-SPORT',  'Book sports facilities and activities',                    30,10,14,24),
(5,'Personal Counselling',         'SUPP-COUNS',  'One-on-one personal counselling session',                  45,15,14,24),
(5,'Crisis Intervention',          'SUPP-CRISIS', 'Immediate crisis support and intervention',                30, 0, 7, 2),
(5,'Peer Support Session',         'SUPP-PEER',   'Peer-to-peer support and guidance',                        30,10,14,24),
(5,'Financial Aid Consultation',   'SUPP-FIN',    'Financial assistance and bursary guidance',                45,15,30,24),
(5,'NSFAS Support',                'SUPP-NSFAS',  'NSFAS funding guidance and application support',           45,15,30,24);

-- Password for all accounts: admin123
SET @hash = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';

INSERT IGNORE INTO admins (username, full_name, email, password_hash, role) VALUES
('admin', 'System Administrator', 'admin@wsu.ac.za', @hash, 'super_admin');

INSERT IGNORE INTO staff (staff_number, first_name, last_name, email, password_hash, phone, department_id, role, qualification, specialization, assigned_campus) VALUES
('STF001','Sarah',   'Mthembu', 'sarah.mthembu@wsu.ac.za',   @hash,'0827654321',4,'counsellor',       'PhD Clinical Psychology',   'Trauma and Anxiety',    'Butterworth'),
('STF002','John',    'Ndlovu',  'john.ndlovu@wsu.ac.za',     @hash,'0823456789',1,'academic_advisor',  'PhD Education',             'Academic Development',  'Butterworth'),
('STF003','Linda',   'Khumalo', 'linda.khumalo@wsu.ac.za',   @hash,'0829876543',5,'career_counsellor', 'MA Career Counselling',     'Career Development',    'Mthatha'),
('STF004','Thabo',   'Dlamini', 'thabo.dlamini@wsu.ac.za',   @hash,'0821234567',4,'counsellor',        'MA Counselling Psychology', 'Student Wellness',      'Mthatha'),
('STF005','Nomsa',   'Zulu',    'nomsa.zulu@wsu.ac.za',      @hash,'0834567890',3,'financial_advisor', 'BCom Honours',              'Financial Aid',         'Butterworth'),
('STF006','Lungelo', 'Nkosi',   'lungelo.nkosi@wsu.ac.za',   @hash,'0811234567',2,'admin',             'BCom Administration',       'Office Management',     'Mthatha'),
('STF007','Ayanda',  'Cele',    'ayanda.cele@wsu.ac.za',     @hash,'0812345678',1,'tutor',             'BSc Computer Science',      'Mathematics and CS',    'Butterworth'),
('STF008','Zanele',  'Mokoena', 'zanele.mokoena@wsu.ac.za',  @hash,'0813456789',2,'tutor',             'BCom Accounting',           'Accounting and Finance','Mthatha'),
('STF009','Siyanda', 'Dube',    'siyanda.dube@wsu.ac.za',    @hash,'0814567890',1,'pal',               'BSc 3rd Year',              'Physics and Maths',     'Butterworth'),
('STF010','Nokwanda','Sithole', 'nokwanda.sithole@wsu.ac.za',@hash,'0815678901',3,'pal',               'BCom 3rd Year',             'Business Studies',      'Mthatha'),
('STF011','Mandla',  'Ntuli',   'mandla.ntuli@wsu.ac.za',    @hash,'0816789012',1,'coordinator',       'MEd Higher Education',      'Academic Coordination', 'Butterworth'),
('STF012','Bongiwe', 'Mthembu', 'bongiwe.mthembu@wsu.ac.za', @hash,'0817890123',2,'coordinator',       'MBA',                       'Student Coordination',  'Mthatha');

INSERT IGNORE INTO students (student_id, first_name, last_name, email, password_hash, phone, faculty_id, year_of_study, student_type) VALUES
('202401234','Sipho',     'Mbeki',   '202401234@mywsu.ac.za',@hash,'0821234567',1,2,'undergraduate'),
('202401235','Thandi',    'Nkosi',   '202401235@mywsu.ac.za',@hash,'0829876543',2,3,'undergraduate'),
('202401236','Bongani',   'Sithole', '202401236@mywsu.ac.za',@hash,'0834567890',3,1,'undergraduate'),
('202401237','Nompilo',   'Dlamini', '202401237@mywsu.ac.za',@hash,'0835678901',4,2,'undergraduate'),
('202401238','Lethiwe',   'Zulu',    '202401238@mywsu.ac.za',@hash,'0836789012',5,1,'undergraduate'),
('202401239','Mthokozisi','Ndlovu',  '202401239@mywsu.ac.za',@hash,'0837890123',1,4,'postgraduate'),
('202401240','Ayabonga',  'Cele',    '202401240@mywsu.ac.za',@hash,'0838901234',2,3,'honours');

INSERT IGNORE INTO staff_schedules (staff_id, service_id, day_of_week, start_time, end_time, slot_duration, location, effective_from) VALUES
(1,1,1,'09:00','16:00',45,'Wellness Centre Room 101','2024-01-01'),
(1,1,2,'09:00','16:00',45,'Wellness Centre Room 101','2024-01-01'),
(1,1,3,'09:00','16:00',45,'Wellness Centre Room 101','2024-01-01'),
(2,4,1,'08:00','17:00',30,'Academic Office B204','2024-01-01'),
(2,4,2,'08:00','17:00',30,'Academic Office B204','2024-01-01'),
(3,5,1,'10:00','16:00',45,'Career Centre Room 5','2024-01-01'),
(3,5,2,'09:00','15:00',30,'Career Centre Room 5','2024-01-01'),
(4,1,2,'10:00','17:00',45,'Wellness Centre Room 102','2024-01-01'),
(4,1,4,'09:00','16:00',45,'Wellness Centre Room 102','2024-01-01'),
(5,18,1,'08:00','16:00',30,'Financial Aid Office','2024-01-01'),
(5,19,2,'08:00','16:00',45,'Financial Aid Office','2024-01-01');

INSERT IGNORE INTO system_settings (setting_key, setting_value, description) VALUES
('booking_advance_days','30',               'Maximum days in advance students can book'),
('cancellation_hours',  '24',               'Minimum hours before appointment to cancel'),
('reminder_hours',      '24',               'Hours before appointment to send reminder'),
('max_active_bookings', '5',                'Maximum active bookings per student'),
('system_email',        'bookings@wsu.ac.za','System email for notifications');
