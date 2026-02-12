-- ============================================
-- ADD CAMPUS ASSIGNMENTS TO STAFF
-- ONE COORDINATOR PER CAMPUS
-- ============================================

USE wsu_booking;

-- Add campus column to staff table (remove faculty assignment)
ALTER TABLE staff
ADD COLUMN IF NOT EXISTS assigned_campus VARCHAR(100) AFTER department_id;

-- Update coordinators with their campus assignments (ONE per campus)
UPDATE staff SET assigned_campus = 'Mthatha' WHERE staff_number = 'COORD001';
UPDATE staff SET assigned_campus = 'East London' WHERE staff_number = 'COORD002';
UPDATE staff SET assigned_campus = 'Butterworth' WHERE staff_number = 'COORD003';
UPDATE staff SET assigned_campus = 'Queenstown' WHERE staff_number = 'COORD004';

SELECT 'Coordinator assignments updated successfully!' as message;

-- Show coordinator assignments
SELECT staff_number, CONCAT(first_name, ' ', last_name) as name, assigned_campus
FROM staff 
WHERE role = 'coordinator'
ORDER BY assigned_campus;
