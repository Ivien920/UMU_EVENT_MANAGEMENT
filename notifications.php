<?php
// ============================================================
// UMU Events — Notifications (Daily Reminders)
// ============================================================
require_once 'includes/db.php';
require_once 'includes/auth.php';
requireLogin();

$db     = getDB();
$userId = currentUserId();

// Mark all as read
if (isset($_GET['mark_read'])) {
    $db->query("UPDATE notifications SET is_read=1 WHERE user_id=$userId");
    setFlash('success', 'All notifications marked as read.');
    redirect('notifications.php');
}

// Delete one
if (isset($_GET['delete']) && ($dId = cleanInt($_GET['delete']))) {
    $del = $db->prepare("DELETE FROM notifications WHERE id=? AND user_id=?");
    $del->bind_param('ii', $dId, $userId);
    $del->execute();
    redirect('notifications.php');
}

// Mark one read and redirect
if (isset($_GET['read']) && isset($_GET['go'])) {
    $rId = cleanInt($_GET['read']);
    $db->prepare("UPDATE notifications SET is_read=1 WHERE id=? AND user_id=?")->bind_param('ii',$rId,$userId) && true;
    redirect($_GET['go']);
}

// Mark single as read on open
if (isset($_GET['read_id'])) {
    $rid = cleanInt($_GET['read_id']);
    $upd = $db->prepare("UPDATE notifications SET is_read=1 WHERE id=? AND user_id=?");
    $upd->bind_param('ii', $rid, $userId);
    $upd->execute();
}

// Fetch all notifications
$stmt = $db->prepare("SELECT n.*, e.title AS event_title FROM notifications n LEFT JOIN events e ON e.id=n.event_id WHERE n.user_id=? ORDER BY n.created_at DESC");
$stmt->bind_param('i', $userId);
$stmt->execute();
$notifs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$unreadCount = array_filter($notifs, fn($n) => !$n['is_read']);

$pageTitle = 'Notifications'; $activePage = 'notifs';
include 'includes/header.php';
?>

<div class="page-wrap">
<?php renderFlash(); ?>

<div class="page-hd">
    <div>
        <h1 class="page-title">Notifications</h1>
        <p class="page-sub"><?= count($unreadCount) ?> unread · <?= count($notifs) ?> total</p>
    </div>
    <?php if (!empty($unreadCount)): ?>
    <a href="notifications.php?mark_read=1" class="btn btn-ghost btn-sm">Mark all as read</a>
    <?php endif; ?>
</div>

<?php if (empty($notifs)): ?>
<div class="empty-state">
    <div class="empty-icon">🔔</div>
    <h3>No notifications yet</h3>
    <p>RSVP to events to start receiving daily reminders.</p>
    <a href="events.php" class="btn btn-primary">Browse Events</a>
</div>
<?php else: ?>
<div class="notif-list">
    <?php foreach ($notifs as $n): ?>
    <?php
    $typeIcons = ['reminder'=>'📅','approval'=>'✅','rejection'=>'❌','comment'=>'💬','system'=>'🔔'];
    $icon = $typeIcons[$n['type']] ?? '🔔';
    $link = $n['event_id'] ? "event_detail.php?id={$n['event_id']}" : '#';
    ?>
    <div class="notif-item <?= $n['is_read']?'notif-read':'notif-unread' ?>">
        <div class="notif-icon notif-icon-<?= $n['type'] ?>"><?= $icon ?></div>
        <div class="notif-body">
            <div class="notif-title"><?= clean($n['title']) ?></div>
            <p class="notif-text"><?= clean($n['body']) ?></p>
            <?php if ($n['event_title']): ?>
            <a href="<?= $link ?>" class="notif-event-link">📎 <?= clean($n['event_title']) ?></a>
            <?php endif; ?>
            <span class="notif-time"><?= timeAgo($n['created_at']) ?></span>
        </div>
        <div class="notif-actions">
            <?php if ($n['event_id']): ?>
            <a href="<?= $link ?>" class="notif-action-link">View →</a>
            <?php endif; ?>
            <a href="notifications.php?delete=<?= $n['id'] ?>"
               class="notif-delete-btn" title="Delete"
               onclick="return confirm('Delete this notification?')">🗑</a>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

</div>
<?php include 'includes/footer.php'; ?>
