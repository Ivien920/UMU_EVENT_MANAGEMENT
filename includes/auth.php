<?php
// ============================================================
// UMU Events — Auth & Session Helpers
// ============================================================

if (session_status() === PHP_SESSION_NONE) session_start();

function redirect(string $url): void { header("Location: $url"); exit(); }

// ── Guards ────────────────────────────────────────────────────
function requireLogin(string $back = '/index.php'): void {
    if (!isset($_SESSION['user_id'])) redirect($back);
}
function requireAdmin(string $back = '/index.php'): void {
    requireLogin($back);
    if ($_SESSION['role'] !== 'admin') redirect('/dashboard.php');
}
function requireCanCreate(): void {
    requireLogin();
    if (!in_array($_SESSION['role'], ['admin','verified'])) {
        setFlash('error', 'Only verified accounts and admins can create events.');
        redirect('dashboard.php');
    }
}

// ── State checks ──────────────────────────────────────────────
function isLoggedIn(): bool  { return isset($_SESSION['user_id']); }
function isAdmin(): bool     { return ($_SESSION['role'] ?? '') === 'admin'; }
function isVerified(): bool  { return in_array($_SESSION['role'] ?? '', ['admin','verified']); }
function currentUserId(): int        { return (int)($_SESSION['user_id'] ?? 0); }
function currentUserName(): string   { return $_SESSION['full_name'] ?? 'Guest'; }
function currentUserRole(): string   { return $_SESSION['role'] ?? 'student'; }
function currentUserRegNo(): string  { return $_SESSION['reg_number'] ?? ''; }

// ── Flash messages ────────────────────────────────────────────
function setFlash(string $type, string $msg): void {
    $_SESSION['flash'] = ['type' => $type, 'message' => $msg];
}
function getFlash(): ?array {
    if (isset($_SESSION['flash'])) { $f = $_SESSION['flash']; unset($_SESSION['flash']); return $f; }
    return null;
}
function renderFlash(): void {
    $f = getFlash();
    if ($f) echo '<div class="alert alert-' . htmlspecialchars($f['type']) . '">'
                . htmlspecialchars($f['message']) . '</div>';
}

// ── Sanitize ──────────────────────────────────────────────────
function clean(string $s): string { return htmlspecialchars(trim($s), ENT_QUOTES, 'UTF-8'); }
function cleanInt($v): int        { return (int)$v; }

// ── Notification helpers ──────────────────────────────────────
function countUnread(int $userId): int {
    $db   = getDB();
    $stmt = $db->prepare("SELECT COUNT(*) FROM notifications WHERE user_id=? AND is_read=0");
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    return (int)$stmt->get_result()->fetch_row()[0];
}

function createNotification(int $userId, string $title, string $body, string $type = 'system', ?int $eventId = null): void {
    $db   = getDB();
    $stmt = $db->prepare("INSERT INTO notifications (user_id,title,body,type,event_id) VALUES (?,?,?,?,?)");
    $stmt->bind_param('isssi', $userId, $title, $body, $type, $eventId);
    $stmt->execute();
}

// ── Send daily reminder notifications (call via cron or on login) ──
function sendDailyReminders(): void {
    $db = getDB();
    // Get approved events happening in the next 3 days
    $events = $db->query("
        SELECT e.id, e.title, e.event_date, e.location, r.user_id
        FROM events e
        JOIN rsvps r ON r.event_id = e.id
        WHERE e.status = 'approved'
          AND e.event_date BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 3 DAY)
    ")->fetch_all(MYSQLI_ASSOC);

    foreach ($events as $ev) {
        // Avoid duplicate reminders (same user+event today)
        $check = $db->prepare("
            SELECT id FROM notifications
            WHERE user_id=? AND event_id=? AND type='reminder'
              AND DATE(created_at)=CURDATE()
        ");
        $check->bind_param('ii', $ev['user_id'], $ev['id']);
        $check->execute();
        if ($check->get_result()->num_rows === 0) {
            $date = date('d M Y, g:i A', strtotime($ev['event_date']));
            createNotification(
                $ev['user_id'],
                '📅 Reminder: ' . $ev['title'],
                'This event you RSVPd for is coming up soon — ' . $date . ' at ' . $ev['location'] . '.',
                'reminder',
                $ev['id']
            );
        }
    }
}

// ── Time ago helper ───────────────────────────────────────────
function timeAgo(string $datetime): string {
    $diff = time() - strtotime($datetime);
    if ($diff < 60)     return 'Just now';
    if ($diff < 3600)   return floor($diff/60) . 'm ago';
    if ($diff < 86400)  return floor($diff/3600) . 'h ago';
    if ($diff < 604800) return floor($diff/86400) . 'd ago';
    return date('d M Y', strtotime($datetime));
}
?>
