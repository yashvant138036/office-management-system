<?php
/**
 * Attendance Tracking & Records
 * Office Management System (OMS)
 */

$pageTitle = 'Attendance Management';
require_once __DIR__ . '/includes/header.php';

$userId = current_user_id();
$today = date('Y-m-d');

// 1. Clock In / Out Action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_attendance'])) {
    $action = $_POST['action_attendance'];
    $targetUserId = (is_admin() && !empty($_POST['manual_user_id'])) ? (int)$_POST['manual_user_id'] : $userId;
    $targetDate = (is_admin() && !empty($_POST['manual_date'])) ? $_POST['manual_date'] : $today;

    $chkStmt = $pdo->prepare("SELECT * FROM attendance WHERE user_id = ? AND date = ?");
    $chkStmt->execute([$targetUserId, $targetDate]);
    $attRecord = $chkStmt->fetch();

    if ($action === 'clock_in') {
        if (!$attRecord) {
            $clockTime = !empty($_POST['manual_clock_in']) ? $_POST['manual_clock_in'] : date('H:i:s');
            $status = (strtotime($clockTime) > strtotime('09:15:00')) ? 'late' : 'present';
            $insStmt = $pdo->prepare("INSERT INTO attendance (user_id, date, clock_in, status, notes) VALUES (?, ?, ?, ?, ?)");
            $insStmt->execute([$targetUserId, $targetDate, $clockTime, $status, trim($_POST['notes'] ?? '')]);
            flash_set('success', 'Clock-in registered successfully.');
        } else {
            flash_set('warning', 'Clock-in entry already exists for this date.');
        }
    } elseif ($action === 'clock_out') {
        if ($attRecord && empty($attRecord['clock_out'])) {
            $clockOutTime = !empty($_POST['manual_clock_out']) ? $_POST['manual_clock_out'] : date('H:i:s');
            $clockInTime = strtotime($attRecord['clock_in']);
            $outTime = strtotime($clockOutTime);
            $hours = round(($outTime - $clockInTime) / 3600, 2);
            if ($hours < 0) $hours = 0;

            $updStmt = $pdo->prepare("UPDATE attendance SET clock_out = ?, total_hours = ? WHERE id = ?");
            $updStmt->execute([$clockOutTime, $hours, $attRecord['id']]);
            flash_set('success', "Clock-out logged successfully ({$hours} hours).");
        } else {
            flash_set('warning', 'Clock-out was already registered or record not found.');
        }
    }
    redirect('attendance.php');
}

// Check current user's today attendance status
$todayStmt = $pdo->prepare("SELECT * FROM attendance WHERE user_id = ? AND date = ?");
$todayStmt->execute([$userId, $today]);
$myTodayAtt = $todayStmt->fetch();

// Filtering for attendance table
$filterDate = $_GET['date'] ?? '';
$filterUser = $_GET['user_id'] ?? '';
$filterMonth = $_GET['month'] ?? date('Y-m');

$params = [];
if (is_admin()) {
    $sql = "
        SELECT a.*, u.full_name, u.designation, d.name AS department_name
        FROM attendance a
        JOIN users u ON a.user_id = u.id
        LEFT JOIN departments d ON u.department_id = d.id
        WHERE 1=1
    ";
    if (!empty($filterDate)) {
        $sql .= " AND a.date = ?";
        $params[] = $filterDate;
    } elseif (!empty($filterMonth)) {
        $sql .= " AND substr(a.date, 1, 7) = ?";
        $params[] = $filterMonth;
    }
    if (!empty($filterUser)) {
        $sql .= " AND a.user_id = ?";
        $params[] = $filterUser;
    }
    $sql .= " ORDER BY a.date DESC, a.clock_in DESC";
} else {
    // Employee sees only their own history
    $sql = "
        SELECT a.*, u.full_name, u.designation, d.name AS department_name
        FROM attendance a
        JOIN users u ON a.user_id = u.id
        LEFT JOIN departments d ON u.department_id = d.id
        WHERE a.user_id = ?
    ";
    $params[] = $userId;
    if (!empty($filterMonth)) {
        $sql .= " AND substr(a.date, 1, 7) = ?";
        $params[] = $filterMonth;
    }
    $sql .= " ORDER BY a.date DESC";
}

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$attendanceLogs = $stmt->fetchAll();

// Users list for admin filter dropdown
$allStaff = is_admin() ? $pdo->query("SELECT id, full_name FROM users WHERE status = 'active' ORDER BY full_name ASC")->fetchAll() : [];
?>

<!-- Clock In/Out Hero Widget -->
<div class="clock-widget">
    <div class="clock-info">
        <h3>Today's Attendance (<?= date('M d, Y') ?>)</h3>
        <p>Office hours: <strong>09:00 AM – 06:00 PM</strong> (Grace period up to 09:15 AM)</p>
    </div>
    <div style="display: flex; align-items: center; gap: 16px; flex-wrap: wrap;">
        <div>
            <div style="font-size: 11px; text-transform: uppercase; color: #94a3b8; font-weight: 600;">Status Today</div>
            <div style="margin-top: 4px;">
                <?php if (!$myTodayAtt): ?>
                    <span class="badge badge-secondary">Not Checked In</span>
                <?php elseif (!empty($myTodayAtt['clock_out'])): ?>
                    <span class="badge badge-success">Completed (<?= $myTodayAtt['total_hours'] ?> hrs)</span>
                <?php else: ?>
                    <span class="badge badge-warning">Clocked In at <?= format_time($myTodayAtt['clock_in']) ?></span>
                <?php endif; ?>
            </div>
        </div>

        <form method="POST" style="display: flex; gap: 10px;">
            <?php if (!$myTodayAtt): ?>
                <button type="submit" name="action_attendance" value="clock_in" class="btn btn-success">
                    ⏰ Clock In
                </button>
            <?php elseif (empty($myTodayAtt['clock_out'])): ?>
                <button type="submit" name="action_attendance" value="clock_out" class="btn btn-danger" onclick="return confirm('Confirm clock out?');">
                    🏁 Clock Out
                </button>
            <?php else: ?>
                <button type="button" class="btn btn-secondary" disabled>
                    ✅ Logged for Today
                </button>
            <?php endif; ?>
        </form>
    </div>
</div>

<!-- Filters Bar -->
<div class="filter-bar">
    <form method="GET" action="attendance.php" style="display: flex; gap: 12px; flex-wrap: wrap; align-items: center;">
        <div>
            <label class="form-label" style="font-size: 11px;">Select Month</label>
            <input type="month" name="month" class="form-control" value="<?= htmlspecialchars($filterMonth) ?>" onchange="this.form.submit();">
        </div>

        <?php if (is_admin()): ?>
            <div>
                <label class="form-label" style="font-size: 11px;">Filter By Date</label>
                <input type="date" name="date" class="form-control" value="<?= htmlspecialchars($filterDate) ?>" onchange="this.form.submit();">
            </div>

            <div>
                <label class="form-label" style="font-size: 11px;">Filter By Employee</label>
                <select name="user_id" class="form-control" onchange="this.form.submit();">
                    <option value="">All Employees</option>
                    <?php foreach ($allStaff as $st): ?>
                        <option value="<?= $st['id'] ?>" <?= ($filterUser == $st['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($st['full_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        <?php endif; ?>

        <div style="align-self: flex-end;">
            <a href="attendance.php" class="btn btn-outline">Reset Filter</a>
        </div>
    </form>

    <?php if (is_admin()): ?>
        <button type="button" class="btn btn-primary" data-open-modal="manualAttModal">
            <span>➕</span> Manual Attendance Entry
        </button>
    <?php endif; ?>
</div>

<!-- Attendance Records Table -->
<div class="card">
    <div class="card-header">
        <div class="card-title">⏱️ Attendance Records (<?= count($attendanceLogs) ?> entries)</div>
    </div>
    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <?php if (is_admin()): ?>
                        <th>Employee</th>
                        <th>Department</th>
                    <?php endif; ?>
                    <th>Date</th>
                    <th>Clock In</th>
                    <th>Clock Out</th>
                    <th>Work Duration</th>
                    <th>Status</th>
                    <th>Notes</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($attendanceLogs)): ?>
                    <tr>
                        <td colspan="<?= is_admin() ? '8' : '6' ?>" class="table-empty">
                            No attendance records found for this period.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($attendanceLogs as $log): ?>
                        <tr>
                            <?php if (is_admin()): ?>
                                <td><strong><?= htmlspecialchars($log['full_name']) ?></strong></td>
                                <td><?= htmlspecialchars($log['department_name'] ?? 'General') ?></td>
                            <?php endif; ?>
                            <td><?= format_date($log['date']) ?></td>
                            <td><?= format_time($log['clock_in']) ?></td>
                            <td><?= !empty($log['clock_out']) ? format_time($log['clock_out']) : '<span class="text-muted">Active</span>' ?></td>
                            <td>
                                <?php if (!empty($log['total_hours']) && $log['total_hours'] > 0): ?>
                                    <strong><?= $log['total_hours'] ?> hrs</strong>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td><?= status_badge($log['status']) ?></td>
                            <td><?= htmlspecialchars($log['notes'] ?? '-') ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal: Manual Attendance Entry (Admin Only) -->
<?php if (is_admin()): ?>
<div class="modal-overlay" id="manualAttModal">
    <div class="modal-card">
        <div class="modal-header">
            <div class="modal-title">Manual Attendance Entry</div>
            <button type="button" class="modal-close" data-close-modal>&times;</button>
        </div>
        <form action="attendance.php" method="POST">
            <input type="hidden" name="action_attendance" value="clock_in">
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label" for="manual_user_id">Employee *</label>
                    <select name="manual_user_id" id="manual_user_id" class="form-control" required>
                        <option value="">Select Employee</option>
                        <?php foreach ($allStaff as $st): ?>
                            <option value="<?= $st['id'] ?>"><?= htmlspecialchars($st['full_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label" for="manual_date">Date *</label>
                    <input type="date" name="manual_date" id="manual_date" class="form-control" required value="<?= date('Y-m-d') ?>">
                </div>
                <div class="form-group">
                    <label class="form-label" for="manual_clock_in">Clock In Time *</label>
                    <input type="time" name="manual_clock_in" id="manual_clock_in" class="form-control" required value="09:00">
                </div>
                <div class="form-group">
                    <label class="form-label" for="notes">Notes / Reason for Manual Entry</label>
                    <input type="text" name="notes" id="notes" class="form-control" placeholder="e.g. Regularized by HR">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-close-modal>Cancel</button>
                <button type="submit" class="btn btn-primary">Save Attendance</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
