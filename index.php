<?php


require_once 'includes/db.php';
require_once 'includes/auth.php';

if (isLoggedIn()) redirect(isAdmin() ? 'admin/dashboard.php' : 'dashboard.php');

$errors = $regErrors = [];
$tab    = $_POST['tab'] ?? 'login';

// ── LOGIN ─────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'login') {
    $reg  = trim($_POST['reg_number'] ?? '');
    $pass = $_POST['password'] ?? '';
    if (empty($reg))  $errors[] = 'Registration number is required.';
    if (empty($pass)) $errors[] = 'Password is required.';

    if (empty($errors)) {
        $db   = getDB();
        $stmt = $db->prepare("SELECT * FROM users WHERE reg_number=? AND is_active=1");
        $stmt->bind_param('s', $reg);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($user && password_verify($pass, $user['password'])) {
            $_SESSION['user_id']    = $user['id'];
            $_SESSION['full_name']  = $user['full_name'];
            $_SESSION['role']       = $user['role'];
            $_SESSION['reg_number'] = $user['reg_number'];
            sendDailyReminders(); // Send daily event reminders on login
            setFlash('success', 'Welcome back, ' . $user['full_name'] . '!');
            redirect($user['role'] === 'admin' ? 'admin/dashboard.php' : 'dashboard.php');
        } else {
            $errors[] = 'Invalid credentials or account is inactive.';
        }
    }
    $tab = 'login';
}

// ── REGISTER ──────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'register') {
    $fullName  = trim($_POST['full_name'] ?? '');
    $regNo     = trim($_POST['reg_number'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $pass      = $_POST['password'] ?? '';
    $confirm   = $_POST['confirm_password'] ?? '';

    if (empty($fullName))  $regErrors[] = 'Full name is required.';
    if (empty($regNo))     $regErrors[] = 'Registration number is required.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $regErrors[] = 'Valid email required.';
    if (strlen($pass) < 6) $regErrors[] = 'Password must be at least 6 characters.';
    if ($pass !== $confirm) $regErrors[] = 'Passwords do not match.';

    if (empty($regErrors)) {
        $db    = getDB();
        $check = $db->prepare("SELECT id FROM users WHERE reg_number=? OR email=?");
        $check->bind_param('ss', $regNo, $email);
        $check->execute();
        $check->store_result();
        if ($check->num_rows > 0) {
            $regErrors[] = 'Registration number or email already exists.';
        } else {
            $hash = password_hash($pass, PASSWORD_BCRYPT);
            $ins  = $db->prepare("INSERT INTO users (reg_number,full_name,email,password,role) VALUES (?,?,?,?,'student')");
            $ins->bind_param('ssss', $regNo, $fullName, $email, $hash);
            if ($ins->execute()) {
                setFlash('success', 'Account created! You can now log in.');
                redirect('index.php');
            } else {
                $regErrors[] = 'Registration failed. Please try again.';
            }
        }
    }
    $tab = 'register';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UMU Events — Sign In</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=DM+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="auth-body">

<div class="auth-layout">

    <!-- Left: Brand Panel -->
    <div class="auth-panel-brand">
        <div class="auth-brand-inner">

            <!-- Logo placeholder -->
            <div class="auth-logo-wrap">
                <div class="auth-logo-ring">
                    <div class="auth-logo-inner"
                   >
                        <img src="UMU.png" alt="UMU Logo">
                        <span class="auth-logo-text" >UMU</span>
                    
                    </div>
                </div>
            </div>

            <h1 class="auth-uni-name">Uganda Martyrs<br>University</h1>
            <p class="auth-uni-location">MASAKA &bull; Est. 2006</p>

            <div class="auth-rule"></div>

            <h2 class="auth-app-name">Events Management<br><em>System</em></h2>
            <p class="auth-app-desc">Discover, RSVP, and create university events. Stay connected with everything happening on campus.</p>

            <div class="auth-features">
                <div class="auth-feature-item"><span>🎓</span> Academic Events</div>
                <div class="auth-feature-item"><span>⚽</span> Sports & Recreation</div>
                <div class="auth-feature-item"><span>🎭</span> Cultural & Arts</div>
                <div class="auth-feature-item"><span>🔔</span> Daily Reminders</div>
            </div>

            <div class="auth-motto">"Making A Difference"</div>
        </div>
        <div class="auth-brand-blob"></div>
    </div>

    <!-- Right: Form Panel -->
    <div class="auth-panel-form">
        <div class="auth-form-wrap">

            <?php if ($f = getFlash()): ?>
                <div class="alert alert-<?= $f['type'] ?>"><?= clean($f['message']) ?></div>
            <?php endif; ?>

            <div class="auth-tabs">
                <button class="auth-tab <?= $tab==='login'?'active':'' ?>" onclick="switchTab('login')">Sign In</button>
                <button class="auth-tab <?= $tab==='register'?'active':'' ?>" onclick="switchTab('register')">Register</button>
                <div class="auth-tab-indicator"></div>
            </div>

            <!-- LOGIN -->
            <div id="pane-login" class="auth-pane <?= $tab==='login'?'active':'' ?>">
                <h2 class="auth-form-title">Welcome Back</h2>
                <p class="auth-form-sub">Sign in with your UMU credentials</p>

                <?php foreach ($errors as $e): ?>
                    <div class="alert alert-error"><?= clean($e) ?></div>
                <?php endforeach; ?>

                <form method="POST">
                    <input type="hidden" name="action" value="login">
                    <input type="hidden" name="tab" value="login">
                    <div class="fg">
                        <label>Registration Number</label>
                        <input type="text" name="reg_number" placeholder="e.g. 2024/BSC/001"
                               value="<?= clean($_POST['reg_number']??'') ?>" required>
                    </div>
                    <div class="fg">
                        <label>Password</label>
                        <input type="password" name="password" placeholder="Your password" required>
                    </div>
                    <button type="submit" class="btn btn-primary btn-full">Sign In →</button>
                </form>
                <p class="auth-switch-hint">No account? <a href="#" onclick="switchTab('register')">Register here</a></p>
            </div>

            <!-- REGISTER -->
            <div id="pane-register" class="auth-pane <?= $tab==='register'?'active':'' ?>">
                <h2 class="auth-form-title">Create Account</h2>
                <p class="auth-form-sub">Register with your UMU student details</p>

                <?php foreach ($regErrors as $e): ?>
                    <div class="alert alert-error"><?= clean($e) ?></div>
                <?php endforeach; ?>

                <form method="POST">
                    <input type="hidden" name="action" value="register">
                    <input type="hidden" name="tab" value="register">
                    <div class="fg">
                        <label>Full Name</label>
                        <input type="text" name="full_name" placeholder="Your full name"
                               value="<?= clean($_POST['full_name']??'') ?>" required>
                    </div>
                    <div class="fg-row">
                        <div class="fg">
                            <label>Registration Number</label>
                            <input type="text" name="reg_number" placeholder="2024/BSC/001"
                                   value="<?= clean($_POST['reg_number']??'') ?>" required>
                        </div>
                        <div class="fg">
                            <label>Email Address</label>
                            <input type="email" name="email" placeholder="you@students.umu.ac.ug"
                                   value="<?= clean($_POST['email']??'') ?>" required>
                        </div>
                    </div>
                    <div class="fg-row">
                        <div class="fg">
                            <label>Password</label>
                            <input type="password" name="password" placeholder="Min. 6 characters" required>
                        </div>
                        <div class="fg">
                            <label>Confirm Password</label>
                            <input type="password" name="confirm_password" placeholder="Repeat password" required>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary btn-full">Create Account →</button>
                </form>
                <p class="auth-switch-hint">Already registered? <a href="#" onclick="switchTab('login')">Sign in</a></p>
            </div>

        </div>
    </div>
</div>

<script>
function switchTab(tab) {
    document.querySelectorAll('.auth-tab').forEach((t,i) => t.classList.toggle('active', (tab==='login'&&i===0)||(tab==='register'&&i===1)));
    document.querySelectorAll('.auth-pane').forEach(p => p.classList.remove('active'));
    document.getElementById('pane-'+tab).classList.add('active');
    // Move indicator
    const idx = tab==='login'?0:1;
    document.querySelector('.auth-tab-indicator').style.left = (idx*50)+'%';
}
// Init indicator
document.querySelector('.auth-tab-indicator').style.left = ('<?= $tab ?>'==='login'?'0':'50')+'%';
</script>
</body>
</html>
