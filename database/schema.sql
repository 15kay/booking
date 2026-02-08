-- Walter Sisulu University Booking System - Professional Database Schema
-- Author: Senior Database Architect
-- Version: 1.0

CREATE DATABASE IF NOT EXISTS wsu_booking CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE wsu_booking;

-- ============================================
-- CORE TABLES
-- ============================================

-- Faculties
CREATE TABLE faculties (
    faculty_id INT AUTO_INCREMENT PRIMARY KEY,
    faculty_name VARCHAR(100) NOT NULL UNIQUE,
    faculty_code VARCHAR(10) NOT NULL UNIQUE,
    dean_name VARCHAR(100),
    contact_email VARCHAR(100),
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Departments
CREATE TABLE departments (
    department_id INT AUTO_INCREMENT PRIMARY KEY,
    faculty_id INT NOT NULL,
    department_name VARCHAR(100) NOT NULL,
    department_code VARCHAR(10) NOT NULL,
    head_of_department VARCHAR(100),
    contact_email VARCHAR(100),
    status ENUM('active', 'inactive') DEFAULT 'active',
    FOREIGN KEY (faculty_id) REFERENCES faculties(faculty_id) ON DELETE RESTRICT,
    UNIQUE KEY unique_dept (faculty_id, department_code)
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
    student_type ENUM('undergraduate', 'postgraduate', 'honours', 'masters', 'phd') DEFAULT 'undergraduate',
    status ENUM('active', 'inactive', 'suspended', 'graduated') DEFAULT 'active',
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (faculty_id) REFERENCES faculties(faculty_id) ON DELETE SET NULL,
    INDEX idx_email (email),
    INDEX idx_status (status)
) ENGINE=InnoDB;

-- Staff
CREATE TABLE staff (
    staff_id INT AUTO_INCREMENT PRIMARY KEY,
    staff_number VARCHAR(20) UNIQUE NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    phone VARCHAR(15),
    department_id INT,
    role ENUM('counsellor', 'academic_advisor', 'career_counsellor', 'financial_advisor', 'admin') NOT NULL,
    qualification VARCHAR(100),
    specialization VARCHAR(200),
    status ENUM('active', 'inactive', 'on_leave') DEFAULT 'active',
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (department_id) REFERENCES departments(department_id) ON DELETE SET NULL,
    INDEX idx_role (role),
    INDEX idx_status (status)
) ENGINE=InnoDB;

-- ============================================
-- SERVICE MANAGEMENT
-- ============================================

-- Service Categories
CREATE TABLE service_categories (
    category_id INT AUTO_INCREMENT PRIMARY KEY,
    category_name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    icon VARCHAR(50),
    display_order INT DEFAULT 0,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Services
CREATE TABLE services (
    service_id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NOT NULL,
    service_name VARCHAR(100) NOT NULL,
    service_code VARCHAR(20) UNIQUE NOT NULL,
    description TEXT,
    duration_minutes INT NOT NULL DEFAULT 30 CHECK (duration_minutes > 0),
    buffer_time_minutes INT DEFAULT 0,
    max_advance_booking_days INT DEFAULT 30,
    cancellation_hours INT DEFAULT 24,
    requires_approval BOOLEAN DEFAULT FALSE,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES service_categories(category_id) ON DELETE RESTRICT,
    INDEX idx_category (category_id),
    INDEX idx_status (status)
) ENGINE=InnoDB;

-- ============================================
-- SCHEDULING
-- ============================================

-- Staff Schedules
CREATE TABLE staff_schedules (
    schedule_id INT AUTO_INCREMENT PRIMARY KEY,
    staff_id INT NOT NULL,
    service_id INT NOT NULL,
    day_of_week TINYINT NOT NULL CHECK (day_of_week BETWEEN 1 AND 7),
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    slot_duration INT DEFAULT 30,
    max_bookings_per_slot INT DEFAULT 1,
    location VARCHAR(100),
    effective_from DATE NOT NULL,
    effective_to DATE NULL,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (staff_id) REFERENCES staff(staff_id) ON DELETE CASCADE,
    FOREIGN KEY (service_id) REFERENCES services(service_id) ON DELETE CASCADE,
    INDEX idx_staff_day (staff_id, day_of_week),
    INDEX idx_service (service_id),
    CHECK (end_time > start_time)
) ENGINE=InnoDB;

-- Staff Leave/Unavailability
CREATE TABLE staff_unavailability (
    unavailability_id INT AUTO_INCREMENT PRIMARY KEY,
    staff_id INT NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    start_time TIME NULL,
    end_time TIME NULL,
    reason ENUM('leave', 'sick', 'meeting', 'training', 'other') NOT NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (staff_id) REFERENCES staff(staff_id) ON DELETE CASCADE,
    INDEX idx_staff_dates (staff_id, start_date, end_date),
    CHECK (end_date >= start_date)
) ENGINE=InnoDB;

-- ============================================
-- BOOKINGS
-- ============================================

-- Bookings
CREATE TABLE bookings (
    booking_id INT AUTO_INCREMENT PRIMARY KEY,
    booking_reference VARCHAR(20) UNIQUE NOT NULL,
    student_id VARCHAR(20) NOT NULL,
    service_id INT NOT NULL,
    staff_id INT NOT NULL,
    booking_date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    location VARCHAR(100),
    status ENUM('pending', 'confirmed', 'cancelled', 'completed', 'no_show', 'rescheduled') DEFAULT 'pending',
    notes TEXT,
    cancellation_reason TEXT,
    cancelled_by ENUM('student', 'staff', 'system') NULL,
    cancelled_at TIMESTAMP NULL,
    reminder_sent BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE RESTRICT,
    FOREIGN KEY (service_id) REFERENCES services(service_id) ON DELETE RESTRICT,
    FOREIGN KEY (staff_id) REFERENCES staff(staff_id) ON DELETE RESTRICT,
    INDEX idx_booking_date (booking_date),
    INDEX idx_student_bookings (student_id, booking_date),
    INDEX idx_staff_bookings (staff_id, booking_date),
    INDEX idx_status (status),
    INDEX idx_reference (booking_reference),
    CHECK (end_time > start_time)
) ENGINE=InnoDB;

-- Booking History (Audit Trail)
CREATE TABLE booking_history (
    history_id INT AUTO_INCREMENT PRIMARY KEY,
    booking_id INT NOT NULL,
    action ENUM('created', 'confirmed', 'cancelled', 'rescheduled', 'completed', 'no_show') NOT NULL,
    old_status VARCHAR(20),
    new_status VARCHAR(20),
    changed_by VARCHAR(50),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (booking_id) REFERENCES bookings(booking_id) ON DELETE CASCADE,
    INDEX idx_booking (booking_id)
) ENGINE=InnoDB;

-- ============================================
-- NOTIFICATIONS & FEEDBACK
-- ============================================

-- Notifications
CREATE TABLE notifications (
    notification_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id VARCHAR(50) NOT NULL,
    user_type ENUM('student', 'staff') NOT NULL,
    booking_id INT NULL,
    notification_type ENUM('booking_confirmed', 'booking_reminder', 'booking_cancelled', 'booking_rescheduled', 'system') NOT NULL,
    title VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    read_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (booking_id) REFERENCES bookings(booking_id) ON DELETE CASCADE,
    INDEX idx_user (user_id, user_type),
    INDEX idx_read (is_read, created_at)
) ENGINE=InnoDB;

-- Feedback/Reviews
CREATE TABLE feedback (
    feedback_id INT AUTO_INCREMENT PRIMARY KEY,
    booking_id INT NOT NULL,
    student_id VARCHAR(20) NOT NULL,
    staff_id INT NOT NULL,
    rating TINYINT CHECK (rating BETWEEN 1 AND 5),
    comments TEXT,
    is_anonymous BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (booking_id) REFERENCES bookings(booking_id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE,
    FOREIGN KEY (staff_id) REFERENCES staff(staff_id) ON DELETE CASCADE,
    UNIQUE KEY unique_feedback (booking_id)
) ENGINE=InnoDB;

-- ============================================
-- SYSTEM CONFIGURATION
-- ============================================

-- System Settings
CREATE TABLE system_settings (
    setting_id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    description TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================
-- INSERT MASTER DATA
-- ============================================

-- Faculties
INSERT INTO faculties (faculty_name, faculty_code, dean_name, contact_email) VALUES
('Faculty of Science and Engineering', 'FSE', 'Prof. Nomvula Dlamini', 'fse@wsu.ac.za'),
('Faculty of Business, Economics and Management Sciences', 'FBEMS', 'Prof. Thabo Mokoena', 'fbems@wsu.ac.za'),
('Faculty of Education', 'FED', 'Prof. Zanele Khumalo', 'fed@wsu.ac.za'),
('Faculty of Health Sciences', 'FHS', 'Prof. Sipho Mthembu', 'fhs@wsu.ac.za'),
('Faculty of Law', 'FLAW', 'Prof. Lindiwe Nkosi', 'flaw@wsu.ac.za');

-- Departments
INSERT INTO departments (faculty_id, department_name, department_code, head_of_department, contact_email) VALUES
(1, 'Computer Science', 'CS', 'Dr. John Smith', 'cs@wsu.ac.za'),
(1, 'Mathematics', 'MATH', 'Dr. Sarah Johnson', 'math@wsu.ac.za'),
(2, 'Business Management', 'BM', 'Dr. Peter Ndlovu', 'bm@wsu.ac.za'),
(3, 'Student Wellness Centre', 'SWC', 'Dr. Linda Khumalo', 'wellness@wsu.ac.za'),
(4, 'Career Development Centre', 'CDC', 'Ms. Thandi Mbeki', 'careers@wsu.ac.za');

-- Service Categories
INSERT INTO service_categories (category_name, description, icon, display_order) VALUES
('Academic Advising', 'Guidance on course selection and program planning to help students achieve their academic goals', 'fa-graduation-cap', 1),
('Career Guidance', 'Assistance in finding suitable career paths and developing professional skills', 'fa-briefcase', 2),
('Healthcare Services', 'Access to medical services and health-related support for students well-being', 'fa-heartbeat', 3),
('Student Life Activities', 'Opportunities for students to engage in various student activities and events', 'fa-users', 4),
('Support Services', 'Assistance with personal and academic challenges, including counseling and support for mental health', 'fa-hands-helping', 5);

-- Services
INSERT INTO services (category_id, service_name, service_code, description, duration_minutes, buffer_time_minutes, max_advance_booking_days, cancellation_hours) VALUES
-- Academic Advising Services
(1, 'Course Selection Consultation', 'ACAD-COURSE', 'Guidance on course selection and module planning', 30, 10, 30, 24),
(1, 'Program Planning Session', 'ACAD-PROGRAM', 'Academic program planning and degree requirements review', 45, 15, 30, 24),
(1, 'Study Skills Workshop', 'ACAD-STUDY', 'Learn effective study techniques and time management', 60, 0, 30, 48),
(1, 'Academic Performance Review', 'ACAD-REVIEW', 'Review academic progress and improvement strategies', 45, 15, 21, 24),
-- Career Guidance Services
(2, 'Career Path Consultation', 'CAREER-PATH', 'Explore suitable career paths and opportunities', 45, 15, 30, 24),
(2, 'Professional Skills Development', 'CAREER-SKILLS', 'Develop professional and workplace skills', 45, 15, 21, 24),
(2, 'CV and Cover Letter Review', 'CAREER-CV', 'Professional CV and cover letter feedback', 30, 10, 21, 24),
(2, 'Interview Preparation', 'CAREER-INTV', 'Mock interviews and preparation coaching', 45, 15, 14, 24),
-- Healthcare Services
(3, 'General Medical Consultation', 'HEALTH-MED', 'General health checkup and medical consultation', 30, 10, 14, 24),
(3, 'Mental Health Counselling', 'HEALTH-MENTAL', 'Mental health support and counselling', 45, 15, 14, 24),
(3, 'Wellness Checkup', 'HEALTH-WELL', 'Overall wellness and health assessment', 30, 10, 21, 24),
-- Student Life Activities
(4, 'Club Registration', 'LIFE-CLUB', 'Register for student clubs and societies', 15, 5, 30, 24),
(4, 'Event Planning Consultation', 'LIFE-EVENT', 'Plan and organize student events', 30, 10, 21, 24),
(4, 'Sports Activities Booking', 'LIFE-SPORT', 'Book sports facilities and activities', 30, 10, 14, 24),
-- Support Services
(5, 'Personal Counselling', 'SUPP-COUNS', 'One-on-one personal counselling session', 45, 15, 14, 24),
(5, 'Crisis Intervention', 'SUPP-CRISIS', 'Immediate crisis support and intervention', 30, 0, 7, 2),
(5, 'Peer Support Session', 'SUPP-PEER', 'Peer-to-peer support and guidance', 30, 10, 14, 24),
(5, 'Financial Aid Consultation', 'SUPP-FIN', 'Financial assistance and bursary guidance', 45, 15, 30, 24),
(5, 'NSFAS Support', 'SUPP-NSFAS', 'NSFAS funding guidance and application support', 45, 15, 30, 24);

-- Staff Members
INSERT INTO staff (staff_number, first_name, last_name, email, password_hash, phone, department_id, role, qualification, specialization) VALUES
('STF001', 'Dr. Sarah', 'Mthembu', 'sarah.mthembu@wsu.ac.za', '$2y$10$T./d56vL6qf6PHYYdZA7/.8IRTzCC0tkBc0qeh0XXKzSKJzaFP0qC', '0827654321', 4, 'counsellor', 'PhD Clinical Psychology', 'Trauma and Anxiety'),
('STF002', 'Prof. John', 'Ndlovu', 'john.ndlovu@wsu.ac.za', '$2y$10$T./d56vL6qf6PHYYdZA7/.8IRTzCC0tkBc0qeh0XXKzSKJzaFP0qC', '0823456789', 1, 'academic_advisor', 'PhD Education', 'Academic Development'),
('STF003', 'Ms. Linda', 'Khumalo', 'linda.khumalo@wsu.ac.za', '$2y$10$T./d56vL6qf6PHYYdZA7/.8IRTzCC0tkBc0qeh0XXKzSKJzaFP0qC', '0829876543', 5, 'career_counsellor', 'MA Career Counselling', 'Career Development'),
('STF004', 'Mr. Thabo', 'Dlamini', 'thabo.dlamini@wsu.ac.za', '$2y$10$T./d56vL6qf6PHYYdZA7/.8IRTzCC0tkBc0qeh0XXKzSKJzaFP0qC', '0821234567', 4, 'counsellor', 'MA Counselling Psychology', 'Student Wellness'),
('STF005', 'Dr. Nomsa', 'Zulu', 'nomsa.zulu@wsu.ac.za', '$2y$10$T./d56vL6qf6PHYYdZA7/.8IRTzCC0tkBc0qeh0XXKzSKJzaFP0qC', '0834567890', 3, 'financial_advisor', 'BCom Honours', 'Financial Aid');

-- Demo Students
INSERT INTO students (student_id, first_name, last_name, email, password_hash, phone, faculty_id, year_of_study, student_type) VALUES
('202401234', 'Sipho', 'Mbeki', '202401234@mywsu.ac.za', '$2y$10$T./d56vL6qf6PHYYdZA7/.8IRTzCC0tkBc0qeh0XXKzSKJzaFP0qC', '0821234567', 1, 2, 'undergraduate'),
('202401235', 'Thandi', 'Nkosi', '202401235@mywsu.ac.za', '$2y$10$T./d56vL6qf6PHYYdZA7/.8IRTzCC0tkBc0qeh0XXKzSKJzaFP0qC', '0829876543', 2, 3, 'undergraduate'),
('202401236', 'Bongani', 'Sithole', '202401236@mywsu.ac.za', '$2y$10$T./d56vL6qf6PHYYdZA7/.8IRTzCC0tkBc0qeh0XXKzSKJzaFP0qC', '0834567890', 3, 1, 'undergraduate');

-- Staff Schedules
INSERT INTO staff_schedules (staff_id, service_id, day_of_week, start_time, end_time, slot_duration, location, effective_from) VALUES
-- Dr. Sarah Mthembu (Counsellor)
(1, 1, 1, '09:00:00', '16:00:00', 45, 'Wellness Centre Room 101', '2024-01-01'),
(1, 1, 2, '09:00:00', '16:00:00', 45, 'Wellness Centre Room 101', '2024-01-01'),
(1, 1, 3, '09:00:00', '16:00:00', 45, 'Wellness Centre Room 101', '2024-01-01'),
(1, 2, 4, '10:00:00', '15:00:00', 60, 'Wellness Centre Group Room', '2024-01-01'),
(1, 3, 5, '09:00:00', '13:00:00', 30, 'Wellness Centre Room 101', '2024-01-01'),
-- Prof. John Ndlovu (Academic Advisor)
(2, 4, 1, '08:00:00', '17:00:00', 30, 'Academic Office B204', '2024-01-01'),
(2, 4, 2, '08:00:00', '17:00:00', 30, 'Academic Office B204', '2024-01-01'),
(2, 5, 3, '09:00:00', '14:00:00', 60, 'Lecture Hall 3', '2024-01-01'),
(2, 6, 4, '09:00:00', '16:00:00', 45, 'Academic Office B204', '2024-01-01'),
-- Ms. Linda Khumalo (Career Counsellor)
(3, 7, 1, '10:00:00', '16:00:00', 45, 'Career Centre Room 5', '2024-01-01'),
(3, 8, 2, '09:00:00', '15:00:00', 30, 'Career Centre Room 5', '2024-01-01'),
(3, 9, 3, '10:00:00', '16:00:00', 45, 'Career Centre Room 5', '2024-01-01'),
(3, 7, 5, '09:00:00', '13:00:00', 45, 'Career Centre Room 5', '2024-01-01'),
-- Mr. Thabo Dlamini (Counsellor)
(4, 1, 2, '10:00:00', '17:00:00', 45, 'Wellness Centre Room 102', '2024-01-01'),
(4, 1, 4, '09:00:00', '16:00:00', 45, 'Wellness Centre Room 102', '2024-01-01'),
-- Dr. Nomsa Zulu (Financial Advisor)
(5, 10, 1, '08:00:00', '16:00:00', 30, 'Financial Aid Office', '2024-01-01'),
(5, 11, 2, '08:00:00', '16:00:00', 45, 'Financial Aid Office', '2024-01-01'),
(5, 12, 3, '09:00:00', '15:00:00', 30, 'Financial Aid Office', '2024-01-01'),
(5, 10, 5, '08:00:00', '14:00:00', 30, 'Financial Aid Office', '2024-01-01');

-- Sample Bookings
INSERT INTO bookings (booking_reference, student_id, service_id, staff_id, booking_date, start_time, end_time, location, status, notes) VALUES
('BK2024120001', '202401234', 1, 1, '2024-12-20', '10:00:00', '10:45:00', 'Wellness Centre Room 101', 'confirmed', 'First counselling session'),
('BK2024120002', '202401234', 4, 2, '2024-12-22', '14:00:00', '14:30:00', 'Academic Office B204', 'pending', 'Course selection for next semester'),
('BK2024120003', '202401235', 7, 3, '2024-12-21', '11:00:00', '11:45:00', 'Career Centre Room 5', 'confirmed', 'Career guidance session'),
('BK2024120004', '202401236', 11, 5, '2024-12-23', '09:00:00', '09:45:00', 'Financial Aid Office', 'confirmed', 'NSFAS application help');

-- System Settings
INSERT INTO system_settings (setting_key, setting_value, description) VALUES
('booking_advance_days', '30', 'Maximum days in advance students can book'),
('cancellation_hours', '24', 'Minimum hours before appointment to cancel'),
('reminder_hours', '24', 'Hours before appointment to send reminder'),
('max_active_bookings', '5', 'Maximum active bookings per student'),
('system_email', 'bookings@wsu.ac.za', 'System email for notifications');
