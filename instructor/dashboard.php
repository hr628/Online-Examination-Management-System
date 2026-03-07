<?php
// instructor/dashboard.php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
checkAuth('instructor');

$conn = getDBConnection();
$uid  = (int)$_SESSION['user_id'];

// Stats - single query for efficiency
$statsStmt = $conn->prepare(
    "SELECT
        COUNT(*) AS my_questions,
        0        AS my_exams
     FROM questions WHERE created_by = ? AND is_active=1"
);
$statsStmt->bind_param('i', $uid);
$statsStmt->execute();
$qRow = $statsStmt->get_result()->fetch_assoc();
$statsStmt->close();
$stats = ['my_questions' => $qRow['my_questions']];

$examStmt = $conn->prepare(
    "SELECT COUNT(*) AS my_exams,
            SUM(status='active') AS active_exams
     FROM exams WHERE created_by = ?"
);
$examStmt->bind_param('i', $uid);
$examStmt->execute();
$eRow = $examStmt->get_result()->fetch_assoc();
$examStmt->close();
$stats['my_exams']     = $eRow['my_exams'];
$stats['active_exams'] = $eRow['active_exams'] ?? 0;

$attStmt = $conn->prepare(
    "SELECT COUNT(*) FROM student_exams se
     JOIN exams e ON e.id=se.exam_id
     WHERE e.created_by=? AND se.status='completed'"
);
$attStmt->bind_param('i', $uid);
$attStmt->execute();
$stats['total_attempts'] = $attStmt->get_result()->fetch_row()[0];
$attStmt->close();

$passStmt = $conn->prepare(
    "SELECT COALESCE(ROUND(AVG(r.is_passed)*100,1),0) AS pass_rate
     FROM results r JOIN exams e ON e.id = r.exam_id WHERE e.created_by = ?"
);
$passStmt->bind_param('i', $uid);
$passStmt->execute();
$stats['pass_rate'] = $passStmt->get_result()->fetch_assoc()['pass_rate'];
$passStmt->close();

// Recent activity for my exams
$recStmt = $conn->prepare(
    "SELECT se.started_at, se.status, u.full_name, e.title AS exam_title,
            r.percentage, r.grade, r.is_passed
     FROM   student_exams se
     JOIN   exams e ON e.id = se.exam_id
     JOIN   users u ON u.id = se.student_id
     LEFT JOIN results r ON r.student_exam_id = se.id
     WHERE  e.created_by = ?
     ORDER  BY se.started_at DESC
     LIMIT  8"
);
$recStmt->bind_param('i', $uid);
$recStmt->execute();
$recentAttempts = $recStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$recStmt->close();

$conn->close();
$pageTitle = 'Instructor Dashboard';
?>
<?php require_once __DIR__ . '/../includes/header.php'; ?>
<?php require_once __DIR__ . '/../includes/navbar.php'; ?>

<div class="container-fluid py-4">
    <?= renderFlash() ?>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-0"><i class="bi bi-person-workspace me-2 text-primary"></i>Instructor Dashboard</h2>
            <small class="text-muted">Welcome, <?= htmlspecialchars($_SESSION['full_name']) ?></small>
        </div>
        <span class="text-muted small"><?= date('l, d F Y') ?></span>
    </div>

    <!-- Stats -->
    <div class="row g-3 mb-4">
        <?php
        $cards = [
            ['label'=>'My Questions',    'value'=>$stats['my_questions'],   'icon'=>'question-circle',  'color'=>'primary'],
            ['label'=>'My Exams',        'value'=>$stats['my_exams'],       'icon'=>'file-earmark-text','color'=>'success'],
            ['label'=>'Active Exams',    'value'=>$stats['active_exams'],   'icon'=>'play-circle',      'color'=>'warning'],
            ['label'=>'Total Attempts',  'value'=>$stats['total_attempts'], 'icon'=>'check2-circle',    'color'=>'info'],
            ['label'=>'Avg Pass Rate',   'value'=>$stats['pass_rate'].'%',  'icon'=>'trophy',           'color'=>'success'],
        ];
        foreach ($cards as $c): ?>
        <div class="col-6 col-md-4 col-lg-2">
            <div class="card stat-card border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="stat-icon bg-<?= $c['color'] ?> bg-opacity-10 text-<?= $c['color'] ?>">
                        <i class="bi bi-<?= $c['icon'] ?>"></i>
                    </div>
                    <div>
                        <div class="fs-3 fw-bold"><?= $c['value'] ?></div>
                        <div class="text-muted small"><?= $c['label'] ?></div>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Quick Links -->
    <div class="row g-3 mb-4">
        <?php
        $links = [
            ['href'=>'create_question.php','icon'=>'plus-circle',    'label'=>'New Question','color'=>'primary'],
            ['href'=>'view_questions.php', 'icon'=>'list-ul',        'label'=>'My Questions','color'=>'info'],
            ['href'=>'create_exam.php',    'icon'=>'file-earmark-plus','label'=>'New Exam', 'color'=>'success'],
            ['href'=>'view_exams.php',     'icon'=>'collection',     'label'=>'My Exams',   'color'=>'warning'],
            ['href'=>'student_results.php','icon'=>'bar-chart',      'label'=>'Results',    'color'=>'secondary'],
        ];
        foreach ($links as $l): ?>
        <div class="col-6 col-md-2">
            <a href="<?= $l['href'] ?>" class="card text-decoration-none text-center p-3 border-0 shadow-sm quick-action-card">
                <i class="bi bi-<?= $l['icon'] ?> fs-1 text-<?= $l['color'] ?>"></i>
                <div class="mt-2 fw-medium small text-dark"><?= $l['label'] ?></div>
            </a>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Recent attempts table -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white fw-semibold py-3">
            <i class="bi bi-clock-history me-2 text-primary"></i>Recent Student Attempts
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0 small">
                    <thead class="table-light">
                        <tr>
                            <th>Student</th><th>Exam</th><th>Score</th><th>Grade</th><th>Status</th><th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentAttempts as $a): ?>
                        <tr>
                            <td><?= htmlspecialchars($a['full_name']) ?></td>
                            <td><?= htmlspecialchars($a['exam_title']) ?></td>
                            <td><?= $a['percentage'] !== null ? $a['percentage'].'%' : '—' ?></td>
                            <td>
                                <?php if ($a['grade']): ?>
                                <span class="badge bg-<?= gradeBadgeClass($a['grade']) ?>"><?= $a['grade'] ?></span>
                                <?php else: ?>—<?php endif; ?>
                            </td>
                            <td><span class="badge bg-<?= $a['status']==='completed'?'success':'warning' ?>"><?= ucfirst($a['status']) ?></span></td>
                            <td><?= formatDate($a['started_at'], 'd M Y') ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($recentAttempts)): ?>
                        <tr><td colspan="6" class="text-center text-muted py-3">No attempts yet.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
