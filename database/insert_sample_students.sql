-- ============================================
-- INSERT SAMPLE STUDENTS
-- Students enrolled in modules with tutors/PALs
-- ============================================

USE wsu_booking;

-- Check existing students
SELECT 'Checking existing students...' as message;
SELECT COUNT(*) as existing_students FROM students;

-- Clear old students (optional - comment out if you want to keep existing)
-- DELETE FROM students;

-- Add sample students for CS101 (Thandi Khumalo's PAL assignment)
INSERT INTO students (student_id, first_name, last_name, email, password_hash, phone, faculty_id, year_of_study, student_type, status) VALUES
-- CS101 Students (Mthatha Campus - Faculty of Science = 1)
('220234501', 'Ayanda', 'Nkosi', 'ayanda.nkosi@wsu.ac.za', '$2y$10$T./d56vL6qf6PHYYdZA7/.8IRTzCC0tkBc0qeh0XXKzSKJzaFP0qC', '0731234501', 1, 1, 'undergraduate', 'active'),
('220234502', 'Busisiwe', 'Dlamini', 'busisiwe.dlamini@wsu.ac.za', '$2y$10$T./d56vL6qf6PHYYdZA7/.8IRTzCC0tkBc0qeh0XXKzSKJzaFP0qC', '0731234502', 1, 1, 'undergraduate', 'active'),
('220234503', 'Cebo', 'Mthembu', 'cebo.mthembu@wsu.ac.za', '$2y$10$T./d56vL6qf6PHYYdZA7/.8IRTzCC0tkBc0qeh0XXKzSKJzaFP0qC', '0731234503', 1, 1, 'undergraduate', 'active'),
('220234504', 'Dineo', 'Mokoena', 'dineo.mokoena@wsu.ac.za', '$2y$10$T./d56vL6qf6PHYYdZA7/.8IRTzCC0tkBc0qeh0XXKzSKJzaFP0qC', '0731234504', 1, 1, 'undergraduate', 'active'),
('220234505', 'Enhle', 'Zulu', 'enhle.zulu@wsu.ac.za', '$2y$10$T./d56vL6qf6PHYYdZA7/.8IRTzCC0tkBc0qeh0XXKzSKJzaFP0qC', '0731234505', 1, 1, 'undergraduate', 'active'),
('220234506', 'Fezile', 'Ndlovu', 'fezile.ndlovu@wsu.ac.za', '$2y$10$T./d56vL6qf6PHYYdZA7/.8IRTzCC0tkBc0qeh0XXKzSKJzaFP0qC', '0731234506', 1, 1, 'undergraduate', 'active'),
('220234507', 'Gcina', 'Khumalo', 'gcina.khumalo@wsu.ac.za', '$2y$10$T./d56vL6qf6PHYYdZA7/.8IRTzCC0tkBc0qeh0XXKzSKJzaFP0qC', '0731234507', 1, 1, 'undergraduate', 'active'),
('220234508', 'Hlengiwe', 'Sithole', 'hlengiwe.sithole@wsu.ac.za', '$2y$10$T./d56vL6qf6PHYYdZA7/.8IRTzCC0tkBc0qeh0XXKzSKJzaFP0qC', '0731234508', 1, 1, 'undergraduate', 'active'),
('220234509', 'Innocent', 'Moyo', 'innocent.moyo@wsu.ac.za', '$2y$10$T./d56vL6qf6PHYYdZA7/.8IRTzCC0tkBc0qeh0XXKzSKJzaFP0qC', '0731234509', 1, 1, 'undergraduate', 'active'),
('220234510', 'Jabu', 'Dube', 'jabu.dube@wsu.ac.za', '$2y$10$T./d56vL6qf6PHYYdZA7/.8IRTzCC0tkBc0qeh0XXKzSKJzaFP0qC', '0731234510', 1, 1, 'undergraduate', 'active'),
('220234511', 'Khanya', 'Radebe', 'khanya.radebe@wsu.ac.za', '$2y$10$T./d56vL6qf6PHYYdZA7/.8IRTzCC0tkBc0qeh0XXKzSKJzaFP0qC', '0731234511', 1, 1, 'undergraduate', 'active'),
('220234512', 'Lungile', 'Mahlangu', 'lungile.mahlangu@wsu.ac.za', '$2y$10$T./d56vL6qf6PHYYdZA7/.8IRTzCC0tkBc0qeh0XXKzSKJzaFP0qC', '0731234512', 1, 1, 'undergraduate', 'active'),
('220234513', 'Mandisa', 'Tshabalala', 'mandisa.tshabalala@wsu.ac.za', '$2y$10$T./d56vL6qf6PHYYdZA7/.8IRTzCC0tkBc0qeh0XXKzSKJzaFP0qC', '0731234513', 1, 1, 'undergraduate', 'active'),
('220234514', 'Nathi', 'Mabaso', 'nathi.mabaso@wsu.ac.za', '$2y$10$T./d56vL6qf6PHYYdZA7/.8IRTzCC0tkBc0qeh0XXKzSKJzaFP0qC', '0731234514', 1, 1, 'undergraduate', 'active'),
('220234515', 'Owami', 'Nkomo', 'owami.nkomo@wsu.ac.za', '$2y$10$T./d56vL6qf6PHYYdZA7/.8IRTzCC0tkBc0qeh0XXKzSKJzaFP0qC', '0731234515', 1, 1, 'undergraduate', 'active'),
('220234516', 'Phila', 'Molefe', 'phila.molefe@wsu.ac.za', '$2y$10$T./d56vL6qf6PHYYdZA7/.8IRTzCC0tkBc0qeh0XXKzSKJzaFP0qC', '0731234516', 1, 1, 'undergraduate', 'active'),
('220234517', 'Qiniso', 'Cele', 'qiniso.cele@wsu.ac.za', '$2y$10$T./d56vL6qf6PHYYdZA7/.8IRTzCC0tkBc0qeh0XXKzSKJzaFP0qC', '0731234517', 1, 1, 'undergraduate', 'active'),
('220234518', 'Rethabile', 'Ngcobo', 'rethabile.ngcobo@wsu.ac.za', '$2y$10$T./d56vL6qf6PHYYdZA7/.8IRTzCC0tkBc0qeh0XXKzSKJzaFP0qC', '0731234518', 1, 1, 'undergraduate', 'active'),
('220234519', 'Siphesihle', 'Zungu', 'siphesihle.zungu@wsu.ac.za', '$2y$10$T./d56vL6qf6PHYYdZA7/.8IRTzCC0tkBc0qeh0XXKzSKJzaFP0qC', '0731234519', 1, 1, 'undergraduate', 'active'),
('220234520', 'Thandeka', 'Mkhize', 'thandeka.mkhize@wsu.ac.za', '$2y$10$T./d56vL6qf6PHYYdZA7/.8IRTzCC0tkBc0qeh0XXKzSKJzaFP0qC', '0731234520', 1, 1, 'undergraduate', 'active');

-- Add more students for MATH101 (Nomsa Dlamini's assignment)
INSERT INTO students (student_id, first_name, last_name, email, password_hash, phone, faculty_id, year_of_study, student_type, status) VALUES
('220234521', 'Unathi', 'Gumede', 'unathi.gumede@wsu.ac.za', '$2y$10$T./d56vL6qf6PHYYdZA7/.8IRTzCC0tkBc0qeh0XXKzSKJzaFP0qC', '0731234521', 1, 1, 'undergraduate', 'active'),
('220234522', 'Vusi', 'Shabalala', 'vusi.shabalala@wsu.ac.za', '$2y$10$T./d56vL6qf6PHYYdZA7/.8IRTzCC0tkBc0qeh0XXKzSKJzaFP0qC', '0731234522', 1, 1, 'undergraduate', 'active'),
('220234523', 'Wandile', 'Buthelezi', 'wandile.buthelezi@wsu.ac.za', '$2y$10$T./d56vL6qf6PHYYdZA7/.8IRTzCC0tkBc0qeh0XXKzSKJzaFP0qC', '0731234523', 1, 1, 'undergraduate', 'active'),
('220234524', 'Xolani', 'Khoza', 'xolani.khoza@wsu.ac.za', '$2y$10$T./d56vL6qf6PHYYdZA7/.8IRTzCC0tkBc0qeh0XXKzSKJzaFP0qC', '0731234524', 1, 1, 'undergraduate', 'active'),
('220234525', 'Yolanda', 'Mthethwa', 'yolanda.mthethwa@wsu.ac.za', '$2y$10$T./d56vL6qf6PHYYdZA7/.8IRTzCC0tkBc0qeh0XXKzSKJzaFP0qC', '0731234525', 1, 1, 'undergraduate', 'active'),
('220234526', 'Zanele', 'Ntuli', 'zanele.ntuli@wsu.ac.za', '$2y$10$T./d56vL6qf6PHYYdZA7/.8IRTzCC0tkBc0qeh0XXKzSKJzaFP0qC', '0731234526', 1, 1, 'undergraduate', 'active'),
('220234527', 'Andile', 'Mnguni', 'andile.mnguni@wsu.ac.za', '$2y$10$T./d56vL6qf6PHYYdZA7/.8IRTzCC0tkBc0qeh0XXKzSKJzaFP0qC', '0731234527', 1, 1, 'undergraduate', 'active'),
('220234528', 'Buhle', 'Hadebe', 'buhle.hadebe@wsu.ac.za', '$2y$10$T./d56vL6qf6PHYYdZA7/.8IRTzCC0tkBc0qeh0XXKzSKJzaFP0qC', '0731234528', 1, 1, 'undergraduate', 'active'),
('220234529', 'Cindy', 'Nxumalo', 'cindy.nxumalo@wsu.ac.za', '$2y$10$T./d56vL6qf6PHYYdZA7/.8IRTzCC0tkBc0qeh0XXKzSKJzaFP0qC', '0731234529', 1, 1, 'undergraduate', 'active'),
('220234530', 'Dumisani', 'Mhlongo', 'dumisani.mhlongo@wsu.ac.za', '$2y$10$T./d56vL6qf6PHYYdZA7/.8IRTzCC0tkBc0qeh0XXKzSKJzaFP0qC', '0731234530', 1, 1, 'undergraduate', 'active');

SELECT 'Sample students inserted!' as message;
SELECT COUNT(*) as total_students FROM students;

-- Now register some students for the existing sessions
-- Get session IDs
SET @session1 = (SELECT session_id FROM tutor_sessions WHERE topic = 'Problem-Solving Strategies in Programming' LIMIT 1);
SET @session2 = (SELECT session_id FROM tutor_sessions WHERE topic = 'Introduction to Variables and Data Types' LIMIT 1);

-- Register students for Thandi's PAL session (Problem-Solving Strategies in Programming)
INSERT INTO session_registrations (session_id, student_id, registration_date, attended, status) VALUES
(@session1, '220234501', '2024-02-28 10:00:00', TRUE, 'attended'),
(@session1, '220234502', '2024-02-28 10:15:00', TRUE, 'attended'),
(@session1, '220234503', '2024-02-28 10:30:00', TRUE, 'attended'),
(@session1, '220234504', '2024-02-28 11:00:00', TRUE, 'attended'),
(@session1, '220234505', '2024-02-28 11:30:00', FALSE, 'absent'),
(@session1, '220234506', '2024-02-28 12:00:00', TRUE, 'attended'),
(@session1, '220234507', '2024-02-28 12:30:00', TRUE, 'attended'),
(@session1, '220234508', '2024-02-28 13:00:00', TRUE, 'attended'),
(@session1, '220234509', '2024-02-28 13:30:00', FALSE, 'absent'),
(@session1, '220234510', '2024-02-28 14:00:00', TRUE, 'attended');

-- Register students for Sipho's session (Introduction to Variables)
INSERT INTO session_registrations (session_id, student_id, registration_date, attended, status) VALUES
(@session2, '220234501', '2024-02-28 09:00:00', TRUE, 'attended'),
(@session2, '220234502', '2024-02-28 09:15:00', TRUE, 'attended'),
(@session2, '220234503', '2024-02-28 09:30:00', TRUE, 'attended'),
(@session2, '220234504', '2024-02-28 09:45:00', TRUE, 'attended'),
(@session2, '220234505', '2024-02-28 10:00:00', TRUE, 'attended'),
(@session2, '220234506', '2024-02-28 10:15:00', FALSE, 'absent'),
(@session2, '220234507', '2024-02-28 10:30:00', TRUE, 'attended'),
(@session2, '220234508', '2024-02-28 10:45:00', TRUE, 'attended'),
(@session2, '220234511', '2024-02-28 11:00:00', TRUE, 'attended'),
(@session2, '220234512', '2024-02-28 11:15:00', TRUE, 'attended'),
(@session2, '220234513', '2024-02-28 11:30:00', FALSE, 'absent'),
(@session2, '220234514', '2024-02-28 11:45:00', TRUE, 'attended'),
(@session2, '220234515', '2024-02-28 12:00:00', TRUE, 'attended'),
(@session2, '220234516', '2024-02-28 12:15:00', TRUE, 'attended'),
(@session2, '220234517', '2024-02-28 12:30:00', TRUE, 'attended');

SELECT 'Session registrations created!' as message;
SELECT 
    ts.topic,
    COUNT(sr.registration_id) as total_registered,
    COUNT(CASE WHEN sr.attended = TRUE THEN 1 END) as attended,
    COUNT(CASE WHEN sr.attended = FALSE THEN 1 END) as absent
FROM tutor_sessions ts
LEFT JOIN session_registrations sr ON ts.session_id = sr.session_id
GROUP BY ts.session_id;

SELECT '✓ DONE! Students and registrations created successfully!' as message;
SELECT 'All student passwords: password123' as note;
