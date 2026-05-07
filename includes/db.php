<?php
// ============================================================
// UMU Event Management System
define('DB_HOST', 'localhost');
define('DB_USER', 'root');       
define('DB_PASS', 'Ivan@2026');         
define('DB_NAME', 'umu_events');

// Upload paths
define('UPLOAD_DIR',   __DIR__ . '/../uploads/posters/');
define('UPLOAD_URL',   'uploads/posters/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5 MB
define('ALLOWED_TYPES', ['image/jpeg', 'image/png', 'image/webp', 'image/gif']);

// App settings
define('APP_NAME',     'UMU Events');
define('APP_MOTTO',    'Uganda Martyrs University');
define('SITE_URL',     'http://localhost/umu-events');

function getDB(): mysqli {
    static $conn = null;
    if ($conn === null) {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if ($conn->connect_error) {
            die('<div style="font-family:sans-serif;padding:2rem;color:#c00;">
                <strong>DB Error:</strong> ' . htmlspecialchars($conn->connect_error) . '
                <br>Please check <code>includes/db.php</code> credentials.
            </div>');
        }
        $conn->set_charset('utf8mb4');
    }
    return $conn;
}

// ── File Upload Helper ────────────────────────────────────────
function uploadPoster(array $file): array {
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'error' => 'Upload error code: ' . $file['error']];
    }
    if ($file['size'] > MAX_FILE_SIZE) {
        return ['success' => false, 'error' => 'File too large. Max 5MB.'];
    }
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime  = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    if (!in_array($mime, ALLOWED_TYPES)) {
        return ['success' => false, 'error' => 'Invalid file type. Use JPG, PNG, WebP, or GIF.'];
    }
    $ext      = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'poster_' . uniqid('', true) . '.' . $ext;
    $dest     = UPLOAD_DIR . $filename;
    if (!is_dir(UPLOAD_DIR)) mkdir(UPLOAD_DIR, 0755, true);
    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        return ['success' => false, 'error' => 'Could not save file. Check folder permissions.'];
    }
    return ['success' => true, 'filename' => $filename];
}
?>
