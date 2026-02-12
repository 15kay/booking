-- ============================================
-- SAMPLE MODULES DATA FOR WSU
-- Walter Sisulu University - All Campuses and Faculties
-- ============================================

USE wsu_booking;

-- First, make sure the modules table is altered
-- Run alter_modules_table.sql or alter_modules_simple.sql first

-- Clean up existing data
DELETE FROM session_registrations;
DELETE FROM tutor_sessions;
DELETE FROM tutor_performance;
DELETE FROM tutor_assignments;
DELETE FROM at_risk_modules;
DELETE FROM modules;
DELETE FROM staff WHERE role IN ('coordinator', 'tutor', 'pal');

-- Insert Sample Modules with Pass Rates
-- Format: (academic_year, campus, faculty, school, subject_area, period_of_study, 
--          academic_block_code, subject_code, subject_name, subjects_passed, headcount, subject_pass_rate)

-- MTHATHA CAMPUS - Faculty of Law, Humanities and Social Sciences
INSERT INTO modules (academic_year, campus, faculty, school, subject_area, period_of_study, academic_block_code, subject_code, subject_name, subjects_passed, headcount, subject_pass_rate, module_code, module_name, status) VALUES
('2024', 'Mthatha', 'Faculty of Law, Humanities and Social Sciences', 'School of Law', 'LAW', 'Semester 1', 'BLK1', 'LAW101', 'Introduction to Law', 65, 90, 0.7222, 'LAW101', 'Introduction to Law', 'active'),
('2024', 'Mthatha', 'Faculty of Law, Humanities and Social Sciences', 'School of Law', 'LAW', 'Semester 2', 'BLK2', 'LAW201', 'Constitutional Law', 50, 85, 0.5882, 'LAW201', 'Constitutional Law', 'active'),
('2024', 'Mthatha', 'Faculty of Law, Humanities and Social Sciences', 'School of Social Sciences', 'SOC', 'Semester 1', 'BLK1', 'SOC101', 'Introduction to Sociology', 80, 105, 0.7619, 'SOC101', 'Introduction to Sociology', 'active'),
('2024', 'Mthatha', 'Faculty of Law, Humanities and Social Sciences', 'School of Humanities', 'HUM', 'Semester 1', 'BLK1', 'HUM101', 'English Literature', 55, 95, 0.5789, 'HUM101', 'English Literature', 'active'),

-- MTHATHA CAMPUS - Faculty of Medicine and Health Sciences
('2024', 'Mthatha', 'Faculty of Medicine and Health Sciences', 'School of Medicine', 'MED', 'Semester 1', 'BLK1', 'MED101', 'Human Anatomy', 38, 75, 0.5067, 'MED101', 'Human Anatomy', 'active'),
('2024', 'Mthatha', 'Faculty of Medicine and Health Sciences', 'School of Nursing', 'NUR', 'Semester 1', 'BLK1', 'NUR101', 'Fundamentals of Nursing', 70, 90, 0.7778, 'NUR101', 'Fundamentals of Nursing', 'active'),
('2024', 'Mthatha', 'Faculty of Medicine and Health Sciences', 'School of Medicine', 'MED', 'Semester 2', 'BLK2', 'MED201', 'Physiology', 42, 70, 0.6000, 'MED201', 'Physiology', 'active'),
('2024', 'Mthatha', 'Faculty of Medicine and Health Sciences', 'School of Public Health', 'PUB', 'Semester 1', 'BLK1', 'PUB101', 'Public Health Principles', 65, 80, 0.8125, 'PUB101', 'Public Health Principles', 'active'),

-- EAST LONDON CAMPUS - Faculty of Natural Sciences
('2024', 'East London', 'Faculty of Natural Sciences', 'School of Mathematics and Computer Science', 'CS', 'Semester 1', 'BLK1', 'CS101', 'Computer Science Fundamentals', 40, 100, 0.4000, 'CS101', 'Computer Science Fundamentals', 'active'),
('2024', 'East London', 'Faculty of Natural Sciences', 'School of Mathematics and Computer Science', 'MATH', 'Semester 1', 'BLK1', 'MATH101', 'Calculus I', 48, 130, 0.3692, 'MATH101', 'Calculus I', 'active'),
('2024', 'East London', 'Faculty of Natural Sciences', 'School of Mathematics and Computer Science', 'MATH', 'Semester 2', 'BLK2', 'MATH201', 'Linear Algebra', 70, 120, 0.5833, 'MATH201', 'Linear Algebra', 'active'),
('2024', 'East London', 'Faculty of Natural Sciences', 'School of Chemistry and Physics', 'CHEM', 'Semester 1', 'BLK1', 'CHEM101', 'General Chemistry', 85, 110, 0.7727, 'CHEM101', 'General Chemistry', 'active'),
('2024', 'East London', 'Faculty of Natural Sciences', 'School of Chemistry and Physics', 'PHYS', 'Semester 1', 'BLK1', 'PHYS101', 'Physics I', 55, 95, 0.5789, 'PHYS101', 'Physics I', 'active'),

-- EAST LONDON CAMPUS - Faculty of Engineering, Built Environment and Information Technology
('2024', 'East London', 'Faculty of Engineering, Built Environment and Information Technology', 'School of Information Technology', 'IT', 'Semester 1', 'BLK1', 'IT101', 'Introduction to Programming', 45, 120, 0.3750, 'IT101', 'Introduction to Programming', 'active'),
('2024', 'East London', 'Faculty of Engineering, Built Environment and Information Technology', 'School of Information Technology', 'IT', 'Semester 1', 'BLK1', 'IT102', 'Database Systems', 65, 110, 0.5909, 'IT102', 'Database Systems', 'active'),
('2024', 'East London', 'Faculty of Engineering, Built Environment and Information Technology', 'School of Information Technology', 'IT', 'Semester 2', 'BLK2', 'IT201', 'Web Development', 80, 100, 0.8000, 'IT201', 'Web Development', 'active'),
('2024', 'East London', 'Faculty of Engineering, Built Environment and Information Technology', 'School of Engineering', 'ENG', 'Semester 1', 'BLK1', 'ENG101', 'Engineering Mathematics I', 35, 95, 0.3684, 'ENG101', 'Engineering Mathematics I', 'active'),
('2024', 'East London', 'Faculty of Engineering, Built Environment and Information Technology', 'School of Engineering', 'ENG', 'Semester 2', 'BLK2', 'ENG201', 'Thermodynamics', 55, 85, 0.6471, 'ENG201', 'Thermodynamics', 'active'),

-- BUTTERWORTH CAMPUS - Faculty of Education
('2024', 'Butterworth', 'Faculty of Education', 'School of General and Further Education and Training', 'EDU', 'Semester 1', 'BLK1', 'EDU101', 'Educational Psychology', 90, 115, 0.7826, 'EDU101', 'Educational Psychology', 'active'),
('2024', 'Butterworth', 'Faculty of Education', 'School of General and Further Education and Training', 'EDU', 'Semester 2', 'BLK2', 'EDU201', 'Curriculum Development', 75, 100, 0.7500, 'EDU201', 'Curriculum Development', 'active'),
('2024', 'Butterworth', 'Faculty of Education', 'School of Postgraduate Studies', 'EDU', 'Semester 1', 'BLK1', 'EDU301', 'Research Methodology', 42, 80, 0.5250, 'EDU301', 'Research Methodology', 'active'),
('2024', 'Butterworth', 'Faculty of Education', 'School of Early Childhood Education', 'EDU', 'Semester 1', 'BLK1', 'EDU102', 'Child Development', 68, 85, 0.8000, 'EDU102', 'Child Development', 'active'),

-- BUTTERWORTH CAMPUS - Faculty of Management and Public Administration Sciences
('2024', 'Butterworth', 'Faculty of Management and Public Administration Sciences', 'School of Business Management', 'BUS', 'Semester 1', 'BLK1', 'BUS101', 'Business Management Principles', 55, 140, 0.3929, 'BUS101', 'Business Management Principles', 'active'),
('2024', 'Butterworth', 'Faculty of Management and Public Administration Sciences', 'School of Business Management', 'BUS', 'Semester 2', 'BLK2', 'BUS201', 'Marketing Management', 85, 120, 0.7083, 'BUS201', 'Marketing Management', 'active'),
('2024', 'Butterworth', 'Faculty of Management and Public Administration Sciences', 'School of Public Administration', 'PAD', 'Semester 1', 'BLK1', 'PAD101', 'Public Administration', 60, 100, 0.6000, 'PAD101', 'Public Administration', 'active'),
('2024', 'Butterworth', 'Faculty of Management and Public Administration Sciences', 'School of Public Administration', 'PAD', 'Semester 2', 'BLK2', 'PAD201', 'Public Policy', 52, 90, 0.5778, 'PAD201', 'Public Policy', 'active'),

-- QUEENSTOWN CAMPUS - Faculty Of Economic And Financial Sciences
('2024', 'Queenstown', 'Faculty Of Economic And Financial Sciences', 'School of Economics', 'ECON', 'Semester 1', 'BLK1', 'ECON101', 'Microeconomics', 45, 115, 0.3913, 'ECON101', 'Microeconomics', 'active'),
('2024', 'Queenstown', 'Faculty Of Economic And Financial Sciences', 'School of Economics', 'ECON', 'Semester 2', 'BLK2', 'ECON201', 'Macroeconomics', 65, 110, 0.5909, 'ECON201', 'Macroeconomics', 'active'),
('2024', 'Queenstown', 'Faculty Of Economic And Financial Sciences', 'School of Accounting', 'ACC', 'Semester 1', 'BLK1', 'ACC101', 'Financial Accounting I', 50, 130, 0.3846, 'ACC101', 'Financial Accounting I', 'active'),
('2024', 'Queenstown', 'Faculty Of Economic And Financial Sciences', 'School of Accounting', 'ACC', 'Semester 2', 'BLK2', 'ACC201', 'Management Accounting', 75, 120, 0.6250, 'ACC201', 'Management Accounting', 'active'),
('2024', 'Queenstown', 'Faculty Of Economic And Financial Sciences', 'School of Finance', 'FIN', 'Semester 1', 'BLK1', 'FIN101', 'Introduction to Finance', 58, 105, 0.5524, 'FIN101', 'Introduction to Finance', 'active');

-- Update risk_category for all modules (if not using generated column)
UPDATE modules 
SET risk_category = CASE 
    WHEN subject_pass_rate < 0.40 THEN 'High Risk'
    WHEN subject_pass_rate < 0.60 THEN 'Moderate Risk'
    WHEN subject_pass_rate < 0.75 THEN 'Low Risk'
    ELSE 'Very Low Risk'
END
WHERE subject_pass_rate IS NOT NULL;

-- ============================================
-- CREATE COORDINATORS - ONE PER CAMPUS
-- ============================================

-- Delete existing coordinators first
DELETE FROM staff WHERE role = 'coordinator';

-- Add coordinators (ONE per campus)
INSERT INTO staff (staff_number, first_name, last_name, email, password_hash, phone, department_id, role, qualification, specialization, status) VALUES
-- Mthatha Campus Coordinator
('COORD001', 'Dr. Themba', 'Nkosi', 'themba.nkosi@wsu.ac.za', '$2y$10$T./d56vL6qf6PHYYdZA7/.8IRTzCC0tkBc0qeh0XXKzSKJzaFP0qC', '0831234567', 1, 'coordinator', 'PhD Education', 'Academic Support Coordinator - Mthatha Campus', 'active'),

-- East London Campus Coordinator
('COORD002', 'Dr. Nomsa', 'Dlamini', 'nomsa.dlamini@wsu.ac.za', '$2y$10$T./d56vL6qf6PHYYdZA7/.8IRTzCC0tkBc0qeh0XXKzSKJzaFP0qC', '0831234568', 1, 'coordinator', 'PhD Science', 'Academic Support Coordinator - East London Campus', 'active'),

-- Butterworth Campus Coordinator
('COORD003', 'Dr. Sipho', 'Mthembu', 'sipho.mthembu@wsu.ac.za', '$2y$10$T./d56vL6qf6PHYYdZA7/.8IRTzCC0tkBc0qeh0XXKzSKJzaFP0qC', '0831234569', 1, 'coordinator', 'PhD Education', 'Academic Support Coordinator - Butterworth Campus', 'active'),

-- Queenstown Campus Coordinator
('COORD004', 'Dr. Thandi', 'Khumalo', 'thandi.khumalo@wsu.ac.za', '$2y$10$T./d56vL6qf6PHYYdZA7/.8IRTzCC0tkBc0qeh0XXKzSKJzaFP0qC', '0831234570', 1, 'coordinator', 'PhD Law', 'Academic Support Coordinator - Queenstown Campus', 'active');

-- ============================================
-- COORDINATOR LOGIN CREDENTIALS
-- ============================================
-- All coordinators use password: password123
-- 
-- COORD001 - Dr. Themba Nkosi (Mthatha Campus)
-- COORD002 - Dr. Nomsa Dlamini (East London Campus)
-- COORD003 - Dr. Sipho Mthembu (Butterworth Campus)
-- COORD004 - Dr. Thandi Khumalo (Queenstown Campus)

-- ============================================
-- CREATE TUTORS AND PALs
-- ============================================

-- Delete existing tutors/PALs handled above

-- Add sample tutors
INSERT INTO staff (staff_number, first_name, last_name, email, password_hash, phone, department_id, role, qualification, specialization, status) VALUES
('TUT001', 'Sipho', 'Mthembu', 'sipho.mthembu@wsu.ac.za', '$2y$10$T./d56vL6qf6PHYYdZA7/.8IRTzCC0tkBc0qeh0XXKzSKJzaFP0qC', '0834567891', 1, 'tutor', 'MSc Computer Science', 'Programming and Algorithms', 'active'),
('TUT002', 'Nomsa', 'Dlamini', 'nomsa.dlamini.tutor@wsu.ac.za', '$2y$10$T./d56vL6qf6PHYYdZA7/.8IRTzCC0tkBc0qeh0XXKzSKJzaFP0qC', '0834567892', 2, 'tutor', 'MSc Mathematics', 'Calculus and Linear Algebra', 'active'),
('TUT003', 'Mandla', 'Zulu', 'mandla.zulu@wsu.ac.za', '$2y$10$T./d56vL6qf6PHYYdZA7/.8IRTzCC0tkBc0qeh0XXKzSKJzaFP0qC', '0834567893', 1, 'tutor', 'MSc Engineering', 'Engineering Mathematics', 'active'),
('TUT004', 'Zanele', 'Ndlovu', 'zanele.ndlovu.tutor@wsu.ac.za', '$2y$10$T./d56vL6qf6PHYYdZA7/.8IRTzCC0tkBc0qeh0XXKzSKJzaFP0qC', '0834567894', 1, 'tutor', 'MSc Economics', 'Microeconomics and Macroeconomics', 'active');

-- Add sample PALs (Peer Assisted Learning)
INSERT INTO staff (staff_number, first_name, last_name, email, password_hash, phone, department_id, role, qualification, specialization, status) VALUES
('PAL001', 'Thandi', 'Khumalo', 'thandi.khumalo.pal@wsu.ac.za', '$2y$10$T./d56vL6qf6PHYYdZA7/.8IRTzCC0tkBc0qeh0XXKzSKJzaFP0qC', '0834567895', 1, 'pal', 'BSc Computer Science (Honours)', 'Peer Learning Facilitation', 'active'),
('PAL002', 'Bongani', 'Sithole', 'bongani.sithole.pal@wsu.ac.za', '$2y$10$T./d56vL6qf6PHYYdZA7/.8IRTzCC0tkBc0qeh0XXKzSKJzaFP0qC', '0834567896', 1, 'pal', 'BSc Computer Science (Honours)', 'Study Skills and Support', 'active'),
('PAL003', 'Lindiwe', 'Moyo', 'lindiwe.moyo@wsu.ac.za', '$2y$10$T./d56vL6qf6PHYYdZA7/.8IRTzCC0tkBc0qeh0XXKzSKJzaFP0qC', '0834567897', 1, 'pal', 'BA Education (Honours)', 'Group Study Facilitation', 'active'),
('PAL004', 'Thabo', 'Nkosi', 'thabo.nkosi@wsu.ac.za', '$2y$10$T./d56vL6qf6PHYYdZA7/.8IRTzCC0tkBc0qeh0XXKzSKJzaFP0qC', '0834567898', 1, 'pal', 'BCom Accounting (Honours)', 'Accounting Peer Support', 'active');

SELECT 'Sample modules and coordinators created successfully!' as message;
SELECT 
    COUNT(*) as total_modules,
    SUM(CASE WHEN risk_category = 'High Risk' THEN 1 ELSE 0 END) as high_risk,
    SUM(CASE WHEN risk_category = 'Moderate Risk' THEN 1 ELSE 0 END) as moderate_risk,
    SUM(CASE WHEN risk_category = 'Low Risk' THEN 1 ELSE 0 END) as low_risk,
    SUM(CASE WHEN risk_category = 'Very Low Risk' THEN 1 ELSE 0 END) as very_low_risk
FROM modules;
