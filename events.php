<?php
// ============================================================
// UMU Events — Browse & Search Events
// ============================================================
require_once 'includes/db.php';
require_once 'includes/auth.php';
requireLogin();

$db     = getDB();
$userId = currentUserId();

// ── HANDLE RSVP ───────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['rsvp_event_id'])) {
    $evId = cleanInt($_POST['rsvp_event_id']);

    $ev = $db->prepare("SELECT id,title,capacity,status FROM events WHERE id=? AND status='approved'");
    $ev->bind_param('i', $evId);
    $ev->execute();
    $event = $ev->get_result()->fetch_assoc();
    $ev->close();

    if (!$event) {
        setFlash('error', 'Event not found or not approved.');
    } else {
        // Check capacity
        $count = $db->query("SELECT COUNT(*) FROM rsvps WHERE event_id=$evId")->fetch_row()[0];
        if ($event['capacity'] && $count >= $event['capacity']) {
            setFlash('error', 'Sorry, this event is at full capacity.');
        } else {
            // Check duplicate
            $dup = $db->prepare("SELECT id FROM rsvps WHERE user_id=? AND event_id=?");
            $dup->bind_param('ii', $userId, $evId);
            $dup->execute();
            $dup->store_result();
            if ($dup->num_rows > 0) {
                setFlash('error', 'You have already RSVPd for this event.');
            } else {
                $ins = $db->prepare("INSERT INTO rsvps (user_id,event_id) VALUES (?,?)");
                $ins->bind_param('ii', $userId, $evId);
                if ($ins->execute()) {
                    createNotification($userId, '✅ RSVP Confirmed', 'You\'ve successfully RSVPd for "' . $event['title'] . '".', 'system', $evId);
                    setFlash('success', 'RSVP confirmed for "' . $event['title'] . '"!');
                } else {
                    setFlash('error', 'Could not process RSVP. Please try again.');
                }
            }
        }
    }
    $redir = ($_POST['redirect'] ?? '') === 'dashboard' ? 'dashboard.php' : 'events.php';
    redirect($redir . (isset($_GET['category']) ? '?category=' . cleanInt($_GET['category']) : '') . (isset($_GET['search']) ? '?search=' . urlencode($_GET['search']) : ''));
}

// ── SEARCH & FILTER ───────────────────────────────────────────
$search   = trim($_GET['search'] ?? '');
$catId    = cleanInt($_GET['category'] ?? 0);
$dateFrom = trim($_GET['date_from'] ?? '');
$dateTo   = trim($_GET['date_to'] ?? '');
$freeOnly = isset($_GET['free']);

$where  = ["e.status = 'approved'", "e.event_date > NOW()"];
$params = []; $types = '';

if (!empty($search)) {
    $where[] = "(e.title LIKE ? OR e.description LIKE ? OR e.location LIKE ?)";
    $s = "%$search%"; $params = array_merge($params, [$s,$s,$s]); $types .= 'sss';
}
if ($catId > 0) {
    $where[] = "e.category_id = ?"; $params[] = $catId; $types .= 'i';
}
if (!empty($dateFrom)) {
    $where[] = "e.event_date >= ?"; $params[] = $dateFrom . ' 00:00:00'; $types .= 's';
}
if (!empty($dateTo)) {
    $where[] = "e.event_date <= ?"; $params[] = $dateTo . ' 23:59:59'; $types .= 's';
}
if ($freeOnly) {
    $where[] = "e.is_free = 1";
}

// Add user_id for i_rsvpd check
$params[] = $userId; $types .= 'i';

$sql = "SELECT e.*, c.name AS cat_name, c.icon AS cat_icon,
               u.full_name AS creator_name,
               (SELECT COUNT(*) FROM rsvps WHERE event_id=e.id) AS rsvp_count,
               (SELECT COUNT(*) FROM rsvps WHERE event_id=e.id AND user_id=?) AS i_rsvpd
        FROM events e
        JOIN categories c ON c.id=e.category_id
        JOIN users u ON u.id=e.creator_id
        WHERE " . implode(' AND ', $where) . "
        ORDER BY e.event_date ASC";

$stmt = $db->prepare($sql);
if (!empty($types)) $stmt->bind_param($types, ...$params);
$stmt->execute();
$events = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$categories = $db->query("SELECT * FROM categories ORDER BY name")->fetch_all(MYSQLI_ASSOC);
$activeCat  = $catId > 0 ? $db->query("SELECT name FROM categories WHERE id=$catId")->fetch_row()[0] : '';

$pageTitle = 'Events'; $activePage = 'events';
include 'includes/header.php';
?>

<div class="page-wrap">

<?php renderFlash(); ?>

<div class="page-hd">
    <h1 class="page-title">All Events</h1>
    <p class="page-sub">Browse and RSVP to upcoming university events</p>
</div>

<!-- Search & Filters -->
<form method="GET" action="events.php" class="events-filter-bar">
    <div class="filter-search-wrap">
        <input type="text" name="search" placeholder="Search events, venues…"
               value="<?= clean($search) ?>" class="filter-search-input">
    </div>
    <select name="category" class="filter-select">
        <option value="">All Categories</option>
        <?php foreach ($categories as $cat): ?>
        <option value="<?= $cat['id'] ?>" <?= $catId===$cat['id']?'selected':'' ?>>
            <?= $cat['icon'] ?> <?= clean($cat['name']) ?>
        </option>
        <?php endforeach; ?>
    </select>
    <input type="date" name="date_from" value="<?= clean($dateFrom) ?>" class="filter-date" title="From date">
    <input type="date" name="date_to"   value="<?= clean($dateTo) ?>"   class="filter-date" title="To date">
    <label class="filter-check">
        <input type="checkbox" name="free" <?= $freeOnly?'checked':'' ?>> Free only
    </label>
    <button type="submit" class="btn btn-primary">Search</button>
    <?php if ($search||$catId||$dateFrom||$dateTo||$freeOnly): ?>
    <a href="events.php" class="btn btn-ghost">Clear</a>
    <?php endif; ?>
</form>

<p class="results-info">
    <?= !empty($activeCat) ? '<span class="cat-filter-label">' . clean($activeCat) . '</span> · ' : '' ?>
    <strong><?= count($events) ?></strong> event<?= count($events)!=1?'s':'' ?> found
    <?= !empty($search) ? ' for "<strong>' . clean($search) . '</strong>"' : '' ?>
</p>

<?php if (empty($events)): ?>
<div class="empty-state">
    <div class="empty-icon">🔍</div>
    <h3>No events match your search</h3>
    <p>Try different keywords or remove some filters.</p>
    <a href="events.php" class="btn btn-primary">View All Events</a>
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
                <div class="event-info-item"><span class="eii-icon">📅</span><?= date('D, d M Y · g:i A', strtotime($ev['event_date'])) ?></div>
                <div class="event-info-item"><span class="eii-icon">📍</span><?= clean($ev['location']) ?></div>
                <div class="event-info-item"><span class="eii-icon">👥</span>
                    <?= $ev['rsvp_count'] ?> attending<?= $ev['capacity'] ? ' · ' . $ev['capacity'] . ' max' : '' ?>
                </div>
            </div>
            <div class="event-card-footer">
                <a href="event_detail.php?id=<?= $ev['id'] ?>" class="btn btn-sm btn-ghost">Details</a>
                <?php if ($ev['i_rsvpd']): ?>
                    <span class="rsvp-confirmed">✓ RSVPd</span>
                <?php elseif (!$ev['capacity'] || $ev['rsvp_count'] < $ev['capacity']): ?>
                    <form method="POST">
                        <input type="hidden" name="rsvp_event_id" value="<?= $ev['id'] ?>">
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

</div>
<?php include 'includes/footer.php'; ?>
