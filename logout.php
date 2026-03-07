<?php
// logout.php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

if (session_status() === PHP_SESSION_NONE) session_start();

// Log the logout action before destroying session
if (!empty($_SESSION['user_id'])) {
    try {
        $conn    = getDBConnection();
        $uid     = (int)$_SESSION['user_id'];
        $action  = 'USER_LOGOUT';
        $tname   = 'users';
        $details = 'IP: ' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
        $stmt = $conn->prepare(
            'INSERT INTO audit_log (user_id, action, table_name, record_id, details)
             VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->bind_param('isiss', $uid, $action, $tname, $uid, $details);
        $stmt->execute();
        $stmt->close();
        $conn->close();
    } catch (Exception $e) {
        // Silently ignore – session destruction is more important
    }
}

$_SESSION = [];

if (ini_get('session.use_cookies')) {
    $p = session_get_cookie_params();
    setcookie(
        session_name(), '',
        time() - 42000,
        $p['path'], $p['domain'],
        $p['secure'], $p['httponly']
    );
}

session_destroy();
redirectTo(BASE_URL . '/index.php?message=' . urlencode('You have been logged out successfully.'));
