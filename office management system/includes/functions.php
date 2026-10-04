<?php
/**
 * Helper and Utility Functions
 * Office Management System (OMS)
 */

function sanitize($data) {
    if (is_array($data)) {
        return array_map('sanitize', $data);
    }
    return htmlspecialchars(trim((string)$data), ENT_QUOTES, 'UTF-8');
}

function redirect($url) {
    header("Location: " . $url);
    exit;
}

function flash_set($type, $message) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $_SESSION['flash'] = [
        'type' => $type, // 'success', 'danger', 'warning', 'info'
        'message' => $message
    ];
}

function flash_get() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

function flash_render() {
    $flash = flash_get();
    if ($flash) {
        $type = htmlspecialchars($flash['type']);
        $msg = htmlspecialchars($flash['message']);
        $icon = 'ℹ️';
        if ($type === 'success') $icon = '✅';
        if ($type === 'danger') $icon = '⚠️';
        if ($type === 'warning') $icon = '⚡';
        
        echo "<div class=\"alert alert-{$type}\"><span class=\"alert-icon\">{$icon}</span> <span>{$msg}</span><button class=\"alert-close\" onclick=\"this.parentElement.remove();\">&times;</button></div>";
    }
}

function current_user() {
    return $_SESSION['user'] ?? null;
}

function current_user_id() {
    return $_SESSION['user']['id'] ?? null;
}

function is_admin() {
    return isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'admin';
}

function format_date($date, $format = 'M d, Y') {
    if (!$date || $date === '0000-00-00') return '-';
    $timestamp = strtotime($date);
    return $timestamp ? date($format, $timestamp) : '-';
}

function format_time($time, $format = 'h:i A') {
    if (!$time) return '-';
    $timestamp = strtotime($time);
    return $timestamp ? date($format, $timestamp) : '-';
}

function format_currency($amount) {
    return '$' . number_format((float)$amount, 2);
}

function status_badge($status) {
    $status = strtolower(trim((string)$status));
    $classes = [
        'active'      => 'badge-success',
        'inactive'    => 'badge-secondary',
        'present'     => 'badge-success',
        'late'        => 'badge-warning',
        'half_day'    => 'badge-info',
        'absent'      => 'badge-danger',
        'pending'     => 'badge-warning',
        'in_progress' => 'badge-info',
        'completed'   => 'badge-success',
        'on_hold'     => 'badge-secondary',
        'approved'    => 'badge-success',
        'rejected'    => 'badge-danger',
    ];
    $cls = $classes[$status] ?? 'badge-secondary';
    $label = ucwords(str_replace('_', ' ', $status));
    return "<span class=\"badge {$cls}\">{$label}</span>";
}

function priority_badge($priority) {
    $priority = strtolower(trim((string)$priority));
    $classes = [
        'low'    => 'priority-low',
        'medium' => 'priority-medium',
        'high'   => 'priority-high',
        'urgent' => 'priority-urgent',
    ];
    $cls = $classes[$priority] ?? 'priority-medium';
    $label = ucfirst($priority);
    return "<span class=\"priority-tag {$cls}\">{$label}</span>";
}
