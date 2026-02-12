# Tutor/PAL Login & Dashboard Guide

## ✅ COMPLETED

### Login System
- Tutors and PALs can now log in via staff login page
- Authentication routes them to dedicated tutor dashboard
- Session management for tutor/PAL role

### Tutor Dashboard Created
- **Location:** `tutor/index.php`
- **Access:** Tutors and PALs only

## 📊 Dashboard Features

### Statistics Cards
1. **Active Assignments** - Current module assignments
2. **Total Sessions** - All sessions conducted
3. **Upcoming Sessions** - Scheduled future sessions
4. **Students Reached** - Total student registrations

### My Assignments Section
- Lists all active module assignments
- Shows module code, name, faculty, campus
- Displays student count and assignment period
- "View" button for assignment details

### Upcoming Sessions Section
- Shows next 5 scheduled sessions
- Displays date, time, module, topic, location
- Shows registered student count
- "View" button for session details

## 🔐 Login Credentials

### Sample Tutors (8 total)
All use password: `password123`

| Staff # | Student # | Name | Role | GPA | Year |
|---------|-----------|------|------|-----|------|
| TUT001 | 220123456 | Sipho Mthembu | Tutor | 3.65 | 3rd |
| TUT002 | 220123457 | Nomsa Dlamini | Tutor | 3.72 | 3rd |
| TUT003 | 220123458 | Mandla Zulu | Tutor | 3.58 | 2nd |
| TUT004 | 220123459 | Zanele Ndlovu | Tutor | 3.68 | 3rd |
| TUT005 | 220123460 | Thabo Mokoena | Tutor | 3.75 | 3rd |
| TUT006 | 220123461 | Lerato Molefe | Tutor | 3.62 | 2nd |
| TUT007 | 220123462 | Kagiso Mabaso | Tutor | 3.70 | 3rd |
| TUT008 | 220123463 | Palesa Nkomo | Tutor | 3.55 | 2nd |

### Sample PALs (8 total)
All use password: `password123`

| Staff # | Student # | Name | Role | GPA | Year |
|---------|-----------|------|------|-----|------|
| PAL001 | 221234567 | Thandi Khumalo | PAL | 3.75 | 4th |
| PAL002 | 221234568 | Bongani Sithole | PAL | 3.68 | 3rd |
| PAL003 | 221234569 | Lindiwe Moyo | PAL | 3.82 | 4th |
| PAL004 | 221234570 | Thabo Nkosi | PAL | 3.80 | 4th |
| PAL005 | 221234571 | Nokuthula Dube | PAL | 3.85 | 4th |
| PAL006 | 221234572 | Mpho Radebe | PAL | 3.77 | 3rd |
| PAL007 | 221234573 | Sello Mahlangu | PAL | 3.73 | 4th |
| PAL008 | 221234574 | Refilwe Tshabalala | PAL | 3.65 | 3rd |

## 🎯 How to Login

1. Go to: `http://localhost/booking/staff-login.php`
2. Enter Staff Number (e.g., TUT001 or PAL001)
3. Enter Password: `password123`
4. Click "Login"
5. You'll be redirected to the tutor dashboard

## 📁 File Structure

```
tutor/
├── index.php                 # Dashboard
├── includes/
│   ├── header.php           # Header component
│   └── sidebar.php          # Sidebar navigation
├── css/
│   └── dashboard.css        # Styling
└── js/
    └── dashboard.js         # JavaScript
```

## 🔄 Navigation Menu

The tutor sidebar includes:
- **Dashboard** - Overview and statistics
- **My Assignments** - View all module assignments
- **My Sessions** - Manage tutoring sessions
- **Schedule** - View and manage schedule
- **Students** - View registered students
- **Profile** - View tutor profile
- **Settings** - Update account settings
- **Logout** - Sign out

## 📋 Next Steps to Complete

### Pages to Create:
1. **my-assignments.php** - Detailed list of all assignments
2. **assignment-details.php** - Individual assignment details
3. **my-sessions.php** - All sessions (past, present, future)
4. **session-details.php** - Individual session details
5. **schedule.php** - Calendar view of sessions
6. **students.php** - List of students in sessions
7. **profile.php** - Tutor profile with academic info
8. **settings.php** - Account settings

### Features to Implement:
- Create new sessions for assignments
- Mark attendance for sessions
- View student registrations
- Update session status
- Add session notes/feedback
- View performance metrics

## 💡 Key Differences: Tutor vs PAL

### Tutors
- Undergraduate students (2nd/3rd year)
- General tutoring support
- GPA: 3.55-3.75
- Help with coursework and assignments

### PALs (Peer Assisted Learning Leaders)
- Senior students (3rd/4th year)
- For historically difficult subjects (low pass rates)
- GPA: 3.65-3.85
- Attend classes WITH students
- Lead PAL sessions to help students master material AND learn how to learn

## 🚀 Testing

1. **Login as Tutor:**
   - Staff Number: TUT001
   - Password: password123

2. **Login as PAL:**
   - Staff Number: PAL001
   - Password: password123

3. **Verify:**
   - Dashboard loads correctly
   - Statistics show (will be 0 until assignments are made)
   - Navigation menu works
   - Role badge shows "Tutor" or "Pal"

## ⚠️ Prerequisites

Before tutors/PALs can see data:
1. Run SQL scripts to create tutors/PALs
2. Coordinator must assign tutors to modules
3. Sessions must be created for assignments

## 📝 Notes

- All tutors and PALs are undergraduate students
- They have student numbers (not just staff numbers)
- Dashboard shows only their own assignments and sessions
- Data is filtered by tutor_id in all queries
- Empty states shown when no data available
