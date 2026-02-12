-- ============================================
-- ALTER EXISTING MODULES TABLE
-- Add columns for pass rate tracking and risk categorization
-- ============================================

USE wsu_booking;

-- Add new columns to existing modules table
ALTER TABLE modules
ADD COLUMN IF NOT EXISTS academic_year VARCHAR(10) AFTER module_id,
ADD COLUMN IF NOT EXISTS campus VARCHAR(100) AFTER academic_year,
ADD COLUMN IF NOT EXISTS custom_grouping VARCHAR(100) AFTER campus,
ADD COLUMN IF NOT EXISTS faculty VARCHAR(200) AFTER custom_grouping,
ADD COLUMN IF NOT EXISTS school VARCHAR(200) AFTER faculty,
ADD COLUMN IF NOT EXISTS subject_area VARCHAR(100) AFTER school,
ADD COLUMN IF NOT EXISTS period_of_study VARCHAR(50) AFTER subject_area,
ADD COLUMN IF NOT EXISTS academic_block_code VARCHAR(50) AFTER period_of_study,
ADD COLUMN IF NOT EXISTS subject_code VARCHAR(20) AFTER academic_block_code,
ADD COLUMN IF NOT EXISTS subject_name VARCHAR(300) AFTER subject_code,
ADD COLUMN IF NOT EXISTS subjects_passed INT DEFAULT 0 AFTER subject_name,
ADD COLUMN IF NOT EXISTS headcount INT DEFAULT 0 AFTER subjects_passed,
ADD COLUMN IF NOT EXISTS subject_pass_rate DECIMAL(5,4) AFTER headcount;

-- Add computed column for risk category (MySQL 5.7.6+)
-- If your MySQL version doesn't support generated columns, comment this out
ALTER TABLE modules
ADD COLUMN IF NOT EXISTS risk_category VARCHAR(20) 
    GENERATED ALWAYS AS (
        CASE 
            WHEN subject_pass_rate < 0.40 THEN 'High Risk'
            WHEN subject_pass_rate < 0.60 THEN 'Moderate Risk'
            WHEN subject_pass_rate < 0.75 THEN 'Low Risk'
            ELSE 'Very Low Risk'
        END
    ) STORED;

-- Add indexes for better performance
CREATE INDEX IF NOT EXISTS idx_subject_code ON modules(subject_code);
CREATE INDEX IF NOT EXISTS idx_academic_year ON modules(academic_year);
CREATE INDEX IF NOT EXISTS idx_pass_rate ON modules(subject_pass_rate);
CREATE INDEX IF NOT EXISTS idx_faculty ON modules(faculty);
CREATE INDEX IF NOT EXISTS idx_school ON modules(school);

-- Update at_risk_modules table to include new fields
ALTER TABLE at_risk_modules
ADD COLUMN IF NOT EXISTS pass_rate DECIMAL(5,4) AFTER failure_rate,
ADD COLUMN IF NOT EXISTS campus VARCHAR(100) AFTER semester,
ADD COLUMN IF NOT EXISTS faculty VARCHAR(200) AFTER campus,
ADD COLUMN IF NOT EXISTS school VARCHAR(200) AFTER faculty;

-- If you need to populate existing data, you can do it here
-- Example:
-- UPDATE modules SET academic_year = '2024' WHERE academic_year IS NULL;

SELECT 'Modules table altered successfully!' as message;
