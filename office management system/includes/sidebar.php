<?php
/**
 * Sidebar Navigation Component
 * Office Management System (OMS)
 */

$currentPage = basename($_SERVER['PHP_SELF']);
$user = current_user();
?>
<aside class="sidebar">
    <div class="sidebar-header">
        <a href="index.php" class="brand-logo">
            <div class="brand-icon">🏢</div>
            <span>OfficeDesk</span>
            <span class="brand-tag"><?= strtoupper(htmlspecialchars($user['role'] ?? 'OMS')) ?></span>
        </a>
    </div>

    <nav class="sidebar-nav">
        <div class="nav-section-title">Main Menu</div>
        <a href="index.php" class="nav-link <?= ($currentPage === 'index.php') ? 'active' : '' ?>">
            <span class="icon">📊</span>
            <span>Dashboard</span>
        </a>

        <?php if (is_admin()): ?>
            <div class="nav-section-title">Administration</div>
            <a href="employees.php" class="nav-link <?= in_array($currentPage, ['employees.php', 'employee-add.php', 'employee-edit.php']) ? 'active' : '' ?>">
                <span class="icon">👥</span>
                <span>Employees</span>
            </a>
            <a href="departments.php" class="nav-link <?= ($currentPage === 'departments.php') ? 'active' : '' ?>">
                <span class="icon">🏢</span>
                <span>Departments</span>
            </a>
        <?php endif; ?>

        <div class="nav-section-title">Operations</div>
        <a href="attendance.php" class="nav-link <?= ($currentPage === 'attendance.php') ? 'active' : '' ?>">
            <span class="icon">⏱️</span>
            <span>Attendance</span>
        </a>
        <a href="leaves.php" class="nav-link <?= ($currentPage === 'leaves.php') ? 'active' : '' ?>">
            <span class="icon">📅</span>
            <span>Leaves</span>
        </a>
        <a href="tasks.php" class="nav-link <?= ($currentPage === 'tasks.php') ? 'active' : '' ?>">
            <span class="icon">✅</span>
            <span>Tasks</span>
        </a>
        <a href="notices.php" class="nav-link <?= ($currentPage === 'notices.php') ? 'active' : '' ?>">
            <span class="icon">📢</span>
            <span>Notice Board</span>
        </a>

        <div class="nav-section-title">Account</div>
        <a href="profile.php" class="nav-link <?= ($currentPage === 'profile.php') ? 'active' : '' ?>">
            <span class="icon">👤</span>
            <span>My Profile</span>
        </a>
        <a href="logout.php" class="nav-link" onclick="return confirm('Are you sure you want to log out?');">
            <span class="icon">🚪</span>
            <span>Log Out</span>
        </a>
    </nav>

    <div class="sidebar-footer">
        <div class="user-mini-card">
            <div class="user-avatar">
                <?= strtoupper(substr($user['full_name'] ?? 'U', 0, 1)) ?>
            </div>
            <div class="user-mini-info">
                <div class="user-mini-name"><?= htmlspecialchars($user['full_name'] ?? 'User') ?></div>
                <div class="user-mini-role"><?= htmlspecialchars($user['designation'] ?? ucfirst($user['role'] ?? 'Staff')) ?></div>
            </div>
        </div>
    </div>
</aside>
