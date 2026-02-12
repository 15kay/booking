-- ============================================
-- FIX TUTOR ASSIGNMENTS
-- Create assignments for the current tutors/PALs
-- ============================================

USE wsu_booking;

-- First, let's see current staff IDs
SELECT 'Current Tutors and PALs:' as message;
SELECT staff_id, staff_number, CONCAT(first_name, ' ', last_name) as name, role, student_number
FROM staff 
WHERE role IN ('tutor', 'pal')
ORDER BY role, staff_number;

-- Check existing assignments
SELECT 'Existing Assignments:' as message;
SELECT COUNT(*) as assignment_count FROM tutor_assignments;

-- Delete old assignments (they reference old staff_ids that don't exist anymore)
DELETE FROM tutor_assignments;
DELETE FROM tutor_sessions;
DELETE FROM session_registrations;

SELECT 'Old assignments cleared!' as message;

-- Get the actual staff_ids for our tutors
SET @sipho_id = (SELECT staff_id FROM staff WHERE staff_number = 'TUT001');
SET @nomsa_id = (SELECT staff_id FROM staff WHERE staff_number = 'TUT002');
SET @thandi_id = (SELECT staff_id FROM staff WHERE staff_number = 'PAL001');
SET @bongani_id = (SELECT staff_id FROM staff WHERE staff_number = 'PAL002');

-- Get coordinator ID (COORD001 - Mthatha)
SET @coord_id = (SELECT staff_id FROM staff WHERE staff_number = 'COORD001');

-- Show the IDs we'll use
SELECT 'Staff IDs to use:' as message;
SELECT @sipho_id as sipho_tut001, @nomsa_id as nomsa_tut002, @thandi_id as thandi_pal001, @bongani_id as bongani_pal002, @coord_id as coordinator;

-- Get risk_ids for modules (these should exist from coordinator_schema.sql)
SET @cs101_risk = (SELECT risk_id FROM at_risk_modules arm 
                   INNER JOIN modules m ON arm.module_id = m.module_id 
                   WHERE m.subject_code = 'CS101' LIMIT 1);
SET @math101_risk = (SELECT risk_id FROM at_risk_modules arm 
                     INNER JOIN modules m ON arm.module_id = m.module_id 
                     WHERE m.subject_code = 'MATH101' LIMIT 1);
SET @it102_risk = (SELECT risk_id FROM at_risk_modules arm 
                   INNER JOIN modules m ON arm.module_id = m.module_id 
                   WHERE m.subject_code = 'IT102' LIMIT 1);

-- Show risk IDs
SELECT 'Risk Module IDs:' as message;
SELECT @cs101_risk as cs101, @math101_risk as math101, @it102_risk as it102;

-- Create new assignments with correct staff_ids
INSERT INTO tutor_assignments (risk_module_id, tutor_id, tutor_type, assigned_by, assignment_date, start_date, end_date, max_students, session_frequency, location, notes, status) VALUES
-- Sipho (TUT001) assigned to CS101
(@cs101_risk, @sipho_id, 'tutor', @coord_id, '2024-02-20', '2024-02-25', '2024-06-30', 20, 'Twice weekly', 'Computer Lab A', 'Focus on programming basics and problem-solving', 'active'),

-- Thandi (PAL001) assigned to CS101 as PAL
(@cs101_risk, @thandi_id, 'pal', @coord_id, '2024-02-20', '2024-02-25', '2024-06-30', 15, 'Three times weekly', 'Study Room 101', 'Peer-led study groups for programming', 'active'),

-- Nomsa (TUT002) assigned to MATH101
(@math101_risk, @nomsa_id, 'tutor', @coord_id, '2024-02-25', '2024-03-01', '2024-06-30', 25, 'Three times weekly', 'Math Tutorial Room', 'Calculus fundamentals and practice', 'active'),

-- Sipho (TUT001) assigned to IT102 (second assignment)
(@it102_risk, @sipho_id, 'tutor', @coord_id, '2024-07-15', '2024-07-20', '2024-11-30', 15, 'Twice weekly', 'Computer Lab B', 'Data structures and algorithms', 'active');

-- Verify assignments
SELECT 'New Assignments Created:' as message;
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
    ta.end_date
FROM tutor_assignments ta
INNER JOIN at_risk_modules arm ON ta.risk_module_id = arm.risk_id
INNER JOIN modules m ON arm.module_id = m.module_id
INNER JOIN staff s ON ta.tutor_id = s.staff_id
ORDER BY ta.assignment_id;

-- Add some sample sessions
INSERT INTO tutor_sessions (assignment_id, session_date, start_time, end_time, location, topic, description, max_capacity, session_type, status) VALUES
(1, '2024-03-01', '14:00:00', '16:00:00', 'Computer Lab A', 'Introduction to Variables and Data Types', 'Learn about different data types in programming', 20, 'group', 'completed'),
(1, '2024-03-04', '14:00:00', '16:00:00', 'Computer Lab A', 'Control Structures and Loops', 'Understanding if statements and loops', 20, 'group', 'scheduled'),
(2, '2024-03-02', '15:00:00', '17:00:00', 'Study Room 101', 'Problem-Solving Strategies', 'Peer-led problem solving session', 15, 'group', 'completed'),
(3, '2024-03-05', '10:00:00', '12:00:00', 'Math Tutorial Room', 'Limits and Continuity', 'Introduction to calculus concepts', 25, 'group', 'scheduled');

SELECT 'Sample sessions created!' as message;
SELECT COUNT(*) as session_count FROM tutor_sessions;

SELECT 'DONE! Tutor assignments fixed with correct staff_ids.' as message;
