<?php
/**
 * Task Management & Tracking
 * Office Management System (OMS)
 */

$pageTitle = 'Task Management';
require_once __DIR__ . '/includes/header.php';

$userId = current_user_id();

// 1. Assign New Task (Admin Only)
if (is_admin() && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_task') {
    $title = trim($_POST['title'] ?? '');
    $desc = trim($_POST['description'] ?? '');
    $assignedTo = (int)$_POST['assigned_to'];
    $priority = $_POST['priority'] ?? 'medium';
    $dueDate = $_POST['due_date'] ?? date('Y-m-d');

    if (empty($title) || empty($assignedTo) || empty($dueDate)) {
        flash_set('danger', 'Please specify task title, assignee, and due date.');
    } else {
        $ins = $pdo->prepare("
            INSERT INTO tasks (title, description, assigned_to, assigned_by, priority, due_date, status)
            VALUES (?, ?, ?, ?, ?, ?, 'pending')
        ");
        $ins->execute([$title, $desc, $assignedTo, $userId, $priority, $dueDate]);
        flash_set('success', 'Task successfully created and assigned!');
        redirect('tasks.php');
    }
}

// 2. Update Task Status (Employee or Admin)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status']) && isset($_POST['task_id'])) {
    $taskId = (int)$_POST['task_id'];
    $newStatus = $_POST['update_status'];

    if (is_admin()) {
        $upd = $pdo->prepare("UPDATE tasks SET status = ? WHERE id = ?");
        $upd->execute([$newStatus, $taskId]);
        flash_set('success', 'Task status updated.');
    } else {
        $upd = $pdo->prepare("UPDATE tasks SET status = ? WHERE id = ? AND assigned_to = ?");
        $upd->execute([$newStatus, $taskId, $userId]);
        flash_set('success', 'Task progress updated.');
    }
    redirect('tasks.php');
}

// 3. Delete Task (Admin Only)
if (is_admin() && isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $taskId = (int)$_GET['id'];
    $del = $pdo->prepare("DELETE FROM tasks WHERE id = ?");
    $del->execute([$taskId]);
    flash_set('success', 'Task deleted successfully.');
    redirect('tasks.php');
}

// Filter tasks
$statusFilter = $_GET['status'] ?? '';
$priorityFilter = $_GET['priority'] ?? '';
$params = [];

if (is_admin()) {
    $sql = "
        SELECT t.*, u_to.full_name AS assignee_name, u_by.full_name AS assigner_name, d.name AS dept_name
        FROM tasks t
        JOIN users u_to ON t.assigned_to = u_to.id
        JOIN users u_by ON t.assigned_by = u_by.id
        LEFT JOIN departments d ON u_to.department_id = d.id
        WHERE 1=1
    ";
} else {
    $sql = "
        SELECT t.*, u_to.full_name AS assignee_name, u_by.full_name AS assigner_name, d.name AS dept_name
        FROM tasks t
        JOIN users u_to ON t.assigned_to = u_to.id
        JOIN users u_by ON t.assigned_by = u_by.id
        LEFT JOIN departments d ON u_to.department_id = d.id
        WHERE t.assigned_to = ?
    ";
    $params[] = $userId;
}

if (!empty($statusFilter)) {
    $sql .= " AND t.status = ?";
    $params[] = $statusFilter;
}
if (!empty($priorityFilter)) {
    $sql .= " AND t.priority = ?";
    $params[] = $priorityFilter;
}

$sql .= " ORDER BY (CASE WHEN t.status = 'completed' THEN 1 ELSE 0 END) ASC, t.due_date ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$tasks = $stmt->fetchAll();

// List of employees for admin assignment dropdown
$employees = is_admin() ? $pdo->query("SELECT id, full_name FROM users WHERE status = 'active' ORDER BY full_name ASC")->fetchAll() : [];
?>

<div class="filter-bar">
    <form method="GET" action="tasks.php" style="display: flex; gap: 12px; align-items: center; flex-wrap: wrap;">
        <select name="status" class="form-control" style="width: 160px;" onchange="this.form.submit();">
            <option value="">All Statuses</option>
            <option value="pending" <?= ($statusFilter === 'pending') ? 'selected' : '' ?>>Pending</option>
            <option value="in_progress" <?= ($statusFilter === 'in_progress') ? 'selected' : '' ?>>In Progress</option>
            <option value="completed" <?= ($statusFilter === 'completed') ? 'selected' : '' ?>>Completed</option>
            <option value="on_hold" <?= ($statusFilter === 'on_hold') ? 'selected' : '' ?>>On Hold</option>
        </select>

        <select name="priority" class="form-control" style="width: 160px;" onchange="this.form.submit();">
            <option value="">All Priorities</option>
            <option value="low" <?= ($priorityFilter === 'low') ? 'selected' : '' ?>>Low</option>
            <option value="medium" <?= ($priorityFilter === 'medium') ? 'selected' : '' ?>>Medium</option>
            <option value="high" <?= ($priorityFilter === 'high') ? 'selected' : '' ?>>High</option>
            <option value="urgent" <?= ($priorityFilter === 'urgent') ? 'selected' : '' ?>>Urgent</option>
        </select>

        <?php if (!empty($statusFilter) || !empty($priorityFilter)): ?>
            <a href="tasks.php" class="btn btn-outline">Clear</a>
        <?php endif; ?>
    </form>

    <?php if (is_admin()): ?>
        <button type="button" class="btn btn-primary" data-open-modal="createTaskModal">
            <span>➕</span> Assign New Task
        </button>
    <?php endif; ?>
</div>

<div class="card">
    <div class="card-header">
        <div class="card-title">✅ Task Board (<?= count($tasks) ?>)</div>
    </div>
    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Task Name & Details</th>
                    <?php if (is_admin()): ?>
                        <th>Assigned To</th>
                    <?php endif; ?>
                    <th>Priority</th>
                    <th>Due Date</th>
                    <th>Status</th>
                    <th>Update Status</th>
                    <?php if (is_admin()): ?>
                        <th>Action</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($tasks)): ?>
                    <tr><td colspan="<?= is_admin() ? '7' : '5' ?>" class="table-empty">No tasks found in this view.</td></tr>
                <?php else: ?>
                    <?php foreach ($tasks as $task): 
                        $isOverdue = ($task['status'] !== 'completed') && (strtotime($task['due_date']) < strtotime(date('Y-m-d')));
                    ?>
                        <tr>
                            <td style="max-width: 320px;">
                                <div style="font-weight: 700; font-size: 14px;"><?= htmlspecialchars($task['title']) ?></div>
                                <?php if (!empty($task['description'])): ?>
                                    <div style="color: #64748b; font-size: 12.5px; margin-top: 4px;"><?= nl2br(htmlspecialchars($task['description'])) ?></div>
                                <?php endif; ?>
                            </td>
                            <?php if (is_admin()): ?>
                                <td>
                                    <strong><?= htmlspecialchars($task['assignee_name']) ?></strong><br>
                                    <small class="text-muted"><?= htmlspecialchars($task['dept_name'] ?? 'General') ?></small>
                                </td>
                            <?php endif; ?>
                            <td><?= priority_badge($task['priority']) ?></td>
                            <td>
                                <div><?= format_date($task['due_date']) ?></div>
                                <?php if ($isOverdue): ?>
                                    <span class="badge badge-danger" style="margin-top: 4px;">Overdue</span>
                                <?php endif; ?>
                            </td>
                            <td><?= status_badge($task['status']) ?></td>
                            <td>
                                <form method="POST" style="display: flex; gap: 6px; align-items: center;">
                                    <input type="hidden" name="task_id" value="<?= $task['id'] ?>">
                                    <select name="update_status" class="form-control" style="width: 130px; font-size: 12px; padding: 5px 8px;" onchange="this.form.submit();">
                                        <option value="pending" <?= ($task['status'] === 'pending') ? 'selected' : '' ?>>Pending</option>
                                        <option value="in_progress" <?= ($task['status'] === 'in_progress') ? 'selected' : '' ?>>In Progress</option>
                                        <option value="completed" <?= ($task['status'] === 'completed') ? 'selected' : '' ?>>Completed</option>
                                        <option value="on_hold" <?= ($task['status'] === 'on_hold') ? 'selected' : '' ?>>On Hold</option>
                                    </select>
                                </form>
                            </td>
                            <?php if (is_admin()): ?>
                                <td>
                                    <a href="tasks.php?action=delete&id=<?= $task['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete this task?');">
                                        🗑️
                                    </a>
                                </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal: Assign Task (Admin Only) -->
<?php if (is_admin()): ?>
<div class="modal-overlay" id="createTaskModal">
    <div class="modal-card">
        <div class="modal-header">
            <div class="modal-title">Assign New Task</div>
            <button type="button" class="modal-close" data-close-modal>&times;</button>
        </div>
        <form action="tasks.php" method="POST">
            <input type="hidden" name="action" value="create_task">
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label" for="task_title">Task Title *</label>
                    <input type="text" name="title" id="task_title" class="form-control" required placeholder="e.g. Audit security logs">
                </div>

                <div class="form-group">
                    <label class="form-label" for="task_assignee">Assign To Employee *</label>
                    <select name="assigned_to" id="task_assignee" class="form-control" required>
                        <option value="">Select Employee</option>
                        <?php foreach ($employees as $emp): ?>
                            <option value="<?= $emp['id'] ?>"><?= htmlspecialchars($emp['full_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-grid" style="grid-template-columns: 1fr 1fr; gap: 12px;">
                    <div class="form-group">
                        <label class="form-label" for="task_priority">Priority</label>
                        <select name="priority" id="task_priority" class="form-control">
                            <option value="low">Low</option>
                            <option value="medium" selected>Medium</option>
                            <option value="high">High</option>
                            <option value="urgent">Urgent</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="task_due_date">Due Date *</label>
                        <input type="date" name="due_date" id="task_due_date" class="form-control" required value="<?= date('Y-m-d', strtotime('+3 days')) ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="task_desc">Description / Instructions</label>
                    <textarea name="description" id="task_desc" class="form-control" placeholder="Provide task deliverables and expectations..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-close-modal>Cancel</button>
                <button type="submit" class="btn btn-primary">Assign Task</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
