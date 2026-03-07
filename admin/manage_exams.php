<?php
// admin/manage_exams.php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
checkAuth('admin');

$conn    = getDBConnection();
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
                $stmt = $conn->prepare('UPDATE exams SET status=? WHERE id=?');
                $stmt->bind_param('si', $newStatus, $id);
                $stmt->execute();
                $stmt->close();
                $message = 'Exam status updated.';
            }
        } elseif ($action === 'delete') {
            $stmt = $conn->prepare('DELETE FROM exams WHERE id=?');
            $stmt->bind_param('i', $id);
            if ($stmt->execute()) {
                $message = 'Exam deleted.';
            } else {
                $error = 'Cannot delete exam (may have associated records).';
            }
            $stmt->close();
        }
    }
}

// List exams
$statusFilter = sanitizeInput($_GET['status'] ?? '');
$where  = $statusFilter ? 'WHERE e.status = ?' : '';
$stmt   = $conn->prepare(
    "SELECT e.*, cat.name AS category_name, u.full_name AS creator_name,
            COUNT(DISTINCT eq.question_id) AS question_count,
            COUNT(DISTINCT se.id) AS attempt_count
     FROM   exams e
     JOIN   categories cat ON cat.id = e.category_id
     JOIN   users u        ON u.id   = e.created_by
     LEFT JOIN exam_questions eq ON eq.exam_id = e.id
     LEFT JOIN student_exams  se ON se.exam_id = e.id
     $where
     GROUP  BY e.id
     ORDER  BY e.created_at DESC"
);
if ($statusFilter) {
    $stmt->bind_param('s', $statusFilter);
}
$stmt->execute();
$exams = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Categories for filter
$categories = $conn->query('SELECT * FROM categories ORDER BY name')->fetch_all(MYSQLI_ASSOC);
$conn->close();

$pageTitle = 'Manage Exams';
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
        <h2 class="fw-bold mb-0"><i class="bi bi-file-earmark-text me-2 text-primary"></i>Manage Exams</h2>
    </div>

    <!-- Status filter -->
    <div class="mb-3">
        <?php foreach (['','draft','active','completed','cancelled'] as $s): ?>
        <a href="?status=<?= $s ?>"
           class="btn btn-sm <?= $statusFilter === $s ? 'btn-primary' : 'btn-outline-secondary' ?> me-1 mb-1">
            <?= $s === '' ? 'All' : ucfirst($s) ?>
        </a>
        <?php endforeach; ?>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>#</th>
                            <th>Title</th>
                            <th>Category</th>
                            <th>Creator</th>
                            <th>Marks</th>
                            <th>Duration</th>
                            <th>Questions</th>
                            <th>Attempts</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($exams as $exam): ?>
                        <tr>
                            <td><?= $exam['id'] ?></td>
                            <td class="fw-medium"><?= htmlspecialchars($exam['title'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($exam['category_name'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($exam['creator_name'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= $exam['total_marks'] ?> <small class="text-muted">(pass: <?= $exam['passing_marks'] ?>)</small></td>
                            <td><?= formatDuration((int)$exam['duration_minutes']) ?></td>
                            <td><span class="badge bg-info"><?= $exam['question_count'] ?></span></td>
                            <td><span class="badge bg-secondary"><?= $exam['attempt_count'] ?></span></td>
                            <td>
                                <span class="badge bg-<?= examStatusBadge($exam['status']) ?>">
                                    <?= ucfirst($exam['status']) ?>
                                </span>
                            </td>
                            <td>
                                <!-- Change status -->
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                                    <input type="hidden" name="action" value="change_status">
                                    <input type="hidden" name="id"     value="<?= $exam['id'] ?>">
                                    <select name="status" class="form-select form-select-sm d-inline w-auto"
                                            onchange="this.form.submit()">
                                        <?php foreach (['draft','active','completed','cancelled'] as $s): ?>
                                        <option value="<?= $s ?>" <?= $exam['status']===$s?'selected':'' ?>><?= ucfirst($s) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </form>
                                <form method="POST" class="d-inline ms-1"
                                      onsubmit="return confirm('Delete this exam and all its data?')">
                                    <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id"     value="<?= $exam['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($exams)): ?>
                        <tr><td colspan="10" class="text-center text-muted py-4">No exams found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
