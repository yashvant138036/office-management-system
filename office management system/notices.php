<?php
/**
 * Company Notice Board & Announcements
 * Office Management System (OMS)
 */

$pageTitle = 'Notice Board';
require_once __DIR__ . '/includes/header.php';

$userId = current_user_id();

// 1. Post New Notice (Admin Only)
if (is_admin() && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'post_notice') {
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $audience = $_POST['audience'] ?? 'all';

    if (empty($title) || empty($content)) {
        flash_set('danger', 'Notice title and content are required.');
    } else {
        $ins = $pdo->prepare("INSERT INTO notices (title, content, posted_by, audience) VALUES (?, ?, ?, ?)");
        $ins->execute([$title, $content, $userId, $audience]);
        flash_set('success', 'Announcement published to notice board!');
        redirect('notices.php');
    }
}

// 2. Delete Notice (Admin Only)
if (is_admin() && isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $noticeId = (int)$_GET['id'];
    $del = $pdo->prepare("DELETE FROM notices WHERE id = ?");
    $del->execute([$noticeId]);
    flash_set('success', 'Notice deleted.');
    redirect('notices.php');
}

// Fetch notices based on role
if (is_admin()) {
    $notices = $pdo->query("
        SELECT n.*, u.full_name AS author_name 
        FROM notices n
        JOIN users u ON n.posted_by = u.id
        ORDER BY n.created_at DESC
    ")->fetchAll();
} else {
    $notices = $pdo->query("
        SELECT n.*, u.full_name AS author_name 
        FROM notices n
        JOIN users u ON n.posted_by = u.id
        WHERE n.audience IN ('all', 'employee')
        ORDER BY n.created_at DESC
    ")->fetchAll();
}
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
    <div style="color: #64748b;">
        Important company announcements, town halls, and operational updates.
    </div>

    <?php if (is_admin()): ?>
        <button type="button" class="btn btn-primary" data-open-modal="postNoticeModal">
            <span>📢</span> Post Announcement
        </button>
    <?php endif; ?>
</div>

<div class="card">
    <div class="card-header">
        <div class="card-title">📌 All Announcements (<?= count($notices) ?>)</div>
    </div>
    <div class="card-body">
        <?php if (empty($notices)): ?>
            <p style="text-align: center; color: #94a3b8; padding: 30px; font-style: italic;">
                No announcements currently published.
            </p>
        <?php else: ?>
            <div class="notices-list">
                <?php foreach ($notices as $notice): ?>
                    <div class="notice-card">
                        <div class="notice-header">
                            <div>
                                <span class="notice-title"><?= htmlspecialchars($notice['title']) ?></span>
                                <?php if (is_admin()): ?>
                                    <span class="badge badge-secondary" style="margin-left: 8px;">Audience: <?= ucfirst($notice['audience']) ?></span>
                                <?php endif; ?>
                            </div>
                            <div style="display: flex; align-items: center; gap: 12px;">
                                <span class="notice-meta">
                                    Posted by <strong><?= htmlspecialchars($notice['author_name']) ?></strong> on <?= format_date($notice['created_at']) ?>
                                </span>
                                <?php if (is_admin()): ?>
                                    <a href="notices.php?action=delete&id=<?= $notice['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete this announcement?');">
                                        🗑️
                                    </a>
                                <?php endif; ?>
                            </div>
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

<!-- Modal: Post Notice (Admin Only) -->
<?php if (is_admin()): ?>
<div class="modal-overlay" id="postNoticeModal">
    <div class="modal-card">
        <div class="modal-header">
            <div class="modal-title">Broadcast Announcement</div>
            <button type="button" class="modal-close" data-close-modal>&times;</button>
        </div>
        <form action="notices.php" method="POST">
            <input type="hidden" name="action" value="post_notice">
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label" for="notice_title">Notice Headline *</label>
                    <input type="text" name="title" id="notice_title" class="form-control" required placeholder="e.g. Annual Company Retreat Details">
                </div>

                <div class="form-group">
                    <label class="form-label" for="audience">Target Audience</label>
                    <select name="audience" id="audience" class="form-control">
                        <option value="all">All Employees & Staff</option>
                        <option value="employee">Employees Only</option>
                        <option value="admin">Administrators Only</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label" for="notice_content">Notice Content / Body *</label>
                    <textarea name="content" id="notice_content" class="form-control" style="min-height: 120px;" required placeholder="Write the full announcement text here..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-close-modal>Cancel</button>
                <button type="submit" class="btn btn-primary">Publish Notice</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
