<?php
// student/view_results.php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
checkAuth('student');

$conn = getDBConnection();
$uid  = (int)$_SESSION['user_id'];

$examId  = (int)($_GET['exam_id'] ?? 0);
$seId    = (int)($_GET['se_id']   ?? 0);

$result  = null;
$answers = [];

if ($seId > 0) {
    // Show specific attempt
    $stmt = $conn->prepare(
        "SELECT r.*, e.title AS exam_title, cat.name AS category_name,
                e.total_marks AS exam_total, e.passing_marks, e.duration_minutes,
                se.started_at, se.submitted_at, se.time_taken_minutes, se.attempt_number
         FROM   results r
         JOIN   student_exams se ON se.id = r.student_exam_id
         JOIN   exams e          ON e.id  = r.exam_id
         JOIN   categories cat   ON cat.id = e.category_id
         WHERE  r.student_exam_id = ? AND r.student_id = ?"
    );
    $stmt->bind_param('ii', $seId, $uid);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($result) {
        // Load answer breakdown
        $stmt = $conn->prepare(
            "SELECT sa.*, q.question_text, q.option_a, q.option_b, q.option_c, q.option_d,
                    q.correct_answer, q.explanation, eq.marks AS exam_marks
             FROM   student_answers sa
             JOIN   questions q         ON q.id  = sa.question_id
             JOIN   exam_questions eq   ON eq.question_id = q.id AND eq.exam_id = ?
             WHERE  sa.student_exam_id = ?
             ORDER  BY eq.order_number"
        );
        $stmt->bind_param('ii', $result['exam_id'], $seId);
        $stmt->execute();
        $answers = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    }
} elseif ($examId > 0) {
    // Get latest completed attempt for this exam
    $stmt = $conn->prepare(
        "SELECT r.*, e.title AS exam_title, cat.name AS category_name,
                e.total_marks AS exam_total, e.passing_marks, e.duration_minutes,
                se.started_at, se.submitted_at, se.time_taken_minutes, se.attempt_number
         FROM   results r
         JOIN   student_exams se ON se.id = r.student_exam_id
         JOIN   exams e          ON e.id  = r.exam_id
         JOIN   categories cat   ON cat.id = e.category_id
         WHERE  r.student_id = ? AND r.exam_id = ?
         ORDER  BY r.calculated_at DESC
         LIMIT  1"
    );
    $stmt->bind_param('ii', $uid, $examId);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

$conn->close();
$pageTitle = $result ? 'Result: ' . $result['exam_title'] : 'My Results';
?>
<?php require_once __DIR__ . '/../includes/header.php'; ?>
<?php require_once __DIR__ . '/../includes/navbar.php'; ?>

<div class="container py-4">
    <?= renderFlash() ?>

    <?php if (!$result): ?>
    <div class="text-center py-5">
        <i class="bi bi-inbox fs-1 text-muted d-block mb-3"></i>
        <h5 class="text-muted">No result found.</h5>
        <a href="exam_history.php" class="btn btn-primary mt-3">View Exam History</a>
    </div>
    <?php else: ?>

    <!-- Result Summary Card -->
    <div class="row justify-content-center mb-4">
        <div class="col-md-8">
            <div class="card border-0 shadow result-summary-card
                <?= $result['is_passed'] ? 'border-success' : 'border-danger' ?> border-top border-4">
                <div class="card-body text-center p-4">
                    <!-- Pass / Fail badge -->
                    <div class="mb-3">
                        <span class="badge bg-<?= $result['is_passed'] ? 'success' : 'danger' ?> fs-5 px-4 py-2">
                            <?= $result['is_passed'] ? '🎉 PASSED' : '❌ FAILED' ?>
                        </span>
                    </div>

                    <h4 class="fw-bold mb-1"><?= htmlspecialchars($result['exam_title']) ?></h4>
                    <p class="text-muted small mb-4">
                        <?= htmlspecialchars($result['category_name']) ?> &bull;
                        Attempt #<?= $result['attempt_number'] ?> &bull;
                        <?= formatDate($result['submitted_at'] ?? $result['started_at'], 'd M Y H:i') ?>
                    </p>

                    <div class="row g-3 mb-4">
                        <div class="col-4">
                            <div class="result-stat">
                                <div class="fs-1 fw-bold text-primary"><?= $result['obtained_marks'] ?></div>
                                <div class="text-muted small">out of <?= $result['total_marks'] ?></div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="result-stat">
                                <div class="fs-1 fw-bold text-<?= $result['percentage']>=50?'success':'danger' ?>">
                                    <?= $result['percentage'] ?>%
                                </div>
                                <div class="text-muted small">Score</div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="result-stat">
                                <div class="display-4 fw-bold text-<?= gradeBadgeClass($result['grade']) ?>">
                                    <?= $result['grade'] ?>
                                </div>
                                <div class="text-muted small">Grade</div>
                            </div>
                        </div>
                    </div>

                    <!-- Progress bar -->
                    <div class="mb-3">
                        <div class="progress" style="height:12px">
                            <div class="progress-bar bg-<?= $result['percentage']>=75?'success':($result['percentage']>=50?'warning':'danger') ?>"
                                 style="width:<?= $result['percentage'] ?>%"></div>
                        </div>
                        <div class="d-flex justify-content-between small text-muted mt-1">
                            <span>0</span>
                            <span>Passing: <?= $result['passing_marks'] ?> marks</span>
                            <span><?= $result['total_marks'] ?></span>
                        </div>
                    </div>

                    <?php if ($result['time_taken_minutes']): ?>
                    <p class="text-muted small mb-0">
                        <i class="bi bi-clock me-1"></i>
                        Time taken: <?= formatDuration($result['time_taken_minutes']) ?>
                        / <?= formatDuration((int)$result['duration_minutes']) ?>
                    </p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Question Breakdown (only when we have answers) -->
    <?php if (!empty($answers)): ?>
    <h5 class="fw-bold mb-3">Answer Breakdown</h5>
    <?php foreach ($answers as $i => $a):
        $userAns    = $a['selected_answer'];
        $correctAns = $a['correct_answer'];
        $isCorrect  = (int)$a['is_correct'];
        $optionMap  = [
            'A' => $a['option_a'],
            'B' => $a['option_b'],
            'C' => $a['option_c'],
            'D' => $a['option_d'],
        ];
    ?>
    <div class="card border-0 shadow-sm mb-3 <?= $isCorrect ? 'border-start border-success border-3' : 'border-start border-danger border-3' ?>">
        <div class="card-body">
            <div class="d-flex justify-content-between mb-2">
                <span class="fw-semibold">Q<?= $i + 1 ?>. <?= htmlspecialchars($a['question_text']) ?></span>
                <?php if ($isCorrect): ?>
                <span class="badge bg-success ms-2"><i class="bi bi-check"></i> +<?= $a['marks_obtained'] ?></span>
                <?php else: ?>
                <span class="badge bg-danger ms-2"><i class="bi bi-x"></i> 0</span>
                <?php endif; ?>
            </div>

            <div class="row g-2 small">
                <?php foreach ($optionMap as $key => $val):
                    if ($val === null) continue;
                    $cls = '';
                    if ($key === $correctAns) $cls = 'bg-success bg-opacity-15 border-success text-success fw-bold';
                    elseif ($key === $userAns && !$isCorrect) $cls = 'bg-danger bg-opacity-15 border-danger text-danger';
                ?>
                <div class="col-md-6">
                    <div class="p-2 rounded border <?= $cls ?>">
                        <strong><?= $key ?>.</strong> <?= htmlspecialchars($val) ?>
                        <?php if ($key === $correctAns): ?><i class="bi bi-check-circle-fill text-success ms-1"></i><?php endif; ?>
                        <?php if ($key === $userAns && !$isCorrect): ?><i class="bi bi-x-circle-fill text-danger ms-1"></i><?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <?php if ($a['explanation']): ?>
            <div class="mt-2 p-2 bg-light rounded small">
                <strong>Explanation:</strong> <?= htmlspecialchars($a['explanation']) ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>

    <div class="d-flex gap-2 mt-4">
        <a href="exam_history.php" class="btn btn-outline-secondary">
            <i class="bi bi-clock-history me-1"></i>Exam History
        </a>
        <a href="available_exams.php" class="btn btn-primary">
            <i class="bi bi-clipboard-check me-1"></i>More Exams
        </a>
    </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
