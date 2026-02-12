# Install Tutors & PALs - Step by Step Guide

## Problem
The Tutors & PALs page only shows tutors, not PALs.

## Solution
You need to run 2 SQL scripts in order:

### Step 1: Add Academic Fields to Staff Table
This adds the new columns needed for academic performance tracking.

**File:** `database/add_tutor_academic_fields.sql`

**In phpMyAdmin:**
1. Open phpMyAdmin: `http://localhost/phpmyadmin`
2. Select `wsu_booking` database
3. Click "SQL" tab
4. Copy and paste the entire contents of `database/add_tutor_academic_fields.sql`
5. Click "Go"

**What it does:**
- Adds `student_number` column
- Adds `gpa` column
- Adds `academic_year_level` column
- Adds `modules_tutored` column
- Adds `application_date` column
- Adds `approval_date` column
- Adds `approved_by` column

### Step 2: Insert Tutors and PALs
This inserts 8 tutors and 8 PALs with academic data.

**File:** `database/insert_tutors_only.sql`

**In phpMyAdmin:**
1. Still in `wsu_booking` database
2. Click "SQL" tab
3. Copy and paste the entire contents of `database/insert_tutors_only.sql`
4. Click "Go"

**What it inserts:**

**8 Tutors (Undergraduate students):**
- TUT001 - Sipho Mthembu (BSc CS, 3rd Year, GPA 3.65)
- TUT002 - Nomsa Dlamini (BSc Math, 3rd Year, GPA 3.72)
- TUT003 - Mandla Zulu (BEng, 2nd Year, GPA 3.58)
- TUT004 - Zanele Ndlovu (BCom Econ, 3rd Year, GPA 3.68)
- TUT005 - Thabo Mokoena (BSc Physics, 3rd Year, GPA 3.75)
- TUT006 - Lerato Molefe (BSc Chem, 2nd Year, GPA 3.62)
- TUT007 - Kagiso Mabaso (BCom Acc, 3rd Year, GPA 3.70)
- TUT008 - Palesa Nkomo (BA Ed, 2nd Year, GPA 3.55)

**8 PALs (Senior students - PAL Leaders):**
- PAL001 - Thandi Khumalo (BSc CS, 4th Year, GPA 3.75) - Programming
- PAL002 - Bongani Sithole (BSc CS, 3rd Year, GPA 3.68) - Database Systems
- PAL003 - Lindiwe Moyo (BSc Math, 4th Year, GPA 3.82) - Calculus
- PAL004 - Thabo Nkosi (BCom Acc, 4th Year, GPA 3.80) - Financial Accounting
- PAL005 - Nokuthula Dube (BEng, 4th Year, GPA 3.85) - Engineering Math
- PAL006 - Mpho Radebe (BCom Econ, 3rd Year, GPA 3.77) - Microeconomics
- PAL007 - Sello Mahlangu (BSc Physics, 4th Year, GPA 3.73) - Physics I
- PAL008 - Refilwe Tshabalala (BCom BM, 3rd Year, GPA 3.65) - Business Principles

### Step 3: Verify Installation

**Option A: Use Test Page**
1. Open: `http://localhost/booking/test-tutors.php`
2. Should show:
   - Total: 16 (8 tutors + 8 PALs)
   - Breakdown by role

**Option B: Check in phpMyAdmin**
```sql
SELECT role, COUNT(*) as count 
FROM staff 
WHERE role IN ('tutor', 'pal') 
GROUP BY role;
```

Should show:
- tutor: 8
- pal: 8

### Step 4: View in Coordinator Dashboard
1. Log in as coordinator (COORD001, password: password123)
2. Go to "Tutors & PALs" page
3. Should see all 16 tutors/PALs
4. Filter tabs should show:
   - All (16)
   - Tutors (8)
   - PALs (8)

## Troubleshooting

### Still only showing tutors?

**Check 1: Verify PALs exist in database**
```sql
SELECT * FROM staff WHERE role = 'pal';
```
If this returns 0 rows, the insert script didn't run.

**Check 2: Check for errors in SQL execution**
- Look at the phpMyAdmin output after running the script
- Any red error messages?
- Common issue: Column doesn't exist (means Step 1 wasn't run)

**Check 3: Clear browser cache**
- Press Ctrl+F5 to hard refresh
- Or clear browser cache completely

**Check 4: Check the tutors.php query**
The query should be:
```php
WHERE s.role IN ('tutor', 'pal') AND s.status = 'active'
```

### Error: "Unknown column 'student_number'"
This means Step 1 wasn't completed. Run `add_tutor_academic_fields.sql` first.

### Error: "Duplicate entry for key 'PRIMARY'"
This means tutors/PALs already exist. The script deletes them first, but if you get this error:
```sql
DELETE FROM staff WHERE role IN ('tutor', 'pal');
```
Then run the insert script again.

## Quick Check Commands

**Count tutors and PALs:**
```sql
SELECT 
    role,
    COUNT(*) as count
FROM staff 
WHERE role IN ('tutor', 'pal')
GROUP BY role;
```

**List all tutors:**
```sql
SELECT staff_number, first_name, last_name, role, gpa, academic_year_level
FROM staff 
WHERE role = 'tutor'
ORDER BY last_name;
```

**List all PALs:**
```sql
SELECT staff_number, first_name, last_name, role, gpa, academic_year_level, specialization
FROM staff 
WHERE role = 'pal'
ORDER BY last_name;
```

## Expected Result

After completing all steps, you should have:
- **16 total** tutors/PALs
- **8 tutors** (undergraduate students, 2nd-3rd year)
- **8 PALs** (senior students, 3rd-4th year, for low pass rate modules)
- All with academic performance data (GPA, year level, etc.)
- All visible on the Tutors & PALs page
- Filter tabs working correctly

## All Passwords
All tutors and PALs use password: `password123`
