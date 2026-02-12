# Student Auto-Enrollment System

## Overview
Students are automatically enrolled in modules from the existing university database. When tutors/PALs create tutoring sessions, all students enrolled in that module are automatically available for attendance marking - no manual registration required.

---

## How It Works

### 1. Student Module Enrollment
- Students are enrolled in modules via the `student_modules` table
- This table links students to their registered modules
- Data comes from the existing university registration system
- Enrollment includes: student_id, module_id, academic_year, semester, status

### 2. Automatic Session Attendance List
When a tutor/PAL marks attendance for a session:
- System automatically fetches ALL students enrolled in that module
- No need for students to manually register for tutoring sessions
- All enrolled students appear in the attendance list
- Tutor marks each student as Present or Absent

### 3. Attendance Recording
- First time marking: Creates a `session_registrations` record automatically
- Subsequent times: Updates the existing record
- Records: attended (TRUE/FALSE), status (attended/absent), timestamp

---

## Database Structure

### student_modules Table
```sql
CREATE TABLE student_modules (
    enrollment_id INT AUTO_INCREMENT PRIMARY KEY,
    student_id VARCHAR(20) NOT NULL,
    module_id INT NOT NULL,
    academic_year INT NOT NULL,
    semester TINYINT NOT NULL,
    enrollment_date DATE NOT NULL,
    status ENUM('active', 'dropped', 'completed', 'failed') DEFAULT 'active',
    final_mark DECIMAL(5,2) NULL,
    FOREIGN KEY (student_id) REFERENCES students(student_id),
    FOREIGN KEY (module_id) REFERENCES modules(module_id),
    UNIQUE KEY unique_enrollment (student_id, module_id, academic_year, semester)
);
```

### session_registrations Table (Auto-Created)
```sql
CREATE TABLE session_registrations (
    registration_id INT AUTO_INCREMENT PRIMARY KEY,
    session_id INT NOT NULL,
    student_id VARCHAR(20) NOT NULL,
    registration_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    attended BOOLEAN DEFAULT FALSE,
    attendance_marked_at TIMESTAMP NULL,
    feedback TEXT,
    rating TINYINT CHECK (rating BETWEEN 1 AND 5),
    status ENUM('registered', 'attended', 'absent', 'cancelled') DEFAULT 'registered',
    FOREIGN KEY (session_id) REFERENCES tutor_sessions(session_id),
    FOREIGN KEY (student_id) REFERENCES students(student_id),
    UNIQUE KEY unique_registration (session_id, student_id)
);
```

---

## Workflow

### For Coordinators:
1. Identify at-risk module (e.g., CS101)
2. Assign tutor/PAL to the module
3. System knows which students are enrolled in CS101

### For Tutors/PALs:
1. Create tutoring session for assigned module
2. Go to "Mark Attendance"
3. See ALL students enrolled in the module (automatically)
4. Mark each student as Present or Absent
5. Save attendance

### For Students:
- No action required!
- Already enrolled in modules via university system
- Automatically appear in attendance lists
- Can view their own attendance records

---

## Key Features

### Automatic Population
- ✓ All enrolled students automatically shown
- ✓ No manual registration needed
- ✓ Matches university enrollment data
- ✓ Updates in real-time

### Smart Record Creation
- First attendance mark: Creates registration record
- Subsequent marks: Updates existing record
- Tracks attendance history
- Records timestamps

### Data Integrity
- Links to official university enrollment
- Prevents duplicate registrations
- Maintains audit trail
- Supports reporting

---

## Sample Data

### Module Enrollments (from student_modules_schema.sql):
- **CS101**: 20 students enrolled (Semester 1, 2024)
- **MATH101**: 20 students enrolled (Semester 1, 2024)
- **IT102**: 15 students enrolled (Semester 2, 2024)

### Student IDs:
- 220234501 through 220234520 (sample students)
- Enrolled in various combinations of modules
- Some students take multiple modules

---

## Installation

### Step 1: Create student_modules Table
```bash
mysql -u root -p wsu_booking < database/student_modules_schema.sql
```

This creates:
- `student_modules` table
- Sample enrollments for CS101, MATH101, IT102
- Links students to modules

### Step 2: Verify Enrollment
```sql
SELECT 
    m.subject_code,
    m.subject_name,
    COUNT(sm.enrollment_id) as enrolled_students
FROM student_modules sm
INNER JOIN modules m ON sm.module_id = m.module_id
WHERE sm.status = 'active'
GROUP BY m.module_id;
```

### Step 3: Test Attendance Marking
1. Login as tutor (TUT001 or PAL001)
2. Go to "My Assignments"
3. Click "Schedule Sessions"
4. Create a session
5. Click "Mark Attendance"
6. See all enrolled students automatically!

---

## Benefits

### For Tutors/PALs:
- No need to manually add students
- Complete class list automatically
- Easy to track who attended
- Quick attendance marking

### For Coordinators:
- Accurate attendance data
- Matches official enrollment
- Easy reporting
- No data entry errors

### For Students:
- No registration hassle
- Automatic inclusion
- Can't be "forgotten"
- Fair and consistent

### For System:
- Single source of truth
- Data integrity
- Audit trail
- Scalable

---

## Technical Details

### Query Logic
The mark-attendance.php page uses this query:
```sql
SELECT 
    s.student_id,
    s.first_name, 
    s.last_name, 
    s.email, 
    s.year_of_study,
    sr.registration_id,
    sr.attended,
    sr.attendance_marked_at,
    sr.status as attendance_status,
    COALESCE(sr.status, 'not_marked') as status
FROM student_modules sm
INNER JOIN students s ON sm.student_id = s.student_id
INNER JOIN at_risk_modules arm ON sm.module_id = arm.module_id
INNER JOIN tutor_assignments ta ON arm.risk_id = ta.risk_module_id
INNER JOIN tutor_sessions ts ON ta.assignment_id = ts.assignment_id
LEFT JOIN session_registrations sr ON (
    sr.session_id = ts.session_id 
    AND sr.student_id = s.student_id
)
WHERE ts.session_id = ?
AND sm.status = 'active'
AND sm.academic_year = YEAR(ts.session_date)
ORDER BY s.last_name, s.first_name
```

### Key Points:
- Starts with `student_modules` (enrollment)
- Joins to get student details
- Links to session via module → assignment → session
- LEFT JOIN to `session_registrations` (may not exist yet)
- Filters by active enrollment and matching academic year
- Orders alphabetically by last name

---

## Future Enhancements

### Possible Additions:
1. **Import from University System**: Automated sync with student information system
2. **Enrollment Verification**: Check if student is still enrolled before marking
3. **Dropped Students**: Handle students who drop modules mid-semester
4. **Multiple Sections**: Support for different sections of same module
5. **Waitlists**: Handle students on waiting lists
6. **Prerequisites**: Check if students meet prerequisites
7. **Attendance Alerts**: Notify students with low attendance
8. **Bulk Import**: CSV import for enrollment data

---

## Troubleshooting

### "No students showing"
- Check if `student_modules` table exists
- Verify students are enrolled in the module
- Check enrollment status is 'active'
- Verify academic year matches session date

### "Wrong students showing"
- Check module_id matches
- Verify academic year and semester
- Check enrollment dates
- Review at-risk module assignment

### "Can't save attendance"
- Check `session_registrations` table exists
- Verify foreign key constraints
- Check student_id format matches
- Review error logs

---

## Related Files

- `database/student_modules_schema.sql` - Creates enrollment table
- `staff/mark-attendance.php` - Attendance marking interface
- `database/coordinator_schema.sql` - Session registrations table
- `ATTENDANCE_MARKING_GUIDE.md` - User guide for tutors

---

## Summary

The auto-enrollment system eliminates manual student registration for tutoring sessions. Students enrolled in a module automatically appear in attendance lists when tutors create sessions. This ensures:

✓ Complete attendance lists  
✓ No missing students  
✓ Accurate data  
✓ Less administrative work  
✓ Better tracking  
✓ Simplified workflow  

The system creates a seamless connection between university enrollment and tutoring attendance, making it easy for tutors to track participation and for coordinators to monitor program effectiveness.
