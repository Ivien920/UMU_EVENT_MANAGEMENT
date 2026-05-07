<?php
// ============================================================
// UMU Events — My RSVPs
// ============================================================
require_once 'includes/db.php';
require_once 'includes/auth.php';
requireLogin();

$db     = getDB();
$userId = currentUserId();

// Cancel RSVP
if (isset($_GET['cancel']) && ($cId = cleanInt($_GET['cancel']))) {
    $del = $db->prepare("DELETE FROM rsvps WHERE id=? AND user_id=?");
    $del->bind_param('ii', $cId, $userId);
    $del->execute();
    setFlash('success', 'RSVP cancelled.');
    redirect('my_rsvps.php');
}

$filter = trim($_GET['filter'] ?? 'upcoming');

$sql = "SELECT r.id AS rsvp_id, r.rsvp_at, e.id AS event_id,
               e.title, e.event_date, e.location, e.is_free, e.price,
               e.poster, c.name AS cat_name, c.icon AS cat_icon,
               (SELECT COUNT(*) FROM rsvps WHERE event_id=e.id) AS rsvp_count
        FROM rsvps r
        JOIN events e ON e.id=r.event_id
        JOIN categories c ON c.id=e.category_id
        WHERE r.user_id=?";

if ($filter === 'upcoming')  $sql .= " AND e.event_date > NOW()";
elseif ($filter === 'past')  $sql .= " AND e.event_date <= NOW()";

$sql .= " ORDER BY e.event_date " . ($filter === 'past' ? 'DESC' : 'ASC');

$stmt = $db->prepare($sql);
$stmt->bind_param('i', $userId);
$stmt->execute();
$rsvps = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$pageTitle = 'My RSVPs'; $activePage = 'rsvps';
include 'includes/header.php';
?>

<div class="page-wrap">
<?php renderFlash(); ?>

<div class="page-hd">
    <h1 class="page-title">My RSVPs</h1>
    <p class="page-sub">Events you've registered to attend</p>
</div>

<!-- Filter tabs -->
<div class="tab-strip">
    <a href="my_rsvps.php?filter=upcoming" class="tab-btn <?= $filter==='upcoming'?'active':'' ?>">📅 Upcoming</a>
    <a href="my_rsvps.php?filter=past"     class="tab-btn <?= $filter==='past'?'active':'' ?>">🕐 Past</a>
    <a href="my_rsvps.php?filter=all"      class="tab-btn <?= $filter==='all'?'active':'' ?>">📋 All</a>
</div>

<?php if (empty($rsvps)): ?>
<div class="empty-state">
    <div class="empty-icon">🎟️</div>
    <h3>No <?= $filter ?> RSVPs</h3>
    <p>Browse events and RSVP to ones you'd like to attend.</p>
    <a href="events.php" class="btn btn-primary">Browse Events</a>
</div>
<?php else: ?>
<div class="rsvp-list">
    <?php foreach ($rsvps as $r): ?>
    <?php $isPast = strtotime($r['event_date']) < time(); ?>
    <div class="rsvp-list-item <?= $isPast?'rsvp-past':'' ?>">
        <?php if ($r['poster']): ?>
        <div class="rsvp-thumb" style="background-image:url('<?= UPLOAD_URL . clean($r['poster']) ?>')"></div>
        <?php else: ?>
        <div class="rsvp-thumb rsvp-thumb-no-img">
            <span><?= $r['cat_icon'] ?></span>
        </div>
        <?php endif; ?>

        <div class="rsvp-item-body">
            <div class="rsvp-item-meta">
                <span class="event-cat-badge"><?= $r['cat_icon'] ?> <?= clean($r['cat_name']) ?></span>
                <span class="rsvp-date-small">RSVPd <?= date('d M Y', strtotime($r['rsvp_at'])) ?></span>
            </div>
            <h3 class="rsvp-item-title">
                <a href="event_detail.php?id=<?= $r['event_id'] ?>"><?= clean($r['title']) ?></a>
            </h3>
            <div class="rsvp-item-info">
                <span>📅 <?= date('D, d M Y · g:i A', strtotime($r['event_date'])) ?></span>
                <span>📍 <?= clean($r['location']) ?></span>
                <span>👥 <?= $r['rsvp_count'] ?> attending</span>
                <span><?= $r['is_free']?'🟢 Free':'💰 UGX '.number_format($r['price']) ?></span>
            </div>
        </div>

        <div class="rsvp-item-actions">
            <a href="event_detail.php?id=<?= $r['event_id'] ?>" class="btn btn-sm btn-ghost">View →</a>
            <?php if (!$isPast): ?>
            <a href="my_rsvps.php?cancel=<?= $r['rsvp_id'] ?>"
               class="btn btn-sm btn-outline-red"
               onclick="return confirm('Cancel your RSVP for this event?')">Cancel</a>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

</div>
<?php include 'includes/footer.php'; ?>
