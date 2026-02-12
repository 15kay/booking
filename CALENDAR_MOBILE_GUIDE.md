# Calendar Mobile Responsive Guide

## Quick Reference

### Desktop (>768px)
```css
.calendar-day {
    min-height: 120px;
    padding: 10px;
    gap: 10px;
}
.day-number { font-size: 18px; }
.weekday { font-size: 14px; }
```

### Tablet (≤768px)
```css
.calendar-day {
    min-height: 100px;
    padding: 8px;
    gap: 8px;
}
.day-number { font-size: 16px; }
.weekday { font-size: 12px; }
```

### Mobile (≤480px)
```css
.calendar-day {
    min-height: 70px;
    padding: 6px;
    gap: 4px;
}
.day-number { font-size: 14px; }
.weekday { font-size: 10px; }
/* 5-day: minmax(60px, 1fr) */
/* 7-day: minmax(45px, 1fr) */
```

### Landscape (≤768px)
```css
.calendar-day {
    min-height: 80px;
    gap: 6px;
}
.day-number { font-size: 15px; }
```

## Calendar Types

### 1. Schedule Calendar (5-day, Mon-Fri)
**Files**: `staff/schedule.php`, `staff/create-session.php`, `staff/staff-schedule.php`

**Structure**:
```html
<div class="calendar-header">
    <div class="calendar-title">Month Year</div>
    <div class="calendar-nav">
        <button>Previous</button>
        <button>Today</button>
        <button>Next</button>
    </div>
</div>
<div class="calendar-grid">
    <div class="calendar-weekdays">
        <div class="weekday">Mon</div>
        <!-- ... Fri -->
    </div>
    <div class="calendar-days">
        <div class="calendar-day">
            <div class="day-number">1</div>
            <div class="day-events">
                <span class="event-dot scheduled"></span>
                <!-- events -->
            </div>
        </div>
    </div>
</div>
```

**Mobile Behavior**:
- Header stacks vertically
- Navigation buttons wrap
- Grid: 5 columns, 60px minimum width
- Horizontal scroll enabled
- Touch-friendly day cells (70px height)

### 2. Booking Calendar (7-day, full week)
**Files**: `student/book-service.php`

**Structure**:
```html
<div class="calendar-header">
    <button class="calendar-nav">Prev</button>
    <div class="calendar-title">Month Year</div>
    <button class="calendar-nav">Next</button>
</div>
<div class="calendar-grid">
    <!-- 7 day headers -->
    <div class="calendar-day-header">Sun</div>
    <!-- ... -->
    <!-- 7 day cells -->
    <div class="calendar-day available">1</div>
    <!-- ... -->
</div>
```

**Mobile Behavior**:
- Compact header (12px padding)
- Grid: 7 columns, 45px minimum width
- Horizontal scroll enabled
- Smaller fonts (9px headers, 14px numbers)
- Touch-optimized selection

## Event Indicators

### Desktop
```css
.event-dot {
    width: 8px;
    height: 8px;
    margin-right: 4px;
}
```

### Mobile
```css
.event-dot {
    width: 6px;
    height: 6px;
    margin-right: 2px;
}
```

### Colors
- **Blue** (`.scheduled`): #1d4ed8 - Scheduled sessions
- **Green** (`.completed`): #10b981 - Completed sessions
- **Red** (`.cancelled`): #ef4444 - Cancelled sessions

## Interactive States

### Available Day
```css
.calendar-day.available {
    border-color: #d1fae5;
    background: #f0fdf4;
}
.calendar-day.available:hover {
    background: #10b981;
    color: white;
}
```

### Selected Day
```css
.calendar-day.selected {
    background: #1d4ed8;
    border-color: #1d4ed8;
    color: white;
}
```

### Today
```css
.calendar-day.today {
    background: #eff6ff;
    border-color: #1d4ed8;
    border-width: 3px;
}
```

### Past Day
```css
.calendar-day.past {
    background: #f9fafb;
    cursor: not-allowed;
    opacity: 0.6;
}
```

## Mobile Optimization Tips

### 1. Touch Targets
- Minimum 44x44px for clickable elements
- Day cells: 70px height on mobile (adequate)
- Navigation buttons: 44px minimum

### 2. Scrolling
```css
.calendar-grid {
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
}
```

### 3. Text Sizing
- Never go below 9px for readability
- Use relative units where possible
- Scale proportionally across breakpoints

### 4. Grid Layout
```css
/* Mobile - Fixed minimum, flexible maximum */
grid-template-columns: repeat(5, minmax(60px, 1fr));

/* Tablet - More breathing room */
grid-template-columns: repeat(5, 1fr);
gap: 8px;

/* Desktop - Spacious */
grid-template-columns: repeat(5, 1fr);
gap: 10px;
```

### 5. Performance
- Use `transform` for animations (GPU accelerated)
- Avoid `width`/`height` animations
- Use `will-change` sparingly
- Debounce scroll events

## Common Issues & Solutions

### Issue: Calendar too wide on mobile
**Solution**: Add horizontal scroll
```css
@media (max-width: 480px) {
    .calendar-grid {
        overflow-x: auto;
    }
    .calendar-days {
        grid-template-columns: repeat(5, minmax(60px, 1fr));
    }
}
```

### Issue: Day numbers too small
**Solution**: Increase font size on mobile
```css
@media (max-width: 480px) {
    .day-number {
        font-size: 14px;
        font-weight: 700;
    }
}
```

### Issue: Events not visible
**Solution**: Adjust event dot size and spacing
```css
@media (max-width: 480px) {
    .event-dot {
        width: 6px;
        height: 6px;
    }
    .day-events {
        gap: 2px;
        font-size: 10px;
    }
}
```

### Issue: Navigation buttons cramped
**Solution**: Stack or wrap buttons
```css
@media (max-width: 480px) {
    .calendar-nav {
        flex-wrap: wrap;
        gap: 8px;
    }
    .calendar-nav .btn {
        padding: 8px 12px;
        font-size: 12px;
    }
}
```

## Testing Checklist

- [ ] Test on iPhone SE (375px width)
- [ ] Test on iPhone 12/13 (390px width)
- [ ] Test on iPhone 14 Pro Max (430px width)
- [ ] Test on Android (360px typical)
- [ ] Test on iPad (768px)
- [ ] Test on iPad Pro (1024px)
- [ ] Test landscape orientation
- [ ] Test touch interactions
- [ ] Test scroll behavior
- [ ] Test day selection
- [ ] Test navigation buttons
- [ ] Test event indicators visibility
- [ ] Test with long month names
- [ ] Test with many events per day

## Browser-Specific Notes

### iOS Safari
- Use `-webkit-overflow-scrolling: touch` for momentum scrolling
- Test touch event handling
- Check for rubber-band effect

### Android Chrome
- Test touch ripple effects
- Verify scroll performance
- Check for layout shifts

### Firefox Mobile
- Test grid layout rendering
- Verify touch target sizes
- Check font rendering

## Accessibility

### Screen Readers
```html
<div class="calendar-day" 
     role="button" 
     aria-label="Monday, January 1, 2024"
     tabindex="0">
    <div class="day-number">1</div>
</div>
```

### Keyboard Navigation
- Tab through days
- Enter/Space to select
- Arrow keys for navigation (if implemented)

### Color Contrast
- Ensure 4.5:1 ratio for text
- Use patterns in addition to colors
- Test with color blindness simulators

## Performance Metrics

### Target Metrics
- First Paint: <1s
- Time to Interactive: <2s
- Smooth scrolling: 60fps
- Touch response: <100ms

### Optimization
- Lazy load calendar data
- Use CSS transforms for animations
- Minimize repaints
- Debounce scroll events
- Use requestAnimationFrame for updates
