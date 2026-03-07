<?php
// instructor/student_results.php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
checkAuth('instructor');

$conn    = getDBConnection();
$uid     = (int)$_SESSION['user_id'];

// Exam selector
$selectedExam = (int)($_GET['exam_id'] ?? 0);

$myExamsStmt = $conn->prepare(
    "SELECT e.*, cat.name AS category_name
     FROM   exams e JOIN categories cat ON cat.id=e.category_id
     WHERE  e.created_by = ?
     ORDER  BY e.created_at DESC"
);
$myExamsStmt->bind_param('i', $uid);
$myExamsStmt->execute();
$myExams = $myExamsStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$myExamsStmt->close();

$examStats = null;
$results   = [];

if ($selectedExam > 0) {
    // Verify ownership
    $chk = $conn->prepare('SELECT id FROM exams WHERE id=? AND created_by=?');
    $chk->bind_param('ii', $selectedExam, $uid);
    $chk->execute();
    if ($chk->get_result()->num_rows > 0) {
        $stmt = $conn->prepare('SELECT * FROM view_exam_statistics WHERE exam_id=?');
        $stmt->bind_param('i', $selectedExam);
        $stmt->execute();
        $examStats = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        $stmt = $conn->prepare(
            "SELECT u.full_name, u.username, se.started_at, se.submitted_at,
                    se.time_taken_minutes, se.attempt_number,
                    r.total_marks, r.obtained_marks, r.percentage, r.grade, r.is_passed
             FROM   student_exams se
             JOIN   users u ON u.id = se.student_id
             LEFT JOIN results r ON r.student_exam_id = se.id
             WHERE  se.exam_id = ?
             ORDER  BY r.percentage IS NULL, r.percentage DESC, se.started_at DESC"
        );
        $stmt->bind_param('i', $selectedExam);
        $stmt->execute();
        $results = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    }
    $chk->close();
}

$conn->close();
$pageTitle = 'Student Results';
?>
<?php require_once __DIR__ . '/../includes/header.php'; ?>
<?php require_once __DIR__ . '/../includes/navbar.php'; ?>

<div class="container-fluid py-4">
    <h2 class="fw-bold mb-4"><i class="bi bi-bar-chart me-2 text-primary"></i>Student Results</h2>

    <!-- Exam selector -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-5">
                    <label class="form-label fw-medium">Select Exam</label>
                    <select name="exam_id" class="form-select" onchange="this.form.submit()">
                        <option value="">-- Choose an exam --</option>
                        <?php foreach ($myExams as $exam): ?>
                        <option value="<?= $exam['id'] ?>" <?= $selectedExam===$exam['id']?'selected':'' ?>>
                            <?= htmlspecialchars($exam['title']) ?>
                            [<?= ucfirst($exam['status']) ?>]
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-primary">View Results</button>
                </div>
            </form>
        </div>
    </div>

    <?php if ($examStats): ?>
    <!-- Exam summary -->
    <div class="row g-3 mb-4">
        <?php
        $sumCards = [
            ['label'=>'Total Attempts',  'value'=>$examStats['total_attempts'],  'color'=>'primary'],
            ['label'=>'Avg Score',       'value'=>$examStats['avg_score'].'%',   'color'=>'info'],
            ['label'=>'Highest',         'value'=>$examStats['highest_score'].'%','color'=>'success'],
            ['label'=>'Lowest',          'value'=>$examStats['lowest_score'].'%', 'color'=>'danger'],
            ['label'=>'Pass Rate',       'value'=>$examStats['pass_rate'].'%',   'color'=>'warning'],
        ];
        foreach ($sumCards as $sc): ?>
        <div class="col-6 col-md-2">
            <div class="card border-top border-<?= $sc['color'] ?> border-3 shadow-sm text-center p-3">
                <div class="fs-3 fw-bold text-<?= $sc['color'] ?>"><?= $sc['value'] ?></div>
                <div class="text-muted small"><?= $sc['label'] ?></div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Results Table -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white fw-semibold py-3">
            <i class="bi bi-table me-2"></i>Student-wise Results —
            <span class="text-primary"><?= htmlspecialchars($examStats['title']) ?></span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>#</th>
                            <th>Student</th>
                            <th>Attempt</th>
                            <th>Started</th>
                            <th>Time Taken</th>
                            <th>Score</th>
                            <th>Percentage</th>
                            <th>Grade</th>
                            <th>Result</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($results as $i => $r): ?>
                        <tr>
                            <td><?= $i + 1 ?></td>
                            <td>
                                <div class="fw-medium"><?= htmlspecialchars($r['full_name']) ?></div>
                                <small class="text-muted"><?= htmlspecialchars($r['username']) ?></small>
                            </td>
                            <td><span class="badge bg-secondary">#<?= $r['attempt_number'] ?></span></td>
                            <td><?= formatDate($r['started_at']) ?></td>
                            <td><?= $r['time_taken_minutes'] ? formatDuration($r['time_taken_minutes']) : '—' ?></td>
                            <td>
                                <?php if ($r['obtained_marks'] !== null): ?>
                                <?= $r['obtained_marks'] ?> / <?= $r['total_marks'] ?>
                                <?php else: ?>—<?php endif; ?>
                            </td>
                            <td>
                                <?php if ($r['percentage'] !== null): ?>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="progress flex-grow-1" style="height:8px;min-width:60px">
                                        <div class="progress-bar bg-<?= $r['percentage']>=75?'success':($r['percentage']>=50?'warning':'danger') ?>"
                                             style="width:<?= $r['percentage'] ?>%"></div>
                                    </div>
                                    <span><?= $r['percentage'] ?>%</span>
                                </div>
                                <?php else: ?>—<?php endif; ?>
                            </td>
                            <td>
                                <?php if ($r['grade']): ?>
                                <span class="badge bg-<?= gradeBadgeClass($r['grade']) ?> fs-6">
                                    <?= $r['grade'] ?>
                                </span>
                                <?php else: ?>—<?php endif; ?>
                            </td>
                            <td>
                                <?php if ($r['is_passed'] !== null): ?>
                                <span class="badge bg-<?= $r['is_passed'] ? 'success' : 'danger' ?>">
                                    <?= $r['is_passed'] ? 'PASS' : 'FAIL' ?>
                                </span>
                                <?php else: ?>—<?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($results)): ?>
                        <tr><td colspan="9" class="text-center text-muted py-4">No attempts for this exam yet.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php elseif ($selectedExam > 0): ?>
    <div class="alert alert-warning">Exam not found or access denied.</div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
