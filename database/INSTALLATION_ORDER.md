# WSU Coordinator System - Database Installation Guide

## Installation Order

Run these SQL scripts in the following order:

### Step 1: Run Coordinator Schema
This creates the base tables for the coordinator system.

```bash
mysql -u root -p wsu_booking < database/coordinator_schema.sql
```

**What it does:**
- Updates staff roles to include 'coordinator', 'tutor', 'pal'
- Creates modules table (basic structure)
- Creates at_risk_modules table
- Creates tutor_assignments table
- Creates tutor_sessions table
- Creates session_registrations table
- Creates tutor_performance table

---

### Step 2: Alter Modules Table
This adds the pass rate tracking columns to the modules table.

```bash
mysql -u root -p wsu_booking < database/alter_modules_simple.sql
```

**What it does:**
- Adds academic_year, campus, faculty, school columns
- Adds subject_code, subject_name, subject_area columns
- Adds subjects_passed, headcount, subject_pass_rate columns
- Adds risk_category column (auto-calculated)
- Creates triggers to calculate risk_category automatically
- Updates at_risk_modules table with pass_rate, campus, faculty, school columns

---

### Step 3: Insert Sample Modules Data
This populates the database with sample modules from all WSU campuses.

```bash
mysql -u root -p wsu_booking < database/sample_modules_data.sql
```

**What it does:**
- Inserts 25+ sample modules across all 4 campuses
- Covers all 7 faculties
- Includes realistic pass rates (some high risk, some low risk)
- Creates 7 coordinators (one per campus/faculty combination)
- All coordinators use password: `password123`

---

### Step 4: Add Coordinator Assignments
This assigns each coordinator to their specific campus and faculty.

```bash
mysql -u root -p wsu_booking < database/add_coordinator_assignments.sql
```

**What it does:**
- Adds assigned_campus and assigned_faculty columns to staff table
- Assigns each coordinator to their campus/faculty:
  - COORD001: Mthatha / IT & Engineering
  - COORD002: Mthatha / Health Sciences
  - COORD003: Mthatha / Economic Sciences
  - COORD004: East London / Natural Sciences
  - COORD005: East London / Management Sciences
  - COORD006: Butterworth / Education
  - COORD007: Queenstown / Law & Humanities

---

## Verification

After running all scripts, verify the installation:

```sql
-- Check modules
SELECT COUNT(*) as total_modules, 
       SUM(CASE WHEN risk_category = 'High Risk' THEN 1 ELSE 0 END) as high_risk
FROM modules;

-- Check coordinators
SELECT staff_number, CONCAT(first_name, ' ', last_name) as name, 
       assigned_campus, assigned_faculty 
FROM staff 
WHERE role = 'coordinator';

-- Check at-risk modules
SELECT COUNT(*) as total_at_risk FROM at_risk_modules;
```

---

## Coordinator Login Credentials

All coordinators use the same password: **password123**

| Staff Number | Name | Campus | Faculty |
|--------------|------|--------|---------|
| COORD001 | Dr. Themba Nkosi | Mthatha | IT & Engineering |
| COORD002 | Dr. Nomsa Dlamini | Mthatha | Health Sciences |
| COORD003 | Dr. Sipho Mthembu | Mthatha | Economic Sciences |
| COORD004 | Dr. Thandi Khumalo | East London | Natural Sciences |
| COORD005 | Dr. Bongani Sithole | East London | Management Sciences |
| COORD006 | Dr. Zanele Ndlovu | Butterworth | Education |
| COORD007 | Dr. Mandla Zulu | Queenstown | Law & Humanities |

---

## Testing

1. Login as any coordinator (e.g., COORD004 / password123)
2. Go to "Browse Modules" - you should only see modules from their campus/faculty
3. Flag a high-risk module for intervention
4. Go to "Flagged Modules" to see at-risk modules
5. Assign tutors to the flagged module

---

## Troubleshooting

**Error: Column already exists**
- Skip that script or comment out the ALTER TABLE statements

**Error: Trigger already exists**
- Drop existing triggers first:
```sql
DROP TRIGGER IF EXISTS calculate_risk_category_insert;
DROP TRIGGER IF EXISTS calculate_risk_category_update;
```

**No modules showing for coordinator**
- Check their assignment:
```sql
SELECT assigned_campus, assigned_faculty FROM staff WHERE staff_number = 'COORD001';
```
- Verify modules exist for that campus/faculty:
```sql
SELECT COUNT(*) FROM modules WHERE campus = 'Mthatha' AND faculty LIKE '%Engineering%';
```

---

## Complete Installation Command

Run all scripts in one go:

```bash
mysql -u root -p wsu_booking < database/coordinator_schema.sql
mysql -u root -p wsu_booking < database/alter_modules_simple.sql
mysql -u root -p wsu_booking < database/sample_modules_data.sql
mysql -u root -p wsu_booking < database/add_coordinator_assignments.sql
```

Done! The coordinator system is now ready to use.
