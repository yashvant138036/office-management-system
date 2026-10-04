<?php
/**
 * User Profile & Password Change
 * Office Management System (OMS)
 */

$pageTitle = 'My Profile';
require_once __DIR__ . '/includes/header.php';

$userId = current_user_id();

// Fetch latest user details from DB
$stmt = $pdo->prepare("
    SELECT u.*, d.name AS department_name 
    FROM users u
    LEFT JOIN departments d ON u.department_id = d.id
    WHERE u.id = ?
");
$stmt->execute([$userId]);
$profile = $stmt->fetch();

$errors = [];

// 1. Update Contact Information
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_info') {
    $phone = trim($_POST['phone'] ?? '');

    $upd = $pdo->prepare("UPDATE users SET phone = ? WHERE id = ?");
    $upd->execute([$phone, $userId]);

    $_SESSION['user']['phone'] = $phone;
    flash_set('success', 'Profile contact details updated successfully.');
    redirect('profile.php');
}

// 2. Change Password
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'change_pass') {
    $currentPass = trim($_POST['current_password'] ?? '');
    $newPass = trim($_POST['new_password'] ?? '');
    $confirmPass = trim($_POST['confirm_password'] ?? '');

    if (empty($currentPass) || empty($newPass) || empty($confirmPass)) {
        $errors[] = 'All password fields are required.';
    } elseif (!password_verify($currentPass, $profile['password'])) {
        $errors[] = 'Current password is incorrect.';
    } elseif (strlen($newPass) < 6) {
        $errors[] = 'New password must be at least 6 characters.';
    } elseif ($newPass !== $confirmPass) {
        $errors[] = 'New password and confirmation do not match.';
    } else {
        $hashed = password_hash($newPass, PASSWORD_BCRYPT);
        $upd = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
        $upd->execute([$hashed, $userId]);

        flash_set('success', 'Password successfully changed! Please use your new password next time you log in.');
        redirect('profile.php');
    }
}
?>

<div style="max-width: 900px; margin: 0 auto;">
    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul style="margin: 0; padding-left: 20px;">
                <?php foreach ($errors as $err): ?>
                    <li><?= htmlspecialchars($err) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <div class="dashboard-grid" style="grid-template-columns: 1fr 2fr;">
        <!-- Left: Profile Summary Card -->
        <div class="card" style="text-align: center;">
            <div class="card-body">
                <div class="user-avatar" style="width: 72px; height: 72px; font-size: 26px; margin: 0 auto 16px;">
                    <?= strtoupper(substr($profile['full_name'], 0, 1)) ?>
                </div>
                <h3 style="font-size: 18px; font-weight: 700; margin-bottom: 4px;"><?= htmlspecialchars($profile['full_name']) ?></h3>
                <div style="color: #64748b; font-size: 13px; margin-bottom: 12px;"><?= htmlspecialchars($profile['designation'] ?: 'Staff Member') ?></div>
                <div><?= status_badge($profile['status']) ?></div>

                <hr style="border: 0; border-top: 1px solid var(--border); margin: 20px 0;">

                <div style="text-align: left; display: flex; flex-direction: column; gap: 12px; font-size: 13px;">
                    <div>
                        <strong style="color: #64748b;">Department:</strong><br>
                        <?= htmlspecialchars($profile['department_name'] ?? 'Not Assigned') ?>
                    </div>
                    <div>
                        <strong style="color: #64748b;">Email:</strong><br>
                        <?= htmlspecialchars($profile['email']) ?>
                    </div>
                    <div>
                        <strong style="color: #64748b;">Role:</strong><br>
                        <?= ucfirst($profile['role']) ?>
                    </div>
                    <div>
                        <strong style="color: #64748b;">Date Joined:</strong><br>
                        <?= format_date($profile['date_of_joining']) ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right: Settings & Password Change -->
        <div>
            <!-- Update Contact Info -->
            <div class="card">
                <div class="card-header">
                    <div class="card-title">📱 Contact Information</div>
                </div>
                <div class="card-body">
                    <form action="profile.php" method="POST">
                        <input type="hidden" name="action" value="update_info">
                        <div class="form-group">
                            <label class="form-label" for="phone">Phone Number</label>
                            <input type="text" name="phone" id="phone" class="form-control" value="<?= htmlspecialchars($profile['phone'] ?? '') ?>" placeholder="+1 (555) 000-0000">
                        </div>
                        <button type="submit" class="btn btn-secondary">Update Phone Number</button>
                    </form>
                </div>
            </div>

            <!-- Change Password -->
            <div class="card">
                <div class="card-header">
                    <div class="card-title">🔒 Change Password</div>
                </div>
                <div class="card-body">
                    <form action="profile.php" method="POST">
                        <input type="hidden" name="action" value="change_pass">
                        
                        <div class="form-group">
                            <label class="form-label" for="current_password">Current Password *</label>
                            <input type="password" name="current_password" id="current_password" class="form-control" required placeholder="••••••••">
                        </div>

                        <div class="form-grid" style="grid-template-columns: 1fr 1fr; gap: 12px;">
                            <div class="form-group">
                                <label class="form-label" for="new_password">New Password * (min 6 chars)</label>
                                <input type="password" name="new_password" id="new_password" class="form-control" required placeholder="••••••••">
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="confirm_password">Confirm New Password *</label>
                                <input type="password" name="confirm_password" id="confirm_password" class="form-control" required placeholder="••••••••">
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary" style="margin-top: 10px;">Update Password</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
