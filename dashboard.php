<?php
// ============================================================
// UMU Events — Student Dashboard
// ============================================================
require_once 'includes/db.php';
require_once 'includes/auth.php';
requireLogin();

$db     = getDB();
$userId = currentUserId();

// Stats
$myRsvps   = $db->query("SELECT COUNT(*) FROM rsvps WHERE user_id=$userId")->fetch_row()[0];
$upcoming  = $db->query("SELECT COUNT(*) FROM rsvps r JOIN events e ON e.id=r.event_id WHERE r.user_id=$userId AND e.event_date>NOW() AND e.status='approved'")->fetch_row()[0];
$totalEvts = $db->query("SELECT COUNT(*) FROM events WHERE status='approved'")->fetch_row()[0];

// Upcoming approved events
$eventsStmt = $db->prepare("
    SELECT e.*, c.name AS cat_name, c.icon AS cat_icon,
           u.full_name AS creator_name,
           (SELECT COUNT(*) FROM rsvps WHERE event_id=e.id) AS rsvp_count,
           (SELECT COUNT(*) FROM rsvps WHERE event_id=e.id AND user_id=?) AS i_rsvpd
    FROM events e
    JOIN categories c ON c.id=e.category_id
    JOIN users u ON u.id=e.creator_id
    WHERE e.status='approved' AND e.event_date > NOW()
    ORDER BY e.event_date ASC
    LIMIT 6
");
$eventsStmt->bind_param('i', $userId);
$eventsStmt->execute();
$events = $eventsStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$eventsStmt->close();

// Categories with counts
$cats = $db->query("
    SELECT c.id, c.name, c.icon, COUNT(e.id) AS cnt
    FROM categories c
    LEFT JOIN events e ON e.category_id=c.id AND e.status='approved'
    GROUP BY c.id ORDER BY cnt DESC LIMIT 6
")->fetch_all(MYSQLI_ASSOC);

$pageTitle = 'Home'; $activePage = 'dashboard';
include 'includes/header.php';
?>

<div class="page-wrap">

<?php renderFlash(); ?>

<!-- Hero Banner -->
<section class="hero-banner">
    <div class="hero-content">
        <p class="hero-greeting">Good <?= date('H')<12?'Morning':(date('H')<17?'Afternoon':'Evening') ?>, <?= clean(explode(' ', currentUserName())[0]) ?> 👋</p>
        <h1 class="hero-title">Discover What's<br><em>Happening on Campus</em></h1>
        <p class="hero-sub"><?= $totalEvts ?> approved events &bull; <?= $upcoming ?> in your schedule</p>
        <div class="hero-actions">
            <a href="events.php" class="btn btn-primary">Browse All Events</a>
            <?php if (isVerified()): ?>
            <a href="create_event.php" class="btn btn-outline-white">+ Create Event</a>
            <?php endif; ?>
        </div>
    </div>
    <div class="hero-deco">
        <div class="hero-deco-circle hero-deco-1"></div>
        <div class="hero-deco-circle hero-deco-2"></div>
        <div class="hero-deco-circle hero-deco-3"></div>
    </div>
</section>

<!-- Stats Row -->
<div class="stats-strip">
    <div class="stat-pill">
        <span class="stat-num"><?= $myRsvps ?></span>
        <span class="stat-lbl">My RSVPs</span>
    </div>
    <div class="stat-divider"></div>
    <div class="stat-pill">
        <span class="stat-num"><?= $upcoming ?></span>
        <span class="stat-lbl">Upcoming</span>
    </div>
    <div class="stat-divider"></div>
    <div class="stat-pill">
        <span class="stat-num"><?= $totalEvts ?></span>
        <span class="stat-lbl">Total Events</span>
    </div>
    <div class="stat-divider"></div>
    <div class="stat-pill">
        <a href="events.php" class="stat-cta">View All →</a>
    </div>
</div>

<!-- Categories -->
<section class="dash-section">
    <div class="section-hd">
        <h2 class="section-title">Browse by Category</h2>
    </div>
    <div class="cat-grid">
        <?php foreach ($cats as $cat): ?>
        <a href="events.php?category=<?= $cat['id'] ?>" class="cat-chip">
            <span class="cat-icon"><?= $cat['icon'] ?></span>
            <span class="cat-name"><?= clean($cat['name']) ?></span>
            <span class="cat-count"><?= $cat['cnt'] ?></span>
        </a>
        <?php endforeach; ?>
    </div>
</section>

<!-- Upcoming Events -->
<section class="dash-section">
    <div class="section-hd">
        <h2 class="section-title">Upcoming Events</h2>
        <a href="events.php" class="section-more">View all →</a>
    </div>

    <?php if (empty($events)): ?>
    <div class="empty-state">
        <div class="empty-icon">🎪</div>
        <h3>No upcoming events yet</h3>
        <p>Check back soon or create one if you have a verified account.</p>
    </div>
    <?php else: ?>
    <div class="events-grid">
        <?php foreach ($events as $ev): ?>
        <article class="event-card">
            <?php if ($ev['poster']): ?>
            <div class="event-poster" style="background-image:url('<?= UPLOAD_URL . clean($ev['poster']) ?>')"></div>
            <?php else: ?>
            <div class="event-poster event-poster-no-img">
                <span class="event-poster-icon"><?= $ev['cat_icon'] ?></span>
            </div>
            <?php endif; ?>

            <div class="event-card-body">
                <div class="event-meta-row">
                    <span class="event-cat-badge"><?= $ev['cat_icon'] ?> <?= clean($ev['cat_name']) ?></span>
                    <span class="event-price-badge <?= $ev['is_free']?'badge-free':'badge-paid' ?>">
                        <?= $ev['is_free'] ? 'Free' : 'UGX ' . number_format($ev['price']) ?>
                    </span>
                </div>
                <h3 class="event-title">
                    <a href="event_detail.php?id=<?= $ev['id'] ?>"><?= clean($ev['title']) ?></a>
                </h3>
                <div class="event-info-list">
                    <div class="event-info-item">
                        <span class="eii-icon">📅</span>
                        <?= date('D, d M Y · g:i A', strtotime($ev['event_date'])) ?>
                    </div>
                    <div class="event-info-item">
                        <span class="eii-icon">📍</span>
                        <?= clean($ev['location']) ?>
                    </div>
                    <div class="event-info-item">
                        <span class="eii-icon">👥</span>
                        <?= $ev['rsvp_count'] ?> attending
                        <?= $ev['capacity'] ? ' · ' . $ev['capacity'] . ' capacity' : '' ?>
                    </div>
                </div>
                <div class="event-card-footer">
                    <a href="event_detail.php?id=<?= $ev['id'] ?>" class="btn btn-sm btn-ghost">Details</a>
                    <?php if ($ev['i_rsvpd']): ?>
                        <span class="rsvp-confirmed">✓ RSVPd</span>
                    <?php elseif (!$ev['capacity'] || $ev['rsvp_count'] < $ev['capacity']): ?>
                        <form method="POST" action="events.php">
                            <input type="hidden" name="rsvp_event_id" value="<?= $ev['id'] ?>">
                            <input type="hidden" name="redirect" value="dashboard">
                            <button type="submit" class="btn btn-sm btn-primary">RSVP</button>
                        </form>
                    <?php else: ?>
                        <span class="badge-full">Full</span>
                    <?php endif; ?>
                </div>
            </div>
        </article>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</section>

</div><!-- /.page-wrap -->

<?php include 'includes/footer.php'; ?>
