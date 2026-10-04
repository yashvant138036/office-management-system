<?php
/**
 * Header Component
 * Office Management System (OMS)
 */

require_once __DIR__ . '/auth.php';
require_login();

$user = current_user();
$pageTitle = $pageTitle ?? 'Office Management System';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> - OfficeDesk</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="app-container">
    <?php include __DIR__ . '/sidebar.php'; ?>

    <div class="main-wrapper">
        <header class="topbar">
            <div class="topbar-left">
                <button type="button" class="sidebar-toggle-btn" id="sidebar-toggle" aria-label="Toggle Sidebar">☰</button>
                <h1 class="page-title"><?= htmlspecialchars($pageTitle) ?></h1>
            </div>

            <div class="topbar-right">
                <div class="time-display">
                    📅 <?= date('l, M d, Y') ?> &nbsp;|&nbsp; 🕒 <span id="live-clock"><?= date('h:i:s A') ?></span>
                    &nbsp;|&nbsp;
                    <span class="badge <?= (DB_ACTIVE_DRIVER === 'mysql') ? 'badge-success' : 'badge-info' ?>" title="Active Storage Engine">
                        <?= strtoupper(DB_ACTIVE_DRIVER) ?>
                    </span>
                </div>
                <a href="profile.php" class="topbar-btn" title="View Profile">
                    <span>👤</span> <?= htmlspecialchars(explode(' ', $user['full_name'] ?? 'User')[0]) ?>
                </a>
                <a href="logout.php" class="topbar-btn" title="Log Out" onclick="return confirm('Are you sure you want to log out?');">
                    <span>🚪</span> Sign Out
                </a>
            </div>
        </header>

        <main class="content-body">
            <?php flash_render(); ?>
