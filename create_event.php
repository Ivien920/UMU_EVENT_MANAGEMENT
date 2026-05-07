<?php
// ============================================================
// UMU Events — Create / Edit Event (Admin + Verified Users)
// ============================================================
require_once 'includes/db.php';
require_once 'includes/auth.php';
requireLogin();
requireCanCreate();

$db     = getDB();
$userId = currentUserId();
$errors = [];
$editId = cleanInt($_GET['edit'] ?? 0);
$event  = null;

// ── Load for editing ──────────────────────────────────────────
if ($editId > 0) {
    $stmt = $db->prepare("SELECT * FROM events WHERE id=? AND (creator_id=? OR ?=1)");
    $isAdm = isAdmin() ? 1 : 0;
    $stmt->bind_param('iii', $editId, $userId, $isAdm);
    $stmt->execute();
    $event = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$event) { setFlash('error', 'Event not found.'); redirect('events.php'); }
}

$categories = $db->query("SELECT * FROM categories ORDER BY name")->fetch_all(MYSQLI_ASSOC);

// ── HANDLE SUBMIT ─────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_event'])) {
    $title       = trim($_POST['title'] ?? '');
    $desc        = trim($_POST['description'] ?? '');
    $location    = trim($_POST['location'] ?? '');
    $evDate      = trim($_POST['event_date'] ?? '');
    $evTime      = trim($_POST['event_time'] ?? '');
    $catId       = cleanInt($_POST['category_id'] ?? 0);
    $isFree      = isset($_POST['is_free']) ? 1 : 0;
    $price       = $isFree ? 0.00 : (float)($_POST['price'] ?? 0);
    $capacity    = $_POST['capacity'] !== '' ? cleanInt($_POST['capacity']) : null;
    $evDateTime  = $evDate . ' ' . $evTime . ':00';

    if (empty($title))    $errors[] = 'Event title is required.';
    if (empty($desc))     $errors[] = 'Description is required.';
    if (empty($location)) $errors[] = 'Location is required.';
    if (empty($evDate) || empty($evTime)) $errors[] = 'Date and time are required.';
    if ($catId < 1)       $errors[] = 'Please select a category.';
    if (!$isFree && $price <= 0) $errors[] = 'Price must be greater than 0 for paid events.';

    // Poster upload
    $posterFile = $event['poster'] ?? null;
    if (!empty($_FILES['poster']['name'])) {
        $result = uploadPoster($_FILES['poster']);
        if (!$result['success']) {
            $errors[] = $result['error'];
        } else {
            // Delete old poster
            if ($posterFile && file_exists(UPLOAD_DIR . $posterFile)) {
                unlink(UPLOAD_DIR . $posterFile);
            }
            $posterFile = $result['filename'];
        }
    }

    if (empty($errors)) {
        // Admin events auto-approved; verified users need approval
        $status = isAdmin() ? 'approved' : 'pending';

        if ($editId > 0) {
            // EDIT — keep current status if admin
            $keepStatus = isAdmin() ? $event['status'] : 'pending';
            $upd = $db->prepare("UPDATE events SET title=?,description=?,location=?,event_date=?,category_id=?,is_free=?,price=?,capacity=?,poster=?,status=? WHERE id=?");
            $upd->bind_param('ssssiidissi', $title,$desc,$location,$evDateTime,$catId,$isFree,$price,$capacity,$posterFile,$keepStatus,$editId);
            if ($upd->execute()) {
                setFlash('success', 'Event updated successfully.');
                redirect('event_detail.php?id=' . $editId);
            } else {
                $errors[] = 'Update failed: ' . $db->error;
            }
        } else {
            // INSERT
            $ins = $db->prepare("INSERT INTO events (creator_id,category_id,title,description,location,event_date,is_free,price,capacity,poster,status) VALUES (?,?,?,?,?,?,?,?,?,?,?)");
            $ins->bind_param('iissssidiss', $userId,$catId,$title,$desc,$location,$evDateTime,$isFree,$price,$capacity,$posterFile,$status);
            if ($ins->execute()) {
                $newId = $db->insert_id;
                // Notify admin if pending
                if ($status === 'pending') {
                    $admins = $db->query("SELECT id FROM users WHERE role='admin'")->fetch_all(MYSQLI_ASSOC);
                    foreach ($admins as $adm) {
                        createNotification($adm['id'], '🆕 New Event Pending Approval', '"' . $title . '" by ' . currentUserName() . ' needs your review.', 'system', $newId);
                    }
                    setFlash('success', 'Event submitted for approval! Admin will review it soon.');
                } else {
                    setFlash('success', 'Event created and published!');
                }
                redirect('event_detail.php?id=' . $newId);
            } else {
                $errors[] = 'Could not create event: ' . $db->error;
            }
        }
    }
}

$pageTitle = $editId ? 'Edit Event' : 'Create Event';
$activePage = 'create';
include 'includes/header.php';
?>

<div class="page-wrap">
<?php renderFlash(); ?>

<div class="page-hd">
    <h1 class="page-title"><?= $editId ? 'Edit Event' : 'Create New Event' ?></h1>
    <p class="page-sub">
        <?php if (!isAdmin()): ?>
        ⚠️ Your event will be reviewed by an admin before appearing on the site.
        <?php else: ?>
        As admin, your event is published immediately.
        <?php endif; ?>
    </p>
</div>

<?php if (!empty($errors)): ?>
<div class="alert alert-error">
    <?php foreach ($errors as $e): ?><p><?= clean($e) ?></p><?php endforeach; ?>
</div>
<?php endif; ?>

<form method="POST" enctype="multipart/form-data" class="create-form">
    <input type="hidden" name="save_event" value="1">

    <div class="create-form-layout">

        <!-- Left: Main fields -->
        <div class="create-form-main">

            <div class="form-section">
                <h3 class="form-section-title">Event Details</h3>

                <div class="fg">
                    <label>Event Title *</label>
                    <input type="text" name="title" placeholder="e.g. UMU Annual Sports Day 2025"
                           value="<?= clean($event['title'] ?? $_POST['title'] ?? '') ?>" required maxlength="200">
                </div>

                <div class="fg">
                    <label>Description *</label>
                    <textarea name="description" rows="5" required placeholder="Describe your event in detail…" maxlength="3000"><?= clean($event['description'] ?? $_POST['description'] ?? '') ?></textarea>
                </div>

                <div class="fg-row">
                    <div class="fg">
                        <label>Category *</label>
                        <select name="category_id" required>
                            <option value="">— Select Category —</option>
                            <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>"
                                <?= (($event['category_id']??$_POST['category_id']??0)==$cat['id'])?'selected':'' ?>>
                                <?= $cat['icon'] ?> <?= clean($cat['name']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="fg">
                        <label>Location *</label>
                        <input type="text" name="location" placeholder="e.g. Main Auditorium, UMU Nkozi"
                               value="<?= clean($event['location'] ?? $_POST['location'] ?? '') ?>" required maxlength="200">
                    </div>
                </div>

                <div class="fg-row">
                    <div class="fg">
                        <label>Event Date *</label>
                        <input type="date" name="event_date" required
                               value="<?= $event ? date('Y-m-d', strtotime($event['event_date'])) : ($_POST['event_date']??'') ?>"
                               min="<?= date('Y-m-d') ?>">
                    </div>
                    <div class="fg">
                        <label>Event Time *</label>
                        <input type="time" name="event_time" required
                               value="<?= $event ? date('H:i', strtotime($event['event_date'])) : ($_POST['event_time']??'') ?>">
                    </div>
                    <div class="fg">
                        <label>Capacity (optional)</label>
                        <input type="number" name="capacity" min="1" placeholder="Leave blank for unlimited"
                               value="<?= clean($event['capacity'] ?? $_POST['capacity'] ?? '') ?>">
                    </div>
                </div>
            </div>

            <div class="form-section">
                <h3 class="form-section-title">Pricing</h3>
                <div class="pricing-toggle">
                    <label class="toggle-label">
                        <input type="checkbox" name="is_free" id="is_free_chk"
                               <?= ($event['is_free'] ?? 1) ? 'checked' : '' ?>
                               onchange="document.getElementById('price_row').style.display=this.checked?'none':'flex'">
                        <span class="toggle-switch"></span>
                        This event is <strong>Free</strong>
                    </label>
                </div>
                <div class="fg-row" id="price_row" style="display:<?= ($event['is_free']??1)?'none':'flex' ?>">
                    <div class="fg">
                        <label>Ticket Price (UGX) *</label>
                        <input type="number" name="price" min="1" step="500"
                               placeholder="e.g. 20000"
                               value="<?= clean($event['price'] ?? $_POST['price'] ?? '') ?>">
                    </div>
                </div>
            </div>

        </div><!-- /.create-form-main -->

        <!-- Right: Poster upload -->
        <div class="create-form-side">
            <div class="form-section">
                <h3 class="form-section-title">Event Poster</h3>
                <div class="poster-upload-area" onclick="document.getElementById('poster_file').click()">
                    <?php if ($event && $event['poster']): ?>
                    <img src="<?= UPLOAD_URL . clean($event['poster']) ?>" alt="Current poster" class="poster-preview" id="poster_preview">
                    <?php else: ?>
                    <img id="poster_preview" style="display:none;" alt="Poster preview" class="poster-preview">
                    <?php endif; ?>
                    <div class="poster-upload-placeholder" id="poster_placeholder"
                         style="<?= ($event && $event['poster'])?'display:none':'' ?>">
                        <div class="poster-upload-icon">🖼️</div>
                        <p>Click to upload poster</p>
                        <span>JPG, PNG, WebP · Max 5MB</span>
                    </div>
                </div>
                <input type="file" name="poster" id="poster_file" accept="image/*" style="display:none"
                       onchange="previewPoster(this)">
                <p class="upload-hint">A poster makes your event stand out.</p>
            </div>
        </div>

    </div><!-- /.create-form-layout -->

    <div class="create-form-footer">
        <button type="submit" class="btn btn-primary btn-lg">
            <?= $editId ? 'Save Changes' : (isAdmin() ? 'Publish Event' : 'Submit for Approval') ?>
        </button>
        <a href="<?= $editId ? 'event_detail.php?id='.$editId : 'dashboard.php' ?>" class="btn btn-ghost">Cancel</a>
    </div>

</form>

</div><!-- /.page-wrap -->

<script>
function previewPoster(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => {
            const prev = document.getElementById('poster_preview');
            const plch = document.getElementById('poster_placeholder');
            prev.src = e.target.result;
            prev.style.display = 'block';
            if (plch) plch.style.display = 'none';
        };
        reader.readAsDataURL(input.files[0]);
    }
}
</script>

<?php include 'includes/footer.php'; ?>
