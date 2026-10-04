<?php
/**
 * Edit Employee Details
 * Office Management System (OMS)
 */

$pageTitle = 'Edit Employee';
require_once __DIR__ . '/includes/header.php';
require_admin();

$empId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$empId]);
$emp = $stmt->fetch();

if (!$emp) {
    flash_set('danger', 'Employee not found.');
    redirect('employees.php');
}

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
    $dateOfJoining = !empty($_POST['date_of_joining']) ? $_POST['date_of_joining'] : null;
    $status = $_POST['status'] ?? 'active';

    if (empty($fullName)) $errors[] = 'Full name is required.';
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'A valid email address is required.';

    // Check unique email except this employee
    $chk = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
    $chk->execute([$email, $empId]);
    if ($chk->fetch()) {
        $errors[] = 'Email is already used by another employee.';
    }

    if (!empty($password) && strlen($password) < 6) {
        $errors[] = 'New password must be at least 6 characters.';
    }

    if (empty($errors)) {
        if (!empty($password)) {
            $hashed = password_hash($password, PASSWORD_BCRYPT);
            $upd = $pdo->prepare("
                UPDATE users SET full_name = ?, email = ?, password = ?, phone = ?, role = ?, 
                department_id = ?, designation = ?, salary = ?, date_of_joining = ?, status = ?
                WHERE id = ?
            ");
            $upd->execute([$fullName, $email, $hashed, $phone, $role, $departmentId, $designation, $salary, $dateOfJoining, $status, $empId]);
        } else {
            $upd = $pdo->prepare("
                UPDATE users SET full_name = ?, email = ?, phone = ?, role = ?, 
                department_id = ?, designation = ?, salary = ?, date_of_joining = ?, status = ?
                WHERE id = ?
            ");
            $upd->execute([$fullName, $email, $phone, $role, $departmentId, $designation, $salary, $dateOfJoining, $status, $empId]);
        }

        // If admin edited their own profile, sync session
        if ($empId === current_user_id()) {
            $_SESSION['user']['full_name'] = $fullName;
            $_SESSION['user']['email'] = $email;
            $_SESSION['user']['role'] = $role;
            $_SESSION['user']['designation'] = $designation;
        }

        flash_set('success', "Employee {$fullName} was updated successfully!");
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
            <div class="card-title">✏️ Edit Employee: <?= htmlspecialchars($emp['full_name']) ?></div>
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

            <form action="employee-edit.php?id=<?= $empId ?>" method="POST">
                <div class="form-grid">
                    <div class="form-group col-span-2">
                        <label class="form-label" for="full_name">Full Name *</label>
                        <input type="text" name="full_name" id="full_name" class="form-control" required value="<?= htmlspecialchars($_POST['full_name'] ?? $emp['full_name']) ?>">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="email">Work Email *</label>
                        <input type="email" name="email" id="email" class="form-control" required value="<?= htmlspecialchars($_POST['email'] ?? $emp['email']) ?>">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="password">Change Password (leave blank to keep current)</label>
                        <input type="password" name="password" id="password" class="form-control" placeholder="••••••••">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="phone">Phone Number</label>
                        <input type="text" name="phone" id="phone" class="form-control" value="<?= htmlspecialchars($_POST['phone'] ?? $emp['phone']) ?>">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="role">User Role *</label>
                        <select name="role" id="role" class="form-control">
                            <option value="employee" <?= (($_POST['role'] ?? $emp['role']) === 'employee') ? 'selected' : '' ?>>Employee</option>
                            <option value="admin" <?= (($_POST['role'] ?? $emp['role']) === 'admin') ? 'selected' : '' ?>>Administrator</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="department_id">Department</label>
                        <select name="department_id" id="department_id" class="form-control">
                            <option value="">Select Department</option>
                            <?php foreach ($departments as $dept): ?>
                                <option value="<?= $dept['id'] ?>" <?= (($_POST['department_id'] ?? $emp['department_id']) == $dept['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($dept['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="designation">Job Title / Designation</label>
                        <input type="text" name="designation" id="designation" class="form-control" value="<?= htmlspecialchars($_POST['designation'] ?? $emp['designation']) ?>">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="salary">Base Salary ($)</label>
                        <input type="number" step="0.01" name="salary" id="salary" class="form-control" value="<?= htmlspecialchars($_POST['salary'] ?? $emp['salary']) ?>">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="date_of_joining">Date of Joining</label>
                        <input type="date" name="date_of_joining" id="date_of_joining" class="form-control" value="<?= htmlspecialchars($_POST['date_of_joining'] ?? $emp['date_of_joining']) ?>">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="status">Account Status</label>
                        <select name="status" id="status" class="form-control">
                            <option value="active" <?= (($_POST['status'] ?? $emp['status']) === 'active') ? 'selected' : '' ?>>Active</option>
                            <option value="inactive" <?= (($_POST['status'] ?? $emp['status']) === 'inactive') ? 'selected' : '' ?>>Inactive</option>
                        </select>
                    </div>
                </div>

                <div style="margin-top: 24px; display: flex; gap: 12px; justify-content: flex-end;">
                    <a href="employees.php" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">Update Employee</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
