<?php
// ============================================================
// UMU Events — User Profile
// ============================================================
require_once 'includes/db.php';
require_once 'includes/auth.php';
requireLogin();

$db     = getDB();
$userId = currentUserId();
$errors = [];

// Fetch user
$stmt = $db->prepare("SELECT * FROM users WHERE id=?");
$stmt->bind_param('i', $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Stats
$totalRsvps   = $db->query("SELECT COUNT(*) FROM rsvps WHERE user_id=$userId")->fetch_row()[0];
$totalEvents  = $db->query("SELECT COUNT(*) FROM events WHERE creator_id=$userId")->fetch_row()[0];
$totalComments = $db->query("SELECT COUNT(*) FROM comments WHERE user_id=$userId")->fetch_row()[0];

// Update profile
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $fullName = trim($_POST['full_name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $newPass  = $_POST['new_password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    if (empty($fullName)) $errors[] = 'Full name is required.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email is required.';
    if (!empty($newPass)) {
        if (strlen($newPass) < 6) $errors[] = 'Password must be at least 6 characters.';
        if ($newPass !== $confirm) $errors[] = 'Passwords do not match.';
    }

    if (empty($errors)) {
        $hash = !empty($newPass) ? password_hash($newPass, PASSWORD_BCRYPT) : $user['password'];
        $upd  = $db->prepare("UPDATE users SET full_name=?,email=?,password=? WHERE id=?");
        $upd->bind_param('sssi', $fullName, $email, $hash, $userId);
        if ($upd->execute()) {
            $_SESSION['full_name'] = $fullName;
            setFlash('success', 'Profile updated successfully.');
            redirect('profile.php');
        } else {
            $errors[] = 'Update failed. Email may already be in use.';
        }
    }
}

$pageTitle = 'My Profile'; $activePage = '';
include 'includes/header.php';
?>

<div class="page-wrap">
<?php renderFlash(); ?>

<div class="profile-layout">

    <!-- Profile Card -->
    <aside class="profile-card">
        <div class="profile-avatar-lg"><?= strtoupper(substr($user['full_name'],0,1)) ?></div>
        <h2 class="profile-name"><?= clean($user['full_name']) ?></h2>
        <p class="profile-reg"><?= clean($user['reg_number']) ?></p>
        <span class="role-badge role-<?= $user['role'] ?>"><?= ucfirst($user['role']) ?></span>
        <div class="profile-stats">
            <div class="profile-stat"><span class="ps-num"><?= $totalRsvps ?></span><span class="ps-lbl">RSVPs</span></div>
            <div class="profile-stat"><span class="ps-num"><?= $totalEvents ?></span><span class="ps-lbl">Events</span></div>
            <div class="profile-stat"><span class="ps-num"><?= $totalComments ?></span><span class="ps-lbl">Comments</span></div>
        </div>
        <p class="profile-since">Member since <?= date('M Y', strtotime($user['created_at'])) ?></p>
    </aside>

    <!-- Edit Form -->
    <div class="profile-form-panel">
        <div class="form-section">
            <h3 class="form-section-title">Edit Profile</h3>

            <?php foreach ($errors as $e): ?>
            <div class="alert alert-error"><?= clean($e) ?></div>
            <?php endforeach; ?>

            <form method="POST">
                <div class="fg-row">
                    <div class="fg">
                        <label>Full Name</label>
                        <input type="text" name="full_name" value="<?= clean($user['full_name']) ?>" required>
                    </div>
                    <div class="fg">
                        <label>Email Address</label>
                        <input type="email" name="email" value="<?= clean($user['email']) ?>" required>
                    </div>
                </div>
                <div class="fg">
                    <label>Registration Number</label>
                    <input type="text" value="<?= clean($user['reg_number']) ?>" disabled>
                    <span class="input-hint">Registration number cannot be changed.</span>
                </div>
                <div class="fg-row">
                    <div class="fg">
                        <label>New Password <span class="optional">(leave blank to keep current)</span></label>
                        <input type="password" name="new_password" placeholder="New password">
                    </div>
                    <div class="fg">
                        <label>Confirm New Password</label>
                        <input type="password" name="confirm_password" placeholder="Repeat password">
                    </div>
                </div>
                <button type="submit" name="update_profile" class="btn btn-primary">Save Changes</button>
            </form>
        </div>

        <!-- Account info -->
        <div class="form-section">
            <h3 class="form-section-title">Account Information</h3>
            <div class="info-grid">
                <div class="info-item"><span class="info-lbl">Role</span><span class="info-val"><?= ucfirst($user['role']) ?></span></div>
                <div class="info-item"><span class="info-lbl">Status</span><span class="info-val"><?= $user['is_active']?'Active':'Inactive' ?></span></div>
                <div class="info-item"><span class="info-lbl">Joined</span><span class="info-val"><?= date('d F Y', strtotime($user['created_at'])) ?></span></div>
                <div class="info-item"><span class="info-lbl">Email</span><span class="info-val"><?= clean($user['email']) ?></span></div>
            </div>
        </div>
    </div>

</div>
</div>
<?php include 'includes/footer.php'; ?>
