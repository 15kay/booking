# Attendance Marking Guide for Tutors/PALs

## Overview
Tutors and PALs can mark attendance for their tutoring sessions to track student participation and engagement.

---

## 3 Ways to Mark Attendance

### Method 1: From "My Sessions" Page (QUICKEST)

1. **Navigate:** Click "My Sessions" in the sidebar
2. **Find Session:** Locate the session you want to mark attendance for
3. **Click Button:** Click the green "Mark Attendance" button
4. **Mark Students:** Toggle Present/Absent for each student
5. **Save:** Click "Save Attendance"

**Best for:** Quick access when you know which session you need

---

### Method 2: From Session Details Page

1. **Navigate:** Go to "My Sessions" → Click "View Details" on a session
2. **Review Info:** See full session details and registered students
3. **Click Button:** Click green "Mark Attendance" button at the bottom
4. **Mark Students:** Toggle Present/Absent for each student
5. **Save:** Click "Save Attendance"

**Best for:** When you want to review session details first

---

### Method 3: From Calendar View

1. **Navigate:** Go to "My Assignments" → Click "Schedule Sessions" on an assignment
2. **View Calendar:** See all your scheduled sessions on the calendar
3. **Click Session:** Click on a session dot in the calendar
4. **Modal Opens:** Session details appear in a popup
5. **Click Button:** Click "Mark Attendance" button in the modal
6. **Mark Students:** Toggle Present/Absent for each student
7. **Save:** Click "Save Attendance"

**Best for:** Visual overview of all sessions and quick access

---

## The Attendance Marking Interface

### What You'll See:

**Session Banner (Top)**
- Session topic and description
- Module code and name
- Date, time, and location
- Campus and faculty

**Attendance Statistics**
- Total Registered: Number of students who signed up
- Present: Count updates as you mark students
- Absent: Count updates as you mark students

**Student List Table**
- Student avatar (initials)
- Full name and student number
- Year of study
- Present/Absent toggle buttons
- Current status badge

### How to Mark:

1. **For Each Student:**
   - Click "Present" button (turns green) OR
   - Click "Absent" button (turns red)
   - Status badge updates automatically
   - Counts update in real-time

2. **Visual Feedback:**
   - Present: Green button, green status badge
   - Absent: Red button, red status badge
   - Unmarked: Gray buttons, gray status badge

3. **Validation:**
   - System checks all students are marked
   - Warning appears if any student is unmarked
   - Cannot save until all students have attendance marked

4. **Save:**
   - Click "Save Attendance" button
   - Session automatically marked as "completed"
   - Success message appears
   - Redirected back to attendance page

---

## After Marking Attendance

### What Happens:
- ✓ Attendance records saved to database
- ✓ Session status changed to "completed"
- ✓ Timestamp recorded for when attendance was marked
- ✓ Attendance statistics visible on session details
- ✓ Reports can be generated with attendance data

### View/Edit Attendance Later:
- For completed sessions, button changes to "View Attendance"
- You can still update attendance if needed
- Useful for corrections or late arrivals

---

## Tips & Best Practices

### Before the Session:
- Check registered students list
- Prepare attendance sheet if needed
- Note session location and time

### During the Session:
- Take note of who attends
- Mark late arrivals appropriately
- Document any issues or absences

### After the Session:
- Mark attendance as soon as possible
- Add any notes about student participation
- Review attendance statistics

### For PALs (Peer-Assisted Learning):
- Attend class WITH students
- Mark attendance for study sessions
- Track engagement in group activities
- Report patterns to coordinator

### For Tutors:
- Mark attendance for tutoring sessions
- Track student progress over time
- Identify students who need extra support
- Use attendance data for reports

---

## Attendance Statistics

### Where to See Stats:
1. **Session Details Page:** Shows attendance rate and count
2. **My Sessions List:** Progress bar showing attendance percentage
3. **Session Reports:** Detailed attendance breakdown
4. **Assignment Details:** Overall attendance across all sessions

### What Stats Show:
- Total registered students
- Number attended
- Number absent
- Attendance percentage
- Attendance rate visualization (progress bar)

---

## Troubleshooting

### "Session not found or unauthorized"
- You can only mark attendance for YOUR sessions
- Check you're logged in as the correct tutor/PAL
- Verify the session belongs to your assignment

### "Cannot save - unmarked students"
- All students must be marked as Present or Absent
- Check for gray status badges (unmarked)
- Mark all students before saving

### Button not showing
- Only scheduled and completed sessions show the button
- Cancelled sessions cannot have attendance marked
- Check session status on details page

### Students not showing
- Students must register for the session first
- Check "Registered Students" section
- Students are NOT automatically registered when session is created

---

## Quick Reference

| Action | Location | Button Color | Icon |
|--------|----------|--------------|------|
| Mark Attendance (Scheduled) | My Sessions, Session Details, Calendar | Green | Clipboard-Check |
| View Attendance (Completed) | My Sessions, Session Details, Calendar | Blue/Info | Clipboard-Check |
| View Details | My Sessions, Calendar | Blue | Eye |
| Cancel Session | My Sessions, Session Details | Red | Times |
| View Report | My Sessions (completed only) | Gray | File-Alt |

---

## Database Fields Updated

When you mark attendance, these fields are updated:

**session_registrations table:**
- `attended` - TRUE (present) or FALSE (absent)
- `status` - 'attended' or 'absent'
- `attendance_marked_at` - Timestamp of when marked

**tutor_sessions table:**
- `status` - Changed to 'completed' after attendance saved

---

## Need Help?

- Contact your coordinator if you have issues
- Check session details to verify information
- Ensure students are registered before marking attendance
- Use "View Attendance" to review past sessions
