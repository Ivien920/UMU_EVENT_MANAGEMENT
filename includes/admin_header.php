<?php
// ============================================================
// UMU Events — Admin Header
// ============================================================
$unread = isLoggedIn() ? countUnread(currentUserId()) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? 'Admin' ?> — UMU Events Admin</title>
    <link rel="stylesheet" href="../css/admin.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=DM+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>

<div class="admin-wrap">

<aside class="admin-sidebar">
    <div class="sidebar-top">
        
        <div class="sidebar-logo">
            <div class="sidebar-logo-circle">UMU</div>
            <div class="sidebar-logo-text">
                <span class="sidebar-app-name">Events Admin</span>
                <span class="sidebar-uni">Uganda Martyrs University</span>
            </div>
        </div>
    </div>

    <nav class="sidebar-nav">
        <span class="sidebar-section-label">Main</span>
        <a href="dashboard.php"     class="sidebar-link <?= ($activePage??'')==='dashboard'?'active':'' ?>"><span>📊</span> Dashboard</a>
        <a href="events.php"        class="sidebar-link <?= ($activePage??'')==='events'?'active':'' ?>"><span>🎪</span> All Events</a>
        <a href="users.php"         class="sidebar-link <?= ($activePage??'')==='users'?'active':'' ?>"><span>👥</span> Users</a>
        <a href="notifications.php" class="sidebar-link <?= ($activePage??'')==='notifs'?'active':'' ?>"><span>🔔</span> Notifications</a>

        <span class="sidebar-section-label">Quick</span>
        <a href="../create_event.php" class="sidebar-link"><span>➕</span> Create Event</a>
        <a href="../dashboard.php"    class="sidebar-link"><span>🌐</span> View Site</a>
    </nav>

    <div class="sidebar-footer">
        <div class="sidebar-user">
            <div class="sidebar-avatar"><?= strtoupper(substr(currentUserName(), 0, 1)) ?></div>
            <div class="sidebar-user-info">
                <span class="sidebar-user-name"><?= clean(currentUserName()) ?></span>
                <span class="sidebar-user-role">Administrator</span>
            </div>
        </div>
        <a href="../logout.php" class="sidebar-logout">Log Out</a>
    </div>
</aside>

<div class="admin-content">
    <div class="admin-topbar">
        <div class="admin-topbar-left">
            <h1 class="admin-title"><?= $pageTitle ?? 'Admin' ?></h1>
            <span class="admin-date"><?= date('l, d F Y') ?></span>
        </div>
        <div class="admin-topbar-right">
            <a href="../notifications.php" class="topbar-notif-btn">
                🔔 <?php if($unread>0): ?><span class="topbar-badge"><?= $unread ?></span><?php endif; ?>
            </a>
        </div>
    </div>
    <div class="admin-body">
