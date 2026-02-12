# Coordinator Main Workflow

## Core Responsibilities

### 1. VIEW MODULES & RISK CATEGORIES
**Pages:** Browse Modules, Flagged Modules
- View all modules from their campus/faculty
- See pass rates and automatic risk categorization:
  - **High Risk**: < 40% pass rate (RED)
  - **Moderate Risk**: 40-59% pass rate (ORANGE)
  - **Low Risk**: 60-74% pass rate (YELLOW)
  - **Very Low Risk**: ≥ 75% pass rate (GREEN)
- Filter by risk level, year, search
- Flag modules that need intervention

**Status:** ✅ COMPLETE

---

### 2. ASSIGN TUTORS & PALs
**Pages:** Assign Tutor, Tutor Assignments
- Assign tutors to high/moderate risk modules
- Assign PALs (Peer Assisted Learning) to support students
- Set:
  - Assignment duration (start/end dates)
  - Session frequency (e.g., "Twice weekly")
  - Maximum students per tutor
  - Location
  - Notes/instructions
- View all active assignments

**Status:** ⚠️ NEEDS: assign-tutor.php, tutor-assignments.php

---

### 3. MONITOR TUTORS & PALs
**Pages:** Tutors & PALs, Tutor Performance
- View all tutors and PALs in their faculty
- See tutor workload and assignments
- Monitor:
  - Sessions conducted
  - Student attendance
  - Session completion rates
  - Student feedback/ratings
- Track tutor availability
- View performance metrics

**Status:** ⚠️ NEEDS: tutors.php, tutor-performance tracking

---

### 4. GENERATE REPORTS
**Pages:** Reports, Analytics
- Module performance reports
- Tutor effectiveness reports
- Student participation reports
- Intervention impact analysis
- Pass rate improvement tracking
- Export reports (PDF/Excel)

**Status:** ⚠️ NEEDS: reports.php

---

## Current System Status

### ✅ COMPLETED:
1. Dashboard - Overview with statistics
2. Browse Modules - View all modules with risk categories
3. Flag Module - Mark modules for intervention
4. Flagged Modules - View at-risk modules
5. Module Details - Detailed view of flagged module
6. Coordinator assignments by campus/faculty
7. Responsive design
8. Modal system

### 🔨 TO BUILD:
1. **assign-tutor.php** - Form to assign tutors/PALs to modules
2. **tutor-assignments.php** - View all tutor assignments
3. **tutors.php** - Manage tutors and PALs
4. **sessions.php** - View all tutoring sessions
5. **reports.php** - Generate reports and analytics

---

## Simplified Menu Structure

```
Coordinator Dashboard
├── Dashboard (Overview)
├── Browse Modules (View & categorize)
├── Flagged Modules (At-risk modules)
├── Assign Tutors (Add tutors/PALs) ⚠️ TO BUILD
├── Tutor Assignments (View assignments) ⚠️ TO BUILD
├── Tutors & PALs (Monitor tutors) ⚠️ TO BUILD
├── Sessions (View sessions) ⚠️ TO BUILD
└── Reports (Generate reports) ⚠️ TO BUILD
```

---

## Next Steps

Build the remaining 5 pages to complete the coordinator system:
1. Assign Tutor page
2. Tutor Assignments page
3. Tutors & PALs management page
4. Sessions overview page
5. Reports & Analytics page
