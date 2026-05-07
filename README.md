# UMU_EVENT_MANAGEMENT
A centralised place for events
# UMU_EVENT
group c project
# UMU Event Management System
## Description

A full-featured Event Management System built with **PHP, MySQL, HTML, CSS and JavaScript**. Supports three user roles — **Student**, **Verified User**, and **Admin** — and satisfies all ten functional requirements of the assessment.

### Key Features
- Student registration & login with session management
- Browse, search, and filter approved events
- RSVP to events (with duplicate prevention + capacity limits)
- Comment on events — full CRUD (post, edit, delete)
- Event poster upload (image files)
- Create events (Admin = instant publish · Verified users = pending approval)
- Daily reminder notifications sent automatically on login
- Admin panel: approve/reject events, manage users, send notifications
- Admin can verify user accounts to grant event-creation rights
- Role-based access control throughout
- UMU brand colors: Black · Red (#C8102E) · Yellow (#F5C518) · White
- Logo placeholder in header

---

## Project Structure

```
umu-events/
├── index.php              ← Login & Registration
├── logout.php             ← Session destroy
├── dashboard.php          ← Student home / event feed
├── events.php             ← Browse + search + RSVP
├── event_detail.php       ← Event detail + comments CRUD
├── create_event.php       ← Create / edit event + poster upload
├── my_rsvps.php           ← Student RSVP history
├── notifications.php      ← Notification centre
├── profile.php            ← Edit profile / change password
│
├── admin/
│   ├── dashboard.php      ← Admin stats + pending approvals
│   ├── events.php         ← Approve · Reject · Edit · Delete events
│   ├── users.php          ← Verify/Unverify · Activate · Remove users
│   └── notifications.php  ← Send bulk or targeted notifications
│
├── includes/
│   ├── db.php             ← DB connection + upload helper
│   ├── auth.php           ← Session guards, flash, notification helpers
│   ├── header.php         ← Student header/nav
│   ├── footer.php         ← Student footer
│   ├── admin_header.php   ← Admin sidebar + topbar
│   └── admin_footer.php   ← Admin closing markup
│
├── css/
│   ├── style.css          ← Student pages stylesheet
│   └── admin.css          ← Admin panel stylesheet
│
├── uploads/
│   └── posters/           ← Uploaded event posters (auto-created)
│
├── assets/                ← Place umu-logo.png here
│
└── events_db.sql          ← Full database dump
```

---

## Setup Instructions

### Prerequisites
- PHP 7.4+ with `mysqli` and `fileinfo` extensions enabled
- MySQL 5.7+
- WAMP local server

### Step 1 — Copy files
Place the `umu-events` folder inside your server root:

- WAMP:  `C:/wamp64/www/umu-events/`

### Step 2 — Import database
1. Open `http://localhost/phpmyadmin`
2. Create database: `umu_events`
3. Select it → **Import** → choose `events_db.sql` → **Go**

### Step 3 — Configure database credentials
Edit `includes/db.php`:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');      
define('DB_PASS', 'Ivan@2026');           
define('DB_NAME', 'umu_events');
```

### Step 4 — Set folder permissions
Ensure `uploads/posters/` is writable (chmod 755 or 775 on Linux/Mac).

Open browser: `http://localhost/umu-events/`

---

## Default Login Credentials

| Role         | Registration Number | Password   |
|--------------|---------------------|------------|
| **Admin**    | `ADMIN001`          | `password` |
| **Verified** | `2024-B072-33329`            | `0708067738` |
| **Student**  | `2024-B221-31814`      | `FLO07578` |

---

## Functional Requirements Coverage

| # | Requirement | How It's Met |
|---|-------------|--------------|
| 1 | User Registration & Login | `index.php` — bcrypt passwords, session start on login |
| 2 | Logout & Session Control | `logout.php` destroys session; all pages call `requireLogin()` |
| 3 | Data Entry through Forms | Event creation (with poster), comment posting, RSVP |
| 4 | Input Validation with Feedback | Server-side validation on all forms; flash alerts shown |
| 5 | Dynamic Data Display | All events, RSVPs, comments pulled live from MySQL |
| 6 | Search / Filter | Search by keyword · filter by category · filter by date · free-only toggle |
| 7 |    Editing Records | Edit events (`create_event.php?edit=ID`); edit comments on `event_detail.php` |
| 8 | Deleting Records | Delete events (admin), delete comments (owner/admin), cancel RSVPs |
| 9 | Role-Based Access | 3 roles: student / verified / admin; guards on every protected page |
| 10 | Persistent Storage | MySQL database; data survives restarts and refreshes |

### Bonus Features (above requirements)
- Event poster image uploads
- Daily reminder notifications (auto-sent on login)
- Admin can send bulk notifications to all / students / verified / one user
- Capacity tracking with progress bar on event detail
- Comment edit history indicator
- Verified user badge system



