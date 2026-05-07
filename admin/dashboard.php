<?php
// ============================================================
// UMU Events — Admin Dashboard
// ============================================================
require_once '../includes/db.php';
require_once '../includes/auth.php';
requireAdmin('../index.php');

$db = getDB();

$totalEvents   = $db->query("SELECT COUNT(*) FROM events")->fetch_row()[0];
$pendingEvents = $db->query("SELECT COUNT(*) FROM events WHERE status='pending'")->fetch_row()[0];
$approvedEvents= $db->query("SELECT COUNT(*) FROM events WHERE status='approved'")->fetch_row()[0];
$rejectedEvents= $db->query("SELECT COUNT(*) FROM events WHERE status='rejected'")->fetch_row()[0];
$totalUsers    = $db->query("SELECT COUNT(*) FROM users WHERE role!='admin'")->fetch_row()[0];
$verifiedUsers = $db->query("SELECT COUNT(*) FROM users WHERE role='verified'")->fetch_row()[0];
$totalRsvps    = $db->query("SELECT COUNT(*) FROM rsvps")->fetch_row()[0];
$totalComments = $db->query("SELECT COUNT(*) FROM comments")->fetch_row()[0];

// Pending events for review
$pending = $db->query("
    SELECT e.*, c.name AS cat_name, c.icon AS cat_icon, u.full_name AS creator
    FROM events e
    JOIN categories c ON c.id=e.category_id
    JOIN users u ON u.id=e.creator_id
    WHERE e.status='pending'
    ORDER BY e.created_at ASC
")->fetch_all(MYSQLI_ASSOC);

// Recent events
$recent = $db->query("
    SELECT e.*, c.name AS cat_name, u.full_name AS creator
    FROM events e
    JOIN categories c ON c.id=e.category_id
    JOIN users u ON u.id=e.creator_id
    ORDER BY e.created_at DESC LIMIT 8
")->fetch_all(MYSQLI_ASSOC);

$pageTitle = 'Admin Dashboard'; $activePage = 'dashboard';
include '../includes/admin_header.php';
?>

<?php renderFlash(); ?>

<!-- Stats Grid -->
<div class="admin-stats-grid">
    <div class="asc asc-yellow"><div class="asc-icon">🎪</div><div class="asc-num"><?= $totalEvents ?></div><div class="asc-lbl">Total Events</div></div>
    <div class="asc asc-red"><div class="asc-icon">⏳</div><div class="asc-num"><?= $pendingEvents ?></div><div class="asc-lbl">Pending Review</div></div>
    <div class="asc asc-black"><div class="asc-icon">✅</div><div class="asc-num"><?= $approvedEvents ?></div><div class="asc-lbl">Approved</div></div>
    <div class="asc asc-white"><div class="asc-icon">👥</div><div class="asc-num"><?= $totalUsers ?></div><div class="asc-lbl">Students</div></div>
    <div class="asc asc-yellow"><div class="asc-icon">⭐</div><div class="asc-num"><?= $verifiedUsers ?></div><div class="asc-lbl">Verified Users</div></div>
    <div class="asc asc-black"><div class="asc-icon">🎟️</div><div class="asc-num"><?= $totalRsvps ?></div><div class="asc-lbl">Total RSVPs</div></div>
</div>

<!-- Quick Actions -->
<div class="admin-quick-row">
    <a href="../create_event.php" class="aqb aqb-primary">+ Create Event</a>
    <a href="events.php" class="aqb aqb-outline">Manage Events</a>
    <a href="users.php" class="aqb aqb-outline">Manage Users</a>
    <a href="notifications.php" class="aqb aqb-outline">Send Notification</a>
</div>

<!-- Pending Events for Approval -->
<?php if (!empty($pending)): ?>
<div class="admin-section">
    <div class="admin-sec-hd">
        <h2 class="admin-sec-title">⏳ Pending Approval (<?= count($pending) ?>)</h2>
    </div>
    <table class="adata-table">
        <thead><tr><th>Event</th><th>Category</th><th>Creator</th><th>Date</th><th>Free?</th><th>Actions</th></tr></thead>
        <tbody>
        <?php foreach ($pending as $ev): ?>
        <tr class="row-pending">
            <td class="td-bold"><a href="../event_detail.php?id=<?= $ev['id'] ?>"><?= clean($ev['title']) ?></a></td>
            <td><?= $ev['cat_icon'] ?> <?= clean($ev['cat_name']) ?></td>
            <td><?= clean($ev['creator']) ?></td>
            <td><?= date('d M Y', strtotime($ev['event_date'])) ?></td>
            <td><?= $ev['is_free']?'<span class="badge-free-sm">Free</span>':'<span class="badge-paid-sm">Paid</span>' ?></td>
            <td class="td-actions">
                <a href="events.php?approve=<?= $ev['id'] ?>" class="btn btn-sm btn-success" onclick="return confirm('Approve this event?')">✓ Approve</a>
                <a href="events.php?action=reject&id=<?= $ev['id'] ?>" class="btn btn-sm btn-danger">✗ Reject</a>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<!-- Recent Events -->
<div class="admin-section">
    <div class="admin-sec-hd">
        <h2 class="admin-sec-title">Recent Events</h2>
        <a href="events.php" class="admin-sec-link">View all →</a>
    </div>
    <table class="adata-table">
        <thead><tr><th>Event</th><th>Category</th><th>Creator</th><th>Date</th><th>Status</th><th>Actions</th></tr></thead>
        <tbody>
        <?php foreach ($recent as $ev): ?>
        <tr>
            <td class="td-bold"><a href="../event_detail.php?id=<?= $ev['id'] ?>"><?= clean($ev['title']) ?></a></td>
            <td><?= clean($ev['cat_name']) ?></td>
            <td><?= clean($ev['creator']) ?></td>
            <td><?= date('d M Y', strtotime($ev['event_date'])) ?></td>
            <td><span class="badge-status badge-<?= $ev['status'] ?>"><?= ucfirst($ev['status']) ?></span></td>
            <td class="td-actions">
                <a href="events.php?action=edit&id=<?= $ev['id'] ?>" class="btn btn-sm btn-outline">Edit</a>
                <a href="events.php?action=delete&id=<?= $ev['id'] ?>" class="btn btn-sm btn-danger"
                   onclick="return confirm('Delete this event permanently?')">Delete</a>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php include '../includes/admin_footer.php'; ?>
