-- ============================================
-- COORDINATOR & TUTOR/PAL MANAGEMENT SYSTEM
-- ============================================

USE wsu_booking;

-- Update staff roles to include coordinator and tutor
ALTER TABLE staff 
MODIFY COLUMN role ENUM('counsellor', 'academic_advisor', 'career_counsellor', 'financial_advisor', 'coordinator', 'tutor', 'pal', 'admin') NOT NULL;

-- Modules Table
CREATE TABLE IF NOT EXISTS modules (
    module_id INT AUTO_INCREMENT PRIMARY KEY,
    module_code VARCHAR(20) UNIQUE NOT NULL,
    module_name VARCHAR(200) NOT NULL,
    department_id INT,
    faculty_id INT,
    year_level TINYINT CHECK (year_level BETWEEN 1 AND 6),
    semester ENUM('1', '2', 'both') DEFAULT 'both',
    credits INT DEFAULT 0,
    description TEXT,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (department_id) REFERENCES departments(department_id) ON DELETE SET NULL,
    FOREIGN KEY (faculty_id) REFERENCES faculties(faculty_id) ON DELETE SET NULL,
    INDEX idx_module_code (module_code),
    INDEX idx_department (department_id),
    INDEX idx_status (status)
) ENGINE=InnoDB;

-- At-Risk Modules (Modules that need intervention)
CREATE TABLE IF NOT EXISTS at_risk_modules (
    risk_id INT AUTO_INCREMENT PRIMARY KEY,
    module_id INT NOT NULL,
    academic_year VARCHAR(10) NOT NULL,
    semester ENUM('1', '2') NOT NULL,
    risk_level ENUM('low', 'medium', 'high', 'critical') DEFAULT 'medium',
    failure_rate DECIMAL(5,2),
    total_students INT DEFAULT 0,
    at_risk_students INT DEFAULT 0,
    reason TEXT,
    intervention_needed TEXT,
    identified_by INT,
    identified_date DATE NOT NULL,
    status ENUM('active', 'resolved', 'monitoring') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (module_id) REFERENCES modules(module_id) ON DELETE CASCADE,
    FOREIGN KEY (identified_by) REFERENCES staff(staff_id) ON DELETE SET NULL,
    INDEX idx_module (module_id),
    INDEX idx_status (status),
    INDEX idx_risk_level (risk_level)
) ENGINE=InnoDB;

-- Tutor/PAL Assignments
CREATE TABLE IF NOT EXISTS tutor_assignments (
    assignment_id INT AUTO_INCREMENT PRIMARY KEY,
    risk_module_id INT NOT NULL,
    tutor_id INT NOT NULL,
    tutor_type ENUM('tutor', 'pal') NOT NULL,
    assigned_by INT NOT NULL,
    assignment_date DATE NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE,
    max_students INT DEFAULT 15,
    current_students INT DEFAULT 0,
    session_frequency VARCHAR(100),
    location VARCHAR(200),
    notes TEXT,
    status ENUM('active', 'completed', 'cancelled') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (risk_module_id) REFERENCES at_risk_modules(risk_id) ON DELETE CASCADE,
    FOREIGN KEY (tutor_id) REFERENCES staff(staff_id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_by) REFERENCES staff(staff_id) ON DELETE RESTRICT,
    INDEX idx_tutor (tutor_id),
    INDEX idx_risk_module (risk_module_id),
    INDEX idx_status (status)
) ENGINE=InnoDB;

-- Tutor Sessions
CREATE TABLE IF NOT EXISTS tutor_sessions (
    session_id INT AUTO_INCREMENT PRIMARY KEY,
    assignment_id INT NOT NULL,
    session_date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    location VARCHAR(200),
    topic VARCHAR(200),
    max_capacity INT DEFAULT 15,
    registered_students INT DEFAULT 0,
    attendance_count INT DEFAULT 0,
    session_type ENUM('group', 'individual', 'online', 'workshop') DEFAULT 'group',
    status ENUM('scheduled', 'completed', 'cancelled') DEFAULT 'scheduled',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (assignment_id) REFERENCES tutor_assignments(assignment_id) ON DELETE CASCADE,
    INDEX idx_assignment (assignment_id),
    INDEX idx_date (session_date),
    INDEX idx_status (status)
) ENGINE=InnoDB;

-- Student Session Registrations
CREATE TABLE IF NOT EXISTS session_registrations (
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
    INDEX idx_student (student_id),
    INDEX idx_session (session_id)
) ENGINE=InnoDB;

-- Tutor Performance Tracking
CREATE TABLE IF NOT EXISTS tutor_performance (
    performance_id INT AUTO_INCREMENT PRIMARY KEY,
    tutor_id INT NOT NULL,
    assignment_id INT NOT NULL,
    month_year VARCHAR(7) NOT NULL,
    total_sessions INT DEFAULT 0,
    sessions_completed INT DEFAULT 0,
    total_students_helped INT DEFAULT 0,
    average_attendance_rate DECIMAL(5,2),
    average_rating DECIMAL(3,2),
    feedback_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (tutor_id) REFERENCES staff(staff_id) ON DELETE CASCADE,
    FOREIGN KEY (assignment_id) REFERENCES tutor_assignments(assignment_id) ON DELETE CASCADE,
    UNIQUE KEY unique_performance (tutor_id, assignment_id, month_year),
    INDEX idx_tutor (tutor_id),
    INDEX idx_month (month_year)
) ENGINE=InnoDB;

-- ============================================
-- SAMPLE DATA
-- ============================================

-- Add sample modules
INSERT INTO modules (module_code, module_name, department_id, faculty_id, year_level, semester, credits, description) VALUES
('CS101', 'Introduction to Computer Science', 1, 1, 1, 'both', 16, 'Fundamentals of programming and computer science'),
('MATH101', 'Mathematics 1A', 2, 1, 1, '1', 16, 'Calculus and algebra fundamentals'),
('MATH102', 'Mathematics 1B', 2, 1, 1, '2', 16, 'Advanced calculus and linear algebra'),
('CS201', 'Data Structures', 1, 1, 2, 'both', 16, 'Advanced data structures and algorithms'),
('CS301', 'Database Systems', 1, 1, 3, 'both', 16, 'Database design and management');

-- Add coordinator staff member
INSERT INTO staff (staff_number, first_name, last_name, email, password_hash, phone, department_id, role, qualification, specialization) VALUES
('STF006', 'Dr. Themba', 'Nkosi', 'themba.nkosi@wsu.ac.za', '$2y$10$T./d56vL6qf6PHYYdZA7/.8IRTzCC0tkBc0qeh0XXKzSKJzaFP0qC', '0831234567', 1, 'coordinator', 'PhD Computer Science', 'Academic Support Coordination');

-- Add tutor staff members
INSERT INTO staff (staff_number, first_name, last_name, email, password_hash, phone, department_id, role, qualification, specialization) VALUES
('TUT001', 'Sipho', 'Mthembu', 'sipho.mthembu@wsu.ac.za', '$2y$10$T./d56vL6qf6PHYYdZA7/.8IRTzCC0tkBc0qeh0XXKzSKJzaFP0qC', '0834567891', 1, 'tutor', 'MSc Computer Science', 'Programming and Algorithms'),
('TUT002', 'Nomsa', 'Dlamini', 'nomsa.dlamini@wsu.ac.za', '$2y$10$T./d56vL6qf6PHYYdZA7/.8IRTzCC0tkBc0qeh0XXKzSKJzaFP0qC', '0834567892', 2, 'tutor', 'MSc Mathematics', 'Calculus and Linear Algebra');

-- Add PAL staff members
INSERT INTO staff (staff_number, first_name, last_name, email, password_hash, phone, department_id, role, qualification, specialization) VALUES
('PAL001', 'Thandi', 'Khumalo', 'thandi.khumalo@wsu.ac.za', '$2y$10$T./d56vL6qf6PHYYdZA7/.8IRTzCC0tkBc0qeh0XXKzSKJzaFP0qC', '0834567893', 1, 'pal', 'BSc Computer Science (Honours)', 'Peer Learning Facilitation'),
('PAL002', 'Bongani', 'Sithole', 'bongani.sithole@wsu.ac.za', '$2y$10$T./d56vL6qf6PHYYdZA7/.8IRTzCC0tkBc0qeh0XXKzSKJzaFP0qC', '0834567894', 1, 'pal', 'BSc Computer Science (Honours)', 'Study Skills and Support');

-- Add sample at-risk modules
INSERT INTO at_risk_modules (module_id, academic_year, semester, risk_level, failure_rate, total_students, at_risk_students, reason, intervention_needed, identified_by, identified_date) VALUES
(1, '2024', '1', 'high', 45.50, 120, 55, 'High failure rate in programming fundamentals', 'Additional tutoring sessions and peer support', 6, '2024-02-15'),
(2, '2024', '1', 'critical', 52.30, 150, 78, 'Students struggling with calculus concepts', 'Intensive tutoring and supplementary materials', 6, '2024-02-20'),
(4, '2024', '2', 'medium', 35.20, 80, 28, 'Difficulty with algorithm complexity', 'Weekly tutorial sessions', 6, '2024-07-10');

-- Add sample tutor assignments
INSERT INTO tutor_assignments (risk_module_id, tutor_id, tutor_type, assigned_by, assignment_date, start_date, end_date, max_students, session_frequency, location, notes) VALUES
(1, 7, 'tutor', 6, '2024-02-20', '2024-02-25', '2024-06-30', 20, 'Twice weekly', 'Computer Lab A', 'Focus on programming basics and problem-solving'),
(1, 9, 'pal', 6, '2024-02-20', '2024-02-25', '2024-06-30', 15, 'Three times weekly', 'Study Room 101', 'Peer-led study groups'),
(2, 8, 'tutor', 6, '2024-02-25', '2024-03-01', '2024-06-30', 25, 'Three times weekly', 'Math Tutorial Room', 'Calculus fundamentals and practice'),
(3, 7, 'tutor', 6, '2024-07-15', '2024-07-20', '2024-11-30', 15, 'Twice weekly', 'Computer Lab B', 'Data structures and algorithms');

-- Add sample tutor sessions
INSERT INTO tutor_sessions (assignment_id, session_date, start_time, end_time, location, topic, max_capacity, session_type) VALUES
(1, '2024-03-01', '14:00:00', '16:00:00', 'Computer Lab A', 'Introduction to Variables and Data Types', 20, 'group'),
(1, '2024-03-04', '14:00:00', '16:00:00', 'Computer Lab A', 'Control Structures and Loops', 20, 'group'),
(2, '2024-03-02', '15:00:00', '17:00:00', 'Study Room 101', 'Problem-Solving Strategies', 15, 'group'),
(3, '2024-03-05', '10:00:00', '12:00:00', 'Math Tutorial Room', 'Limits and Continuity', 25, 'group');

