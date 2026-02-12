<?php
session_start();
if(!isset($_SESSION['staff_id']) || $_SESSION['role'] != 'coordinator') {
    header('Location: ../staff-login.php');
    exit();
}

require_once '../config/database.php';

$db = new Database();
$conn = $db->connect();

$module_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Get module details
$stmt = $conn->prepare("SELECT * FROM modules WHERE module_id = ?");
$stmt->execute([$module_id]);
$module = $stmt->fetch();

if(!$module) {
    header('Location: browse-modules.php?error=Module not found');
    exit();
}

// Handle form submission
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $semester = $_POST['semester'];
    $reason = trim($_POST['reason']);
    $intervention_needed = trim($_POST['intervention_needed']);
    
    // Calculate failure rate from pass rate
    $failure_rate = (1 - $module['subject_pass_rate']) * 100;
    
    // Determine risk level based on pass rate
    if($module['subject_pass_rate'] < 0.40) {
        $risk_level = 'critical';
    } elseif($module['subject_pass_rate'] < 0.60) {
        $risk_level = 'high';
    } elseif($module['subject_pass_rate'] < 0.75) {
        $risk_level = 'medium';
    } else {
        $risk_level = 'low';
    }
    
    // Calculate at-risk students (students who failed)
    $at_risk_students = $module['headcount'] - $module['subjects_passed'];
    
    try {
        // Check if already flagged
        $check_stmt = $conn->prepare("
            SELECT * FROM at_risk_modules 
            WHERE module_id = ? AND academic_year = ? AND semester = ? AND status = 'active'
        ");
        $check_stmt->execute([$module_id, $module['academic_year'], $semester]);
        
        if($check_stmt->rowCount() > 0) {
            header('Location: browse-modules.php?error=Module already flagged for this semester');
            exit();
        }
        
        $stmt = $conn->prepare("
            INSERT INTO at_risk_modules 
            (module_id, academic_year, semester, risk_level, failure_rate, pass_rate,
             total_students, at_risk_students, reason, intervention_needed, 
             campus, faculty, school, identified_by, identified_date, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), 'active')
        ");
        
        $stmt->execute([
            $module_id,
            $module['academic_year'],
            $semester,
            $risk_level,
            $failure_rate,
            $module['subject_pass_rate'],
            $module['headcount'],
            $at_risk_students,
            $reason,
            $intervention_needed,
            $module['campus'],
            $module['faculty'],
            $module['school'],
            $_SESSION['staff_id']
        ]);
        
        header('Location: at-risk-modules.php?success=Module flagged successfully');
        exit();
        
    } catch(PDOException $e) {
        $error = "Error: " . $e->getMessage();
    }
}

$risk_colors = [
    'Very Low Risk' => ['bg' => '#d1fae5', 'color' => '#10b981'],
    'Low Risk' => ['bg' => '#fef3c7', 'color' => '#f59e0b'],
    'Moderate Risk' => ['bg' => '#fed7aa', 'color' => '#ea580c'],
    'High Risk' => ['bg' => '#fee2e2', 'color' => '#dc2626']
];
$colors = $risk_colors[$module['risk_category']];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Flag Module for Intervention - WSU Booking</title>
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .form-container {
            max-width: 900px;
            margin: 0 auto;
        }
        
        .module-info-card {
            background: linear-gradient(135deg, #000000 0%, #1d4ed8 100%);
            color: white;
            padding: 30px;
            border-radius: 12px;
            margin-bottom: 30px;
        }
        
        .module-info-card h2 {
            font-size: 24px;
            margin-bottom: 10px;
        }
        
        .module-info-card p {
            opacity: 0.9;
            margin: 5px 0;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 20px;
            padding: 20px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 8px;
        }
        
        .info-item {
            text-align: center;
        }
        
        .info-item .label {
            font-size: 12px;
            opacity: 0.8;
            margin-bottom: 5px;
        }
        
        .info-item .value {
            font-size: 24px;
            font-weight: 700;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: var(--dark);
            font-weight: 600;
            font-size: 14px;
        }
        
        .form-group label i {
            margin-right: 8px;
            color: var(--blue);
        }
        
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 15px;
            transition: all 0.3s;
        }
        
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--blue);
            box-shadow: 0 0 0 3px rgba(29, 78, 216, 0.1);
        }
        
        .form-group textarea {
            resize: vertical;
            min-height: 100px;
            font-family: inherit;
        }
        
        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .alert-error {
            background: #fee2e2;
            color: #dc2626;
            border-left: 4px solid #dc2626;
        }
    </style>
</head>
<body>
    <div class="dashboard-layout">
        <?php include 'includes/sidebar.php'; ?>
        
        <div class="main-content">
            <?php include 'includes/header.php'; ?>
            
            <div class="content">
                <div class="form-container">
                    <!-- Back Button -->
                    <div style="margin-bottom: 20px;">
                        <a href="browse-modules.php" class="btn-link">
                            <i class="fas fa-arrow-left"></i> Back to Browse Modules
                        </a>
                    </div>

                    <!-- Page Header -->
                    <div class="page-header">
                        <h1><i class="fas fa-flag"></i> Flag Module for Intervention</h1>
                        <p>Mark this module as at-risk and specify intervention needed</p>
                    </div>

                    <?php if(isset($error)): ?>
                        <div class="alert alert-error">
                            <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                        </div>
                    <?php endif; ?>

                    <!-- Module Information -->
                    <div class="module-info-card">
                        <h2><?php echo htmlspecialchars($module['subject_code']); ?> - <?php echo htmlspecialchars($module['subject_name']); ?></h2>
                        <p><i class="fas fa-building"></i> <?php echo htmlspecialchars($module['faculty']); ?></p>
                        <p><i class="fas fa-school"></i> <?php echo htmlspecialchars($module['school']); ?></p>
                        
                        <div class="info-grid">
                            <div class="info-item">
                                <div class="label">Pass Rate</div>
                                <div class="value"><?php echo number_format($module['subject_pass_rate'] * 100, 1); ?>%</div>
                            </div>
                            <div class="info-item">
                                <div class="label">Students Passed</div>
                                <div class="value"><?php echo $module['subjects_passed']; ?>/<?php echo $module['headcount']; ?></div>
                            </div>
                            <div class="info-item">
                                <div class="label">Risk Category</div>
                                <div class="value" style="font-size: 16px;">
                                    <span style="background: <?php echo $colors['bg']; ?>; color: <?php echo $colors['color']; ?>; padding: 8px 16px; border-radius: 20px;">
                                        <?php echo $module['risk_category']; ?>
                                    </span>
                                </div>
                            </div>
                            <div class="info-item">
                                <div class="label">Academic Year</div>
                                <div class="value" style="font-size: 18px;"><?php echo htmlspecialchars($module['academic_year']); ?></div>
                            </div>
                        </div>
                    </div>

                    <!-- Flag Form -->
                    <div class="section">
                        <h2 class="section-title"><i class="fas fa-edit"></i> Intervention Details</h2>
                        
                        <form method="POST" action="">
                            <div class="form-group">
                                <label><i class="fas fa-calendar-alt"></i> Semester</label>
                                <select name="semester" required>
                                    <option value="">Select Semester</option>
                                    <option value="1">Semester 1</option>
                                    <option value="2">Semester 2</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label><i class="fas fa-comment-alt"></i> Reason for Flagging</label>
                                <textarea name="reason" required placeholder="Describe why this module needs intervention (e.g., low pass rate, student feedback, assessment challenges)"></textarea>
                            </div>

                            <div class="form-group">
                                <label><i class="fas fa-lightbulb"></i> Intervention Needed</label>
                                <textarea name="intervention_needed" required placeholder="Describe what intervention is needed (e.g., additional tutoring sessions, supplementary materials, study groups, PAL support)"></textarea>
                            </div>

                            <div style="padding: 15px; background: #eff6ff; border-radius: 8px; border-left: 4px solid var(--blue); margin-bottom: 20px;">
                                <h4 style="color: var(--dark); margin-bottom: 10px;">
                                    <i class="fas fa-info-circle"></i> What Happens Next?
                                </h4>
                                <ul style="color: #6b7280; font-size: 14px; line-height: 1.8; margin-left: 20px;">
                                    <li>Module will be added to the At-Risk Modules list</li>
                                    <li>You can assign tutors and PALs to provide support</li>
                                    <li>Tutoring sessions can be scheduled for students</li>
                                    <li>Progress will be tracked and monitored</li>
                                </ul>
                            </div>

                            <div style="display: flex; gap: 15px;">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-flag"></i> Flag Module for Intervention
                                </button>
                                <a href="browse-modules.php" class="btn btn-secondary">
                                    <i class="fas fa-times"></i> Cancel
                                </a>
                            </div>
                        </form>
                    </div>
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
