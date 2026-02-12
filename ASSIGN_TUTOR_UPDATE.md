# Assign Tutor Page - Student Number Update

## ✅ CHANGES COMPLETED

### Updated Display Fields

The assign-tutor page now correctly shows **student numbers** instead of staff numbers for tutors and PALs, since they are all undergraduate students.

### 1. Database Query Update
- **Added fields:** `student_number`, `gpa`, `academic_year_level`
- Now fetches academic performance data for tutors/PALs

### 2. Tutor List Display (Main View)
**Before:**
- Showed staff number only
- No GPA or year level

**After:**
- Shows student number (falls back to staff_number if not available)
- Displays GPA with color coding:
  - Green: GPA ≥ 3.7
  - Blue: GPA ≥ 3.5
  - Orange: GPA < 3.5
- Shows academic year level (2nd Year, 3rd Year, 4th Year)

### 3. Tutor Details (Expanded View)
**Personal Information Section:**
- Changed "Staff Number" → "Student Number"
- Added GPA display with color coding
- Added Year Level display
- Kept email and phone

**Role Description:**
- Tutor: "Undergraduate Tutor" (instead of "Expert/Lecturer")
- PAL: "PAL Leader - Peer Assisted Learning" (more descriptive)

### 4. Quick Info Display
Now shows:
- Qualification (e.g., BSc Computer Science)
- Academic year level (e.g., 3rd Year)
- Current assignments count

## 📋 WHAT IT LOOKS LIKE NOW

### Tutor Card Header
```
Sipho Mthembu [RECOMMENDED] [TUTOR]
220123456 • sipho.mthembu@wsu.ac.za • GPA: 3.65
📚 BSc Computer Science • 👨‍🎓 3rd Year • 👨‍🏫 2 active assignments
```

### PAL Card Header
```
Thandi Khumalo [RECOMMENDED] [PAL]
221234567 • thandi.khumalo.pal@wsu.ac.za • GPA: 3.75
📚 BSc Computer Science • 👨‍🎓 4th Year • 👨‍🏫 1 active assignment
```

### Personal Information (Expanded)
```
👤 Personal Information
🆔 Student Number: 220123456
📊 GPA: 3.65/4.00 (color-coded)
👨‍🎓 Year Level: 3rd Year
✉️ Email: sipho.mthembu@wsu.ac.za
📞 Phone: 0834567891
```

### Role Display
```
Tutor: "Undergraduate Tutor"
PAL: "PAL Leader - Peer Assisted Learning"
```

## 🎯 KEY FEATURES

### Student Number Priority
- Shows `student_number` if available
- Falls back to `staff_number` if student_number is NULL
- Ensures backward compatibility

### GPA Color Coding
- **Green (≥3.7):** Excellent academic performance
- **Blue (≥3.5):** Good academic performance
- **Orange (<3.5):** Acceptable academic performance

### Academic Year Display
- Shows year level (2nd, 3rd, 4th Year)
- Helps coordinators understand experience level
- PALs are typically 3rd/4th year (senior students)
- Tutors are typically 2nd/3rd year

### Role Clarity
- Tutors: Undergraduate students providing tutoring
- PALs: Senior students (PAL Leaders) who attend classes WITH students and lead PAL sessions

## 🔍 VERIFICATION

After running the SQL scripts, the page will show:

### For Tutors (8 total)
- Student numbers: 220123456 - 220123463
- GPAs: 3.55 - 3.75
- Year levels: 2nd Year, 3rd Year
- Role: "Undergraduate Tutor"

### For PALs (8 total)
- Student numbers: 221234567 - 221234574
- GPAs: 3.65 - 3.85
- Year levels: 3rd Year, 4th Year
- Role: "PAL Leader - Peer Assisted Learning"

## 📁 FILES UPDATED

- `coordinator/assign-tutor.php` - Main assignment page

## 💡 NOTES

- All tutors and PALs are undergraduate students
- Student numbers clearly identify them as students
- GPA and year level provide academic context
- PALs are senior students (3rd/4th year) for historically difficult subjects
- Tutors are 2nd/3rd year students for general tutoring
- The page maintains backward compatibility if student_number is NULL

## 🚀 NEXT STEPS

1. Run SQL scripts to populate tutors/PALs data
2. Log in as coordinator
3. Go to Browse Modules
4. Click "Assign Tutor" on any module
5. Verify student numbers, GPAs, and year levels display correctly
6. Test the "View Details" expansion
7. Select a tutor and complete assignment
