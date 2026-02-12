# Coordinator Campus Filter - Troubleshooting Guide

## Issue
The browse-modules page is showing all faculties instead of only the faculties from the coordinator's assigned campus.

## Root Cause
The coordinator logged in BEFORE the campus assignments were added to the database. The session still contains the old data without the `assigned_campus` field.

## Solution
**You need to log out and log back in** to refresh your session with the updated campus assignment.

### Steps:
1. Click on your profile in the top-right corner
2. Click "Logout"
3. Log back in with your coordinator credentials
4. The browse-modules page will now show only faculties from your campus

## Verification
After logging back in, you should see:
- Your campus name displayed in the header (e.g., "Mthatha", "East London", etc.)
- A blue info box on the browse-modules page showing "Your Campus: [Campus Name] Campus (All faculties in your campus)"
- The faculty dropdown should only show faculties from your campus

## Campus-Faculty Distribution

### Mthatha Campus
- Faculty of Law, Humanities and Social Sciences
- Faculty of Medicine and Health Sciences

### East London Campus
- Faculty of Natural Sciences
- Faculty of Engineering, Built Environment and Information Technology

### Butterworth Campus
- Faculty of Education
- Faculty of Management and Public Administration Sciences

### Queenstown Campus
- Faculty Of Economic And Financial Sciences

## Coordinator Login Credentials
All coordinators use password: `password123`

- **COORD001** - Dr. Themba Nkosi (Mthatha Campus)
- **COORD002** - Dr. Nomsa Dlamini (East London Campus)
- **COORD003** - Dr. Sipho Mthembu (Butterworth Campus)
- **COORD004** - Dr. Thandi Khumalo (Queenstown Campus)

## Debug Tool
If you're still experiencing issues, visit: `coordinator/debug-session.php`

This page will show:
- Your current session variables
- Whether assigned_campus is set
- The faculties query being executed
- All modules grouped by campus
- Your staff record from the database

## Technical Details
The filtering is working correctly in the code:

```php
// Filter faculties by coordinator's campus
$faculties_query = "
    SELECT DISTINCT faculty FROM modules 
    WHERE faculty IS NOT NULL AND faculty != '' AND status = 'active'
";

if(isset($_SESSION['assigned_campus']) && $_SESSION['assigned_campus']) {
    $faculties_query .= " AND campus = ?";
    $faculties_params[] = $_SESSION['assigned_campus'];
}
```

The issue is simply that the session needs to be refreshed after the database was updated with campus assignments.
