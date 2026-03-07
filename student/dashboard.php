<?php
// student/dashboard.php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
checkAuth('student');

$conn = getDBConnection();
$uid  = (int)$_SESSION['user_id'];

// Stats – single combined query for efficiency
$statsRow = $conn->prepare(
    "SELECT
        COUNT(*) AS total_exams,
        COALESCE(ROUND(AVG(percentage),1),0) AS avg_score,
        SUM(is_passed) AS total_passed
     FROM results WHERE student_id = ?"
);
$statsRow->bind_param('i', $uid);
$statsRow->execute();
$statsData   = $statsRow->get_result()->fetch_assoc();
$statsRow->close();
$totalExams  = $statsData['total_exams'];
$avgScore    = $statsData['avg_score'];
$totalPassed = $statsData['total_passed'];

$gradeStmt = $conn->prepare(
    "SELECT grade FROM results WHERE student_id=?
     ORDER BY FIELD(grade,'A+','A','B+','B','C','F') LIMIT 1"
);
$gradeStmt->bind_param('i', $uid);
$gradeStmt->execute();
$bestGradeRow = $gradeStmt->get_result()->fetch_row();
$gradeStmt->close();
$bestGrade = $bestGradeRow ? $bestGradeRow[0] : 'N/A';

// Available exams (active, time within window, not exceeded max_attempts)
$availStmt = $conn->prepare(
    "SELECT e.*, cat.name AS category_name, u.full_name AS instructor_name,
            COUNT(DISTINCT eq.question_id) AS qcount,
            (SELECT COUNT(*) FROM student_exams se2
             WHERE se2.exam_id=e.id AND se2.student_id=? AND se2.status='completed') AS my_attempts
     FROM   exams e
     JOIN   categories cat ON cat.id = e.category_id
     JOIN   users u        ON u.id   = e.created_by
     LEFT JOIN exam_questions eq ON eq.exam_id = e.id
     WHERE  e.status = 'active'
       AND  e.start_time <= NOW()
       AND  e.end_time   >= NOW()
     GROUP  BY e.id
     HAVING my_attempts < e.max_attempts
     ORDER  BY e.start_time ASC
     LIMIT  5"
);
$availStmt->bind_param('i', $uid);
$availStmt->execute();
$availableExams = $availStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$availStmt->close();

// Recent results
$recStmt = $conn->prepare(
    "SELECT r.*, e.title AS exam_title, se.started_at
     FROM   results r
     JOIN   student_exams se ON se.id = r.student_exam_id
     JOIN   exams e          ON e.id  = r.exam_id
     WHERE  r.student_id = ?
     ORDER  BY r.calculated_at DESC
     LIMIT  5"
);
$recStmt->bind_param('i', $uid);
$recStmt->execute();
$recentResults = $recStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$recStmt->close();

$conn->close();
$pageTitle = 'Student Dashboard';
?>
<?php require_once __DIR__ . '/../includes/header.php'; ?>
<?php require_once __DIR__ . '/../includes/navbar.php'; ?>

<div class="container-fluid py-4">
    <?= renderFlash() ?>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-0"><i class="bi bi-mortarboard me-2 text-primary"></i>My Dashboard</h2>
            <small class="text-muted">Welcome back, <?= htmlspecialchars($_SESSION['full_name']) ?>!</small>
        </div>
        <span class="text-muted small"><?= date('l, d F Y') ?></span>
    </div>

    <!-- Stats -->
    <div class="row g-3 mb-4">
        <?php
        $cards = [
            ['label'=>'Exams Taken',  'value'=>$totalExams,  'icon'=>'clipboard-check',  'color'=>'primary'],
            ['label'=>'Avg Score',    'value'=>$avgScore.'%','icon'=>'percent',           'color'=>'info'],
            ['label'=>'Passed',       'value'=>$totalPassed, 'icon'=>'check-circle',      'color'=>'success'],
            ['label'=>'Best Grade',   'value'=>$bestGrade,   'icon'=>'trophy',            'color'=>'warning'],
        ];
        foreach ($cards as $c): ?>
        <div class="col-6 col-md-3">
            <div class="card stat-card border-0 shadow-sm">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="stat-icon bg-<?= $c['color'] ?> bg-opacity-10 text-<?= $c['color'] ?>">
                        <i class="bi bi-<?= $c['icon'] ?>"></i>
                    </div>
                    <div>
                        <div class="fs-2 fw-bold"><?= $c['value'] ?></div>
                        <div class="text-muted small"><?= $c['label'] ?></div>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <div class="row g-3">
        <!-- Available Exams -->
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white d-flex justify-content-between align-items-center fw-semibold py-3">
                    <span><i class="bi bi-clipboard-check me-2 text-success"></i>Available Exams</span>
                    <a href="available_exams.php" class="btn btn-sm btn-outline-primary">View All</a>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($availableExams)): ?>
                    <div class="p-4 text-center text-muted">
                        <i class="bi bi-calendar-x fs-1 d-block mb-2"></i>
                        No exams available right now.
                    </div>
                    <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($availableExams as $exam): ?>
                        <div class="list-group-item px-4 py-3">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h6 class="mb-1 fw-semibold"><?= htmlspecialchars($exam['title']) ?></h6>
                                    <small class="text-muted">
                                        <?= htmlspecialchars($exam['category_name']) ?> •
                                        <?= $exam['qcount'] ?> questions •
                                        <?= formatDuration((int)$exam['duration_minutes']) ?> •
                                        <?= $exam['total_marks'] ?> marks
                                    </small>
                                </div>
                                <a href="take_exam.php?exam_id=<?= $exam['id'] ?>"
                                   class="btn btn-success btn-sm ms-3">
                                    <i class="bi bi-play-fill me-1"></i>Start
                                </a>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Recent Results -->
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white d-flex justify-content-between align-items-center fw-semibold py-3">
                    <span><i class="bi bi-trophy me-2 text-warning"></i>Recent Results</span>
                    <a href="view_results.php" class="btn btn-sm btn-outline-primary">View All</a>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($recentResults)): ?>
                    <div class="p-4 text-center text-muted">
                        <i class="bi bi-inbox fs-1 d-block mb-2"></i>No results yet.
                    </div>
                    <?php else: ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($recentResults as $r): ?>
                        <li class="list-group-item px-4 py-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="fw-medium small"><?= htmlspecialchars($r['exam_title']) ?></div>
                                    <small class="text-muted"><?= formatDate($r['started_at'], 'd M Y') ?></small>
                                </div>
                                <div class="text-end">
                                    <span class="badge bg-<?= gradeBadgeClass($r['grade']) ?> fs-6 me-1">
                                        <?= $r['grade'] ?>
                                    </span>
                                    <span class="badge bg-<?= $r['is_passed'] ? 'success' : 'danger' ?>">
                                        <?= $r['is_passed'] ? 'PASS' : 'FAIL' ?>
                                    </span>
                                    <div class="small text-muted mt-1"><?= $r['percentage'] ?>%</div>
                                </div>
                            </div>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
