<?php
// ============================================================
// Global Helper Functions
// ============================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ── Authentication ──────────────────────────────────────────

/**
 * Ensure user is logged in. Optionally restrict to a specific role.
 * Redirects to login page on failure.
 */
function checkAuth(?string $role = null): void
{
    if (empty($_SESSION['user_id'])) {
        redirectTo(BASE_URL . '/index.php?error=Please+log+in+to+continue');
    }

    if ($role !== null && $_SESSION['role'] !== $role) {
        // Allow admin to access instructor/student pages as well
        if ($_SESSION['role'] !== 'admin') {
            redirectTo(BASE_URL . '/index.php?error=Access+denied');
        }
    }
}

/**
 * Redirect to a URL and exit.
 */
function redirectTo(string $url): void
{
    header('Location: ' . $url);
    exit;
}

// ── Input / Output ───────────────────────────────────────────

/**
 * Sanitize a scalar input value.
 */
function sanitizeInput(mixed $data): string
{
    return htmlspecialchars(strip_tags(trim((string)$data)), ENT_QUOTES, 'UTF-8');
}

/**
 * Format a MySQL DATETIME string for display.
 */
function formatDate(string $date, string $format = 'd M Y, H:i'): string
{
    if (empty($date) || $date === '0000-00-00 00:00:00') {
        return '—';
    }
    return (new DateTime($date))->format($format);
}

/**
 * Convert a percentage to a grade string.
 */
function calculateGrade(float $percentage): string
{
    return match(true) {
        $percentage >= 95 => 'A+',
        $percentage >= 85 => 'A',
        $percentage >= 75 => 'B+',
        $percentage >= 65 => 'B',
        $percentage >= 50 => 'C',
        default           => 'F',
    };
}

/**
 * Format an integer number of minutes as "Xh Ym" (e.g. "1h 30m").
 */
function formatDuration(int $minutes): string
{
    $h = intdiv($minutes, 60);
    $m = $minutes % 60;
    if ($h === 0) {
        return "{$m}m";
    }
    return $m === 0 ? "{$h}h" : "{$h}h {$m}m";
}

// ── CSRF ─────────────────────────────────────────────────────

/**
 * Return (and generate if missing) the CSRF token for the current session.
 */
function getCSRFToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify a submitted CSRF token against the session token.
 */
function verifyCSRFToken(string $token): bool
{
    return isset($_SESSION['csrf_token']) &&
           hash_equals($_SESSION['csrf_token'], $token);
}

// ── Flash Messages ────────────────────────────────────────────

/**
 * Store a flash message to display on next page load.
 */
function setFlash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

/**
 * Return and clear the flash message array, or null if none.
 */
function getFlash(): ?array
{
    if (!empty($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Render the Bootstrap alert HTML for a flash message.
 */
function renderFlash(): string
{
    $flash = getFlash();
    if (!$flash) {
        return '';
    }
    $type = htmlspecialchars($flash['type'], ENT_QUOTES, 'UTF-8');
    $msg  = htmlspecialchars($flash['message'], ENT_QUOTES, 'UTF-8');
    return <<<HTML
    <div class="alert alert-{$type} alert-dismissible fade show flash-message" role="alert">
        {$msg}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    HTML;
}

// ── Misc ─────────────────────────────────────────────────────

/**
 * Return the Bootstrap badge colour class for an exam status.
 */
function examStatusBadge(string $status): string
{
    return match($status) {
        'active'    => 'success',
        'completed' => 'secondary',
        'draft'     => 'warning',
        'cancelled' => 'danger',
        default     => 'dark',
    };
}

/**
 * Return the Bootstrap badge colour class for a grade.
 */
function gradeBadgeClass(string $grade): string
{
    return match($grade) {
        'A+'     => 'success',
        'A'      => 'primary',
        'B+'     => 'info',
        'B'      => 'info',
        'C'      => 'warning',
        default  => 'danger',
    };
}

/**
 * Return the base URL of the application (no trailing slash).
 */
if (!defined('BASE_URL')) {
    $scheme   = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host     = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $script   = $_SERVER['SCRIPT_NAME'] ?? '';
    // Walk up to the project root (remove sub-directory segments)
    $root     = rtrim(dirname(dirname($script)), '/\\');
    define('BASE_URL', $scheme . '://' . $host . ($root === '' ? '' : $root));
}
