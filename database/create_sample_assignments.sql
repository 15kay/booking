-- ============================================
-- CREATE SAMPLE TUTOR ASSIGNMENTS
-- First create at-risk modules, then assign tutors
-- ============================================

USE wsu_booking;

-- Get staff IDs
SET @sipho_id = (SELECT staff_id FROM staff WHERE staff_number = 'TUT001');
SET @nomsa_id = (SELECT staff_id FROM staff WHERE staff_number = 'TUT002');
SET @thandi_id = (SELECT staff_id FROM staff WHERE staff_number = 'PAL001');
SET @coord_id = (SELECT staff_id FROM staff WHERE staff_number = 'COORD001');

-- Get module IDs
SET @cs101_module = (SELECT module_id FROM modules WHERE subject_code = 'CS101' LIMIT 1);
SET @math101_module = (SELECT module_id FROM modules WHERE subject_code = 'MATH101' LIMIT 1);
SET @it102_module = (SELECT module_id FROM modules WHERE subject_code = 'IT102' LIMIT 1);

SELECT 'Module IDs:' as message;
SELECT @cs101_module as cs101, @math101_module as math101, @it102_module as it102;

-- If modules don't exist, create them
INSERT IGNORE INTO modules (subject_code, subject_name, faculty, campus, academic_year, semester, headcount, subject_pass_rate, risk_category, subject_area) VALUES
('CS101', 'Introduction to Programming', 'Faculty of Science', 'Mthatha', 2024, 1, 45, 0.35, 'High Risk', 'Computer Science'),
('MATH101', 'Calculus I', 'Faculty of Science', 'Mthatha', 2024, 1, 55, 0.38, 'High Risk', 'Mathematics'),
('IT102', 'Data Structures', 'Faculty of Science', 'Mthatha', 2024, 2, 38, 0.52, 'Moderate Risk', 'Computer Science');

-- Get module IDs again after insert
SET @cs101_module = (SELECT module_id FROM modules WHERE subject_code = 'CS101' LIMIT 1);
SET @math101_module = (SELECT module_id FROM modules WHERE subject_code = 'MATH101' LIMIT 1);
SET @it102_module = (SELECT module_id FROM modules WHERE subject_code = 'IT102' LIMIT 1);

SELECT 'Modules created/found:' as message;
SELECT module_id, subject_code, subject_name, headcount, risk_category 
FROM modules 
WHERE subject_code IN ('CS101', 'MATH101', 'IT102');

-- Clear old at-risk modules
DELETE FROM at_risk_modules WHERE module_id IN (@cs101_module, @math101_module, @it102_module);

-- Create at-risk module entries
INSERT INTO at_risk_modules (module_id, academic_year, semester, campus, faculty, identified_date, at_risk_students, reason, status) VALUES
(@cs101_module, 2024, 1, 'Mthatha', 'Faculty of Science', '2024-02-15', 18, 'Low pass rate (35%) - High risk module requiring tutor support. Students struggling with programming fundamentals and logical thinking.', 'active'),
(@math101_module, 2024, 1, 'Mthatha', 'Faculty of Science', '2024-02-20', 22, 'Low pass rate (38%) - High risk module. Students need support with calculus concepts, limits, and derivatives.', 'active'),
(@it102_module, 2024, 2, 'Mthatha', 'Faculty of Science', '2024-07-10', 15, 'Moderate pass rate (52%) - Requires additional support. Complex data structures need more practice and guidance.', 'active');

-- Get risk IDs
SET @cs101_risk = (SELECT risk_id FROM at_risk_modules WHERE module_id = @cs101_module LIMIT 1);
SET @math101_risk = (SELECT risk_id FROM at_risk_modules WHERE module_id = @math101_module LIMIT 1);
SET @it102_risk = (SELECT risk_id FROM at_risk_modules WHERE module_id = @it102_module LIMIT 1);

SELECT 'At-risk modules created:' as message;
SELECT risk_id, module_id, at_risk_students, reason, status 
FROM at_risk_modules 
WHERE module_id IN (@cs101_module, @math101_module, @it102_module);

-- Clear old assignments
DELETE FROM session_registrations;
DELETE FROM tutor_sessions;
DELETE FROM tutor_assignments;

-- Create tutor assignments
INSERT INTO tutor_assignments (risk_module_id, tutor_id, tutor_type, assigned_by, assignment_date, start_date, end_date, max_students, session_frequency, location, notes, status) VALUES
-- Sipho (TUT001) assigned to CS101
(@cs101_risk, @sipho_id, 'tutor', @coord_id, '2024-02-20', '2024-02-25', '2024-06-30', 20, 'Twice weekly', 'Computer Lab A', 'Focus on programming basics, variables, control structures, and problem-solving. Help students build strong foundation in coding logic.', 'active'),

-- Thandi (PAL001) assigned to CS101 as PAL
(@cs101_risk, @thandi_id, 'pal', @coord_id, '2024-02-20', '2024-02-25', '2024-06-30', 15, 'Three times weekly', 'Study Room 101', 'Peer-led study groups for programming. Attend lectures with students and facilitate collaborative learning sessions.', 'active'),

-- Nomsa (TUT002) assigned to MATH101
(@math101_risk, @nomsa_id, 'tutor', @coord_id, '2024-02-25', '2024-03-01', '2024-06-30', 25, 'Three times weekly', 'Math Tutorial Room', 'Calculus fundamentals - limits, derivatives, and integration. Focus on practice problems and conceptual understanding.', 'active'),

-- Sipho (TUT001) assigned to IT102 (second assignment)
(@it102_risk, @sipho_id, 'tutor', @coord_id, '2024-07-15', '2024-07-20', '2024-11-30', 15, 'Twice weekly', 'Computer Lab B', 'Data structures and algorithms - arrays, linked lists, stacks, queues. Emphasis on implementation and time complexity.', 'active');

SELECT 'Tutor assignments created:' as message;
SELECT 
    ta.assignment_id,
    m.subject_code,
    m.subject_name,
    CONCAT(s.first_name, ' ', s.last_name) as tutor_name,
    s.staff_number,
    s.student_number,
    ta.tutor_type,
    ta.status,
    ta.start_date,
    ta.end_date,
    ta.location
FROM tutor_assignments ta
INNER JOIN at_risk_modules arm ON ta.risk_module_id = arm.risk_id
INNER JOIN modules m ON arm.module_id = m.module_id
INNER JOIN staff s ON ta.tutor_id = s.staff_id
ORDER BY ta.assignment_id;

-- Add some sample sessions (with description field)
INSERT INTO tutor_sessions (assignment_id, session_date, start_time, end_time, location, topic, description, max_capacity, session_type, status) VALUES
(1, '2024-03-01', '14:00:00', '16:00:00', 'Computer Lab A', 'Introduction to Variables and Data Types', 'Learn about different data types in programming (integers, strings, booleans). Practice declaring variables and understanding type conversion. Hands-on coding exercises included.', 20, 'group', 'completed'),
(1, '2024-03-04', '14:00:00', '16:00:00', 'Computer Lab A', 'Control Structures and Loops', 'Master if-else statements, switch cases, and loops (for, while). Build simple programs using conditional logic and iteration.', 20, 'group', 'scheduled'),
(2, '2024-03-02', '15:00:00', '17:00:00', 'Study Room 101', 'Problem-Solving Strategies in Programming', 'Collaborative session on breaking down complex problems into smaller steps. Practice pseudocode and flowcharts. Work through coding challenges together.', 15, 'group', 'completed'),
(3, '2024-03-05', '10:00:00', '12:00:00', 'Math Tutorial Room', 'Limits and Continuity', 'Understanding limits, one-sided limits, and continuity. Practice calculating limits using algebraic techniques and graphical interpretation.', 25, 'group', 'scheduled');

SELECT 'Sample sessions created:' as message;
SELECT 
    ts.session_id,
    m.subject_code,
    ts.topic,
    ts.session_date,
    ts.start_time,
    ts.status
FROM tutor_sessions ts
INNER JOIN tutor_assignments ta ON ts.assignment_id = ta.assignment_id
INNER JOIN at_risk_modules arm ON ta.risk_module_id = arm.risk_id
INNER JOIN modules m ON arm.module_id = m.module_id
ORDER BY ts.session_id;

SELECT '✓ DONE! Sample assignments created successfully!' as message;
SELECT 'Login as PAL001 (Thandi Khumalo) with password: password123' as instructions;
