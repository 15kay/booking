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
$imported_count = 0;

// Handle CSV upload
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['csv_file'])) {
    $file = $_FILES['csv_file'];
    
    if($file['error'] == 0) {
        $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if($file_ext == 'csv') {
            $handle = fopen($file['tmp_name'], 'r');
            
            // Skip header row
            $header = fgetcsv($handle);
            
            $conn->beginTransaction();
            
            try {
                while(($row = fgetcsv($handle)) !== false) {
                    // Map CSV columns to database fields
                    // Expected order: Academic Year, Campus, Custom Grouping, Faculty, School, 
                    // Subject Area, Period Of Study, Academic Block Code, Subject Code, 
                    // Subject Name, Subjects Passed, Headcount, Subject Pass Rate
                    
                    if(count($row) >= 13) {
                        $academic_year = trim($row[0]);
                        $campus = trim($row[1]);
                        $custom_grouping = trim($row[2]);
                        $faculty = trim($row[3]);
                        $school = trim($row[4]);
                        $subject_area = trim($row[5]);
                        $period_of_study = trim($row[6]);
                        $academic_block_code = trim($row[7]);
                        $subject_code = trim($row[8]);
                        $subject_name = trim($row[9]);
                        $subjects_passed = intval($row[10]);
                        $headcount = intval($row[11]);
                        $subject_pass_rate = floatval($row[12]);
                        
                        // Check if module already exists
                        $check_stmt = $conn->prepare("
                            SELECT module_id FROM modules 
                            WHERE subject_code = ? AND academic_year = ? AND period_of_study = ?
                        ");
                        $check_stmt->execute([$subject_code, $academic_year, $period_of_study]);
                        
                        if($check_stmt->rowCount() > 0) {
                            // Update existing module
                            $stmt = $conn->prepare("
                                UPDATE modules SET
                                    campus = ?, custom_grouping = ?, faculty = ?, school = ?,
                                    subject_area = ?, academic_block_code = ?, subject_name = ?,
                                    subjects_passed = ?, headcount = ?, subject_pass_rate = ?,
                                    updated_at = NOW()
                                WHERE subject_code = ? AND academic_year = ? AND period_of_study = ?
                            ");
                            $stmt->execute([
                                $campus, $custom_grouping, $faculty, $school, $subject_area,
                                $academic_block_code, $subject_name, $subjects_passed, $headcount,
                                $subject_pass_rate, $subject_code, $academic_year, $period_of_study
                            ]);
                        } else {
                            // Insert new module
                            $stmt = $conn->prepare("
                                INSERT INTO modules 
                                (academic_year, campus, custom_grouping, faculty, school, subject_area,
                                 period_of_study, academic_block_code, subject_code, subject_name,
                                 subjects_passed, headcount, subject_pass_rate)
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                            ");
                            $stmt->execute([
                                $academic_year, $campus, $custom_grouping, $faculty, $school,
                                $subject_area, $period_of_study, $academic_block_code, $subject_code,
                                $subject_name, $subjects_passed, $headcount, $subject_pass_rate
                            ]);
                            $imported_count++;
                        }
                    }
                }
                
                $conn->commit();
                $success_message = "Successfully imported/updated $imported_count modules!";
                
            } catch(Exception $e) {
                $conn->rollBack();
                $error_message = "Error importing modules: " . $e->getMessage();
            }
            
            fclose($handle);
        } else {
            $error_message = "Please upload a CSV file.";
        }
    } else {
        $error_message = "Error uploading file.";
    }
}

// Get statistics
$stats = $conn->query("
    SELECT 
        COUNT(*) as total_modules,
        SUM(CASE WHEN risk_category = 'High Risk' THEN 1 ELSE 0 END) as high_risk,
        SUM(CASE WHEN risk_category = 'Moderate Risk' THEN 1 ELSE 0 END) as moderate_risk,
        SUM(CASE WHEN risk_category = 'Low Risk' THEN 1 ELSE 0 END) as low_risk,
        SUM(CASE WHEN risk_category = 'Very Low Risk' THEN 1 ELSE 0 END) as very_low_risk,
        AVG(subject_pass_rate) as avg_pass_rate
    FROM modules WHERE status = 'active'
")->fetch();

// Get recent modules
$recent_modules = $conn->query("
    SELECT * FROM modules 
    WHERE status = 'active'
    ORDER BY created_at DESC 
    LIMIT 10
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Import Modules - WSU Booking</title>
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .upload-area {
            border: 3px dashed #e5e7eb;
            border-radius: 12px;
            padding: 40px;
            text-align: center;
            background: #f9fafb;
            transition: all 0.3s;
        }
        
        .upload-area:hover {
            border-color: var(--blue);
            background: #eff6ff;
        }
        
        .upload-area i {
            font-size: 64px;
            color: var(--blue);
            margin-bottom: 20px;
        }
        
        .file-input {
            display: none;
        }
        
        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
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
        
        .table-container {
            overflow-x: auto;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        table th,
        table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
        }
        
        table th {
            background: #f9fafb;
            font-weight: 600;
            color: var(--dark);
        }
        
        table tr:hover {
            background: #f9fafb;
        }
        
        .risk-badge {
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
        }
        
        .risk-high {
            background: #fee2e2;
            color: #dc2626;
        }
        
        .risk-moderate {
            background: #fed7aa;
            color: #ea580c;
        }
        
        .risk-low {
            background: #fef3c7;
            color: #f59e0b;
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
                    <h1><i class="fas fa-file-import"></i> Import Modules</h1>
                    <p>Upload module data with pass rates to identify at-risk modules</p>
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

                <!-- Statistics -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon blue">
                            <i class="fas fa-book"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $stats['total_modules']; ?></h3>
                            <p>Total Modules</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon red">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $stats['high_risk']; ?></h3>
                            <p>High Risk (<40%)</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon orange">
                            <i class="fas fa-exclamation-circle"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $stats['moderate_risk']; ?></h3>
                            <p>Moderate Risk (40-59%)</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon green">
                            <i class="fas fa-percentage"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo number_format($stats['avg_pass_rate'] * 100, 1); ?>%</h3>
                            <p>Average Pass Rate</p>
                        </div>
                    </div>
                </div>

                <!-- Upload Section -->
                <div class="section">
                    <h2 class="section-title"><i class="fas fa-upload"></i> Upload Module Data</h2>
                    
                    <form method="POST" enctype="multipart/form-data">
                        <div class="upload-area" onclick="document.getElementById('csvFile').click()">
                            <i class="fas fa-cloud-upload-alt"></i>
                            <h3>Click to Upload CSV File</h3>
                            <p style="color: #6b7280; margin-top: 10px;">
                                Upload a CSV file with module data including pass rates
                            </p>
                            <input type="file" id="csvFile" name="csv_file" accept=".csv" class="file-input" onchange="this.form.submit()">
                        </div>
                    </form>

                    <div style="margin-top: 20px; padding: 15px; background: #eff6ff; border-radius: 8px; border-left: 4px solid var(--blue);">
                        <h4 style="color: var(--dark); margin-bottom: 10px;">
                            <i class="fas fa-info-circle"></i> CSV Format Requirements
                        </h4>
                        <p style="color: #6b7280; font-size: 14px; line-height: 1.6;">
                            Your CSV file should contain the following columns in order:<br>
                            <strong>Academic Year, Campus, Custom Grouping, Faculty, School, Subject Area, 
                            Period Of Study, Academic Block Code, Subject Code, Subject Name, 
                            Subjects Passed, Headcount, Subject Pass Rate</strong>
                        </p>
                        <p style="color: #6b7280; font-size: 14px; margin-top: 10px;">
                            <strong>Risk Categories:</strong><br>
                            • High Risk: Pass Rate < 40%<br>
                            • Moderate Risk: Pass Rate 40-59%<br>
                            • Low Risk: Pass Rate 60-74%<br>
                            • Very Low Risk: Pass Rate ≥ 75%
                        </p>
                    </div>
                </div>

                <!-- Recent Modules -->
                <div class="section">
                    <h2 class="section-title"><i class="fas fa-list"></i> Recently Added Modules</h2>
                    
                    <?php if(count($recent_modules) > 0): ?>
                        <div class="table-container">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Subject Code</th>
                                        <th>Subject Name</th>
                                        <th>Faculty</th>
                                        <th>Pass Rate</th>
                                        <th>Students</th>
                                        <th>Risk Category</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($recent_modules as $module): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($module['subject_code']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($module['subject_name']); ?></td>
                                        <td><?php echo htmlspecialchars($module['faculty']); ?></td>
                                        <td><strong><?php echo number_format($module['subject_pass_rate'] * 100, 1); ?>%</strong></td>
                                        <td><?php echo $module['subjects_passed']; ?>/<?php echo $module['headcount']; ?></td>
                                        <td>
                                            <span class="risk-badge risk-<?php echo strtolower(str_replace(' ', '-', $module['risk_category'])); ?>">
                                                <?php echo $module['risk_category']; ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-database"></i>
                            <h3>No Modules Yet</h3>
                            <p>Upload a CSV file to import module data</p>
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
