-- Add readiness score field to students table
-- Readiness scores typically range from 0-100 or use standardized test scores

ALTER TABLE students 
ADD COLUMN IF NOT EXISTS reading_score DECIMAL(5,2) NULL COMMENT 'Student readiness score (0-100)' AFTER year_of_study;

-- Update sample students with readiness scores for testing
-- Scores distributed across different proficiency levels

-- Excellent students (80-100)
UPDATE students SET reading_score = 95.00 WHERE student_id = '220234501';
UPDATE students SET reading_score = 88.50 WHERE student_id = '220234502';
UPDATE students SET reading_score = 92.75 WHERE student_id = '220234503';
UPDATE students SET reading_score = 85.00 WHERE student_id = '220234504';
UPDATE students SET reading_score = 91.25 WHERE student_id = '220234505';
UPDATE students SET reading_score = 87.50 WHERE student_id = '220234506';
UPDATE students SET reading_score = 94.00 WHERE student_id = '220234507';
UPDATE students SET reading_score = 89.75 WHERE student_id = '220234508';
UPDATE students SET reading_score = 83.50 WHERE student_id = '220234509';
UPDATE students SET reading_score = 90.00 WHERE student_id = '220234510';

-- Good students (60-79)
UPDATE students SET reading_score = 75.50 WHERE student_id = '220234511';
UPDATE students SET reading_score = 72.00 WHERE student_id = '220234512';
UPDATE students SET reading_score = 68.75 WHERE student_id = '220234513';
UPDATE students SET reading_score = 78.25 WHERE student_id = '220234514';
UPDATE students SET reading_score = 71.50 WHERE student_id = '220234515';
UPDATE students SET reading_score = 76.00 WHERE student_id = '220234516';
UPDATE students SET reading_score = 69.50 WHERE student_id = '220234517';
UPDATE students SET reading_score = 74.75 WHERE student_id = '220234518';
UPDATE students SET reading_score = 70.25 WHERE student_id = '220234519';
UPDATE students SET reading_score = 77.50 WHERE student_id = '220234520';

-- Fair students (40-59)
UPDATE students SET reading_score = 55.00 WHERE student_id = '220234521';
UPDATE students SET reading_score = 48.50 WHERE student_id = '220234522';
UPDATE students SET reading_score = 52.75 WHERE student_id = '220234523';
UPDATE students SET reading_score = 45.00 WHERE student_id = '220234524';
UPDATE students SET reading_score = 58.25 WHERE student_id = '220234525';
UPDATE students SET reading_score = 50.50 WHERE student_id = '220234526';
UPDATE students SET reading_score = 47.00 WHERE student_id = '220234527';
UPDATE students SET reading_score = 54.75 WHERE student_id = '220234528';
UPDATE students SET reading_score = 49.25 WHERE student_id = '220234529';
UPDATE students SET reading_score = 56.50 WHERE student_id = '220234530';

-- At-risk students (below 40)
UPDATE students SET reading_score = 38.00 WHERE student_id = '220234531';
UPDATE students SET reading_score = 32.50 WHERE student_id = '220234532';
UPDATE students SET reading_score = 35.75 WHERE student_id = '220234533';
UPDATE students SET reading_score = 28.00 WHERE student_id = '220234534';
UPDATE students SET reading_score = 36.25 WHERE student_id = '220234535';

-- Update any existing students from demo data
UPDATE students SET reading_score = 82.00 WHERE student_id = '202401234';
UPDATE students SET reading_score = 76.50 WHERE student_id = '202401235';
UPDATE students SET reading_score = 88.75 WHERE student_id = '202401236';
UPDATE students SET reading_score = 71.00 WHERE student_id = '202401237';
UPDATE students SET reading_score = 65.50 WHERE student_id = '202401238';

SELECT 'Readiness scores added successfully!' as message;
SELECT COUNT(*) as students_with_scores FROM students WHERE reading_score IS NOT NULL;
