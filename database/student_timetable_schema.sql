-- ============================================
-- STUDENT CLASS TIMETABLE SCHEMA
-- Stores class schedules to prevent session conflicts
-- ============================================

USE wsu_booking;

-- Create class timetable table
CREATE TABLE IF NOT EXISTS class_timetable (
    timetable_id INT AUTO_INCREMENT PRIMARY KEY,
    module_id INT NOT NULL,
    day_of_week TINYINT NOT NULL COMMENT '1=Monday, 2=Tuesday, 3=Wednesday, 4=Thursday, 5=Friday (Weekdays only)',
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    location VARCHAR(100),
    class_type ENUM('lecture', 'tutorial', 'practical', 'lab') DEFAULT 'lecture',
    academic_year INT NOT NULL,
    semester TINYINT NOT NULL,
    effective_from DATE NOT NULL,
    effective_to DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (module_id) REFERENCES modules(module_id) ON DELETE CASCADE,
    INDEX idx_module (module_id),
    INDEX idx_day_time (day_of_week, start_time, end_time),
    CHECK (day_of_week BETWEEN 1 AND 5)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert sample timetable for CS101 (Introduction to Programming)
INSERT INTO class_timetable (module_id, day_of_week, start_time, end_time, location, class_type, academic_year, semester, effective_from, effective_to) 
SELECT 
    module_id,
    1, -- Monday
    '08:00:00',
    '10:00:00',
    'Lecture Hall A',
    'lecture',
    2024,
    1,
    '2024-02-01',
    '2024-06-30'
FROM modules WHERE subject_code = 'CS101' LIMIT 1;

INSERT INTO class_timetable (module_id, day_of_week, start_time, end_time, location, class_type, academic_year, semester, effective_from, effective_to) 
SELECT 
    module_id,
    3, -- Wednesday
    '10:00:00',
    '12:00:00',
    'Computer Lab A',
    'practical',
    2024,
    1,
    '2024-02-01',
    '2024-06-30'
FROM modules WHERE subject_code = 'CS101' LIMIT 1;

INSERT INTO class_timetable (module_id, day_of_week, start_time, end_time, location, class_type, academic_year, semester, effective_from, effective_to) 
SELECT 
    module_id,
    5, -- Friday
    '08:00:00',
    '10:00:00',
    'Lecture Hall A',
    'lecture',
    2024,
    1,
    '2024-02-01',
    '2024-06-30'
FROM modules WHERE subject_code = 'CS101' LIMIT 1;

-- Insert sample timetable for MATH101 (Calculus I)
INSERT INTO class_timetable (module_id, day_of_week, start_time, end_time, location, class_type, academic_year, semester, effective_from, effective_to) 
SELECT 
    module_id,
    2, -- Tuesday
    '08:00:00',
    '10:00:00',
    'Lecture Hall B',
    'lecture',
    2024,
    1,
    '2024-02-01',
    '2024-06-30'
FROM modules WHERE subject_code = 'MATH101' LIMIT 1;

INSERT INTO class_timetable (module_id, day_of_week, start_time, end_time, location, class_type, academic_year, semester, effective_from, effective_to) 
SELECT 
    module_id,
    4, -- Thursday
    '10:00:00',
    '12:00:00',
    'Math Tutorial Room',
    'tutorial',
    2024,
    1,
    '2024-02-01',
    '2024-06-30'
FROM modules WHERE subject_code = 'MATH101' LIMIT 1;

INSERT INTO class_timetable (module_id, day_of_week, start_time, end_time, location, class_type, academic_year, semester, effective_from, effective_to) 
SELECT 
    module_id,
    5, -- Friday
    '10:00:00',
    '12:00:00',
    'Lecture Hall B',
    'lecture',
    2024,
    1,
    '2024-02-01',
    '2024-06-30'
FROM modules WHERE subject_code = 'MATH101' LIMIT 1;

-- Insert sample timetable for IT102 (Data Structures)
INSERT INTO class_timetable (module_id, day_of_week, start_time, end_time, location, class_type, academic_year, semester, effective_from, effective_to) 
SELECT 
    module_id,
    1, -- Monday
    '14:00:00',
    '16:00:00',
    'Computer Lab B',
    'practical',
    2024,
    2,
    '2024-07-01',
    '2024-11-30'
FROM modules WHERE subject_code = 'IT102' LIMIT 1;

INSERT INTO class_timetable (module_id, day_of_week, start_time, end_time, location, class_type, academic_year, semester, effective_from, effective_to) 
SELECT 
    module_id,
    3, -- Wednesday
    '14:00:00',
    '16:00:00',
    'Lecture Hall C',
    'lecture',
    2024,
    2,
    '2024-07-01',
    '2024-11-30'
FROM modules WHERE subject_code = 'IT102' LIMIT 1;

SELECT 'Class timetable created successfully!' as message;
SELECT 
    m.subject_code,
    m.subject_name,
    ct.day_of_week,
    CASE ct.day_of_week
        WHEN 1 THEN 'Monday'
        WHEN 2 THEN 'Tuesday'
        WHEN 3 THEN 'Wednesday'
        WHEN 4 THEN 'Thursday'
        WHEN 5 THEN 'Friday'
    END as day_name,
    ct.start_time,
    ct.end_time,
    ct.location,
    ct.class_type
FROM class_timetable ct
INNER JOIN modules m ON ct.module_id = m.module_id
ORDER BY m.subject_code, ct.day_of_week, ct.start_time;
