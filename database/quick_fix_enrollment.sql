-- ============================================
-- QUICK FIX: Create students and enroll them
-- ============================================

USE wsu_booking;

-- First, let's check what we have
SELECT 'Checking existing data...' as message;

-- Check if students exist
SELECT COUNT(*) as student_count FROM students;

-- Check if student_modules table exists
SELECT COUNT(*) as enrollment_count FROM student_modules;

-- Get module IDs
SET @cs101_module = (SELECT module_id FROM modules WHERE subject_code = 'CS101' LIMIT 1);
SET @math101_module = (SELECT module_id FROM modules WHERE subject_code = 'MATH101' LIMIT 1);
SET @it102_module = (SELECT module_id FROM modules WHERE subject_code = 'IT102' LIMIT 1);

SELECT @cs101_module as cs101_id, @math101_module as math101_id, @it102_module as it102_id;

-- Create some students if they don't exist (using INSERT IGNORE to avoid duplicates)
INSERT IGNORE INTO students (student_id, first_name, last_name, email, password_hash, year_of_study, status) VALUES
('220234501', 'Thabo', 'Molefe', '220234501@student.wsu.ac.za', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 2, 'active'),
('220234502', 'Lerato', 'Dlamini', '220234502@student.wsu.ac.za', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 2, 'active'),
('220234503', 'Sipho', 'Khumalo', '220234503@student.wsu.ac.za', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 2, 'active'),
('220234504', 'Nomsa', 'Mthembu', '220234504@student.wsu.ac.za', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 2, 'active'),
('220234505', 'Bongani', 'Ndlovu', '220234505@student.wsu.ac.za', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 2, 'active'),
('220234506', 'Zanele', 'Sithole', '220234506@student.wsu.ac.za', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 2, 'active'),
('220234507', 'Mandla', 'Zulu', '220234507@student.wsu.ac.za', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 2, 'active'),
('220234508', 'Thandi', 'Nkosi', '220234508@student.wsu.ac.za', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 2, 'active'),
('220234509', 'Sello', 'Mokoena', '220234509@student.wsu.ac.za', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 2, 'active'),
('220234510', 'Palesa', 'Mahlangu', '220234510@student.wsu.ac.za', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 2, 'active'),
('220234511', 'Tshepo', 'Radebe', '220234511@student.wsu.ac.za', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 2, 'active'),
('220234512', 'Mpho', 'Maseko', '220234512@student.wsu.ac.za', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 2, 'active'),
('220234513', 'Kagiso', 'Moyo', '220234513@student.wsu.ac.za', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 2, 'active'),
('220234514', 'Lindiwe', 'Cele', '220234514@student.wsu.ac.za', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 2, 'active'),
('220234515', 'Thabiso', 'Ngcobo', '220234515@student.wsu.ac.za', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 2, 'active');

SELECT 'Students created!' as message;
SELECT COUNT(*) as total_students FROM students;

-- Now enroll them in CS101
DELETE FROM student_modules WHERE module_id = @cs101_module;

INSERT INTO student_modules (student_id, module_id, academic_year, semester, enrollment_date, status) VALUES
('220234501', @cs101_module, 2024, 1, '2024-02-01', 'active'),
('220234502', @cs101_module, 2024, 1, '2024-02-01', 'active'),
('220234503', @cs101_module, 2024, 1, '2024-02-01', 'active'),
('220234504', @cs101_module, 2024, 1, '2024-02-01', 'active'),
('220234505', @cs101_module, 2024, 1, '2024-02-01', 'active'),
('220234506', @cs101_module, 2024, 1, '2024-02-01', 'active'),
('220234507', @cs101_module, 2024, 1, '2024-02-01', 'active'),
('220234508', @cs101_module, 2024, 1, '2024-02-01', 'active'),
('220234509', @cs101_module, 2024, 1, '2024-02-01', 'active'),
('220234510', @cs101_module, 2024, 1, '2024-02-01', 'active'),
('220234511', @cs101_module, 2024, 1, '2024-02-01', 'active'),
('220234512', @cs101_module, 2024, 1, '2024-02-01', 'active'),
('220234513', @cs101_module, 2024, 1, '2024-02-01', 'active'),
('220234514', @cs101_module, 2024, 1, '2024-02-01', 'active'),
('220234515', @cs101_module, 2024, 1, '2024-02-01', 'active');

SELECT 'Enrollments created!' as message;

-- Verify the enrollments
SELECT 
    m.subject_code,
    m.subject_name,
    COUNT(sm.enrollment_id) as enrolled_students
FROM student_modules sm
INNER JOIN modules m ON sm.module_id = m.module_id
WHERE sm.status = 'active'
GROUP BY m.module_id;

-- Show the students enrolled in CS101
SELECT 
    s.student_id,
    s.first_name,
    s.last_name,
    sm.academic_year,
    sm.semester
FROM student_modules sm
INNER JOIN students s ON sm.student_id = s.student_id
INNER JOIN modules m ON sm.module_id = m.module_id
WHERE m.subject_code = 'CS101'
AND sm.status = 'active';

SELECT '✓ DONE! 15 students enrolled in CS101' as message;
