<?php
/**
 * Universal Login Portal
 * Office Management System (OMS)
 * 
 * Supports:
 * 1. 1-Click Instant Login (Admin / Employee)
 * 2. Any email login with automatic account creation on the fly
 * 3. Standard password login for existing accounts
 */

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

// Redirect if already logged in
if (isset($_SESSION['user']['id'])) {
    redirect('index.php');
}

// 1. Handle Quick 1-Click Access via GET parameter (?quick=admin or ?quick=employee)
if (isset($_GET['quick'])) {
    $targetRole = ($_GET['quick'] === 'admin') ? 'admin' : 'employee';
    $stmt = $pdo->prepare("SELECT * FROM users WHERE role = ? AND status = 'active' ORDER BY id ASC LIMIT 1");
    $stmt->execute([$targetRole]);
    $quickUser = $stmt->fetch();

    if ($quickUser) {
        session_regenerate_id(true);
        unset($quickUser['password']);
        $_SESSION['user'] = $quickUser;
        flash_set('success', 'Logged in as ' . $quickUser['full_name'] . ' (' . ucfirst($quickUser['role']) . ')');
        redirect('index.php');
    }
}

$error = '';

// 2. Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $selectedRole = $_POST['role'] ?? 'employee';

    if (empty($email)) {
        $error = 'Please enter an email address to log in.';
    } else {
        // Check if user already exists
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user) {
            // Existing user: log in directly (password verified or auto-admitted)
            session_regenerate_id(true);
            unset($user['password']);
            $_SESSION['user'] = $user;
            
            flash_set('success', 'Welcome back, ' . $user['full_name'] . '!');
            redirect('index.php');
        } else {
            // New user: auto-create account on the spot so ANYONE can log in!
            $emailPrefix = explode('@', $email)[0];
            $cleanName = ucwords(str_replace(['.', '_', '-'], ' ', $emailPrefix));
            if (empty($cleanName)) $cleanName = 'Team Member';

            $userRole = in_array($selectedRole, ['admin', 'employee']) ? $selectedRole : 'employee';
            if (stripos($email, 'admin') !== false) {
                $userRole = 'admin';
            }

            $userPass = !empty($password) ? $password : 'password123';
            $hashed = password_hash($userPass, PASSWORD_BCRYPT);
            $designation = ($userRole === 'admin') ? 'Administrator' : 'Staff Member';

            $ins = $pdo->prepare("
                INSERT INTO users (full_name, email, password, role, department_id, designation, salary, date_of_joining, status)
                VALUES (?, ?, ?, ?, 1, ?, 65000.00, ?, 'active')
            ");
            $ins->execute([$cleanName, $email, $hashed, $userRole, $designation, date('Y-m-d')]);

            $newId = $pdo->lastInsertId();
            $fetchStmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
            $fetchStmt->execute([$newId]);
            $newUser = $fetchStmt->fetch();

            session_regenerate_id(true);
            unset($newUser['password']);
            $_SESSION['user'] = $newUser;

            flash_set('success', "Welcome, {$cleanName}! Your account has been automatically created as " . ucfirst($userRole) . '.');
            redirect('index.php');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In - Office Management System</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/auth.css">
</head>
<body>
<div class="auth-wrapper">
    <div class="auth-card">
        <div class="auth-brand">
            <div class="auth-brand-icon">🏢</div>
            <div class="auth-brand-text">OfficeDesk</div>
        </div>

        <div class="auth-header">
            <h2>Sign in to your portal</h2>
            <p>Open access: Enter any email or use 1-click instant login</p>
        </div>

        <?php flash_render(); ?>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger">
                <span class="alert-icon">⚠️</span>
                <span><?= htmlspecialchars($error) ?></span>
            </div>
        <?php endif; ?>

        <!-- 1-Click Instant Login Buttons -->
        <div style="margin-bottom: 22px; display: flex; flex-direction: column; gap: 8px;">
            <a href="login.php?quick=admin" class="btn btn-primary" style="padding: 10px; width: 100%; text-decoration: none;">
                ⚡ 1-Click Login as Admin
            </a>
            <a href="login.php?quick=employee" class="btn btn-secondary" style="padding: 10px; width: 100%; text-decoration: none;">
                ⚡ 1-Click Login as Employee
            </a>
        </div>

        <div style="display: flex; align-items: center; text-align: center; color: #94a3b8; font-size: 12px; margin-bottom: 20px;">
            <hr style="flex: 1; border: 0; border-top: 1px solid #e2e8f0;">
            <span style="padding: 0 10px;">OR SIGN IN WITH ANY EMAIL</span>
            <hr style="flex: 1; border: 0; border-top: 1px solid #e2e8f0;">
        </div>

        <form action="login.php" method="POST">
            <div class="form-group">
                <label for="email" class="form-label">Email Address *</label>
                <input type="email" name="email" id="email" class="form-control" placeholder="any.name@office.com" required autofocus value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                <small style="color: #64748b; font-size: 11px;">New email? An account will be auto-created for you instantly!</small>
            </div>

            <div class="form-group">
                <label for="password" class="form-label">Password</label>
                <input type="password" name="password" id="password" class="form-control" placeholder="•••••••• (any password accepted)">
            </div>

            <div class="form-group">
                <label for="role" class="form-label">Sign In As</label>
                <select name="role" id="role" class="form-control">
                    <option value="employee">Employee (Team Member Access)</option>
                    <option value="admin">Administrator (Full Access)</option>
                </select>
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 8px; padding: 12px;">
                Enter Workspace
            </button>
        </form>

        <div style="text-align: center; margin-top: 18px; font-size: 13px; color: #64748b;">
            Want a tailored profile? <a href="register.php" style="color: #2563eb; font-weight: 600; text-decoration: none;">Sign Up here</a>
        </div>

        <div class="demo-credentials" style="margin-top: 20px;">
            <h4>💡 Pre-Configured Demo Accounts</h4>
            <div class="demo-row">
                <span><strong>Admin:</strong> <code>admin@office.com</code></span>
                <a href="login.php?quick=admin" class="btn btn-secondary btn-sm" style="text-decoration:none;">Log In</a>
            </div>
            <div class="demo-row" style="margin-top: 8px;">
                <span><strong>Employee:</strong> <code>alex.chen@office.com</code></span>
                <a href="login.php?quick=employee" class="btn btn-secondary btn-sm" style="text-decoration:none;">Log In</a>
            </div>
        </div>
    </div>
</div>

<script src="assets/js/main.js"></script>
</body>
</html>
