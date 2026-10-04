<?php
/**
 * Leave Management & Requests
 * Office Management System (OMS)
 */

$pageTitle = 'Leave Management';
require_once __DIR__ . '/includes/header.php';

$userId = current_user_id();

// 1. Handle New Leave Application (Employee / Any user)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'apply') {
    $leaveType = $_POST['leave_type'] ?? 'casual';
    $startDate = $_POST['start_date'] ?? '';
    $endDate = $_POST['end_date'] ?? '';
    $reason = trim($_POST['reason'] ?? '');

    if (empty($startDate) || empty($endDate) || empty($reason)) {
        flash_set('danger', 'Please provide start date, end date, and reason for leave.');
    } elseif (strtotime($endDate) < strtotime($startDate)) {
        flash_set('danger', 'End date cannot be earlier than start date.');
    } else {
        // Calculate total days
        $start = new DateTime($startDate);
        $end = new DateTime($endDate);
        $diff = $start->diff($end)->days + 1; // inclusive

        $ins = $pdo->prepare("
            INSERT INTO leaves (user_id, leave_type, start_date, end_date, total_days, reason, status)
            VALUES (?, ?, ?, ?, ?, ?, 'pending')
        ");
        $ins->execute([$userId, $leaveType, $startDate, $endDate, $diff, $reason]);
        flash_set('success', "Leave request for {$diff} day(s) submitted successfully!");
        redirect('leaves.php');
    }
}

// 2. Handle Admin Status Update (Approve / Reject)
if (is_admin() && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_decision'])) {
    $leaveId = (int)$_POST['leave_id'];
    $status = $_POST['action_decision'] === 'approve' ? 'approved' : 'rejected';
    $remarks = trim($_POST['admin_remarks'] ?? '');

    $upd = $pdo->prepare("UPDATE leaves SET status = ?, admin_remarks = ? WHERE id = ?");
    $upd->execute([$status, $remarks, $leaveId]);
    flash_set('success', "Leave request #{$leaveId} was marked as {$status}.");
    redirect('leaves.php');
}

// 3. Handle Cancel Request (Employee can cancel their own pending request)
if (isset($_GET['cancel_id'])) {
    $cancelId = (int)$_GET['cancel_id'];
    $chk = $pdo->prepare("SELECT id FROM leaves WHERE id = ? AND user_id = ? AND status = 'pending'");
    $chk->execute([$cancelId, $userId]);
    if ($chk->fetch()) {
        $del = $pdo->prepare("DELETE FROM leaves WHERE id = ?");
        $del->execute([$cancelId]);
        flash_set('info', 'Leave request cancelled.');
    } else {
        flash_set('danger', 'Cannot cancel this leave request.');
    }
    redirect('leaves.php');
}

// Fetch leaves
$statusFilter = $_GET['status'] ?? '';
$params = [];

if (is_admin()) {
    $sql = "
        SELECT l.*, u.full_name, u.designation, d.name AS department_name
        FROM leaves l
        JOIN users u ON l.user_id = u.id
        LEFT JOIN departments d ON u.department_id = d.id
        WHERE 1=1
    ";
    if (!empty($statusFilter)) {
        $sql .= " AND l.status = ?";
        $params[] = $statusFilter;
    }
    $sql .= " ORDER BY l.created_at DESC";
} else {
    $sql = "
        SELECT l.*, u.full_name, u.designation, d.name AS department_name
        FROM leaves l
        JOIN users u ON l.user_id = u.id
        LEFT JOIN departments d ON u.department_id = d.id
        WHERE l.user_id = ?
    ";
    $params[] = $userId;
    if (!empty($statusFilter)) {
        $sql .= " AND l.status = ?";
        $params[] = $statusFilter;
    }
    $sql .= " ORDER BY l.created_at DESC";
}

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$leaves = $stmt->fetchAll();
?>

<div class="filter-bar">
    <form method="GET" action="leaves.php" style="display: flex; gap: 12px; align-items: center;">
        <select name="status" class="form-control" style="width: 180px;" onchange="this.form.submit();">
            <option value="">All Statuses</option>
            <option value="pending" <?= ($statusFilter === 'pending') ? 'selected' : '' ?>>Pending</option>
            <option value="approved" <?= ($statusFilter === 'approved') ? 'selected' : '' ?>>Approved</option>
            <option value="rejected" <?= ($statusFilter === 'rejected') ? 'selected' : '' ?>>Rejected</option>
        </select>
        <?php if (!empty($statusFilter)): ?>
            <a href="leaves.php" class="btn btn-outline">Clear</a>
        <?php endif; ?>
    </form>

    <button type="button" class="btn btn-primary" data-open-modal="applyLeaveModal">
        <span>🌴</span> Apply For Leave
    </button>
</div>

<div class="card">
    <div class="card-header">
        <div class="card-title">📅 Leave Applications (<?= count($leaves) ?>)</div>
    </div>
    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <?php if (is_admin()): ?>
                        <th>Employee</th>
                    <?php endif; ?>
                    <th>Leave Type</th>
                    <th>Dates</th>
                    <th>Days</th>
                    <th>Reason</th>
                    <th>Status</th>
                    <th>HR Remarks</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($leaves)): ?>
                    <tr><td colspan="<?= is_admin() ? '8' : '7' ?>" class="table-empty">No leave applications found.</td></tr>
                <?php else: ?>
                    <?php foreach ($leaves as $leave): ?>
                        <tr>
                            <?php if (is_admin()): ?>
                                <td>
                                    <strong><?= htmlspecialchars($leave['full_name']) ?></strong><br>
                                    <small class="text-muted"><?= htmlspecialchars($leave['department_name'] ?? 'General') ?></small>
                                </td>
                            <?php endif; ?>
                            <td><span class="badge badge-info"><?= ucfirst($leave['leave_type']) ?></span></td>
                            <td>
                                <?= format_date($leave['start_date']) ?> → <?= format_date($leave['end_date']) ?>
                            </td>
                            <td><strong><?= $leave['total_days'] ?></strong></td>
                            <td style="max-width: 200px;"><?= htmlspecialchars($leave['reason']) ?></td>
                            <td><?= status_badge($leave['status']) ?></td>
                            <td><?= htmlspecialchars($leave['admin_remarks'] ?: '-') ?></td>
                            <td>
                                <?php if (is_admin() && $leave['status'] === 'pending'): ?>
                                    <form method="POST" style="display: flex; gap: 6px;">
                                        <input type="hidden" name="leave_id" value="<?= $leave['id'] ?>">
                                        <button type="submit" name="action_decision" value="approve" class="btn btn-success btn-sm">Approve</button>
                                        <button type="submit" name="action_decision" value="reject" class="btn btn-danger btn-sm" onclick="return confirm('Reject this leave application?');">Reject</button>
                                    </form>
                                <?php elseif ($leave['user_id'] === $userId && $leave['status'] === 'pending'): ?>
                                    <a href="leaves.php?cancel_id=<?= $leave['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Cancel this leave request?');">Cancel</a>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal: Apply Leave -->
<div class="modal-overlay" id="applyLeaveModal">
    <div class="modal-card">
        <div class="modal-header">
            <div class="modal-title">Apply For Leave</div>
            <button type="button" class="modal-close" data-close-modal>&times;</button>
        </div>
        <form action="leaves.php" method="POST">
            <input type="hidden" name="action" value="apply">
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label" for="leave_type">Leave Type *</label>
                    <select name="leave_type" id="leave_type" class="form-control" required>
                        <option value="casual">Casual Leave</option>
                        <option value="sick">Sick Leave</option>
                        <option value="annual">Annual / Vacation Leave</option>
                        <option value="unpaid">Unpaid Leave</option>
                        <option value="other">Other</option>
                    </select>
                </div>

                <div class="form-grid" style="grid-template-columns: 1fr 1fr; gap: 12px;">
                    <div class="form-group">
                        <label class="form-label" for="start_date">Start Date *</label>
                        <input type="date" name="start_date" id="start_date" class="form-control" required value="<?= date('Y-m-d') ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="end_date">End Date *</label>
                        <input type="date" name="end_date" id="end_date" class="form-control" required value="<?= date('Y-m-d') ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="leave_reason">Reason for Leave *</label>
                    <textarea name="reason" id="leave_reason" class="form-control" required placeholder="Describe your leave reason..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-close-modal>Cancel</button>
                <button type="submit" class="btn btn-primary">Submit Application</button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
