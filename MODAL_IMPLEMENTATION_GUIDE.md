# Modal System Implementation Guide

## Overview
The WSU Booking System now uses a unified modal system to replace all `alert()` and `confirm()` dialogs with styled, professional modals.

## Files Created
1. `assets/css/modals.css` - Modal styling
2. `assets/js/modals.js` - Modal JavaScript functions
3. `assets/includes/modals.php` - Modal HTML templates

## How to Add to Any Page

### Step 1: Add CSS in `<head>` section
```php
<link rel="stylesheet" href="../assets/css/modals.css">
```

### Step 2: Include Modal HTML before `</body>`
```php
<?php include '../assets/includes/modals.php'; ?>
<script src="../assets/js/modals.js"></script>
```

### Step 3: Replace alert() and confirm() calls

#### Replace alert():
**Before:**
```javascript
alert('Operation successful!');
```

**After:**
```javascript
showMessageModal('Success', 'Operation successful!', 'success');
```

**Types:** 'success', 'error', 'info', 'warning'

#### Replace confirm():
**Before:**
```javascript
if(confirm('Are you sure?')) {
    // do something
}
```

**After:**
```javascript
showConfirmModal(
    'Confirm Action',
    'Are you sure you want to proceed?',
    function() {
        // do something
    }
);
```

## API Reference

### showMessageModal(title, message, type)
- **title**: Modal title (string)
- **message**: Message content (string)
- **type**: 'success', 'error', 'info', 'warning' (default: 'info')

### showConfirmModal(title, message, callback)
- **title**: Modal title (string)
- **message**: Confirmation message (string)
- **callback**: Function to execute on confirm

### confirmLogout()
Shows a confirmation modal before logging out. Automatically handles the correct logout path.

### closeMessageModal()
Closes the message modal

### closeConfirmModal()
Closes the confirmation modal

## Logout Confirmation
All logout links now show a confirmation modal. The system automatically detects the correct path to logout.php based on the current location.

**Updated Files:**
- student/includes/sidebar.php
- student/includes/header.php
- staff/includes/sidebar.php
- staff/includes/header.php
- admin/includes/sidebar.php
- admin/includes/header.php

## Files Already Updated
✅ student/my-bookings.php
✅ student/index.php
✅ staff/student-profile.php
✅ staff/schedule.php
✅ staff/index.php
✅ admin/index.php
✅ All sidebar and header includes (logout confirmation)

## Files That Need Updates
- student/booking-details.php
- staff/notifications.php
- staff/appointments.php
- staff/appointment-details.php
- staff/settings.php
- admin/users.php
- admin/students-management.php
- admin/staff-management.php
- admin/services.php
- admin/settings.php
- admin/reports.php

## Features
- Smooth animations (fade-in, slide-up)
- Click outside to close
- ESC key to close
- Responsive design
- Color-coded icons
- Professional styling
- Keyboard accessible
