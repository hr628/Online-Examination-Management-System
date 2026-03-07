<?php
// register.php – New user registration
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

if (session_status() === PHP_SESSION_NONE) session_start();

// Already logged in → go to dashboard
if (!empty($_SESSION['user_id'])) {
    redirectTo(BASE_URL . '/index.php');
}

$errors  = [];
$success = '';
$form    = [];   // re-populate form on error

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid request token. Please refresh and try again.';
    } else {
        // Collect & sanitize
        $form['username']  = sanitizeInput($_POST['username']  ?? '');
        $form['email']     = sanitizeInput($_POST['email']     ?? '');
        $form['full_name'] = sanitizeInput($_POST['full_name'] ?? '');
        $form['phone']     = sanitizeInput($_POST['phone']     ?? '');
        $form['role']      = sanitizeInput($_POST['role']      ?? '');
        $password          = $_POST['password']         ?? '';
        $confirmPassword   = $_POST['confirm_password'] ?? '';

        // ── Validate ──────────────────────────────────────────
        if (strlen($form['username']) < 3 || strlen($form['username']) > 50) {
            $errors[] = 'Username must be 3–50 characters.';
        }
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $form['username'])) {
            $errors[] = 'Username may only contain letters, digits and underscores.';
        }
        if (!filter_var($form['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Please enter a valid email address.';
        }
        if (strlen($form['full_name']) < 2) {
            $errors[] = 'Full name is required.';
        }
        if (strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters.';
        }
        if ($password !== $confirmPassword) {
            $errors[] = 'Passwords do not match.';
        }
        if (!in_array($form['role'], ['student', 'instructor'], true)) {
            $errors[] = 'Role must be Student or Instructor.';
        }

        if (empty($errors)) {
            $conn = getDBConnection();

            // Unique username check
            $stmt = $conn->prepare('SELECT id FROM users WHERE username = ? LIMIT 1');
            $stmt->bind_param('s', $form['username']);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                $errors[] = 'Username is already taken.';
            }
            $stmt->close();

            // Unique email check
            $stmt = $conn->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
            $stmt->bind_param('s', $form['email']);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                $errors[] = 'Email address is already registered.';
            }
            $stmt->close();

            if (empty($errors)) {
                $hash = password_hash($password, PASSWORD_BCRYPT);
                $stmt = $conn->prepare(
                    'INSERT INTO users (username, email, password_hash, full_name, role, phone)
                     VALUES (?, ?, ?, ?, ?, ?)'
                );
                $phone = $form['phone'] !== '' ? $form['phone'] : null;
                $stmt->bind_param('ssssss',
                    $form['username'], $form['email'], $hash,
                    $form['full_name'], $form['role'], $phone
                );
                if ($stmt->execute()) {
                    $stmt->close();
                    $conn->close();
                    redirectTo(BASE_URL . '/index.php?message=' . urlencode('Registration successful! Please log in.'));
                } else {
                    $errors[] = 'Registration failed. Please try again.';
                }
                $stmt->close();
            }
            $conn->close();
        }
    }
}

$pageTitle = 'Register';
$csrf      = getCSRFToken();
?>
<?php require_once __DIR__ . '/includes/header.php'; ?>

<div class="min-vh-100 d-flex align-items-center justify-content-center login-bg py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-7 col-lg-6">

                <div class="text-center mb-4">
                    <i class="bi bi-mortarboard-fill display-4 text-primary"></i>
                    <h2 class="fw-bold text-primary mt-2">OEMS</h2>
                    <p class="text-muted">Create a new account</p>
                </div>

                <div class="card shadow-lg border-0">
                    <div class="card-body p-4">
                        <h4 class="card-title text-center mb-4 fw-semibold">Register</h4>

                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger">
                                <ul class="mb-0 ps-3">
                                    <?php foreach ($errors as $e): ?>
                                        <li><?= htmlspecialchars($e, ENT_QUOTES, 'UTF-8') ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="" novalidate>
                            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="full_name" class="form-label fw-medium">Full Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="full_name" name="full_name"
                                           value="<?= htmlspecialchars($form['full_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                           placeholder="Your full name" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="username" class="form-label fw-medium">Username <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="username" name="username"
                                           value="<?= htmlspecialchars($form['username'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                           placeholder="alphanumeric, min 3 chars" required>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="email" class="form-label fw-medium">Email Address <span class="text-danger">*</span></label>
                                <input type="email" class="form-control" id="email" name="email"
                                       value="<?= htmlspecialchars($form['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                       placeholder="you@example.com" required>
                            </div>

                            <div class="mb-3">
                                <label for="phone" class="form-label fw-medium">Phone</label>
                                <input type="tel" class="form-control" id="phone" name="phone"
                                       value="<?= htmlspecialchars($form['phone'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                       placeholder="Optional">
                            </div>

                            <div class="mb-3">
                                <label for="role" class="form-label fw-medium">Role <span class="text-danger">*</span></label>
                                <select class="form-select" id="role" name="role" required>
                                    <option value="">-- Select role --</option>
                                    <option value="student"    <?= ($form['role'] ?? '') === 'student'    ? 'selected' : '' ?>>Student</option>
                                    <option value="instructor" <?= ($form['role'] ?? '') === 'instructor' ? 'selected' : '' ?>>Instructor</option>
                                </select>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="password" class="form-label fw-medium">Password <span class="text-danger">*</span></label>
                                    <input type="password" class="form-control" id="password" name="password"
                                           placeholder="Min 8 characters" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="confirm_password" class="form-label fw-medium">Confirm Password <span class="text-danger">*</span></label>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password"
                                           placeholder="Repeat password" required>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary w-100 fw-semibold py-2">
                                <i class="bi bi-person-plus me-1"></i>Create Account
                            </button>
                        </form>

                        <hr class="my-3">
                        <p class="text-center mb-0 small">
                            Already have an account? <a href="<?= BASE_URL ?>/index.php">Login here</a>
                        </p>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
