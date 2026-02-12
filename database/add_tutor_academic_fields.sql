-- ============================================
-- ADD ACADEMIC PERFORMANCE FIELDS FOR TUTORS/PALs
-- These are students who applied to be tutors/PALs
-- ============================================

USE wsu_booking;

-- Add academic performance columns to staff table
ALTER TABLE staff
ADD COLUMN IF NOT EXISTS student_number VARCHAR(50) AFTER staff_number,
ADD COLUMN IF NOT EXISTS gpa DECIMAL(3,2) AFTER qualification,
ADD COLUMN IF NOT EXISTS academic_year_level VARCHAR(50) AFTER gpa,
ADD COLUMN IF NOT EXISTS modules_tutored TEXT AFTER specialization,
ADD COLUMN IF NOT EXISTS application_date DATE AFTER status,
ADD COLUMN IF NOT EXISTS approval_date DATE AFTER application_date,
ADD COLUMN IF NOT EXISTS approved_by INT AFTER approval_date;

SELECT 'Academic performance fields added successfully!' as message;

-- Show updated structure
DESCRIBE staff;
