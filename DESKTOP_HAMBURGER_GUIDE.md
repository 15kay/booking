# Desktop Hamburger Menu Guide

## Overview
The hamburger menu now works on both desktop and mobile with different behaviors:

### Desktop (>768px)
- **Click hamburger**: Collapses sidebar to 80px width (icon-only mode)
- **Main content**: Adjusts margin to accommodate collapsed sidebar
- **Tooltips**: Hover over icons to see menu item names
- **Smooth transition**: 0.3s animation for collapse/expand

### Mobile (≤768px)
- **Click hamburger**: Slides sidebar in as overlay
- **Dark backdrop**: Appears behind sidebar
- **Click outside**: Closes sidebar
- **No collapse mode**: Always full width when visible

## CSS Changes

### Sidebar Collapsed State
```css
.sidebar.collapsed {
    width: 80px;
}

.sidebar.collapsed .sidebar-header h3,
.sidebar.collapsed .sidebar-subtitle,
.sidebar.collapsed .nav-item span {
    display: none;
}

.sidebar.collapsed .sidebar-logo {
    width: 40px;
}

.sidebar.collapsed .nav-item {
    justify-content: center;
    padding: 14px 0;
}
```

### Main Content Adjustment
```css
.main-content.sidebar-collapsed {
    margin-left: 80px;
}
```

### Tooltips on Hover
```css
.sidebar.collapsed .nav-item::after {
    content: attr(data-tooltip);
    position: absolute;
    left: 100%;
    top: 50%;
    transform: translateY(-50%);
    background: var(--dark);
    color: var(--white);
    padding: 8px 12px;
    border-radius: 6px;
    white-space: nowrap;
    font-size: 13px;
    margin-left: 10px;
    opacity: 0;
    pointer-events: none;
    transition: opacity 0.3s;
    z-index: 1000;
}

.sidebar.collapsed .nav-item:hover::after {
    opacity: 1;
}
```

## JavaScript Logic

### Desktop vs Mobile Detection
```javascript
const isMobile = window.innerWidth <= 768;

if (isMobile) {
    // Mobile: overlay behavior
    sidebar.classList.toggle('active');
    document.body.classList.toggle('sidebar-open');
} else {
    // Desktop: collapse behavior
    sidebar.classList.toggle('collapsed');
    mainContent.classList.toggle('sidebar-collapsed');
}
```

### Window Resize Handler
```javascript
window.addEventListener('resize', function() {
    const isMobile = window.innerWidth <= 768;
    
    if (!isMobile) {
        // Remove mobile classes when switching to desktop
        sidebar.classList.remove('active');
        document.body.classList.remove('sidebar-open');
    } else {
        // Remove desktop classes when switching to mobile
        sidebar.classList.remove('collapsed');
        mainContent.classList.remove('sidebar-collapsed');
    }
});
```

## HTML Requirements

### Add data-tooltip attribute to nav items
```html
<a href="index.php" class="nav-item active" data-tooltip="Dashboard">
    <i class="fas fa-home"></i>
    <span>Dashboard</span>
</a>

<a href="students.php" class="nav-item" data-tooltip="Students">
    <i class="fas fa-users"></i>
    <span>Students</span>
</a>

<a href="schedule.php" class="nav-item" data-tooltip="Schedule">
    <i class="fas fa-calendar"></i>
    <span>Schedule</span>
</a>
```

## Visual States

### Expanded (Default Desktop)
```
┌─────────────────────┐
│  [Logo]             │
│  WSU Booking        │
│  Staff Portal       │
├─────────────────────┤
│  🏠 Dashboard       │
│  👥 Students        │
│  📅 Schedule        │
│  📊 Reports         │
│  ⚙️  Settings       │
├─────────────────────┤
│  🚪 Logout          │
└─────────────────────┘
Width: 250px
```

### Collapsed (Desktop)
```
┌────┐
│ 🏠 │ → Dashboard (tooltip)
│ 👥 │ → Students (tooltip)
│ 📅 │ → Schedule (tooltip)
│ 📊 │ → Reports (tooltip)
│ ⚙️ │ → Settings (tooltip)
├────┤
│ 🚪 │ → Logout (tooltip)
└────┘
Width: 80px
```

### Mobile Overlay
```
[Dark Overlay]
┌─────────────────────┐
│  [Logo]             │
│  WSU Booking        │
│  Staff Portal       │
├─────────────────────┤
│  🏠 Dashboard       │
│  👥 Students        │
│  📅 Schedule        │
│  📊 Reports         │
│  ⚙️  Settings       │
├─────────────────────┤
│  🚪 Logout          │
└─────────────────────┘
Width: 280px
Position: Overlay
```

## Benefits

### Desktop Users
- **More screen space**: Collapsed sidebar gives 170px more content width
- **Quick access**: Icons remain visible for navigation
- **Tooltips**: Hover to see full menu item names
- **Persistent state**: Stays collapsed until toggled again
- **Smooth animation**: Professional collapse/expand effect

### Mobile Users
- **Full menu**: Always see complete menu items
- **Overlay design**: Doesn't push content
- **Easy dismiss**: Tap outside to close
- **Touch-friendly**: Large tap targets

## Customization

### Change Collapsed Width
```css
.sidebar.collapsed {
    width: 60px; /* Change from 80px */
}

.main-content.sidebar-collapsed {
    margin-left: 60px; /* Match sidebar width */
}
```

### Change Tooltip Style
```css
.sidebar.collapsed .nav-item::after {
    background: #2563eb; /* Change color */
    padding: 10px 15px; /* Change size */
    font-size: 14px; /* Change font */
    border-radius: 8px; /* Change roundness */
}
```

### Change Animation Speed
```css
.sidebar {
    transition: transform 0.3s ease, width 0.5s ease; /* Slower */
}

.main-content {
    transition: margin-left 0.5s ease; /* Match sidebar */
}
```

## Accessibility

### Keyboard Navigation
- Tab through menu items
- Enter/Space to activate
- Focus visible on all items

### Screen Readers
```html
<button id="menuToggle" 
        aria-label="Toggle sidebar menu"
        aria-expanded="true">
    <i class="fas fa-bars"></i>
</button>
```

### ARIA States
```javascript
menuToggle.addEventListener('click', function() {
    const isCollapsed = sidebar.classList.contains('collapsed');
    menuToggle.setAttribute('aria-expanded', !isCollapsed);
});
```

## Browser Support

✅ Chrome/Edge (Chromium)
✅ Firefox
✅ Safari
✅ Opera

## Performance

- **GPU Accelerated**: Uses transform for animations
- **Minimal Repaints**: Only affected elements update
- **Smooth 60fps**: Optimized transitions
- **No Layout Shift**: Content adjusts smoothly

## Troubleshooting

### Issue: Sidebar doesn't collapse on desktop
**Solution**: Check if `menuToggle` and `sidebar` elements exist
```javascript
console.log('Menu Toggle:', menuToggle);
console.log('Sidebar:', sidebar);
```

### Issue: Tooltips not showing
**Solution**: Ensure `data-tooltip` attribute is set on nav items
```html
<a href="#" class="nav-item" data-tooltip="Menu Item">
```

### Issue: Content jumps when collapsing
**Solution**: Ensure transition is set on main-content
```css
.main-content {
    transition: margin-left 0.3s ease;
}
```

### Issue: Mobile overlay not working
**Solution**: Check window resize handler is removing desktop classes
```javascript
if (isMobile) {
    sidebar.classList.remove('collapsed');
    mainContent.classList.remove('sidebar-collapsed');
}
```

## Testing

### Desktop
1. Open page on desktop (>768px width)
2. Click hamburger menu
3. Sidebar should collapse to 80px
4. Content should adjust margin
5. Hover over icons to see tooltips
6. Click again to expand

### Mobile
1. Resize to mobile (<768px width)
2. Click hamburger menu
3. Sidebar should slide in as overlay
4. Dark backdrop should appear
5. Click outside to close
6. Sidebar should slide out

### Resize
1. Start on desktop with collapsed sidebar
2. Resize to mobile width
3. Collapsed state should be removed
4. Hamburger should now trigger overlay
5. Resize back to desktop
6. Overlay state should be removed
7. Hamburger should now trigger collapse

## Files Updated

- `staff/css/dashboard.css`
- `staff/js/dashboard.js`
- `student/css/dashboard.css`
- `student/js/dashboard.js`
- `admin/css/dashboard.css`
- `admin/js/dashboard.js`
- `coordinator/css/dashboard.css`
- `coordinator/js/dashboard.js`

All sections now support desktop hamburger menu!
