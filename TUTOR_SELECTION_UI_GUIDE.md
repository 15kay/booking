# Tutor Selection UI - User Guide

## Overview
The tutor selection interface shows a list of available tutors/PALs with expandable details, making it easy to review qualifications and select the best match.

## Interface Layout

### Tutor List View
Each tutor is displayed as a card with:

#### Collapsed View (Default):
- **Avatar** - Initials in colored circle (green for recommended, blue for others)
- **Name** - Full name with badges
  - "RECOMMENDED" badge (green) - Specialization matches module
  - "TUTOR" or "PAL" badge - Role indicator
- **Staff Number & Email** - Contact information
- **Quick Info**:
  - Qualification (e.g., MSc Computer Science)
  - Current workload (e.g., 2 active assignments)
- **Actions**:
  - "View Details" button - Expands to show full information
  - "Select" button - Chooses this tutor

#### Expanded View (After clicking "View Details"):
Shows three detailed sections:

**1. Personal Information**
- Staff Number
- Email address
- Phone number

**2. Qualifications**
- Full qualification details
- Specialization area
- Role type (Tutor/PAL with description)

**3. Current Workload**
- Number of active assignments
- Availability status:
  - Green: "Available - No current assignments" (0 assignments)
  - Blue: "Good availability" (1-2 assignments)
  - Orange: "Moderate workload" (3-4 assignments)
  - Red: "High workload" (5+ assignments)
- Subject match indicator (if applicable)

## Visual Indicators

### Color Coding:
- **Green border/background**: Recommended tutor (specialization matches)
- **Blue border/background**: Selected tutor
- **Gray border**: Regular tutor (not selected)

### Badges:
- **RECOMMENDED** (green, white text): Specialization matches module subject
- **TUTOR** (blue): Expert/Lecturer role
- **PAL** (yellow): Peer Assisted Learning role

## User Workflow

### Step 1: Review Tutor List
1. Page loads showing up to 10 tutors
2. Recommended tutors appear at the top with green highlighting
3. Scan the quick info to get overview

### Step 2: View Details (Optional)
1. Click "View Details" button on any tutor
2. Card expands to show full information
3. Review qualifications, specialization, and workload
4. Click "Hide Details" to collapse

### Step 3: Select Tutor
1. Click "Select" button on chosen tutor
2. Card highlights in blue
3. Page scrolls to assignment details form
4. "Assign Tutor" button becomes enabled

### Step 4: Fill Assignment Details
1. Maximum students per session (default: 20)
2. Session frequency (default: Twice weekly)
3. Start date (default: today)
4. End date (default: +3 months)
5. Location (required)
6. Notes (optional)

### Step 5: Submit
1. Click "Assign Tutor" button
2. Returns to browse modules page
3. Shows success message

## Example Tutor Cards

### Recommended Tutor (Collapsed):
```
┌─────────────────────────────────────────────────────────────┐
│ [SM]  Sipho Mthembu  [RECOMMENDED] [TUTOR]                  │
│       STAFF001 • sipho.mthembu@wsu.ac.za                     │
│       🎓 MSc Computer Science                                │
│       👨‍🏫 2 active assignments                                │
│                                                               │
│                          [View Details ▼]  [✓ Select]        │
└─────────────────────────────────────────────────────────────┘
```

### Regular Tutor (Expanded):
```
┌─────────────────────────────────────────────────────────────┐
│ [ND]  Nomsa Dlamini  [TUTOR]                                │
│       STAFF002 • nomsa.dlamini@wsu.ac.za                     │
│       🎓 MSc Mathematics                                     │
│       👨‍🏫 1 active assignment                                 │
│                                                               │
│                          [Hide Details ▲]  [✓ Select]        │
├─────────────────────────────────────────────────────────────┤
│ Personal Information    │ Qualifications      │ Workload    │
│ 📇 STAFF002            │ 🎓 MSc Mathematics  │ 📋 1 active │
│ ✉️ nomsa.dlamini@...   │ ⭐ Calculus and    │ ℹ️ Good     │
│ 📞 0834567892          │    Linear Algebra   │    availability│
│                         │ 🏷️ Tutor (Expert)  │             │
└─────────────────────────────────────────────────────────────┘
```

### Selected Tutor:
```
┌═════════════════════════════════════════════════════════════┐ (Blue border)
║ [SM]  Sipho Mthembu  [RECOMMENDED] [TUTOR]                  ║
║       STAFF001 • sipho.mthembu@wsu.ac.za                     ║
║       🎓 MSc Computer Science                                ║
║       👨‍🏫 2 active assignments                                ║
║                                                               ║
║                          [View Details ▼]  [✓ Select]        ║
└═════════════════════════════════════════════════════════════┘
```

## Sorting Logic

Tutors are displayed in this order:
1. **Recommended first** (specialization matches)
2. **Then by role** (Tutors before PALs)
3. **Then by workload** (Fewer assignments first)
4. **Then alphabetically** (By last name)

## Recommendations

### When to Choose Recommended Tutors:
- Module requires specific subject expertise
- High risk modules needing expert support
- Complex subject matter

### When to Choose PALs:
- Peer learning is beneficial
- Students need relatable support
- Budget considerations
- Building student leadership

### Workload Considerations:
- **0 assignments**: Best availability, can dedicate full attention
- **1-2 assignments**: Good balance, experienced but not overloaded
- **3-4 assignments**: Moderate workload, still manageable
- **5+ assignments**: High workload, may have limited availability

## Tips

1. **Start with recommended tutors** - They have matching specializations
2. **Check workload** - Balance between experience and availability
3. **Expand details** - Review full qualifications before selecting
4. **Consider role** - Tutors for complex topics, PALs for peer support
5. **Multiple assignments** - You can assign multiple tutors to same module

## Mobile Responsiveness

On smaller screens:
- Tutor cards stack vertically
- Details sections stack in single column
- Buttons remain accessible
- Scrolling is smooth

## Accessibility

- Keyboard navigation supported
- Screen reader friendly
- Clear visual indicators
- High contrast colors
- Large touch targets
