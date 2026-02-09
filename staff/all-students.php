<?php
session_start();
if(!isset($_SESSION['staff_id'])) {
    header('Location: ../index.php');
    exit();
}

require_once '../config/database.php';

$db = new Database();
$conn = $db->connect();

// Get filter parameters
$faculty_filter = isset($_GET['faculty']) ? $_GET['faculty'] : '';
$year_filter = isset($_GET['year']) ? $_GET['year'] : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$date_filter = isset($_GET['date_filter']) ? $_GET['date_filter'] : '';
$month_filter = isset($_GET['month']) ? $_GET['month'] : '';
$year_date_filter = isset($_GET['year_date']) ? $_GET['year_date'] : '';
$specific_date = isset($_GET['specific_date']) ? $_GET['specific_date'] : '';

// Build query
$query = "
    SELECT s.*, f.faculty_name, f.faculty_code,
           COUNT(DISTINCT b.booking_id) as total_bookings,
           MAX(b.booking_date) as last_booking_date,
           COUNT(DISTINCT CASE WHEN b.status = 'completed' THEN b.booking_id END) as completed_sessions,
           COUNT(DISTINCT CASE WHEN b.status = 'cancelled' THEN b.booking_id END) as cancelled_sessions,
           COUNT(DISTINCT CASE WHEN b.status = 'no_show' THEN b.booking_id END) as no_show_sessions
    FROM students s
    LEFT JOIN faculties f ON s.faculty_id = f.faculty_id
    LEFT JOIN bookings b ON s.student_id = b.student_id
    WHERE 1=1
";

$params = [];

if(!empty($faculty_filter)) {
    $query .= " AND s.faculty_id = ?";
    $params[] = $faculty_filter;
}

if(!empty($year_filter)) {
    $query .= " AND s.year_of_study = ?";
    $params[] = $year_filter;
}

if(!empty($status_filter)) {
    $query .= " AND s.status = ?";
    $params[] = $status_filter;
}

if(!empty($search)) {
    $query .= " AND (s.student_id LIKE ? OR s.first_name LIKE ? OR s.last_name LIKE ? OR s.email LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
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
            if(!empty($month_filter) && !empty($year_date_filter)) {
                $query .= " AND YEAR(b.booking_date) = ? AND MONTH(b.booking_date) = ?";
                $params[] = $year_date_filter;
                $params[] = $month_filter;
            }
            break;
        case 'custom_year':
            if(!empty($year_date_filter)) {
                $query .= " AND YEAR(b.booking_date) = ?";
                $params[] = $year_date_filter;
            }
            break;
        case 'specific_date':
            if(!empty($specific_date)) {
                $query .= " AND DATE(b.booking_date) = ?";
                $params[] = $specific_date;
            }
            break;
    }
}

$query .= " GROUP BY s.student_id ORDER BY s.student_id DESC";

$stmt = $conn->prepare($query);
$stmt->execute($params);
$students = $stmt->fetchAll();

// Get faculties for filter
$stmt = $conn->query("SELECT * FROM faculties WHERE status = 'active' ORDER BY faculty_name");
$faculties = $stmt->fetchAll();

// Get statistics
$stmt = $conn->query("SELECT COUNT(*) as total FROM students WHERE status = 'active'");
$total_students = $stmt->fetch()['total'];

$stmt = $conn->query("SELECT COUNT(DISTINCT student_id) as total FROM bookings");
$students_with_bookings = $stmt->fetch()['total'];

$students_without_bookings = $total_students - $students_with_bookings;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Students - WSU Booking</title>
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .filters-section {
            background: var(--white);
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }
        
        .filters-grid {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 1fr;
            gap: 15px;
            align-items: end;
        }
        
        .date-filter-section {
            background: #f9fafb;
            padding: 15px;
            border-radius: 8px;
            margin-top: 15px;
            border: 2px solid #e5e7eb;
        }
        
        .date-filter-header {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 15px;
            font-weight: 600;
            color: var(--dark);
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
            padding: 10px 15px;
            background: var(--white);
            border: 2px solid #e5e7eb;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 13px;
            font-weight: 600;
        }
        
        .date-option input[type="radio"]:checked + .date-option-label {
            background: linear-gradient(135deg, rgba(29, 78, 216, 0.1) 0%, rgba(29, 78, 216, 0.05) 100%);
            border-color: var(--blue);
            color: var(--blue);
            box-shadow: 0 0 0 3px rgba(29, 78, 216, 0.1);
        }
        
        .date-option-label:hover {
            border-color: var(--blue);
        }
        
        .custom-date-inputs {
            display: none;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 10px;
            margin-top: 10px;
            padding: 15px;
            background: var(--white);
            border-radius: 6px;
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
        }
        
        .filter-group input,
        .filter-group select {
            padding: 10px 12px;
            border: 2px solid #e5e7eb;
            border-radius: 6px;
            font-size: 14px;
            transition: all 0.3s;
        }
        
        .filter-group input:focus,
        .filter-group select:focus {
            outline: none;
            border-color: var(--blue);
            box-shadow: 0 0 0 3px rgba(29, 78, 216, 0.1);
        }
        
        .students-table-container {
            background: var(--white);
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        
        .students-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .students-table thead {
            background: #f9fafb;
            border-bottom: 2px solid #e5e7eb;
        }
        
        .students-table th {
            padding: 15px;
            text-align: left;
            font-size: 13px;
            font-weight: 700;
            color: var(--dark);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .students-table td {
            padding: 15px;
            border-bottom: 1px solid #f3f4f6;
            font-size: 14px;
            color: #6b7280;
        }
        
        .students-table tbody tr {
            transition: all 0.2s;
        }
        
        .students-table tbody tr:hover {
            background: #f9fafb;
        }
        
        .student-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .student-avatar {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #000000 0%, #1d4ed8 100%);
            color: var(--white);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 14px;
            flex-shrink: 0;
        }
        
        .student-details h4 {
            font-size: 14px;
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 3px;
        }
        
        .student-details p {
            font-size: 12px;
            color: #9ca3af;
            margin: 0;
        }
        
        .progress-indicator {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .progress-bar {
            width: 80px;
            height: 6px;
            background: #e5e7eb;
            border-radius: 3px;
            overflow: hidden;
        }
        
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #10b981 0%, #059669 100%);
            transition: width 0.3s;
        }
        
        .progress-text {
            font-size: 12px;
            font-weight: 600;
            color: #6b7280;
        }
        
        .action-buttons {
            display: flex;
            gap: 8px;
        }
        
        .btn-table {
            padding: 6px 12px;
            border: none;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        
        .btn-table.btn-view {
            background: #dbeafe;
            color: #2563eb;
        }
        
        .btn-table.btn-view:hover {
            background: #2563eb;
            color: var(--white);
        }
        
        .btn-table.btn-contact {
            background: #d1fae5;
            color: #10b981;
        }
        
        .btn-table.btn-contact:hover {
            background: #10b981;
            color: var(--white);
        }
        
        .no-bookings-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 10px;
            background: #fef3c7;
            color: #f59e0b;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
        }
        
        @media (max-width: 1200px) {
            .filters-grid {
                grid-template-columns: 1fr 1fr;
            }
        }
        
        @media (max-width: 768px) {
            .filters-grid {
                grid-template-columns: 1fr;
            }
            
            .students-table-container {
                overflow-x: auto;
            }
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
                        <h1>All Students Directory</h1>
                        <p>View and manage all students, track academic progress, and monitor engagement</p>
                        <div class="hero-stats">
                            <div class="hero-stat">
                                <i class="fas fa-users"></i>
                                <span><?php echo $total_students; ?> Total Students</span>
                            </div>
                            <div class="hero-stat">
                                <i class="fas fa-calendar-check"></i>
                                <span><?php echo $students_with_bookings; ?> With Bookings</span>
                            </div>
                            <div class="hero-stat">
                                <i class="fas fa-user-clock"></i>
                                <span><?php echo $students_without_bookings; ?> Need Engagement</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filters -->
                <div class="filters-section">
                    <form method="GET" action="">
                        <div class="filters-grid">
                            <div class="filter-group">
                                <label for="search"><i class="fas fa-search"></i> Search</label>
                                <input type="text" id="search" name="search" placeholder="Student ID, name, or email..." value="<?php echo htmlspecialchars($search); ?>">
                            </div>
                            
                            <div class="filter-group">
                                <label for="faculty"><i class="fas fa-university"></i> Faculty</label>
                                <select id="faculty" name="faculty">
                                    <option value="">All Faculties</option>
                                    <?php foreach($faculties as $faculty): ?>
                                        <option value="<?php echo $faculty['faculty_id']; ?>" <?php echo $faculty_filter == $faculty['faculty_id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($faculty['faculty_code']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="filter-group">
                                <label for="year"><i class="fas fa-graduation-cap"></i> Year</label>
                                <select id="year" name="year">
                                    <option value="">All Years</option>
                                    <?php for($i = 1; $i <= 6; $i++): ?>
                                        <option value="<?php echo $i; ?>" <?php echo $year_filter == $i ? 'selected' : ''; ?>>Year <?php echo $i; ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            
                            <div class="filter-group">
                                <label for="status"><i class="fas fa-check-circle"></i> Status</label>
                                <select id="status" name="status">
                                    <option value="">All Status</option>
                                    <option value="active" <?php echo $status_filter == 'active' ? 'selected' : ''; ?>>Active</option>
                                    <option value="inactive" <?php echo $status_filter == 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                    <option value="suspended" <?php echo $status_filter == 'suspended' ? 'selected' : ''; ?>>Suspended</option>
                                    <option value="graduated" <?php echo $status_filter == 'graduated' ? 'selected' : ''; ?>>Graduated</option>
                                </select>
                            </div>
                        </div>
                        
                        <!-- Date Filter Section -->
                        <div class="date-filter-section">
                            <div class="date-filter-header">
                                <i class="fas fa-calendar-alt"></i>
                                <span>Filter by Booking Date</span>
                            </div>
                            
                            <div class="date-filter-options">
                                <div class="date-option">
                                    <input type="radio" name="date_filter" value="" id="filter_all" <?php echo empty($date_filter) ? 'checked' : ''; ?> onchange="toggleCustomInputs('')">
                                    <label for="filter_all" class="date-option-label">
                                        <i class="fas fa-calendar"></i> All Time
                                    </label>
                                </div>
                                
                                <div class="date-option">
                                    <input type="radio" name="date_filter" value="today" id="filter_today" <?php echo $date_filter == 'today' ? 'checked' : ''; ?> onchange="toggleCustomInputs('')">
                                    <label for="filter_today" class="date-option-label">
                                        <i class="fas fa-calendar-day"></i> Today
                                    </label>
                                </div>
                                
                                <div class="date-option">
                                    <input type="radio" name="date_filter" value="this_week" id="filter_week" <?php echo $date_filter == 'this_week' ? 'checked' : ''; ?> onchange="toggleCustomInputs('')">
                                    <label for="filter_week" class="date-option-label">
                                        <i class="fas fa-calendar-week"></i> This Week
                                    </label>
                                </div>
                                
                                <div class="date-option">
                                    <input type="radio" name="date_filter" value="this_month" id="filter_this_month" <?php echo $date_filter == 'this_month' ? 'checked' : ''; ?> onchange="toggleCustomInputs('')">
                                    <label for="filter_this_month" class="date-option-label">
                                        <i class="fas fa-calendar-alt"></i> This Month
                                    </label>
                                </div>
                                
                                <div class="date-option">
                                    <input type="radio" name="date_filter" value="this_year" id="filter_this_year" <?php echo $date_filter == 'this_year' ? 'checked' : ''; ?> onchange="toggleCustomInputs('')">
                                    <label for="filter_this_year" class="date-option-label">
                                        <i class="fas fa-calendar"></i> This Year
                                    </label>
                                </div>
                                
                                <div class="date-option">
                                    <input type="radio" name="date_filter" value="custom_month" id="filter_custom_month" <?php echo $date_filter == 'custom_month' ? 'checked' : ''; ?> onchange="toggleCustomInputs('month')">
                                    <label for="filter_custom_month" class="date-option-label">
                                        <i class="fas fa-calendar-alt"></i> Custom Month
                                    </label>
                                </div>
                                
                                <div class="date-option">
                                    <input type="radio" name="date_filter" value="custom_year" id="filter_custom_year" <?php echo $date_filter == 'custom_year' ? 'checked' : ''; ?> onchange="toggleCustomInputs('year')">
                                    <label for="filter_custom_year" class="date-option-label">
                                        <i class="fas fa-calendar"></i> Custom Year
                                    </label>
                                </div>
                                
                                <div class="date-option">
                                    <input type="radio" name="date_filter" value="specific_date" id="filter_specific" <?php echo $date_filter == 'specific_date' ? 'checked' : ''; ?> onchange="toggleCustomInputs('date')">
                                    <label for="filter_specific" class="date-option-label">
                                        <i class="fas fa-calendar-day"></i> Specific Date
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
                                    <label for="year_date"><i class="fas fa-calendar"></i> Year</label>
                                    <select id="year_date" name="year_date">
                                        <?php for($y = date('Y'); $y >= date('Y') - 5; $y--): ?>
                                            <option value="<?php echo $y; ?>" <?php echo $year_date_filter == $y ? 'selected' : ''; ?>><?php echo $y; ?></option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                            </div>
                            
                            <!-- Custom Year Input -->
                            <div class="custom-date-inputs <?php echo $date_filter == 'custom_year' ? 'active' : ''; ?>" id="customYearInputs">
                                <div class="filter-group">
                                    <label for="year_date_only"><i class="fas fa-calendar"></i> Year</label>
                                    <select id="year_date_only" name="year_date">
                                        <?php for($y = date('Y'); $y >= date('Y') - 5; $y--): ?>
                                            <option value="<?php echo $y; ?>" <?php echo $year_date_filter == $y ? 'selected' : ''; ?>><?php echo $y; ?></option>
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
                        </div>
                        
                        <div style="display: flex; gap: 10px; margin-top: 15px;">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-filter"></i> Apply Filters
                            </button>
                            <a href="all-students.php" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Clear All
                            </a>
                        </div>
                    </form>
                </div>

                <!-- Students Table -->
                <div class="students-table-container">
                    <table class="students-table">
                        <thead>
                            <tr>
                                <th>Student</th>
                                <th>Faculty</th>
                                <th>Year & Type</th>
                                <th>Status</th>
                                <th>Success Score</th>
                                <th>Engagement</th>
                                <th>Sessions</th>
                                <th>Last Activity</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(count($students) > 0): ?>
                                <?php foreach($students as $student): 
                                    // Calculate success score (0-100)
                                    $success_score = 0;
                                    if($student['total_bookings'] > 0) {
                                        $completion_rate = ($student['completed_sessions'] / $student['total_bookings']) * 100;
                                        $engagement_score = min(100, ($student['total_bookings'] / 10) * 100); // Max at 10 bookings
                                        $no_show_penalty = ($student['no_show_sessions'] * 10); // -10 per no-show
                                        $cancelled_penalty = ($student['cancelled_sessions'] * 5); // -5 per cancellation
                                        
                                        $success_score = max(0, min(100, 
                                            ($completion_rate * 0.5) + // 50% weight on completion
                                            ($engagement_score * 0.3) + // 30% weight on engagement
                                            (20) - // 20% base score
                                            $no_show_penalty - 
                                            $cancelled_penalty
                                        ));
                                    }
                                    $success_score = round($success_score);
                                    
                                    // Determine score color
                                    if($success_score >= 80) {
                                        $score_color = '#10b981'; // Green
                                        $score_bg = '#d1fae5';
                                        $score_label = 'Excellent';
                                    } elseif($success_score >= 60) {
                                        $score_color = '#2563eb'; // Blue
                                        $score_bg = '#dbeafe';
                                        $score_label = 'Good';
                                    } elseif($success_score >= 40) {
                                        $score_color = '#f59e0b'; // Orange
                                        $score_bg = '#fef3c7';
                                        $score_label = 'Fair';
                                    } else {
                                        $score_color = '#ef4444'; // Red
                                        $score_bg = '#fee2e2';
                                        $score_label = 'Needs Support';
                                    }
                                ?>
                                <tr>
                                    <td>
                                        <div class="student-info">
                                            <div class="student-avatar">
                                                <?php echo strtoupper(substr($student['first_name'], 0, 1) . substr($student['last_name'], 0, 1)); ?>
                                            </div>
                                            <div class="student-details">
                                                <h4><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></h4>
                                                <p><?php echo htmlspecialchars($student['student_id']); ?></p>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if($student['faculty_name']): ?>
                                            <strong><?php echo htmlspecialchars($student['faculty_code']); ?></strong>
                                        <?php else: ?>
                                            <span style="color: #9ca3af;">Not assigned</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div>
                                            <strong>Year <?php echo $student['year_of_study']; ?></strong><br>
                                            <small style="color: #9ca3af;"><?php echo ucfirst(str_replace('_', ' ', $student['student_type'])); ?></small>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge badge-<?php echo $student['status']; ?>">
                                            <?php echo ucfirst($student['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div style="display: flex; flex-direction: column; align-items: center; gap: 8px;">
                                            <div style="position: relative; width: 60px; height: 60px;">
                                                <svg style="transform: rotate(-90deg);" width="60" height="60">
                                                    <circle cx="30" cy="30" r="25" fill="none" stroke="#e5e7eb" stroke-width="6"/>
                                                    <circle cx="30" cy="30" r="25" fill="none" stroke="<?php echo $score_color; ?>" stroke-width="6" 
                                                            stroke-dasharray="<?php echo (2 * 3.14159 * 25); ?>" 
                                                            stroke-dashoffset="<?php echo (2 * 3.14159 * 25) * (1 - $success_score / 100); ?>"
                                                            stroke-linecap="round"/>
                                                </svg>
                                                <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); font-weight: 700; font-size: 14px; color: <?php echo $score_color; ?>;">
                                                    <?php echo $success_score; ?>
                                                </div>
                                            </div>
                                            <span style="font-size: 11px; font-weight: 600; padding: 3px 8px; border-radius: 10px; background: <?php echo $score_bg; ?>; color: <?php echo $score_color; ?>;">
                                                <?php echo $score_label; ?>
                                            </span>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if($student['total_bookings'] > 0): ?>
                                            <div class="progress-indicator">
                                                <div class="progress-bar">
                                                    <div class="progress-fill" style="width: <?php echo min(100, ($student['completed_sessions'] / max(1, $student['total_bookings'])) * 100); ?>%"></div>
                                                </div>
                                                <span class="progress-text"><?php echo round(($student['completed_sessions'] / max(1, $student['total_bookings'])) * 100); ?>%</span>
                                            </div>
                                        <?php else: ?>
                                            <span class="no-bookings-badge">
                                                <i class="fas fa-exclamation-circle"></i> No bookings
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <strong><?php echo $student['completed_sessions']; ?></strong> / <?php echo $student['total_bookings']; ?>
                                    </td>
                                    <td>
                                        <?php if($student['last_booking_date']): ?>
                                            <?php echo date('d M Y', strtotime($student['last_booking_date'])); ?>
                                        <?php else: ?>
                                            <span style="color: #9ca3af;">Never</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="student-profile.php?id=<?php echo $student['student_id']; ?>" class="btn-table btn-view">
                                                <i class="fas fa-eye"></i> View
                                            </a>
                                            <a href="mailto:<?php echo $student['email']; ?>" class="btn-table btn-contact">
                                                <i class="fas fa-envelope"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="9" style="text-align: center; padding: 40px;">
                                        <i class="fas fa-users" style="font-size: 48px; color: #d1d5db; margin-bottom: 10px;"></i>
                                        <p style="color: #9ca3af;">No students found matching your criteria</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Summary Stats -->
                <div class="section" style="margin-top: 20px;">
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-icon blue">
                                <i class="fas fa-users"></i>
                            </div>
                            <div class="stat-info">
                                <h3><?php echo count($students); ?></h3>
                                <p>Students Displayed</p>
                            </div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-icon green">
                                <i class="fas fa-user-check"></i>
                            </div>
                            <div class="stat-info">
                                <h3><?php echo count(array_filter($students, function($s) { return $s['total_bookings'] > 0; })); ?></h3>
                                <p>Engaged Students</p>
                            </div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-icon orange">
                                <i class="fas fa-user-clock"></i>
                            </div>
                            <div class="stat-info">
                                <h3><?php echo count(array_filter($students, function($s) { return $s['total_bookings'] == 0; })); ?></h3>
                                <p>Need Outreach</p>
                            </div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-icon red">
                                <i class="fas fa-calendar-check"></i>
                            </div>
                            <div class="stat-info">
                                <h3><?php echo array_sum(array_column($students, 'completed_sessions')); ?></h3>
                                <p>Total Sessions</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="js/dashboard.js"></script>
    <script>
        function toggleCustomInputs(type) {
            // Hide all custom inputs
            document.getElementById('customMonthInputs').classList.remove('active');
            document.getElementById('customYearInputs').classList.remove('active');
            document.getElementById('customDateInputs').classList.remove('active');
            
            // Show the selected custom input
            if(type === 'month') {
                document.getElementById('customMonthInputs').classList.add('active');
            } else if(type === 'year') {
                document.getElementById('customYearInputs').classList.add('active');
            } else if(type === 'date') {
                document.getElementById('customDateInputs').classList.add('active');
            }
        }
    </script>
</body>
</html>
