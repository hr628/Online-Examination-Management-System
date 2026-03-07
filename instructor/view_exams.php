<?php
// instructor/view_exams.php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
checkAuth('instructor');

$conn = getDBConnection();
$uid  = (int)$_SESSION['user_id'];

$message = '';
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid CSRF token.';
    } else {
        $action = $_POST['action'] ?? '';
        $id     = (int)($_POST['id'] ?? 0);
        if ($action === 'change_status') {
            $newStatus = sanitizeInput($_POST['status'] ?? '');
            if (in_array($newStatus, ['draft','active','completed','cancelled'], true)) {
                $stmt = $conn->prepare('UPDATE exams SET status=? WHERE id=? AND created_by=?');
                $stmt->bind_param('sii', $newStatus, $id, $uid);
                $stmt->execute();
                $stmt->close();
                $message = 'Status updated.';
            }
        } elseif ($action === 'delete') {
            $stmt = $conn->prepare('DELETE FROM exams WHERE id=? AND created_by=?');
            $stmt->bind_param('ii', $id, $uid);
            if ($stmt->execute()) $message = 'Exam deleted.';
            else $error = 'Cannot delete exam (may have student attempts).';
            $stmt->close();
        }
    }
}

$stmt = $conn->prepare(
    "SELECT e.*, cat.name AS category_name,
            COUNT(DISTINCT eq.question_id) AS qcount,
            COUNT(DISTINCT se.id) AS attempt_count
     FROM   exams e
     JOIN   categories cat ON cat.id = e.category_id
     LEFT JOIN exam_questions eq ON eq.exam_id = e.id
     LEFT JOIN student_exams se  ON se.exam_id = e.id
     WHERE  e.created_by = ?
     GROUP  BY e.id
     ORDER  BY e.created_at DESC"
);
$stmt->bind_param('i', $uid);
$stmt->execute();
$exams = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$conn->close();

$pageTitle = 'My Exams';
$csrf      = getCSRFToken();
?>
<?php require_once __DIR__ . '/../includes/header.php'; ?>
<?php require_once __DIR__ . '/../includes/navbar.php'; ?>

<div class="container-fluid py-4">
    <?php if ($message): ?>
    <div class="alert alert-success alert-dismissible fade show"><?= htmlspecialchars($message) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    <?php endif; ?>
    <?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show"><?= htmlspecialchars($error) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    <?php endif; ?>

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="fw-bold mb-0"><i class="bi bi-collection me-2 text-primary"></i>My Exams</h2>
        <a href="create_exam.php" class="btn btn-primary btn-sm">
            <i class="bi bi-plus me-1"></i>Create Exam
        </a>
    </div>

    <?php if (empty($exams)): ?>
    <div class="alert alert-info">
        <i class="bi bi-info-circle me-2"></i>You haven't created any exams yet.
        <a href="create_exam.php" class="alert-link">Create your first exam!</a>
    </div>
    <?php else: ?>
    <div class="row g-3">
        <?php foreach ($exams as $exam): ?>
        <div class="col-md-6 col-lg-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <h6 class="card-title fw-bold mb-0"><?= htmlspecialchars($exam['title']) ?></h6>
                        <span class="badge bg-<?= examStatusBadge($exam['status']) ?>">
                            <?= ucfirst($exam['status']) ?>
                        </span>
                    </div>
                    <p class="text-muted small mb-2"><?= htmlspecialchars($exam['category_name']) ?></p>
                    <?php if ($exam['description']): ?>
                    <p class="small mb-2"><?= htmlspecialchars(substr($exam['description'], 0, 80)) ?>…</p>
                    <?php endif; ?>

                    <div class="row g-1 text-center small mb-3">
                        <div class="col-3">
                            <div class="bg-light rounded p-1">
                                <div class="fw-bold"><?= $exam['total_marks'] ?></div>
                                <div class="text-muted" style="font-size:0.7rem">Marks</div>
                            </div>
                        </div>
                        <div class="col-3">
                            <div class="bg-light rounded p-1">
                                <div class="fw-bold"><?= formatDuration((int)$exam['duration_minutes']) ?></div>
                                <div class="text-muted" style="font-size:0.7rem">Duration</div>
                            </div>
                        </div>
                        <div class="col-3">
                            <div class="bg-light rounded p-1">
                                <div class="fw-bold"><?= $exam['qcount'] ?></div>
                                <div class="text-muted" style="font-size:0.7rem">Questions</div>
                            </div>
                        </div>
                        <div class="col-3">
                            <div class="bg-light rounded p-1">
                                <div class="fw-bold"><?= $exam['attempt_count'] ?></div>
                                <div class="text-muted" style="font-size:0.7rem">Attempts</div>
                            </div>
                        </div>
                    </div>

                    <div class="small text-muted mb-3">
                        <i class="bi bi-calendar me-1"></i><?= formatDate($exam['start_time'], 'd M Y H:i') ?>
                        → <?= formatDate($exam['end_time'], 'd M Y H:i') ?>
                    </div>

                    <div class="d-flex gap-2">
                        <form method="POST" class="flex-grow-1">
                            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                            <input type="hidden" name="action" value="change_status">
                            <input type="hidden" name="id"     value="<?= $exam['id'] ?>">
                            <select name="status" class="form-select form-select-sm"
                                    onchange="this.form.submit()">
                                <?php foreach (['draft','active','completed','cancelled'] as $s): ?>
                                <option value="<?= $s ?>" <?= $exam['status']===$s?'selected':'' ?>><?= ucfirst($s) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </form>
                        <form method="POST" onsubmit="return confirm('Delete this exam?')">
                            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id"     value="<?= $exam['id'] ?>">
                            <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
