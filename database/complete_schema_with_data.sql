-- ============================================
-- WSU BOOKING SYSTEM - COMPLETE DATABASE SCHEMA
-- ============================================
-- This file contains the complete database schema and sample data
-- for the WSU Booking System including:
-- - Core booking system (students, staff, services, bookings)
-- - Tutor/PAL system (modules, sessions, assignments)
-- - Coordinator system (at-risk modules, tutor assignments)
-- - Readiness scores for students
-- ============================================

-- Drop existing database and create fresh
DROP DATABASE IF EXISTS wsu_booking;
CREATE DATABASE wsu_booking CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE wsu_booking;

-- ============================================
-- CORE TABLES
-- ============================================

-- Campuses
CREATE TABLE campuses (
    campus_id INT AUTO_INCREMENT PRIMARY KEY,
    campus_name VARCHAR(100) NOT NULL UNIQUE,
    campus_code VARCHAR(20) NOT NULL UNIQUE,
    location VARCHAR(255),
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_status (status)
) ENGINE=InnoDB;

-- Faculties
CREATE TABLE faculties (
    faculty_id INT AUTO_INCREMENT PRIMARY KEY,
    faculty_name VARCHAR(100) NOT NULL,
    faculty_code VARCHAR(20) NOT NULL,
    campus_id INT,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (campus_id) REFERENCES campuses(campus_id) ON DELETE SET NULL,
    INDEX idx_campus (campus_id),
    INDEX idx_status (status)
) ENGINE=InnoDB;

-- Students
CREATE TABLE students (
    student_id VARCHAR(20) PRIMARY KEY,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    phone VARCHAR(15),
    faculty_id INT,
    year_of_study TINYINT CHECK (year_of_study BETWEEN 1 AND 6),
    reading_score DECIMAL(5,2) NULL COMMENT 'Student readiness score (0-100)',
    student_type ENUM('undergraduate', 'postgraduate', 'honours', 'masters', 'phd') DEFAULT 'undergraduate',
    status ENUM('active', 'inactive', 'suspended', 'graduated') DEFAULT 'active',
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (faculty_id) REFERENCES faculties(faculty_id) ON DELETE SET NULL,
    INDEX idx_email (email),
    INDEX idx_status (status),
    INDEX idx_faculty (faculty_id)
) ENGINE=InnoDB;

-- Departments
CREATE TABLE departments (
    department_id INT AUTO_INCREMENT PRIMARY KEY,
    department_name VARCHAR(100) NOT NULL,
    department_code VARCHAR(20) NOT NULL,
    faculty_id INT,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (faculty_id) REFERENCES faculties(faculty_id) ON DELETE SET NULL,
    INDEX idx_faculty (faculty_id)
) ENGINE=InnoDB;

-- Staff
CREATE TABLE staff (
    staff_id INT AUTO_INCREMENT PRIMARY KEY,
    staff_number VARCHAR(20) UNIQUE NOT NULL,
    student_number VARCHAR(50),
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    phone VARCHAR(15),
    department_id INT,
    campus_id INT,
    role ENUM('admin', 'counsellor', 'academic_advisor', 'career_counsellor', 'financial_advisor', 'coordinator', 'tutor', 'pal') NOT NULL,
    qualification VARCHAR(255),
    gpa DECIMAL(3,2),
    academic_year_level VARCHAR(50),
    specialization TEXT,
    modules_tutored TEXT,
    status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
    application_date DATE,
    approval_date DATE,
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (department_id) REFERENCES departments(department_id) ON DELETE SET NULL,
    FOREIGN KEY (campus_id) REFERENCES campuses(campus_id) ON DELETE SET NULL,
    INDEX idx_role (role),
    INDEX idx_status (status),
    INDEX idx_campus (campus_id)
) ENGINE=InnoDB;

-- Service Categories
CREATE TABLE service_categories (
    category_id INT AUTO_INCREMENT PRIMARY KEY,
    category_name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    icon VARCHAR(50),
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Services
CREATE TABLE services (
    service_id INT AUTO_INCREMENT PRIMARY KEY,
    service_name VARCHAR(100) NOT NULL,
    service_code VARCHAR(20) UNIQUE NOT NULL,
    category_id INT NOT NULL,
    description TEXT,
    duration_minutes INT NOT NULL DEFAULT 30,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES service_categories(category_id) ON DELETE RESTRICT,
    INDEX idx_category (category_id),
    INDEX idx_status (status)
) ENGINE=InnoDB;

-- Staff Schedules (links staff to services they provide)
CREATE TABLE staff_schedules (
    schedule_id INT AUTO_INCREMENT PRIMARY KEY,
    staff_id INT NOT NULL,
    service_id INT NOT NULL,
    day_of_week ENUM('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday') NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    location VARCHAR(255),
    max_bookings INT DEFAULT 1,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (staff_id) REFERENCES staff(staff_id) ON DELETE CASCADE,
    FOREIGN KEY (service_id) REFERENCES services(service_id) ON DELETE CASCADE,
    INDEX idx_staff (staff_id),
    INDEX idx_service (service_id),
    INDEX idx_day (day_of_week)
) ENGINE=InnoDB;

-- Bookings
CREATE TABLE bookings (
    booking_id INT AUTO_INCREMENT PRIMARY KEY,
    booking_reference VARCHAR(50) UNIQUE NOT NULL,
    student_id VARCHAR(20) NOT NULL,
    service_id INT NOT NULL,
    staff_id INT NOT NULL,
    booking_date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    location VARCHAR(255),
    status ENUM('pending', 'confirmed', 'completed', 'cancelled', 'no_show') DEFAULT 'pending',
    notes TEXT,
    cancellation_reason TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE RESTRICT,
    FOREIGN KEY (service_id) REFERENCES services(service_id) ON DELETE RESTRICT,
    FOREIGN KEY (staff_id) REFERENCES staff(staff_id) ON DELETE RESTRICT,
    INDEX idx_student (student_id),
    INDEX idx_staff (staff_id),
    INDEX idx_date (booking_date),
    INDEX idx_status (status)
) ENGINE=InnoDB;

-- Booking History
CREATE TABLE booking_history (
    history_id INT AUTO_INCREMENT PRIMARY KEY,
    booking_id INT NOT NULL,
    old_status VARCHAR(50),
    new_status VARCHAR(50),
    changed_by INT,
    change_reason TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (booking_id) REFERENCES bookings(booking_id) ON DELETE CASCADE,
    INDEX idx_booking (booking_id)
) ENGINE=InnoDB;

-- Feedback
CREATE TABLE feedback (
    feedback_id INT AUTO_INCREMENT PRIMARY KEY,
    booking_id INT NOT NULL,
    student_id VARCHAR(20) NOT NULL,
    staff_id INT NOT NULL,
    rating TINYINT CHECK (rating BETWEEN 1 AND 5),
    comments TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (booking_id) REFERENCES bookings(booking_id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE,
    FOREIGN KEY (staff_id) REFERENCES staff(staff_id) ON DELETE CASCADE,
    UNIQUE KEY unique_feedback (booking_id)
) ENGINE=InnoDB;

-- ============================================
-- TUTOR/PAL SYSTEM TABLES
-- ============================================

-- Modules
CREATE TABLE modules (
    module_id INT AUTO_INCREMENT PRIMARY KEY,
    subject_code VARCHAR(20) NOT NULL,
    subject_name VARCHAR(255) NOT NULL,
    faculty_id INT,
    campus_id INT,
    academic_year VARCHAR(10),
    semester VARCHAR(20),
    block VARCHAR(20),
    headcount INT DEFAULT 0,
    enrolled_students INT DEFAULT 0,
    pass_rate DECIMAL(5,2),
    status ENUM('active', 'inactive', 'archived') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (faculty_id) REFERENCES faculties(faculty_id) ON DELETE SET NULL,
    FOREIGN KEY (campus_id) REFERENCES campuses(campus_id) ON DELETE SET NULL,
    UNIQUE KEY unique_module (subject_code, campus_id, academic_year, semester),
    INDEX idx_faculty (faculty_id),
    INDEX idx_campus (campus_id),
    INDEX idx_status (status)
) ENGINE=InnoDB;

-- Student Modules (enrollment)
CREATE TABLE student_modules (
    enrollment_id INT AUTO_INCREMENT PRIMARY KEY,
    student_id VARCHAR(20) NOT NULL,
    module_id INT NOT NULL,
    academic_year VARCHAR(10) NOT NULL,
    semester VARCHAR(20) NOT NULL,
    enrollment_date DATE NOT NULL,
    status ENUM('active', 'dropped', 'completed', 'failed') DEFAULT 'active',
    final_mark DECIMAL(5,2) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE,
    FOREIGN KEY (module_id) REFERENCES modules(module_id) ON DELETE CASCADE,
    UNIQUE KEY unique_enrollment (student_id, module_id, academic_year, semester),
    INDEX idx_student (student_id),
    INDEX idx_module (module_id)
) ENGINE=InnoDB;

-- At-Risk Modules
CREATE TABLE at_risk_modules (
    risk_id INT AUTO_INCREMENT PRIMARY KEY,
    module_id INT NOT NULL,
    coordinator_id INT,
    risk_level ENUM('low', 'medium', 'high', 'critical') DEFAULT 'medium',
    reason TEXT,
    intervention_plan TEXT,
    flagged_date DATE NOT NULL,
    status ENUM('active', 'resolved', 'monitoring') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (module_id) REFERENCES modules(module_id) ON DELETE CASCADE,
    FOREIGN KEY (coordinator_id) REFERENCES staff(staff_id) ON DELETE SET NULL,
    INDEX idx_module (module_id),
    INDEX idx_coordinator (coordinator_id),
    INDEX idx_status (status)
) ENGINE=InnoDB;

-- Tutor Assignments
CREATE TABLE tutor_assignments (
    assignment_id INT AUTO_INCREMENT PRIMARY KEY,
    tutor_id INT NOT NULL,
    risk_module_id INT NOT NULL,
    tutor_type ENUM('tutor', 'pal') NOT NULL,
    assigned_by INT,
    assignment_date DATE NOT NULL,
    status ENUM('active', 'completed', 'cancelled') DEFAULT 'active',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (tutor_id) REFERENCES staff(staff_id) ON DELETE CASCADE,
    FOREIGN KEY (risk_module_id) REFERENCES at_risk_modules(risk_id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_by) REFERENCES staff(staff_id) ON DELETE SET NULL,
    INDEX idx_tutor (tutor_id),
    INDEX idx_module (risk_module_id),
    INDEX idx_status (status)
) ENGINE=InnoDB;

-- Tutor Sessions
CREATE TABLE tutor_sessions (
    session_id INT AUTO_INCREMENT PRIMARY KEY,
    assignment_id INT NOT NULL,
    session_date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    location VARCHAR(255) NOT NULL,
    topic VARCHAR(255) NOT NULL,
    description TEXT,
    max_students INT DEFAULT 30,
    status ENUM('scheduled', 'completed', 'cancelled') DEFAULT 'scheduled',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (assignment_id) REFERENCES tutor_assignments(assignment_id) ON DELETE CASCADE,
    INDEX idx_assignment (assignment_id),
    INDEX idx_date (session_date),
    INDEX idx_status (status)
) ENGINE=InnoDB;

-- Session Registrations
CREATE TABLE session_registrations (
    registration_id INT AUTO_INCREMENT PRIMARY KEY,
    session_id INT NOT NULL,
    student_id VARCHAR(20) NOT NULL,
    registration_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    attended BOOLEAN DEFAULT FALSE,
    attendance_marked_at TIMESTAMP NULL,
    feedback TEXT,
    rating TINYINT CHECK (rating BETWEEN 1 AND 5),
    status ENUM('registered', 'attended', 'absent', 'cancelled') DEFAULT 'registered',
    FOREIGN KEY (session_id) REFERENCES tutor_sessions(session_id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE,
    UNIQUE KEY unique_registration (session_id, student_id),
    INDEX idx_session (session_id),
    INDEX idx_student (student_id)
) ENGINE=InnoDB;

-- Notifications
CREATE TABLE notifications (
    notification_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id VARCHAR(50) NOT NULL,
    user_type ENUM('student', 'staff') NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    type ENUM('info', 'success', 'warning', 'error') DEFAULT 'info',
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user (user_id, user_type),
    INDEX idx_read (is_read)
) ENGINE=InnoDB;

-- ============================================
-- SAMPLE DATA - CAMPUSES
-- ============================================

INSERT INTO campuses (campus_name, campus_code, location, status) VALUES
('Mthatha Campus', 'MTH', 'Mthatha, Eastern Cape', 'active'),
('Butterworth Campus', 'BTW', 'Butterworth, Eastern Cape', 'active'),
('East London Campus', 'EL', 'East London, Eastern Cape', 'active'),
('Queenstown Campus', 'QTN', 'Queenstown, Eastern Cape', 'active');

-- ============================================
-- SAMPLE DATA - FACULTIES
-- ============================================

INSERT INTO faculties (faculty_name, faculty_code, campus_id, status) VALUES
('Faculty of Science', 'SCI', 1, 'active'),
('Faculty of Education', 'EDU', 1, 'active'),
('Faculty of Health Sciences', 'HEA', 1, 'active'),
('Faculty of Management and Public Administration Sciences', 'MPA', 2, 'active'),
('Faculty of Law', 'LAW', 1, 'active');

-- ============================================
-- SAMPLE DATA - DEPARTMENTS
-- ============================================

INSERT INTO departments (department_name, department_code, faculty_id, status) VALUES
('Computer Science', 'CS', 1, 'active'),
('Mathematics', 'MATH', 1, 'active'),
('Student Counselling', 'COUNS', 2, 'active'),
('Career Services', 'CAREER', 2, 'active'),
('Academic Support', 'ACAD', 2, 'active');

-- ============================================
-- SAMPLE DATA - STUDENTS
-- ============================================

-- Password for all: password123
-- Hash: $2y$10$T./d56vL6qf6PHYYdZA7/.8IRTzCC0tkBc0qeh0XXKzSKJzaFP0qC

INSERT INTO students (student_id, first_name, last_name, email, password_hash, phone, faculty_id, year_of_study, reading_score, student_type, status) VALUES
-- Excellent readiness scores (80-100)
('220234501', 'Ayanda', 'Nkosi', 'ayanda.nkosi@wsu.ac.za', '$2y$10$T./d56vL6qf6PHYYdZA7/.8IRTzCC0tkBc0qeh0XXKzSKJzaFP0qC', '0731234501', 1, 1, 95.00, 'undergraduate', 'active'),
('220234502', 'Busisiwe', 'Dlamini', 'busisiwe.dlamini@wsu.ac.za', '$2y$10$T./d56vL6qf6PHYYdZA7/.8IRTzCC0tkBc0qeh0XXKzSKJzaFP0qC', '0731234502', 1, 1, 88.50, 'undergraduate', 'active'),
('220234503', 'Cebo', 'Mkhize', 'cebo.mkhize@wsu.ac.za', '$2y$10$T./d56vL6qf6PHYYdZA7/.8IRTzCC0tkBc0qeh0XXKzSKJzaFP0qC', '0731234503', 1, 1, 92.75, 'undergraduate', 'active'),
('220234504', 'Dineo', 'Mokoena', 'dineo.mokoena@wsu.ac.za', '$2y$10$T./d56vL6qf6PHYYdZA7/.8IRTzCC0tkBc0qeh0XXKzSKJzaFP0qC', '0731234504', 1, 1, 85.00, 'undergraduate', 'active'),
('220234505', 'Ethan', 'Zulu', 'ethan.zulu@wsu.ac.za', '$2y$10$T./d56vL6qf6PHYYdZA7/.8IRTzCC0tkBc0qeh0XXKzSKJzaFP0qC', '0731234505', 1, 1, 91.25, 'undergraduate', 'active'),
('220234506', 'Fikile', 'Ndlovu', 'fikile.ndlovu@wsu.ac.za', '$2y$10$T./d56vL6qf6PHYYdZA7/.8IRTzCC0tkBc0qeh0XXKzSKJzaFP0qC', '0731234506', 1, 1, 87.50, 'undergraduate', 'active'),
('220234507', 'Gcina', 'Khumalo', 'gcina.khumalo@wsu.ac.za', '$2y$10$T./d56vL6qf6PHYYdZA7/.8IRTzCC0tkBc0qeh0XXKzSKJzaFP0qC', '0731234507', 1, 1, 94.00, 'undergraduate', 'active'),
('220234508', 'Hlengiwe', 'Sithole', 'hlengiwe.sithole@wsu.ac.za', '$2y$10$T./d56vL6qf6PHYYdZA7/.8IRTzCC0tkBc0qeh0XXKzSKJzaFP0qC', '0731234508', 1, 1, 89.75, 'undergraduate', 'active'),
('220234509', 'Innocent', 'Mahlangu', 'innocent.mahlangu@wsu.ac.za', '$2y$10$T./d56vL6qf6PHYYdZA7/.8IRTzCC0tkBc0qeh0XXKzSKJzaFP0qC', '0731234509', 1, 1, 83.50, 'undergraduate', 'active'),
('220234510', 'Jabu', 'Cele', 'jabu.cele@wsu.ac.za', '$2y$10$T./d56vL6qf6PHYYdZA7/.8IRTzCC0tkBc0qeh0XXKzSKJzaFP0qC', '0731234510', 1, 1, 90.00, 'undergraduate', 'active'),

-- Good readiness scores (60-79)
('220234511', 'Khanya', 'Radebe', 'khanya.radebe@wsu.ac.za', '$2y$10$T./d56vL6qf6PHYYdZA7/.8IRTzCC0tkBc0qeh0XXKzSKJzaFP0qC', '0731234511', 1, 1, 75.50, 'undergraduate', 'active'),
('220234512', 'Lindiwe', 'Ngubane', 'lindiwe.ngubane@wsu.ac.za', '$2y$10$T./d56vL6qf6PHYYdZA7/.8IRTzCC0tkBc0qeh0XXKzSKJzaFP0qC', '0731234512', 1, 1, 72.00, 'undergraduate', 'active'),
('220234513', 'Mandla', 'Buthelezi', 'mandla.buthelezi@wsu.ac.za', '$2y$10$T./d56vL6qf6PHYYdZA7/.8IRTzCC0tkBc0qeh0XXKzSKJzaFP0qC', '0731234513', 1, 1, 68.75, 'undergraduate', 'active'),
('220234514', 'Nandi', 'Shabalala', 'nandi.shabalala@wsu.ac.za', '$2y$10$T./d56vL6qf6PHYYdZA7/.8IRTzCC0tkBc0qeh0XXKzSKJzaFP0qC', '0731234514', 1, 1, 78.25, 'undergraduate', 'active'),
('220234515', 'Oupa', 'Mthembu', 'oupa.mthembu@wsu.ac.za', '$2y$10$T./d56vL6qf6PHYYdZA7/.8IRTzCC0tkBc0qeh0XXKzSKJzaFP0qC', '0731234515', 1, 1, 71.50, 'undergraduate', 'active'),
('220234516', 'Palesa', 'Dube', 'palesa.dube@wsu.ac.za', '$2y$10$T./d56vL6qf6PHYYdZA7/.8IRTzCC0tkBc0qeh0XXKzSKJzaFP0qC', '0731234516', 1, 1, 76.00, 'undergraduate', 'active'),
('220234517', 'Quinton', 'Gumede', 'quinton.gumede@wsu.ac.za', '$2y$10$T./d56vL6qf6PHYYdZA7/.8IRTzCC0tkBc0qeh0XXKzSKJzaFP0qC', '0731234517', 1, 1, 69.50, 'undergraduate', 'active'),
('220234518', 'Rethabile', 'Moloi', 'rethabile.moloi@wsu.ac.za', '$2y$10$T./d56vL6qf6PHYYdZA7/.8IRTzCC0tkBc0qeh0XXKzSKJzaFP0qC', '0731234518', 1, 1, 74.75, 'undergraduate', 'active'),
('220234519', 'Sibusiso', 'Zwane', 'sibusiso.zwane@wsu.ac.za', '$2y$10$T./d56vL6qf6PHYYdZA7/.8IRTzCC0tkBc0qeh0XXKzSKJzaFP0qC', '0731234519', 1, 1, 70.25, 'undergraduate', 'active'),
('220234520', 'Thandeka', 'Mkhize', 'thandeka.mkhize@wsu.ac.za', '$2y$10$T./d56vL6qf6PHYYdZA7/.8IRTzCC0tkBc0qeh0XXKzSKJzaFP0qC', '0731234520', 1, 1, 77.50, 'undergraduate', 'active'),

-- Fair readiness scores (40-59)
('220234521', 'Unathi', 'Gumede', 'unathi.gumede@wsu.ac.za', '$2y$10$T./d56vL6qf6PHYYdZA7/.8IRTzCC0tkBc0qeh0XXKzSKJzaFP0qC', '0731234521', 1, 1, 55.00, 'undergraduate', 'active'),
('220234522', 'Vusi', 'Shabalala', 'vusi.shabalala@wsu.ac.za', '$2y$10$T./d56vL6qf6PHYYdZA7/.8IRTzCC0tkBc0qeh0XXKzSKJzaFP0qC', '0731234522', 1, 1, 48.50, 'undergraduate', 'active'),
('220234523', 'Winnie', 'Nkosi', 'winnie.nkosi@wsu.ac.za', '$2y$10$T./d56vL6qf6PHYYdZA7/.8IRTzCC0tkBc0qeh0XXKzSKJzaFP0qC', '0731234523', 1, 1, 52.75, 'undergraduate', 'active'),
('220234524', 'Xolani', 'Dlamini', 'xolani.dlamini@wsu.ac.za', '$2y$10$T./d56vL6qf6PHYYdZA7/.8IRTzCC0tkBc0qeh0XXKzSKJzaFP0qC', '0731234524', 1, 1, 45.00, 'undergraduate', 'active'),
('220234525', 'Yolanda', 'Khumalo', 'yolanda.khumalo@wsu.ac.za', '$2y$10$T./d56vL6qf6PHYYdZA7/.8IRTzCC0tkBc0qeh0XXKzSKJzaFP0qC', '0731234525', 1, 1, 58.25, 'undergraduate', 'active'),

-- At-risk readiness scores (below 40)
('220234526', 'Zanele', 'Sithole', 'zanele.sithole@wsu.ac.za', '$2y$10$T./d56vL6qf6PHYYdZA7/.8IRTzCC0tkBc0qeh0XXKzSKJzaFP0qC', '0731234526', 1, 1, 38.00, 'undergraduate', 'active'),
('220234527', 'Andile', 'Mahlangu', 'andile.mahlangu@wsu.ac.za', '$2y$10$T./d56vL6qf6PHYYdZA7/.8IRTzCC0tkBc0qeh0XXKzSKJzaFP0qC', '0731234527', 1, 1, 32.50, 'undergraduate', 'active'),
('220234528', 'Buhle', 'Cele', 'buhle.cele@wsu.ac.za', '$2y$10$T./d56vL6qf6PHYYdZA7/.8IRTzCC0tkBc0qeh0XXKzSKJzaFP0qC', '0731234528', 1, 1, 35.75, 'undergraduate', 'active');

-- ============================================
-- SAMPLE DATA - STAFF
-- ============================================

INSERT INTO staff (staff_number, student_number, first_name, last_name, email, password_hash, phone, department_id, campus_id, role, qualification, gpa, academic_year_level, specialization, modules_tutored, status, application_date, approval_date) VALUES
-- Admin
('ADMIN001', NULL, 'System', 'Administrator', 'admin@wsu.ac.za', '$2y$10$T./d56vL6qf6PHYYdZA7/.8IRTzCC0tkBc0qeh0XXKzSKJzaFP0qC', '0431234567', 1, 1, 'admin', 'MSc Computer Science', NULL, NULL, NULL, NULL, 'active', NULL, NULL),

-- Coordinators (one per campus)
('COORD001', NULL, 'Nomvula', 'Mthembu', 'nomvula.mthembu@wsu.ac.za', '$2y$10$T./d56vL6qf6PHYYdZA7/.8IRTzCC0tkBc0qeh0XXKzSKJzaFP0qC', '0431234568', 1, 1, 'coordinator', 'PhD Education', NULL, NULL, 'Academic Coordination', NULL, 'active', '2024-01-10', '2024-01-15'),

-- Counsellors and Advisors
('ST001', NULL, 'Thabo', 'Molefe', 'thabo.molefe@wsu.ac.za', '$2y$10$T./d56vL6qf6PHYYdZA7/.8IRTzCC0tkBc0qeh0XXKzSKJzaFP0qC', '0821234567', 3, 1, 'counsellor', 'MA Psychology', NULL, NULL, 'Student Counselling', NULL, 'active', NULL, NULL),
('ST002', NULL, 'Lerato', 'Dlamini', 'lerato.dlamini@wsu.ac.za', '$2y$10$T./d56vL6qf6PHYYdZA7/.8IRTzCC0tkBc0qeh0XXKzSKJzaFP0qC', '0829876543', 4, 1, 'academic_advisor', 'MEd Educational Psychology', NULL, NULL, 'Academic Advising', NULL, 'active', NULL, NULL),
('ST003', NULL, 'Sipho', 'Nkosi', 'sipho.nkosi@wsu.ac.za', '$2y$10$T./d56vL6qf6PHYYdZA7/.8IRTzCC0tkBc0qeh0XXKzSKJzaFP0qC', '0835551234', 4, 1, 'career_counsellor', 'BA Social Work', NULL, NULL, 'Career Guidance', NULL, 'active', NULL, NULL),

-- Tutors (2nd/3rd year undergraduates)
('TUT001', '220123456', 'Sipho', 'Mthembu', 'sipho.mthembu@wsu.ac.za', '$2y$10$T./d56vL6qf6PHYYdZA7/.8IRTzCC0tkBc0qeh0XXKzSKJzaFP0qC', '0834567891', 1, 1, 'tutor', 'BSc Computer Science', 3.65, '3rd Year', 'Programming and Web Development', 'CS101, IT101, IT102', 'active', '2024-01-15', '2024-01-20'),
('TUT002', '220123457', 'Nomsa', 'Dlamini', 'nomsa.dlamini.tutor@wsu.ac.za', '$2y$10$T./d56vL6qf6PHYYdZA7/.8IRTzCC0tkBc0qeh0XXKzSKJzaFP0qC', '0834567892', 2, 1, 'tutor', 'BSc Mathematics', 3.72, '3rd Year', 'Calculus and Statistics', 'MATH101, MATH201', 'active', '2024-01-16', '2024-01-21'),

-- PALs (3rd/4th year senior students)
('PAL001', '221234567', 'Thandi', 'Khumalo', 'thandi.khumalo.pal@wsu.ac.za', '$2y$10$T./d56vL6qf6PHYYdZA7/.8IRTzCC0tkBc0qeh0XXKzSKJzaFP0qC', '0834567895', 1, 1, 'pal', 'BSc Computer Science', 3.75, '4th Year', 'PAL Leader - Programming (Low Pass Rate)', 'CS101, IT101', 'active', '2024-02-01', '2024-02-05'),
('PAL002', '221234568', 'Bongani', 'Sithole', 'bongani.sithole.pal@wsu.ac.za', '$2y$10$T./d56vL6qf6PHYYdZA7/.8IRTzCC0tkBc0qeh0XXKzSKJzaFP0qC', '0834567896', 1, 1, 'pal', 'BSc Computer Science', 3.68, '3rd Year', 'PAL Leader - Database Systems (Low Pass Rate)', 'IT102', 'active', '2024-02-02', '2024-02-06');

-- ============================================
-- SAMPLE DATA - SERVICE CATEGORIES & SERVICES
-- ============================================

INSERT INTO service_categories (category_name, description, icon, status) VALUES
('Academic Counselling', 'Support for academic challenges and study strategies', 'fa-book', 'active'),
('Personal Counselling', 'Mental health and personal wellbeing support', 'fa-heart', 'active'),
('Career Guidance', 'Career planning and job search assistance', 'fa-briefcase', 'active'),
('Financial Advising', 'Financial aid and budgeting guidance', 'fa-money-bill', 'active');

INSERT INTO services (service_name, service_code, category_id, description, duration_minutes, status) VALUES
('Academic Performance Review', 'APR001', 1, 'One-on-one review of academic progress', 45, 'active'),
('Study Skills Workshop', 'SSW001', 1, 'Learn effective study techniques', 60, 'active'),
('Stress Management', 'SM001', 2, 'Coping strategies for academic stress', 45, 'active'),
('Career Planning Session', 'CPS001', 3, 'Explore career options and pathways', 60, 'active'),
('Resume Review', 'RR001', 3, 'Professional resume feedback', 30, 'active'),
('Financial Aid Consultation', 'FAC001', 4, 'Discuss financial aid options', 45, 'active');

-- ============================================
-- SAMPLE DATA - MODULES
-- ============================================

INSERT INTO modules (subject_code, subject_name, faculty_id, campus_id, academic_year, semester, block, headcount, enrolled_students, pass_rate, status) VALUES
('CS101', 'Introduction to Programming', 1, 1, '2024', 'Semester 1', 'BLK1', 120, 115, 45.50, 'active'),
('IT101', 'Information Technology Fundamentals', 1, 1, '2024', 'Semester 1', 'BLK1', 100, 95, 52.30, 'active'),
('IT102', 'Database Systems', 1, 1, '2024', 'Semester 1', 'BLK2', 85, 80, 48.75, 'active'),
('MATH101', 'Calculus I', 1, 1, '2024', 'Semester 1', 'BLK1', 150, 145, 55.20, 'active'),
('MATH201', 'Linear Algebra', 1, 1, '2024', 'Semester 2', 'BLK2', 90, 85, 60.00, 'active');

-- ============================================
-- SAMPLE DATA - STUDENT MODULE ENROLLMENTS
-- ============================================

-- Enroll first 20 students in CS101
INSERT INTO student_modules (student_id, module_id, academic_year, semester, enrollment_date, status) 
SELECT student_id, 1, '2024', 'Semester 1', '2024-02-01', 'active'
FROM students 
WHERE student_id BETWEEN '220234501' AND '220234520';

-- Enroll students 11-25 in MATH101
INSERT INTO student_modules (student_id, module_id, academic_year, semester, enrollment_date, status) 
SELECT student_id, 4, '2024', 'Semester 1', '2024-02-01', 'active'
FROM students 
WHERE student_id BETWEEN '220234511' AND '220234525';

-- ============================================
-- SAMPLE DATA - AT-RISK MODULES & ASSIGNMENTS
-- ============================================

-- Flag CS101 as at-risk (low pass rate)
INSERT INTO at_risk_modules (module_id, coordinator_id, risk_level, reason, intervention_plan, flagged_date, status) VALUES
(1, 2, 'high', 'Pass rate below 50% - students struggling with programming concepts', 'Assign PAL leader and tutors for additional support sessions', '2024-02-15', 'active');

-- Flag IT102 as at-risk
INSERT INTO at_risk_modules (module_id, coordinator_id, risk_level, reason, intervention_plan, flagged_date, status) VALUES
(3, 2, 'high', 'Database concepts proving difficult - low pass rate', 'Assign PAL for hands-on database practice sessions', '2024-02-15', 'active');

-- Assign PAL001 (Thandi) to CS101
INSERT INTO tutor_assignments (tutor_id, risk_module_id, tutor_type, assigned_by, assignment_date, status, notes) VALUES
(8, 1, 'pal', 2, '2024-02-20', 'active', 'Thandi will lead PAL sessions for CS101 - attends classes with students');

-- Assign TUT001 (Sipho) to CS101
INSERT INTO tutor_assignments (tutor_id, risk_module_id, tutor_type, assigned_by, assignment_date, status, notes) VALUES
(6, 1, 'tutor', 2, '2024-02-20', 'active', 'Sipho will provide tutoring support for CS101');

-- Assign PAL002 (Bongani) to IT102
INSERT INTO tutor_assignments (tutor_id, risk_module_id, tutor_type, assigned_by, assignment_date, status, notes) VALUES
(9, 2, 'pal', 2, '2024-02-20', 'active', 'Bongani will lead PAL sessions for IT102 database module');

-- ============================================
-- SAMPLE DATA - TUTOR SESSIONS
-- ============================================

-- PAL001 sessions for CS101
INSERT INTO tutor_sessions (assignment_id, session_date, start_time, end_time, location, topic, description, max_students, status) VALUES
(1, '2024-03-05', '14:00:00', '16:00:00', 'Lab A101', 'Problem-Solving Strategies in Programming', 'Learn systematic approaches to solving programming problems', 30, 'completed'),
(1, '2024-03-12', '14:00:00', '16:00:00', 'Lab A101', 'Debugging Techniques', 'Master debugging tools and strategies', 30, 'completed'),
(1, '2024-03-19', '14:00:00', '16:00:00', 'Lab A101', 'Data Structures Basics', 'Introduction to arrays and lists', 30, 'scheduled');

-- TUT001 sessions for CS101
INSERT INTO tutor_sessions (assignment_id, session_date, start_time, end_time, location, topic, description, max_students, status) VALUES
(2, '2024-03-06', '15:00:00', '17:00:00', 'Lab B202', 'Introduction to Variables and Data Types', 'Understanding variables, integers, strings, and booleans', 25, 'completed'),
(2, '2024-03-13', '15:00:00', '17:00:00', 'Lab B202', 'Control Structures', 'If statements, loops, and conditionals', 25, 'scheduled');

-- PAL002 sessions for IT102
INSERT INTO tutor_sessions (assignment_id, session_date, start_time, end_time, location, topic, description, max_students, status) VALUES
(3, '2024-03-07', '13:00:00', '15:00:00', 'Lab C303', 'SQL Basics', 'SELECT, INSERT, UPDATE, DELETE operations', 30, 'completed'),
(3, '2024-03-14', '13:00:00', '15:00:00', 'Lab C303', 'Database Design', 'Entity-Relationship diagrams and normalization', 30, 'scheduled');

-- ============================================
-- SAMPLE DATA - SESSION REGISTRATIONS
-- ============================================

-- Register students for PAL001 first session (completed)
INSERT INTO session_registrations (session_id, student_id, registration_date, attended, attendance_marked_at, status) VALUES
(1, '220234501', '2024-03-04 10:00:00', TRUE, '2024-03-05 16:05:00', 'attended'),
(1, '220234502', '2024-03-04 10:15:00', TRUE, '2024-03-05 16:05:00', 'attended'),
(1, '220234503', '2024-03-04 10:30:00', TRUE, '2024-03-05 16:05:00', 'attended'),
(1, '220234504', '2024-03-04 11:00:00', FALSE, '2024-03-05 16:05:00', 'absent'),
(1, '220234505', '2024-03-04 11:15:00', TRUE, '2024-03-05 16:05:00', 'attended');

-- Register students for TUT001 first session (completed)
INSERT INTO session_registrations (session_id, student_id, registration_date, attended, attendance_marked_at, status) VALUES
(4, '220234501', '2024-03-05 09:00:00', TRUE, '2024-03-06 17:05:00', 'attended'),
(4, '220234502', '2024-03-05 09:15:00', TRUE, '2024-03-06 17:05:00', 'attended'),
(4, '220234506', '2024-03-05 09:30:00', TRUE, '2024-03-06 17:05:00', 'attended');

-- ============================================
-- SAMPLE DATA - STAFF SCHEDULES
-- ============================================

INSERT INTO staff_schedules (staff_id, service_id, day_of_week, start_time, end_time, location, max_bookings, status) VALUES
-- Counsellor (ST001) schedule
(3, 1, 'Monday', '09:00:00', '12:00:00', 'Counselling Office 101', 6, 'active'),
(3, 1, 'Wednesday', '09:00:00', '12:00:00', 'Counselling Office 101', 6, 'active'),
(3, 3, 'Tuesday', '14:00:00', '17:00:00', 'Counselling Office 101', 6, 'active'),
(3, 3, 'Thursday', '14:00:00', '17:00:00', 'Counselling Office 101', 6, 'active'),

-- Academic Advisor (ST002) schedule
(4, 1, 'Monday', '10:00:00', '13:00:00', 'Academic Support Center', 6, 'active'),
(4, 2, 'Wednesday', '10:00:00', '13:00:00', 'Academic Support Center', 4, 'active'),
(4, 1, 'Friday', '09:00:00', '12:00:00', 'Academic Support Center', 6, 'active'),

-- Career Counsellor (ST003) schedule
(5, 4, 'Tuesday', '09:00:00', '12:00:00', 'Career Services Office', 4, 'active'),
(5, 5, 'Tuesday', '13:00:00', '16:00:00', 'Career Services Office', 8, 'active'),
(5, 4, 'Thursday', '09:00:00', '12:00:00', 'Career Services Office', 4, 'active');

-- ============================================
-- SAMPLE DATA - BOOKINGS
-- ============================================

INSERT INTO bookings (booking_reference, student_id, service_id, staff_id, booking_date, start_time, end_time, location, status, notes) VALUES
('BK2024030001', '220234501', 1, 3, '2024-03-15', '09:00:00', '09:45:00', 'Counselling Office 101', 'confirmed', 'First counselling session'),
('BK2024030002', '220234502', 1, 4, '2024-03-15', '10:00:00', '10:45:00', 'Academic Support Center', 'confirmed', 'Academic progress review'),
('BK2024030003', '220234503', 4, 5, '2024-03-16', '09:00:00', '10:00:00', 'Career Services Office', 'pending', 'Career planning discussion'),
('BK2024030004', '220234504', 3, 3, '2024-03-12', '14:00:00', '14:45:00', 'Counselling Office 101', 'completed', 'Stress management session'),
('BK2024030005', '220234505', 1, 3, '2024-03-11', '09:00:00', '09:45:00', 'Counselling Office 101', 'completed', 'Follow-up session');

-- ============================================
-- COMPLETION MESSAGE
-- ============================================

SELECT '✓ Database schema created successfully!' as message;
SELECT '✓ Sample data inserted for all tables!' as message;
SELECT CONCAT('✓ ', COUNT(*), ' students created with readiness scores') as message FROM students;
SELECT CONCAT('✓ ', COUNT(*), ' staff members created (admin, coordinators, counsellors, tutors, PALs)') as message FROM staff;
SELECT CONCAT('✓ ', COUNT(*), ' modules created') as message FROM modules;
SELECT CONCAT('✓ ', COUNT(*), ' at-risk modules flagged') as message FROM at_risk_modules;
SELECT CONCAT('✓ ', COUNT(*), ' tutor assignments created') as message FROM tutor_assignments;
SELECT CONCAT('✓ ', COUNT(*), ' tutor sessions scheduled') as message FROM tutor_sessions;
SELECT CONCAT('✓ ', COUNT(*), ' student enrollments created') as message FROM student_modules;
SELECT CONCAT('✓ ', COUNT(*), ' bookings created') as message FROM bookings;

SELECT '
============================================
SYSTEM READY!
============================================
Login Credentials (all passwords: password123):

ADMIN:
- Email: admin@wsu.ac.za

COORDINATOR:
- Email: nomvula.mthembu@wsu.ac.za

STAFF (Counsellors/Advisors):
- Email: thabo.molefe@wsu.ac.za (Counsellor)
- Email: lerato.dlamini@wsu.ac.za (Academic Advisor)
- Email: sipho.nkosi@wsu.ac.za (Career Counsellor)

TUTORS:
- Email: sipho.mthembu@wsu.ac.za (Tutor - CS)
- Email: nomsa.dlamini.tutor@wsu.ac.za (Tutor - Math)

PALs:
- Email: thandi.khumalo.pal@wsu.ac.za (PAL - CS101)
- Email: bongani.sithole.pal@wsu.ac.za (PAL - IT102)

STUDENTS:
- Email: ayanda.nkosi@wsu.ac.za (Readiness: 95.00)
- Email: busisiwe.dlamini@wsu.ac.za (Readiness: 88.50)
- ... and 26 more students with varying readiness scores

============================================
' as 'SYSTEM_INFO';
