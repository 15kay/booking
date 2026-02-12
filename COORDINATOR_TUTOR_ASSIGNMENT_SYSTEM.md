# Coordinator Tutor Assignment System - Complete Guide

## Overview
Coordinators can browse modules and assign tutors/PALs based on risk category and student count. The system provides intelligent recommendations and smart tutor matching.

## Key Features

### 1. Browse Modules Page
- View all modules in coordinator's campus
- See risk categories and pass rates
- Get tutor recommendations based on risk level
- Track assigned vs. recommended tutors
- Direct "Assign Tutor" action button

### 2. Smart Tutor Recommendations

#### Recommendation Formula:
- **High Risk** (<40% pass rate): 1 tutor per 15 students
- **Moderate Risk** (40-59%): 1 tutor per 20 students
- **Low Risk** (60-74%): 1 tutor per 30 students
- **Very Low Risk** (≥75%): Minimum 1 tutor

#### Example:
- Module with 120 students at High Risk → Recommend 8 tutors
- Module with 100 students at Moderate Risk → Recommend 5 tutors
- Module with 90 students at Low Risk → Recommend 3 tutors

### 3. Intelligent Tutor Matching

When assigning tutors, the system:
- **Prioritizes tutors with matching specializations** (marked as "RECOMMENDED")
- Shows tutor qualifications and expertise
- Displays current workload (active assignments)
- Filters by role (Tutor vs PAL)
- Orders by: Subject match → Role → Workload → Name

### 4. Auto-Flagging
- Coordinators **cannot manually flag modules**
- Modules are **automatically flagged** when a tutor is assigned
- System creates at-risk entry in background
- No separate flagging workflow needed

## Browse Modules Table

| Column | Description |
|--------|-------------|
| Subject Code | Module identifier (e.g., CS101) |
| Subject Name | Full module name |
| Faculty | Faculty name |
| Year | Academic year |
| Pass Rate | Percentage with color coding |
| Students | Passed/Total headcount |
| Risk Category | Badge showing risk level |
| **Recommended** | Number of tutors recommended |
| **Assigned** | Current tutor count with status |
| Action | "Assign Tutor" button |

### Assigned Column Status:
- **Green**: Fully staffed (assigned ≥ recommended)
- **Orange**: Understaffed (assigned < recommended) - shows "Need X more"
- **Red**: No tutors assigned

## Assign Tutor Page

### Module Information Card
Shows:
- Subject code and name
- Faculty
- Academic year
- Total students
- Pass rate
- Risk category

### Recommendation Box
Displays:
- Recommended number of tutors
- Current assignment status
- How many more tutors needed

### Tutor Selection Grid
Each tutor card shows:
- **Name and staff number**
- **Role badge** (TUTOR or PAL)
- **Qualification** (e.g., MSc Computer Science)
- **Specialization** (e.g., Programming and Algorithms)
- **Current workload** (number of active assignments)
- **"RECOMMENDED" badge** if specialization matches module

### Assignment Details Form
- Maximum students per session (default: 20)
- Session frequency (Once/Twice/Three times weekly, Daily, As needed)
- Start date (default: today)
- End date (default: +3 months)
- Location (e.g., Computer Lab A, Room 101)
- Notes/Instructions (optional)

## Workflow

### Scenario 1: Assign Tutor to Module
1. Coordinator logs in and goes to "Browse Modules"
2. Views modules filtered by their campus
3. Sees module with High Risk (35% pass rate, 120 students)
4. System recommends 8 tutors
5. Currently shows "0 assigned" in red
6. Clicks "Assign Tutor" button
7. System auto-flags module as at-risk (background)
8. Shows tutor selection page with recommended tutors highlighted
9. Selects tutor with matching specialization
10. Fills in assignment details
11. Clicks "Assign Tutor"
12. Returns to browse page with success message
13. Module now shows "1 assigned (Need 7 more)" in orange

### Scenario 2: Add More Tutors
1. Module already has 3 tutors assigned
2. System recommends 8 tutors
3. Shows "3 assigned (Need 5 more)" in orange
4. Clicks "Assign Tutor" again
5. Selects another tutor
6. Assigns successfully
7. Now shows "4 assigned (Need 4 more)"

### Scenario 3: Fully Staffed Module
1. Module has 8 tutors assigned
2. System recommends 8 tutors
3. Shows "8 assigned" in green (no "Need more" message)
4. Can still click "Assign Tutor" to add more if needed

## Tutor Matching Logic

### Priority Order:
1. **Subject Match**: Tutors whose specialization contains the module's subject area
2. **Role**: Tutors before PALs
3. **Workload**: Fewer assignments first (balanced distribution)
4. **Name**: Alphabetical

### Example:
Module: CS101 (Computer Science)

**Recommended Tutors** (shown first):
- Sipho Mthembu - MSc Computer Science, "Programming and Algorithms" ✓
- Thandi Khumalo - BSc Computer Science (Honours), "Peer Learning" ✓

**Other Tutors** (shown after):
- Nomsa Dlamini - MSc Mathematics, "Calculus and Linear Algebra"
- Mandla Zulu - MSc Engineering, "Engineering Mathematics"

## Technical Details

### Database Flow:
1. Coordinator clicks "Assign Tutor" with `module_id` and `year`
2. System checks if module is in `at_risk_modules` table
3. If not, creates entry automatically with:
   - module_id, academic_year, semester
   - campus, faculty
   - at_risk_students = headcount
   - reason = "Low pass rate - requires tutor support"
   - status = 'active'
4. Gets or creates `risk_id`
5. Shows tutor selection page
6. On submit, creates entry in `tutor_assignments` table with `risk_id`

### Query for Smart Matching:
```sql
SELECT 
    s.staff_id, s.staff_number, s.first_name, s.last_name, 
    s.email, s.phone, s.role, s.specialization, s.qualification,
    COUNT(ta.assignment_id) as current_assignments,
    CASE WHEN s.specialization LIKE '%CS%' THEN 1 ELSE 0 END as subject_match
FROM staff s
LEFT JOIN tutor_assignments ta ON s.staff_id = ta.tutor_id AND ta.status = 'active'
WHERE s.role IN ('tutor', 'pal') AND s.status = 'active'
GROUP BY s.staff_id
ORDER BY subject_match DESC, s.role ASC, current_assignments ASC, s.last_name ASC
```

## Campus Data Isolation

### Important:
- Each coordinator sees ONLY modules from their assigned campus
- Mthatha coordinator sees only Mthatha modules
- East London coordinator sees only East London modules
- Etc.

### Verification:
After logging in, coordinator should see:
- Campus name in header: "📍 Mthatha"
- Blue info box on browse page: "Your Campus: Mthatha Campus (All faculties in your campus)"
- Only faculties from their campus in dropdown

## Testing Checklist

### Browse Modules Page:
- [ ] Shows only campus-specific modules
- [ ] "Recommended" column shows correct tutor count
- [ ] "Assigned" column shows current assignments
- [ ] Color coding: Red (none), Orange (understaffed), Green (fully staffed)
- [ ] "Assign Tutor" button works for all modules

### Assign Tutor Page:
- [ ] Module information displays correctly
- [ ] Recommendation box shows correct tutor count
- [ ] Tutors with matching specialization marked as "RECOMMENDED"
- [ ] Tutor cards show all details (qualification, specialization, workload)
- [ ] Can select tutor by clicking card
- [ ] Submit button disabled until tutor selected
- [ ] Form validation works
- [ ] Successfully assigns tutor and returns to browse page

### Auto-Flagging:
- [ ] Module not in at_risk_modules before assignment
- [ ] After assignment, module appears in at_risk_modules
- [ ] risk_id created automatically
- [ ] Can assign multiple tutors to same module

### Data Isolation:
- [ ] Coordinator only sees their campus modules
- [ ] Faculty dropdown only shows their campus faculties
- [ ] Cannot access other campus data

## Sample Data

### Coordinators:
- COORD001 (Mthatha) - password: password123
- COORD002 (East London) - password: password123
- COORD003 (Butterworth) - password: password123
- COORD004 (Queenstown) - password: password123

### Sample Tutors:
- TUT001 - Sipho Mthembu (MSc Computer Science)
- TUT002 - Nomsa Dlamini (MSc Mathematics)
- TUT003 - Mandla Zulu (MSc Engineering)
- TUT004 - Zanele Ndlovu (MSc Economics)

### Sample PALs:
- PAL001 - Thandi Khumalo (BSc Computer Science Honours)
- PAL002 - Bongani Sithole (BSc Computer Science Honours)
- PAL003 - Lindiwe Moyo (BA Education Honours)
- PAL004 - Thabo Nkosi (BCom Accounting Honours)

## Notes

- Coordinators **cannot flag modules manually** - flagging happens automatically
- System recommends tutors based on risk + student count
- Tutors with matching specializations are prioritized
- Workload is balanced across tutors
- Multiple tutors can be assigned to same module
- Assignment details can be customized per tutor
