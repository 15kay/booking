-- WSU Booking System - SQL Server Schema
-- Converted from MySQL to T-SQL
-- Target: clestudtrack02.wsu.ac.za / wsu_booking

USE wsu_booking;
GO

-- ============================================
-- CORE TABLES
-- ============================================

IF NOT EXISTS (SELECT * FROM sysobjects WHERE name='faculties' AND xtype='U')
CREATE TABLE dbo.faculties (
    faculty_id   INT IDENTITY(1,1) PRIMARY KEY,
    faculty_name VARCHAR(100) NOT NULL UNIQUE,
    faculty_code VARCHAR(10)  NOT NULL UNIQUE,
    dean_name    VARCHAR(100),
    contact_email VARCHAR(100),
    status       VARCHAR(10)  NOT NULL DEFAULT 'active' CHECK (status IN ('active','inactive')),
    created_at   DATETIME     NOT NULL DEFAULT GETDATE()
);
GO

IF NOT EXISTS (SELECT * FROM sysobjects WHERE name='departments' AND xtype='U')
CREATE TABLE dbo.departments (
    department_id   INT IDENTITY(1,1) PRIMARY KEY,
    faculty_id      INT          NOT NULL,
    department_name VARCHAR(100) NOT NULL,
    department_code VARCHAR(10)  NOT NULL,
    head_of_department VARCHAR(100),
    contact_email   VARCHAR(100),
    status          VARCHAR(10)  NOT NULL DEFAULT 'active' CHECK (status IN ('active','inactive')),
    CONSTRAINT fk_dept_faculty FOREIGN KEY (faculty_id) REFERENCES dbo.faculties(faculty_id),
    CONSTRAINT uq_dept UNIQUE (faculty_id, department_code)
);
GO

IF NOT EXISTS (SELECT * FROM sysobjects WHERE name='students' AND xtype='U')
CREATE TABLE dbo.students (
    student_id   VARCHAR(20)  PRIMARY KEY,
    first_name   VARCHAR(50)  NOT NULL,
    last_name    VARCHAR(50)  NOT NULL,
    email        VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    phone        VARCHAR(15),
    faculty_id   INT,
    year_of_study TINYINT      CHECK (year_of_study BETWEEN 1 AND 6),
    student_type VARCHAR(20)  NOT NULL DEFAULT 'undergraduate' CHECK (student_type IN ('undergraduate','postgraduate','honours','masters','phd')),
    status       VARCHAR(20)  NOT NULL DEFAULT 'active' CHECK (status IN ('active','inactive','suspended','graduated')),
    last_login   DATETIME     NULL,
    created_at   DATETIME     NOT NULL DEFAULT GETDATE(),
    updated_at   DATETIME     NOT NULL DEFAULT GETDATE(),
    CONSTRAINT fk_student_faculty FOREIGN KEY (faculty_id) REFERENCES dbo.faculties(faculty_id)
);
GO

IF NOT EXISTS (SELECT * FROM sysobjects WHERE name='staff' AND xtype='U')
CREATE TABLE dbo.staff (
    staff_id      INT IDENTITY(1,1) PRIMARY KEY,
    staff_number  VARCHAR(20)  NOT NULL UNIQUE,
    first_name    VARCHAR(50)  NOT NULL,
    last_name     VARCHAR(50)  NOT NULL,
    email         VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    phone         VARCHAR(15),
    department_id INT,
    role          VARCHAR(30)  NOT NULL CHECK (role IN ('counsellor','academic_advisor','career_counsellor','financial_advisor','admin','tutor','pal','coordinator')),
    qualification VARCHAR(100),
    specialization VARCHAR(200),
    assigned_campus VARCHAR(100),
    status        VARCHAR(20)  NOT NULL DEFAULT 'active' CHECK (status IN ('active','inactive','on_leave')),
    last_login    DATETIME     NULL,
    created_at    DATETIME     NOT NULL DEFAULT GETDATE(),
    updated_at    DATETIME     NOT NULL DEFAULT GETDATE(),
    CONSTRAINT fk_staff_dept FOREIGN KEY (department_id) REFERENCES dbo.departments(department_id)
);
GO

-- ============================================
-- SERVICE MANAGEMENT
-- ============================================

IF NOT EXISTS (SELECT * FROM sysobjects WHERE name='service_categories' AND xtype='U')
CREATE TABLE dbo.service_categories (
    category_id   INT IDENTITY(1,1) PRIMARY KEY,
    category_name VARCHAR(100) NOT NULL UNIQUE,
    description   NVARCHAR(MAX),
    icon          VARCHAR(50),
    display_order INT          NOT NULL DEFAULT 0,
    status        VARCHAR(10)  NOT NULL DEFAULT 'active' CHECK (status IN ('active','inactive')),
    created_at    DATETIME     NOT NULL DEFAULT GETDATE()
);
GO

IF NOT EXISTS (SELECT * FROM sysobjects WHERE name='services' AND xtype='U')
CREATE TABLE dbo.services (
    service_id              INT IDENTITY(1,1) PRIMARY KEY,
    category_id             INT          NOT NULL,
    service_name            VARCHAR(100) NOT NULL,
    service_code            VARCHAR(20)  NOT NULL UNIQUE,
    description             NVARCHAR(MAX),
    duration_minutes        INT          NOT NULL DEFAULT 30 CHECK (duration_minutes > 0),
    buffer_time_minutes     INT          NOT NULL DEFAULT 0,
    max_advance_booking_days INT         NOT NULL DEFAULT 30,
    cancellation_hours      INT          NOT NULL DEFAULT 24,
    requires_approval       BIT          NOT NULL DEFAULT 0,
    status                  VARCHAR(10)  NOT NULL DEFAULT 'active' CHECK (status IN ('active','inactive')),
    created_at              DATETIME     NOT NULL DEFAULT GETDATE(),
    CONSTRAINT fk_service_category FOREIGN KEY (category_id) REFERENCES dbo.service_categories(category_id)
);
GO

-- ============================================
-- SCHEDULING
-- ============================================

IF NOT EXISTS (SELECT * FROM sysobjects WHERE name='staff_schedules' AND xtype='U')
CREATE TABLE dbo.staff_schedules (
    schedule_id          INT IDENTITY(1,1) PRIMARY KEY,
    staff_id             INT      NOT NULL,
    service_id           INT      NOT NULL,
    day_of_week          TINYINT  NOT NULL CHECK (day_of_week BETWEEN 1 AND 7),
    start_time           TIME     NOT NULL,
    end_time             TIME     NOT NULL,
    slot_duration        INT      NOT NULL DEFAULT 30,
    max_bookings_per_slot INT     NOT NULL DEFAULT 1,
    location             VARCHAR(100),
    effective_from       DATE     NOT NULL,
    effective_to         DATE     NULL,
    status               VARCHAR(10) NOT NULL DEFAULT 'active' CHECK (status IN ('active','inactive')),
    created_at           DATETIME NOT NULL DEFAULT GETDATE(),
    CONSTRAINT fk_sched_staff   FOREIGN KEY (staff_id)   REFERENCES dbo.staff(staff_id)   ON DELETE CASCADE,
    CONSTRAINT fk_sched_service FOREIGN KEY (service_id) REFERENCES dbo.services(service_id) ON DELETE CASCADE,
    CONSTRAINT chk_sched_time   CHECK (end_time > start_time)
);
GO

IF NOT EXISTS (SELECT * FROM sysobjects WHERE name='staff_unavailability' AND xtype='U')
CREATE TABLE dbo.staff_unavailability (
    unavailability_id INT IDENTITY(1,1) PRIMARY KEY,
    staff_id          INT      NOT NULL,
    start_date        DATE     NOT NULL,
    end_date          DATE     NOT NULL,
    start_time        TIME     NULL,
    end_time          TIME     NULL,
    reason            VARCHAR(20) NOT NULL CHECK (reason IN ('leave','sick','meeting','training','other')),
    notes             NVARCHAR(MAX),
    created_at        DATETIME NOT NULL DEFAULT GETDATE(),
    CONSTRAINT fk_unavail_staff FOREIGN KEY (staff_id) REFERENCES dbo.staff(staff_id) ON DELETE CASCADE,
    CONSTRAINT chk_unavail_dates CHECK (end_date >= start_date)
);
GO

-- ============================================
-- BOOKINGS
-- ============================================

IF NOT EXISTS (SELECT * FROM sysobjects WHERE name='bookings' AND xtype='U')
CREATE TABLE dbo.bookings (
    booking_id        INT IDENTITY(1,1) PRIMARY KEY,
    booking_reference VARCHAR(20)  NOT NULL UNIQUE,
    student_id        VARCHAR(20)  NOT NULL,
    service_id        INT          NOT NULL,
    staff_id          INT          NOT NULL,
    booking_date      DATE         NOT NULL,
    start_time        TIME         NOT NULL,
    end_time          TIME         NOT NULL,
    location          VARCHAR(100),
    status            VARCHAR(20)  NOT NULL DEFAULT 'pending' CHECK (status IN ('pending','confirmed','cancelled','completed','no_show','rescheduled')),
    notes             NVARCHAR(MAX),
    cancellation_reason NVARCHAR(MAX),
    cancelled_by      VARCHAR(10)  NULL CHECK (cancelled_by IN ('student','staff','system')),
    cancelled_at      DATETIME     NULL,
    reminder_sent     BIT          NOT NULL DEFAULT 0,
    created_at        DATETIME     NOT NULL DEFAULT GETDATE(),
    updated_at        DATETIME     NOT NULL DEFAULT GETDATE(),
    CONSTRAINT fk_booking_student FOREIGN KEY (student_id) REFERENCES dbo.students(student_id),
    CONSTRAINT fk_booking_service FOREIGN KEY (service_id) REFERENCES dbo.services(service_id),
    CONSTRAINT fk_booking_staff   FOREIGN KEY (staff_id)   REFERENCES dbo.staff(staff_id),
    CONSTRAINT chk_booking_time   CHECK (end_time > start_time)
);
GO

IF NOT EXISTS (SELECT * FROM sysobjects WHERE name='booking_history' AND xtype='U')
CREATE TABLE dbo.booking_history (
    history_id  INT IDENTITY(1,1) PRIMARY KEY,
    booking_id  INT          NOT NULL,
    action      VARCHAR(20)  NOT NULL CHECK (action IN ('created','confirmed','cancelled','rescheduled','completed','no_show')),
    old_status  VARCHAR(20),
    new_status  VARCHAR(20),
    changed_by  VARCHAR(50),
    notes       NVARCHAR(MAX),
    created_at  DATETIME     NOT NULL DEFAULT GETDATE(),
    CONSTRAINT fk_history_booking FOREIGN KEY (booking_id) REFERENCES dbo.bookings(booking_id) ON DELETE CASCADE
);
GO

-- ============================================
-- NOTIFICATIONS & FEEDBACK
-- ============================================

IF NOT EXISTS (SELECT * FROM sysobjects WHERE name='notifications' AND xtype='U')
CREATE TABLE dbo.notifications (
    notification_id   INT IDENTITY(1,1) PRIMARY KEY,
    user_id           VARCHAR(50)  NOT NULL,
    user_type         VARCHAR(10)  NOT NULL CHECK (user_type IN ('student','staff')),
    booking_id        INT          NULL,
    notification_type VARCHAR(30)  NOT NULL CHECK (notification_type IN ('booking_confirmed','booking_reminder','booking_cancelled','booking_rescheduled','system')),
    title             VARCHAR(200) NOT NULL,
    message           NVARCHAR(MAX) NOT NULL,
    is_read           BIT          NOT NULL DEFAULT 0,
    read_at           DATETIME     NULL,
    created_at        DATETIME     NOT NULL DEFAULT GETDATE(),
    CONSTRAINT fk_notif_booking FOREIGN KEY (booking_id) REFERENCES dbo.bookings(booking_id) ON DELETE CASCADE
);
GO

IF NOT EXISTS (SELECT * FROM sysobjects WHERE name='feedback' AND xtype='U')
CREATE TABLE dbo.feedback (
    feedback_id  INT IDENTITY(1,1) PRIMARY KEY,
    booking_id   INT      NOT NULL UNIQUE,
    student_id   VARCHAR(20) NOT NULL,
    staff_id     INT      NOT NULL,
    rating       TINYINT  CHECK (rating BETWEEN 1 AND 5),
    comments     NVARCHAR(MAX),
    is_anonymous BIT      NOT NULL DEFAULT 0,
    created_at   DATETIME NOT NULL DEFAULT GETDATE(),
    CONSTRAINT fk_feedback_booking FOREIGN KEY (booking_id) REFERENCES dbo.bookings(booking_id) ON DELETE CASCADE,
    CONSTRAINT fk_feedback_student FOREIGN KEY (student_id) REFERENCES dbo.students(student_id),
    CONSTRAINT fk_feedback_staff   FOREIGN KEY (staff_id)   REFERENCES dbo.staff(staff_id)
);
GO

-- ============================================
-- SYSTEM CONFIGURATION
-- ============================================

IF NOT EXISTS (SELECT * FROM sysobjects WHERE name='system_settings' AND xtype='U')
CREATE TABLE dbo.system_settings (
    setting_id    INT IDENTITY(1,1) PRIMARY KEY,
    setting_key   VARCHAR(100) NOT NULL UNIQUE,
    setting_value NVARCHAR(MAX),
    description   NVARCHAR(MAX),
    updated_at    DATETIME     NOT NULL DEFAULT GETDATE()
);
GO

-- ============================================
-- SEED DATA
-- ============================================

IF NOT EXISTS (SELECT 1 FROM dbo.faculties)
BEGIN
    INSERT INTO dbo.faculties (faculty_name, faculty_code, dean_name, contact_email) VALUES
    ('Faculty of Science and Engineering',                    'FSE',   'Prof. Nomvula Dlamini', 'fse@wsu.ac.za'),
    ('Faculty of Business, Economics and Management Sciences','FBEMS', 'Prof. Thabo Mokoena',   'fbems@wsu.ac.za'),
    ('Faculty of Education',                                  'FED',   'Prof. Zanele Khumalo',  'fed@wsu.ac.za'),
    ('Faculty of Health Sciences',                            'FHS',   'Prof. Sipho Mthembu',   'fhs@wsu.ac.za'),
    ('Faculty of Law',                                        'FLAW',  'Prof. Lindiwe Nkosi',   'flaw@wsu.ac.za');
END
GO

IF NOT EXISTS (SELECT 1 FROM dbo.departments)
BEGIN
    INSERT INTO dbo.departments (faculty_id, department_name, department_code, head_of_department, contact_email) VALUES
    (1, 'Computer Science',          'CS',   'Dr. John Smith',    'cs@wsu.ac.za'),
    (1, 'Mathematics',               'MATH', 'Dr. Sarah Johnson', 'math@wsu.ac.za'),
    (2, 'Business Management',       'BM',   'Dr. Peter Ndlovu',  'bm@wsu.ac.za'),
    (3, 'Student Wellness Centre',   'SWC',  'Dr. Linda Khumalo', 'wellness@wsu.ac.za'),
    (4, 'Career Development Centre', 'CDC',  'Ms. Thandi Mbeki',  'careers@wsu.ac.za');
END
GO

IF NOT EXISTS (SELECT 1 FROM dbo.service_categories)
BEGIN
    INSERT INTO dbo.service_categories (category_name, description, icon, display_order) VALUES
    ('Academic Advising',    'Guidance on course selection and program planning', 'fa-graduation-cap', 1),
    ('Career Guidance',      'Assistance in finding suitable career paths',       'fa-briefcase',      2),
    ('Healthcare Services',  'Access to medical services and health support',     'fa-heartbeat',      3),
    ('Student Life Activities','Opportunities for students to engage in activities','fa-users',         4),
    ('Support Services',     'Counseling and support for personal challenges',    'fa-hands-helping',  5);
END
GO

IF NOT EXISTS (SELECT 1 FROM dbo.services)
BEGIN
    INSERT INTO dbo.services (category_id, service_name, service_code, description, duration_minutes, buffer_time_minutes, max_advance_booking_days, cancellation_hours) VALUES
    (1,'Course Selection Consultation','ACAD-COURSE', 'Guidance on course selection and module planning',          30,10,30,24),
    (1,'Program Planning Session',     'ACAD-PROGRAM','Academic program planning and degree requirements review',  45,15,30,24),
    (1,'Study Skills Workshop',        'ACAD-STUDY',  'Learn effective study techniques and time management',      60, 0,30,48),
    (1,'Academic Performance Review',  'ACAD-REVIEW', 'Review academic progress and improvement strategies',       45,15,21,24),
    (2,'Career Path Consultation',     'CAREER-PATH', 'Explore suitable career paths and opportunities',           45,15,30,24),
    (2,'Professional Skills Development','CAREER-SKILLS','Develop professional and workplace skills',              45,15,21,24),
    (2,'CV and Cover Letter Review',   'CAREER-CV',   'Professional CV and cover letter feedback',                 30,10,21,24),
    (2,'Interview Preparation',        'CAREER-INTV', 'Mock interviews and preparation coaching',                  45,15,14,24),
    (3,'General Medical Consultation', 'HEALTH-MED',  'General health checkup and medical consultation',           30,10,14,24),
    (3,'Mental Health Counselling',    'HEALTH-MENTAL','Mental health support and counselling',                    45,15,14,24),
    (3,'Wellness Checkup',             'HEALTH-WELL', 'Overall wellness and health assessment',                    30,10,21,24),
    (4,'Club Registration',            'LIFE-CLUB',   'Register for student clubs and societies',                  15, 5,30,24),
    (4,'Event Planning Consultation',  'LIFE-EVENT',  'Plan and organize student events',                          30,10,21,24),
    (4,'Sports Activities Booking',    'LIFE-SPORT',  'Book sports facilities and activities',                     30,10,14,24),
    (5,'Personal Counselling',         'SUPP-COUNS',  'One-on-one personal counselling session',                   45,15,14,24),
    (5,'Crisis Intervention',          'SUPP-CRISIS', 'Immediate crisis support and intervention',                 30, 0, 7, 2),
    (5,'Peer Support Session',         'SUPP-PEER',   'Peer-to-peer support and guidance',                         30,10,14,24),
    (5,'Financial Aid Consultation',   'SUPP-FIN',    'Financial assistance and bursary guidance',                 45,15,30,24),
    (5,'NSFAS Support',                'SUPP-NSFAS',  'NSFAS funding guidance and application support',            45,15,30,24);
END
GO

IF NOT EXISTS (SELECT 1 FROM dbo.staff)
BEGIN
    INSERT INTO dbo.staff (staff_number, first_name, last_name, email, password_hash, phone, department_id, role, qualification, specialization, assigned_campus) VALUES
    -- Counsellors
    ('STF001','Sarah',  'Mthembu','sarah.mthembu@wsu.ac.za',  '$2y$10$T./d56vL6qf6PHYYdZA7/.8IRTzCC0tkBc0qeh0XXKzSKJzaFP0qC','0827654321',4,'counsellor',        'PhD Clinical Psychology',   'Trauma and Anxiety',    'Butterworth'),
    ('STF004','Thabo',  'Dlamini','thabo.dlamini@wsu.ac.za',  '$2y$10$T./d56vL6qf6PHYYdZA7/.8IRTzCC0tkBc0qeh0XXKzSKJzaFP0qC','0821234567',4,'counsellor',        'MA Counselling Psychology', 'Student Wellness',      'Mthatha'),
    -- Academic Advisors
    ('STF002','John',   'Ndlovu', 'john.ndlovu@wsu.ac.za',    '$2y$10$T./d56vL6qf6PHYYdZA7/.8IRTzCC0tkBc0qeh0XXKzSKJzaFP0qC','0823456789',1,'academic_advisor',  'PhD Education',             'Academic Development',  'Butterworth'),
    -- Career Counsellors
    ('STF003','Linda',  'Khumalo','linda.khumalo@wsu.ac.za',  '$2y$10$T./d56vL6qf6PHYYdZA7/.8IRTzCC0tkBc0qeh0XXKzSKJzaFP0qC','0829876543',5,'career_counsellor', 'MA Career Counselling',     'Career Development',    'Mthatha'),
    -- Financial Advisors
    ('STF005','Nomsa',  'Zulu',   'nomsa.zulu@wsu.ac.za',     '$2y$10$T./d56vL6qf6PHYYdZA7/.8IRTzCC0tkBc0qeh0XXKzSKJzaFP0qC','0834567890',3,'financial_advisor', 'BCom Honours',              'Financial Aid',         'Butterworth'),
    -- Admin staff
    ('STF006','Lungelo','Nkosi',  'lungelo.nkosi@wsu.ac.za',  '$2y$10$T./d56vL6qf6PHYYdZA7/.8IRTzCC0tkBc0qeh0XXKzSKJzaFP0qC','0811234567',2,'admin',             'BCom Administration',       'Office Management',     'Mthatha'),
    -- Tutors
    ('STF007','Ayanda', 'Cele',   'ayanda.cele@wsu.ac.za',    '$2y$10$T./d56vL6qf6PHYYdZA7/.8IRTzCC0tkBc0qeh0XXKzSKJzaFP0qC','0812345678',1,'tutor',             'BSc Computer Science',      'Mathematics and CS',    'Butterworth'),
    ('STF008','Zanele', 'Mokoena','zanele.mokoena@wsu.ac.za', '$2y$10$T./d56vL6qf6PHYYdZA7/.8IRTzCC0tkBc0qeh0XXKzSKJzaFP0qC','0813456789',2,'tutor',             'BCom Accounting',           'Accounting and Finance','Mthatha'),
    -- PAL (Peer Assisted Learning)
    ('STF009','Siyanda','Dube',   'siyanda.dube@wsu.ac.za',   '$2y$10$T./d56vL6qf6PHYYdZA7/.8IRTzCC0tkBc0qeh0XXKzSKJzaFP0qC','0814567890',1,'pal',               'BSc 3rd Year',              'Physics and Maths',     'Butterworth'),
    ('STF010','Nokwanda','Sithole','nokwanda.sithole@wsu.ac.za','$2y$10$T./d56vL6qf6PHYYdZA7/.8IRTzCC0tkBc0qeh0XXKzSKJzaFP0qC','0815678901',3,'pal',              'BCom 3rd Year',             'Business Studies',      'Mthatha'),
    -- Coordinators
    ('STF011','Mandla', 'Ntuli',  'mandla.ntuli@wsu.ac.za',   '$2y$10$T./d56vL6qf6PHYYdZA7/.8IRTzCC0tkBc0qeh0XXKzSKJzaFP0qC','0816789012',1,'coordinator',      'MEd Higher Education',      'Academic Coordination', 'Butterworth'),
    ('STF012','Bongiwe','Mthembu','bongiwe.mthembu@wsu.ac.za','$2y$10$T./d56vL6qf6PHYYdZA7/.8IRTzCC0tkBc0qeh0XXKzSKJzaFP0qC','0817890123',2,'coordinator',      'MBA',                       'Student Coordination',  'Mthatha');
END
GO

IF NOT EXISTS (SELECT 1 FROM dbo.students)
BEGIN
    INSERT INTO dbo.students (student_id, first_name, last_name, email, password_hash, phone, faculty_id, year_of_study, student_type) VALUES
    ('202401234','Sipho',    'Mbeki',   '202401234@mywsu.ac.za','$2y$10$T./d56vL6qf6PHYYdZA7/.8IRTzCC0tkBc0qeh0XXKzSKJzaFP0qC','0821234567',1,2,'undergraduate'),
    ('202401235','Thandi',   'Nkosi',   '202401235@mywsu.ac.za','$2y$10$T./d56vL6qf6PHYYdZA7/.8IRTzCC0tkBc0qeh0XXKzSKJzaFP0qC','0829876543',2,3,'undergraduate'),
    ('202401236','Bongani',  'Sithole', '202401236@mywsu.ac.za','$2y$10$T./d56vL6qf6PHYYdZA7/.8IRTzCC0tkBc0qeh0XXKzSKJzaFP0qC','0834567890',3,1,'undergraduate'),
    ('202401237','Nompilo',  'Dlamini', '202401237@mywsu.ac.za','$2y$10$T./d56vL6qf6PHYYdZA7/.8IRTzCC0tkBc0qeh0XXKzSKJzaFP0qC','0835678901',4,2,'undergraduate'),
    ('202401238','Lethiwe',  'Zulu',    '202401238@mywsu.ac.za','$2y$10$T./d56vL6qf6PHYYdZA7/.8IRTzCC0tkBc0qeh0XXKzSKJzaFP0qC','0836789012',5,1,'undergraduate'),
    ('202401239','Mthokozisi','Ndlovu', '202401239@mywsu.ac.za','$2y$10$T./d56vL6qf6PHYYdZA7/.8IRTzCC0tkBc0qeh0XXKzSKJzaFP0qC','0837890123',1,4,'postgraduate'),
    ('202401240','Ayabonga', 'Cele',    '202401240@mywsu.ac.za','$2y$10$T./d56vL6qf6PHYYdZA7/.8IRTzCC0tkBc0qeh0XXKzSKJzaFP0qC','0838901234',2,3,'honours');
END
GO

IF NOT EXISTS (SELECT 1 FROM dbo.staff_schedules)
BEGIN
    INSERT INTO dbo.staff_schedules (staff_id, service_id, day_of_week, start_time, end_time, slot_duration, location, effective_from) VALUES
    (1,1,1,'09:00','16:00',45,'Wellness Centre Room 101','2024-01-01'),
    (1,1,2,'09:00','16:00',45,'Wellness Centre Room 101','2024-01-01'),
    (1,1,3,'09:00','16:00',45,'Wellness Centre Room 101','2024-01-01'),
    (1,2,4,'10:00','15:00',60,'Wellness Centre Group Room','2024-01-01'),
    (1,3,5,'09:00','13:00',30,'Wellness Centre Room 101','2024-01-01'),
    (2,4,1,'08:00','17:00',30,'Academic Office B204','2024-01-01'),
    (2,4,2,'08:00','17:00',30,'Academic Office B204','2024-01-01'),
    (2,5,3,'09:00','14:00',60,'Lecture Hall 3','2024-01-01'),
    (2,6,4,'09:00','16:00',45,'Academic Office B204','2024-01-01'),
    (3,7,1,'10:00','16:00',45,'Career Centre Room 5','2024-01-01'),
    (3,8,2,'09:00','15:00',30,'Career Centre Room 5','2024-01-01'),
    (3,9,3,'10:00','16:00',45,'Career Centre Room 5','2024-01-01'),
    (3,7,5,'09:00','13:00',45,'Career Centre Room 5','2024-01-01'),
    (4,1,2,'10:00','17:00',45,'Wellness Centre Room 102','2024-01-01'),
    (4,1,4,'09:00','16:00',45,'Wellness Centre Room 102','2024-01-01'),
    (5,10,1,'08:00','16:00',30,'Financial Aid Office','2024-01-01'),
    (5,11,2,'08:00','16:00',45,'Financial Aid Office','2024-01-01'),
    (5,12,3,'09:00','15:00',30,'Financial Aid Office','2024-01-01'),
    (5,10,5,'08:00','14:00',30,'Financial Aid Office','2024-01-01');
END
GO

IF NOT EXISTS (SELECT 1 FROM dbo.bookings)
BEGIN
    INSERT INTO dbo.bookings (booking_reference, student_id, service_id, staff_id, booking_date, start_time, end_time, location, status, notes) VALUES
    ('BK2024120001','202401234',1,1,'2024-12-20','10:00','10:45','Wellness Centre Room 101','confirmed','First counselling session'),
    ('BK2024120002','202401234',4,2,'2024-12-22','14:00','14:30','Academic Office B204',    'pending',  'Course selection for next semester'),
    ('BK2024120003','202401235',7,3,'2024-12-21','11:00','11:45','Career Centre Room 5',    'confirmed','Career guidance session'),
    ('BK2024120004','202401236',11,5,'2024-12-23','09:00','09:45','Financial Aid Office',   'confirmed','NSFAS application help');
END
GO

IF NOT EXISTS (SELECT 1 FROM dbo.system_settings)
BEGIN
    INSERT INTO dbo.system_settings (setting_key, setting_value, description) VALUES
    ('booking_advance_days','30',              'Maximum days in advance students can book'),
    ('cancellation_hours',  '24',              'Minimum hours before appointment to cancel'),
    ('reminder_hours',      '24',              'Hours before appointment to send reminder'),
    ('max_active_bookings', '5',               'Maximum active bookings per student'),
    ('system_email',        'bookings@wsu.ac.za','System email for notifications');
END
GO

-- ============================================
-- ADMINS TABLE
-- ============================================

IF NOT EXISTS (SELECT * FROM sysobjects WHERE name='admins' AND xtype='U')
CREATE TABLE dbo.admins (
    admin_id      INT IDENTITY(1,1) PRIMARY KEY,
    username      VARCHAR(50)  NOT NULL UNIQUE,
    full_name     VARCHAR(100) NOT NULL,
    email         VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role          VARCHAR(30)  NOT NULL DEFAULT 'admin' CHECK (role IN ('super_admin','admin')),
    status        VARCHAR(10)  NOT NULL DEFAULT 'active' CHECK (status IN ('active','inactive')),
    last_login    DATETIME     NULL,
    created_at    DATETIME     NOT NULL DEFAULT GETDATE()
);
GO

-- Seed: admin / admin123
IF NOT EXISTS (SELECT 1 FROM dbo.admins)
BEGIN
    INSERT INTO dbo.admins (username, full_name, email, password_hash, role) VALUES
    ('admin', 'System Administrator', 'admin@wsu.ac.za', '$2y$10$T./d56vL6qf6PHYYdZA7/.8IRTzCC0tkBc0qeh0XXKzSKJzaFP0qC', 'super_admin');
END
GO
