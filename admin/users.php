<?php
// ============================================================
// UMU Events — Admin: Manage Users
// ============================================================
require_once '../includes/db.php';
require_once '../includes/auth.php';
requireAdmin('../index.php');

$db = getDB();

// ── TOGGLE ROLE ───────────────────────────────────────────────
if (isset($_GET['verify'])) {
    $uid = cleanInt($_GET['verify']);
    $cur = $db->query("SELECT role,full_name FROM users WHERE id=$uid AND role!='admin'")->fetch_assoc();
    if ($cur) {
        $newRole = $cur['role']==='verified' ? 'student' : 'verified';
        $db->query("UPDATE users SET role='$newRole' WHERE id=$uid");
        $msg = $newRole==='verified' ? 'Account verified — user can now create events.' : 'Verification removed.';
        createNotification($uid, $newRole==='verified'?'⭐ Account Verified!':'ℹ️ Verification Removed',
            $newRole==='verified'
                ? 'Your account has been verified. You can now create and submit events for approval.'
                : 'Your account verification has been removed by an administrator.', 'system');
        setFlash('success', $msg);
    }
    redirect('users.php');
}

// ── TOGGLE ACTIVE ─────────────────────────────────────────────
if (isset($_GET['toggle_active'])) {
    $uid = cleanInt($_GET['toggle_active']);
    $db->query("UPDATE users SET is_active = 1 - is_active WHERE id=$uid AND role!='admin'");
    setFlash('success', 'User status updated.');
    redirect('users.php');
}

// ── DELETE USER ───────────────────────────────────────────────
if (isset($_GET['delete'])) {
    $uid = cleanInt($_GET['delete']);
    $db->query("DELETE FROM users WHERE id=$uid AND role!='admin'");
    setFlash('success', 'User removed from system.');
    redirect('users.php');
}

// ── SEARCH ────────────────────────────────────────────────────
$search  = trim($_GET['search'] ?? '');
$roleF   = trim($_GET['role'] ?? '');
$where   = ["u.role != 'admin'"]; $params = []; $types = '';

if (!empty($search)) {
    $where[] = "(u.full_name LIKE ? OR u.reg_number LIKE ? OR u.email LIKE ?)";
    $s="%$search%"; $params=array_merge($params,[$s,$s,$s]); $types.='sss';
}
if (!empty($roleF)) {
    $where[] = "u.role=?"; $params[]=$roleF; $types.='s';
}

$sql = "SELECT u.*,
               COUNT(DISTINCT r.id) AS rsvp_count,
               COUNT(DISTINCT e.id) AS event_count
        FROM users u
        LEFT JOIN rsvps r ON r.user_id=u.id
        LEFT JOIN events e ON e.creator_id=u.id
        WHERE ".implode(' AND ',$where)."
        GROUP BY u.id ORDER BY u.created_at DESC";

$stmt = $db->prepare($sql);
if ($types) $stmt->bind_param($types,...$params);
$stmt->execute();
$users = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$pageTitle = 'Users'; $activePage = 'users';
include '../includes/admin_header.php';
?>

<?php renderFlash(); ?>

<div class="admin-list-hd">
    <form method="GET" class="admin-filter-bar">
        <input type="text" name="search" placeholder="Search users…" value="<?= clean($search) ?>" class="filter-input">
        <select name="role" class="filter-select">
            <option value="">All Roles</option>
            <option value="student"  <?= $roleF==='student'?'selected':'' ?>>Student</option>
            <option value="verified" <?= $roleF==='verified'?'selected':'' ?>>Verified</option>
        </select>
        <button class="btn btn-primary btn-sm">Filter</button>
        <?php if ($search||$roleF): ?><a href="users.php" class="btn btn-ghost btn-sm">Clear</a><?php endif; ?>
    </form>
</div>

<p class="results-count">Showing <strong><?= count($users) ?></strong> user<?= count($users)!=1?'s':'' ?></p>

<?php if (empty($users)): ?>
<div class="admin-empty">No users found.</div>
<?php else: ?>
<table class="adata-table">
    <thead>
        <tr><th>#</th><th>Name</th><th>Reg. No.</th><th>Email</th><th>Role</th><th>RSVPs</th><th>Events</th><th>Status</th><th>Actions</th></tr>
    </thead>
    <tbody>
    <?php foreach ($users as $i => $u): ?>
    <tr class="<?= !$u['is_active']?'row-inactive':'' ?>">
        <td><?= $i+1 ?></td>
        <td class="td-bold"><?= clean($u['full_name']) ?></td>
        <td><span class="mono"><?= clean($u['reg_number']) ?></span></td>
        <td><?= clean($u['email']) ?></td>
        <td>
            <span class="role-badge role-<?= $u['role'] ?>"><?= ucfirst($u['role']) ?></span>
        </td>
        <td><?= $u['rsvp_count'] ?></td>
        <td><?= $u['event_count'] ?></td>
        <td>
            <span class="<?= $u['is_active']?'text-green':'text-red' ?>">
                <?= $u['is_active']?'Active':'Inactive' ?>
            </span>
        </td>
        <td class="td-actions">
            <a href="users.php?verify=<?= $u['id'] ?>" class="btn btn-sm <?= $u['role']==='verified'?'btn-outline':'btn-success' ?>"
               onclick="return confirm('<?= $u['role']==='verified'?'Remove verification?':'Verify this user?' ?>')">
               <?= $u['role']==='verified'?'Unverify':'Verify' ?>
            </a>
            <a href="users.php?toggle_active=<?= $u['id'] ?>" class="btn btn-sm btn-outline"
               onclick="return confirm('Toggle active status?')">
               <?= $u['is_active']?'Deactivate':'Activate' ?>
            </a>
            <a href="users.php?delete=<?= $u['id'] ?>" class="btn btn-sm btn-danger"
               onclick="return confirm('Permanently remove this user?')">Remove</a>
        </td>
    </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php endif; ?>

<?php include '../includes/admin_footer.php'; ?>
