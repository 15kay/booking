# Coordinator & Tutor/PAL Management System Implementation Guide

## Overview
This system adds a **Coordinator** role that can identify at-risk modules and assign **Tutors** and **PALs (Peer Assisted Learning)** to help struggling students.

## Database Changes

### 1. Run the SQL Script
Execute `database/coordinator_schema.sql` to create:

**New Tables:**
- `modules` - All university modules
- `at_risk_modules` - Modules identified as needing intervention
- `tutor_assignments` - Tutor/PAL assignments to at-risk modules
- `tutor_sessions` - Individual tutoring sessions
- `session_registrations` - Student registrations for sessions
- `tutor_performance` - Performance tracking for tutors

**Updated Tables:**
- `staff` role now includes: 'coordinator', 'tutor', 'pal'

### 2. Sample Data Included
- 5 sample modules (CS101, MATH101, etc.)
- 1 coordinator (STF006 - Dr. Themba Nkosi)
- 2 tutors (TUT001, TUT002)
- 2 PALs (PAL001, PAL002)
- 3 at-risk modules
- 4 tutor assignments
- 4 sample sessions

**Login Credentials (all users):**
- Password: `password123`

## System Features

### Coordinator Dashboard Features

1. **At-Risk Module Management**
   - View all at-risk modules
   - Add new at-risk modules
   - Set risk levels (low, medium, high, critical)
   - Track failure rates and student numbers
   - Monitor intervention status

2. **Tutor/PAL Assignment**
   - Assign tutors to at-risk modules
   - Assign PALs to at-risk modules
   - Set assignment duration
   - Define session frequency
   - Set maximum students per tutor
   - Track assignment status

3. **Session Management**
   - View all tutoring sessions
   - Create new sessions
   - Set session capacity
   - Track attendance
   - Monitor session completion

4. **Performance Tracking**
   - Tutor performance metrics
   - Attendance rates
   - Student feedback ratings
   - Sessions completed vs scheduled
   - Monthly performance reports

5. **Reporting & Analytics**
   - At-risk module trends
   - Intervention effectiveness
   - Tutor utilization
   - Student participation rates
   - Success rate improvements

### Tutor/PAL Dashboard Features

1. **My Assignments**
   - View assigned modules
   - See at-risk student lists
   - Access module details

2. **Session Management**
   - Create tutoring sessions
   - Manage session schedule
   - Set session topics
   - Define capacity limits

3. **Attendance Tracking**
   - Mark student attendance
   - Track participation
   - Record session notes

4. **Student Interaction**
   - View registered students
   - Provide feedback
   - Track individual progress

5. **Performance Dashboard**
   - View own performance metrics
   - See attendance statistics
   - Review student ratings

### Student Features

1. **Browse Available Sessions**
   - View tutoring sessions for their modules
   - See tutor/PAL information
   - Check session availability

2. **Register for Sessions**
   - Book tutoring sessions
   - Receive confirmations
   - Get reminders

3. **Track Attendance**
   - View attended sessions
   - See upcoming sessions
   - Access session materials

4. **Provide Feedback**
   - Rate tutoring sessions
   - Leave comments
   - Help improve services

## File Structure

```
coordinator/
├── index.php                    # Dashboard
├── at-risk-modules.php         # View/manage at-risk modules
├── add-at-risk-module.php      # Add new at-risk module
├── assign-tutor.php            # Assign tutor/PAL to module
├── tutor-assignments.php       # View all assignments
├── sessions.php                # View all sessions
├── performance.php             # Performance reports
├── reports.php                 # Analytics and reports
├── includes/
│   ├── header.php
│   └── sidebar.php
├── css/
│   └── dashboard.css
└── js/
    └── dashboard.js

tutor/
├── index.php                    # Tutor dashboard
├── my-assignments.php          # View assignments
├── sessions.php                # Manage sessions
├── create-session.php          # Create new session
├── attendance.php              # Mark attendance
├── students.php                # View students
└── performance.php             # View own performance
```

## Key Workflows

### 1. Identifying At-Risk Module
```
Coordinator → At-Risk Modules → Add New
→ Select Module
→ Set Risk Level
→ Enter Failure Rate
→ Describe Intervention Needed
→ Save
```

### 2. Assigning Tutor/PAL
```
Coordinator → At-Risk Modules → View Module
→ Assign Tutor/PAL
→ Select Tutor Type (Tutor/PAL)
→ Choose Staff Member
→ Set Duration & Frequency
→ Set Max Students
→ Assign
```

### 3. Creating Tutoring Session
```
Tutor → My Assignments → Select Module
→ Create Session
→ Set Date & Time
→ Define Topic
→ Set Capacity
→ Set Location
→ Save
```

### 4. Student Registration
```
Student → Browse Sessions
→ Select Module
→ View Available Sessions
→ Register for Session
→ Receive Confirmation
```

### 5. Marking Attendance
```
Tutor → Sessions → Select Session
→ Mark Attendance
→ Check Present Students
→ Add Session Notes
→ Submit
```

## Risk Levels

- **Low (0-25% failure rate)**: Monitoring required
- **Medium (26-40% failure rate)**: Regular tutoring recommended
- **High (41-55% failure rate)**: Intensive intervention needed
- **Critical (>55% failure rate)**: Urgent comprehensive support required

## Performance Metrics

### Tutor Performance
- Total sessions conducted
- Average attendance rate
- Student satisfaction rating
- Sessions completed vs scheduled
- Number of students helped

### Module Performance
- Failure rate trends
- Student participation in tutoring
- Improvement in pass rates
- Intervention effectiveness

## Next Steps

1. **Run Database Script**
   ```sql
   mysql -u root -p wsu_booking < database/coordinator_schema.sql
   ```

2. **Test Login**
   - Coordinator: STF006 / password123
   - Tutor: TUT001 / password123
   - PAL: PAL001 / password123

3. **Create Coordinator Dashboard**
   - Copy staff dashboard structure
   - Modify for coordinator features
   - Add at-risk module management
   - Add tutor assignment interface

4. **Create Tutor Dashboard**
   - Similar to staff dashboard
   - Focus on session management
   - Add attendance tracking
   - Add student interaction features

5. **Extend Student Dashboard**
   - Add "Tutoring Sessions" menu item
   - Create session browsing interface
   - Add registration functionality
   - Add feedback system

## Benefits

1. **Proactive Intervention**: Identify struggling modules early
2. **Targeted Support**: Assign appropriate tutors/PALs
3. **Track Effectiveness**: Monitor intervention success
4. **Resource Optimization**: Allocate tutors efficiently
5. **Student Success**: Improve pass rates through support
6. **Data-Driven Decisions**: Use metrics to improve programs

## Integration with Existing System

- Uses existing authentication system
- Extends staff roles
- Integrates with student records
- Uses existing notification system
- Follows same UI/UX patterns
- Fully responsive design

