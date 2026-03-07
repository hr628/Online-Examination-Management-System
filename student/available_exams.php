<?php
// student/available_exams.php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
checkAuth('student');

$conn = getDBConnection();
$uid  = (int)$_SESSION['user_id'];

$catFilter = (int)($_GET['category'] ?? 0);

$baseSql = "SELECT e.*, cat.name AS category_name, u.full_name AS instructor_name,
            COUNT(DISTINCT eq.question_id) AS qcount,
            (SELECT COUNT(*) FROM student_exams se2
             WHERE  se2.exam_id  = e.id
               AND  se2.student_id = ?
               AND  se2.status   = 'completed') AS my_attempts,
            (SELECT COUNT(*) FROM student_exams se3
             WHERE  se3.exam_id  = e.id
               AND  se3.student_id = ?
               AND  se3.status   = 'in_progress') AS in_progress
     FROM   exams e
     JOIN   categories cat ON cat.id = e.category_id
     JOIN   users u        ON u.id   = e.created_by
     LEFT JOIN exam_questions eq ON eq.exam_id = e.id
     WHERE  e.status = 'active'";

if ($catFilter > 0) {
    $stmt = $conn->prepare($baseSql . " AND e.category_id = ? GROUP BY e.id ORDER BY e.start_time ASC");
    $stmt->bind_param('iii', $uid, $uid, $catFilter);
} else {
    $stmt = $conn->prepare($baseSql . " GROUP BY e.id ORDER BY e.start_time ASC");
    $stmt->bind_param('ii', $uid, $uid);
}
$stmt->execute();
$exams = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$categories = $conn->query('SELECT * FROM categories ORDER BY name')->fetch_all(MYSQLI_ASSOC);
$conn->close();

$pageTitle = 'Available Exams';
?>
<?php require_once __DIR__ . '/../includes/header.php'; ?>
<?php require_once __DIR__ . '/../includes/navbar.php'; ?>

<div class="container-fluid py-4">
    <?= renderFlash() ?>
    <h2 class="fw-bold mb-4"><i class="bi bi-clipboard-check me-2 text-primary"></i>Available Exams</h2>

    <!-- Category filter -->
    <div class="mb-4">
        <a href="available_exams.php" class="btn btn-sm <?= !$catFilter ? 'btn-primary' : 'btn-outline-secondary' ?> me-1">All</a>
        <?php foreach ($categories as $cat): ?>
        <a href="?category=<?= $cat['id'] ?>"
           class="btn btn-sm <?= $catFilter === $cat['id'] ? 'btn-primary' : 'btn-outline-secondary' ?> me-1">
            <?= htmlspecialchars($cat['name']) ?>
        </a>
        <?php endforeach; ?>
    </div>

    <?php if (empty($exams)): ?>
    <div class="text-center py-5">
        <i class="bi bi-calendar-x fs-1 text-muted d-block mb-3"></i>
        <h5 class="text-muted">No active exams available right now.</h5>
    </div>
    <?php else: ?>
    <div class="row g-3">
        <?php foreach ($exams as $exam):
            $now         = time();
            $startTime   = strtotime($exam['start_time']);
            $endTime     = strtotime($exam['end_time']);
            $canStart    = $now >= $startTime && $now <= $endTime && (int)$exam['my_attempts'] < (int)$exam['max_attempts'];
            $isUpcoming  = $now < $startTime;
            $isExpired   = $now > $endTime;
            $maxReached  = (int)$exam['my_attempts'] >= (int)$exam['max_attempts'];
        ?>
        <div class="col-md-6 col-lg-4">
            <div class="card border-0 shadow-sm h-100 <?= $canStart ? '' : 'opacity-75' ?>">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <h6 class="fw-bold mb-0"><?= htmlspecialchars($exam['title']) ?></h6>
                        <?php if ($isUpcoming): ?>
                        <span class="badge bg-warning text-dark">Upcoming</span>
                        <?php elseif ($isExpired): ?>
                        <span class="badge bg-secondary">Expired</span>
                        <?php elseif ($maxReached): ?>
                        <span class="badge bg-secondary">Max Attempts</span>
                        <?php else: ?>
                        <span class="badge bg-success">Available</span>
                        <?php endif; ?>
                    </div>

                    <p class="small text-muted mb-2">
                        <i class="bi bi-tag me-1"></i><?= htmlspecialchars($exam['category_name']) ?><br>
                        <i class="bi bi-person me-1"></i><?= htmlspecialchars($exam['instructor_name']) ?>
                    </p>

                    <?php if ($exam['description']): ?>
                    <p class="small mb-2"><?= htmlspecialchars(substr($exam['description'], 0, 100)) ?></p>
                    <?php endif; ?>

                    <div class="row g-1 text-center small mb-3">
                        <div class="col-4">
                            <div class="bg-light rounded p-1">
                                <div class="fw-bold"><?= $exam['qcount'] ?></div>
                                <div class="text-muted" style="font-size:.7rem">Questions</div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="bg-light rounded p-1">
                                <div class="fw-bold"><?= formatDuration((int)$exam['duration_minutes']) ?></div>
                                <div class="text-muted" style="font-size:.7rem">Duration</div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="bg-light rounded p-1">
                                <div class="fw-bold"><?= $exam['total_marks'] ?></div>
                                <div class="text-muted" style="font-size:.7rem">Marks</div>
                            </div>
                        </div>
                    </div>

                    <div class="small text-muted mb-3">
                        <i class="bi bi-calendar me-1"></i>
                        <?= formatDate($exam['start_time'], 'd M Y H:i') ?> –
                        <?= formatDate($exam['end_time'],   'd M Y H:i') ?>
                    </div>

                    <div class="d-flex justify-content-between align-items-center">
                        <small class="text-muted">
                            Attempts: <?= $exam['my_attempts'] ?> / <?= $exam['max_attempts'] ?>
                        </small>
                        <?php if ($canStart): ?>
                        <a href="take_exam.php?exam_id=<?= $exam['id'] ?>"
                           class="btn btn-success btn-sm"
                           onclick="return confirm('Start exam now? The timer will begin immediately.')">
                            <i class="bi bi-play-fill me-1"></i>Start Exam
                        </a>
                        <?php elseif ($isUpcoming): ?>
                        <span class="text-muted small">Starts <?= formatDate($exam['start_time'], 'd M H:i') ?></span>
                        <?php else: ?>
                        <span class="btn btn-secondary btn-sm disabled">Not Available</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
