<?php
// index.php – Login / Landing page
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

if (session_status() === PHP_SESSION_NONE) session_start();

// Already logged in → redirect to the correct dashboard
if (!empty($_SESSION['user_id'])) {
    $dest = match($_SESSION['role']) {
        'admin'      => BASE_URL . '/admin/dashboard.php',
        'instructor' => BASE_URL . '/instructor/dashboard.php',
        default      => BASE_URL . '/student/dashboard.php',
    };
    redirectTo($dest);
}

$error = '';
$info  = '';

// URL-passed messages (from redirects)
if (!empty($_GET['error']))   $error = sanitizeInput($_GET['error']);
if (!empty($_GET['message'])) $info  = sanitizeInput($_GET['message']);

// ── Handle Login POST ─────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF check
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request. Please try again.';
    } else {
        $username = sanitizeInput($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($username === '' || $password === '') {
            $error = 'Please enter both username and password.';
        } else {
            $conn = getDBConnection();
            $stmt = $conn->prepare(
                'SELECT id, username, password_hash, full_name, role, is_active
                 FROM   users
                 WHERE  username = ?
                 LIMIT  1'
            );
            $stmt->bind_param('s', $username);
            $stmt->execute();
            $result = $stmt->get_result();
            $user   = $result->fetch_assoc();
            $stmt->close();

            if ($user && $user['is_active'] && password_verify($password, $user['password_hash'])) {
                // Regenerate session ID to prevent fixation
                session_regenerate_id(true);
                $_SESSION['user_id']   = $user['id'];
                $_SESSION['username']  = $user['username'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['role']      = $user['role'];

                // Audit login
                $ip    = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
                $astmt = $conn->prepare(
                    'INSERT INTO audit_log (user_id, action, table_name, record_id, details)
                     VALUES (?, ?, ?, ?, ?)'
                );
                $action  = 'USER_LOGIN';
                $tname   = 'users';
                $rid     = $user['id'];
                $details = 'IP: ' . $ip;
                $astmt->bind_param('isiss', $user['id'], $action, $tname, $rid, $details);
                $astmt->execute();
                $astmt->close();
                $conn->close();

                $dest = match($user['role']) {
                    'admin'      => BASE_URL . '/admin/dashboard.php',
                    'instructor' => BASE_URL . '/instructor/dashboard.php',
                    default      => BASE_URL . '/student/dashboard.php',
                };
                redirectTo($dest);
            } else {
                $error = 'Invalid username or password, or account is inactive.';
                $conn->close();
            }
        }
    }
}

$pageTitle = 'Login';
$csrf      = getCSRFToken();
?>
<?php require_once __DIR__ . '/includes/header.php'; ?>

<div class="min-vh-100 d-flex align-items-center justify-content-center login-bg">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-5 col-lg-4">

                <!-- Brand header -->
                <div class="text-center mb-4">
                    <div class="brand-icon mb-3">
                        <i class="bi bi-mortarboard-fill display-3 text-primary"></i>
                    </div>
                    <h2 class="fw-bold text-primary">OEMS</h2>
                    <p class="text-muted">Online Examination Management System</p>
                </div>

                <div class="card shadow-lg border-0">
                    <div class="card-body p-4">
                        <h4 class="card-title text-center mb-4 fw-semibold">Sign In</h4>

                        <?php if ($error): ?>
                            <div class="alert alert-danger py-2 small">
                                <i class="bi bi-exclamation-triangle me-1"></i>
                                <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($info): ?>
                            <div class="alert alert-success py-2 small">
                                <i class="bi bi-check-circle me-1"></i>
                                <?= htmlspecialchars($info, ENT_QUOTES, 'UTF-8') ?>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="" novalidate>
                            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">

                            <div class="mb-3">
                                <label for="username" class="form-label fw-medium">Username</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-person"></i></span>
                                    <input type="text" class="form-control" id="username" name="username"
                                           value="<?= htmlspecialchars($_POST['username'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                           placeholder="Enter your username" required autofocus>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label for="password" class="form-label fw-medium">Password</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-lock"></i></span>
                                    <input type="password" class="form-control" id="password" name="password"
                                           placeholder="Enter your password" required>
                                    <button class="btn btn-outline-secondary" type="button"
                                            id="togglePassword" title="Show/Hide password">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary w-100 fw-semibold py-2">
                                <i class="bi bi-box-arrow-in-right me-1"></i>Login
                            </button>
                        </form>

                        <hr class="my-3">
                        <p class="text-center mb-0 small">
                            Don't have an account?
                            <a href="<?= BASE_URL ?>/register.php">Register here</a>
                        </p>
                    </div>
                </div>

                <!-- Demo credentials hint -->
                <div class="card mt-3 border-0 bg-light">
                    <div class="card-body py-2 px-3 small text-muted">
                        <strong>Demo credentials:</strong><br>
                        Admin: <code>admin</code> / <code>Admin@123</code><br>
                        Instructor: <code>instructor1</code> / <code>Pass@1234</code><br>
                        Student: <code>student1</code> / <code>Pass@1234</code>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('togglePassword').addEventListener('click', function () {
    const pw   = document.getElementById('password');
    const icon = this.querySelector('i');
    if (pw.type === 'password') {
        pw.type = 'text';
        icon.className = 'bi bi-eye-slash';
    } else {
        pw.type = 'password';
        icon.className = 'bi bi-eye';
    }
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
