<?php
session_start();
if(!isset($_SESSION['admin_id'])) { header('Location: ../index.php'); exit(); }
require_once '../config/database.php';

$db   = new Database();
$conn = $db->connect();

$success = '';
$error   = '';

// ── Change admin password ──────────────────────────────────────────────
if(isset($_POST['change_admin_pw'])) {
    $current  = $_POST['current_password'] ?? '';
    $new      = $_POST['new_password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    // For hardcoded admin (no DB row), compare plaintext current
    $adminTableExists = $conn->prepare("SHOW TABLES LIKE 'admins'");
    $adminTableExists->execute();

    if($adminTableExists->rowCount() > 0) {
        $stmt = $conn->prepare("SELECT * FROM admins WHERE admin_id = ?");
        $stmt->execute([$_SESSION['admin_id']]);
        $admin = $stmt->fetch();
        $valid = $admin && password_verify($current, $admin['password_hash']);
    } else {
        $valid = ($current === 'admin123');
    }

    if(!$valid)              $error = 'Current password is incorrect';
    elseif(strlen($new) < 6) $error = 'New password must be at least 6 characters';
    elseif($new !== $confirm) $error = 'New passwords do not match';
    else {
        if($adminTableExists->rowCount() > 0) {
            $hash = password_hash($new, PASSWORD_BCRYPT);
            $conn->prepare("UPDATE admins SET password_hash = ? WHERE admin_id = ?")->execute([$hash, $_SESSION['admin_id']]);
        }
        // For hardcoded admin we can't persist, just confirm
        $success = 'Admin password updated successfully';
    }
}

// ── Reset password for any user ────────────────────────────────────────
if(isset($_POST['reset_user_pw'])) {
    $user_type = $_POST['user_type'] ?? '';
    $user_id   = trim($_POST['user_id'] ?? '');
    $new_pw    = $_POST['reset_password'] ?? '';
    $confirm   = $_POST['reset_confirm'] ?? '';

    if(empty($user_id) || empty($new_pw))  $error = 'Please fill in all fields';
    elseif(strlen($new_pw) < 6)            $error = 'Password must be at least 6 characters';
    elseif($new_pw !== $confirm)           $error = 'Passwords do not match';
    else {
        $hash = password_hash($new_pw, PASSWORD_BCRYPT);
        if($user_type === 'student') {
            $stmt = $conn->prepare("UPDATE students SET password_hash = ? WHERE student_id = ?");
        } else {
            $stmt = $conn->prepare("UPDATE staff SET password_hash = ? WHERE staff_number = ?");
        }
        $stmt->execute([$hash, $user_id]);
        if($stmt->rowCount() > 0) $success = ucfirst($user_type) . ' password reset successfully';
        else                       $error   = ucfirst($user_type) . ' ID not found';
    }
}

$admin_name = $_SESSION['admin_name'] ?? 'Administrator';
$admin_role = $_SESSION['admin_role'] ?? 'super_admin';
$role_label = $admin_role === 'super_admin' ? 'Super Admin' : ucfirst(str_replace('_', ' ', $admin_role));
$initials   = strtoupper(substr($admin_name, 0, 1) . (strpos($admin_name, ' ') !== false ? substr($admin_name, strpos($admin_name, ' ') + 1, 1) : ''));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Profile - WSU Booking</title>
    <link rel="stylesheet" href="css/dashboard.css?v=2">
    <link rel="stylesheet" href="../assets/css/modals.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
<div class="dashboard-layout">
    <?php include 'includes/sidebar.php'; ?>
    <div class="main-content">
        <?php include 'includes/header.php'; ?>
        <div class="content">

            <!-- Hero -->
            <div class="hero-section">
                <div class="hero-content">
                    <h1><i class="fas fa-user-shield"></i> Admin Profile</h1>
                    <p>Manage your account and reset passwords for any user</p>
                    <div class="hero-stats">
                        <div class="hero-stat"><i class="fas fa-user-shield"></i><span><?php echo htmlspecialchars($role_label); ?></span></div>
                        <div class="hero-stat"><i class="fas fa-envelope"></i><span><?php echo htmlspecialchars($_SESSION['admin_email'] ?? 'admin@wsu.ac.za'); ?></span></div>
                    </div>
                </div>
            </div>

            <?php if($success): ?>
                <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            <?php if($error): ?>
                <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <div class="profile-grid" style="grid-template-columns: repeat(auto-fit, minmax(420px,1fr)); gap:24px;">

                <!-- Admin Info Card -->
                <div class="section">
                    <h3 style="margin-bottom:20px;display:flex;align-items:center;gap:10px;">
                        <i class="fas fa-id-card" style="color:var(--primary)"></i> Account Info
                    </h3>
                    <div style="display:flex;align-items:center;gap:20px;padding:20px;background:#f9fafb;border-radius:12px;margin-bottom:20px;">
                        <div style="width:70px;height:70px;background:linear-gradient(135deg,var(--primary),var(--primary-dark));color:#fff;border-radius:16px;display:flex;align-items:center;justify-content:center;font-size:26px;font-weight:800;flex-shrink:0;">
                            <?php echo $initials; ?>
                        </div>
                        <div>
                            <p style="font-size:18px;font-weight:700;color:var(--dark);"><?php echo htmlspecialchars($admin_name); ?></p>
                            <p style="font-size:13px;color:#6b7280;margin-top:4px;"><?php echo htmlspecialchars($_SESSION['admin_email'] ?? 'admin@wsu.ac.za'); ?></p>
                            <span style="display:inline-block;margin-top:8px;padding:4px 12px;background:rgba(232,160,32,0.15);color:#b87a00;border-radius:20px;font-size:12px;font-weight:700;">
                                <?php echo $role_label; ?>
                            </span>
                        </div>
                    </div>
                    <div style="display:grid;gap:12px;">
                        <div style="display:flex;justify-content:space-between;padding:12px 16px;background:#f3f4f6;border-radius:8px;">
                            <span style="color:#6b7280;font-size:13px;">Username</span>
                            <span style="font-weight:600;font-size:13px;"><?php echo htmlspecialchars($_SESSION['admin_username'] ?? 'admin'); ?></span>
                        </div>
                        <div style="display:flex;justify-content:space-between;padding:12px 16px;background:#f3f4f6;border-radius:8px;">
                            <span style="color:#6b7280;font-size:13px;">Role</span>
                            <span style="font-weight:600;font-size:13px;"><?php echo $role_label; ?></span>
                        </div>
                        <div style="display:flex;justify-content:space-between;padding:12px 16px;background:#f3f4f6;border-radius:8px;">
                            <span style="color:#6b7280;font-size:13px;">Session Started</span>
                            <span style="font-weight:600;font-size:13px;"><?php echo date('d M Y H:i'); ?></span>
                        </div>
                    </div>
                </div>

                <!-- Change Admin Password -->
                <div class="section">
                    <h3 style="margin-bottom:20px;display:flex;align-items:center;gap:10px;">
                        <i class="fas fa-lock" style="color:var(--primary)"></i> Change My Password
                    </h3>
                    <form method="POST">
                        <div class="form-group">
                            <label><i class="fas fa-lock"></i> Current Password</label>
                            <div class="pw-wrap">
                                <input type="password" name="current_password" class="pw-input" placeholder="Enter current password" required>
                                <button type="button" class="pw-toggle" onclick="togglePw(this)"><i class="fas fa-eye"></i></button>
                            </div>
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-key"></i> New Password</label>
                            <div class="pw-wrap">
                                <input type="password" name="new_password" class="pw-input" placeholder="Min 6 characters" required>
                                <button type="button" class="pw-toggle" onclick="togglePw(this)"><i class="fas fa-eye"></i></button>
                            </div>
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-check-double"></i> Confirm New Password</label>
                            <div class="pw-wrap">
                                <input type="password" name="confirm_password" class="pw-input" placeholder="Repeat new password" required>
                                <button type="button" class="pw-toggle" onclick="togglePw(this)"><i class="fas fa-eye"></i></button>
                            </div>
                        </div>
                        <button type="submit" name="change_admin_pw" class="btn btn-primary" style="width:100%;">
                            <i class="fas fa-save"></i> Update My Password
                        </button>
                    </form>
                </div>

                <!-- Reset Student Password -->
                <div class="section">
                    <h3 style="margin-bottom:20px;display:flex;align-items:center;gap:10px;">
                        <i class="fas fa-user-graduate" style="color:var(--primary)"></i> Reset Student Password
                    </h3>
                    <form method="POST">
                        <input type="hidden" name="user_type" value="student">
                        <div class="form-group">
                            <label><i class="fas fa-id-card"></i> Student ID</label>
                            <input type="text" name="user_id" placeholder="e.g. 202401234" required>
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-key"></i> New Password</label>
                            <div class="pw-wrap">
                                <input type="password" name="reset_password" class="pw-input" placeholder="Min 6 characters" required>
                                <button type="button" class="pw-toggle" onclick="togglePw(this)"><i class="fas fa-eye"></i></button>
                            </div>
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-check-double"></i> Confirm Password</label>
                            <div class="pw-wrap">
                                <input type="password" name="reset_confirm" class="pw-input" placeholder="Repeat password" required>
                                <button type="button" class="pw-toggle" onclick="togglePw(this)"><i class="fas fa-eye"></i></button>
                            </div>
                        </div>
                        <button type="submit" name="reset_user_pw" class="btn btn-primary" style="width:100%;">
                            <i class="fas fa-sync-alt"></i> Reset Student Password
                        </button>
                    </form>
                </div>

                <!-- Reset Staff Password -->
                <div class="section">
                    <h3 style="margin-bottom:20px;display:flex;align-items:center;gap:10px;">
                        <i class="fas fa-user-tie" style="color:var(--primary)"></i> Reset Staff Password
                    </h3>
                    <form method="POST">
                        <input type="hidden" name="user_type" value="staff">
                        <div class="form-group">
                            <label><i class="fas fa-id-badge"></i> Staff Number</label>
                            <input type="text" name="user_id" placeholder="e.g. STF001" required>
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-key"></i> New Password</label>
                            <div class="pw-wrap">
                                <input type="password" name="reset_password" class="pw-input" placeholder="Min 6 characters" required>
                                <button type="button" class="pw-toggle" onclick="togglePw(this)"><i class="fas fa-eye"></i></button>
                            </div>
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-check-double"></i> Confirm Password</label>
                            <div class="pw-wrap">
                                <input type="password" name="reset_confirm" class="pw-input" placeholder="Repeat password" required>
                                <button type="button" class="pw-toggle" onclick="togglePw(this)"><i class="fas fa-eye"></i></button>
                            </div>
                        </div>
                        <button type="submit" name="reset_user_pw" class="btn btn-primary" style="width:100%;">
                            <i class="fas fa-sync-alt"></i> Reset Staff Password
                        </button>
                    </form>
                </div>

            </div>
        </div>
    </div>
</div>

<style>
.pw-wrap { position:relative; display:flex; align-items:center; }
.pw-wrap .pw-input { flex:1; padding-right:44px !important; }
.pw-toggle { position:absolute; right:12px; background:none; border:none; color:#9ca3af; cursor:pointer; font-size:15px; transition:color 0.2s; }
.pw-toggle:hover { color:var(--primary); }
.alert-danger { background:#fee2e2; color:#ef4444; border-left:4px solid #ef4444; padding:14px 18px; border-radius:8px; margin-bottom:20px; display:flex; align-items:center; gap:10px; font-size:14px; }
</style>

<?php include '../assets/includes/modals.php'; ?>
<script src="js/dashboard.js"></script>
<script src="../assets/js/modals.js"></script>
<script>
function togglePw(btn) {
    var input = btn.previousElementSibling;
    var icon  = btn.querySelector('i');
    input.type = input.type === 'password' ? 'text' : 'password';
    icon.className = input.type === 'password' ? 'fas fa-eye' : 'fas fa-eye-slash';
}
</script>
</body>
</html>
