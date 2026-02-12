<?php
session_start();
if(!isset($_SESSION['staff_id']) || $_SESSION['role'] != 'coordinator') {
    header('Location: ../staff-login.php');
    exit();
}

require_once '../config/database.php';

$db = new Database();
$conn = $db->connect();

// Get all modules
$modules_query = "SELECT * FROM modules ORDER BY module_code";
$modules = $conn->query($modules_query)->fetchAll();

// Handle form submission
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $module_id = $_POST['module_id'];
    $academic_year = $_POST['academic_year'];
    $semester = $_POST['semester'];
    $risk_level = $_POST['risk_level'];
    $failure_rate = $_POST['failure_rate'];
    $total_students = $_POST['total_students'];
    $at_risk_students = $_POST['at_risk_students'];
    $reason = trim($_POST['reason']);
    $intervention_needed = trim($_POST['intervention_needed']);
    
    try {
        // Check if module already exists as at-risk for this year/semester
        $check_stmt = $conn->prepare("
            SELECT * FROM at_risk_modules 
            WHERE module_id = ? AND academic_year = ? AND semester = ? AND status = 'active'
        ");
        $check_stmt->execute([$module_id, $academic_year, $semester]);
        
        if($check_stmt->rowCount() > 0) {
            $error = "This module is already marked as at-risk for the selected year and semester.";
        } else {
            $stmt = $conn->prepare("
                INSERT INTO at_risk_modules 
                (module_id, academic_year, semester, risk_level, failure_rate, total_students, 
                 at_risk_students, reason, intervention_needed, identified_by, identified_date, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), 'active')
            ");
            
            $stmt->execute([
                $module_id, $academic_year, $semester, $risk_level, $failure_rate,
                $total_students, $at_risk_students, $reason, $intervention_needed,
                $_SESSION['staff_id']
            ]);
            
            header('Location: at-risk-modules.php?success=Module added successfully');
            exit();
        }
    } catch(PDOException $e) {
        $error = "Error: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add At-Risk Module - WSU Booking</title>
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .form-container {
            max-width: 800px;
            margin: 0 auto;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group.full-width {
            grid-column: 1 / -1;
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
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 15px;
            transition: all 0.3s;
        }
        
        .form-group input:focus,
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
        
        .form-group small {
            display: block;
            margin-top: 5px;
            color: #6b7280;
            font-size: 13px;
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
        
        .risk-level-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 10px;
            margin-top: 10px;
            padding: 15px;
            background: #f9fafb;
            border-radius: 8px;
        }
        
        .risk-info-item {
            font-size: 12px;
            padding: 8px;
            border-radius: 6px;
            text-align: center;
        }
        
        .risk-info-item.low {
            background: #d1fae5;
            color: #10b981;
        }
        
        .risk-info-item.medium {
            background: #fef3c7;
            color: #f59e0b;
        }
        
        .risk-info-item.high {
            background: #fed7aa;
            color: #ea580c;
        }
        
        .risk-info-item.critical {
            background: #fee2e2;
            color: #dc2626;
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
                    <!-- Page Header -->
                    <div class="page-header">
                        <h1><i class="fas fa-plus-circle"></i> Add At-Risk Module</h1>
                        <p>Identify a module that needs intervention and support</p>
                    </div>

                    <?php if(isset($error)): ?>
                        <div class="alert alert-error">
                            <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                        </div>
                    <?php endif; ?>

                    <div class="section">
                        <form method="POST" action="">
                            <div class="form-grid">
                                <div class="form-group">
                                    <label><i class="fas fa-book"></i> Module</label>
                                    <select name="module_id" required>
                                        <option value="">Select Module</option>
                                        <?php foreach($modules as $module): ?>
                                            <option value="<?php echo $module['module_id']; ?>">
                                                <?php echo htmlspecialchars($module['module_code'] . ' - ' . $module['module_name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label><i class="fas fa-calendar"></i> Academic Year</label>
                                    <select name="academic_year" required>
                                        <option value="2024">2024</option>
                                        <option value="2025">2025</option>
                                        <option value="2026" selected>2026</option>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label><i class="fas fa-calendar-alt"></i> Semester</label>
                                    <select name="semester" required>
                                        <option value="">Select Semester</option>
                                        <option value="1">Semester 1</option>
                                        <option value="2">Semester 2</option>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label><i class="fas fa-exclamation-triangle"></i> Risk Level</label>
                                    <select name="risk_level" id="riskLevel" required>
                                        <option value="">Select Risk Level</option>
                                        <option value="low">Low (0-25% failure rate)</option>
                                        <option value="medium">Medium (26-40% failure rate)</option>
                                        <option value="high">High (41-55% failure rate)</option>
                                        <option value="critical">Critical (>55% failure rate)</option>
                                    </select>
                                    <div class="risk-level-info">
                                        <div class="risk-info-item low">
                                            <strong>Low</strong><br>0-25%
                                        </div>
                                        <div class="risk-info-item medium">
                                            <strong>Medium</strong><br>26-40%
                                        </div>
                                        <div class="risk-info-item high">
                                            <strong>High</strong><br>41-55%
                                        </div>
                                        <div class="risk-info-item critical">
                                            <strong>Critical</strong><br>>55%
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label><i class="fas fa-percentage"></i> Failure Rate (%)</label>
                                    <input type="number" name="failure_rate" step="0.1" min="0" max="100" required>
                                    <small>Enter the percentage of students who failed this module</small>
                                </div>

                                <div class="form-group">
                                    <label><i class="fas fa-users"></i> Total Students</label>
                                    <input type="number" name="total_students" min="1" required>
                                    <small>Total number of students enrolled</small>
                                </div>

                                <div class="form-group">
                                    <label><i class="fas fa-user-times"></i> At-Risk Students</label>
                                    <input type="number" name="at_risk_students" min="0" required>
                                    <small>Number of students currently at risk</small>
                                </div>

                                <div class="form-group full-width">
                                    <label><i class="fas fa-comment-alt"></i> Reason for At-Risk Status</label>
                                    <textarea name="reason" required placeholder="Describe why this module is at risk (e.g., high failure rate, student feedback, assessment results)"></textarea>
                                </div>

                                <div class="form-group full-width">
                                    <label><i class="fas fa-lightbulb"></i> Intervention Needed</label>
                                    <textarea name="intervention_needed" required placeholder="Describe what intervention is needed (e.g., additional tutoring, supplementary materials, study groups)"></textarea>
                                </div>
                            </div>

                            <div class="form-actions" style="display: flex; gap: 15px; margin-top: 30px;">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Add At-Risk Module
                                </button>
                                <a href="at-risk-modules.php" class="btn btn-secondary">
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
