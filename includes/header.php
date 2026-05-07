<?php
// ============================================================
// UMU Events — Student Header
// ============================================================
$unread = isLoggedIn() ? countUnread(currentUserId()) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? 'UMU Events' ?> — Uganda Martyrs University</title>
    <link rel="stylesheet" href="<?= $cssRoot ?? '' ?>css/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=DM+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>

<header class="site-header">
    <div class="header-inner">

        <!-- Brand -->
        <a href="<?= $cssRoot ?? '' ?>dashboard.php" class="brand">
            <div class="brand-logo">
                <?php
                // Logo placeholder — replace with actual UMU logo
                $logoPath = ($cssRoot ?? '') . 'assets/umu-logo.png';
                ?>
                <div class=">
                    <span class="brand-umu></span>
                </div>
            </div>
            <div class="brand-text">
                <span class="brand-name">UMU Events</span>
                <span class="brand-tagline">Uganda Martyrs University</span>
            </div>
        </a>

        <!-- Nav -->
        <nav class="main-nav">
            <a href="<?= $cssRoot ?? '' ?>dashboard.php"   class="nav-link <?= ($activePage??'')==='dashboard'?'active':'' ?>">Home</a>
            <a href="<?= $cssRoot ?? '' ?>events.php"      class="nav-link <?= ($activePage??'')==='events'?'active':'' ?>">Events</a>
            <a href="<?= $cssRoot ?? '' ?>my_rsvps.php"    class="nav-link <?= ($activePage??'')==='rsvps'?'active':'' ?>">My RSVPs</a>
            <?php if (isVerified()): ?>
            <a href="<?= $cssRoot ?? '' ?>create_event.php" class="nav-link nav-create <?= ($activePage??'')==='create'?'active':'' ?>">+ Create Event</a>
            <?php endif; ?>
        </nav>

        <!-- Right -->
        <div class="header-right">
            <a href="<?= $cssRoot ?? '' ?>notifications.php" class="notif-bell <?= ($activePage??'')==='notifs'?'active':'' ?>">
                <span class="bell-icon">🔔</span>
                <?php if ($unread > 0): ?>
                    <span class="notif-badge"><?= $unread > 9 ? '9+' : $unread ?></span>
                <?php endif; ?>
            </a>
            <div class="user-menu">
                <div class="user-avatar"><?= strtoupper(substr(currentUserName(), 0, 1)) ?></div>
                <div class="user-dropdown">
                    <div class="dropdown-header">
                        <strong><?= clean(currentUserName()) ?></strong>
                        <span class="role-badge role-<?= currentUserRole() ?>"><?= ucfirst(currentUserRole()) ?></span>
                    </div>
                    <a href="<?= $cssRoot ?? '' ?>profile.php" class="dropdown-item">👤 My Profile</a>
                    <?php if (isAdmin()): ?>
                    <a href="<?= $cssRoot ?? '' ?>admin/dashboard.php" class="dropdown-item">⚙️ Admin Panel</a>
                    <?php endif; ?>
                    <div class="dropdown-divider"></div>
                    <a href="<?= $cssRoot ?? '' ?>logout.php" class="dropdown-item dropdown-logout">🚪 Log Out</a>
                </div>
            </div>
        </div>

    </div>
</header>

<main class="site-main">
