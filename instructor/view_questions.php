<?php
// instructor/view_questions.php
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

        // Only allow modifying own questions
        $checkStmt = $conn->prepare('SELECT created_by FROM questions WHERE id=?');
        $checkStmt->bind_param('i', $id);
        $checkStmt->execute();
        $owner = $checkStmt->get_result()->fetch_assoc();
        $checkStmt->close();

        if (!$owner || (int)$owner['created_by'] !== $uid) {
            $error = 'Access denied.';
        } elseif ($action === 'delete') {
            $stmt = $conn->prepare('DELETE FROM questions WHERE id=? AND created_by=?');
            $stmt->bind_param('ii', $id, $uid);
            if ($stmt->execute()) $message = 'Question deleted.';
            else $error = 'Cannot delete (used in active/draft exams).';
            $stmt->close();
        } elseif ($action === 'toggle') {
            $stmt = $conn->prepare('UPDATE questions SET is_active = NOT is_active WHERE id=? AND created_by=?');
            $stmt->bind_param('ii', $id, $uid);
            $stmt->execute();
            $stmt->close();
            $message = 'Status updated.';
        }
    }
}

$catFilter  = (int)($_GET['category'] ?? 0);
$typeFilter = sanitizeInput($_GET['type'] ?? '');
$page       = max(1, (int)($_GET['page'] ?? 1));
$perPage    = 15;
$offset     = ($page - 1) * $perPage;

$wheres = ['q.created_by = ?'];
$params = [$uid];
$types  = 'i';

if ($catFilter > 0) { $wheres[] = 'qb.category_id=?'; $params[]=$catFilter; $types.='i'; }
if ($typeFilter)    { $wheres[] = 'q.question_type=?'; $params[]=$typeFilter; $types.='s'; }

$whereStr = implode(' AND ', $wheres);

$cStmt = $conn->prepare("SELECT COUNT(*) FROM questions q JOIN question_banks qb ON qb.id=q.question_bank_id WHERE $whereStr");
$cStmt->bind_param($types, ...$params);
$cStmt->execute();
$total = $cStmt->get_result()->fetch_row()[0];
$cStmt->close();
$totalPages = max(1, (int)ceil($total / $perPage));

$stmt = $conn->prepare(
    "SELECT q.*, qb.name AS bank_name, cat.name AS category_name
     FROM   questions q
     JOIN   question_banks qb ON qb.id  = q.question_bank_id
     JOIN   categories cat    ON cat.id = qb.category_id
     WHERE  $whereStr
     ORDER  BY q.created_at DESC
     LIMIT  ? OFFSET ?"
);
$allP = array_merge($params, [$perPage, $offset]);
$allT = $types . 'ii';
$stmt->bind_param($allT, ...$allP);
$stmt->execute();
$questions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$categories = $conn->query('SELECT * FROM categories ORDER BY name')->fetch_all(MYSQLI_ASSOC);
$conn->close();

$pageTitle = 'My Questions';
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
        <h2 class="fw-bold mb-0"><i class="bi bi-list-ul me-2 text-primary"></i>My Questions
            <span class="badge bg-secondary fs-6 ms-2"><?= $total ?></span>
        </h2>
        <a href="create_question.php" class="btn btn-primary btn-sm">
            <i class="bi bi-plus me-1"></i>Add Question
        </a>
    </div>

    <form method="GET" class="row g-2 mb-3">
        <div class="col-md-3">
            <select name="category" class="form-select form-select-sm">
                <option value="">All Categories</option>
                <?php foreach ($categories as $cat): ?>
                <option value="<?= $cat['id'] ?>" <?= $catFilter==$cat['id']?'selected':'' ?>>
                    <?= htmlspecialchars($cat['name']) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <select name="type" class="form-select form-select-sm">
                <option value="">All Types</option>
                <option value="mcq"        <?= $typeFilter==='mcq'?'selected':'' ?>>MCQ</option>
                <option value="true_false" <?= $typeFilter==='true_false'?'selected':'' ?>>True/False</option>
            </select>
        </div>
        <div class="col-auto">
            <button type="submit" class="btn btn-outline-primary btn-sm">Filter</button>
            <a href="view_questions.php" class="btn btn-outline-secondary btn-sm ms-1">Reset</a>
        </div>
    </form>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0 small">
                    <thead class="table-dark">
                        <tr>
                            <th>#</th><th>Question</th><th>Bank</th><th>Type</th>
                            <th>Difficulty</th><th>Marks</th><th>Active</th><th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($questions as $q): ?>
                        <tr>
                            <td><?= $q['id'] ?></td>
                            <td style="max-width:350px">
                                <?= htmlspecialchars(substr($q['question_text'], 0, 100)) ?>…
                            </td>
                            <td><?= htmlspecialchars($q['bank_name']) ?><br>
                                <small class="text-muted"><?= htmlspecialchars($q['category_name']) ?></small>
                            </td>
                            <td><span class="badge bg-<?= $q['question_type']==='mcq'?'primary':'secondary' ?>"><?= strtoupper($q['question_type']) ?></span></td>
                            <td><span class="badge bg-<?= $q['difficulty']==='easy'?'success':($q['difficulty']==='medium'?'warning text-dark':'danger') ?>"><?= ucfirst($q['difficulty']) ?></span></td>
                            <td><?= $q['marks'] ?></td>
                            <td><span class="badge bg-<?= $q['is_active']?'success':'secondary' ?>"><?= $q['is_active']?'Yes':'No' ?></span></td>
                            <td>
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                                    <input type="hidden" name="action" value="toggle">
                                    <input type="hidden" name="id"     value="<?= $q['id'] ?>">
                                    <button class="btn btn-sm btn-outline-warning me-1"><i class="bi bi-toggle-on"></i></button>
                                </form>
                                <form method="POST" class="d-inline" onsubmit="return confirm('Delete?')">
                                    <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id"     value="<?= $q['id'] ?>">
                                    <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($questions)): ?>
                        <tr><td colspan="8" class="text-center text-muted py-4">No questions yet. <a href="create_question.php">Create one!</a></td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php if ($totalPages > 1): ?>
        <div class="card-footer bg-white d-flex justify-content-between">
            <small>Showing <?= $offset+1 ?>–<?= min($offset+$perPage,$total) ?> of <?= $total ?></small>
            <nav><ul class="pagination pagination-sm mb-0">
                <?php for ($p=1;$p<=$totalPages;$p++): ?>
                <li class="page-item <?= $p===$page?'active':'' ?>">
                    <a class="page-link" href="?page=<?= $p ?>&category=<?= $catFilter ?>&type=<?= urlencode($typeFilter) ?>"><?= $p ?></a>
                </li>
                <?php endfor; ?>
            </ul></nav>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
