# Tutor/PAL Application System

## Overview
Tutors and PALs are high-performing students who apply to help their peers. They are selected based on academic excellence and approved by coordinators.

## Who Can Be a Tutor/PAL?

### Tutors (Graduate Students)
- **Requirement**: Postgraduate students (MSc, PhD)
- **Minimum GPA**: 3.70/4.00 (Excellent)
- **Role**: Expert support for complex modules
- **Compensation**: Paid position

### PALs (Peer Assisted Learning)
- **Requirement**: High-performing undergraduate students (3rd/4th year, Honours)
- **Minimum GPA**: 3.50/4.00 (Very Good)
- **Role**: Peer-to-peer learning support
- **Compensation**: Stipend or academic credit

## Academic Performance Display

### On Tutors List Page:
Each tutor/PAL card shows:
- **Student Number**: Their student ID
- **GPA**: Color-coded academic performance
  - Green (≥3.70): Excellent
  - Blue (3.50-3.69): Very Good
  - Orange (<3.50): Good
- **Qualification**: Current degree program
- **Academic Level**: Year/level of study
- **Specialization**: Area of expertise
- **Contact**: Email and phone

### On Tutor Details Page:
Comprehensive information including:
- Personal information (student #, staff #, contact)
- Academic performance section:
  - GPA with performance indicator
  - Academic year level
  - Modules they can tutor
  - Application date
  - Approval date
- Current assignments and workload

## Application Process (Conceptual)

### Step 1: Student Application
1. High-performing student applies online
2. Submits:
   - Academic transcript (GPA verification)
   - Modules they excel in
   - Availability schedule
   - Motivation letter

### Step 2: Academic Review
1. System checks GPA requirements:
   - Tutors: ≥3.70 GPA
   - PALs: ≥3.50 GPA
2. Verifies module performance (must have passed with distinction)
3. Checks academic standing (no disciplinary issues)

### Step 3: Coordinator Approval
1. Coordinator reviews application
2. Checks:
   - Academic performance
   - Module expertise match
   - Campus needs
   - Availability
3. Approves or rejects application

### Step 4: Activation
1. Approved applicant receives:
   - Staff number (TUT### or PAL###)
   - Login credentials
   - Training schedule
2. Added to tutor/PAL pool
3. Can be assigned to modules

## Sample Data Structure

### Tutor Example:
```
Staff Number: TUT001
Student Number: 220123456
Name: Sipho Mthembu
GPA: 3.85/4.00 (Excellent)
Level: Postgraduate (MSc Computer Science)
Specialization: Programming and Algorithms
Can Tutor: CS101, CS201, IT101
Application Date: Jan 15, 2024
Approval Date: Jan 20, 2024
Status: Active
```

### PAL Example:
```
Staff Number: PAL001
Student Number: 221234567
Name: Thandi Khumalo
GPA: 3.75/4.00 (Excellent)
Level: 4th Year (BSc Computer Science Honours)
Specialization: Peer Learning Facilitation
Can Tutor: CS101, IT101
Application Date: Feb 1, 2024
Approval Date: Feb 5, 2024
Status: Active
```

## Database Schema

### New Fields in `staff` Table:
```sql
student_number VARCHAR(50)      -- Their student ID
gpa DECIMAL(3,2)                -- Academic GPA (0.00-4.00)
academic_year_level VARCHAR(50) -- e.g., "4th Year", "Postgraduate"
modules_tutored TEXT            -- Comma-separated module codes
application_date DATE           -- When they applied
approval_date DATE              -- When approved
approved_by INT                 -- Coordinator who approved
```

## Installation Steps

### 1. Add Academic Fields to Database
Run in phpMyAdmin:
```sql
-- File: database/add_tutor_academic_fields.sql
```

This adds:
- student_number
- gpa
- academic_year_level
- modules_tutored
- application_date
- approval_date
- approved_by

### 2. Insert Sample Tutors/PALs
Run in phpMyAdmin:
```sql
-- File: database/insert_tutors_only.sql
```

This inserts:
- 6 Tutors (Postgraduate, GPA 3.78-3.95)
- 6 PALs (4th Year, GPA 3.68-3.85)

### 3. Verify Installation
1. Open: `http://localhost/booking/test-tutors.php`
2. Should show 12 tutors/PALs with academic data
3. Log in as coordinator
4. Go to "Tutors & PALs" page
5. See all tutors with GPA and academic info

## GPA Color Coding

### Visual Indicators:
- **Green (≥3.70)**: Excellent performance
  - Eligible for tutor positions
  - Top-tier academic standing
  
- **Blue (3.50-3.69)**: Very Good performance
  - Eligible for PAL positions
  - Strong academic standing
  
- **Orange (<3.50)**: Good performance
  - May need additional review
  - Case-by-case approval

## Benefits of This System

### For Students:
- Peer support from high-achievers
- Relatable learning experience
- Study skills development
- Academic role models

### For Tutors/PALs:
- Leadership experience
- Teaching skills development
- Financial compensation
- Resume building
- Academic credit (PALs)

### For Institution:
- Cost-effective support system
- Improved pass rates
- Student engagement
- Peer learning culture

## Future Enhancements

### Application Portal:
- Online application form
- Transcript upload
- Automated GPA verification
- Email notifications

### Performance Tracking:
- Student feedback ratings
- Session attendance
- Module pass rate improvement
- Renewal eligibility

### Scheduling System:
- Availability calendar
- Session booking
- Conflict detection
- Automated reminders

## Current Sample Data

### 6 Tutors (Postgraduate):
1. Sipho Mthembu - MSc CS (3.85 GPA)
2. Nomsa Dlamini - MSc Math (3.92 GPA)
3. Mandla Zulu - MSc Engineering (3.78 GPA)
4. Zanele Ndlovu - MSc Economics (3.88 GPA)
5. Thabo Mokoena - MSc Physics (3.95 GPA)
6. Lerato Molefe - MSc Chemistry (3.82 GPA)

### 6 PALs (4th Year):
1. Thandi Khumalo - BSc CS Honours (3.75 GPA)
2. Bongani Sithole - BSc CS Honours (3.68 GPA)
3. Lindiwe Moyo - BA Education Honours (3.72 GPA)
4. Thabo Nkosi - BCom Accounting Honours (3.80 GPA)
5. Nokuthula Dube - BSc Math Honours (3.85 GPA)
6. Mpho Radebe - BCom Economics Honours (3.77 GPA)

All passwords: `password123`
