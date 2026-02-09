<?php
session_start();
if(!isset($_SESSION['staff_id'])) {
    header('Location: ../index.php');
    exit();
}

require_once '../config/database.php';

$db = new Database();
$conn = $db->connect();

// Get filter from URL
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
$date_filter = isset($_GET['date_filter']) ? $_GET['date_filter'] : '';
$month_filter = isset($_GET['month']) ? $_GET['month'] : date('m');
$year_filter = isset($_GET['year']) ? $_GET['year'] : date('Y');
$specific_date = isset($_GET['specific_date']) ? $_GET['specific_date'] : '';
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : '';

// Build query based on filter
$query = "
    SELECT b.*, s.service_name, st.first_name, st.last_name, st.student_id, st.email, st.phone,
           sc.category_name
    FROM bookings b
    JOIN services s ON b.service_id = s.service_id
    JOIN students st ON b.student_id = st.student_id
    JOIN service_categories sc ON s.category_id = sc.category_id
    WHERE b.staff_id = ?
";

$params = [$_SESSION['staff_id']];

switch($filter) {
    case 'pending':
        $query .= " AND b.status = 'pending'";
        break;
    case 'confirmed':
        $query .= " AND b.status = 'confirmed'";
        break;
    case 'completed':
        $query .= " AND b.status = 'completed'";
        break;
    case 'cancelled':
        $query .= " AND b.status = 'cancelled'";
        break;
    case 'upcoming':
        $query .= " AND b.booking_date >= CURDATE() AND b.status IN ('pending', 'confirmed')";
        break;
    case 'today':
        $query .= " AND b.booking_date = CURDATE() AND b.status IN ('pending', 'confirmed')";
        break;
}

// Date filtering
if(!empty($date_filter)) {
    switch($date_filter) {
        case 'today':
            $query .= " AND DATE(b.booking_date) = CURDATE()";
            break;
        case 'this_week':
            $query .= " AND YEARWEEK(b.booking_date, 1) = YEARWEEK(CURDATE(), 1)";
            break;
        case 'this_month':
            $query .= " AND YEAR(b.booking_date) = YEAR(CURDATE()) AND MONTH(b.booking_date) = MONTH(CURDATE())";
            break;
        case 'this_year':
            $query .= " AND YEAR(b.booking_date) = YEAR(CURDATE())";
            break;
        case 'custom_month':
            if(!empty($month_filter) && !empty($year_filter)) {
                $query .= " AND YEAR(b.booking_date) = ? AND MONTH(b.booking_date) = ?";
                $params[] = $year_filter;
                $params[] = $month_filter;
            }
            break;
        case 'custom_year':
            if(!empty($year_filter)) {
                $query .= " AND YEAR(b.booking_date) = ?";
                $params[] = $year_filter;
            }
            break;
        case 'specific_date':
            if(!empty($specific_date)) {
                $query .= " AND DATE(b.booking_date) = ?";
                $params[] = $specific_date;
            }
            break;
        case 'date_range':
            if(!empty($start_date) && !empty($end_date)) {
                $query .= " AND DATE(b.booking_date) BETWEEN ? AND ?";
                $params[] = $start_date;
                $params[] = $end_date;
            }
            break;
    }
}

$query .= " ORDER BY b.booking_date DESC, b.start_time DESC";

$stmt = $conn->prepare($query);
$stmt->execute($params);
$appointments = $stmt->fetchAll();

// Get counts for filter tabs
$counts = [];
$countQueries = [
    'all' => "SELECT COUNT(*) as count FROM bookings WHERE staff_id = ?",
    'pending' => "SELECT COUNT(*) as count FROM bookings WHERE staff_id = ? AND status = 'pending'",
    'confirmed' => "SELECT COUNT(*) as count FROM bookings WHERE staff_id = ? AND status = 'confirmed'",
    'completed' => "SELECT COUNT(*) as count FROM bookings WHERE staff_id = ? AND status = 'completed'",
    'cancelled' => "SELECT COUNT(*) as count FROM bookings WHERE staff_id = ? AND status = 'cancelled'",
    'upcoming' => "SELECT COUNT(*) as count FROM bookings WHERE staff_id = ? AND booking_date >= CURDATE() AND status IN ('pending', 'confirmed')",
    'today' => "SELECT COUNT(*) as count FROM bookings WHERE staff_id = ? AND booking_date = CURDATE() AND status IN ('pending', 'confirmed')"
];

foreach($countQueries as $key => $countQuery) {
    $stmt = $conn->prepare($countQuery);
    $stmt->execute([$_SESSION['staff_id']]);
    $counts[$key] = $stmt->fetch()['count'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Appointments - WSU Booking</title>
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .date-filter-section {
            background: var(--white);
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }
        
        .date-filter-header {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 15px;
            font-weight: 600;
            color: var(--dark);
            font-size: 16px;
        }
        
        .date-filter-header i {
            color: var(--blue);
        }
        
        .date-filter-options {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 10px;
            margin-bottom: 15px;
        }
        
        .date-option {
            position: relative;
        }
        
        .date-option input[type="radio"] {
            position: absolute;
            opacity: 0;
            width: 0;
            height: 0;
        }
        
        .date-option-label {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 12px 15px;
            background: #f9fafb;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 13px;
            font-weight: 600;
            color: #6b7280;
        }
        
        .date-option input[type="radio"]:checked + .date-option-label {
            background: linear-gradient(135deg, rgba(29, 78, 216, 0.1) 0%, rgba(29, 78, 216, 0.05) 100%);
            border-color: var(--blue);
            color: var(--blue);
            box-shadow: 0 0 0 3px rgba(29, 78, 216, 0.1);
        }
        
        .date-option-label:hover {
            border-color: var(--blue);
            background: #f3f4f6;
        }
        
        .date-option-label i {
            font-size: 16px;
        }
        
        .custom-date-inputs {
            display: none;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 15px;
            padding: 20px;
            background: #f9fafb;
            border-radius: 8px;
            border: 2px solid #e5e7eb;
        }
        
        .custom-date-inputs.active {
            display: grid;
        }
        
        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        
        .filter-group label {
            font-size: 13px;
            font-weight: 600;
            color: #6b7280;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        
        .filter-group label i {
            color: var(--blue);
        }
        
        .filter-group input,
        .filter-group select {
            padding: 10px 12px;
            border: 2px solid #e5e7eb;
            border-radius: 6px;
            font-size: 14px;
            transition: all 0.3s;
            background: var(--white);
        }
        
        .filter-group input:focus,
        .filter-group select:focus {
            outline: none;
            border-color: var(--blue);
            box-shadow: 0 0 0 3px rgba(29, 78, 216, 0.1);
        }
    </style>
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
                        <h1>Appointment Management</h1>
                        <p>View, manage, and track all your student appointments in one place</p>
                        <div class="hero-stats">
                            <div class="hero-stat">
                                <i class="fas fa-calendar-day"></i>
                                <span><?php echo $counts['today']; ?> Today</span>
                            </div>
                            <div class="hero-stat">
                                <i class="fas fa-calendar-alt"></i>
                                <span><?php echo $counts['upcoming']; ?> Upcoming</span>
                            </div>
                            <div class="hero-stat">
                                <i class="fas fa-clock"></i>
                                <span><?php echo $counts['pending']; ?> Pending</span>
                            </div>
                            <div class="hero-stat">
                                <i class="fas fa-check-circle"></i>
                                <span><?php echo $counts['confirmed']; ?> Confirmed</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Page Header -->
                <div class="page-header">
                    <h1><i class="fas fa-calendar-check"></i> Appointments</h1>
                    <p>Manage and view all your appointments</p>
                </div>

                <!-- Success/Error Messages -->
                <?php if(isset($_GET['success'])): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i>
                        <?php echo htmlspecialchars($_GET['success']); ?>
                    </div>
                <?php endif; ?>

                <?php if(isset($_GET['error'])): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i>
                        <?php echo htmlspecialchars($_GET['error']); ?>
                    </div>
                <?php endif; ?>

                <!-- Filter Tabs -->
                <div class="filter-tabs">
                    <a href="appointments.php?filter=all<?php echo !empty($date_filter) ? '&date_filter='.$date_filter.'&month='.$month_filter.'&year='.$year_filter.'&specific_date='.$specific_date.'&start_date='.$start_date.'&end_date='.$end_date : ''; ?>" class="filter-tab <?php echo $filter == 'all' ? 'active' : ''; ?>">
                        <i class="fas fa-list"></i> All (<?php echo $counts['all']; ?>)
                    </a>
                    <a href="appointments.php?filter=today<?php echo !empty($date_filter) ? '&date_filter='.$date_filter.'&month='.$month_filter.'&year='.$year_filter.'&specific_date='.$specific_date.'&start_date='.$start_date.'&end_date='.$end_date : ''; ?>" class="filter-tab <?php echo $filter == 'today' ? 'active' : ''; ?>">
                        <i class="fas fa-calendar-day"></i> Today (<?php echo $counts['today']; ?>)
                    </a>
                    <a href="appointments.php?filter=upcoming<?php echo !empty($date_filter) ? '&date_filter='.$date_filter.'&month='.$month_filter.'&year='.$year_filter.'&specific_date='.$specific_date.'&start_date='.$start_date.'&end_date='.$end_date : ''; ?>" class="filter-tab <?php echo $filter == 'upcoming' ? 'active' : ''; ?>">
                        <i class="fas fa-calendar-alt"></i> Upcoming (<?php echo $counts['upcoming']; ?>)
                    </a>
                    <a href="appointments.php?filter=pending<?php echo !empty($date_filter) ? '&date_filter='.$date_filter.'&month='.$month_filter.'&year='.$year_filter.'&specific_date='.$specific_date.'&start_date='.$start_date.'&end_date='.$end_date : ''; ?>" class="filter-tab <?php echo $filter == 'pending' ? 'active' : ''; ?>">
                        <i class="fas fa-clock"></i> Pending (<?php echo $counts['pending']; ?>)
                    </a>
                    <a href="appointments.php?filter=confirmed<?php echo !empty($date_filter) ? '&date_filter='.$date_filter.'&month='.$month_filter.'&year='.$year_filter.'&specific_date='.$specific_date.'&start_date='.$start_date.'&end_date='.$end_date : ''; ?>" class="filter-tab <?php echo $filter == 'confirmed' ? 'active' : ''; ?>">
                        <i class="fas fa-check-circle"></i> Confirmed (<?php echo $counts['confirmed']; ?>)
                    </a>
                    <a href="appointments.php?filter=completed<?php echo !empty($date_filter) ? '&date_filter='.$date_filter.'&month='.$month_filter.'&year='.$year_filter.'&specific_date='.$specific_date.'&start_date='.$start_date.'&end_date='.$end_date : ''; ?>" class="filter-tab <?php echo $filter == 'completed' ? 'active' : ''; ?>">
                        <i class="fas fa-calendar-check"></i> Completed (<?php echo $counts['completed']; ?>)
                    </a>
                    <a href="appointments.php?filter=cancelled<?php echo !empty($date_filter) ? '&date_filter='.$date_filter.'&month='.$month_filter.'&year='.$year_filter.'&specific_date='.$specific_date.'&start_date='.$start_date.'&end_date='.$end_date : ''; ?>" class="filter-tab <?php echo $filter == 'cancelled' ? 'active' : ''; ?>">
                        <i class="fas fa-times-circle"></i> Cancelled (<?php echo $counts['cancelled']; ?>)
                    </a>
                </div>

                <!-- Date Filter Section -->
                <div class="date-filter-section">
                    <form method="GET" action="" id="dateFilterForm">
                        <input type="hidden" name="filter" value="<?php echo htmlspecialchars($filter); ?>">
                        
                        <div class="date-filter-header">
                            <i class="fas fa-calendar-alt"></i>
                            <span>Filter by Date</span>
                        </div>
                        
                        <div class="date-filter-options">
                            <div class="date-option">
                                <input type="radio" name="date_filter" value="" id="filter_all_dates" <?php echo empty($date_filter) ? 'checked' : ''; ?> onchange="toggleCustomInputs('')">
                                <label for="filter_all_dates" class="date-option-label">
                                    <i class="fas fa-calendar"></i> All Dates
                                </label>
                            </div>
                            
                            <div class="date-option">
                                <input type="radio" name="date_filter" value="today" id="filter_today_date" <?php echo $date_filter == 'today' ? 'checked' : ''; ?> onchange="toggleCustomInputs('')">
                                <label for="filter_today_date" class="date-option-label">
                                    <i class="fas fa-calendar-day"></i> Today
                                </label>
                            </div>
                            
                            <div class="date-option">
                                <input type="radio" name="date_filter" value="this_week" id="filter_week_date" <?php echo $date_filter == 'this_week' ? 'checked' : ''; ?> onchange="toggleCustomInputs('')">
                                <label for="filter_week_date" class="date-option-label">
                                    <i class="fas fa-calendar-week"></i> This Week
                                </label>
                            </div>
                            
                            <div class="date-option">
                                <input type="radio" name="date_filter" value="this_month" id="filter_this_month_date" <?php echo $date_filter == 'this_month' ? 'checked' : ''; ?> onchange="toggleCustomInputs('')">
                                <label for="filter_this_month_date" class="date-option-label">
                                    <i class="fas fa-calendar-alt"></i> This Month
                                </label>
                            </div>
                            
                            <div class="date-option">
                                <input type="radio" name="date_filter" value="this_year" id="filter_this_year_date" <?php echo $date_filter == 'this_year' ? 'checked' : ''; ?> onchange="toggleCustomInputs('')">
                                <label for="filter_this_year_date" class="date-option-label">
                                    <i class="fas fa-calendar"></i> This Year
                                </label>
                            </div>
                            
                            <div class="date-option">
                                <input type="radio" name="date_filter" value="custom_month" id="filter_custom_month_date" <?php echo $date_filter == 'custom_month' ? 'checked' : ''; ?> onchange="toggleCustomInputs('month')">
                                <label for="filter_custom_month_date" class="date-option-label">
                                    <i class="fas fa-calendar-alt"></i> Custom Month
                                </label>
                            </div>
                            
                            <div class="date-option">
                                <input type="radio" name="date_filter" value="custom_year" id="filter_custom_year_date" <?php echo $date_filter == 'custom_year' ? 'checked' : ''; ?> onchange="toggleCustomInputs('year')">
                                <label for="filter_custom_year_date" class="date-option-label">
                                    <i class="fas fa-calendar"></i> Custom Year
                                </label>
                            </div>
                            
                            <div class="date-option">
                                <input type="radio" name="date_filter" value="specific_date" id="filter_specific_date" <?php echo $date_filter == 'specific_date' ? 'checked' : ''; ?> onchange="toggleCustomInputs('date')">
                                <label for="filter_specific_date" class="date-option-label">
                                    <i class="fas fa-calendar-day"></i> Specific Date
                                </label>
                            </div>
                            
                            <div class="date-option">
                                <input type="radio" name="date_filter" value="date_range" id="filter_date_range" <?php echo $date_filter == 'date_range' ? 'checked' : ''; ?> onchange="toggleCustomInputs('range')">
                                <label for="filter_date_range" class="date-option-label">
                                    <i class="fas fa-calendar-week"></i> Date Range
                                </label>
                            </div>
                        </div>
                        
                        <!-- Custom Month Inputs -->
                        <div class="custom-date-inputs <?php echo $date_filter == 'custom_month' ? 'active' : ''; ?>" id="customMonthInputs">
                            <div class="filter-group">
                                <label for="month"><i class="fas fa-calendar-alt"></i> Month</label>
                                <select id="month" name="month">
                                    <?php
                                    $months = [
                                        '01' => 'January', '02' => 'February', '03' => 'March', '04' => 'April',
                                        '05' => 'May', '06' => 'June', '07' => 'July', '08' => 'August',
                                        '09' => 'September', '10' => 'October', '11' => 'November', '12' => 'December'
                                    ];
                                    foreach($months as $num => $name):
                                    ?>
                                        <option value="<?php echo $num; ?>" <?php echo $month_filter == $num ? 'selected' : ''; ?>><?php echo $name; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="filter-group">
                                <label for="year"><i class="fas fa-calendar"></i> Year</label>
                                <select id="year" name="year">
                                    <?php for($y = date('Y'); $y >= date('Y') - 5; $y--): ?>
                                        <option value="<?php echo $y; ?>" <?php echo $year_filter == $y ? 'selected' : ''; ?>><?php echo $y; ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                        </div>
                        
                        <!-- Custom Year Input -->
                        <div class="custom-date-inputs <?php echo $date_filter == 'custom_year' ? 'active' : ''; ?>" id="customYearInputs">
                            <div class="filter-group">
                                <label for="year_only"><i class="fas fa-calendar"></i> Year</label>
                                <select id="year_only" name="year">
                                    <?php for($y = date('Y'); $y >= date('Y') - 5; $y--): ?>
                                        <option value="<?php echo $y; ?>" <?php echo $year_filter == $y ? 'selected' : ''; ?>><?php echo $y; ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                        </div>
                        
                        <!-- Specific Date Input -->
                        <div class="custom-date-inputs <?php echo $date_filter == 'specific_date' ? 'active' : ''; ?>" id="customDateInputs">
                            <div class="filter-group">
                                <label for="specific_date"><i class="fas fa-calendar-day"></i> Select Date</label>
                                <input type="date" id="specific_date" name="specific_date" value="<?php echo htmlspecialchars($specific_date); ?>">
                            </div>
                        </div>
                        
                        <!-- Date Range Inputs -->
                        <div class="custom-date-inputs <?php echo $date_filter == 'date_range' ? 'active' : ''; ?>" id="customRangeInputs">
                            <div class="filter-group">
                                <label for="start_date"><i class="fas fa-calendar"></i> Start Date</label>
                                <input type="date" id="start_date" name="start_date" value="<?php echo htmlspecialchars($start_date); ?>">
                            </div>
                            <div class="filter-group">
                                <label for="end_date"><i class="fas fa-calendar"></i> End Date</label>
                                <input type="date" id="end_date" name="end_date" value="<?php echo htmlspecialchars($end_date); ?>">
                            </div>
                        </div>
                        
                        <div class="form-actions" style="margin-top: 15px;">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-filter"></i> Apply Filter
                            </button>
                            <a href="appointments.php?filter=<?php echo $filter; ?>" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Clear Date Filter
                            </a>
                        </div>
                    </form>
                </div>

                <!-- Appointments Grid -->
                <?php if(count($appointments) > 0): ?>
                    <div class="bookings-grid">
                        <?php foreach($appointments as $appointment): ?>
                        <div class="booking-item">
                            <div class="booking-item-header">
                                <div>
                                    <h3><?php echo htmlspecialchars($appointment['service_name']); ?></h3>
                                    <span class="category-badge"><?php echo htmlspecialchars($appointment['category_name']); ?></span>
                                </div>
                                <span class="badge badge-<?php echo $appointment['status']; ?>">
                                    <?php echo ucfirst($appointment['status']); ?>
                                </span>
                            </div>
                            
                            <div class="booking-item-body">
                                <div class="booking-info-row">
                                    <i class="fas fa-user"></i>
                                    <div>
                                        <strong><?php echo htmlspecialchars($appointment['first_name'] . ' ' . $appointment['last_name']); ?></strong>
                                        <small><?php echo htmlspecialchars($appointment['student_id']); ?></small>
                                    </div>
                                </div>
                                
                                <div class="booking-info-row">
                                    <i class="fas fa-calendar"></i>
                                    <span><?php echo date('l, d F Y', strtotime($appointment['booking_date'])); ?></span>
                                </div>
                                
                                <div class="booking-info-row">
                                    <i class="fas fa-clock"></i>
                                    <span><?php echo date('H:i', strtotime($appointment['start_time'])); ?> - <?php echo date('H:i', strtotime($appointment['end_time'])); ?></span>
                                </div>
                                
                                <div class="booking-info-row">
                                    <i class="fas fa-map-marker-alt"></i>
                                    <span><?php echo htmlspecialchars($appointment['location']); ?></span>
                                </div>
                                
                                <div class="booking-info-row">
                                    <i class="fas fa-hashtag"></i>
                                    <span><?php echo htmlspecialchars($appointment['booking_reference']); ?></span>
                                </div>

                                <?php if(!empty($appointment['notes'])): ?>
                                <div class="booking-info-row">
                                    <i class="fas fa-sticky-note"></i>
                                    <span><?php echo htmlspecialchars($appointment['notes']); ?></span>
                                </div>
                                <?php endif; ?>

                                <div class="booking-info-row">
                                    <i class="fas fa-envelope"></i>
                                    <span><?php echo htmlspecialchars($appointment['email']); ?></span>
                                </div>

                                <?php if(!empty($appointment['phone'])): ?>
                                <div class="booking-info-row">
                                    <i class="fas fa-phone"></i>
                                    <span><?php echo htmlspecialchars($appointment['phone']); ?></span>
                                </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="booking-item-footer">
                                <?php if($appointment['status'] == 'pending'): ?>
                                    <button class="btn-action btn-success" onclick="updateStatus(<?php echo $appointment['booking_id']; ?>, 'confirmed')">
                                        <i class="fas fa-check"></i> Confirm
                                    </button>
                                    <button class="btn-action btn-danger" onclick="updateStatus(<?php echo $appointment['booking_id']; ?>, 'cancelled')">
                                        <i class="fas fa-times"></i> Decline
                                    </button>
                                <?php elseif($appointment['status'] == 'confirmed'): ?>
                                    <button class="btn-action btn-primary" onclick="updateStatus(<?php echo $appointment['booking_id']; ?>, 'completed')">
                                        <i class="fas fa-check-circle"></i> Mark Complete
                                    </button>
                                    <button class="btn-action btn-danger" onclick="updateStatus(<?php echo $appointment['booking_id']; ?>, 'cancelled')">
                                        <i class="fas fa-times"></i> Cancel
                                    </button>
                                <?php endif; ?>
                                <a href="appointment-details.php?id=<?php echo $appointment['booking_id']; ?>" class="btn-action btn-secondary">
                                    <i class="fas fa-eye"></i> View Details
                                </a>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="section">
                        <div class="empty-state">
                            <i class="fas fa-calendar-times"></i>
                            <h3>No Appointments Found</h3>
                            <p>There are no appointments matching your filter criteria</p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script src="js/dashboard.js"></script>
    <script>
        function toggleCustomInputs(type) {
            // Hide all custom inputs
            document.getElementById('customMonthInputs')?.classList.remove('active');
            document.getElementById('customYearInputs')?.classList.remove('active');
            document.getElementById('customDateInputs')?.classList.remove('active');
            document.getElementById('customRangeInputs')?.classList.remove('active');
            
            // Show the selected custom input
            if(type === 'month') {
                document.getElementById('customMonthInputs')?.classList.add('active');
            } else if(type === 'year') {
                document.getElementById('customYearInputs')?.classList.add('active');
            } else if(type === 'date') {
                document.getElementById('customDateInputs')?.classList.add('active');
            } else if(type === 'range') {
                document.getElementById('customRangeInputs')?.classList.add('active');
            }
        }
        
        function updateStatus(bookingId, newStatus) {
            const messages = {
                'cancelled': 'Are you sure you want to cancel this appointment?',
                'completed': 'Mark this appointment as completed?'
            };
            
            const titles = {
                'cancelled': 'Cancel Appointment',
                'completed': 'Complete Appointment'
            };
            
            showConfirmModal(
                titles[newStatus],
                messages[newStatus],
                function() {
                    window.location.href = 'update-appointment.php?id=' + bookingId + '&status=' + newStatus;
                }
            );
        }
    </script>
    <?php include '../assets/includes/modals.php'; ?>
    <link rel="stylesheet" href="../assets/css/modals.css">
    <script src="../assets/js/modals.js"></script>
</body>
</html>
