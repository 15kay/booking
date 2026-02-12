<?php
session_start();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Session Debug</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        .info { background: #e3f2fd; padding: 15px; border-radius: 8px; margin: 10px 0; }
        .error { background: #ffebee; padding: 15px; border-radius: 8px; margin: 10px 0; }
        .success { background: #e8f5e9; padding: 15px; border-radius: 8px; margin: 10px 0; }
        pre { background: #f5f5f5; padding: 10px; border-radius: 4px; overflow-x: auto; }
    </style>
</head>
<body>
    <h1>Session Debug Information</h1>
    
    <?php if(isset($_SESSION['staff_id'])): ?>
        <div class="success">
            <h2>✓ User is logged in</h2>
        </div>
        
        <div class="info">
            <h3>Session Data:</h3>
            <pre><?php print_r($_SESSION); ?></pre>
        </div>
        
        <div class="info">
            <h3>Role Check:</h3>
            <p><strong>Role:</strong> <?php echo $_SESSION['role'] ?? 'NOT SET'; ?></p>
            <p><strong>Is Tutor/PAL:</strong> <?php echo in_array($_SESSION['role'] ?? '', ['tutor', 'pal']) ? 'YES' : 'NO'; ?></p>
        </div>
        
        <div class="info">
            <h3>Access Test:</h3>
            <?php if(in_array($_SESSION['role'] ?? '', ['tutor', 'pal'])): ?>
                <p style="color: green;">✓ You SHOULD be able to access schedule.php</p>
                <p><a href="schedule.php" style="color: blue; text-decoration: underline;">Click here to test Schedule page</a></p>
            <?php else: ?>
                <p style="color: red;">✗ You CANNOT access schedule.php (role must be 'tutor' or 'pal')</p>
                <p>Your current role is: <strong><?php echo $_SESSION['role'] ?? 'NOT SET'; ?></strong></p>
            <?php endif; ?>
        </div>
        
    <?php else: ?>
        <div class="error">
            <h2>✗ User is NOT logged in</h2>
            <p>No staff_id found in session</p>
            <p><a href="../staff-login.php">Go to Login</a></p>
        </div>
    <?php endif; ?>
    
    <hr>
    <p><a href="index.php">← Back to Dashboard</a></p>
</body>
</html>
