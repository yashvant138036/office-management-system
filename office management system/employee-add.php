<?php
/**
 * Add New Employee
 * Office Management System (OMS)
 */

$pageTitle = 'Add New Employee';
require_once __DIR__ . '/includes/header.php';
require_admin();

$errors = [];
$departments = $pdo->query("SELECT * FROM departments ORDER BY name ASC")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $role = $_POST['role'] ?? 'employee';
    $departmentId = !empty($_POST['department_id']) ? (int)$_POST['department_id'] : null;
    $designation = trim($_POST['designation'] ?? '');
    $salary = !empty($_POST['salary']) ? (float)$_POST['salary'] : 0.00;
    $dateOfJoining = !empty($_POST['date_of_joining']) ? $_POST['date_of_joining'] : date('Y-m-d');
    $status = $_POST['status'] ?? 'active';

    if (empty($fullName)) $errors[] = 'Full name is required.';
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'A valid email address is required.';
    if (empty($password) || strlen($password) < 6) $errors[] = 'Password must be at least 6 characters.';

    // Check email uniqueness
    $chk = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $chk->execute([$email]);
    if ($chk->fetch()) {
        $errors[] = 'An employee with this email address already exists.';
    }

    if (empty($errors)) {
        $hashedPass = password_hash($password, PASSWORD_BCRYPT);
        $insert = $pdo->prepare("
            INSERT INTO users (full_name, email, password, phone, role, department_id, designation, salary, date_of_joining, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $insert->execute([$fullName, $email, $hashedPass, $phone, $role, $departmentId, $designation, $salary, $dateOfJoining, $status]);

        flash_set('success', "Employee {$fullName} was successfully created!");
        redirect('employees.php');
    }
}
?>

<div style="max-width: 800px; margin: 0 auto;">
    <div style="margin-bottom: 20px;">
        <a href="employees.php" class="btn btn-secondary btn-sm">← Back to Employee Directory</a>
    </div>

    <div class="card">
        <div class="card-header">
            <div class="card-title">👤 New Employee Information</div>
        </div>
        <div class="card-body">
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul style="margin: 0; padding-left: 20px;">
                        <?php foreach ($errors as $err): ?>
                            <li><?= htmlspecialchars($err) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form action="employee-add.php" method="POST">
                <div class="form-grid">
                    <div class="form-group col-span-2">
                        <label class="form-label" for="full_name">Full Name *</label>
                        <input type="text" name="full_name" id="full_name" class="form-control" required value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>" placeholder="e.g. Jane Doe">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="email">Work Email *</label>
                        <input type="email" name="email" id="email" class="form-control" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" placeholder="jane.doe@office.com">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="password">Initial Password * (min 6 chars)</label>
                        <input type="password" name="password" id="password" class="form-control" required placeholder="••••••••">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="phone">Phone Number</label>
                        <input type="text" name="phone" id="phone" class="form-control" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>" placeholder="+1 (555) 000-0000">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="role">User Role *</label>
                        <select name="role" id="role" class="form-control">
                            <option value="employee" <?= (($_POST['role'] ?? '') === 'employee') ? 'selected' : '' ?>>Employee (Staff Access)</option>
                            <option value="admin" <?= (($_POST['role'] ?? '') === 'admin') ? 'selected' : '' ?>>Administrator (Full Control)</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="department_id">Department</label>
                        <select name="department_id" id="department_id" class="form-control">
                            <option value="">Select Department</option>
                            <?php foreach ($departments as $dept): ?>
                                <option value="<?= $dept['id'] ?>" <?= (($_POST['department_id'] ?? '') == $dept['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($dept['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="designation">Job Title / Designation</label>
                        <input type="text" name="designation" id="designation" class="form-control" value="<?= htmlspecialchars($_POST['designation'] ?? '') ?>" placeholder="e.g. Senior Frontend Engineer">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="salary">Base Salary ($)</label>
                        <input type="number" step="0.01" name="salary" id="salary" class="form-control" value="<?= htmlspecialchars($_POST['salary'] ?? '60000.00') ?>">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="date_of_joining">Date of Joining</label>
                        <input type="date" name="date_of_joining" id="date_of_joining" class="form-control" value="<?= htmlspecialchars($_POST['date_of_joining'] ?? date('Y-m-d')) ?>">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="status">Account Status</label>
                        <select name="status" id="status" class="form-control">
                            <option value="active" <?= (($_POST['status'] ?? '') === 'active') ? 'selected' : '' ?>>Active</option>
                            <option value="inactive" <?= (($_POST['status'] ?? '') === 'inactive') ? 'selected' : '' ?>>Inactive</option>
                        </select>
                    </div>
                </div>

                <div style="margin-top: 24px; display: flex; gap: 12px; justify-content: flex-end;">
                    <a href="employees.php" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">Save Employee</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
