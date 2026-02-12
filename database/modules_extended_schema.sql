-- ============================================
-- EXTENDED MODULES TABLE FOR REAL DATA
-- ============================================

USE wsu_booking;

-- Drop existing modules table if you want to recreate with new structure
-- DROP TABLE IF EXISTS modules;

-- Extended Modules Table matching your data structure
CREATE TABLE IF NOT EXISTS modules (
    module_id INT AUTO_INCREMENT PRIMARY KEY,
    
    -- Basic Information
    academic_year VARCHAR(10) NOT NULL,
    campus VARCHAR(100),
    custom_grouping VARCHAR(100),
    faculty VARCHAR(200),
    school VARCHAR(200),
    subject_area VARCHAR(100),
    period_of_study VARCHAR(50),
    academic_block_code VARCHAR(50),
    
    -- Module Identification
    subject_code VARCHAR(20) NOT NULL,
    subject_name VARCHAR(300) NOT NULL,
    
    -- Performance Metrics
    subjects_passed INT DEFAULT 0,
    headcount INT DEFAULT 0,
    subject_pass_rate DECIMAL(5,4),
    
    -- Calculated Risk Category
    risk_category ENUM('Very Low Risk', 'Low Risk', 'Moderate Risk', 'High Risk') 
        GENERATED ALWAYS AS (
            CASE 
                WHEN subject_pass_rate < 0.40 THEN 'High Risk'
                WHEN subject_pass_rate < 0.60 THEN 'Moderate Risk'
                WHEN subject_pass_rate < 0.75 THEN 'Low Risk'
                ELSE 'Very Low Risk'
            END
        ) STORED,
    
    -- Legacy fields for compatibility
    department_id INT,
    year_level TINYINT,
    semester ENUM('1', '2', 'both') DEFAULT 'both',
    credits INT DEFAULT 0,
    description TEXT,
    
    -- Status
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Indexes
    INDEX idx_subject_code (subject_code),
    INDEX idx_academic_year (academic_year),
    INDEX idx_risk_category (risk_category),
    INDEX idx_pass_rate (subject_pass_rate),
    INDEX idx_faculty (faculty),
    INDEX idx_school (school),
    INDEX idx_status (status),
    
    -- Unique constraint
    UNIQUE KEY unique_module (subject_code, academic_year, period_of_study)
) ENGINE=InnoDB;

-- Update at_risk_modules to use pass_rate instead of failure_rate
ALTER TABLE at_risk_modules 
ADD COLUMN pass_rate DECIMAL(5,4) AFTER risk_level,
ADD COLUMN campus VARCHAR(100) AFTER semester,
ADD COLUMN faculty VARCHAR(200) AFTER campus,
ADD COLUMN school VARCHAR(200) AFTER faculty;

-- Update risk_level calculation based on pass rate
-- High Risk: < 40% pass rate
-- Moderate Risk: 40-59% pass rate  
-- Low Risk: 60-74% pass rate
-- Very Low Risk: >= 75% pass rate

-- Sample data insert (you can modify based on your actual data)
-- INSERT INTO modules (academic_year, campus, faculty, school, subject_area, period_of_study, 
--                      academic_block_code, subject_code, subject_name, subjects_passed, 
--                      headcount, subject_pass_rate)
-- VALUES 
-- ('2024', 'Main Campus', 'Faculty of Science', 'School of Computer Science', 'CS', 
--  'Semester 1', 'BLK1', 'CS101', 'Introduction to Programming', 45, 100, 0.45);
