<?php
session_start();
if(!isset($_SESSION['staff_id']) || !in_array($_SESSION['role'], ['tutor', 'pal'])) {
    header('Location: ../staff-login.php');
    exit();
}

require_once '../config/database.php';

$db = new Database();
$conn = $db->connect();

$tutor_id = $_SESSION['staff_id'];
$role = $_SESSION['role'];

// Get filter
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';

// my-assignments.php - tutor_assignments/tutor_sessions don't exist, show bookings instead
$assignments = [];
$statistics = ['total'=>0,'active'=>0,'completed'=>0,'total_sessions'=>0];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Assignments - WSU Booking</title>
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .assignment-card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 20px;
            border: 2px solid #e5e7eb;
            transition: all 0.3s;
        }
        
        .assignment-card:hover {
            border-color: var(--blue);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        
        .assignment-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f3f4f6;
        }
        
        .assignment-title h3 {
            margin: 0 0 5px 0;
            font-size: 20px;
            color: var(--dark);
        }
        
        .assignment-title p {
            margin: 0;
            color: #6b7280;
            font-size: 14px;
        }
        
        .assignment-body {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .info-item {
            display: flex;
            align-items: center;
            gap: 10px;
            min-width: 0;
        }
        
        .info-item i {
            color: var(--blue);
            width: 20px;
            flex-shrink: 0;
        }
        
        .info-item span {
            font-size: 14px;
            color: #4b5563;
            word-break: break-word;
        }
        
        .assignment-stats {
            display: flex;
            gap: 20px;
            padding: 15px;
            background: #f9fafb;
            border-radius: 8px;
            margin-bottom: 15px;
        }
        
        .stat-item {
            flex: 1;
            text-align: center;
        }
        
        .stat-value {
            font-size: 24px;
            font-weight: 700;
            color: var(--blue);
        }
        
        .stat-label {
            font-size: 12px;
            color: #6b7280;
            margin-top: 2px;
        }
        
        .assignment-actions {
            display: flex;
            gap: 10px;
        }
        
        .risk-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
        }
        
        .risk-high {
            background: #fee2e2;
            color: #dc2626;
        }
        
        .risk-moderate {
            background: #fef3c7;
            color: #f59e0b;
        }
        
        .risk-low {
            background: #dbeafe;
            color: #1d4ed8;
        }
        
        .risk-very-low {
            background: #d1fae5;
            color: #10b981;
        }
    </style>
</head>
<body>
    <div class="dashboard-layout">
        <?php include 'includes/sidebar.php'; ?>
        
        <div class="main-content">
            <?php include 'includes/header.php'; ?>
            
            <div class="content">
                <div class="page-header">
                    <h1><i class="fas fa-clipboard-list"></i> My Assignments</h1>
                    <p>View and manage your module assignments</p>
                </div>

                <!-- Statistics -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon blue">
                            <i class="fas fa-clipboard-list"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $statistics['total']; ?></h3>
                            <p>Total Assignments</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon green">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $statistics['active']; ?></h3>
                            <p>Active</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon orange">
                            <i class="fas fa-calendar-check"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $statistics['completed']; ?></h3>
                            <p>Completed</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon blue">
                            <i class="fas fa-chalkboard-teacher"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $statistics['total_sessions']; ?></h3>
                            <p>Total Sessions</p>
                        </div>
                    </div>
                </div>

                <!-- Filters -->
                <div class="section">
                    <div class="filter-tabs">
                        <a href="?status=all" class="filter-tab <?php echo $status_filter == 'all' ? 'active' : ''; ?>">
                            <i class="fas fa-list"></i> All (<?php echo $statistics['total']; ?>)
                        </a>
                        <a href="?status=active" class="filter-tab <?php echo $status_filter == 'active' ? 'active' : ''; ?>">
                            <i class="fas fa-check-circle"></i> Active (<?php echo $statistics['active']; ?>)
                        </a>
                        <a href="?status=completed" class="filter-tab <?php echo $status_filter == 'completed' ? 'active' : ''; ?>">
                            <i class="fas fa-calendar-check"></i> Completed (<?php echo $statistics['completed']; ?>)
                        </a>
                    </div>
                </div>

                <!-- Assignments List -->
                <div class="section">
                    <?php if(count($assignments) > 0): ?>
                        <?php foreach($assignments as $assignment): ?>
                            <div class="assignment-card">
                                <div class="assignment-header">
                                    <div class="assignment-title">
                                        <h3><?php echo htmlspecialchars($assignment['subject_code']); ?> - <?php echo htmlspecialchars($assignment['subject_name']); ?></h3>
                                        <p><?php echo htmlspecialchars($assignment['faculty']); ?> • <?php echo htmlspecialchars($assignment['campus']); ?> Campus</p>
                                    </div>
                                    <div style="display: flex; gap: 10px; align-items: center;">
                                        <span class="risk-badge risk-<?php echo strtolower(str_replace(' ', '-', $assignment['risk_category'])); ?>">
                                            <?php echo $assignment['risk_category']; ?>
                                        </span>
                                        <span class="badge badge-<?php echo $assignment['status']; ?>">
                                            <?php echo ucfirst($assignment['status']); ?>
                                        </span>
                                    </div>
                                </div>
                                
                                <div class="assignment-body">
                                    <div class="info-item">
                                        <i class="fas fa-users"></i>
                                        <span><strong><?php echo $assignment['headcount']; ?></strong> students enrolled</span>
                                    </div>
                                    <div class="info-item">
                                        <i class="fas fa-user-friends"></i>
                                        <span><strong><?php echo $assignment['at_risk_students']; ?></strong> at-risk students</span>
                                    </div>
                                    <div class="info-item">
                                        <i class="fas fa-calendar"></i>
                                        <span><?php echo date('M j', strtotime($assignment['start_date'])); ?> - <?php echo date('M j, Y', strtotime($assignment['end_date'])); ?></span>
                                    </div>
                                    <div class="info-item">
                                        <i class="fas fa-clock"></i>
                                        <span><?php echo htmlspecialchars($assignment['session_frequency']); ?></span>
                                    </div>
                                    <div class="info-item">
                                        <i class="fas fa-map-marker-alt"></i>
                                        <span><?php echo htmlspecialchars($assignment['location']); ?></span>
                                    </div>
                                    <div class="info-item">
                                        <i class="fas fa-user-tie"></i>
                                        <span>Assigned by <?php echo htmlspecialchars($assignment['coordinator_first'] . ' ' . $assignment['coordinator_last']); ?></span>
                                    </div>
                                </div>
                                
                                <div class="assignment-stats">
                                    <div class="stat-item">
                                        <div class="stat-value"><?php echo $assignment['total_sessions']; ?></div>
                                        <div class="stat-label">Total Sessions</div>
                                    </div>
                                    <div class="stat-item">
                                        <div class="stat-value"><?php echo $assignment['completed_sessions']; ?></div>
                                        <div class="stat-label">Completed</div>
                                    </div>
                                    <div class="stat-item">
                                        <div class="stat-value"><?php echo $assignment['total_students']; ?></div>
                                        <div class="stat-label">Students Reached</div>
                                    </div>
                                    <div class="stat-item">
                                        <div class="stat-value"><?php echo $assignment['max_students']; ?></div>
                                        <div class="stat-label">Max per Session</div>
                                    </div>
                                </div>
                                
                                <?php if($assignment['notes']): ?>
                                    <div style="padding: 15px; background: #eff6ff; border-radius: 8px; margin-bottom: 15px;">
                                        <strong style="color: var(--blue);"><i class="fas fa-sticky-note"></i> Assignment Notes:</strong>
                                        <p style="margin: 5px 0 0 0; color: #4b5563; line-height: 1.6;"><?php echo nl2br(htmlspecialchars($assignment['notes'])); ?></p>
                                    </div>
                                <?php endif; ?>
                                
                                <div style="padding: 15px; background: #fef3c7; border-radius: 8px; margin-bottom: 15px;">
                                    <strong style="color: #92400e;"><i class="fas fa-info-circle"></i> Assignment Period:</strong>
                                    <p style="margin: 5px 0 0 0; color: #4b5563;">
                                        <i class="fas fa-calendar-day"></i> 
                                        <strong>Start:</strong> <?php echo date('F j, Y', strtotime($assignment['start_date'])); ?> • 
                                        <strong>End:</strong> <?php echo date('F j, Y', strtotime($assignment['end_date'])); ?>
                                    </p>
                                </div>
                                
                                <?php if($assignment['reason']): ?>
                                    <div style="padding: 15px; background: #fee2e2; border-radius: 8px; margin-bottom: 15px;">
                                        <strong style="color: #dc2626;"><i class="fas fa-exclamation-triangle"></i> Why This Module Needs Support:</strong>
                                        <p style="margin: 5px 0 0 0; color: #4b5563; line-height: 1.6;"><?php echo htmlspecialchars($assignment['reason']); ?></p>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="assignment-actions">
                                    <a href="assignment-details.php?id=<?php echo $assignment['assignment_id']; ?>" class="btn btn-primary">
                                        <i class="fas fa-eye"></i> View Details
                                    </a>
                                    <?php if($assignment['status'] == 'active'): ?>
                                        <a href="create-session.php?assignment_id=<?php echo $assignment['assignment_id']; ?>" class="btn btn-success">
                                            <i class="fas fa-plus"></i> Create Session
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-clipboard"></i>
                            <h3>No Assignments Found</h3>
                            <p>
                                <?php if($status_filter != 'all'): ?>
                                    No <?php echo $status_filter; ?> assignments found.
                                <?php else: ?>
                                    You don't have any assignments yet. Coordinators will assign you to modules.
                                <?php endif; ?>
                            </p>
                            <?php if($status_filter != 'all'): ?>
                                <a href="?status=all" class="btn btn-secondary">
                                    <i class="fas fa-list"></i> View All Assignments
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <?php include '../assets/includes/modals.php'; ?>
    <link rel="stylesheet" href="../assets/css/modals.css">
    <script src="../assets/js/modals.js"></script>
    <script src="js/dashboard.js"></script>
</body>
</html>
