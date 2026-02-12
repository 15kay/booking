<?php
session_start();
if(!isset($_SESSION['staff_id']) || $_SESSION['role'] != 'coordinator') {
    header('Location: ../staff-login.php');
    exit();
}

require_once '../config/database.php';

$db = new Database();
$conn = $db->connect();

$success_message = '';
$error_message = '';

// Handle form submission
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $academic_year = trim($_POST['academic_year']);
    $campus = trim($_POST['campus']);
    $custom_grouping = trim($_POST['custom_grouping']);
    $faculty = trim($_POST['faculty']);
    $school = trim($_POST['school']);
    $subject_area = trim($_POST['subject_area']);
    $period_of_study = trim($_POST['period_of_study']);
    $academic_block_code = trim($_POST['academic_block_code']);
    $subject_code = trim($_POST['subject_code']);
    $subject_name = trim($_POST['subject_name']);
    $subjects_passed = intval($_POST['subjects_passed']);
    $headcount = intval($_POST['headcount']);
    
    // Calculate pass rate
    $subject_pass_rate = $headcount > 0 ? $subjects_passed / $headcount : 0;
    
    try {
        // Check if module already exists
        $check_stmt = $conn->prepare("
            SELECT module_id FROM modules 
            WHERE subject_code = ? AND academic_year = ? AND period_of_study = ?
        ");
        $check_stmt->execute([$subject_code, $academic_year, $period_of_study]);
        
        if($check_stmt->rowCount() > 0) {
            $error_message = "Module with this subject code already exists for the selected year and period.";
        } else {
            $stmt = $conn->prepare("
                INSERT INTO modules 
                (academic_year, campus, custom_grouping, faculty, school, subject_area,
                 period_of_study, academic_block_code, subject_code, subject_name,
                 subjects_passed, headcount, subject_pass_rate, module_code, module_name, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active')
            ");
            
            $stmt->execute([
                $academic_year, $campus, $custom_grouping, $faculty, $school,
                $subject_area, $period_of_study, $academic_block_code, $subject_code,
                $subject_name, $subjects_passed, $headcount, $subject_pass_rate,
                $subject_code, $subject_name
            ]);
            
            $success_message = "Module added successfully!";
            
            // Clear form
            $_POST = array();
        }
    } catch(PDOException $e) {
        $error_message = "Error: " . $e->getMessage();
    }
}

// Get distinct values for dropdowns
$faculties = $conn->query("
    SELECT DISTINCT faculty FROM modules 
    WHERE faculty IS NOT NULL AND faculty != '' 
    ORDER BY faculty
")->fetchAll(PDO::FETCH_COLUMN);

$schools = $conn->query("
    SELECT DISTINCT school FROM modules 
    WHERE school IS NOT NULL AND school != '' 
    ORDER BY school
")->fetchAll(PDO::FETCH_COLUMN);

$campuses = $conn->query("
    SELECT DISTINCT campus FROM modules 
    WHERE campus IS NOT NULL AND campus != '' 
    ORDER BY campus
")->fetchAll(PDO::FETCH_COLUMN);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Module - WSU Booking</title>
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .form-container {
            max-width: 1000px;
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
        
        .form-group label .required {
            color: #ef4444;
        }
        
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 15px;
            transition: all 0.3s;
        }
        
        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: var(--blue);
            box-shadow: 0 0 0 3px rgba(29, 78, 216, 0.1);
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
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .alert-success {
            background: #d1fae5;
            color: #10b981;
            border-left: 4px solid #10b981;
        }
        
        .alert-error {
            background: #fee2e2;
            color: #dc2626;
            border-left: 4px solid #dc2626;
        }
        
        .pass-rate-display {
            padding: 15px;
            background: #f9fafb;
            border-radius: 8px;
            border: 2px solid #e5e7eb;
            text-align: center;
        }
        
        .pass-rate-value {
            font-size: 32px;
            font-weight: 700;
            color: var(--blue);
            margin-bottom: 5px;
        }
        
        .risk-indicator {
            display: inline-block;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
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
                        <h1><i class="fas fa-plus-circle"></i> Add New Module</h1>
                        <p>Add a module with performance data to the system</p>
                    </div>

                    <?php if($success_message): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
                        </div>
                    <?php endif; ?>

                    <?php if($error_message): ?>
                        <div class="alert alert-error">
                            <i class="fas fa-exclamation-circle"></i> <?php echo $error_message; ?>
                        </div>
                    <?php endif; ?>

                    <div class="section">
                        <form method="POST" action="" id="moduleForm">
                            <div class="form-grid">
                                <!-- Academic Year -->
                                <div class="form-group">
                                    <label><i class="fas fa-calendar"></i> Academic Year <span class="required">*</span></label>
                                    <select name="academic_year" required>
                                        <option value="">Select Year</option>
                                        <option value="2024">2024</option>
                                        <option value="2025">2025</option>
                                        <option value="2026" selected>2026</option>
                                        <option value="2027">2027</option>
                                    </select>
                                </div>

                                <!-- Campus -->
                                <div class="form-group">
                                    <label><i class="fas fa-building"></i> Campus</label>
                                    <input type="text" name="campus" list="campusList" placeholder="e.g., Main Campus">
                                    <datalist id="campusList">
                                        <?php foreach($campuses as $campus): ?>
                                            <option value="<?php echo htmlspecialchars($campus); ?>">
                                        <?php endforeach; ?>
                                    </datalist>
                                </div>

                                <!-- Faculty -->
                                <div class="form-group">
                                    <label><i class="fas fa-university"></i> Faculty <span class="required">*</span></label>
                                    <input type="text" name="faculty" list="facultyList" required placeholder="e.g., Faculty of Science">
                                    <datalist id="facultyList">
                                        <?php foreach($faculties as $faculty): ?>
                                            <option value="<?php echo htmlspecialchars($faculty); ?>">
                                        <?php endforeach; ?>
                                    </datalist>
                                </div>

                                <!-- School -->
                                <div class="form-group">
                                    <label><i class="fas fa-school"></i> School</label>
                                    <input type="text" name="school" list="schoolList" placeholder="e.g., School of Computer Science">
                                    <datalist id="schoolList">
                                        <?php foreach($schools as $school): ?>
                                            <option value="<?php echo htmlspecialchars($school); ?>">
                                        <?php endforeach; ?>
                                    </datalist>
                                </div>

                                <!-- Subject Area -->
                                <div class="form-group">
                                    <label><i class="fas fa-tag"></i> Subject Area</label>
                                    <input type="text" name="subject_area" placeholder="e.g., CS, MATH, ENG">
                                </div>

                                <!-- Period of Study -->
                                <div class="form-group">
                                    <label><i class="fas fa-calendar-alt"></i> Period of Study</label>
                                    <select name="period_of_study">
                                        <option value="">Select Period</option>
                                        <option value="Semester 1">Semester 1</option>
                                        <option value="Semester 2">Semester 2</option>
                                        <option value="Full Year">Full Year</option>
                                    </select>
                                </div>

                                <!-- Academic Block Code -->
                                <div class="form-group">
                                    <label><i class="fas fa-cube"></i> Academic Block Code</label>
                                    <input type="text" name="academic_block_code" placeholder="e.g., BLK1">
                                </div>

                                <!-- Custom Grouping -->
                                <div class="form-group">
                                    <label><i class="fas fa-layer-group"></i> Custom Grouping</label>
                                    <input type="text" name="custom_grouping" placeholder="Optional grouping">
                                </div>

                                <!-- Subject Code -->
                                <div class="form-group">
                                    <label><i class="fas fa-code"></i> Subject Code <span class="required">*</span></label>
                                    <input type="text" name="subject_code" required placeholder="e.g., CS101" style="text-transform: uppercase;">
                                    <small>Unique identifier for the module</small>
                                </div>

                                <!-- Subject Name -->
                                <div class="form-group full-width">
                                    <label><i class="fas fa-book"></i> Subject Name <span class="required">*</span></label>
                                    <input type="text" name="subject_name" required placeholder="e.g., Introduction to Programming">
                                </div>

                                <!-- Subjects Passed -->
                                <div class="form-group">
                                    <label><i class="fas fa-user-check"></i> Students Passed <span class="required">*</span></label>
                                    <input type="number" name="subjects_passed" id="subjectsPassed" required min="0" placeholder="0" onchange="calculatePassRate()">
                                    <small>Number of students who passed</small>
                                </div>

                                <!-- Headcount -->
                                <div class="form-group">
                                    <label><i class="fas fa-users"></i> Total Students (Headcount) <span class="required">*</span></label>
                                    <input type="number" name="headcount" id="headcount" required min="1" placeholder="0" onchange="calculatePassRate()">
                                    <small>Total number of students enrolled</small>
                                </div>

                                <!-- Pass Rate Display -->
                                <div class="form-group full-width">
                                    <label><i class="fas fa-chart-line"></i> Calculated Pass Rate & Risk Category</label>
                                    <div class="pass-rate-display">
                                        <div class="pass-rate-value" id="passRateDisplay">0.0%</div>
                                        <div id="riskDisplay">
                                            <span class="risk-indicator" style="background: #e5e7eb; color: #6b7280;">Enter data to calculate</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div style="display: flex; gap: 15px; margin-top: 30px;">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Add Module
                                </button>
                                <button type="reset" class="btn btn-secondary" onclick="resetCalculations()">
                                    <i class="fas fa-redo"></i> Reset Form
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
    
    <script>
        function calculatePassRate() {
            const passed = parseInt(document.getElementById('subjectsPassed').value) || 0;
            const total = parseInt(document.getElementById('headcount').value) || 0;
            
            if(total > 0) {
                const passRate = (passed / total) * 100;
                document.getElementById('passRateDisplay').textContent = passRate.toFixed(1) + '%';
                
                // Determine risk category
                let riskText, riskColor, riskBg;
                if(passRate < 40) {
                    riskText = 'High Risk';
                    riskColor = '#dc2626';
                    riskBg = '#fee2e2';
                } else if(passRate < 60) {
                    riskText = 'Moderate Risk';
                    riskColor = '#ea580c';
                    riskBg = '#fed7aa';
                } else if(passRate < 75) {
                    riskText = 'Low Risk';
                    riskColor = '#f59e0b';
                    riskBg = '#fef3c7';
                } else {
                    riskText = 'Very Low Risk';
                    riskColor = '#10b981';
                    riskBg = '#d1fae5';
                }
                
                document.getElementById('riskDisplay').innerHTML = 
                    '<span class="risk-indicator" style="background: ' + riskBg + '; color: ' + riskColor + ';">' + riskText + '</span>';
            } else {
                document.getElementById('passRateDisplay').textContent = '0.0%';
                document.getElementById('riskDisplay').innerHTML = 
                    '<span class="risk-indicator" style="background: #e5e7eb; color: #6b7280;">Enter data to calculate</span>';
            }
        }
        
        function resetCalculations() {
            setTimeout(function() {
                document.getElementById('passRateDisplay').textContent = '0.0%';
                document.getElementById('riskDisplay').innerHTML = 
                    '<span class="risk-indicator" style="background: #e5e7eb; color: #6b7280;">Enter data to calculate</span>';
            }, 100);
        }
    </script>
</body>
</html>
