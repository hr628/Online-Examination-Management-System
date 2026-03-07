<?php
// student/take_exam.php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
checkAuth('student');

$conn    = getDBConnection();
$uid     = (int)$_SESSION['user_id'];
$examId  = (int)($_GET['exam_id'] ?? 0);

if ($examId <= 0) {
    redirectTo(BASE_URL . '/student/available_exams.php');
}

// ── Load exam ────────────────────────────────────────────────
$stmt = $conn->prepare(
    'SELECT e.*, cat.name AS category_name
     FROM   exams e
     JOIN   categories cat ON cat.id = e.category_id
     WHERE  e.id = ? AND e.status = ?'
);
$activeStatus = 'active';
$stmt->bind_param('is', $examId, $activeStatus);
$stmt->execute();
$exam = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$exam) {
    setFlash('danger', 'Exam not found or not available.');
    redirectTo(BASE_URL . '/student/available_exams.php');
}

// Check timing
$now = time();
if ($now < strtotime($exam['start_time'])) {
    setFlash('warning', 'This exam has not started yet.');
    redirectTo(BASE_URL . '/student/available_exams.php');
}
if ($now > strtotime($exam['end_time'])) {
    setFlash('danger', 'This exam has already ended.');
    redirectTo(BASE_URL . '/student/available_exams.php');
}

// Check attempts
$stmt = $conn->prepare(
    "SELECT COUNT(*) FROM student_exams WHERE student_id=? AND exam_id=? AND status='completed'"
);
$stmt->bind_param('ii', $uid, $examId);
$stmt->execute();
$completedAttempts = $stmt->get_result()->fetch_row()[0];
$stmt->close();

if ($completedAttempts >= $exam['max_attempts']) {
    setFlash('warning', 'You have reached the maximum number of attempts for this exam.');
    redirectTo(BASE_URL . '/student/available_exams.php');
}

// Check for in-progress attempt
$stmt = $conn->prepare(
    "SELECT * FROM student_exams WHERE student_id=? AND exam_id=? AND status='in_progress' LIMIT 1"
);
$stmt->bind_param('ii', $uid, $examId);
$stmt->execute();
$existing = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($existing) {
    $studentExamId = $existing['id'];
} else {
    // Create new attempt
    $attemptNo = $completedAttempts + 1;
    $stmt = $conn->prepare(
        "INSERT INTO student_exams (student_id, exam_id, started_at, status, attempt_number)
         VALUES (?, ?, NOW(), 'in_progress', ?)"
    );
    $stmt->bind_param('iii', $uid, $examId, $attemptNo);
    $stmt->execute();
    $studentExamId = $conn->insert_id;
    $stmt->close();
}

// Load questions (randomise if flag set)
$orderBy = $exam['is_randomized'] ? 'RAND()' : 'eq.order_number';
$stmt = $conn->prepare(
    "SELECT q.*, eq.marks AS exam_marks
     FROM   exam_questions eq
     JOIN   questions q ON q.id = eq.question_id
     WHERE  eq.exam_id = ?
     ORDER  BY $orderBy"
);
$stmt->bind_param('i', $examId);
$stmt->execute();
$questions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

if (empty($questions)) {
    setFlash('danger', 'This exam has no questions assigned yet.');
    redirectTo(BASE_URL . '/student/available_exams.php');
}

// Calculate seconds remaining
$startedAt  = strtotime($existing['started_at'] ?? date('Y-m-d H:i:s'));
$elapsed    = $now - $startedAt;
$totalSecs  = (int)$exam['duration_minutes'] * 60;
$remaining  = max(0, $totalSecs - $elapsed);
// Also cap by exam end time
$secsToEnd  = strtotime($exam['end_time']) - $now;
$remaining  = min($remaining, $secsToEnd);

$conn->close();
$pageTitle = $exam['title'];
$csrf      = getCSRFToken();
?>
<?php require_once __DIR__ . '/../includes/header.php'; ?>

<!-- Exam nav is minimal – no main navbar to avoid distraction -->
<nav class="navbar navbar-dark bg-primary py-2">
    <div class="container-fluid">
        <span class="navbar-brand fw-bold mb-0">
            <i class="bi bi-mortarboard-fill me-2"></i>OEMS — Exam in Progress
        </span>
        <div id="examTimer" class="badge bg-warning text-dark fs-6 px-3 py-2">
            <i class="bi bi-clock me-1"></i>Loading…
        </div>
    </div>
</nav>

<div class="container py-4">
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body py-2 px-4 d-flex justify-content-between align-items-center">
            <div>
                <h5 class="mb-0 fw-bold"><?= htmlspecialchars($exam['title']) ?></h5>
                <small class="text-muted">
                    <?= htmlspecialchars($exam['category_name']) ?> &bull;
                    <?= count($questions) ?> questions &bull;
                    Total: <?= $exam['total_marks'] ?> marks
                </small>
            </div>
            <small class="text-muted"><?= htmlspecialchars($_SESSION['full_name']) ?></small>
        </div>
    </div>

    <form id="examForm" method="POST" action="submit_exam.php">
        <input type="hidden" name="csrf_token"     value="<?= $csrf ?>">
        <input type="hidden" name="student_exam_id" value="<?= $studentExamId ?>">
        <input type="hidden" name="exam_id"         value="<?= $examId ?>">

        <?php foreach ($questions as $i => $q): ?>
        <div class="card border-0 shadow-sm mb-3 question-card">
            <div class="card-body">
                <div class="d-flex justify-content-between mb-2">
                    <span class="fw-semibold text-primary">Question <?= $i + 1 ?></span>
                    <span class="badge bg-light text-dark border">
                        <?= $q['exam_marks'] ?> mark<?= $q['exam_marks'] > 1 ? 's' : '' ?>
                        &bull; <?= ucfirst($q['difficulty']) ?>
                    </span>
                </div>
                <p class="mb-3"><?= htmlspecialchars($q['question_text']) ?></p>

                <div class="row g-2">
                    <?php
                    $options = [
                        'A' => $q['option_a'],
                        'B' => $q['option_b'],
                        'C' => $q['option_c'] ?? null,
                        'D' => $q['option_d'] ?? null,
                    ];
                    foreach ($options as $key => $value):
                        if ($value === null) continue;
                    ?>
                    <div class="col-md-6">
                        <label class="option-label w-100 d-flex align-items-center gap-2 p-3 border rounded cursor-pointer">
                            <input type="radio"
                                   name="answer_<?= $q['id'] ?>"
                                   value="<?= $key ?>"
                                   class="form-check-input mt-0 flex-shrink-0"
                                   onchange="countAnswered(<?= $i + 1 ?>)">
                            <strong class="text-primary me-1"><?= $key ?>.</strong>
                            <?= htmlspecialchars($value) ?>
                        </label>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>

        <!-- Submit -->
        <div class="d-flex justify-content-between align-items-center mt-4">
            <small class="text-muted">
                <span id="answeredCount">0</span> / <?= count($questions) ?> answered
            </small>
            <button type="submit" class="btn btn-danger btn-lg px-5"
                    onclick="return confirm('Submit exam? You cannot change your answers after submission.')">
                <i class="bi bi-check2-circle me-1"></i>Submit Exam
            </button>
        </div>
    </form>
</div>

<!-- Timer Script -->
<script src="<?= BASE_URL ?>/assets/js/exam_timer.js"></script>
<script>
const timer = new ExamTimer(<?= $remaining ?>, 'examTimer', function() {
    document.getElementById('examForm').submit();
});
timer.start();

function countAnswered() {
    let count = 0;
    <?php foreach ($questions as $q): ?>
    if (document.querySelector('[name="answer_<?= $q['id'] ?>"]:checked')) count++;
    <?php endforeach; ?>
    document.getElementById('answeredCount').textContent = count;
}

// Style radio labels on click
document.querySelectorAll('.option-label').forEach(label => {
    label.addEventListener('click', function() {
        const name = this.querySelector('input').name;
        document.querySelectorAll(`[name="${name}"]`).forEach(r => {
            r.closest('.option-label').classList.remove('selected');
        });
        this.classList.add('selected');
        countAnswered();
    });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
