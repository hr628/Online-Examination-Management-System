<?php
// student/exam_history.php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
checkAuth('student');

$conn = getDBConnection();
$uid  = (int)$_SESSION['user_id'];

$stmt = $conn->prepare(
    "SELECT se.id AS student_exam_id, se.started_at, se.submitted_at,
            se.time_taken_minutes, se.status, se.attempt_number,
            e.id AS exam_id, e.title AS exam_title, e.total_marks, e.duration_minutes,
            cat.name AS category_name,
            r.obtained_marks, r.percentage, r.grade, r.is_passed, r.calculated_at
     FROM   student_exams se
     JOIN   exams e        ON e.id   = se.exam_id
     JOIN   categories cat ON cat.id = e.category_id
     LEFT JOIN results r   ON r.student_exam_id = se.id
     WHERE  se.student_id = ?
     ORDER  BY se.started_at DESC"
);
$stmt->bind_param('i', $uid);
$stmt->execute();
$history = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$conn->close();

$pageTitle = 'Exam History';
?>
<?php require_once __DIR__ . '/../includes/header.php'; ?>
<?php require_once __DIR__ . '/../includes/navbar.php'; ?>

<div class="container-fluid py-4">
    <h2 class="fw-bold mb-4"><i class="bi bi-clock-history me-2 text-primary"></i>Exam History</h2>

    <?php if (empty($history)): ?>
    <div class="text-center py-5">
        <i class="bi bi-inbox fs-1 text-muted d-block mb-3"></i>
        <h5 class="text-muted">You haven't taken any exams yet.</h5>
        <a href="available_exams.php" class="btn btn-primary mt-3">
            <i class="bi bi-clipboard-check me-1"></i>Browse Available Exams
        </a>
    </div>
    <?php else: ?>

    <!-- Summary row -->
    <?php
    $pcts      = array_column(array_filter($history, fn($h) => $h['percentage'] !== null), 'percentage');
    $avgPct    = count($pcts) > 0 ? round(array_sum($pcts) / count($pcts), 1) : 0;
    $completed = array_filter($history, fn($h) => $h['status'] === 'completed');
    $passed    = array_filter($history, fn($h) => !empty($h['is_passed']));
    ?>
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card stat-card border-0 shadow-sm text-center p-3">
                <div class="fs-2 fw-bold text-primary"><?= count($history) ?></div>
                <div class="text-muted small">Total Attempts</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card stat-card border-0 shadow-sm text-center p-3">
                <div class="fs-2 fw-bold text-success"><?= count($passed) ?></div>
                <div class="text-muted small">Passed</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card stat-card border-0 shadow-sm text-center p-3">
                <div class="fs-2 fw-bold text-danger"><?= count($completed) - count($passed) ?></div>
                <div class="text-muted small">Failed</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card stat-card border-0 shadow-sm text-center p-3">
                <div class="fs-2 fw-bold text-info"><?= $avgPct ?>%</div>
                <div class="text-muted small">Avg Score</div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>Exam</th>
                            <th>Category</th>
                            <th>Attempt</th>
                            <th>Started</th>
                            <th>Time Taken</th>
                            <th>Score</th>
                            <th>Grade</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($history as $h): ?>
                        <tr>
                            <td class="fw-medium"><?= htmlspecialchars($h['exam_title']) ?></td>
                            <td>
                                <span class="badge bg-secondary"><?= htmlspecialchars($h['category_name']) ?></span>
                            </td>
                            <td><span class="badge bg-light text-dark border">#<?= $h['attempt_number'] ?></span></td>
                            <td><?= formatDate($h['started_at'], 'd M Y H:i') ?></td>
                            <td>
                                <?= $h['time_taken_minutes'] ? formatDuration($h['time_taken_minutes']) : '—' ?>
                            </td>
                            <td>
                                <?php if ($h['percentage'] !== null): ?>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="progress flex-grow-1" style="height:6px;min-width:50px">
                                        <div class="progress-bar bg-<?= $h['percentage']>=75?'success':($h['percentage']>=50?'warning':'danger') ?>"
                                             style="width:<?= $h['percentage'] ?>%"></div>
                                    </div>
                                    <span class="small"><?= $h['percentage'] ?>%</span>
                                </div>
                                <?php else: ?>—<?php endif; ?>
                            </td>
                            <td>
                                <?php if ($h['grade']): ?>
                                <span class="badge bg-<?= gradeBadgeClass($h['grade']) ?> fs-6">
                                    <?= $h['grade'] ?>
                                </span>
                                <?php else: ?>—<?php endif; ?>
                            </td>
                            <td>
                                <?php if ($h['status'] === 'completed' && $h['is_passed'] !== null): ?>
                                <span class="badge bg-<?= $h['is_passed'] ? 'success' : 'danger' ?>">
                                    <?= $h['is_passed'] ? 'PASS' : 'FAIL' ?>
                                </span>
                                <?php else: ?>
                                <span class="badge bg-<?= $h['status']==='in_progress'?'warning text-dark':'secondary' ?>">
                                    <?= ucfirst(str_replace('_',' ',$h['status'])) ?>
                                </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($h['status'] === 'completed'): ?>
                                <a href="view_results.php?exam_id=<?= $h['exam_id'] ?>&se_id=<?= $h['student_exam_id'] ?>"
                                   class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-eye me-1"></i>View
                                </a>
                                <?php elseif ($h['status'] === 'in_progress'): ?>
                                <a href="take_exam.php?exam_id=<?= $h['exam_id'] ?>"
                                   class="btn btn-sm btn-warning">
                                    <i class="bi bi-play me-1"></i>Resume
                                </a>
                                <?php else: ?>—<?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
