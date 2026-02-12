-- ============================================
-- INSERT TUTORS AND PALs
-- Tutors: Undergraduate students with good academic performance
-- PALs: Senior students (PAL Leaders) for historically difficult subjects
-- ============================================

USE wsu_booking;

-- Check if tutors already exist
SELECT 'Checking existing tutors...' as message;
SELECT COUNT(*) as existing_tutors FROM staff WHERE role IN ('tutor', 'pal');

-- Delete existing tutors/PALs to avoid duplicates
DELETE FROM staff WHERE role IN ('tutor', 'pal');

-- Add sample TUTORS (Undergraduate students - various years)
INSERT INTO staff (staff_number, student_number, first_name, last_name, email, password_hash, phone, department_id, role, qualification, gpa, academic_year_level, specialization, modules_tutored, status, application_date, approval_date) VALUES
('TUT001', '220123456', 'Sipho', 'Mthembu', 'sipho.mthembu@wsu.ac.za', '$2y$10$T./d56vL6qf6PHYYdZA7/.8IRTzCC0tkBc0qeh0XXKzSKJzaFP0qC', '0834567891', 1, 'tutor', 'BSc Computer Science', 3.65, '3rd Year', 'Programming and Web Development', 'CS101, IT101, IT102', 'active', '2024-01-15', '2024-01-20'),
('TUT002', '220123457', 'Nomsa', 'Dlamini', 'nomsa.dlamini.tutor@wsu.ac.za', '$2y$10$T./d56vL6qf6PHYYdZA7/.8IRTzCC0tkBc0qeh0XXKzSKJzaFP0qC', '0834567892', 2, 'tutor', 'BSc Mathematics', 3.72, '3rd Year', 'Calculus and Statistics', 'MATH101, MATH201', 'active', '2024-01-16', '2024-01-21'),
('TUT003', '220123458', 'Mandla', 'Zulu', 'mandla.zulu@wsu.ac.za', '$2y$10$T./d56vL6qf6PHYYdZA7/.8IRTzCC0tkBc0qeh0XXKzSKJzaFP0qC', '0834567893', 1, 'tutor', 'BEng Engineering', 3.58, '2nd Year', 'Engineering Mathematics', 'ENG101, MATH101', 'active', '2024-01-17', '2024-01-22'),
('TUT004', '220123459', 'Zanele', 'Ndlovu', 'zanele.ndlovu.tutor@wsu.ac.za', '$2y$10$T./d56vL6qf6PHYYdZA7/.8IRTzCC0tkBc0qeh0XXKzSKJzaFP0qC', '0834567894', 1, 'tutor', 'BCom Economics', 3.68, '3rd Year', 'Microeconomics and Statistics', 'ECON101, ECON201', 'active', '2024-01-18', '2024-01-23'),
('TUT005', '220123460', 'Thabo', 'Mokoena', 'thabo.mokoena@wsu.ac.za', '$2y$10$T./d56vL6qf6PHYYdZA7/.8IRTzCC0tkBc0qeh0XXKzSKJzaFP0qC', '0834567899', 1, 'tutor', 'BSc Physics', 3.75, '3rd Year', 'Physics and Chemistry', 'PHYS101, CHEM101', 'active', '2024-01-19', '2024-01-24'),
('TUT006', '220123461', 'Lerato', 'Molefe', 'lerato.molefe@wsu.ac.za', '$2y$10$T./d56vL6qf6PHYYdZA7/.8IRTzCC0tkBc0qeh0XXKzSKJzaFP0qC', '0834567800', 1, 'tutor', 'BSc Chemistry', 3.62, '2nd Year', 'General Chemistry', 'CHEM101', 'active', '2024-01-20', '2024-01-25'),
('TUT007', '220123462', 'Kagiso', 'Mabaso', 'kagiso.mabaso@wsu.ac.za', '$2y$10$T./d56vL6qf6PHYYdZA7/.8IRTzCC0tkBc0qeh0XXKzSKJzaFP0qC', '0834567803', 1, 'tutor', 'BCom Accounting', 3.70, '3rd Year', 'Financial and Management Accounting', 'ACC101, ACC201', 'active', '2024-01-21', '2024-01-26'),
('TUT008', '220123463', 'Palesa', 'Nkomo', 'palesa.nkomo@wsu.ac.za', '$2y$10$T./d56vL6qf6PHYYdZA7/.8IRTzCC0tkBc0qeh0XXKzSKJzaFP0qC', '0834567804', 1, 'tutor', 'BA Education', 3.55, '2nd Year', 'Educational Psychology', 'EDU101, EDU201', 'active', '2024-01-22', '2024-01-27');

-- Add sample PALs (PAL Leaders - Senior students for historically difficult subjects)
-- These are 3rd/4th year students who attend classes and lead PAL sessions
INSERT INTO staff (staff_number, student_number, first_name, last_name, email, password_hash, phone, department_id, role, qualification, gpa, academic_year_level, specialization, modules_tutored, status, application_date, approval_date) VALUES
('PAL001', '221234567', 'Thandi', 'Khumalo', 'thandi.khumalo.pal@wsu.ac.za', '$2y$10$T./d56vL6qf6PHYYdZA7/.8IRTzCC0tkBc0qeh0XXKzSKJzaFP0qC', '0834567895', 1, 'pal', 'BSc Computer Science', 3.75, '4th Year', 'PAL Leader - Programming (Low Pass Rate)', 'CS101, IT101', 'active', '2024-02-01', '2024-02-05'),
('PAL002', '221234568', 'Bongani', 'Sithole', 'bongani.sithole.pal@wsu.ac.za', '$2y$10$T./d56vL6qf6PHYYdZA7/.8IRTzCC0tkBc0qeh0XXKzSKJzaFP0qC', '0834567896', 1, 'pal', 'BSc Computer Science', 3.68, '3rd Year', 'PAL Leader - Database Systems (Low Pass Rate)', 'IT102', 'active', '2024-02-02', '2024-02-06'),
('PAL003', '221234569', 'Lindiwe', 'Moyo', 'lindiwe.moyo@wsu.ac.za', '$2y$10$T./d56vL6qf6PHYYdZA7/.8IRTzCC0tkBc0qeh0XXKzSKJzaFP0qC', '0834567897', 1, 'pal', 'BSc Mathematics', 3.82, '4th Year', 'PAL Leader - Calculus (Low Pass Rate)', 'MATH101', 'active', '2024-02-03', '2024-02-07'),
('PAL004', '221234570', 'Thabo', 'Nkosi', 'thabo.nkosi@wsu.ac.za', '$2y$10$T./d56vL6qf6PHYYdZA7/.8IRTzCC0tkBc0qeh0XXKzSKJzaFP0qC', '0834567898', 1, 'pal', 'BCom Accounting', 3.80, '4th Year', 'PAL Leader - Financial Accounting (Low Pass Rate)', 'ACC101', 'active', '2024-02-04', '2024-02-08'),
('PAL005', '221234571', 'Nokuthula', 'Dube', 'nokuthula.dube@wsu.ac.za', '$2y$10$T./d56vL6qf6PHYYdZA7/.8IRTzCC0tkBc0qeh0XXKzSKJzaFP0qC', '0834567801', 1, 'pal', 'BEng Engineering', 3.85, '4th Year', 'PAL Leader - Engineering Math (Low Pass Rate)', 'ENG101', 'active', '2024-02-05', '2024-02-09'),
('PAL006', '221234572', 'Mpho', 'Radebe', 'mpho.radebe@wsu.ac.za', '$2y$10$T./d56vL6qf6PHYYdZA7/.8IRTzCC0tkBc0qeh0XXKzSKJzaFP0qC', '0834567802', 1, 'pal', 'BCom Economics', 3.77, '3rd Year', 'PAL Leader - Microeconomics (Low Pass Rate)', 'ECON101', 'active', '2024-02-06', '2024-02-10'),
('PAL007', '221234573', 'Sello', 'Mahlangu', 'sello.mahlangu@wsu.ac.za', '$2y$10$T./d56vL6qf6PHYYdZA7/.8IRTzCC0tkBc0qeh0XXKzSKJzaFP0qC', '0834567805', 1, 'pal', 'BSc Physics', 3.73, '4th Year', 'PAL Leader - Physics I (Low Pass Rate)', 'PHYS101', 'active', '2024-02-07', '2024-02-11'),
('PAL008', '221234574', 'Refilwe', 'Tshabalala', 'refilwe.tshabalala@wsu.ac.za', '$2y$10$T./d56vL6qf6PHYYdZA7/.8IRTzCC0tkBc0qeh0XXKzSKJzaFP0qC', '0834567806', 1, 'pal', 'BCom Business Management', 3.65, '3rd Year', 'PAL Leader - Business Principles (Low Pass Rate)', 'BUS101', 'active', '2024-02-08', '2024-02-12');

-- Verify insertion
SELECT 'Tutors and PALs inserted successfully!' as message;
SELECT 
    role,
    COUNT(*) as count
FROM staff 
WHERE role IN ('tutor', 'pal')
GROUP BY role;

-- Show all tutors/PALs
SELECT 
    staff_number,
    student_number,
    CONCAT(first_name, ' ', last_name) as name,
    role,
    gpa,
    academic_year_level,
    qualification,
    specialization,
    status
FROM staff 
WHERE role IN ('tutor', 'pal')
ORDER BY role, last_name;
