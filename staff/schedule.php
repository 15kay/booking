<?php
session_start();
if(!isset($_SESSION['staff_id'])) {
    header('Location: ../index.php');
    exit();
}

require_once '../config/database.php';

$db = new Database();
$conn = $db->connect();

// Get date filter parameters
$date_filter = isset($_GET['date_filter']) ? $_GET['date_filter'] : '';
$month_filter = isset($_GET['month']) ? $_GET['month'] : date('m');
$year_filter = isset($_GET['year']) ? $_GET['year'] : date('Y');
$specific_date = isset($_GET['specific_date']) ? $_GET['specific_date'] : '';

// Get staff schedules
$stmt = $conn->prepare("
    SELECT ss.*, s.service_name, s.service_code, sc.category_name
    FROM staff_schedules ss
    JOIN services s ON ss.service_id = s.service_id
    JOIN service_categories sc ON s.category_id = sc.category_id
    WHERE ss.staff_id = ? AND ss.status = 'active'
    ORDER BY ss.day_of_week, ss.start_time
");
$stmt->execute([$_SESSION['staff_id']]);
$schedules = $stmt->fetchAll();

// Get staff unavailability with date filtering
$unavail_query = "
    SELECT * FROM staff_unavailability
    WHERE staff_id = ? AND end_date >= CURDATE()
";
$unavail_params = [$_SESSION['staff_id']];

if(!empty($date_filter)) {
    switch($date_filter) {
        case 'this_month':
            $unavail_query .= " AND YEAR(start_date) = YEAR(CURDATE()) AND MONTH(start_date) = MONTH(CURDATE())";
            break;
        case 'this_year':
            $unavail_query .= " AND YEAR(start_date) = YEAR(CURDATE())";
            break;
        case 'custom_month':
            if(!empty($month_filter) && !empty($year_filter)) {
                $unavail_query .= " AND YEAR(start_date) = ? AND MONTH(start_date) = ?";
                $unavail_params[] = $year_filter;
                $unavail_params[] = $month_filter;
            }
            break;
        case 'custom_year':
            if(!empty($year_filter)) {
                $unavail_query .= " AND YEAR(start_date) = ?";
                $unavail_params[] = $year_filter;
            }
            break;
        case 'specific_date':
            if(!empty($specific_date)) {
                $unavail_query .= " AND ? BETWEEN start_date AND end_date";
                $unavail_params[] = $specific_date;
            }
            break;
    }
}

$unavail_query .= " ORDER BY start_date";

$stmt = $conn->prepare($unavail_query);
$stmt->execute($unavail_params);
$unavailability = $stmt->fetchAll();

// Get bookings with date filtering for statistics
$bookings_query = "
    SELECT COUNT(*) as count,
           DATE(booking_date) as date,
           MONTH(booking_date) as month,
           YEAR(booking_date) as year
    FROM bookings
    WHERE staff_id = ?
";
$bookings_params = [$_SESSION['staff_id']];

if(!empty($date_filter)) {
    switch($date_filter) {
        case 'today':
            $bookings_query .= " AND DATE(booking_date) = CURDATE()";
            break;
        case 'this_week':
            $bookings_query .= " AND YEARWEEK(booking_date, 1) = YEARWEEK(CURDATE(), 1)";
            break;
        case 'this_month':
            $bookings_query .= " AND YEAR(booking_date) = YEAR(CURDATE()) AND MONTH(booking_date) = MONTH(CURDATE())";
            break;
        case 'this_year':
            $bookings_query .= " AND YEAR(booking_date) = YEAR(CURDATE())";
            break;
        case 'custom_month':
            if(!empty($month_filter) && !empty($year_filter)) {
                $bookings_query .= " AND YEAR(booking_date) = ? AND MONTH(booking_date) = ?";
                $bookings_params[] = $year_filter;
                $bookings_params[] = $month_filter;
            }
            break;
        case 'custom_year':
            if(!empty($year_filter)) {
                $bookings_query .= " AND YEAR(booking_date) = ?";
                $bookings_params[] = $year_filter;
            }
            break;
        case 'specific_date':
            if(!empty($specific_date)) {
                $bookings_query .= " AND DATE(booking_date) = ?";
                $bookings_params[] = $specific_date;
            }
            break;
    }
}

$bookings_query .= " GROUP BY date";

$stmt = $conn->prepare($bookings_query);
$stmt->execute($bookings_params);
$bookings_by_date = $stmt->fetchAll();

// Get all services for adding new schedule
$stmt = $conn->prepare("SELECT s.*, sc.category_name FROM services s JOIN service_categories sc ON s.category_id = sc.category_id WHERE s.status = 'active' ORDER BY sc.category_name, s.service_name");
$stmt->execute();
$services = $stmt->fetchAll();

// Group schedules by day
$days = [
    1 => 'Monday',
    2 => 'Tuesday',
    3 => 'Wednesday',
    4 => 'Thursday',
    5 => 'Friday'
];

$schedules_by_day = [];
foreach($schedules as $schedule) {
    $day = $schedule['day_of_week'];
    if(!isset($schedules_by_day[$day])) {
        $schedules_by_day[$day] = [];
    }
    $schedules_by_day[$day][] = $schedule;
}

// Calculate filtered bookings count
$filtered_bookings_count = array_sum(array_column($bookings_by_date, 'count'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Schedule Management - WSU Booking</title>
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Calendar View */
        .calendar-container {
            background: var(--white);
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
            overflow-x: auto;
        }
        
        .calendar-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #e5e7eb;
        }
        
        .calendar-header h3 {
            font-size: 20px;
            color: var(--dark);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .view-toggle {
            display: flex;
            gap: 10px;
        }
        
        .view-btn {
            padding: 8px 16px;
            border: 2px solid #e5e7eb;
            background: var(--white);
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            color: #6b7280;
            transition: all 0.3s;
        }
        
        .view-btn.active {
            background: var(--blue);
            border-color: var(--blue);
            color: var(--white);
        }
        
        .view-btn:hover:not(.active) {
            border-color: var(--blue);
            color: var(--blue);
        }
        
        .calendar-grid {
            display: grid;
            grid-template-columns: 80px repeat(5, 1fr);
            gap: 1px;
            background: #e5e7eb;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            overflow: hidden;
            min-width: 800px;
        }
        
        .calendar-time-label,
        .calendar-day-header,
        .calendar-cell {
            background: var(--white);
            padding: 12px;
            min-height: 60px;
            overflow: hidden;
        }
        
        .calendar-day-header {
            text-align: center;
            font-weight: 700;
            font-size: 14px;
            color: var(--dark);
            background: #f9fafb;
            padding: 15px 12px;
            min-height: auto;
        }
        
        .calendar-day-header .day-name {
            display: block;
            margin-bottom: 4px;
        }
        
        .calendar-day-header .day-abbr {
            display: block;
            font-size: 12px;
            color: #6b7280;
            font-weight: 500;
        }
        
        .calendar-time-label {
            font-size: 12px;
            color: #6b7280;
            font-weight: 600;
            text-align: right;
            padding-right: 15px;
            background: #f9fafb;
            display: flex;
            align-items: center;
            justify-content: flex-end;
        }
        
        .calendar-cell {
            position: relative;
            cursor: pointer;
            transition: all 0.2s;
            overflow-y: auto;
            max-height: 120px;
        }
        
        .calendar-cell:hover {
            background: #f9fafb;
        }
        
        .calendar-cell.has-schedule {
            background: linear-gradient(135deg, rgba(29, 78, 216, 0.1) 0%, rgba(29, 78, 216, 0.05) 100%);
            border-left: 3px solid var(--blue);
        }
        
        .calendar-cell.has-schedule:hover {
            background: linear-gradient(135deg, rgba(29, 78, 216, 0.15) 0%, rgba(29, 78, 216, 0.08) 100%);
        }
        
        .schedule-block {
            font-size: 11px;
            line-height: 1.3;
            margin-bottom: 8px;
            padding-bottom: 8px;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .schedule-block:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }
        
        .schedule-block-title {
            font-weight: 700;
            color: var(--blue);
            margin-bottom: 3px;
            display: block;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .schedule-block-time {
            color: #6b7280;
            display: block;
            margin-bottom: 2px;
        }
        
        .schedule-block-location {
            color: #9ca3af;
            display: block;
            font-size: 10px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .schedule-block-actions {
            margin-top: 6px;
            display: flex;
            gap: 4px;
        }
        
        .btn-icon-small {
            width: 24px;
            height: 24px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 11px;
            transition: all 0.3s;
        }
        
        .btn-icon-small.btn-edit {
            background: #dbeafe;
            color: #2563eb;
        }
        
        .btn-icon-small.btn-edit:hover {
            background: #2563eb;
            color: var(--white);
        }
        
        .btn-icon-small.btn-delete {
            background: #fee2e2;
            color: #ef4444;
        }
        
        .btn-icon-small.btn-delete:hover {
            background: #ef4444;
            color: var(--white);
        }
        
        .empty-cell {
            color: #d1d5db;
            font-size: 11px;
            text-align: center;
        }
        
        /* Scrollbar styling for calendar cells */
        .calendar-cell::-webkit-scrollbar {
            width: 4px;
        }
        
        .calendar-cell::-webkit-scrollbar-track {
            background: #f3f4f6;
        }
        
        .calendar-cell::-webkit-scrollbar-thumb {
            background: #d1d5db;
            border-radius: 2px;
        }
        
        .calendar-cell::-webkit-scrollbar-thumb:hover {
            background: #9ca3af;
        }
        
        /* List View (original) */
        .schedule-list-view {
            display: none;
        }
        
        .schedule-list-view.active {
            display: block;
        }
        
        .schedule-grid {
            display: grid;
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .day-schedule {
            background: var(--white);
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        .day-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 2px solid #e5e7eb;
        }
        
        .day-header h3 {
            font-size: 18px;
            color: var(--dark);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .day-header .day-icon {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #000000 0%, #1d4ed8 100%);
            color: var(--white);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 14px;
        }
        
        .schedule-slots {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        
        .schedule-slot {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px;
            background: #f9fafb;
            border-radius: 8px;
            border-left: 4px solid var(--blue);
        }
        
        .slot-time {
            min-width: 120px;
            font-weight: 600;
            color: var(--blue);
            font-size: 14px;
        }
        
        .slot-service {
            flex: 1;
        }
        
        .slot-service h4 {
            font-size: 15px;
            color: var(--dark);
            margin-bottom: 4px;
        }
        
        .slot-service p {
            font-size: 13px;
            color: #6b7280;
        }
        
        .slot-location {
            font-size: 13px;
            color: #6b7280;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        
        .slot-actions {
            display: flex;
            gap: 8px;
        }
        
        .btn-icon {
            width: 32px;
            height: 32px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s;
        }
        
        .btn-icon.btn-edit {
            background: #dbeafe;
            color: #2563eb;
        }
        
        .btn-icon.btn-edit:hover {
            background: #2563eb;
            color: var(--white);
        }
        
        .btn-icon.btn-delete {
            background: #fee2e2;
            color: #ef4444;
        }
        
        .btn-icon.btn-delete:hover {
            background: #ef4444;
            color: var(--white);
        }
        
        .empty-day {
            text-align: center;
            padding: 30px;
            color: #9ca3af;
        }
        
        .empty-day i {
            font-size: 48px;
            margin-bottom: 10px;
            opacity: 0.5;
        }
        
        .unavailability-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        
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
        
        .unavailability-item {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px;
            background: #fef3c7;
            border-radius: 8px;
            border-left: 4px solid #f59e0b;
        }
        
        .unavailability-icon {
            width: 40px;
            height: 40px;
            background: rgba(245, 158, 11, 0.2);
            color: #f59e0b;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
        }
        
        .unavailability-info {
            flex: 1;
        }
        
        .unavailability-info h4 {
            font-size: 15px;
            color: var(--dark);
            margin-bottom: 4px;
        }
        
        .unavailability-info p {
            font-size: 13px;
            color: #6b7280;
        }
        
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 9999;
            align-items: center;
            justify-content: center;
            animation: fadeIn 0.3s;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        .modal-overlay.active {
            display: flex;
        }
        
        .modal-content {
            background: var(--white);
            border-radius: 12px;
            padding: 0;
            max-width: 600px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            animation: slideUp 0.3s;
        }
        
        @keyframes slideUp {
            from {
                transform: translateY(50px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
        
        .unavailability-modal {
            max-width: 700px;
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 25px 30px;
            border-bottom: 2px solid #e5e7eb;
            background: linear-gradient(135deg, #f9fafb 0%, #ffffff 100%);
            border-radius: 12px 12px 0 0;
        }
        
        .modal-header h2 {
            font-size: 22px;
            color: var(--dark);
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .modal-header h2 i {
            color: var(--blue);
            font-size: 24px;
        }
        
        .modal-header-content {
            display: flex;
            gap: 15px;
            align-items: flex-start;
        }
        
        .modal-icon-header {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--white);
            font-size: 24px;
            flex-shrink: 0;
        }
        
        .modal-header h2 {
            font-size: 22px;
            color: var(--dark);
            margin-bottom: 5px;
        }
        
        .modal-subtitle {
            font-size: 14px;
            color: #6b7280;
            margin: 0;
        }
        
        .modal-close {
            background: none;
            border: none;
            font-size: 32px;
            color: #9ca3af;
            cursor: pointer;
            padding: 0;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            transition: all 0.3s;
            line-height: 1;
        }
        
        .modal-close:hover {
            background: #fee2e2;
            color: #ef4444;
            transform: rotate(90deg);
        }
        
        /* Modal Form Styling */
        .modal-content form {
            padding: 30px;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        .form-grid .form-group.full-width {
            grid-column: 1 / -1;
        }
        
        .form-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        
        .form-group label {
            font-size: 14px;
            font-weight: 600;
            color: var(--dark);
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .form-group label i {
            color: var(--blue);
            font-size: 16px;
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            padding: 12px 15px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 14px;
            font-family: inherit;
            transition: all 0.3s;
            background: var(--white);
        }
        
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--blue);
            box-shadow: 0 0 0 4px rgba(29, 78, 216, 0.1);
        }
        
        .form-group select {
            cursor: pointer;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%236b7280' d='M6 9L1 4h10z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 12px center;
            padding-right: 40px;
        }
        
        .form-group input[type="number"] {
            -moz-appearance: textfield;
        }
        
        .form-group input[type="number"]::-webkit-inner-spin-button,
        .form-group input[type="number"]::-webkit-outer-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }
        
        .form-actions {
            display: flex;
            gap: 12px;
            justify-content: flex-end;
            margin-top: 25px;
            padding-top: 25px;
            border-top: 2px solid #f3f4f6;
        }
        
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
            text-decoration: none;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #1d4ed8 0%, #2563eb 100%);
            color: var(--white);
            box-shadow: 0 4px 12px rgba(29, 78, 216, 0.3);
        }
        
        .btn-primary:hover {
            background: linear-gradient(135deg, #1e40af 0%, #1d4ed8 100%);
            box-shadow: 0 6px 16px rgba(29, 78, 216, 0.4);
            transform: translateY(-2px);
        }
        
        .btn-secondary {
            background: #f3f4f6;
            color: #6b7280;
        }
        
        .btn-secondary:hover {
            background: #e5e7eb;
            color: var(--dark);
        }
        
        /* Scrollbar for modal */
        .modal-content::-webkit-scrollbar {
            width: 8px;
        }
        
        .modal-content::-webkit-scrollbar-track {
            background: #f3f4f6;
        }
        
        .modal-content::-webkit-scrollbar-thumb {
            background: #d1d5db;
            border-radius: 4px;
        }
        
        .modal-content::-webkit-scrollbar-thumb:hover {
            background: #9ca3af;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .modal-content {
                width: 95%;
                max-height: 95vh;
            }
            
            .form-grid {
                grid-template-columns: 1fr;
            }
            
            .modal-header {
                padding: 20px;
            }
            
            .modal-content form {
                padding: 20px;
            }
        }
        
        /* Form Sections */
        #unavailabilityForm {
            padding: 30px;
        }
        
        .section-label {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 15px;
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 15px;
        }
        
        .section-label i {
            color: var(--blue);
        }
        
        .helper-text {
            font-size: 13px;
            color: #6b7280;
            margin-top: 8px;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        
        .helper-text i {
            color: #9ca3af;
            font-size: 12px;
        }
        
        /* Reason Cards */
        .reason-selection {
            margin-bottom: 30px;
        }
        
        .reason-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 12px;
        }
        
        .reason-card {
            cursor: pointer;
            position: relative;
        }
        
        .reason-card input[type="radio"] {
            position: absolute;
            opacity: 0;
            width: 0;
            height: 0;
        }
        
        .reason-card-content {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10px;
            padding: 20px 15px;
            background: #f9fafb;
            border: 2px solid #e5e7eb;
            border-radius: 10px;
            transition: all 0.3s;
        }
        
        .reason-card-content i {
            font-size: 28px;
            color: #6b7280;
            transition: all 0.3s;
        }
        
        .reason-card-content span {
            font-size: 13px;
            font-weight: 600;
            color: #6b7280;
            transition: all 0.3s;
        }
        
        .reason-card:hover .reason-card-content {
            border-color: #f59e0b;
            background: #fffbeb;
        }
        
        .reason-card input[type="radio"]:checked + .reason-card-content {
            background: linear-gradient(135deg, rgba(245, 158, 11, 0.1) 0%, rgba(245, 158, 11, 0.05) 100%);
            border-color: #f59e0b;
            box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.1);
        }
        
        .reason-card input[type="radio"]:checked + .reason-card-content i,
        .reason-card input[type="radio"]:checked + .reason-card-content span {
            color: #f59e0b;
        }
        
        /* Date Range */
        .date-range-section {
            margin-bottom: 30px;
        }
        
        .date-range-inputs {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .date-input-group {
            flex: 1;
        }
        
        .date-input-group label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: #6b7280;
            margin-bottom: 8px;
        }
        
        .date-input-group input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s;
        }
        
        .date-input-group input:focus {
            outline: none;
            border-color: #f59e0b;
            box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.1);
        }
        
        .date-separator {
            color: #9ca3af;
            font-size: 18px;
            margin-top: 28px;
        }
        
        /* Time Range */
        .time-range-section {
            margin-bottom: 30px;
        }
        
        .section-header-with-toggle {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
        }
        
        .toggle-switch {
            position: relative;
            display: inline-block;
            width: 50px;
            height: 26px;
        }
        
        .toggle-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        
        .toggle-slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #d1d5db;
            transition: 0.3s;
            border-radius: 26px;
        }
        
        .toggle-slider:before {
            position: absolute;
            content: "";
            height: 20px;
            width: 20px;
            left: 3px;
            bottom: 3px;
            background-color: white;
            transition: 0.3s;
            border-radius: 50%;
        }
        
        .toggle-switch input:checked + .toggle-slider {
            background-color: #f59e0b;
        }
        
        .toggle-switch input:checked + .toggle-slider:before {
            transform: translateX(24px);
        }
        
        .time-range-inputs {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-top: 15px;
        }
        
        .time-input-group {
            flex: 1;
        }
        
        .time-input-group label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: #6b7280;
            margin-bottom: 8px;
        }
        
        .time-input-group input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s;
        }
        
        .time-input-group input:focus {
            outline: none;
            border-color: #f59e0b;
            box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.1);
        }
        
        .time-separator {
            color: #9ca3af;
            font-size: 18px;
            margin-top: 28px;
        }
        
        /* Notes */
        .notes-section {
            margin-bottom: 25px;
        }
        
        .notes-section textarea {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 14px;
            font-family: inherit;
            resize: vertical;
            transition: all 0.3s;
        }
        
        .notes-section textarea:focus {
            outline: none;
            border-color: #f59e0b;
            box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.1);
        }
        
        /* Conflict Warning */
        .conflict-warning {
            display: flex;
            gap: 12px;
            padding: 15px;
            background: #fef3c7;
            border: 1px solid #fbbf24;
            border-radius: 8px;
            margin-bottom: 25px;
        }
        
        .conflict-warning i {
            color: #f59e0b;
            font-size: 20px;
            flex-shrink: 0;
        }
        
        .conflict-warning strong {
            display: block;
            color: var(--dark);
            margin-bottom: 4px;
        }
        
        .conflict-warning p {
            font-size: 13px;
            color: #6b7280;
            margin: 0;
        }
        
        /* Modal Actions */
        .modal-actions {
            display: flex;
            gap: 12px;
            justify-content: flex-end;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
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
                        <h1>Schedule Management</h1>
                        <p>Configure your availability, working hours, and manage time off efficiently</p>
                        <div class="hero-stats">
                            <div class="hero-stat">
                                <i class="fas fa-calendar-week"></i>
                                <span><?php echo count($schedules); ?> Active Schedules</span>
                            </div>
                            <div class="hero-stat">
                                <i class="fas fa-calendar-check"></i>
                                <span><?php echo $filtered_bookings_count; ?> Bookings <?php echo !empty($date_filter) ? '(Filtered)' : ''; ?></span>
                            </div>
                            <div class="hero-stat">
                                <i class="fas fa-calendar-times"></i>
                                <span><?php echo count($unavailability); ?> Upcoming Leave</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Page Header -->
                <div class="page-header">
                    <h1><i class="fas fa-clock"></i> Schedule Management</h1>
                    <p>Manage your availability and working hours</p>
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

                <!-- Action Buttons -->
                <div class="form-actions" style="margin-bottom: 30px;">
                    <button class="btn btn-primary" onclick="openAddScheduleModal()">
                        <i class="fas fa-plus"></i> Add Schedule
                    </button>
                    <button class="btn btn-secondary" onclick="openAddUnavailabilityModal()">
                        <i class="fas fa-calendar-times"></i> Add Unavailability
                    </button>
                </div>

                <!-- Date Filter Section -->
                <div class="date-filter-section">
                    <form method="GET" action="" id="dateFilterForm">
                        <div class="date-filter-header">
                            <i class="fas fa-filter"></i>
                            <span>Filter by Date</span>
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
                                <label for="year_month"><i class="fas fa-calendar"></i> Year</label>
                                <select id="year_month" name="year">
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
                        
                        <div class="form-actions" style="margin-top: 15px;">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-filter"></i> Apply Filter
                            </button>
                            <a href="schedule.php" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Clear Filter
                            </a>
                        </div>
                    </form>
                </div>

                <!-- Weekly Schedule -->
                <div class="calendar-container">
                    <div class="calendar-header">
                        <h3><i class="fas fa-calendar-week"></i> Weekly Schedule</h3>
                        <div class="view-toggle">
                            <button class="view-btn active" onclick="switchView('calendar')">
                                <i class="fas fa-calendar"></i> Calendar View
                            </button>
                            <button class="view-btn" onclick="switchView('list')">
                                <i class="fas fa-list"></i> List View
                            </button>
                        </div>
                    </div>
                    
                    <!-- Calendar View -->
                    <div id="calendarView" class="calendar-view">
                        <div class="calendar-grid">
                            <!-- Header Row -->
                            <div class="calendar-time-label"></div>
                            <?php foreach($days as $day_num => $day_name): ?>
                            <div class="calendar-day-header">
                                <span class="day-name"><?php echo $day_name; ?></span>
                                <span class="day-abbr"><?php echo substr($day_name, 0, 3); ?></span>
                            </div>
                            <?php endforeach; ?>
                            
                            <!-- Time Slots (8 AM to 5 PM) -->
                            <?php 
                            $time_slots = [
                                '08:00', '09:00', '10:00', '11:00', '12:00', 
                                '13:00', '14:00', '15:00', '16:00', '17:00'
                            ];
                            
                            foreach($time_slots as $time_slot): 
                            ?>
                                <div class="calendar-time-label"><?php echo $time_slot; ?></div>
                                <?php foreach($days as $day_num => $day_name): ?>
                                    <?php
                                    // Find schedules for this day and time
                                    $cell_schedules = [];
                                    if(isset($schedules_by_day[$day_num])) {
                                        foreach($schedules_by_day[$day_num] as $schedule) {
                                            $start_hour = (int)date('H', strtotime($schedule['start_time']));
                                            $end_hour = (int)date('H', strtotime($schedule['end_time']));
                                            $slot_hour = (int)substr($time_slot, 0, 2);
                                            
                                            if($slot_hour >= $start_hour && $slot_hour < $end_hour) {
                                                $cell_schedules[] = $schedule;
                                            }
                                        }
                                    }
                                    ?>
                                    <div class="calendar-cell <?php echo !empty($cell_schedules) ? 'has-schedule' : ''; ?>">
                                        <?php if(!empty($cell_schedules)): ?>
                                            <?php foreach($cell_schedules as $schedule): ?>
                                                <div class="schedule-block">
                                                    <span class="schedule-block-title"><?php echo htmlspecialchars($schedule['service_name']); ?></span>
                                                    <span class="schedule-block-time">
                                                        <?php echo date('H:i', strtotime($schedule['start_time'])); ?> - 
                                                        <?php echo date('H:i', strtotime($schedule['end_time'])); ?>
                                                    </span>
                                                    <span class="schedule-block-location">
                                                        <i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($schedule['location']); ?>
                                                    </span>
                                                    <div class="schedule-block-actions">
                                                        <button class="btn-icon-small btn-edit" onclick="editSchedule(<?php echo $schedule['schedule_id']; ?>)" title="Edit">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        <button class="btn-icon-small btn-delete" onclick="deleteSchedule(<?php echo $schedule['schedule_id']; ?>)" title="Delete">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <span class="empty-cell">-</span>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <!-- List View -->
                    <div id="listView" class="schedule-list-view">
                        <div class="schedule-grid">
                            <?php foreach($days as $day_num => $day_name): ?>
                            <div class="day-schedule">
                                <div class="day-header">
                                    <h3>
                                        <div class="day-icon"><?php echo substr($day_name, 0, 3); ?></div>
                                        <?php echo $day_name; ?>
                                    </h3>
                                </div>
                                
                                <?php if(isset($schedules_by_day[$day_num]) && count($schedules_by_day[$day_num]) > 0): ?>
                                    <div class="schedule-slots">
                                        <?php foreach($schedules_by_day[$day_num] as $schedule): ?>
                                        <div class="schedule-slot">
                                            <div class="slot-time">
                                                <i class="fas fa-clock"></i>
                                                <?php echo date('H:i', strtotime($schedule['start_time'])); ?> - <?php echo date('H:i', strtotime($schedule['end_time'])); ?>
                                            </div>
                                            <div class="slot-service">
                                                <h4><?php echo htmlspecialchars($schedule['service_name']); ?></h4>
                                                <p><?php echo htmlspecialchars($schedule['category_name']); ?></p>
                                                <div class="slot-location">
                                                    <i class="fas fa-map-marker-alt"></i>
                                                    <?php echo htmlspecialchars($schedule['location']); ?>
                                                </div>
                                            </div>
                                            <div class="slot-actions">
                                                <button class="btn-icon btn-edit" onclick="editSchedule(<?php echo $schedule['schedule_id']; ?>)" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="btn-icon btn-delete" onclick="deleteSchedule(<?php echo $schedule['schedule_id']; ?>)" title="Delete">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <div class="empty-day">
                                        <i class="fas fa-calendar-times"></i>
                                        <p>No schedule for this day</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Unavailability -->
                <?php if(count($unavailability) > 0): ?>
                <div class="section">
                    <h3><i class="fas fa-calendar-times"></i> Upcoming Unavailability</h3>
                    <div class="unavailability-list">
                        <?php foreach($unavailability as $unavail): ?>
                        <div class="unavailability-item">
                            <div class="unavailability-icon">
                                <i class="fas fa-ban"></i>
                            </div>
                            <div class="unavailability-info">
                                <h4><?php echo ucfirst($unavail['reason']); ?></h4>
                                <p>
                                    <?php echo date('d M Y', strtotime($unavail['start_date'])); ?> - <?php echo date('d M Y', strtotime($unavail['end_date'])); ?>
                                    <?php if($unavail['start_time']): ?>
                                        | <?php echo date('H:i', strtotime($unavail['start_time'])); ?> - <?php echo date('H:i', strtotime($unavail['end_time'])); ?>
                                    <?php endif; ?>
                                </p>
                                <?php if(!empty($unavail['notes'])): ?>
                                    <p><i class="fas fa-sticky-note"></i> <?php echo htmlspecialchars($unavail['notes']); ?></p>
                                <?php endif; ?>
                            </div>
                            <div class="slot-actions">
                                <button class="btn-icon btn-delete" onclick="deleteUnavailability(<?php echo $unavail['unavailability_id']; ?>)" title="Delete">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Add Schedule Modal -->
    <div class="modal-overlay" id="addScheduleModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Add Schedule</h2>
                <button class="modal-close" onclick="closeModal('addScheduleModal')">&times;</button>
            </div>
            <form action="add-schedule.php" method="POST">
                <div class="form-grid">
                    <div class="form-group full-width">
                        <label for="service_id"><i class="fas fa-briefcase"></i> Service</label>
                        <select id="service_id" name="service_id" required>
                            <option value="">Select Service</option>
                            <?php 
                            $current_category = '';
                            foreach($services as $service): 
                                if($current_category != $service['category_name']):
                                    if($current_category != '') echo '</optgroup>';
                                    echo '<optgroup label="' . htmlspecialchars($service['category_name']) . '">';
                                    $current_category = $service['category_name'];
                                endif;
                            ?>
                                <option value="<?php echo $service['service_id']; ?>">
                                    <?php echo htmlspecialchars($service['service_name']); ?>
                                </option>
                            <?php endforeach; ?>
                            <?php if($current_category != '') echo '</optgroup>'; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="day_of_week"><i class="fas fa-calendar"></i> Day of Week</label>
                        <select id="day_of_week" name="day_of_week" required>
                            <option value="">Select Day</option>
                            <?php foreach($days as $num => $name): ?>
                                <option value="<?php echo $num; ?>"><?php echo $name; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="slot_duration"><i class="fas fa-hourglass-half"></i> Slot Duration (min)</label>
                        <input type="number" id="slot_duration" name="slot_duration" value="30" min="15" step="15" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="start_time"><i class="fas fa-clock"></i> Start Time</label>
                        <input type="time" id="start_time" name="start_time" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="end_time"><i class="fas fa-clock"></i> End Time</label>
                        <input type="time" id="end_time" name="end_time" required>
                    </div>
                    
                    <div class="form-group full-width">
                        <label for="location"><i class="fas fa-map-marker-alt"></i> Location</label>
                        <input type="text" id="location" name="location" placeholder="e.g., Office B204" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="effective_from"><i class="fas fa-calendar-alt"></i> Effective From</label>
                        <input type="date" id="effective_from" name="effective_from" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="effective_to"><i class="fas fa-calendar-alt"></i> Effective To (Optional)</label>
                        <input type="date" id="effective_to" name="effective_to">
                    </div>
                </div>
                
                <div class="form-actions" style="margin-top: 20px;">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Save Schedule
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="closeModal('addScheduleModal')">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Add Unavailability Modal -->
    <div class="modal-overlay" id="addUnavailabilityModal">
        <div class="modal-content unavailability-modal">
            <div class="modal-header">
                <div class="modal-header-content">
                    <div class="modal-icon-header">
                        <i class="fas fa-calendar-times"></i>
                    </div>
                    <div>
                        <h2>Add Unavailability</h2>
                        <p class="modal-subtitle">Block time when you're not available for appointments</p>
                    </div>
                </div>
                <button class="modal-close" onclick="closeModal('addUnavailabilityModal')">&times;</button>
            </div>
            
            <form action="add-unavailability.php" method="POST" id="unavailabilityForm">
                <!-- Reason Selection (First) -->
                <div class="reason-selection">
                    <label class="section-label">
                        <i class="fas fa-info-circle"></i> What's the reason?
                    </label>
                    <div class="reason-cards">
                        <label class="reason-card">
                            <input type="radio" name="reason" value="leave" required>
                            <div class="reason-card-content">
                                <i class="fas fa-umbrella-beach"></i>
                                <span>Leave</span>
                            </div>
                        </label>
                        
                        <label class="reason-card">
                            <input type="radio" name="reason" value="sick" required>
                            <div class="reason-card-content">
                                <i class="fas fa-thermometer"></i>
                                <span>Sick Leave</span>
                            </div>
                        </label>
                        
                        <label class="reason-card">
                            <input type="radio" name="reason" value="meeting" required>
                            <div class="reason-card-content">
                                <i class="fas fa-users"></i>
                                <span>Meeting</span>
                            </div>
                        </label>
                        
                        <label class="reason-card">
                            <input type="radio" name="reason" value="training" required>
                            <div class="reason-card-content">
                                <i class="fas fa-graduation-cap"></i>
                                <span>Training</span>
                            </div>
                        </label>
                        
                        <label class="reason-card">
                            <input type="radio" name="reason" value="other" required>
                            <div class="reason-card-content">
                                <i class="fas fa-ellipsis-h"></i>
                                <span>Other</span>
                            </div>
                        </label>
                    </div>
                </div>
                
                <!-- Date Range -->
                <div class="date-range-section">
                    <label class="section-label">
                        <i class="fas fa-calendar-alt"></i> When will you be unavailable?
                    </label>
                    <div class="date-range-inputs">
                        <div class="date-input-group">
                            <label for="start_date">From</label>
                            <input type="date" id="start_date" name="start_date" min="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        <div class="date-separator">
                            <i class="fas fa-arrow-right"></i>
                        </div>
                        <div class="date-input-group">
                            <label for="end_date">To</label>
                            <input type="date" id="end_date" name="end_date" min="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                    </div>
                    <p class="helper-text">
                        <i class="fas fa-info-circle"></i> Select the date range when you won't be available
                    </p>
                </div>
                
                <!-- Time Range (Optional) -->
                <div class="time-range-section">
                    <div class="section-header-with-toggle">
                        <label class="section-label">
                            <i class="fas fa-clock"></i> Specific time range (Optional)
                        </label>
                        <label class="toggle-switch">
                            <input type="checkbox" id="enableTimeRange" onchange="toggleTimeRange()">
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                    <p class="helper-text">Enable if you're only unavailable during specific hours</p>
                    
                    <div class="time-range-inputs" id="timeRangeInputs" style="display: none;">
                        <div class="time-input-group">
                            <label for="start_time_unavail">Start Time</label>
                            <input type="time" id="start_time_unavail" name="start_time">
                        </div>
                        <div class="time-separator">
                            <i class="fas fa-arrow-right"></i>
                        </div>
                        <div class="time-input-group">
                            <label for="end_time_unavail">End Time</label>
                            <input type="time" id="end_time_unavail" name="end_time">
                        </div>
                    </div>
                </div>
                
                <!-- Notes -->
                <div class="notes-section">
                    <label class="section-label" for="notes">
                        <i class="fas fa-sticky-note"></i> Additional notes (Optional)
                    </label>
                    <textarea id="notes" name="notes" rows="3" placeholder="Add any additional details or context..."></textarea>
                </div>
                
                <!-- Conflict Warning -->
                <div class="conflict-warning" style="display: none;">
                    <i class="fas fa-exclamation-triangle"></i>
                    <div>
                        <strong>Potential Conflicts</strong>
                        <p>You have existing appointments during this period. They will need to be rescheduled.</p>
                    </div>
                </div>
                
                <!-- Action Buttons -->
                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('addUnavailabilityModal')">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-check"></i> Add Unavailability
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Schedule Modal -->
    <div class="modal-overlay" id="editScheduleModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-edit"></i> Edit Schedule</h2>
                <button class="modal-close" onclick="closeModal('editScheduleModal')">&times;</button>
            </div>
            <form action="edit-schedule.php" method="POST">
                <input type="hidden" id="edit_schedule_id" name="schedule_id">
                <div class="form-grid">
                    <div class="form-group full-width">
                        <label for="edit_service_id"><i class="fas fa-briefcase"></i> Service</label>
                        <select id="edit_service_id" name="service_id" required>
                            <option value="">Select Service</option>
                            <?php 
                            $current_category = '';
                            foreach($services as $service): 
                                if($current_category != $service['category_name']):
                                    if($current_category != '') echo '</optgroup>';
                                    echo '<optgroup label="' . htmlspecialchars($service['category_name']) . '">';
                                    $current_category = $service['category_name'];
                                endif;
                            ?>
                                <option value="<?php echo $service['service_id']; ?>">
                                    <?php echo htmlspecialchars($service['service_name']); ?>
                                </option>
                            <?php endforeach; ?>
                            <?php if($current_category != '') echo '</optgroup>'; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_day_of_week"><i class="fas fa-calendar"></i> Day of Week</label>
                        <select id="edit_day_of_week" name="day_of_week" required>
                            <option value="">Select Day</option>
                            <?php foreach($days as $num => $name): ?>
                                <option value="<?php echo $num; ?>"><?php echo $name; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_slot_duration"><i class="fas fa-hourglass-half"></i> Slot Duration (min)</label>
                        <input type="number" id="edit_slot_duration" name="slot_duration" value="30" min="15" step="15" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_start_time"><i class="fas fa-clock"></i> Start Time</label>
                        <input type="time" id="edit_start_time" name="start_time" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_end_time"><i class="fas fa-clock"></i> End Time</label>
                        <input type="time" id="edit_end_time" name="end_time" required>
                    </div>
                    
                    <div class="form-group full-width">
                        <label for="edit_location"><i class="fas fa-map-marker-alt"></i> Location</label>
                        <input type="text" id="edit_location" name="location" placeholder="e.g., Office B204" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_effective_from"><i class="fas fa-calendar-alt"></i> Effective From</label>
                        <input type="date" id="edit_effective_from" name="effective_from" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_effective_to"><i class="fas fa-calendar-alt"></i> Effective To (Optional)</label>
                        <input type="date" id="edit_effective_to" name="effective_to">
                    </div>
                </div>
                
                <div class="form-actions" style="margin-top: 20px;">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Update Schedule
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="closeModal('editScheduleModal')">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Confirmation Modal -->
    <div class="modal-overlay" id="confirmModal">
        <div class="modal-content" style="max-width: 450px;">
            <div class="modal-header">
                <h2 id="confirmTitle"><i class="fas fa-question-circle"></i> Confirm Action</h2>
                <button class="modal-close" onclick="closeConfirmModal()">&times;</button>
            </div>
            <div style="padding: 30px;">
                <p id="confirmMessage" style="font-size: 15px; color: #6b7280; line-height: 1.6; margin-bottom: 25px;"></p>
                <div class="form-actions" style="margin-top: 0; padding-top: 0; border-top: none;">
                    <button type="button" class="btn btn-secondary" onclick="closeConfirmModal()">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                    <button type="button" class="btn btn-primary" id="confirmButton" onclick="confirmAction()">
                        <i class="fas fa-check"></i> Confirm
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Error/Success Modal -->
    <div class="modal-overlay" id="messageModal">
        <div class="modal-content" style="max-width: 450px;">
            <div class="modal-header" id="messageModalHeader">
                <h2 id="messageTitle"><i class="fas fa-info-circle"></i> Message</h2>
                <button class="modal-close" onclick="closeMessageModal()">&times;</button>
            </div>
            <div style="padding: 30px;">
                <p id="messageContent" style="font-size: 15px; color: #6b7280; line-height: 1.6; margin-bottom: 25px;"></p>
                <div class="form-actions" style="margin-top: 0; padding-top: 0; border-top: none; justify-content: center;">
                    <button type="button" class="btn btn-primary" onclick="closeMessageModal()">
                        <i class="fas fa-check"></i> OK
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <script src="js/dashboard.js"></script>
    <script>
        // Confirmation modal variables
        let confirmCallback = null;
        
        function showConfirmModal(title, message, callback) {
            document.getElementById('confirmTitle').innerHTML = '<i class="fas fa-question-circle"></i> ' + title;
            document.getElementById('confirmMessage').textContent = message;
            confirmCallback = callback;
            document.getElementById('confirmModal').classList.add('active');
        }
        
        function closeConfirmModal() {
            document.getElementById('confirmModal').classList.remove('active');
            confirmCallback = null;
        }
        
        function confirmAction() {
            if(confirmCallback) {
                confirmCallback();
            }
            closeConfirmModal();
        }
        
        function showMessageModal(title, message, type = 'info') {
            const icons = {
                'success': '<i class="fas fa-check-circle" style="color: #10b981;"></i>',
                'error': '<i class="fas fa-exclamation-circle" style="color: #ef4444;"></i>',
                'info': '<i class="fas fa-info-circle" style="color: #2563eb;"></i>'
            };
            
            document.getElementById('messageTitle').innerHTML = icons[type] + ' ' + title;
            document.getElementById('messageContent').textContent = message;
            document.getElementById('messageModal').classList.add('active');
        }
        
        function closeMessageModal() {
            document.getElementById('messageModal').classList.remove('active');
        }
        
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
        
        function switchView(view) {
            const calendarView = document.getElementById('calendarView');
            const listView = document.getElementById('listView');
            const buttons = document.querySelectorAll('.view-btn');
            
            if(view === 'calendar') {
                calendarView.style.display = 'block';
                listView.classList.remove('active');
                buttons[0].classList.add('active');
                buttons[1].classList.remove('active');
            } else {
                calendarView.style.display = 'none';
                listView.classList.add('active');
                buttons[0].classList.remove('active');
                buttons[1].classList.add('active');
            }
        }
        
        function toggleTimeRange() {
            const checkbox = document.getElementById('enableTimeRange');
            const timeRangeInputs = document.getElementById('timeRangeInputs');
            
            if(checkbox.checked) {
                timeRangeInputs.style.display = 'flex';
            } else {
                timeRangeInputs.style.display = 'none';
                // Clear time inputs when disabled
                document.getElementById('start_time_unavail').value = '';
                document.getElementById('end_time_unavail').value = '';
            }
        }
        
        function openAddScheduleModal() {
            document.getElementById('addScheduleModal').classList.add('active');
        }
        
        function openAddUnavailabilityModal() {
            document.getElementById('addUnavailabilityModal').classList.add('active');
        }
        
        function closeModal(modalId) {
            document.getElementById(modalId).classList.remove('active');
            
            // Reset form if it's the unavailability modal
            if(modalId === 'addUnavailabilityModal') {
                document.getElementById('unavailabilityForm').reset();
                document.getElementById('timeRangeInputs').style.display = 'none';
                document.getElementById('enableTimeRange').checked = false;
            }
            
            // Reset edit schedule modal
            if(modalId === 'editScheduleModal') {
                const form = document.querySelector('#editScheduleModal form');
                if(form) form.reset();
            }
        }
        
        function editSchedule(scheduleId) {
            // Fetch schedule data and populate edit modal
            fetch('get-schedule.php?id=' + scheduleId)
                .then(response => response.json())
                .then(data => {
                    if(data.success) {
                        // Populate edit form
                        document.getElementById('edit_schedule_id').value = data.schedule.schedule_id;
                        document.getElementById('edit_service_id').value = data.schedule.service_id;
                        document.getElementById('edit_day_of_week').value = data.schedule.day_of_week;
                        document.getElementById('edit_start_time').value = data.schedule.start_time;
                        document.getElementById('edit_end_time').value = data.schedule.end_time;
                        document.getElementById('edit_location').value = data.schedule.location;
                        document.getElementById('edit_slot_duration').value = data.schedule.slot_duration;
                        document.getElementById('edit_effective_from').value = data.schedule.effective_from;
                        document.getElementById('edit_effective_to').value = data.schedule.effective_to || '';
                        
                        // Open modal
                        document.getElementById('editScheduleModal').classList.add('active');
                    } else {
                        showMessageModal('Error', 'Error loading schedule: ' + data.message, 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showMessageModal('Error', 'Error loading schedule data. Please try again.', 'error');
                });
        }
        
        function deleteSchedule(scheduleId) {
            showConfirmModal(
                'Delete Schedule',
                'Are you sure you want to delete this schedule? This action cannot be undone.',
                function() {
                    window.location.href = 'delete-schedule.php?id=' + scheduleId;
                }
            );
        }
        
        function deleteUnavailability(unavailabilityId) {
            showConfirmModal(
                'Delete Unavailability',
                'Are you sure you want to delete this unavailability period?',
                function() {
                    window.location.href = 'delete-unavailability.php?id=' + unavailabilityId;
                }
            );
        }
        
        // Close modal when clicking outside
        document.querySelectorAll('.modal-overlay').forEach(modal => {
            modal.addEventListener('click', function(e) {
                if(e.target === this) {
                    const modalId = this.id;
                    if(modalId === 'confirmModal') {
                        closeConfirmModal();
                    } else if(modalId === 'messageModal') {
                        closeMessageModal();
                    } else {
                        closeModal(modalId);
                    }
                }
            });
        });
        
        // Date validation
        document.getElementById('start_date')?.addEventListener('change', function() {
            const endDateInput = document.getElementById('end_date');
            endDateInput.min = this.value;
            if(endDateInput.value && endDateInput.value < this.value) {
                endDateInput.value = this.value;
            }
        });
        
        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            // ESC key closes modals
            if(e.key === 'Escape') {
                if(document.getElementById('confirmModal').classList.contains('active')) {
                    closeConfirmModal();
                } else if(document.getElementById('messageModal').classList.contains('active')) {
                    closeMessageModal();
                } else if(document.getElementById('editScheduleModal').classList.contains('active')) {
                    closeModal('editScheduleModal');
                } else if(document.getElementById('addScheduleModal').classList.contains('active')) {
                    closeModal('addScheduleModal');
                } else if(document.getElementById('addUnavailabilityModal').classList.contains('active')) {
                    closeModal('addUnavailabilityModal');
                }
            }
        });
    </script>
</body>
</html>
