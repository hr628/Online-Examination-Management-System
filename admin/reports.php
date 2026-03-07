<?php
// admin/reports.php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
checkAuth('admin');

$conn = getDBConnection();

$studentPerf = $conn->query('SELECT * FROM view_student_performance ORDER BY avg_percentage DESC')->fetch_all(MYSQLI_ASSOC);
$examStats   = $conn->query('SELECT * FROM view_exam_statistics ORDER BY avg_score DESC')->fetch_all(MYSQLI_ASSOC);
$questionUse = $conn->query('SELECT * FROM view_question_usage ORDER BY usage_count DESC LIMIT 20')->fetch_all(MYSQLI_ASSOC);
$conn->close();

$pageTitle = 'Reports';
?>
<?php require_once __DIR__ . '/../includes/header.php'; ?>
<?php require_once __DIR__ . '/../includes/navbar.php'; ?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold mb-0"><i class="bi bi-bar-chart me-2 text-primary"></i>Reports &amp; Analytics</h2>
        <button class="btn btn-outline-secondary btn-sm" onclick="window.print()">
            <i class="bi bi-printer me-1"></i>Print
        </button>
    </div>

    <!-- Student Performance -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-primary text-white fw-semibold py-3">
            <i class="bi bi-person-lines-fill me-2"></i>Student Performance Summary
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped table-hover mb-0 small">
                    <thead class="table-dark">
                        <tr>
                            <th>#</th>
                            <th>Student Name</th>
                            <th>Username</th>
                            <th>Exams Taken</th>
                            <th>Avg %</th>
                            <th>Best %</th>
                            <th>Worst %</th>
                            <th>Best Grade</th>
                            <th>Passed</th>
                            <th>Failed</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($studentPerf as $i => $s): ?>
                        <tr>
                            <td><?= $i+1 ?></td>
                            <td><?= htmlspecialchars($s['student_name']) ?></td>
                            <td><code><?= htmlspecialchars($s['username']) ?></code></td>
                            <td><?= $s['total_exams_taken'] ?></td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="progress flex-grow-1" style="height:8px;min-width:60px">
                                        <div class="progress-bar bg-<?= $s['avg_percentage']>=75?'success':($s['avg_percentage']>=50?'warning':'danger') ?>"
                                             style="width:<?= $s['avg_percentage'] ?>%"></div>
                                    </div>
                                    <span><?= $s['avg_percentage'] ?>%</span>
                                </div>
                            </td>
                            <td class="text-success fw-medium"><?= $s['best_percentage'] ?>%</td>
                            <td class="text-danger"><?= $s['worst_percentage'] ?>%</td>
                            <td>
                                <span class="badge bg-<?= gradeBadgeClass($s['best_grade'] ?? 'F') ?>">
                                    <?= $s['best_grade'] ?? 'N/A' ?>
                                </span>
                            </td>
                            <td class="text-success fw-bold"><?= $s['total_passed'] ?></td>
                            <td class="text-danger"><?= $s['total_failed'] ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($studentPerf)): ?>
                        <tr><td colspan="10" class="text-center text-muted py-4">No data.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Exam Statistics -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-success text-white fw-semibold py-3">
            <i class="bi bi-file-earmark-bar-graph me-2"></i>Exam Statistics
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped table-hover mb-0 small">
                    <thead class="table-dark">
                        <tr>
                            <th>Exam</th>
                            <th>Category</th>
                            <th>Status</th>
                            <th>Attempts</th>
                            <th>Students</th>
                            <th>Avg Score</th>
                            <th>Highest</th>
                            <th>Lowest</th>
                            <th>Pass Rate</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($examStats as $es): ?>
                        <tr>
                            <td class="fw-medium"><?= htmlspecialchars($es['title']) ?></td>
                            <td><?= htmlspecialchars($es['category_name']) ?></td>
                            <td>
                                <span class="badge bg-<?= examStatusBadge($es['status']) ?>">
                                    <?= ucfirst($es['status']) ?>
                                </span>
                            </td>
                            <td><?= $es['total_attempts'] ?></td>
                            <td><?= $es['distinct_students'] ?></td>
                            <td><?= $es['avg_score'] ?>%</td>
                            <td class="text-success"><?= $es['highest_score'] ?>%</td>
                            <td class="text-danger"><?= $es['lowest_score'] ?>%</td>
                            <td>
                                <div class="d-flex align-items-center gap-1">
                                    <div class="progress flex-grow-1" style="height:8px;min-width:50px">
                                        <div class="progress-bar bg-<?= $es['pass_rate']>=70?'success':($es['pass_rate']>=50?'warning':'danger') ?>"
                                             style="width:<?= $es['pass_rate'] ?>%"></div>
                                    </div>
                                    <span><?= $es['pass_rate'] ?>%</span>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($examStats)): ?>
                        <tr><td colspan="9" class="text-center text-muted py-4">No data.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Question Usage -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-info text-white fw-semibold py-3">
            <i class="bi bi-question-circle me-2"></i>Top 20 Question Usage
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped table-hover mb-0 small">
                    <thead class="table-dark">
                        <tr>
                            <th>#</th>
                            <th>Question</th>
                            <th>Type</th>
                            <th>Difficulty</th>
                            <th>Bank</th>
                            <th>Used In</th>
                            <th>Exams</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($questionUse as $i => $qu): ?>
                        <tr>
                            <td><?= $i+1 ?></td>
                            <td><?= htmlspecialchars($qu['question_text']) ?></td>
                            <td><span class="badge bg-secondary"><?= strtoupper($qu['question_type']) ?></span></td>
                            <td>
                                <span class="badge bg-<?= $qu['difficulty']==='easy'?'success':($qu['difficulty']==='medium'?'warning text-dark':'danger') ?>">
                                    <?= ucfirst($qu['difficulty']) ?>
                                </span>
                            </td>
                            <td><?= htmlspecialchars($qu['question_bank_name']) ?></td>
                            <td><span class="badge bg-primary"><?= $qu['usage_count'] ?></span></td>
                            <td class="text-muted" style="max-width:200px"><?= htmlspecialchars($qu['exam_list'] ?? '—') ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($questionUse)): ?>
                        <tr><td colspan="7" class="text-center text-muted py-4">No data.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
