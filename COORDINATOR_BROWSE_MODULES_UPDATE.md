# Coordinator Browse Modules - Updated

## Changes Made

### 1. Updated Page Purpose
- Changed from "flag modules" to "assign tutors/PALs"
- Coordinators now focus on assigning support staff to modules based on risk category

### 2. Updated Table Columns
Added new column:
- **Assigned Tutors**: Shows how many tutors/PALs are currently assigned to each module
  - Displays count with green checkmark if tutors are assigned
  - Shows "None" in gray if no tutors assigned

### 3. Updated Action Buttons

#### For Already Flagged Modules (At-Risk):
- **Button**: "Assign Tutor" (blue, prominent)
- **Action**: Takes coordinator directly to assign-tutor page
- **Icon**: User-plus icon

#### For Not Yet Flagged Modules:
- **Button**: "Flag & Assign" (gray, dashed border)
- **Action**: Takes coordinator to flag-module page first
- **Icon**: Flag icon

### 4. Visual Improvements
- Blue "Assign Tutor" button stands out for quick action
- Shows tutor assignment status at a glance
- Clear distinction between modules that need flagging vs. ready for assignment

## Coordinator Workflow

### Scenario 1: Module Already At-Risk
1. Browse modules page shows module with risk category
2. "Assigned Tutors" column shows current assignments (or "None")
3. Click "Assign Tutor" button
4. Goes directly to assignment page

### Scenario 2: Module Not Yet Flagged
1. Browse modules page shows module with risk category
2. "Assigned Tutors" column shows "None"
3. Click "Flag & Assign" button
4. Goes to flag-module page to mark as at-risk
5. After flagging, can assign tutors

## Table Structure

| Column | Description |
|--------|-------------|
| Subject Code | Module code (e.g., CS101) |
| Subject Name | Full module name |
| Faculty | Faculty name |
| Year | Academic year |
| Pass Rate | Percentage with color coding |
| Students | Passed/Total headcount |
| Risk Category | Badge showing risk level |
| Assigned Tutors | Count of assigned tutors/PALs |
| Action | "Assign Tutor" or "Flag & Assign" button |

## Risk Category Color Coding

- **High Risk** (<40%): Red badge
- **Moderate Risk** (40-59%): Orange badge
- **Low Risk** (60-74%): Yellow badge
- **Very Low Risk** (≥75%): Green badge

## Button Styles

### Assign Tutor Button
- Background: Blue (#3b82f6)
- Text: White
- Hover: Darker blue with shadow
- Purpose: Primary action for at-risk modules

### Flag & Assign Button
- Background: Light gray
- Border: Dashed gray
- Text: Gray
- Hover: Darker gray
- Purpose: Secondary action for modules not yet flagged

## Technical Details

### Query Changes
- Now fetches `risk_id` for at-risk modules
- Counts assigned tutors per module
- Passes correct `risk_id` to assign-tutor page

### Parameters
- At-risk modules: `assign-tutor.php?id={risk_id}`
- Not flagged: `flag-module.php?id={module_id}`

## Testing Checklist

- [ ] Log in as coordinator
- [ ] Browse modules shows all columns correctly
- [ ] "Assigned Tutors" column shows accurate counts
- [ ] "Assign Tutor" button appears for at-risk modules
- [ ] "Flag & Assign" button appears for non-flagged modules
- [ ] Clicking "Assign Tutor" goes to correct assignment page
- [ ] Clicking "Flag & Assign" goes to flag-module page
- [ ] Only campus-specific modules are shown (after logout/login)
