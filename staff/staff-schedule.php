<?php
session_start();

// Check if user is logged in
if(!isset($_SESSION['staff_id'])) {
    header('Location: ../staff-login.php');
    exit();
}

$role = $_SESSION['role'] ?? 'staff';
$staff_id = $_SESSION['staff_id'];

// Only counselors and advisors can access this page
$is_counselor = in_array($role, ['counsellor', 'academic_advisor', 'career_counsellor', 'financial_advisor']);

if(!$is_counselor) {
    header('Location: index.php');
    exit();
}

require_once '../config/database.php';

$db = new Database();
$conn = $db->connect();

// Get current month and year
$month = isset($_GET['month']) ? intval($_GET['month']) : date('n');
$year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');

// Get first and last day of month
$first_day = date('Y-m-01', strtotime("$year-$month-01"));
$last_day = date('Y-m-t', strtotime("$year-$month-01"));

// Get bookings for the month
$bookings_query = $conn->prepare("
    SELECT b.*, s.service_name, st.first_name, st.last_name
    FROM bookings b
    JOIN services s ON b.service_id = s.service_id
    JOIN students st ON b.student_id = st.student_id
    WHERE b.staff_id = ? AND b.booking_date BETWEEN ? AND ?
    ORDER BY b.booking_date, b.start_time
");
$bookings_query->execute([$staff_id, $first_day, $last_day]);
$all_bookings = $bookings_query->fetchAll();

// Organize bookings by date
$bookings_by_date = [];
foreach($all_bookings as $booking) {
    $date = $booking['booking_date'];
    if(!isset($bookings_by_date[$date])) {
        $bookings_by_date[$date] = [];
    }
    $bookings_by_date[$date][] = $booking;
}

// Get staff info
$staff_query = $conn->prepare("SELECT * FROM staff WHERE staff_id = ?");
$staff_query->execute([$staff_id]);
$staff_info = $staff_query->fetch();

// Get all services offered by this staff member (through staff_schedules)
$services_query = $conn->prepare("
    SELECT DISTINCT s.* 
    FROM services s
    INNER JOIN staff_schedules ss ON s.service_id = ss.service_id
    WHERE ss.staff_id = ? AND s.status = 'active'
    ORDER BY s.service_name
");
$services_query->execute([$staff_id]);
$staff_services = $services_query->fetchAll();

// If no services found through schedules, get all active services
if(empty($staff_services)) {
    $services_query = $conn->prepare("
        SELECT * FROM services
        WHERE status = 'active'
        ORDER BY service_name
    ");
    $services_query->execute();
    $staff_services = $services_query->fetchAll();
}

// Get all students for the appointment form
$students_query = $conn->prepare("
    SELECT student_id, first_name, last_name, email
    FROM students
    WHERE status = 'active'
    ORDER BY first_name, last_name
");
$students_query->execute();
$all_students = $students_query->fetchAll();

// Handle AJAX appointment creation
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['ajax'])) {
    header('Content-Type: application/json');
    
    $student_id = intval($_POST['student_id']);
    $service_id = intval($_POST['service_id']);
    $booking_date = $_POST['booking_date'];
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];
    $notes = trim($_POST['notes'] ?? '');
    
    // Validate end time is after start time
    if($end_time <= $start_time) {
        echo json_encode(['success' => false, 'message' => 'End time must be after start time.']);
        exit();
    }
    
    // Check for overlapping appointments
    $overlap_check = $conn->prepare("
        SELECT b.booking_id, st.first_name, st.last_name
        FROM bookings b
        JOIN students st ON b.student_id = st.student_id
        WHERE b.staff_id = ?
        AND b.booking_date = ?
        AND b.status NOT IN ('cancelled', 'completed')
        AND (
            (b.start_time < ? AND b.end_time > ?) OR
            (b.start_time < ? AND b.end_time > ?) OR
            (b.start_time >= ? AND b.end_time <= ?)
        )
    ");
    
    $overlap_check->execute([
        $staff_id,
        $booking_date,
        $end_time, $start_time,
        $end_time, $start_time,
        $start_time, $end_time
    ]);
    
    $overlaps = $overlap_check->fetchAll();
    
    if(count($overlaps) > 0) {
        $overlap = $overlaps[0];
        $message = "You already have an appointment on this date that overlaps with this time slot.";
        echo json_encode(['success' => false, 'message' => $message]);
        exit();
    }
    
    try {
        $insert = $conn->prepare("
            INSERT INTO bookings 
            (student_id, staff_id, service_id, booking_date, start_time, end_time, 
             status, notes, created_at)
            VALUES (?, ?, ?, ?, ?, ?, 'confirmed', ?, NOW())
        ");
        
        $insert->execute([
            $student_id, $staff_id, $service_id, $booking_date, 
            $start_time, $end_time, $notes
        ]);
        
        $booking_id = $conn->lastInsertId();
        
        echo json_encode([
            'success' => true, 
            'message' => 'Appointment scheduled successfully!',
            'booking_id' => $booking_id
        ]);
        exit();
        
    } catch(PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error creating appointment: ' . $e->getMessage()]);
        exit();
    }
}

// Organize bookings by date
$bookings_by_date = [];
foreach($all_bookings as $booking) {
    $date = $booking['booking_date'];
    if(!isset($bookings_by_date[$date])) {
        $bookings_by_date[$date] = [];
    }
    $bookings_by_date[$date][] = $booking;
}

// Get staff info
$staff_query = $conn->prepare("SELECT * FROM staff WHERE staff_id = ?");
$staff_query->execute([$staff_id]);
$staff_info = $staff_query->fetch();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Schedule - WSU Booking</title>
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .calendar-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            background: white;
            padding: 20px;
            border-radius: 12px;
        }
        
        .calendar-nav {
            display: flex;
            gap: 10px;
        }
        
        .calendar-title {
            font-size: 24px;
            font-weight: 700;
            color: var(--dark);
        }
        
        .calendar-grid {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }
        
        .calendar-weekdays {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 10px;
            margin-bottom: 10px;
            padding: 20px;
            background: #f9fafb;
        }
        
        .weekday {
            text-align: center;
            font-weight: 700;
            color: var(--dark);
            padding: 10px;
            font-size: 14px;
        }
        
        .calendar-days {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 10px;
            padding: 20px;
        }
        
        .calendar-day {
            min-height: 120px;
            padding: 10px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
            position: relative;
            background: white;
        }
        
        .calendar-day:hover {
            border-color: var(--blue);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }
        
        .calendar-day.other-month {
            background: #f9fafb;
            opacity: 0.5;
        }
        
        .calendar-day.today {
            background: #eff6ff;
            border-color: var(--blue);
        }
        
        .calendar-day.past {
            background: #f9fafb;
            cursor: not-allowed;
        }
        
        .calendar-day.past:hover {
            border-color: #e5e7eb;
            box-shadow: none;
        }
        
        .day-number {
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 5px;
        }
        
        .calendar-day.past .day-number {
            color: #9ca3af;
        }
        
        .booking-dot {
            width: 100%;
            padding: 4px 6px;
            background: var(--blue);
            color: white;
            border-radius: 4px;
            font-size: 11px;
            margin-bottom: 3px;
            cursor: pointer;
            transition: all 0.3s;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .booking-dot:hover {
            transform: translateX(2px);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }
        
        .booking-dot.pending {
            background: #f59e0b;
        }
        
        .booking-dot.confirmed {
            background: var(--blue);
        }
        
        .booking-dot.completed {
            background: var(--green);
        }
        
        .booking-dot.cancelled {
            background: #dc2626;
        }
        
        .legend {
            display: flex;
            gap: 20px;
            margin-top: 20px;
            padding: 15px;
            background: white;
            border-radius: 12px;
        }
        
        .legend-item {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
        }
        
        .legend-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
        }
    </style>
</head>
<body>
    <div class="dashboard-layout">
        <?php include 'includes/sidebar.php'; ?>
        
        <div class="main-content">
            <?php include 'includes/header.php'; ?>
            
            <div class="content">
                <!-- Page Header -->
                <div class="page-header">
                    <h1><i class="fas fa-calendar"></i> Schedule</h1>
                    <p>View and manage your appointments</p>
                </div>

                <!-- Calendar Header -->
                <div class="calendar-header">
                    <div class="calendar-title" id="currentMonth"></div>
                    <div class="calendar-nav">
                        <button onclick="previousMonth()" class="btn btn-secondary">
                            <i class="fas fa-chevron-left"></i> Previous
                        </button>
                        <button onclick="goToToday()" class="btn btn-primary">
                            <i class="fas fa-calendar-day"></i> Today
                        </button>
                        <button onclick="nextMonth()" class="btn btn-secondary">
                            Next <i class="fas fa-chevron-right"></i>
                        </button>
                    </div>
                </div>

                <!-- Calendar Grid -->
                <div class="calendar-grid">
                    <div class="calendar-weekdays">
                        <div class="weekday">Mon</div>
                        <div class="weekday">Tue</div>
                        <div class="weekday">Wed</div>
                        <div class="weekday">Thu</div>
                        <div class="weekday">Fri</div>
                    </div>
                    <div class="calendar-days" id="calendarDays"></div>
                </div>

                <!-- Legend -->
                <div class="legend">
                    <div class="legend-item">
                        <div class="legend-dot" style="background: #f59e0b;"></div>
                        <span>Pending</span>
                    </div>
                    <div class="legend-item">
                        <div class="legend-dot" style="background: var(--blue);"></div>
                        <span>Confirmed</span>
                    </div>
                    <div class="legend-item">
                        <div class="legend-dot" style="background: var(--green);"></div>
                        <span>Completed</span>
                    </div>
                    <div class="legend-item">
                        <div class="legend-dot" style="background: #dc2626;"></div>
                        <span>Cancelled</span>
                    </div>
                    <div class="legend-item">
                        <div class="legend-dot" style="background: #eff6ff; border: 2px solid var(--blue);"></div>
                        <span>Today</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php include '../assets/includes/modals.php'; ?>
    
    <!-- Appointment Creation Modal -->
    <div class="session-modal" id="appointmentModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.5); z-index: 10000; align-items: center; justify-content: center; padding: 20px;">
        <div class="modal-content" style="background: white; border-radius: 12px; max-width: 600px; width: 100%; max-height: 90vh; overflow-y: auto; box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);">
            <div class="modal-header" style="padding: 25px 30px; border-bottom: 2px solid #e5e7eb; display: flex; justify-content: space-between; align-items: center;">
                <h3 style="margin: 0; font-size: 22px; color: var(--dark);"><i class="fas fa-calendar-plus"></i> Schedule Appointment</h3>
                <button class="modal-close" onclick="closeAppointmentModal()" style="background: none; border: none; font-size: 24px; color: #6b7280; cursor: pointer; padding: 0; width: 30px; height: 30px; display: flex; align-items: center; justify-content: center; border-radius: 6px;">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form id="appointmentForm">
                <div class="modal-body" style="padding: 30px;">
                    <div style="background: #eff6ff; border-left: 4px solid var(--blue); padding: 12px 15px; border-radius: 8px; margin-bottom: 20px; font-size: 13px; color: #4b5563;">
                        <i class="fas fa-info-circle"></i>
                        Schedule an appointment with a student. The appointment will be marked as confirmed.
                    </div>

                    <input type="hidden" name="booking_date" id="bookingDate">

                    <div class="form-group" style="margin-bottom: 20px;">
                        <label style="display: block; margin-bottom: 8px; color: var(--dark); font-weight: 600; font-size: 14px;"><i class="fas fa-user-graduate" style="margin-right: 8px; color: var(--blue); width: 20px;"></i> Select Student</label>
                        <select name="student_id" id="studentSelect" required style="width: 100%; padding: 12px 15px; border: 2px solid #e5e7eb; border-radius: 8px; font-size: 15px;">
                            <option value="">-- Select Student --</option>
                            <?php foreach($all_students as $student): ?>
                                <option value="<?php echo $student['student_id']; ?>">
                                    <?php echo htmlspecialchars($student['student_id'] . ' - ' . $student['first_name'] . ' ' . $student['last_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group" style="margin-bottom: 20px;">
                        <label style="display: block; margin-bottom: 8px; color: var(--dark); font-weight: 600; font-size: 14px;"><i class="fas fa-briefcase" style="margin-right: 8px; color: var(--blue); width: 20px;"></i> Service Type</label>
                        <select name="service_id" id="serviceSelect" required style="width: 100%; padding: 12px 15px; border: 2px solid #e5e7eb; border-radius: 8px; font-size: 15px;">
                            <option value="">-- Select Service --</option>
                            <?php foreach($staff_services as $service): ?>
                                <option value="<?php echo $service['service_id']; ?>">
                                    <?php echo htmlspecialchars($service['service_name']); ?> (<?php echo $service['duration_minutes']; ?> min)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                        <div class="form-group" style="margin-bottom: 20px;">
                            <label style="display: block; margin-bottom: 8px; color: var(--dark); font-weight: 600; font-size: 14px;"><i class="fas fa-clock" style="margin-right: 8px; color: var(--blue); width: 20px;"></i> Start Time</label>
                            <input type="time" name="start_time" id="startTime" required style="width: 100%; padding: 12px 15px; border: 2px solid #e5e7eb; border-radius: 8px; font-size: 15px;">
                        </div>
                        <div class="form-group" style="margin-bottom: 20px;">
                            <label style="display: block; margin-bottom: 8px; color: var(--dark); font-weight: 600; font-size: 14px;"><i class="fas fa-clock" style="margin-right: 8px; color: var(--blue); width: 20px;"></i> End Time</label>
                            <input type="time" name="end_time" id="endTime" required style="width: 100%; padding: 12px 15px; border: 2px solid #e5e7eb; border-radius: 8px; font-size: 15px;">
                        </div>
                    </div>

                    <div class="form-group" style="margin-bottom: 20px;">
                        <label style="display: block; margin-bottom: 8px; color: var(--dark); font-weight: 600; font-size: 14px;"><i class="fas fa-sticky-note" style="margin-right: 8px; color: var(--blue); width: 20px;"></i> Notes (Optional)</label>
                        <textarea name="notes" placeholder="Add any notes about this appointment..." rows="3" style="width: 100%; padding: 12px 15px; border: 2px solid #e5e7eb; border-radius: 8px; font-size: 15px; resize: vertical; min-height: 80px; font-family: inherit;"></textarea>
                    </div>
                </div>
                <div class="modal-footer" style="padding: 20px 30px; border-top: 2px solid #e5e7eb; display: flex; gap: 10px; justify-content: flex-end;">
                    <button type="button" onclick="closeAppointmentModal()" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-check"></i> Schedule Appointment
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <link rel="stylesheet" href="../assets/css/modals.css">
    <script src="../assets/js/modals.js"></script>
    <script src="js/dashboard.js"></script>

    <script>
        // Bookings data from PHP
        const bookings = <?php echo json_encode($all_bookings); ?>;
        
        let currentDate = new Date();
        
        // Initialize calendar
        function initCalendar() {
            renderCalendar();
        }
        
        // Render calendar
        function renderCalendar() {
            const year = currentDate.getFullYear();
            const month = currentDate.getMonth();
            
            // Update month display
            const monthNames = ['January', 'February', 'March', 'April', 'May', 'June',
                              'July', 'August', 'September', 'October', 'November', 'December'];
            document.getElementById('currentMonth').textContent = `${monthNames[month]} ${year}`;
            
            // Get first day of month and number of days
            const firstDay = new Date(year, month, 1).getDay();
            const daysInMonth = new Date(year, month + 1, 0).getDate();
            
            const calendarDays = document.getElementById('calendarDays');
            calendarDays.innerHTML = '';
            
            const today = new Date();
            today.setHours(0, 0, 0, 0);
            
            // Calculate offset for Monday start
            let offset = firstDay === 0 ? 6 : firstDay - 1;
            
            // Add empty cells for days before month starts
            for(let i = 0; i < offset; i++) {
                const emptyDiv = document.createElement('div');
                emptyDiv.className = 'calendar-day other-month';
                calendarDays.appendChild(emptyDiv);
            }
            
            // Current month days (only weekdays)
            for(let day = 1; day <= daysInMonth; day++) {
                const date = new Date(year, month, day);
                const dayOfWeek = date.getDay();
                
                // Skip weekends
                if(dayOfWeek === 0 || dayOfWeek === 6) {
                    continue;
                }
                
                date.setHours(0, 0, 0, 0);
                const isPast = date < today;
                const isToday = date.getTime() === today.getTime();
                
                const dateStr = `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
                const dayBookings = bookings.filter(b => b.booking_date === dateStr);
                
                const dayDiv = createDayElement(day, false, dayBookings, isPast, isToday, dateStr);
                calendarDays.appendChild(dayDiv);
            }
        }
        
        // Create day element
        function createDayElement(day, isOtherMonth, dayBookings, isPast = false, isToday = false, dateStr = null) {
            const dayDiv = document.createElement('div');
            dayDiv.className = 'calendar-day';
            
            if(isOtherMonth) {
                dayDiv.classList.add('other-month');
            }
            if(isPast && !isOtherMonth) {
                dayDiv.classList.add('past');
            }
            if(isToday) {
                dayDiv.classList.add('today');
            }
            
            const dayNumber = document.createElement('div');
            dayNumber.className = 'day-number';
            dayNumber.textContent = day;
            dayDiv.appendChild(dayNumber);
            
            // Add bookings
            if(dayBookings && dayBookings.length > 0) {
                dayBookings.forEach(booking => {
                    const bookingDot = document.createElement('div');
                    bookingDot.className = `booking-dot ${booking.status}`;
                    
                    const time = booking.start_time.substring(0, 5);
                    const studentName = `${booking.first_name} ${booking.last_name}`;
                    bookingDot.textContent = `${time} ${studentName}`;
                    bookingDot.title = `${booking.service_name} - ${time}`;
                    
                    bookingDot.onclick = (e) => {
                        e.stopPropagation();
                        window.location.href = `appointment-details.php?id=${booking.booking_id}`;
                    };
                    
                    dayDiv.appendChild(bookingDot);
                });
            }
            
            // Click handler to schedule appointment on this date
            if(!isPast && !isOtherMonth && dateStr) {
                dayDiv.onclick = () => {
                    openAppointmentModal(dateStr);
                };
            }
            
            return dayDiv;
        }
        
        // Navigation functions
        function previousMonth() {
            currentDate.setMonth(currentDate.getMonth() - 1);
            renderCalendar();
        }
        
        function nextMonth() {
            currentDate.setMonth(currentDate.getMonth() + 1);
            renderCalendar();
        }
        
        function goToToday() {
            currentDate = new Date();
            renderCalendar();
        }
        
        // Modal functions
        function openAppointmentModal(date = null) {
            if(!date) {
                showMessageModal('Select a Date', 'Please click on a date in the calendar to schedule an appointment.', 'info');
                return;
            }
            
            // Check if date is in the past
            const selectedDate = new Date(date);
            const today = new Date();
            today.setHours(0, 0, 0, 0);
            
            if(selectedDate < today) {
                showMessageModal('Invalid Date', 'Cannot schedule appointments in the past. Please select a future date.', 'error');
                return;
            }
            
            // Check if it's a weekend
            const dayOfWeek = selectedDate.getDay();
            if(dayOfWeek === 0 || dayOfWeek === 6) {
                showMessageModal('Weekend Not Allowed', 'Cannot schedule appointments on weekends. Please select a weekday.', 'error');
                return;
            }
            
            const modal = document.getElementById('appointmentModal');
            modal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
            
            // Set the date field
            document.getElementById('bookingDate').value = date;
            
            // Update modal title to show selected date
            const dateObj = new Date(date);
            const dayNames = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
            const monthNames = ['January', 'February', 'March', 'April', 'May', 'June',
                              'July', 'August', 'September', 'October', 'November', 'December'];
            const formattedDate = `${dayNames[dateObj.getDay()]}, ${monthNames[dateObj.getMonth()]} ${dateObj.getDate()}, ${dateObj.getFullYear()}`;
            
            document.querySelector('#appointmentModal .modal-header h3').innerHTML = `<i class="fas fa-calendar-plus"></i> Schedule Appointment - ${formattedDate}`;
        }

        function closeAppointmentModal() {
            document.getElementById('appointmentModal').style.display = 'none';
            document.body.style.overflow = 'auto';
            document.getElementById('appointmentForm').reset();
            // Reset modal title
            document.querySelector('#appointmentModal .modal-header h3').innerHTML = '<i class="fas fa-calendar-plus"></i> Schedule Appointment';
        }

        // Form submission
        document.getElementById('appointmentForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            formData.append('ajax', '1');
            
            // Validate times
            const startTime = formData.get('start_time');
            const endTime = formData.get('end_time');
            
            if(endTime <= startTime) {
                showMessageModal('Invalid Time', 'End time must be after start time.', 'error');
                return;
            }
            
            // Disable submit button
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Scheduling...';
            
            try {
                const response = await fetch('staff-schedule.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if(result.success) {
                    closeAppointmentModal();
                    showMessageModal('Success!', 'Appointment scheduled successfully! Refreshing calendar...', 'success');
                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);
                } else {
                    showMessageModal('Error', result.message, 'error');
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalText;
                }
            } catch(error) {
                showMessageModal('Error', 'Failed to schedule appointment. Please try again.', 'error');
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            }
        });

        // Close modal on outside click
        document.getElementById('appointmentModal').addEventListener('click', function(e) {
            if(e.target === this) {
                closeAppointmentModal();
            }
        });
        
        // Initialize on page load
        document.addEventListener('DOMContentLoaded', initCalendar);
    </script>
</body>
</html>
