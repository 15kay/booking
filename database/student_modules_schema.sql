-- ============================================
-- STUDENT MODULE ENROLLMENT
-- Links students to modules they are enrolled in
-- ============================================

USE wsu_booking;

-- Create student_modules table (enrollment)
CREATE TABLE IF NOT EXISTS student_modules (
    enrollment_id INT AUTO_INCREMENT PRIMARY KEY,
    student_id VARCHAR(20) NOT NULL,
    module_id INT NOT NULL,
    academic_year INT NOT NULL,
    semester TINYINT NOT NULL,
    enrollment_date DATE NOT NULL,
    status ENUM('active', 'dropped', 'completed', 'failed') DEFAULT 'active',
    final_mark DECIMAL(5,2) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE,
    FOREIGN KEY (module_id) REFERENCES modules(module_id) ON DELETE CASCADE,
    UNIQUE KEY unique_enrollment (student_id, module_id, academic_year, semester),
    INDEX idx_student (student_id),
    INDEX idx_module (module_id),
    INDEX idx_status (status)
) ENGINE=InnoDB;

-- Get module IDs
SET @cs101_module = (SELECT module_id FROM modules WHERE subject_code = 'CS101' LIMIT 1);
SET @math101_module = (SELECT module_id FROM modules WHERE subject_code = 'MATH101' LIMIT 1);
SET @it102_module = (SELECT module_id FROM modules WHERE subject_code = 'IT102' LIMIT 1);

-- Enroll sample students in CS101 (45 students as per module headcount)
-- Using existing students from insert_sample_students.sql
INSERT INTO student_modules (student_id, module_id, academic_year, semester, enrollment_date, status) VALUES
-- CS101 enrollments (45 students)
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
('220234515', @cs101_module, 2024, 1, '2024-02-01', 'active'),
('220234516', @cs101_module, 2024, 1, '2024-02-01', 'active'),
('220234517', @cs101_module, 2024, 1, '2024-02-01', 'active'),
('220234518', @cs101_module, 2024, 1, '2024-02-01', 'active'),
('220234519', @cs101_module, 2024, 1, '2024-02-01', 'active'),
('220234520', @cs101_module, 2024, 1, '2024-02-01', 'active'),
('220234521', @cs101_module, 2024, 1, '2024-02-01', 'active'),
('220234522', @cs101_module, 2024, 1, '2024-02-01', 'active'),
('220234523', @cs101_module, 2024, 1, '2024-02-01', 'active'),
('220234524', @cs101_module, 2024, 1, '2024-02-01', 'active'),
('220234525', @cs101_module, 2024, 1, '2024-02-01', 'active'),
('220234526', @cs101_module, 2024, 1, '2024-02-01', 'active'),
('220234527', @cs101_module, 2024, 1, '2024-02-01', 'active'),
('220234528', @cs101_module, 2024, 1, '2024-02-01', 'active'),
('220234529', @cs101_module, 2024, 1, '2024-02-01', 'active'),
('220234530', @cs101_module, 2024, 1, '2024-02-01', 'active'),
('220234531', @cs101_module, 2024, 1, '2024-02-01', 'active'),
('220234532', @cs101_module, 2024, 1, '2024-02-01', 'active'),
('220234533', @cs101_module, 2024, 1, '2024-02-01', 'active'),
('220234534', @cs101_module, 2024, 1, '2024-02-01', 'active'),
('220234535', @cs101_module, 2024, 1, '2024-02-01', 'active'),
('220234536', @cs101_module, 2024, 1, '2024-02-01', 'active'),
('220234537', @cs101_module, 2024, 1, '2024-02-01', 'active'),
('220234538', @cs101_module, 2024, 1, '2024-02-01', 'active'),
('220234539', @cs101_module, 2024, 1, '2024-02-01', 'active'),
('220234540', @cs101_module, 2024, 1, '2024-02-01', 'active'),
('220234541', @cs101_module, 2024, 1, '2024-02-01', 'active'),
('220234542', @cs101_module, 2024, 1, '2024-02-01', 'active'),
('220234543', @cs101_module, 2024, 1, '2024-02-01', 'active'),
('220234544', @cs101_module, 2024, 1, '2024-02-01', 'active'),
('220234545', @cs101_module, 2024, 1, '2024-02-01', 'active'),

-- MATH101 enrollments (55 students - first 30 overlap with CS101)
('220234501', @math101_module, 2024, 1, '2024-02-01', 'active'),
('220234502', @math101_module, 2024, 1, '2024-02-01', 'active'),
('220234503', @math101_module, 2024, 1, '2024-02-01', 'active'),
('220234504', @math101_module, 2024, 1, '2024-02-01', 'active'),
('220234505', @math101_module, 2024, 1, '2024-02-01', 'active'),
('220234506', @math101_module, 2024, 1, '2024-02-01', 'active'),
('220234507', @math101_module, 2024, 1, '2024-02-01', 'active'),
('220234508', @math101_module, 2024, 1, '2024-02-01', 'active'),
('220234509', @math101_module, 2024, 1, '2024-02-01', 'active'),
('220234510', @math101_module, 2024, 1, '2024-02-01', 'active'),
('220234511', @math101_module, 2024, 1, '2024-02-01', 'active'),
('220234512', @math101_module, 2024, 1, '2024-02-01', 'active'),
('220234513', @math101_module, 2024, 1, '2024-02-01', 'active'),
('220234514', @math101_module, 2024, 1, '2024-02-01', 'active'),
('220234515', @math101_module, 2024, 1, '2024-02-01', 'active'),
('220234516', @math101_module, 2024, 1, '2024-02-01', 'active'),
('220234517', @math101_module, 2024, 1, '2024-02-01', 'active'),
('220234518', @math101_module, 2024, 1, '2024-02-01', 'active'),
('220234519', @math101_module, 2024, 1, '2024-02-01', 'active'),
('220234520', @math101_module, 2024, 1, '2024-02-01', 'active'),
('220234521', @math101_module, 2024, 1, '2024-02-01', 'active'),
('220234522', @math101_module, 2024, 1, '2024-02-01', 'active'),
('220234523', @math101_module, 2024, 1, '2024-02-01', 'active'),
('220234524', @math101_module, 2024, 1, '2024-02-01', 'active'),
('220234525', @math101_module, 2024, 1, '2024-02-01', 'active'),
('220234526', @math101_module, 2024, 1, '2024-02-01', 'active'),
('220234527', @math101_module, 2024, 1, '2024-02-01', 'active'),
('220234528', @math101_module, 2024, 1, '2024-02-01', 'active'),
('220234529', @math101_module, 2024, 1, '2024-02-01', 'active'),
('220234530', @math101_module, 2024, 1, '2024-02-01', 'active'),
('220234546', @math101_module, 2024, 1, '2024-02-01', 'active'),
('220234547', @math101_module, 2024, 1, '2024-02-01', 'active'),
('220234548', @math101_module, 2024, 1, '2024-02-01', 'active'),
('220234549', @math101_module, 2024, 1, '2024-02-01', 'active'),
('220234550', @math101_module, 2024, 1, '2024-02-01', 'active'),
('220234551', @math101_module, 2024, 1, '2024-02-01', 'active'),
('220234552', @math101_module, 2024, 1, '2024-02-01', 'active'),
('220234553', @math101_module, 2024, 1, '2024-02-01', 'active'),
('220234554', @math101_module, 2024, 1, '2024-02-01', 'active'),
('220234555', @math101_module, 2024, 1, '2024-02-01', 'active'),
('220234556', @math101_module, 2024, 1, '2024-02-01', 'active'),
('220234557', @math101_module, 2024, 1, '2024-02-01', 'active'),
('220234558', @math101_module, 2024, 1, '2024-02-01', 'active'),
('220234559', @math101_module, 2024, 1, '2024-02-01', 'active'),
('220234560', @math101_module, 2024, 1, '2024-02-01', 'active'),
('220234561', @math101_module, 2024, 1, '2024-02-01', 'active'),
('220234562', @math101_module, 2024, 1, '2024-02-01', 'active'),
('220234563', @math101_module, 2024, 1, '2024-02-01', 'active'),
('220234564', @math101_module, 2024, 1, '2024-02-01', 'active'),
('220234565', @math101_module, 2024, 1, '2024-02-01', 'active'),
('220234566', @math101_module, 2024, 1, '2024-02-01', 'active'),
('220234567', @math101_module, 2024, 1, '2024-02-01', 'active'),
('220234568', @math101_module, 2024, 1, '2024-02-01', 'active'),
('220234569', @math101_module, 2024, 1, '2024-02-01', 'active'),
('220234570', @math101_module, 2024, 1, '2024-02-01', 'active'),

-- IT102 enrollments (38 students - some overlap with CS101)
('220234501', @it102_module, 2024, 2, '2024-07-01', 'active'),
('220234502', @it102_module, 2024, 2, '2024-07-01', 'active'),
('220234503', @it102_module, 2024, 2, '2024-07-01', 'active'),
('220234504', @it102_module, 2024, 2, '2024-07-01', 'active'),
('220234505', @it102_module, 2024, 2, '2024-07-01', 'active'),
('220234506', @it102_module, 2024, 2, '2024-07-01', 'active'),
('220234507', @it102_module, 2024, 2, '2024-07-01', 'active'),
('220234508', @it102_module, 2024, 2, '2024-07-01', 'active'),
('220234509', @it102_module, 2024, 2, '2024-07-01', 'active'),
('220234510', @it102_module, 2024, 2, '2024-07-01', 'active'),
('220234511', @it102_module, 2024, 2, '2024-07-01', 'active'),
('220234512', @it102_module, 2024, 2, '2024-07-01', 'active'),
('220234513', @it102_module, 2024, 2, '2024-07-01', 'active'),
('220234514', @it102_module, 2024, 2, '2024-07-01', 'active'),
('220234515', @it102_module, 2024, 2, '2024-07-01', 'active'),
('220234516', @it102_module, 2024, 2, '2024-07-01', 'active'),
('220234517', @it102_module, 2024, 2, '2024-07-01', 'active'),
('220234518', @it102_module, 2024, 2, '2024-07-01', 'active'),
('220234519', @it102_module, 2024, 2, '2024-07-01', 'active'),
('220234520', @it102_module, 2024, 2, '2024-07-01', 'active'),
('220234521', @it102_module, 2024, 2, '2024-07-01', 'active'),
('220234522', @it102_module, 2024, 2, '2024-07-01', 'active'),
('220234523', @it102_module, 2024, 2, '2024-07-01', 'active'),
('220234524', @it102_module, 2024, 2, '2024-07-01', 'active'),
('220234525', @it102_module, 2024, 2, '2024-07-01', 'active'),
('220234526', @it102_module, 2024, 2, '2024-07-01', 'active'),
('220234527', @it102_module, 2024, 2, '2024-07-01', 'active'),
('220234528', @it102_module, 2024, 2, '2024-07-01', 'active'),
('220234529', @it102_module, 2024, 2, '2024-07-01', 'active'),
('220234530', @it102_module, 2024, 2, '2024-07-01', 'active'),
('220234531', @it102_module, 2024, 2, '2024-07-01', 'active'),
('220234532', @it102_module, 2024, 2, '2024-07-01', 'active'),
('220234533', @it102_module, 2024, 2, '2024-07-01', 'active'),
('220234534', @it102_module, 2024, 2, '2024-07-01', 'active'),
('220234535', @it102_module, 2024, 2, '2024-07-01', 'active'),
('220234536', @it102_module, 2024, 2, '2024-07-01', 'active'),
('220234537', @it102_module, 2024, 2, '2024-07-01', 'active'),
('220234538', @it102_module, 2024, 2, '2024-07-01', 'active');

SELECT 'Student module enrollments created!' as message;
SELECT 
    m.subject_code,
    m.subject_name,
    COUNT(sm.enrollment_id) as enrolled_students,
    m.headcount as expected_headcount
FROM student_modules sm
INNER JOIN modules m ON sm.module_id = m.module_id
WHERE sm.status = 'active'
GROUP BY m.module_id
ORDER BY m.subject_code;

SELECT '✓ Students are now enrolled in modules!' as message;
SELECT 'When tutors create sessions, all enrolled students will be automatically available for attendance marking.' as note;
