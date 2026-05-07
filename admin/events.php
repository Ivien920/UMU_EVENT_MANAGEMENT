<?php
// ============================================================
// UMU Events — Admin: Manage All Events
// ============================================================
require_once '../includes/db.php';
require_once '../includes/auth.php';
requireAdmin('../index.php');

$db     = getDB();
$action = $_GET['action'] ?? 'list';
$evId   = cleanInt($_GET['id'] ?? 0);
$errors = [];

// ── APPROVE ───────────────────────────────────────────────────
if (isset($_GET['approve'])) {
    $id = cleanInt($_GET['approve']);
    $db->query("UPDATE events SET status='approved' WHERE id=$id");
    // Notify creator
    $ev = $db->query("SELECT title,creator_id FROM events WHERE id=$id")->fetch_assoc();
    if ($ev) {
        createNotification($ev['creator_id'],'✅ Event Approved!','"'.$ev['title'].'" has been approved and is now live on the platform.','approval',$id);
    }
    setFlash('success','Event approved and published.');
    redirect('events.php');
}

// ── REJECT ────────────────────────────────────────────────────
if ($action === 'reject' && $evId) {
    if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['do_reject'])) {
        $reason = trim($_POST['rejection_reason']??'');
        $db->prepare("UPDATE events SET status='rejected',rejection_reason=? WHERE id=?")->bind_param('si',$reason,$evId) && true;
        $stmt2 = $db->prepare("UPDATE events SET status='rejected',rejection_reason=? WHERE id=?");
        $stmt2->bind_param('si',$reason,$evId); $stmt2->execute();
        // Notify
        $ev2 = $db->query("SELECT title,creator_id FROM events WHERE id=$evId")->fetch_assoc();
        if ($ev2) createNotification($ev2['creator_id'],'❌ Event Not Approved','"'.$ev2['title'].'" was not approved. '.($reason?'Reason: '.$reason:''),'rejection',$evId);
        setFlash('success','Event rejected.'); redirect('events.php');
    }
}

// ── DELETE ────────────────────────────────────────────────────
if ($action === 'delete' && $evId) {
    // Delete poster file
    $row = $db->query("SELECT poster FROM events WHERE id=$evId")->fetch_assoc();
    if ($row && $row['poster'] && file_exists(UPLOAD_DIR.$row['poster'])) unlink(UPLOAD_DIR.$row['poster']);
    $db->query("DELETE FROM events WHERE id=$evId");
    setFlash('success','Event deleted.'); redirect('events.php');
}

// ── EDIT (redirect to create_event.php) ───────────────────────
if ($action === 'edit' && $evId) {
    redirect('../create_event.php?edit='.$evId);
}

// ── FETCH EVENT FOR REJECT FORM ───────────────────────────────
$rejectEvent = null;
if ($action === 'reject' && $evId) {
    $rejectEvent = $db->query("SELECT * FROM events WHERE id=$evId")->fetch_assoc();
}

// ── SEARCH & LIST ─────────────────────────────────────────────
$search    = trim($_GET['search'] ?? '');
$statusF   = trim($_GET['status'] ?? '');
$where     = ['1=1']; $params = []; $types = '';

if (!empty($search)) {
    $where[] = "(e.title LIKE ? OR e.location LIKE ? OR u.full_name LIKE ?)";
    $s="%$search%"; $params=array_merge($params,[$s,$s,$s]); $types.='sss';
}
if (!empty($statusF)) {
    $where[] = "e.status=?"; $params[]=$statusF; $types.='s';
}

$sql = "SELECT e.*,c.name AS cat_name,c.icon AS cat_icon,u.full_name AS creator,
               (SELECT COUNT(*) FROM rsvps WHERE event_id=e.id) AS rsvp_count
        FROM events e
        JOIN categories c ON c.id=e.category_id
        JOIN users u ON u.id=e.creator_id
        WHERE ".implode(' AND ',$where)."
        ORDER BY e.created_at DESC";

$stmt = $db->prepare($sql);
if ($types) $stmt->bind_param($types,...$params);
$stmt->execute();
$events = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$pageTitle = 'All Events'; $activePage = 'events';
include '../includes/admin_header.php';
?>

<?php renderFlash(); ?>

<?php if ($action === 'reject' && $rejectEvent): ?>
<!-- Reject Form Modal-style -->
<div class="admin-form-card">
    <h2 class="admin-form-title">Reject Event</h2>
    <p>You are rejecting: <strong><?= clean($rejectEvent['title']) ?></strong></p>
    <form method="POST">
        <div class="fg">
            <label>Reason for Rejection (optional)</label>
            <textarea name="rejection_reason" rows="3" placeholder="Provide feedback to the event creator…"></textarea>
        </div>
        <div class="fg-actions">
            <button type="submit" name="do_reject" class="btn btn-danger">Confirm Rejection</button>
            <a href="events.php" class="btn btn-ghost">Cancel</a>
        </div>
    </form>
</div>

<?php else: ?>

<!-- Filter Bar -->
<div class="admin-list-hd">
    <form method="GET" class="admin-filter-bar">
        <input type="text" name="search" placeholder="Search events…" value="<?= clean($search) ?>" class="filter-input">
        <select name="status" class="filter-select">
            <option value="">All Statuses</option>
            <option value="pending"  <?= $statusF==='pending'?'selected':'' ?>>Pending</option>
            <option value="approved" <?= $statusF==='approved'?'selected':'' ?>>Approved</option>
            <option value="rejected" <?= $statusF==='rejected'?'selected':'' ?>>Rejected</option>
        </select>
        <button class="btn btn-primary btn-sm">Filter</button>
        <?php if ($search||$statusF): ?><a href="events.php" class="btn btn-ghost btn-sm">Clear</a><?php endif; ?>
    </form>
    <a href="../create_event.php" class="btn btn-primary btn-sm">+ Add Event</a>
</div>

<p class="results-count">Showing <strong><?= count($events) ?></strong> events</p>

<?php if (empty($events)): ?>
<div class="admin-empty">No events found.</div>
<?php else: ?>
<table class="adata-table">
    <thead>
        <tr><th>Title</th><th>Category</th><th>Creator</th><th>Date</th><th>RSVPs</th><th>Free?</th><th>Status</th><th>Actions</th></tr>
    </thead>
    <tbody>
    <?php foreach ($events as $ev): ?>
    <tr class="<?= $ev['status']==='pending'?'row-pending':'' ?>">
        <td class="td-bold"><a href="../event_detail.php?id=<?= $ev['id'] ?>"><?= clean($ev['title']) ?></a></td>
        <td><?= $ev['cat_icon'] ?> <?= clean($ev['cat_name']) ?></td>
        <td><?= clean($ev['creator']) ?></td>
        <td><?= date('d M Y', strtotime($ev['event_date'])) ?></td>
        <td><?= $ev['rsvp_count'] ?></td>
        <td><?= $ev['is_free']?'<span class="badge-free-sm">Free</span>':'<span class="badge-paid-sm">UGX '.number_format($ev['price']).'</span>' ?></td>
        <td><span class="badge-status badge-<?= $ev['status'] ?>"><?= ucfirst($ev['status']) ?></span></td>
        <td class="td-actions">
            <?php if ($ev['status']==='pending'): ?>
            <a href="events.php?approve=<?= $ev['id'] ?>" class="btn btn-sm btn-success" onclick="return confirm('Approve?')">✓</a>
            <a href="events.php?action=reject&id=<?= $ev['id'] ?>" class="btn btn-sm btn-danger">✗</a>
            <?php endif; ?>
            <a href="events.php?action=edit&id=<?= $ev['id'] ?>" class="btn btn-sm btn-outline">Edit</a>
            <a href="events.php?action=delete&id=<?= $ev['id'] ?>" class="btn btn-sm btn-danger"
               onclick="return confirm('Delete this event?')">Del</a>
        </td>
    </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php endif; ?>
<?php endif; ?>

<?php include '../includes/admin_footer.php'; ?>
