-- ===================================================
-- Office Management System (OMS) Database Schema
-- Database: office_management
-- ===================================================

CREATE DATABASE IF NOT EXISTS `office_management` 
DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE `office_management`;

-- 1. Departments Table
DROP TABLE IF EXISTS `notices`;
DROP TABLE IF EXISTS `tasks`;
DROP TABLE IF EXISTS `leaves`;
DROP TABLE IF EXISTS `attendance`;
DROP TABLE IF EXISTS `users`;
DROP TABLE IF EXISTS `departments`;

CREATE TABLE `departments` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL UNIQUE,
    `description` TEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Users / Employees Table
CREATE TABLE `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `full_name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(150) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `phone` VARCHAR(20) NULL,
    `role` ENUM('admin', 'employee') NOT NULL DEFAULT 'employee',
    `department_id` INT NULL,
    `designation` VARCHAR(100) NULL,
    `salary` DECIMAL(10,2) DEFAULT 0.00,
    `date_of_joining` DATE NULL,
    `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_users_department` FOREIGN KEY (`department_id`) 
        REFERENCES `departments`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Attendance Table
CREATE TABLE `attendance` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `date` DATE NOT NULL,
    `clock_in` TIME NULL,
    `clock_out` TIME NULL,
    `total_hours` DECIMAL(4,2) DEFAULT 0.00,
    `status` ENUM('present', 'late', 'half_day', 'absent') NOT NULL DEFAULT 'present',
    `notes` VARCHAR(255) NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `user_daily_attendance` (`user_id`, `date`),
    CONSTRAINT `fk_attendance_user` FOREIGN KEY (`user_id`) 
        REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Leaves Table
CREATE TABLE `leaves` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `leave_type` ENUM('casual', 'sick', 'annual', 'unpaid', 'other') NOT NULL DEFAULT 'casual',
    `start_date` DATE NOT NULL,
    `end_date` DATE NOT NULL,
    `total_days` INT NOT NULL DEFAULT 1,
    `reason` TEXT NOT NULL,
    `status` ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
    `admin_remarks` VARCHAR(255) NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_leaves_user` FOREIGN KEY (`user_id`) 
        REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. Tasks Table
CREATE TABLE `tasks` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(200) NOT NULL,
    `description` TEXT NULL,
    `assigned_to` INT NOT NULL,
    `assigned_by` INT NOT NULL,
    `priority` ENUM('low', 'medium', 'high', 'urgent') NOT NULL DEFAULT 'medium',
    `due_date` DATE NOT NULL,
    `status` ENUM('pending', 'in_progress', 'completed', 'on_hold') NOT NULL DEFAULT 'pending',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_tasks_assigned_to` FOREIGN KEY (`assigned_to`) 
        REFERENCES `users`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_tasks_assigned_by` FOREIGN KEY (`assigned_by`) 
        REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 6. Notices Table
CREATE TABLE `notices` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(200) NOT NULL,
    `content` TEXT NOT NULL,
    `posted_by` INT NOT NULL,
    `audience` ENUM('all', 'admin', 'employee') NOT NULL DEFAULT 'all',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_notices_posted_by` FOREIGN KEY (`posted_by`) 
        REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ===================================================
-- SEED DATA
-- ===================================================

-- Departments
INSERT INTO `departments` (`id`, `name`, `description`) VALUES
(1, 'Human Resources', 'Handles recruitment, employee relations, and payroll benefits.'),
(2, 'Information Technology', 'Software development, infrastructure, and tech support.'),
(3, 'Sales & Marketing', 'Brand promotion, client outreach, and revenue generation.'),
(4, 'Finance & Accounting', 'Budgeting, expense audits, and financial reporting.'),
(5, 'Operations', 'Daily business logistics, procurement, and facility maintenance.');

-- Users
-- Password for admin: admin123
-- Password for employees: password123
INSERT INTO `users` (`id`, `full_name`, `email`, `password`, `phone`, `role`, `department_id`, `designation`, `salary`, `date_of_joining`, `status`) VALUES
(1, 'System Administrator', 'admin@office.com', '$2y$10$kdkOELWT3dc60wFTlsi.R.I6JPmN.2OZCtmwbuLJx1FuvSYSAKps.', '+1 (555) 019-2831', 'admin', 1, 'HR Director', 85000.00, '2023-01-10', 'active'),
(2, 'Alex Chen', 'alex.chen@office.com', '$2y$10$39MWi8t/eDWzBoWI6zSkbe8H/PWKBb0ZL1c5.ur3dmEgYw/vouJ1G', '+1 (555) 014-9921', 'employee', 2, 'Senior Full-Stack Engineer', 72000.00, '2023-03-15', 'active'),
(3, 'Sarah Smith', 'sarah.smith@office.com', '$2y$10$39MWi8t/eDWzBoWI6zSkbe8H/PWKBb0ZL1c5.ur3dmEgYw/vouJ1G', '+1 (555) 017-3829', 'employee', 3, 'Marketing Lead', 65000.00, '2023-06-01', 'active'),
(4, 'David Kumar', 'david.kumar@office.com', '$2y$10$39MWi8t/eDWzBoWI6zSkbe8H/PWKBb0ZL1c5.ur3dmEgYw/vouJ1G', '+1 (555) 018-4720', 'employee', 4, 'Financial Analyst', 58000.00, '2023-09-20', 'active');

-- Sample Notices
INSERT INTO `notices` (`id`, `title`, `content`, `posted_by`, `audience`) VALUES
(1, 'Quarterly All-Hands Meeting', 'The Q3 All-Hands townhall will be held this Friday at 3:00 PM in Conference Room A and virtually via Google Meet. Please review team slides in advance.', 1, 'all'),
(2, 'Upcoming System Maintenance', 'IT will be conducting scheduled server updates this Saturday from 11:00 PM to 2:00 AM UTC. Internal network services may experience brief interruptions.', 1, 'all'),
(3, 'Annual Health Checkup Drive', 'Free executive health checkups for all employees will take place next Wednesday. Slots can be reserved with HR.', 1, 'employee');

-- Sample Tasks
INSERT INTO `tasks` (`id`, `title`, `description`, `assigned_to`, `assigned_by`, `priority`, `due_date`, `status`) VALUES
(1, 'Prepare Q3 Financial Audit', 'Consolidate department expense reports and prepare balance sheet for executive review.', 4, 1, 'high', '2026-10-05', 'in_progress'),
(2, 'Deploy Mobile Responsive UI', 'Optimize dashboard styles for mobile viewports and tablets across all major browsers.', 2, 1, 'urgent', '2026-09-30', 'in_progress'),
(3, 'Launch Autumn Social Campaign', 'Finalize copy and graphics for LinkedIn and Twitter launch.', 3, 1, 'medium', '2026-10-12', 'pending'),
(4, 'Update IT Security Policy', 'Review password expiration and 2FA guidelines for new hires.', 2, 1, 'low', '2026-10-20', 'completed');

-- Sample Leaves
INSERT INTO `leaves` (`id`, `user_id`, `leave_type`, `start_date`, `end_date`, `total_days`, `reason`, `status`, `admin_remarks`) VALUES
(1, 2, 'casual', '2026-10-01', '2026-10-02', 2, 'Attending family wedding ceremony.', 'approved', 'Approved. Ensure tasks are handed over to secondary engineer.'),
(2, 3, 'sick', '2026-09-28', '2026-09-29', 2, 'Recovery from viral fever.', 'pending', NULL);

-- Sample Attendance
INSERT INTO `attendance` (`user_id`, `date`, `clock_in`, `clock_out`, `total_hours`, `status`, `notes`) VALUES
(2, CURRENT_DATE(), '09:05:00', '17:30:00', 8.42, 'present', 'On time'),
(3, CURRENT_DATE(), '09:25:00', '18:00:00', 8.58, 'late', 'Traffic delay'),
(4, CURRENT_DATE(), '09:00:00', NULL, 0.00, 'present', 'In office');
