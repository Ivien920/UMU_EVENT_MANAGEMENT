<?php
// ============================================================
// UMU Events — Admin: Send Notifications
// ============================================================
require_once '../includes/db.php';
require_once '../includes/auth.php';
requireAdmin('../index.php');

$db     = getDB();
$errors = [];

// ── SEND NOTIFICATION ─────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_notif'])) {
    $title    = trim($_POST['title'] ?? '');
    $body     = trim($_POST['body'] ?? '');
    $type     = trim($_POST['type'] ?? 'system');
    $target   = trim($_POST['target'] ?? 'all');
    $targetId = cleanInt($_POST['target_user_id'] ?? 0);

    if (empty($title)) $errors[] = 'Title is required.';
    if (empty($body))  $errors[] = 'Message body is required.';

    if (empty($errors)) {
        if ($target === 'all') {
            $users = $db->query("SELECT id FROM users")->fetch_all(MYSQLI_ASSOC);
            foreach ($users as $u) createNotification($u['id'], $title, $body, $type);
            setFlash('success', 'Notification sent to all ' . count($users) . ' users.');
        } elseif ($target === 'students') {
            $users = $db->query("SELECT id FROM users WHERE role='student'")->fetch_all(MYSQLI_ASSOC);
            foreach ($users as $u) createNotification($u['id'], $title, $body, $type);
            setFlash('success', 'Notification sent to all students.');
        } elseif ($target === 'verified') {
            $users = $db->query("SELECT id FROM users WHERE role='verified'")->fetch_all(MYSQLI_ASSOC);
            foreach ($users as $u) createNotification($u['id'], $title, $body, $type);
            setFlash('success', 'Notification sent to all verified users.');
        } elseif ($target === 'one' && $targetId > 0) {
            createNotification($targetId, $title, $body, $type);
            setFlash('success', 'Notification sent to user.');
        }
        redirect('notifications.php');
    }
}

// ── DELETE NOTIFICATION ───────────────────────────────────────
if (isset($_GET['delete'])) {
    $nId = cleanInt($_GET['delete']);
    $db->query("DELETE FROM notifications WHERE id=$nId");
    setFlash('success', 'Notification deleted.');
    redirect('notifications.php');
}

// All notifications (admin view)
$notifs = $db->query("
    SELECT n.*, u.full_name, u.reg_number
    FROM notifications n
    JOIN users u ON u.id=n.user_id
    ORDER BY n.created_at DESC
    LIMIT 100
")->fetch_all(MYSQLI_ASSOC);

$allUsers = $db->query("SELECT id, full_name, reg_number FROM users ORDER BY full_name")->fetch_all(MYSQLI_ASSOC);

$pageTitle = 'Notifications'; $activePage = 'notifs';
include '../includes/admin_header.php';
?>

<?php renderFlash(); ?>

<!-- Send Form -->
<div class="admin-form-card" style="margin-bottom:2rem;">
    <h2 class="admin-form-title">📢 Send Notification</h2>

    <?php foreach ($errors as $e): ?>
    <div class="alert alert-error"><?= clean($e) ?></div>
    <?php endforeach; ?>

    <form method="POST">
        <div class="fg-row">
            <div class="fg">
                <label>Notification Title *</label>
                <input type="text" name="title" placeholder="e.g. 📅 Event Reminder" required maxlength="200">
            </div>
            <div class="fg">
                <label>Type</label>
                <select name="type">
                    <option value="system">System</option>
                    <option value="reminder">Reminder</option>
                    <option value="approval">Approval</option>
                </select>
            </div>
        </div>
        <div class="fg">
            <label>Message *</label>
            <textarea name="body" rows="3" placeholder="Notification message…" required maxlength="500"></textarea>
        </div>
        <div class="fg-row">
            <div class="fg">
                <label>Send To</label>
                <select name="target" id="target_select" onchange="document.getElementById('one_user_row').style.display=this.value==='one'?'block':'none'">
                    <option value="all">All Users</option>
                    <option value="students">Students Only</option>
                    <option value="verified">Verified Users Only</option>
                    <option value="one">Specific User</option>
                </select>
            </div>
            <div class="fg" id="one_user_row" style="display:none;">
                <label>Select User</label>
                <select name="target_user_id">
                    <option value="">— Choose —</option>
                    <?php foreach ($allUsers as $u): ?>
                    <option value="<?= $u['id'] ?>"><?= clean($u['full_name']) ?> (<?= clean($u['reg_number']) ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <button type="submit" name="send_notif" class="btn btn-primary">Send Notification</button>
    </form>
</div>

<!-- Notifications Log -->
<div class="admin-section">
    <div class="admin-sec-hd">
        <h2 class="admin-sec-title">Recent Notifications (<?= count($notifs) ?>)</h2>
    </div>
    <?php if (empty($notifs)): ?>
    <div class="admin-empty">No notifications sent yet.</div>
    <?php else: ?>
    <table class="adata-table">
        <thead><tr><th>To</th><th>Title</th><th>Type</th><th>Read</th><th>Sent</th><th>Action</th></tr></thead>
        <tbody>
        <?php foreach ($notifs as $n): ?>
        <tr>
            <td class="td-bold"><?= clean($n['full_name']) ?> <span class="mono text-muted"><?= clean($n['reg_number']) ?></span></td>
            <td><?= clean($n['title']) ?></td>
            <td><span class="badge-status badge-<?= $n['type'] ?>"><?= ucfirst($n['type']) ?></span></td>
            <td><?= $n['is_read'] ? '<span class="text-green">✓ Read</span>' : '<span class="text-red">Unread</span>' ?></td>
            <td><?= date('d M Y H:i', strtotime($n['created_at'])) ?></td>
            <td>
                <a href="notifications.php?delete=<?= $n['id'] ?>" class="btn btn-sm btn-danger"
                   onclick="return confirm('Delete this notification?')">Delete</a>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

<?php include '../includes/admin_footer.php'; ?>
