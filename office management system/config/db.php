<?php
/**
 * Database Configuration & Smart Connection Handler
 * Office Management System (OMS)
 * 
 * Supports:
 * 1. Primary: MySQL (Standard XAMPP / Laragon / MySQL Server)
 * 2. Automatic Fallback: Local Embedded SQLite (Zero-config instant run if MySQL is offline)
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// MySQL Default Credentials
defined('DB_HOST') or define('DB_HOST', 'localhost');
defined('DB_PORT') or define('DB_PORT', '3306');
defined('DB_NAME') or define('DB_NAME', 'office_management');
defined('DB_USER') or define('DB_USER', 'root');
defined('DB_PASS') or define('DB_PASS', '');

// Base URL configuration
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
$baseUrl = rtrim($protocol . $host . $scriptDir, '/');
defined('BASE_URL') or define('BASE_URL', $baseUrl);

$pdo = null;
$activeDriver = 'mysql';

// 1. Attempt MySQL Connection
try {
    // Quick test if port is responding or connect directly with short timeout
    $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
        PDO::ATTR_TIMEOUT            => 1, // Quick 1s timeout to prevent long delays if offline
    ];
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    $activeDriver = 'mysql';

    // Auto-create MySQL tables if database was just created empty
    $tblChk = $pdo->query("SHOW TABLES LIKE 'users'")->fetch();
    if (!$tblChk && file_exists(__DIR__ . '/../sql/schema.sql')) {
        $sqlScript = file_get_contents(__DIR__ . '/../sql/schema.sql');
        $pdo->exec($sqlScript);
    }
} catch (PDOException $e) {
    // MySQL is offline or not installed. Fallback to SQLite for instant zero-config operation
    $sqliteFile = __DIR__ . '/../office_management.sqlite';
    $needInit = !file_exists($sqliteFile) || filesize($sqliteFile) === 0;

    try {
        $pdo = new PDO("sqlite:" . $sqliteFile, null, null, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        $pdo->exec("PRAGMA foreign_keys = ON;");
        $activeDriver = 'sqlite';

        if ($needInit) {
            init_sqlite_database($pdo);
        }
    } catch (Exception $sqliteEx) {
        die("Fatal Database Error: Unable to initialize storage. " . htmlspecialchars($sqliteEx->getMessage()));
    }
}

defined('DB_ACTIVE_DRIVER') or define('DB_ACTIVE_DRIVER', $activeDriver);

/**
 * Auto-initialize SQLite database if running without MySQL
 */
function init_sqlite_database(PDO $db) {
    // 1. Schema
    $db->exec("
        CREATE TABLE IF NOT EXISTS departments (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL UNIQUE,
            description TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            full_name TEXT NOT NULL,
            email TEXT NOT NULL UNIQUE,
            password TEXT NOT NULL,
            phone TEXT,
            role TEXT NOT NULL DEFAULT 'employee',
            department_id INTEGER,
            designation TEXT,
            salary NUMERIC DEFAULT 0.00,
            date_of_joining DATE,
            status TEXT NOT NULL DEFAULT 'active',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL
        );

        CREATE TABLE IF NOT EXISTS attendance (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            date DATE NOT NULL,
            clock_in TIME,
            clock_out TIME,
            total_hours NUMERIC DEFAULT 0.00,
            status TEXT NOT NULL DEFAULT 'present',
            notes TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            UNIQUE(user_id, date),
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        );

        CREATE TABLE IF NOT EXISTS leaves (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            leave_type TEXT NOT NULL DEFAULT 'casual',
            start_date DATE NOT NULL,
            end_date DATE NOT NULL,
            total_days INTEGER NOT NULL DEFAULT 1,
            reason TEXT NOT NULL,
            status TEXT NOT NULL DEFAULT 'pending',
            admin_remarks TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        );

        CREATE TABLE IF NOT EXISTS tasks (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            title TEXT NOT NULL,
            description TEXT,
            assigned_to INTEGER NOT NULL,
            assigned_by INTEGER NOT NULL,
            priority TEXT NOT NULL DEFAULT 'medium',
            due_date DATE NOT NULL,
            status TEXT NOT NULL DEFAULT 'pending',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (assigned_by) REFERENCES users(id) ON DELETE CASCADE
        );

        CREATE TABLE IF NOT EXISTS notices (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            title TEXT NOT NULL,
            content TEXT NOT NULL,
            posted_by INTEGER NOT NULL,
            audience TEXT NOT NULL DEFAULT 'all',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (posted_by) REFERENCES users(id) ON DELETE CASCADE
        );
    ");

    // 2. Seed Data
    $adminPass = password_hash('admin123', PASSWORD_BCRYPT);
    $empPass = password_hash('password123', PASSWORD_BCRYPT);

    $db->exec("
        INSERT INTO departments (id, name, description) VALUES
        (1, 'Human Resources', 'Recruitment, employee relations, and benefits.'),
        (2, 'Information Technology', 'Software development, infrastructure, and tech support.'),
        (3, 'Sales & Marketing', 'Brand promotion, client outreach, and revenue generation.'),
        (4, 'Finance & Accounting', 'Budgeting, expense audits, and financial reporting.'),
        (5, 'Operations', 'Daily business logistics and facility maintenance.');

        INSERT INTO users (id, full_name, email, password, phone, role, department_id, designation, salary, date_of_joining, status) VALUES
        (1, 'System Administrator', 'admin@office.com', '{$adminPass}', '+1 (555) 019-2831', 'admin', 1, 'HR Director', 85000.00, '2023-01-10', 'active'),
        (2, 'Alex Chen', 'alex.chen@office.com', '{$empPass}', '+1 (555) 014-9921', 'employee', 2, 'Senior Full-Stack Engineer', 72000.00, '2023-03-15', 'active'),
        (3, 'Sarah Smith', 'sarah.smith@office.com', '{$empPass}', '+1 (555) 017-3829', 'employee', 3, 'Marketing Lead', 65000.00, '2023-06-01', 'active'),
        (4, 'David Kumar', 'david.kumar@office.com', '{$empPass}', '+1 (555) 018-4720', 'employee', 4, 'Financial Analyst', 58000.00, '2023-09-20', 'active');

        INSERT INTO notices (id, title, content, posted_by, audience) VALUES
        (1, 'Quarterly All-Hands Townhall', 'The Q3 All-Hands townhall will be held this Friday at 3:00 PM in Conference Room A and virtually via Google Meet.', 1, 'all'),
        (2, 'Scheduled Server Maintenance', 'IT will be conducting scheduled infrastructure updates this Saturday from 11:00 PM to 2:00 AM UTC.', 1, 'all'),
        (3, 'Annual Health Checkup Program', 'Free executive health checkups for all employees will take place next Wednesday. Reserve slots with HR.', 1, 'employee');

        INSERT INTO tasks (id, title, description, assigned_to, assigned_by, priority, due_date, status) VALUES
        (1, 'Prepare Q3 Financial Audit', 'Consolidate department expense reports and prepare balance sheet for executive review.', 4, 1, 'high', '2026-10-05', 'in_progress'),
        (2, 'Deploy Mobile Responsive UI', 'Optimize dashboard styles for mobile viewports and tablets across all major browsers.', 2, 1, 'urgent', '2026-09-30', 'in_progress'),
        (3, 'Launch Autumn Social Campaign', 'Finalize copy and graphics for LinkedIn and Twitter launch.', 3, 1, 'medium', '2026-10-12', 'pending'),
        (4, 'Update IT Security Policy', 'Review password expiration and 2FA guidelines for new hires.', 2, 1, 'low', '2026-10-20', 'completed');

        INSERT INTO leaves (id, user_id, leave_type, start_date, end_date, total_days, reason, status, admin_remarks) VALUES
        (1, 2, 'casual', '2026-10-01', '2026-10-02', 2, 'Attending family wedding ceremony.', 'approved', 'Approved. Ensure tasks are handed over to secondary engineer.'),
        (2, 3, 'sick', '2026-09-28', '2026-09-29', 2, 'Recovery from seasonal flu.', 'pending', NULL);

        INSERT INTO attendance (user_id, date, clock_in, clock_out, total_hours, status, notes) VALUES
        (2, date('now'), '09:05:00', '17:30:00', 8.42, 'present', 'On time'),
        (3, date('now'), '09:25:00', '18:00:00', 8.58, 'late', 'Traffic delay'),
        (4, date('now'), '09:00:00', NULL, 0.00, 'present', 'In office');
    ");
}
