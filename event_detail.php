<?php
// ============================================================
// UMU Events — Event Detail + Comments CRUD
// ============================================================
require_once 'includes/db.php';
require_once 'includes/auth.php';
requireLogin();

$db     = getDB();
$userId = currentUserId();
$evId   = cleanInt($_GET['id'] ?? 0);
if (!$evId) { redirect('events.php'); }

// ── FETCH EVENT ───────────────────────────────────────────────
$stmt = $db->prepare("
    SELECT e.*, c.name AS cat_name, c.icon AS cat_icon,
           u.full_name AS creator_name, u.role AS creator_role,
           (SELECT COUNT(*) FROM rsvps WHERE event_id=e.id) AS rsvp_count,
           (SELECT COUNT(*) FROM rsvps WHERE event_id=e.id AND user_id=?) AS i_rsvpd
    FROM events e
    JOIN categories c ON c.id=e.category_id
    JOIN users u ON u.id=e.creator_id
    WHERE e.id=?
");
$stmt->bind_param('ii', $userId, $evId);
$stmt->execute();
$event = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$event || ($event['status'] !== 'approved' && !isAdmin() && $event['creator_id'] != $userId)) {
    setFlash('error', 'Event not found or not available.');
    redirect('events.php');
}

// ── HANDLE RSVP ───────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['do_rsvp'])) {
    if ($event['status'] !== 'approved') {
        setFlash('error', 'Can only RSVP to approved events.');
    } elseif ($event['i_rsvpd']) {
        setFlash('error', 'You have already RSVPd.');
    } elseif ($event['capacity'] && $event['rsvp_count'] >= $event['capacity']) {
        setFlash('error', 'Event is at full capacity.');
    } else {
        $ins = $db->prepare("INSERT INTO rsvps (user_id,event_id) VALUES (?,?)");
        $ins->bind_param('ii', $userId, $evId);
        if ($ins->execute()) {
            createNotification($userId, '✅ RSVP Confirmed', 'You\'ve RSVPd for "' . $event['title'] . '".', 'system', $evId);
            setFlash('success', 'RSVP confirmed!');
        }
    }
    redirect("event_detail.php?id=$evId");
}

// ── CANCEL RSVP ───────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_rsvp'])) {
    $del = $db->prepare("DELETE FROM rsvps WHERE user_id=? AND event_id=?");
    $del->bind_param('ii', $userId, $evId);
    $del->execute();
    setFlash('success', 'RSVP cancelled.');
    redirect("event_detail.php?id=$evId");
}

// ── ADD COMMENT ───────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_comment'])) {
    $body = trim($_POST['comment_body'] ?? '');
    if (empty($body)) {
        setFlash('error', 'Comment cannot be empty.');
    } elseif (strlen($body) > 1000) {
        setFlash('error', 'Comment too long (max 1000 characters).');
    } else {
        $ins = $db->prepare("INSERT INTO comments (event_id,user_id,body) VALUES (?,?,?)");
        $ins->bind_param('iis', $evId, $userId, $body);
        $ins->execute();
        setFlash('success', 'Comment posted.');
    }
    redirect("event_detail.php?id=$evId#comments");
}

// ── EDIT COMMENT ──────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_comment_id'])) {
    $cId  = cleanInt($_POST['edit_comment_id']);
    $body = trim($_POST['edit_comment_body'] ?? '');
    if (empty($body)) {
        setFlash('error', 'Comment cannot be empty.');
    } else {
        // Only owner or admin can edit
        $upd = $db->prepare("UPDATE comments SET body=? WHERE id=? AND (user_id=? OR ?=1)");
        $isAdm = isAdmin() ? 1 : 0;
        $upd->bind_param('siii', $body, $cId, $userId, $isAdm);
        $upd->execute();
        setFlash('success', 'Comment updated.');
    }
    redirect("event_detail.php?id=$evId#comments");
}

// ── DELETE COMMENT ────────────────────────────────────────────
if (isset($_GET['delete_comment'])) {
    $cId = cleanInt($_GET['delete_comment']);
    $del = $db->prepare("DELETE FROM comments WHERE id=? AND (user_id=? OR ?=1)");
    $isAdm = isAdmin() ? 1 : 0;
    $del->bind_param('iii', $cId, $userId, $isAdm);
    $del->execute();
    setFlash('success', 'Comment deleted.');
    redirect("event_detail.php?id=$evId#comments");
}

// ── FETCH COMMENTS ────────────────────────────────────────────
$cStmt = $db->prepare("
    SELECT cm.*, u.full_name, u.reg_number, u.role
    FROM comments cm
    JOIN users u ON u.id=cm.user_id
    WHERE cm.event_id=?
    ORDER BY cm.created_at ASC
");
$cStmt->bind_param('i', $evId);
$cStmt->execute();
$comments = $cStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$cStmt->close();

$editCId = cleanInt($_GET['edit_comment'] ?? 0);

$pageTitle = clean($event['title']); $activePage = 'events';
include 'includes/header.php';
?>

<div class="page-wrap">
<?php renderFlash(); ?>

<!-- Back -->
<a href="events.php" class="back-link">← Back to Events</a>

<div class="detail-layout">

    <!-- Main -->
    <div class="detail-main">

        <!-- Poster -->
        <?php if ($event['poster']): ?>
        <div class="detail-poster">
            <img src="<?= UPLOAD_URL . clean($event['poster']) ?>" alt="<?= clean($event['title']) ?> poster">
        </div>
        <?php else: ?>
        <div class="detail-poster-placeholder">
            <span><?= $event['cat_icon'] ?></span>
        </div>
        <?php endif; ?>

        <!-- Status Banner (pending/rejected) -->
        <?php if ($event['status'] === 'pending'): ?>
        <div class="status-banner status-pending">⏳ This event is pending admin approval.</div>
        <?php elseif ($event['status'] === 'rejected'): ?>
        <div class="status-banner status-rejected">❌ This event was rejected.
            <?= $event['rejection_reason'] ? ' Reason: ' . clean($event['rejection_reason']) : '' ?>
        </div>
        <?php endif; ?>

        <!-- Event Header -->
        <div class="detail-header">
            <div class="detail-badges">
                <span class="event-cat-badge"><?= $event['cat_icon'] ?> <?= clean($event['cat_name']) ?></span>
                <span class="event-price-badge <?= $event['is_free']?'badge-free':'badge-paid' ?>">
                    <?= $event['is_free'] ? 'Free Entry' : 'UGX ' . number_format($event['price']) ?>
                </span>
            </div>
            <h1 class="detail-title"><?= clean($event['title']) ?></h1>
            <p class="detail-creator">Posted by <strong><?= clean($event['creator_name']) ?></strong>
                <?= $event['creator_role']==='admin' ? '<span class="role-tag">Admin</span>' : ($event['creator_role']==='verified'?'<span class="role-tag role-verified">Verified</span>':'') ?>
            </p>
        </div>

        <!-- Event Info Grid -->
        <div class="detail-info-grid">
            <div class="detail-info-item">
                <span class="dii-icon">📅</span>
                <div>
                    <div class="dii-label">Date & Time</div>
                    <div class="dii-value"><?= date('l, d F Y', strtotime($event['event_date'])) ?></div>
                    <div class="dii-sub"><?= date('g:i A', strtotime($event['event_date'])) ?></div>
                </div>
            </div>
            <div class="detail-info-item">
                <span class="dii-icon">📍</span>
                <div>
                    <div class="dii-label">Location</div>
                    <div class="dii-value"><?= clean($event['location']) ?></div>
                </div>
            </div>
            <div class="detail-info-item">
                <span class="dii-icon">👥</span>
                <div>
                    <div class="dii-label">Attendance</div>
                    <div class="dii-value"><?= $event['rsvp_count'] ?> attending</div>
                    <div class="dii-sub"><?= $event['capacity'] ? 'Capacity: ' . $event['capacity'] : 'Unlimited capacity' ?></div>
                </div>
            </div>
            <div class="detail-info-item">
                <span class="dii-icon">💬</span>
                <div>
                    <div class="dii-label">Comments</div>
                    <div class="dii-value"><?= count($comments) ?> comments</div>
                </div>
            </div>
        </div>

        <!-- Description -->
        <div class="detail-section">
            <h2 class="detail-section-title">About This Event</h2>
            <div class="detail-description"><?= nl2br(clean($event['description'])) ?></div>
        </div>

        <!-- ── COMMENTS ────────────────────────────────────── -->
        <div class="detail-section" id="comments">
            <h2 class="detail-section-title">Comments (<?= count($comments) ?>)</h2>

            <!-- Add Comment Form -->
            <form method="POST" class="comment-form">
                <div class="comment-form-row">
                    <div class="comment-avatar"><?= strtoupper(substr(currentUserName(), 0, 1)) ?></div>
                    <div class="comment-input-wrap">
                        <textarea name="comment_body" placeholder="Share your thoughts about this event…" rows="3" maxlength="1000" required></textarea>
                        <div class="comment-form-actions">
                            <button type="submit" name="add_comment" class="btn btn-sm btn-primary">Post Comment</button>
                        </div>
                    </div>
                </div>
            </form>

            <!-- Comment List -->
            <?php if (empty($comments)): ?>
            <div class="comment-empty">No comments yet. Be the first to comment!</div>
            <?php else: ?>
            <div class="comment-list">
                <?php foreach ($comments as $cm): ?>
                <div class="comment-item" id="comment-<?= $cm['id'] ?>">
                    <?php if ($editCId === $cm['id'] && ($cm['user_id'] === $userId || isAdmin())): ?>
                    <!-- Edit form -->
                    <form method="POST" class="comment-edit-form">
                        <textarea name="edit_comment_body" rows="3" maxlength="1000" required><?= clean($cm['body']) ?></textarea>
                        <div class="comment-edit-actions">
                            <input type="hidden" name="edit_comment_id" value="<?= $cm['id'] ?>">
                            <button type="submit" class="btn btn-sm btn-primary">Save</button>
                            <a href="event_detail.php?id=<?= $evId ?>#comments" class="btn btn-sm btn-ghost">Cancel</a>
                        </div>
                    </form>
                    <?php else: ?>
                    <div class="comment-avatar"><?= strtoupper(substr($cm['full_name'], 0, 1)) ?></div>
                    <div class="comment-body-wrap">
                        <div class="comment-meta">
                            <strong><?= clean($cm['full_name']) ?></strong>
                            <span class="comment-reg"><?= clean($cm['reg_number']) ?></span>
                            <span class="comment-time"><?= timeAgo($cm['created_at']) ?></span>
                            <?php if ($cm['created_at'] !== $cm['updated_at']): ?>
                                <span class="comment-edited">(edited)</span>
                            <?php endif; ?>
                        </div>
                        <p class="comment-text"><?= nl2br(clean($cm['body'])) ?></p>
                        <?php if ($cm['user_id'] === $userId || isAdmin()): ?>
                        <div class="comment-actions">
                            <a href="event_detail.php?id=<?= $evId ?>&edit_comment=<?= $cm['id'] ?>#comment-<?= $cm['id'] ?>" class="comment-action-btn">Edit</a>
                            <a href="event_detail.php?id=<?= $evId ?>&delete_comment=<?= $cm['id'] ?>"
                               class="comment-action-btn comment-delete-btn"
                               onclick="return confirm('Delete this comment?')">Delete</a>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

    </div><!-- /.detail-main -->

    <!-- Sidebar -->
    <aside class="detail-sidebar">

        <!-- RSVP Card -->
        <?php if ($event['status'] === 'approved'): ?>
        <div class="rsvp-card">
            <h3 class="rsvp-card-title">Attend This Event</h3>
            <div class="rsvp-progress-wrap">
                <?php $pct = $event['capacity'] ? min(100, round($event['rsvp_count']/$event['capacity']*100)) : 0; ?>
                <?php if ($event['capacity']): ?>
                <div class="rsvp-progress-bar">
                    <div class="rsvp-progress-fill" style="width:<?= $pct ?>%"></div>
                </div>
                <p class="rsvp-slots"><?= $event['rsvp_count'] ?>/<?= $event['capacity'] ?> spots taken</p>
                <?php else: ?>
                <p class="rsvp-slots"><?= $event['rsvp_count'] ?> attending · Unlimited spots</p>
                <?php endif; ?>
            </div>
            <?php if ($event['i_rsvpd']): ?>
                <div class="rsvp-confirmed-box">✓ You're attending this event!</div>
                <form method="POST">
                    <button type="submit" name="cancel_rsvp" class="btn btn-full btn-outline-red"
                            onclick="return confirm('Cancel your RSVP?')">Cancel RSVP</button>
                </form>
            <?php elseif ($event['capacity'] && $event['rsvp_count'] >= $event['capacity']): ?>
                <div class="rsvp-full-box">This event is at full capacity.</div>
            <?php else: ?>
                <form method="POST">
                    <button type="submit" name="do_rsvp" class="btn btn-full btn-primary">RSVP Now</button>
                </form>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- Admin Actions -->
        <?php if (isAdmin() || $event['creator_id'] === $userId): ?>
        <div class="sidebar-card admin-actions-card">
            <h3 class="sidebar-card-title">⚙️ Event Actions</h3>
            <?php if (isAdmin()): ?>
            <a href="admin/events.php?action=edit&id=<?= $evId ?>" class="btn btn-full btn-outline mb-sm">Edit Event</a>
            <a href="admin/events.php?action=delete&id=<?= $evId ?>"
               class="btn btn-full btn-danger"
               data-confirm="Permanently delete this event?"
               onclick="return confirm('Permanently delete this event?')">Delete Event</a>
            <?php elseif ($event['creator_id'] === $userId): ?>
            <a href="create_event.php?edit=<?= $evId ?>" class="btn btn-full btn-outline mb-sm">Edit My Event</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- Event Info Summary -->
        <div class="sidebar-card">
            <h3 class="sidebar-card-title">Quick Info</h3>
            <ul class="sidebar-info-list">
                <li><span>📅</span> <?= date('d M Y', strtotime($event['event_date'])) ?></li>
                <li><span>⏰</span> <?= date('g:i A', strtotime($event['event_date'])) ?></li>
                <li><span>📍</span> <?= clean($event['location']) ?></li>
                <li><span>💰</span> <?= $event['is_free']?'Free':'UGX '.number_format($event['price']) ?></li>
                <li><span>👥</span> <?= $event['capacity']??'Unlimited' ?> capacity</li>
                <li><span>📅</span> Posted <?= date('d M Y', strtotime($event['created_at'])) ?></li>
            </ul>
        </div>

    </aside><!-- /.detail-sidebar -->

</div><!-- /.detail-layout -->
</div><!-- /.page-wrap -->

<?php include 'includes/footer.php'; ?>
