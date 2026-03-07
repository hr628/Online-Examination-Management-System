<?php
// admin/manage_questions.php
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

        if ($action === 'toggle') {
            $stmt = $conn->prepare('UPDATE questions SET is_active = NOT is_active WHERE id=?');
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $stmt->close();
            $message = 'Question status updated.';
        } elseif ($action === 'delete') {
            $stmt = $conn->prepare('DELETE FROM questions WHERE id=?');
            $stmt->bind_param('i', $id);
            if ($stmt->execute()) {
                $message = 'Question deleted.';
            } else {
                $error = 'Cannot delete this question (assigned to active/draft exams).';
            }
            $stmt->close();
        }
    }
}

// Filters
$catFilter  = (int)($_GET['category'] ?? 0);
$typeFilter = sanitizeInput($_GET['type'] ?? '');
$diffFilter = sanitizeInput($_GET['difficulty'] ?? '');
$page       = max(1, (int)($_GET['page'] ?? 1));
$perPage    = 20;
$offset     = ($page - 1) * $perPage;

$wheres = ['1=1'];
$params = [];
$types  = '';

if ($catFilter > 0) {
    $wheres[] = 'qb.category_id = ?';
    $params[] = $catFilter;
    $types   .= 'i';
}
if ($typeFilter) {
    $wheres[] = 'q.question_type = ?';
    $params[] = $typeFilter;
    $types   .= 's';
}
if ($diffFilter) {
    $wheres[] = 'q.difficulty = ?';
    $params[] = $diffFilter;
    $types   .= 's';
}
$whereStr = implode(' AND ', $wheres);

$countStmt = $conn->prepare("SELECT COUNT(*) FROM questions q JOIN question_banks qb ON qb.id=q.question_bank_id WHERE $whereStr");
if ($types) $countStmt->bind_param($types, ...$params);
$countStmt->execute();
$total      = $countStmt->get_result()->fetch_row()[0];
$countStmt->close();
$totalPages = max(1, (int)ceil($total / $perPage));

$stmt = $conn->prepare(
    "SELECT q.*, qb.name AS bank_name, cat.name AS category_name, u.full_name AS creator_name
     FROM   questions q
     JOIN   question_banks qb ON qb.id  = q.question_bank_id
     JOIN   categories cat    ON cat.id = qb.category_id
     JOIN   users u           ON u.id   = q.created_by
     WHERE  $whereStr
     ORDER  BY q.created_at DESC
     LIMIT  ? OFFSET ?"
);
$allParams = array_merge($params, [$perPage, $offset]);
$allTypes  = $types . 'ii';
$stmt->bind_param($allTypes, ...$allParams);
$stmt->execute();
$questions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$categories = $conn->query('SELECT * FROM categories ORDER BY name')->fetch_all(MYSQLI_ASSOC);
$conn->close();

$pageTitle = 'Manage Questions';
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
        <h2 class="fw-bold mb-0"><i class="bi bi-question-circle me-2 text-primary"></i>Manage Questions</h2>
    </div>

    <!-- Filters -->
    <form method="GET" class="row g-2 mb-3">
        <div class="col-md-3">
            <select name="category" class="form-select">
                <option value="">All Categories</option>
                <?php foreach ($categories as $cat): ?>
                <option value="<?= $cat['id'] ?>" <?= $catFilter==$cat['id']?'selected':'' ?>>
                    <?= htmlspecialchars($cat['name']) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <select name="type" class="form-select">
                <option value="">All Types</option>
                <option value="mcq"        <?= $typeFilter==='mcq'        ?'selected':'' ?>>MCQ</option>
                <option value="true_false" <?= $typeFilter==='true_false' ?'selected':'' ?>>True/False</option>
            </select>
        </div>
        <div class="col-md-2">
            <select name="difficulty" class="form-select">
                <option value="">All Difficulties</option>
                <option value="easy"   <?= $diffFilter==='easy'   ?'selected':'' ?>>Easy</option>
                <option value="medium" <?= $diffFilter==='medium' ?'selected':'' ?>>Medium</option>
                <option value="hard"   <?= $diffFilter==='hard'   ?'selected':'' ?>>Hard</option>
            </select>
        </div>
        <div class="col-auto">
            <button type="submit" class="btn btn-outline-primary">Filter</button>
            <a href="manage_questions.php" class="btn btn-outline-secondary ms-1">Reset</a>
        </div>
    </form>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0 small">
                    <thead class="table-dark">
                        <tr>
                            <th>#</th>
                            <th>Question</th>
                            <th>Category</th>
                            <th>Bank</th>
                            <th>Type</th>
                            <th>Difficulty</th>
                            <th>Marks</th>
                            <th>Creator</th>
                            <th>Active</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($questions as $q): ?>
                        <tr>
                            <td><?= $q['id'] ?></td>
                            <td style="max-width:300px">
                                <?= htmlspecialchars(substr($q['question_text'], 0, 80), ENT_QUOTES, 'UTF-8') ?>
                                <?= strlen($q['question_text']) > 80 ? '…' : '' ?>
                            </td>
                            <td><?= htmlspecialchars($q['category_name']) ?></td>
                            <td><?= htmlspecialchars($q['bank_name']) ?></td>
                            <td>
                                <span class="badge bg-<?= $q['question_type']==='mcq' ? 'primary' : 'secondary' ?>">
                                    <?= strtoupper($q['question_type']) ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-<?= $q['difficulty']==='easy' ? 'success' : ($q['difficulty']==='medium' ? 'warning text-dark' : 'danger') ?>">
                                    <?= ucfirst($q['difficulty']) ?>
                                </span>
                            </td>
                            <td><?= $q['marks'] ?></td>
                            <td><?= htmlspecialchars($q['creator_name']) ?></td>
                            <td>
                                <span class="badge bg-<?= $q['is_active'] ? 'success' : 'secondary' ?>">
                                    <?= $q['is_active'] ? 'Yes' : 'No' ?>
                                </span>
                            </td>
                            <td>
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                                    <input type="hidden" name="action" value="toggle">
                                    <input type="hidden" name="id"     value="<?= $q['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-<?= $q['is_active'] ? 'warning' : 'success' ?> me-1">
                                        <i class="bi bi-<?= $q['is_active'] ? 'pause' : 'play' ?>"></i>
                                    </button>
                                </form>
                                <form method="POST" class="d-inline"
                                      onsubmit="return confirm('Delete this question?')">
                                    <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id"     value="<?= $q['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($questions)): ?>
                        <tr><td colspan="10" class="text-center text-muted py-4">No questions found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php if ($totalPages > 1): ?>
        <div class="card-footer bg-white d-flex justify-content-between">
            <small class="text-muted">Showing <?= $offset+1 ?>–<?= min($offset+$perPage,$total) ?> of <?= $total ?></small>
            <nav><ul class="pagination pagination-sm mb-0">
                <?php for ($p=1;$p<=$totalPages;$p++): ?>
                <li class="page-item <?= $p===$page?'active':'' ?>">
                    <a class="page-link" href="?page=<?= $p ?>&category=<?= $catFilter ?>&type=<?= urlencode($typeFilter) ?>&difficulty=<?= urlencode($diffFilter) ?>"><?= $p ?></a>
                </li>
                <?php endfor; ?>
            </ul></nav>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
