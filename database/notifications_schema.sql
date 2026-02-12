-- ============================================
-- NOTIFICATIONS SYSTEM
-- Real-time notifications for staff
-- ============================================

USE wsu_booking;

-- Create notifications table
CREATE TABLE IF NOT EXISTS notifications (
    notification_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    user_type ENUM('staff', 'student') DEFAULT 'staff',
    type VARCHAR(50) NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    link VARCHAR(255) NULL,
    icon VARCHAR(50) DEFAULT 'fa-bell',
    color VARCHAR(20) DEFAULT 'blue',
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    read_at TIMESTAMP NULL,
    INDEX idx_user (user_id, user_type),
    INDEX idx_read (is_read),
    INDEX idx_created (created_at)
) ENGINE=InnoDB;

-- Sample notifications for testing
-- These would normally be created by triggers or application logic

-- For coordinators (assuming COORD001 has staff_id = 1)
INSERT INTO notifications (user_id, user_type, type, title, message, link, icon, color, is_read) VALUES
(1, 'staff', 'assignment', 'Tutor Assignment Successful', 'You successfully assigned 2 tutors to COMP101 - Introduction to Programming', 'tutor-assignments.php', 'fa-user-plus', 'blue', FALSE),
(1, 'staff', 'module', 'High Risk Module Alert', 'MATH201 has been flagged as high risk with a 32% pass rate. Consider assigning additional tutors.', 'browse-modules.php', 'fa-exclamation-triangle', 'red', FALSE),
(1, 'staff', 'session', 'Session Completed', 'Sipho Mthembu completed a tutoring session for CS101 with 15 students attending', 'sessions.php', 'fa-calendar-check', 'green', TRUE);

-- For tutors/PALs (these would be created when actual events happen)
-- INSERT INTO notifications (user_id, user_type, type, title, message, link, icon, color, is_read) VALUES
-- (tutor_staff_id, 'staff', 'assignment', 'New Assignment', 'You have been assigned to COMP101 - Introduction to Programming', 'my-assignments.php', 'fa-clipboard-list', 'blue', FALSE);

SELECT 'Notifications table created successfully!' as message;
