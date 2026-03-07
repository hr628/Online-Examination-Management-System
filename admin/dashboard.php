<?php
// admin/dashboard.php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
checkAuth('admin');

$conn = getDBConnection();

// ── Stats ── single query combining all counts ────────────────
$statsRow = $conn->query(
    "SELECT
        SUM(is_active)                                             AS total_users,
        SUM(role='student'  AND is_active=1)                      AS total_students,
        SUM(role='instructor' AND is_active=1)                    AS total_instructors
     FROM users"
)->fetch_assoc();
$examCounts = $conn->query(
    "SELECT COUNT(*) AS total_exams,
            SUM(status='active') AS active_exams
     FROM exams"
)->fetch_assoc();
$qCount   = $conn->query("SELECT COUNT(*) FROM questions WHERE is_active=1")->fetch_row()[0];
$attCount = $conn->query("SELECT COUNT(*) FROM student_exams WHERE status='completed'")->fetch_row()[0];
$catCount = $conn->query("SELECT COUNT(*) FROM categories")->fetch_row()[0];

$stats = [
    'total_users'      => $statsRow['total_users'],
    'total_students'   => $statsRow['total_students'],
    'total_instructors'=> $statsRow['total_instructors'],
    'total_exams'      => $examCounts['total_exams'],
    'active_exams'     => $examCounts['active_exams'],
    'total_questions'  => $qCount,
    'total_attempts'   => $attCount,
    'total_categories' => $catCount,
];

// Recent activity from audit_log
$recentActivity = $conn->query(
    "SELECT al.*, u.username, u.role
     FROM   audit_log al
     LEFT JOIN users u ON u.id = al.user_id
     ORDER  BY al.created_at DESC
     LIMIT  10"
)->fetch_all(MYSQLI_ASSOC);

// Recent exam attempts
$recentAttempts = $conn->query(
    "SELECT se.*, u.full_name, u.username, e.title AS exam_title,
            r.percentage, r.grade, r.is_passed
     FROM   student_exams se
     JOIN   users u ON u.id = se.student_id
     JOIN   exams e ON e.id = se.exam_id
     LEFT JOIN results r ON r.student_exam_id = se.id
     ORDER  BY se.started_at DESC
     LIMIT  8"
)->fetch_all(MYSQLI_ASSOC);

$conn->close();
$pageTitle = 'Admin Dashboard';
?>
<?php require_once __DIR__ . '/../includes/header.php'; ?>
<?php require_once __DIR__ . '/../includes/navbar.php'; ?>

<div class="container-fluid py-4">
    <?= renderFlash() ?>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-0"><i class="bi bi-speedometer2 me-2 text-primary"></i>Admin Dashboard</h2>
            <small class="text-muted">Welcome back, <?= htmlspecialchars($_SESSION['full_name'], ENT_QUOTES, 'UTF-8') ?></small>
        </div>
        <span class="text-muted small"><?= date('l, d F Y') ?></span>
    </div>

    <!-- Stats Cards -->
    <div class="row g-3 mb-4">
        <?php
        $cards = [
            ['label'=>'Total Users',    'value'=>$stats['total_users'],      'icon'=>'people-fill',         'color'=>'primary'],
            ['label'=>'Students',       'value'=>$stats['total_students'],   'icon'=>'person-badge',        'color'=>'success'],
            ['label'=>'Instructors',    'value'=>$stats['total_instructors'],'icon'=>'person-workspace',    'color'=>'info'],
            ['label'=>'Total Exams',    'value'=>$stats['total_exams'],      'icon'=>'file-earmark-text',   'color'=>'warning'],
            ['label'=>'Active Exams',   'value'=>$stats['active_exams'],     'icon'=>'play-circle',         'color'=>'success'],
            ['label'=>'Questions',      'value'=>$stats['total_questions'],  'icon'=>'question-circle',     'color'=>'secondary'],
            ['label'=>'Attempts',       'value'=>$stats['total_attempts'],   'icon'=>'check2-circle',       'color'=>'primary'],
            ['label'=>'Categories',     'value'=>$stats['total_categories'], 'icon'=>'tags',                'color'=>'dark'],
        ];
        foreach ($cards as $c): ?>
        <div class="col-6 col-md-3">
            <div class="card stat-card border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="stat-icon bg-<?= $c['color'] ?> bg-opacity-10 text-<?= $c['color'] ?>">
                        <i class="bi bi-<?= $c['icon'] ?>"></i>
                    </div>
                    <div>
                        <div class="fs-2 fw-bold"><?= number_format($c['value']) ?></div>
                        <div class="text-muted small"><?= $c['label'] ?></div>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Quick Links -->
    <div class="row g-3 mb-4">
        <div class="col-12">
            <h5 class="fw-semibold mb-3">Quick Actions</h5>
        </div>
        <?php
        $links = [
            ['href'=>'manage_users.php',      'icon'=>'people',          'label'=>'Manage Users',     'color'=>'primary'],
            ['href'=>'manage_exams.php',       'icon'=>'file-earmark-plus','label'=>'Manage Exams',   'color'=>'success'],
            ['href'=>'manage_questions.php',   'icon'=>'question-circle', 'label'=>'Questions',        'color'=>'info'],
            ['href'=>'manage_categories.php',  'icon'=>'tags',            'label'=>'Categories',       'color'=>'warning'],
            ['href'=>'reports.php',            'icon'=>'bar-chart',       'label'=>'Reports',          'color'=>'secondary'],
        ];
        foreach ($links as $l): ?>
        <div class="col-6 col-md-2">
            <a href="<?= $l['href'] ?>" class="card text-decoration-none text-center p-3 border-0 shadow-sm h-100 quick-action-card">
                <i class="bi bi-<?= $l['icon'] ?> fs-1 text-<?= $l['color'] ?>"></i>
                <div class="mt-2 fw-medium small text-dark"><?= $l['label'] ?></div>
            </a>
        </div>
        <?php endforeach; ?>
    </div>

    <div class="row g-3">
        <!-- Recent Attempts -->
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white fw-semibold py-3">
                    <i class="bi bi-clock-history me-2 text-primary"></i>Recent Exam Attempts
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0 small">
                            <thead class="table-light">
                                <tr>
                                    <th>Student</th>
                                    <th>Exam</th>
                                    <th>Score</th>
                                    <th>Grade</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentAttempts as $a): ?>
                                <tr>
                                    <td><?= htmlspecialchars($a['full_name'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars($a['exam_title'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= $a['percentage'] !== null ? $a['percentage'] . '%' : '—' ?></td>
                                    <td>
                                        <?php if ($a['grade']): ?>
                                        <span class="badge bg-<?= gradeBadgeClass($a['grade']) ?>">
                                            <?= $a['grade'] ?>
                                        </span>
                                        <?php else: ?>—<?php endif; ?>
                                    </td>
                                    <td><?= formatDate($a['started_at'], 'd M Y') ?></td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if (empty($recentAttempts)): ?>
                                <tr><td colspan="5" class="text-center text-muted py-3">No attempts yet.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white fw-semibold py-3">
                    <i class="bi bi-activity me-2 text-success"></i>Recent Activity
                </div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush small">
                        <?php foreach ($recentActivity as $act): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-start py-2">
                            <div>
                                <span class="badge bg-secondary me-1"><?= htmlspecialchars($act['action'], ENT_QUOTES, 'UTF-8') ?></span>
                                <span class="text-muted"><?= htmlspecialchars($act['username'] ?? 'System', ENT_QUOTES, 'UTF-8') ?></span>
                            </div>
                            <small class="text-muted text-nowrap ms-2"><?= formatDate($act['created_at'], 'd M H:i') ?></small>
                        </li>
                        <?php endforeach; ?>
                        <?php if (empty($recentActivity)): ?>
                        <li class="list-group-item text-center text-muted py-3">No activity yet.</li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
