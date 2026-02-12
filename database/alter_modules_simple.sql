-- ============================================
-- ALTER EXISTING MODULES TABLE (Simple Version)
-- For MySQL versions that don't support generated columns
-- ============================================

USE wsu_booking;

-- Add new columns to existing modules table
ALTER TABLE modules
ADD COLUMN academic_year VARCHAR(10),
ADD COLUMN campus VARCHAR(100),
ADD COLUMN custom_grouping VARCHAR(100),
ADD COLUMN faculty VARCHAR(200),
ADD COLUMN school VARCHAR(200),
ADD COLUMN subject_area VARCHAR(100),
ADD COLUMN period_of_study VARCHAR(50),
ADD COLUMN academic_block_code VARCHAR(50),
ADD COLUMN subject_code VARCHAR(20),
ADD COLUMN subject_name VARCHAR(300),
ADD COLUMN subjects_passed INT DEFAULT 0,
ADD COLUMN headcount INT DEFAULT 0,
ADD COLUMN subject_pass_rate DECIMAL(5,4),
ADD COLUMN risk_category VARCHAR(20);

-- Add indexes for better performance
ALTER TABLE modules
ADD INDEX idx_subject_code (subject_code),
ADD INDEX idx_academic_year (academic_year),
ADD INDEX idx_pass_rate (subject_pass_rate),
ADD INDEX idx_faculty (faculty),
ADD INDEX idx_school (school);

-- Update at_risk_modules table to include new fields
ALTER TABLE at_risk_modules
ADD COLUMN pass_rate DECIMAL(5,4) AFTER failure_rate,
ADD COLUMN campus VARCHAR(100) AFTER semester,
ADD COLUMN faculty VARCHAR(200) AFTER campus,
ADD COLUMN school VARCHAR(200) AFTER faculty;

-- Create a trigger to automatically calculate risk_category when data is inserted/updated
DELIMITER $$

DROP TRIGGER IF EXISTS calculate_risk_category_insert$$
CREATE TRIGGER calculate_risk_category_insert
BEFORE INSERT ON modules
FOR EACH ROW
BEGIN
    IF NEW.subject_pass_rate IS NOT NULL THEN
        SET NEW.risk_category = CASE 
            WHEN NEW.subject_pass_rate < 0.40 THEN 'High Risk'
            WHEN NEW.subject_pass_rate < 0.60 THEN 'Moderate Risk'
            WHEN NEW.subject_pass_rate < 0.75 THEN 'Low Risk'
            ELSE 'Very Low Risk'
        END;
    END IF;
END$$

DROP TRIGGER IF EXISTS calculate_risk_category_update$$
CREATE TRIGGER calculate_risk_category_update
BEFORE UPDATE ON modules
FOR EACH ROW
BEGIN
    IF NEW.subject_pass_rate IS NOT NULL THEN
        SET NEW.risk_category = CASE 
            WHEN NEW.subject_pass_rate < 0.40 THEN 'High Risk'
            WHEN NEW.subject_pass_rate < 0.60 THEN 'Moderate Risk'
            WHEN NEW.subject_pass_rate < 0.75 THEN 'Low Risk'
            ELSE 'Very Low Risk'
        END;
    END IF;
END$$

DELIMITER ;

-- Update existing records to calculate risk_category
UPDATE modules 
SET risk_category = CASE 
    WHEN subject_pass_rate < 0.40 THEN 'High Risk'
    WHEN subject_pass_rate < 0.60 THEN 'Moderate Risk'
    WHEN subject_pass_rate < 0.75 THEN 'Low Risk'
    ELSE 'Very Low Risk'
END
WHERE subject_pass_rate IS NOT NULL;

SELECT 'Modules table altered successfully with triggers!' as message;
