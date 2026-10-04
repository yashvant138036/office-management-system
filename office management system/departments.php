<?php
/**
 * Department Management
 * Office Management System (OMS)
 */

$pageTitle = 'Departments';
require_once __DIR__ . '/includes/header.php';
require_admin();

$error = '';

// Handle Create Department
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $name = trim($_POST['name'] ?? '');
    $desc = trim($_POST['description'] ?? '');

    if (empty($name)) {
        $error = 'Department name is required.';
    } else {
        $chk = $pdo->prepare("SELECT id FROM departments WHERE name = ?");
        $chk->execute([$name]);
        if ($chk->fetch()) {
            $error = 'A department with this name already exists.';
        } else {
            $ins = $pdo->prepare("INSERT INTO departments (name, description) VALUES (?, ?)");
            $ins->execute([$name, $desc]);
            flash_set('success', "Department '{$name}' created successfully!");
            redirect('departments.php');
        }
    }
}

// Handle Delete Department
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $deptId = (int)$_GET['id'];
    
    // Check if there are employees assigned to this department
    $chkStaff = $pdo->prepare("SELECT COUNT(*) FROM users WHERE department_id = ?");
    $chkStaff->execute([$deptId]);
    $staffCount = $chkStaff->fetchColumn();

    if ($staffCount > 0) {
        flash_set('warning', "Cannot delete department: {$staffCount} employees are currently assigned to it. Reassign them first.");
    } else {
        $del = $pdo->prepare("DELETE FROM departments WHERE id = ?");
        $del->execute([$deptId]);
        flash_set('success', 'Department deleted successfully.');
    }
    redirect('departments.php');
}

// Fetch all departments with staff count
$departments = $pdo->query("
    SELECT d.*, COUNT(u.id) AS total_employees 
    FROM departments d
    LEFT JOIN users u ON u.department_id = d.id AND u.status = 'active'
    GROUP BY d.id
    ORDER BY d.name ASC
")->fetchAll();
?>

<div style="display: flex; justify-content: flex-end; margin-bottom: 20px;">
    <button type="button" class="btn btn-primary" data-open-modal="addDeptModal">
        <span>➕</span> Create Department
    </button>
</div>

<?php if (!empty($error)): ?>
    <div class="alert alert-danger">
        <span class="alert-icon">⚠️</span>
        <span><?= htmlspecialchars($error) ?></span>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <div class="card-title">🏢 Company Departments (<?= count($departments) ?>)</div>
    </div>
    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Department Name</th>
                    <th>Description</th>
                    <th>Active Employees</th>
                    <th>Created On</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($departments)): ?>
                    <tr><td colspan="5" class="table-empty">No departments configured yet.</td></tr>
                <?php else: ?>
                    <?php foreach ($departments as $dept): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($dept['name']) ?></strong></td>
                            <td style="max-width: 300px;"><?= htmlspecialchars($dept['description'] ?: 'No description provided') ?></td>
                            <td>
                                <span class="badge badge-info"><?= $dept['total_employees'] ?> members</span>
                            </td>
                            <td><?= format_date($dept['created_at']) ?></td>
                            <td>
                                <?php if ($dept['total_employees'] == 0): ?>
                                    <a href="departments.php?action=delete&id=<?= $dept['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete this department?');">
                                        🗑️ Delete
                                    </a>
                                <?php else: ?>
                                    <span style="font-size: 12px; color: #94a3b8;">In Use</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal: Add Department -->
<div class="modal-overlay" id="addDeptModal">
    <div class="modal-card">
        <div class="modal-header">
            <div class="modal-title">Create New Department</div>
            <button type="button" class="modal-close" data-close-modal>&times;</button>
        </div>
        <form action="departments.php" method="POST">
            <input type="hidden" name="action" value="add">
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label" for="dept_name">Department Name *</label>
                    <input type="text" name="name" id="dept_name" class="form-control" required placeholder="e.g. Quality Assurance">
                </div>
                <div class="form-group">
                    <label class="form-label" for="dept_desc">Description / Purpose</label>
                    <textarea name="description" id="dept_desc" class="form-control" placeholder="Brief explanation of department scope..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-close-modal>Cancel</button>
                <button type="submit" class="btn btn-primary">Create Department</button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
