# 🏢 OfficeDesk - Office Management System (OMS)

A modern, responsive, and secure Office Management System built with **HTML5, CSS3, PHP (PDO), and MySQL**.

---

## ✨ Features Included

- **🔐 Authentication & Role-Based Access Control (RBAC):**
  - **Administrator:** Full administrative rights, staff directory management, department setup, leave approvals, task assignments, and company announcements.
  - **Employee:** Personal self-service portal, daily attendance clock-in/out, leave applications, task status updates, and announcement board.
  - Secure bcrypt password hashing and session hijacking mitigation.

- **⏱️ Attendance Management:**
  - One-click daily Clock In and Clock Out with automatic late status calculation.
  - Monthly attendance history with filters for date, month, and employee.
  - Manual attendance regularization by Administrators.

- **📅 Leave Tracking & Approvals:**
  - Multi-day leave requests (Casual, Sick, Annual, Unpaid).
  - Admin approval/rejection workflow with remarks.
  - Employee cancellation of pending requests.

- **✅ Task Assignment & Progress Board:**
  - Assign tasks to team members with priorities (Low, Medium, High, Urgent) and due dates.
  - Real-time status tracker (Pending, In Progress, Completed, On Hold).
  - Overdue task alerts.

- **👥 Employee Directory & HR:**
  - Add, edit, deactivate, or delete employee profiles.
  - Department assignments, designations, salary records, and contact details.

- **🏢 Department Management:**
  - Manage company departments with real-time employee counts and deletion protection for active departments.

- **📢 Notice Board:**
  - Broadcast company-wide notices or targeted announcements.

- **👤 User Profile & Security:**
  - Update personal contact details.
  - Secure password changes with current password verification.

---

## 📁 Project Structure

```
office-management-system/
├── assets/
│   ├── css/
│   │   ├── style.css         # Main application stylesheet & design system
│   │   └── auth.css          # Login portal styling
│   └── js/
│       └── main.js           # Responsive sidebar, modals, live digital clock
├── config/
│   └── db.php                # MySQL PDO connection & configuration
├── includes/
│   ├── auth.php              # Auth guards & access control checks
│   ├── functions.php         # Sanitization, flash messages, status badges
│   ├── header.php            # Global header & topbar
│   ├── sidebar.php           # Role-based navigation sidebar
│   └── footer.php            # Global footer & scripts
├── sql/
│   └── schema.sql            # Database schema & sample seed data
├── index.php                 # Interactive dashboard
├── login.php                 # Login portal with 1-click demo login buttons
├── logout.php                # Session termination
├── employees.php             # Employee directory
├── employee-add.php          # Add employee (Admin)
├── employee-edit.php         # Edit employee (Admin)
├── departments.php           # Department management (Admin)
├── attendance.php            # Attendance clock & records
├── leaves.php                # Leave requests & approval workflow
├── tasks.php                 # Task management board
├── notices.php               # Company notice board
├── profile.php               # Profile and password management
└── README.md                 # Setup and run instructions
```

---

## 🚀 Setup & Installation Instructions

### 1. Database Setup (MySQL)
1. Start your MySQL service (e.g. via **XAMPP**, **Laragon**, or Windows Services).
2. Open **phpMyAdmin** or MySQL CLI.
3. Import the file located at:
   ```
   office-management-system/sql/schema.sql
   ```
   *Or from MySQL command prompt:*
   ```bash
   mysql -u root -p < sql/schema.sql
   ```
   *(This creates the `office_management` database with pre-configured tables and sample seed records.)*

### 2. Configure Database Credentials (if needed)
Open `config/db.php` and adjust your MySQL credentials if they differ from the defaults:
```php
define('DB_HOST', 'localhost');
define('DB_PORT', '3306');
define('DB_NAME', 'office_management');
define('DB_USER', 'root');
define('DB_PASS', ''); // set your MySQL password here if applicable
```

### 3. Running the Website

#### Method A: Using PHP Built-in Server (Quickest)
Open PowerShell or Terminal in the project directory:
```powershell
cd "C:\Users\jiten\.gemini\antigravity\scratch\office-management-system"
php -S localhost:8000
```
Then open your browser and navigate to:
```
http://localhost:8000
```

#### Method B: Using XAMPP / Laragon
1. Copy or move the `office-management-system` folder to your web server root:
   - XAMPP: `C:\xampp\htdocs\office-management-system`
   - Laragon: `C:\laragon\www\office-management-system`
2. Start Apache and MySQL from the control panel.
3. Open your browser and navigate to:
   ```
   http://localhost/office-management-system/
   ```

---

## 🔑 Login & Access Modes (Anyone Can Log In)

The application has open access enabled so anyone can test and use it immediately:

1. **⚡ 1-Click Instant Login:**
   - Click **"⚡ 1-Click Login as Admin"** to access as System Administrator.
   - Click **"⚡ 1-Click Login as Employee"** to access as Staff Member (Alex Chen).

2. **🚀 Auto-Registration with Any Email:**
   - Enter **any email address** on the login form (e.g., `yourname@office.com`).
   - If the account doesn't exist yet, it is **created automatically on the spot** and logs you straight in.
   - You can choose your desired access level (**Employee** or **Administrator**) directly from the dropdown.

3. **📝 Custom Registration / Sign Up:**
   - Visit [`register.php`](file:///C:/Users/jiten/.gemini/antigravity/scratch/office-management-system/register.php) to create an account with a custom department, designation, and password.

4. **Default Demo Accounts:**
   - **Admin:** `admin@office.com` (password: `admin123`)
   - **Employee:** `alex.chen@office.com` (password: `password123`)
