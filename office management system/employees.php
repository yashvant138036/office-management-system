<?php
/**
 * Employee Management & Directory
 * Office Management System (OMS)
 */

$pageTitle = 'Employee Directory';
require_once __DIR__ . '/includes/header.php';

// Handle Delete or Status Toggle (Admin Only)
if (is_admin() && isset($_GET['action']) && isset($_GET['id'])) {
    $empId = (int)$_GET['id'];
    $action = $_GET['action'];

    // Prevent admin from deleting themselves
    if ($empId === current_user_id() && ($action === 'delete' || $action === 'toggle')) {
        flash_set('danger', 'You cannot alter your own administrative account status from here.');
        redirect('employees.php');
    }

    if ($action === 'delete') {
        $delStmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $delStmt->execute([$empId]);
        flash_set('success', 'Employee record removed successfully.');
    } elseif ($action === 'toggle') {
        $togStmt = $pdo->prepare("UPDATE users SET status = (CASE WHEN status = 'active' THEN 'inactive' ELSE 'active' END) WHERE id = ?");
        $togStmt->execute([$empId]);
        flash_set('success', 'Employee status updated successfully.');
    }
    redirect('employees.php');
}

// Search and filter parameters
$search = trim($_GET['search'] ?? '');
$deptFilter = trim($_GET['department'] ?? '');

$sql = "
    SELECT u.*, d.name AS department_name 
    FROM users u
    LEFT JOIN departments d ON u.department_id = d.id
    WHERE 1=1
";
$params = [];

if (!empty($search)) {
    $sql .= " AND (u.full_name LIKE ? OR u.email LIKE ? OR u.designation LIKE ?)";
    $term = "%{$search}%";
    $params[] = $term;
    $params[] = $term;
    $params[] = $term;
}

if (!empty($deptFilter)) {
    $sql .= " AND u.department_id = ?";
    $params[] = $deptFilter;
}

$sql .= " ORDER BY u.role ASC, u.full_name ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$employees = $stmt->fetchAll();

// Fetch departments for filter dropdown
$departments = $pdo->query("SELECT * FROM departments ORDER BY name ASC")->fetchAll();
?>

<div class="filter-bar">
    <form method="GET" action="employees.php" style="display: flex; gap: 12px; flex-wrap: wrap; align-items: center;">
        <div class="search-box">
            <span>🔍</span>
            <input type="text" name="search" placeholder="Search by name, email, role..." value="<?= htmlspecialchars($search) ?>">
        </div>

        <select name="department" class="form-control" style="width: 200px;" onchange="this.form.submit();">
            <option value="">All Departments</option>
            <?php foreach ($departments as $dept): ?>
                <option value="<?= $dept['id'] ?>" <?= ($deptFilter == $dept['id']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($dept['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <button type="submit" class="btn btn-secondary">Filter</button>
        <?php if (!empty($search) || !empty($deptFilter)): ?>
            <a href="employees.php" class="btn btn-outline">Clear</a>
        <?php endif; ?>
    </form>

    <?php if (is_admin()): ?>
        <a href="employee-add.php" class="btn btn-primary">
            <span>➕</span> Add New Employee
        </a>
    <?php endif; ?>
</div>

<div class="card">
    <div class="card-header">
        <div class="card-title">👥 Team Members (<?= count($employees) ?>)</div>
    </div>
    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Employee</th>
                    <th>Contact</th>
                    <th>Department</th>
                    <th>Designation</th>
                    <th>Role</th>
                    <?php if (is_admin()): ?>
                        <th>Salary</th>
                        <th>Joined</th>
                    <?php endif; ?>
                    <th>Status</th>
                    <?php if (is_admin()): ?>
                        <th>Actions</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($employees)): ?>
                    <tr>
                        <td colspan="<?= is_admin() ? '9' : '6' ?>" class="table-empty">
                            No employees match your search criteria.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($employees as $emp): ?>
                        <tr>
                            <td>
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <div class="user-avatar" style="width: 32px; height: 32px; font-size: 12px;">
                                        <?= strtoupper(substr($emp['full_name'], 0, 1)) ?>
                                    </div>
                                    <div>
                                        <strong><?= htmlspecialchars($emp['full_name']) ?></strong>
                                        <?php if ($emp['id'] === current_user_id()): ?>
                                            <span style="font-size: 11px; color: #2563eb; font-weight: 600;">(You)</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div><?= htmlspecialchars($emp['email']) ?></div>
                                <small class="text-muted"><?= htmlspecialchars($emp['phone'] ?? '-') ?></small>
                            </td>
                            <td><?= htmlspecialchars($emp['department_name'] ?? 'Unassigned') ?></td>
                            <td><?= htmlspecialchars($emp['designation'] ?? '-') ?></td>
                            <td>
                                <span class="badge <?= ($emp['role'] === 'admin') ? 'badge-primary' : 'badge-secondary' ?>" style="<?= ($emp['role'] === 'admin') ? 'background:#e0e7ff; color:#3730a3;' : '' ?>">
                                    <?= ucfirst($emp['role']) ?>
                                </span>
                            </td>
                            <?php if (is_admin()): ?>
                                <td><?= format_currency($emp['salary']) ?></td>
                                <td><?= format_date($emp['date_of_joining']) ?></td>
                            <?php endif; ?>
                            <td><?= status_badge($emp['status']) ?></td>
                            <?php if (is_admin()): ?>
                                <td>
                                    <div style="display: flex; gap: 6px;">
                                        <a href="employee-edit.php?id=<?= $emp['id'] ?>" class="btn btn-secondary btn-sm" title="Edit">✏️ Edit</a>
                                        <?php if ($emp['id'] !== current_user_id()): ?>
                                            <a href="employees.php?action=toggle&id=<?= $emp['id'] ?>" class="btn btn-outline btn-sm" title="Toggle Status">
                                                <?= ($emp['status'] === 'active') ? 'Deactivate' : 'Activate' ?>
                                            </a>
                                            <a href="employees.php?action=delete&id=<?= $emp['id'] ?>" class="btn btn-danger btn-sm" title="Delete" onclick="return confirm('Permanently remove this employee account? This cannot be undone.');">🗑️</a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
