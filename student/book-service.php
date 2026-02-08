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
    SELECT sc.*, COUNT(s.service_id) as service_count
    FROM service_categories sc
    LEFT JOIN services s ON sc.category_id = s.category_id AND s.status = 'active'
    WHERE sc.status = 'active'
    GROUP BY sc.category_id
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
                                <input type="date" name="booking_date" id="dateSelect"
                                       min="<?php echo date('Y-m-d'); ?>" 
                                       max="<?php echo date('Y-m-d', strtotime('+' . $service_details['max_advance_booking_days'] . ' days')); ?>" 
                                       required>
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
    
    <script>
        const menuToggle = document.getElementById('menuToggle');
        const sidebar = document.querySelector('.sidebar');
        const headerLogo = document.getElementById('headerLogo');
        const userMenuBtn = document.getElementById('userMenuBtn');
        const userDropdown = document.getElementById('userDropdown');
        const notificationBtn = document.getElementById('notificationBtn');
        const notificationsDropdown = document.getElementById('notificationsDropdown');
        
        menuToggle.addEventListener('click', function() {
            sidebar.classList.toggle('closed');
            headerLogo.style.display = sidebar.classList.contains('closed') ? 'flex' : 'none';
        });
        
        userMenuBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            userDropdown.classList.toggle('active');
            notificationsDropdown.classList.remove('active');
        });
        
        notificationBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            notificationsDropdown.classList.toggle('active');
            userDropdown.classList.remove('active');
        });
        
        document.addEventListener('click', function() {
            userDropdown.classList.remove('active');
            notificationsDropdown.classList.remove('active');
        });
        
        // Available time slots functionality
        const staffSelect = document.getElementById('staffSelect');
        const dateSelect = document.getElementById('dateSelect');
        const timeSlotSelect = document.getElementById('timeSlotSelect');
        const loadingSlots = document.getElementById('loadingSlots');
        const serviceId = <?php echo $selected_service ? $selected_service : 'null'; ?>;
        
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
        
        if(staffSelect && dateSelect) {
            staffSelect.addEventListener('change', loadAvailableSlots);
            dateSelect.addEventListener('change', loadAvailableSlots);
        }
    </script>
</body>
</html>
