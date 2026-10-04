<?php
/**
 * Main Dashboard
 * Office Management System (OMS)
 */

$pageTitle = 'Dashboard Overview';
require_once __DIR__ . '/includes/header.php';

$userId = current_user_id();
$today = date('Y-m-d');

// Handle Quick Attendance Clock-In / Clock-Out if submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_attendance'])) {
    $action = $_POST['action_attendance'];
    
    // Check if attendance row exists today for current user
    $chkStmt = $pdo->prepare("SELECT * FROM attendance WHERE user_id = ? AND date = ?");
    $chkStmt->execute([$userId, $today]);
    $attRecord = $chkStmt->fetch();

    if ($action === 'clock_in') {
        if (!$attRecord) {
            $currentTime = date('H:i:s');
            // Determine status based on time (e.g. after 09:15 is 'late')
            $status = (strtotime($currentTime) > strtotime('09:15:00')) ? 'late' : 'present';
            $insStmt = $pdo->prepare("INSERT INTO attendance (user_id, date, clock_in, status) VALUES (?, ?, ?, ?)");
            $insStmt->execute([$userId, $today, $currentTime, $status]);
            flash_set('success', 'Clock-in recorded successfully at ' . date('h:i A'));
        } else {
            flash_set('warning', 'You have already clocked in today.');
        }
    } elseif ($action === 'clock_out') {
        if ($attRecord && empty($attRecord['clock_out'])) {
            $currentTime = date('H:i:s');
            // Calculate total hours
            $clockInTime = strtotime($attRecord['clock_in']);
            $clockOutTime = strtotime($currentTime);
            $hours = round(($clockOutTime - $clockInTime) / 3600, 2);
            if ($hours < 0) $hours = 0;

            $updStmt = $pdo->prepare("UPDATE attendance SET clock_out = ?, total_hours = ? WHERE id = ?");
            $updStmt->execute([$currentTime, $hours, $attRecord['id']]);
            flash_set('success', 'Clock-out recorded successfully at ' . date('h:i A') . " (Total: {$hours} hrs)");
        } else {
            flash_set('warning', 'Clock-out was already logged or no clock-in found.');
        }
    }
    redirect('index.php');
}

// Handle Quick Leave Approval / Rejection from Admin Dashboard
if (is_admin() && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_leave'])) {
    $leaveId = (int)$_POST['leave_id'];
    $leaveStatus = $_POST['action_leave'] === 'approve' ? 'approved' : 'rejected';
    $remarks = trim($_POST['remarks'] ?? '');

    $stmt = $pdo->prepare("UPDATE leaves SET status = ?, admin_remarks = ? WHERE id = ?");
    $stmt->execute([$leaveStatus, $remarks, $leaveId]);
    flash_set('success', 'Leave request #' . $leaveId . ' has been ' . $leaveStatus . '.');
    redirect('index.php');
}

// Fetch Metrics Data
if (is_admin()) {
    // 1. Total Employees
    $totalEmployees = $pdo->query("SELECT COUNT(*) FROM users WHERE status = 'active'")->fetchColumn();

    // 2. Present Today
    $presentTodayStmt = $pdo->prepare("SELECT COUNT(*) FROM attendance WHERE date = ?");
    $presentTodayStmt->execute([$today]);
    $presentToday = $presentTodayStmt->fetchColumn();

    // 3. Pending Leaves
    $pendingLeaves = $pdo->query("SELECT COUNT(*) FROM leaves WHERE status = 'pending'")->fetchColumn();

    // 4. Active Tasks
    $activeTasks = $pdo->query("SELECT COUNT(*) FROM tasks WHERE status IN ('pending', 'in_progress')")->fetchColumn();

    // Pending Leaves List
    $pendingLeavesList = $pdo->query("
        SELECT l.*, u.full_name, u.designation, d.name AS department_name 
        FROM leaves l
        JOIN users u ON l.user_id = u.id
        LEFT JOIN departments d ON u.department_id = d.id
        WHERE l.status = 'pending'
        ORDER BY l.created_at ASC LIMIT 5
    ")->fetchAll();

    // Recent Active Tasks
    $recentTasks = $pdo->query("
        SELECT t.*, u.full_name AS assignee_name 
        FROM tasks t
        JOIN users u ON t.assigned_to = u.id
        ORDER BY t.due_date ASC LIMIT 5
    ")->fetchAll();

} else {
    // Employee Specific Metrics
    // 1. My attendance this month
    $startMonth = date('Y-m-01');
    $myAttStmt = $pdo->prepare("SELECT COUNT(*) FROM attendance WHERE user_id = ? AND date >= ?");
    $myAttStmt->execute([$userId, $startMonth]);
    $myMonthlyAttendance = $myAttStmt->fetchColumn();

    // 2. My Pending Tasks
    $myTasksStmt = $pdo->prepare("SELECT COUNT(*) FROM tasks WHERE assigned_to = ? AND status IN ('pending', 'in_progress')");
    $myTasksStmt->execute([$userId]);
    $myPendingTasks = $myTasksStmt->fetchColumn();

    // 3. My Leaves (Approved this year)
    $myLeavesStmt = $pdo->prepare("SELECT COUNT(*) FROM leaves WHERE user_id = ? AND status = 'approved' AND start_date >= ? AND start_date <= ?");
    $myLeavesStmt->execute([$userId, date('Y-01-01'), date('Y-12-31')]);
    $myApprovedLeaves = $myLeavesStmt->fetchColumn();

    // 4. Completed Tasks
    $myCompletedTasksStmt = $pdo->prepare("SELECT COUNT(*) FROM tasks WHERE assigned_to = ? AND status = 'completed'");
    $myCompletedTasksStmt->execute([$userId]);
    $myCompletedTasks = $myCompletedTasksStmt->fetchColumn();

    // My Recent Tasks
    $myTasksListStmt = $pdo->prepare("
        SELECT t.*, u.full_name AS assigner_name 
        FROM tasks t
        JOIN users u ON t.assigned_by = u.id
        WHERE t.assigned_to = ?
        ORDER BY t.status = 'completed' ASC, t.due_date ASC LIMIT 5
    ");
    $myTasksListStmt->execute([$userId]);
    $myTasksList = $myTasksListStmt->fetchAll();
}

// Current User Today's Attendance State
$todayAttStmt = $pdo->prepare("SELECT * FROM attendance WHERE user_id = ? AND date = ?");
$todayAttStmt->execute([$userId, $today]);
$myTodayAttendance = $todayAttStmt->fetch();

// Announcements
$noticesStmt = $pdo->query("
    SELECT n.*, u.full_name AS author_name 
    FROM notices n
    JOIN users u ON n.posted_by = u.id
    ORDER BY n.created_at DESC LIMIT 4
");
$recentNotices = $noticesStmt->fetchAll();
?>

<!-- Clock-in/Clock-out banner -->
<div class="clock-widget">
    <div class="clock-info">
        <h3>Welcome, <?= htmlspecialchars($user['full_name']) ?>!</h3>
        <p>Today is <strong><?= date('l, F j, Y') ?></strong></p>
    </div>
    <div style="display: flex; align-items: center; gap: 20px; flex-wrap: wrap;">
        <div>
            <div style="font-size: 11px; text-transform: uppercase; color: #94a3b8; font-weight: 600;">Status Today</div>
            <div style="margin-top: 4px;">
                <?php if (!$myTodayAttendance): ?>
                    <span class="badge badge-secondary">Not Checked In</span>
                <?php elseif (!empty($myTodayAttendance['clock_out'])): ?>
                    <span class="badge badge-success">Completed (<?= $myTodayAttendance['total_hours'] ?> hrs)</span>
                <?php else: ?>
                    <span class="badge badge-warning">Clocked In at <?= format_time($myTodayAttendance['clock_in']) ?></span>
                <?php endif; ?>
            </div>
        </div>

        <form method="POST" style="display: flex; gap: 10px;">
            <?php if (!$myTodayAttendance): ?>
                <button type="submit" name="action_attendance" value="clock_in" class="btn btn-success">
                    ⏰ Clock In Now
                </button>
            <?php elseif (empty($myTodayAttendance['clock_out'])): ?>
                <button type="submit" name="action_attendance" value="clock_out" class="btn btn-danger" onclick="return confirm('Confirm clock out for today?');">
                    🏁 Clock Out
                </button>
            <?php else: ?>
                <button type="button" class="btn btn-secondary" disabled>
                    ✅ Day Finished
                </button>
            <?php endif; ?>
        </form>
    </div>
</div>

<!-- KPI Cards -->
<div class="stats-grid">
    <?php if (is_admin()): ?>
        <div class="stat-card">
            <div class="stat-info">
                <h4>Total Staff</h4>
                <div class="stat-number"><?= $totalEmployees ?></div>
                <div class="stat-subtext">Active team members</div>
            </div>
            <div class="stat-icon blue">👥</div>
        </div>
        <div class="stat-card">
            <div class="stat-info">
                <h4>Present Today</h4>
                <div class="stat-number"><?= $presentToday ?></div>
                <div class="stat-subtext"><?= ($totalEmployees > 0) ? round(($presentToday / $totalEmployees) * 100) : 0 ?>% attendance rate</div>
            </div>
            <div class="stat-icon green">⏱️</div>
        </div>
        <div class="stat-card">
            <div class="stat-info">
                <h4>Pending Leaves</h4>
                <div class="stat-number"><?= $pendingLeaves ?></div>
                <div class="stat-subtext">Awaiting your approval</div>
            </div>
            <div class="stat-icon amber">📅</div>
        </div>
        <div class="stat-card">
            <div class="stat-info">
                <h4>Active Tasks</h4>
                <div class="stat-number"><?= $activeTasks ?></div>
                <div class="stat-subtext">Assigned across projects</div>
            </div>
            <div class="stat-icon purple">📌</div>
        </div>
    <?php else: ?>
        <div class="stat-card">
            <div class="stat-info">
                <h4>Days Present</h4>
                <div class="stat-number"><?= $myMonthlyAttendance ?></div>
                <div class="stat-subtext">In <?= date('F') ?></div>
            </div>
            <div class="stat-icon green">⏱️</div>
        </div>
        <div class="stat-card">
            <div class="stat-info">
                <h4>Assigned Tasks</h4>
                <div class="stat-number"><?= $myPendingTasks ?></div>
                <div class="stat-subtext">Pending / in-progress</div>
            </div>
            <div class="stat-icon purple">📝</div>
        </div>
        <div class="stat-card">
            <div class="stat-info">
                <h4>Completed Tasks</h4>
                <div class="stat-number"><?= $myCompletedTasks ?></div>
                <div class="stat-subtext">Successfully finished</div>
            </div>
            <div class="stat-icon blue">✅</div>
        </div>
        <div class="stat-card">
            <div class="stat-info">
                <h4>Leaves Taken</h4>
                <div class="stat-number"><?= $myApprovedLeaves ?></div>
                <div class="stat-subtext">Approved in <?= date('Y') ?></div>
            </div>
            <div class="stat-icon amber">🌴</div>
        </div>
    <?php endif; ?>
</div>

<!-- Two-Column Layout -->
<div class="dashboard-grid">
    <!-- Main Left Column -->
    <div>
        <?php if (is_admin()): ?>
            <!-- Admin: Pending Leave Approvals -->
            <div class="card">
                <div class="card-header">
                    <div class="card-title">⏳ Pending Leave Requests (<?= count($pendingLeavesList) ?>)</div>
                    <a href="leaves.php" class="btn btn-secondary btn-sm">View All Leaves</a>
                </div>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Employee</th>
                                <th>Type</th>
                                <th>Duration</th>
                                <th>Reason</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($pendingLeavesList)): ?>
                                <tr>
                                    <td colspan="5" class="table-empty">No pending leave requests at this time.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($pendingLeavesList as $lv): ?>
                                    <tr>
                                        <td>
                                            <strong><?= htmlspecialchars($lv['full_name']) ?></strong><br>
                                            <small class="text-muted"><?= htmlspecialchars($lv['department_name'] ?? 'General') ?></small>
                                        </td>
                                        <td><span class="badge badge-info"><?= ucfirst($lv['leave_type']) ?></span></td>
                                        <td>
                                            <?= format_date($lv['start_date']) ?> - <?= format_date($lv['end_date']) ?><br>
                                            <small><?= $lv['total_days'] ?> day(s)</small>
                                        </td>
                                        <td style="max-width: 220px;"><?= htmlspecialchars($lv['reason']) ?></td>
                                        <td>
                                            <form method="POST" style="display: flex; gap: 6px;">
                                                <input type="hidden" name="leave_id" value="<?= $lv['id'] ?>">
                                                <button type="submit" name="action_leave" value="approve" class="btn btn-success btn-sm" title="Approve">✓ Approve</button>
                                                <button type="submit" name="action_leave" value="reject" class="btn btn-danger btn-sm" title="Reject" onclick="return confirm('Reject this leave application?');">✗ Reject</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Admin: Recent Tasks -->
            <div class="card">
                <div class="card-header">
                    <div class="card-title">📋 Priority Tasks</div>
                    <a href="tasks.php" class="btn btn-secondary btn-sm">Manage All Tasks</a>
                </div>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Task</th>
                                <th>Assigned To</th>
                                <th>Priority</th>
                                <th>Due Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($recentTasks)): ?>
                                <tr><td colspan="5" class="table-empty">No tasks found.</td></tr>
                            <?php else: ?>
                                <?php foreach ($recentTasks as $task): ?>
                                    <tr>
                                        <td><strong><?= htmlspecialchars($task['title']) ?></strong></td>
                                        <td><?= htmlspecialchars($task['assignee_name']) ?></td>
                                        <td><?= priority_badge($task['priority']) ?></td>
                                        <td><?= format_date($task['due_date']) ?></td>
                                        <td><?= status_badge($task['status']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        <?php else: ?>
            <!-- Employee: My Tasks -->
            <div class="card">
                <div class="card-header">
                    <div class="card-title">📝 My Assigned Tasks</div>
                    <a href="tasks.php" class="btn btn-secondary btn-sm">View Full Taskboard</a>
                </div>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Priority</th>
                                <th>Due Date</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($myTasksList)): ?>
                                <tr><td colspan="5" class="table-empty">You have no pending tasks. Great job!</td></tr>
                            <?php else: ?>
                                <?php foreach ($myTasksList as $t): ?>
                                    <tr>
                                        <td>
                                            <strong><?= htmlspecialchars($t['title']) ?></strong>
                                            <?php if (!empty($t['description'])): ?>
                                                <div style="font-size: 12px; color: #64748b; margin-top: 2px;">
                                                    <?= htmlspecialchars($t['description']) ?>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= priority_badge($t['priority']) ?></td>
                                        <td><?= format_date($t['due_date']) ?></td>
                                        <td><?= status_badge($t['status']) ?></td>
                                        <td>
                                            <?php if ($t['status'] !== 'completed'): ?>
                                                <form action="tasks.php" method="POST" style="display:inline;">
                                                    <input type="hidden" name="task_id" value="<?= $t['id'] ?>">
                                                    <input type="hidden" name="update_status" value="completed">
                                                    <button type="submit" class="btn btn-success btn-sm">Mark Done</button>
                                                </form>
                                            <?php else: ?>
                                                <span class="badge badge-success">Finished</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Right Sidebar Column: Notice Board & Quick Actions -->
    <div>
        <!-- Notice Board Widget -->
        <div class="card">
            <div class="card-header">
                <div class="card-title">📢 Notice Board</div>
                <a href="notices.php" class="btn btn-secondary btn-sm">All</a>
            </div>
            <div class="card-body">
                <?php if (empty($recentNotices)): ?>
                    <p style="color: #64748b; font-style: italic;">No announcements currently posted.</p>
                <?php else: ?>
                    <div class="notices-list">
                        <?php foreach ($recentNotices as $notice): ?>
                            <div class="notice-card">
                                <div class="notice-header">
                                    <span class="notice-title"><?= htmlspecialchars($notice['title']) ?></span>
                                    <span class="notice-meta"><?= format_date($notice['created_at']) ?></span>
                                </div>
                                <div class="notice-content">
                                    <?= nl2br(htmlspecialchars($notice['content'])) ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Quick Links Card -->
        <div class="card">
            <div class="card-header">
                <div class="card-title">⚡ Quick Shortcuts</div>
            </div>
            <div class="card-body" style="display: flex; flex-direction: column; gap: 10px;">
                <?php if (is_admin()): ?>
                    <a href="employee-add.php" class="btn btn-outline" style="justify-content: flex-start;">
                        <span>➕</span> Add New Employee
                    </a>
                    <a href="tasks.php" class="btn btn-outline" style="justify-content: flex-start;">
                        <span>📌</span> Assign New Task
                    </a>
                    <a href="notices.php" class="btn btn-outline" style="justify-content: flex-start;">
                        <span>📢</span> Broadcast Announcement
                    </a>
                <?php else: ?>
                    <a href="leaves.php" class="btn btn-outline" style="justify-content: flex-start;">
                        <span>🌴</span> Apply For Leave
                    </a>
                    <a href="attendance.php" class="btn btn-outline" style="justify-content: flex-start;">
                        <span>⏱️</span> View My Attendance Log
                    </a>
                <?php endif; ?>
                <a href="profile.php" class="btn btn-outline" style="justify-content: flex-start;">
                    <span>🔒</span> Change My Password
                </a>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
