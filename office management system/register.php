<?php
/**
 * Public Registration / Sign Up Portal
 * Office Management System (OMS)
 */

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

// Redirect if already logged in
if (isset($_SESSION['user']['id'])) {
    redirect('index.php');
}

$error = '';
$departments = $pdo->query("SELECT * FROM departments ORDER BY name ASC")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $role = $_POST['role'] ?? 'employee';
    $departmentId = !empty($_POST['department_id']) ? (int)$_POST['department_id'] : 1;
    $designation = trim($_POST['designation'] ?? 'Staff Member');

    if (empty($fullName) || empty($email) || empty($password)) {
        $error = 'Full name, email address, and password are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        // Check if email already exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $error = 'An account with this email address already exists. Please sign in instead.';
        } else {
            $hashedPass = password_hash($password, PASSWORD_BCRYPT);
            $roleVal = ($role === 'admin') ? 'admin' : 'employee';
            
            $ins = $pdo->prepare("
                INSERT INTO users (full_name, email, password, role, department_id, designation, salary, date_of_joining, status)
                VALUES (?, ?, ?, ?, ?, ?, 65000.00, ?, 'active')
            ");
            $ins->execute([$fullName, $email, $hashedPass, $roleVal, $departmentId, $designation, date('Y-m-d')]);

            // Auto-login
            $newId = $pdo->lastInsertId();
            $userStmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
            $userStmt->execute([$newId]);
            $user = $userStmt->fetch();

            session_regenerate_id(true);
            unset($user['password']);
            $_SESSION['user'] = $user;

            flash_set('success', "Welcome to OfficeDesk, {$fullName}! Your account has been registered successfully.");
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
    <title>Create Account - Office Management System</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/auth.css">
</head>
<body>
<div class="auth-wrapper">
    <div class="auth-card" style="max-width: 500px;">
        <div class="auth-brand">
            <div class="auth-brand-icon">🏢</div>
            <div class="auth-brand-text">OfficeDesk</div>
        </div>

        <div class="auth-header">
            <h2>Create New Account</h2>
            <p>Join the office workspace as an employee or administrator</p>
        </div>

        <?php flash_render(); ?>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger">
                <span class="alert-icon">⚠️</span>
                <span><?= htmlspecialchars($error) ?></span>
            </div>
        <?php endif; ?>

        <form action="register.php" method="POST">
            <div class="form-group">
                <label for="full_name" class="form-label">Full Name *</label>
                <input type="text" name="full_name" id="full_name" class="form-control" placeholder="e.g. John Doe" required value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label for="email" class="form-label">Email Address *</label>
                <input type="email" name="email" id="email" class="form-control" placeholder="john.doe@office.com" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label for="password" class="form-label">Password *</label>
                <input type="password" name="password" id="password" class="form-control" placeholder="••••••••" required>
            </div>

            <div class="form-grid" style="grid-template-columns: 1fr 1fr; gap: 12px;">
                <div class="form-group">
                    <label for="role" class="form-label">Access Level / Role</label>
                    <select name="role" id="role" class="form-control">
                        <option value="employee" <?= (($_POST['role'] ?? '') === 'employee') ? 'selected' : '' ?>>Employee</option>
                        <option value="admin" <?= (($_POST['role'] ?? '') === 'admin') ? 'selected' : '' ?>>Administrator</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="department_id" class="form-label">Department</label>
                    <select name="department_id" id="department_id" class="form-control">
                        <?php foreach ($departments as $dept): ?>
                            <option value="<?= $dept['id'] ?>"><?= htmlspecialchars($dept['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label for="designation" class="form-label">Job Title / Designation</label>
                <input type="text" name="designation" id="designation" class="form-control" placeholder="e.g. Operations Coordinator" value="<?= htmlspecialchars($_POST['designation'] ?? '') ?>">
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 10px; padding: 12px;">
                Register & Enter Portal
            </button>
        </form>

        <div style="text-align: center; margin-top: 20px; font-size: 13.5px; color: #64748b;">
            Already have an account? <a href="login.php" style="color: #2563eb; font-weight: 600; text-decoration: none;">Sign In here</a>
        </div>
    </div>
</div>

<script src="assets/js/main.js"></script>
</body>
</html>
