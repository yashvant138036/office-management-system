<?php
/**
 * Authentication and Access Control Guard
 * Office Management System (OMS)
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/functions.php';

function require_login() {
    if (!isset($_SESSION['user']) || empty($_SESSION['user']['id'])) {
        flash_set('danger', 'Please sign in to access the system.');
        redirect('login.php');
    }
}

function require_admin() {
    require_login();
    if (!is_admin()) {
        flash_set('danger', 'Access denied. Administrator privileges required.');
        redirect('index.php');
    }
}
