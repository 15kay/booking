# WSU Campus & Faculty Distribution

## Campus Structure

### 🏛️ MTHATHA CAMPUS
**Coordinator:** COORD001 - Dr. Themba Nkosi

**Faculties:**
1. **Faculty of Law, Humanities and Social Sciences**
   - School of Law (LAW101, LAW201)
   - School of Social Sciences (SOC101)
   - School of Humanities (HUM101)

2. **Faculty of Medicine and Health Sciences**
   - School of Medicine (MED101, MED201)
   - School of Nursing (NUR101)
   - School of Public Health (PUB101)

**Sample Modules:** 8 modules
**High Risk Modules:** LAW201, HUM101, MED101

---

### 🏛️ EAST LONDON CAMPUS
**Coordinator:** COORD002 - Dr. Nomsa Dlamini

**Faculties:**
1. **Faculty of Natural Sciences**
   - School of Mathematics and Computer Science (CS101, MATH101, MATH201)
   - School of Chemistry and Physics (CHEM101, PHYS101)

2. **Faculty of Engineering, Built Environment and Information Technology**
   - School of Information Technology (IT101, IT102, IT201)
   - School of Engineering (ENG101, ENG201)

**Sample Modules:** 10 modules
**High Risk Modules:** MATH101, IT101, ENG101

---

### 🏛️ BUTTERWORTH CAMPUS
**Coordinator:** COORD003 - Dr. Sipho Mthembu

**Faculties:**
1. **Faculty of Education**
   - School of General and Further Education and Training (EDU101, EDU201)
   - School of Postgraduate Studies (EDU301)
   - School of Early Childhood Education (EDU102)

2. **Faculty of Management and Public Administration Sciences**
   - School of Business Management (BUS101, BUS201)
   - School of Public Administration (PAD101, PAD201)

**Sample Modules:** 8 modules
**High Risk Modules:** BUS101

---

### 🏛️ QUEENSTOWN CAMPUS
**Coordinator:** COORD004 - Dr. Thandi Khumalo

**Faculties:**
1. **Faculty Of Economic And Financial Sciences**
   - School of Economics (ECON101, ECON201)
   - School of Accounting (ACC101, ACC201)
   - School of Finance (FIN101)

**Sample Modules:** 5 modules
**High Risk Modules:** ECON101, ACC101

---

## Coordinator Access Summary

| Campus | Coordinator | Total Modules | Faculties | High Risk |
|--------|-------------|---------------|-----------|-----------|
| Mthatha | Dr. Themba Nkosi | 8 | 2 | 3 |
| East London | Dr. Nomsa Dlamini | 10 | 2 | 3 |
| Butterworth | Dr. Sipho Mthembu | 8 | 2 | 1 |
| Queenstown | Dr. Thandi Khumalo | 5 | 1 | 2 |

---

## Data Isolation Rules

✅ Each coordinator sees ONLY their campus modules
✅ Each coordinator manages ALL faculties in their campus
✅ Cannot view or manage modules from other campuses
✅ Tutor assignments filtered by campus
✅ Sessions filtered by campus
✅ Reports filtered by campus

---

## Login Credentials

All coordinators use password: **password123**

- **COORD001** - Mthatha Campus
- **COORD002** - East London Campus
- **COORD003** - Butterworth Campus
- **COORD004** - Queenstown Campus

---

## Installation

Run these SQL scripts in order:
```bash
mysql -u root -p wsu_booking < database/coordinator_schema.sql
mysql -u root -p wsu_booking < database/alter_modules_simple.sql
mysql -u root -p wsu_booking < database/sample_modules_data.sql
mysql -u root -p wsu_booking < database/add_coordinator_assignments.sql
```

The system is now ready with the correct campus-faculty distribution!
