<?php
session_start();
if(!isset($_SESSION['student_id'])) {
    header('Location: ../index.php');
    exit();
}

require_once '../config/database.php';

$db = new Database();
$conn = $db->connect();

// Get all service categories with services
$stmt = $conn->query("
    SELECT sc.category_id, sc.category_name, sc.description, sc.icon, sc.display_order, sc.status, sc.created_at, COUNT(s.service_id) as service_count
    FROM service_categories sc
    LEFT JOIN services s ON sc.category_id = s.category_id AND s.status = 'active'
    WHERE sc.status = 'active'
    GROUP BY sc.category_id, sc.category_name, sc.description, sc.icon, sc.display_order, sc.status, sc.created_at
    ORDER BY sc.display_order
");
$categories = $stmt->fetchAll();

// Get selected category services
$selected_category = isset($_GET['category']) ? $_GET['category'] : null;
$services = [];
if($selected_category) {
    $stmt = $conn->prepare("
        SELECT * FROM services 
        WHERE category_id = ? AND status = 'active'
        ORDER BY service_name
    ");
    $stmt->execute([$selected_category]);
    $services = $stmt->fetchAll();
}

// Get selected service details
$selected_service = isset($_GET['service']) ? $_GET['service'] : null;
$service_details = null;
$available_staff = [];
if($selected_service) {
    $stmt = $conn->prepare("SELECT * FROM services WHERE service_id = ?");
    $stmt->execute([$selected_service]);
    $service_details = $stmt->fetch();
    
    // Get available staff for this service
    $stmt = $conn->prepare("
        SELECT DISTINCT st.* 
        FROM staff st
        JOIN staff_schedules ss ON st.staff_id = ss.staff_id
        WHERE ss.service_id = ? AND st.status = 'active' AND ss.status = 'active'
    ");
    $stmt->execute([$selected_service]);
    $available_staff = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Service - WSU Booking</title>
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="dashboard-layout">
        <?php include 'includes/sidebar.php'; ?>
        
        <div class="main-content">
            <?php include 'includes/header.php'; ?>
            
            <div class="content">
                <!-- Hero Section -->
                <div class="hero-section">
                    <div class="hero-content">
                        <h1>Book Your Service</h1>
                        <p>Access professional support services tailored to your needs. Choose from counseling, academic advising, career guidance, and more.</p>
                    </div>
                    <div class="hero-stats">
                        <div class="hero-stat">
                            <i class="fas fa-calendar-check"></i>
                            <span>Easy Booking</span>
                        </div>
                        <div class="hero-stat">
                            <i class="fas fa-user-md"></i>
                            <span>Expert Staff</span>
                        </div>
                        <div class="hero-stat">
                            <i class="fas fa-clock"></i>
                            <span>Flexible Times</span>
                        </div>
                    </div>
                </div>

                <!-- Step Indicator -->
                <div class="steps">
                    <div class="step <?php echo !$selected_category ? 'active' : 'completed'; ?>">
                        <div class="step-number">1</div>
                        <span>Choose Category</span>
                    </div>
                    <div class="step <?php echo $selected_category && !$selected_service ? 'active' : ($selected_service ? 'completed' : ''); ?>">
                        <div class="step-number">2</div>
                        <span>Select Service</span>
                    </div>
                    <div class="step <?php echo $selected_service ? 'active' : ''; ?>">
                        <div class="step-number">3</div>
                        <span>Book Appointment</span>
                    </div>
                </div>

                <?php if(!$selected_category): ?>
                <!-- Step 1: Service Categories -->
                <div class="section">
                    <h2 class="section-title">Select Service Category</h2>
                    <div class="categories-grid">
                        <?php foreach($categories as $category): ?>
                        <a href="?category=<?php echo $category['category_id']; ?>" class="category-card">
                            <div class="category-icon">
                                <i class="fas <?php echo htmlspecialchars($category['icon']); ?>"></i>
                            </div>
                            <h3><?php echo htmlspecialchars($category['category_name']); ?></h3>
                            <p><?php echo htmlspecialchars($category['description']); ?></p>
                            <span class="service-count"><?php echo $category['service_count']; ?> Services</span>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>

                <?php elseif(!$selected_service): ?>
                <!-- Step 2: Services List -->
                <div class="section">
                    <div class="section-header">
                        <h2 class="section-title">Select a Service</h2>
                        <a href="book-service.php" class="btn-link"><i class="fas fa-arrow-left"></i> Back to Categories</a>
                    </div>
                    <div class="services-list">
                        <?php foreach($services as $service): ?>
                        <a href="?category=<?php echo $selected_category; ?>&service=<?php echo $service['service_id']; ?>" class="service-card">
                            <div class="service-header">
                                <h3><?php echo htmlspecialchars($service['service_name']); ?></h3>
                                <span class="duration"><i class="fas fa-clock"></i> <?php echo $service['duration_minutes']; ?> min</span>
                            </div>
                            <p><?php echo htmlspecialchars($service['description']); ?></p>
                            <div class="service-footer">
                                <span class="service-code"><?php echo htmlspecialchars($service['service_code']); ?></span>
                                <span class="book-btn">Book Now <i class="fas fa-arrow-right"></i></span>
                            </div>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>

                <?php else: ?>
                <!-- Step 3: Booking Form -->
                <div class="section">
                    <div class="section-header">
                        <h2 class="section-title">Book Appointment</h2>
                        <a href="?category=<?php echo $selected_category; ?>" class="btn-link"><i class="fas fa-arrow-left"></i> Back to Services</a>
                    </div>
                    
                    <div class="booking-container">
                        <div class="service-summary">
                            <h3><?php echo htmlspecialchars($service_details['service_name']); ?></h3>
                            <p><?php echo htmlspecialchars($service_details['description']); ?></p>
                            <div class="summary-details">
                                <div class="detail-item">
                                    <i class="fas fa-clock"></i>
                                    <span>Duration: <?php echo $service_details['duration_minutes']; ?> minutes</span>
                                </div>
                                <div class="detail-item">
                                    <i class="fas fa-calendar"></i>
                                    <span>Book up to <?php echo $service_details['max_advance_booking_days']; ?> days in advance</span>
                                </div>
                                <div class="detail-item">
                                    <i class="fas fa-info-circle"></i>
                                    <span>Cancel <?php echo $service_details['cancellation_hours']; ?> hours before</span>
                                </div>
                            </div>
                        </div>

                        <form action="process-booking.php" method="POST" class="booking-form">
                            <input type="hidden" name="service_id" value="<?php echo $service_details['service_id']; ?>">
                            
                            <div class="form-group">
                                <label><i class="fas fa-user-md"></i> Select Staff Member</label>
                                <select name="staff_id" id="staffSelect" required>
                                    <option value="">Choose a staff member</option>
                                    <?php foreach($available_staff as $staff): ?>
                                    <option value="<?php echo $staff['staff_id']; ?>">
                                        <?php echo htmlspecialchars($staff['first_name'] . ' ' . $staff['last_name']); ?>
                                        <?php if($staff['specialization']): ?>
                                        - <?php echo htmlspecialchars($staff['specialization']); ?>
                                        <?php endif; ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label><i class="fas fa-calendar"></i> Preferred Date</label>
                                <input type="hidden" name="booking_date" id="dateSelect" required>
                                <div id="calendarContainer">
                                    <div class="calendar-header">
                                        <button type="button" class="calendar-nav" id="prevMonth">
                                            <i class="fas fa-chevron-left"></i>
                                        </button>
                                        <span class="calendar-month" id="currentMonth"></span>
                                        <button type="button" class="calendar-nav" id="nextMonth">
                                            <i class="fas fa-chevron-right"></i>
                                        </button>
                                    </div>
                                    <div class="calendar-grid" id="calendarGrid">
                                        <div class="calendar-loading">
                                            <i class="fas fa-spinner fa-spin"></i> Loading available dates...
                                        </div>
                                    </div>
                                    <div class="calendar-legend">
                                        <span><span class="legend-dot available"></span> Available</span>
                                        <span><span class="legend-dot selected"></span> Selected</span>
                                        <span><span class="legend-dot unavailable"></span> Unavailable</span>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label><i class="fas fa-clock"></i> Preferred Time</label>
                                <select name="time_slot" id="timeSlotSelect" required disabled>
                                    <option value="">Select staff and date first</option>
                                </select>
                                <div id="loadingSlots" style="display: none; color: #6b7280; font-size: 14px; margin-top: 8px;">
                                    <i class="fas fa-spinner fa-spin"></i> Loading available times...
                                </div>
                            </div>

                            <div class="form-group">
                                <label><i class="fas fa-comment"></i> Additional Notes (Optional)</label>
                                <textarea name="notes" rows="4" placeholder="Any specific requirements or information..."></textarea>
                            </div>

                            <div class="form-actions">
                                <a href="?category=<?php echo $selected_category; ?>" class="btn btn-secondary">Cancel</a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-check"></i> Confirm Booking
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <style>
        /* Calendar Styles */
        #calendarContainer {
            background: #f9fafb;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            padding: 20px;
            margin-top: 10px;
        }
        
        .calendar-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .calendar-month {
            font-size: 18px;
            font-weight: 700;
            color: var(--dark);
        }
        
        .calendar-nav {
            background: var(--white);
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s;
            color: var(--dark);
        }
        
        .calendar-nav:hover {
            background: var(--blue);
            border-color: var(--blue);
            color: var(--white);
        }
        
        .calendar-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 8px;
            margin-bottom: 15px;
        }
        
        .calendar-day-header {
            text-align: center;
            font-size: 12px;
            font-weight: 700;
            color: #6b7280;
            padding: 8px;
            text-transform: uppercase;
        }
        
        .calendar-day {
            aspect-ratio: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            background: var(--white);
            border: 2px solid #e5e7eb;
            color: #9ca3af;
        }
        
        .calendar-day.available {
            color: var(--dark);
            border-color: #d1fae5;
            background: #d1fae5;
        }
        
        .calendar-day.available:hover {
            background: #10b981;
            border-color: #10b981;
            color: var(--white);
            transform: scale(1.05);
        }
        
        .calendar-day.selected {
            background: var(--blue);
            border-color: var(--blue);
            color: var(--white);
            box-shadow: 0 0 0 4px rgba(29, 78, 216, 0.2);
        }
        
        .calendar-day.today {
            border-color: var(--blue);
            border-width: 3px;
        }
        
        .calendar-day.unavailable {
            cursor: not-allowed;
            opacity: 0.4;
        }
        
        .calendar-loading {
            grid-column: 1 / -1;
            text-align: center;
            padding: 40px;
            color: #6b7280;
        }
        
        .calendar-legend {
            display: flex;
            gap: 20px;
            justify-content: center;
            flex-wrap: wrap;
            font-size: 13px;
            color: #6b7280;
        }
        
        .calendar-legend span {
            display: flex;
            align-items: center;
            gap: 6px;
        }
        
        .legend-dot {
            width: 16px;
            height: 16px;
            border-radius: 4px;
            border: 2px solid #e5e7eb;
        }
        
        .legend-dot.available {
            background: #d1fae5;
            border-color: #d1fae5;
        }
        
        .legend-dot.selected {
            background: var(--blue);
            border-color: var(--blue);
        }
        
        .legend-dot.unavailable {
            background: var(--white);
            border-color: #e5e7eb;
            opacity: 0.4;
        }
        
        /* Responsive Calendar */
        @media (max-width: 768px) {
            #calendarContainer {
                padding: 15px;
            }
            
            .calendar-month {
                font-size: 16px;
            }
            
            .calendar-nav {
                width: 36px;
                height: 36px;
            }
            
            .calendar-grid {
                gap: 4px;
            }
            
            .calendar-day {
                font-size: 12px;
            }
            
            .calendar-day-header {
                font-size: 10px;
                padding: 4px;
            }
        }
    </style>
    
    <script>
        // Calendar and booking functionality
        const staffSelect = document.getElementById('staffSelect');
        const dateSelect = document.getElementById('dateSelect');
        const timeSlotSelect = document.getElementById('timeSlotSelect');
        const loadingSlots = document.getElementById('loadingSlots');
        const serviceId = <?php echo $selected_service ? $selected_service : 'null'; ?>;
        
        let currentMonth = new Date();
        let availableDates = [];
        let selectedDate = null;
        
        // Calendar functions
        function renderCalendar() {
            const year = currentMonth.getFullYear();
            const month = currentMonth.getMonth();
            const monthStr = `${year}-${String(month + 1).padStart(2, '0')}`;
            
            document.getElementById('currentMonth').textContent = 
                currentMonth.toLocaleDateString('en-US', { month: 'long', year: 'numeric' });
            
            const firstDay = new Date(year, month, 1);
            const lastDay = new Date(year, month + 1, 0);
            const startDay = firstDay.getDay(); // 0 = Sunday
            const daysInMonth = lastDay.getDate();
            
            const grid = document.getElementById('calendarGrid');
            grid.innerHTML = '';
            
            // Day headers
            const dayHeaders = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
            dayHeaders.forEach(day => {
                const header = document.createElement('div');
                header.className = 'calendar-day-header';
                header.textContent = day;
                grid.appendChild(header);
            });
            
            // Empty cells before first day
            for(let i = 0; i < startDay; i++) {
                const empty = document.createElement('div');
                grid.appendChild(empty);
            }
            
            // Days
            const today = new Date().toISOString().split('T')[0];
            for(let day = 1; day <= daysInMonth; day++) {
                const dateStr = `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
                const dayEl = document.createElement('div');
                dayEl.className = 'calendar-day';
                dayEl.textContent = day;
                dayEl.dataset.date = dateStr;
                
                if(dateStr === today) {
                    dayEl.classList.add('today');
                }
                
                if(availableDates.includes(dateStr)) {
                    dayEl.classList.add('available');
                    dayEl.addEventListener('click', () => selectDate(dateStr));
                } else if(new Date(dateStr) >= new Date(today)) {
                    dayEl.classList.add('unavailable');
                }
                
                if(selectedDate === dateStr) {
                    dayEl.classList.add('selected');
                }
                
                grid.appendChild(dayEl);
            }
        }
        
        function selectDate(date) {
            selectedDate = date;
            dateSelect.value = date;
            renderCalendar();
            loadAvailableSlots();
        }
        
        function loadAvailableDates() {
            const staffId = staffSelect?.value;
            if(!staffId || !serviceId) {
                const grid = document.getElementById('calendarGrid');
                grid.innerHTML = '<div class="calendar-loading" style="color: #6b7280;"><i class="fas fa-info-circle"></i> Please select a staff member first</div>';
                return;
            }
            
            const year = currentMonth.getFullYear();
            const month = String(currentMonth.getMonth() + 1).padStart(2, '0');
            const monthStr = `${year}-${month}`;
            
            const grid = document.getElementById('calendarGrid');
            grid.innerHTML = '<div class="calendar-loading"><i class="fas fa-spinner fa-spin"></i> Loading available dates...</div>';
            
            fetch(`get-available-dates.php?staff_id=${staffId}&service_id=${serviceId}&month=${monthStr}`)
                .then(response => response.json())
                .then(data => {
                    if(data.error) {
                        console.error('Error:', data.error);
                        grid.innerHTML = `<div class="calendar-loading" style="color: #ef4444;"><i class="fas fa-exclamation-circle"></i> ${data.error}</div>`;
                        return;
                    }
                    availableDates = data.available_dates || [];
                    renderCalendar();
                    
                    if(availableDates.length === 0) {
                        grid.innerHTML = '<div class="calendar-loading" style="color: #f59e0b;"><i class="fas fa-calendar-times"></i> No available dates this month. Try another month.</div>';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    grid.innerHTML = '<div class="calendar-loading" style="color: #ef4444;"><i class="fas fa-exclamation-circle"></i> Error loading dates. Please try again.</div>';
                });
        }
        
        function loadAvailableSlots() {
            const staffId = staffSelect?.value;
            const date = dateSelect?.value;
            
            if(!staffId || !date || !serviceId) {
                return;
            }
            
            timeSlotSelect.disabled = true;
            timeSlotSelect.innerHTML = '<option value="">Loading...</option>';
            loadingSlots.style.display = 'block';
            
            fetch(`get-available-slots.php?staff_id=${staffId}&service_id=${serviceId}&date=${date}`)
                .then(response => response.json())
                .then(data => {
                    loadingSlots.style.display = 'none';
                    timeSlotSelect.innerHTML = '';
                    
                    if(data.slots && data.slots.length > 0) {
                        timeSlotSelect.innerHTML = '<option value="">Select a time slot</option>';
                        data.slots.forEach(slot => {
                            const option = document.createElement('option');
                            option.value = slot.value;
                            option.textContent = slot.label;
                            timeSlotSelect.appendChild(option);
                        });
                        timeSlotSelect.disabled = false;
                    } else {
                        timeSlotSelect.innerHTML = '<option value="">No available slots for this date</option>';
                    }
                })
                .catch(error => {
                    loadingSlots.style.display = 'none';
                    timeSlotSelect.innerHTML = '<option value="">Error loading slots</option>';
                    console.error('Error:', error);
                });
        }
        
        // Event listeners
        if(staffSelect) {
            staffSelect.addEventListener('change', () => {
                selectedDate = null;
                dateSelect.value = '';
                timeSlotSelect.innerHTML = '<option value="">Select date first</option>';
                timeSlotSelect.disabled = true;
                loadAvailableDates();
            });
        }
        
        document.getElementById('prevMonth')?.addEventListener('click', () => {
            currentMonth.setMonth(currentMonth.getMonth() - 1);
            loadAvailableDates();
        });
        
        document.getElementById('nextMonth')?.addEventListener('click', () => {
            currentMonth.setMonth(currentMonth.getMonth() + 1);
            loadAvailableDates();
        });
        
        // Initialize calendar if on booking step
        if(serviceId && staffSelect) {
            const grid = document.getElementById('calendarGrid');
            grid.innerHTML = '<div class="calendar-loading" style="color: #6b7280;"><i class="fas fa-info-circle"></i> Please select a staff member to view available dates</div>';
        }
    </script>
    
    <?php include '../assets/includes/modals.php'; ?>
    <link rel="stylesheet" href="../assets/css/modals.css">
    <script src="../assets/js/modals.js"></script>
    <script src="js/dashboard.js"></script>
</body>
</html>
