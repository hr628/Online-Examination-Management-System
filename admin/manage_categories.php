<?php
// admin/manage_categories.php
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
        $action      = $_POST['action'] ?? '';
        $id          = (int)($_POST['id'] ?? 0);
        $entity      = sanitizeInput($_POST['entity'] ?? 'category'); // 'category' or 'bank'

        if ($entity === 'category') {
            $name = sanitizeInput($_POST['name'] ?? '');
            $desc = sanitizeInput($_POST['description'] ?? '');
            if ($action === 'add') {
                $uid  = (int)$_SESSION['user_id'];
                $stmt = $conn->prepare('INSERT INTO categories (name, description, created_by) VALUES (?,?,?)');
                $stmt->bind_param('ssi', $name, $desc, $uid);
                if ($stmt->execute()) $message = 'Category added.';
                else $error = 'Failed (name may already exist).';
                $stmt->close();
            } elseif ($action === 'edit') {
                $stmt = $conn->prepare('UPDATE categories SET name=?, description=? WHERE id=?');
                $stmt->bind_param('ssi', $name, $desc, $id);
                $stmt->execute();
                $stmt->close();
                $message = 'Category updated.';
            } elseif ($action === 'delete') {
                $stmt = $conn->prepare('DELETE FROM categories WHERE id=?');
                $stmt->bind_param('i', $id);
                if ($stmt->execute()) $message = 'Category deleted.';
                else $error = 'Cannot delete (has linked question banks or exams).';
                $stmt->close();
            }
        } else { // bank
            $name   = sanitizeInput($_POST['name'] ?? '');
            $desc   = sanitizeInput($_POST['description'] ?? '');
            $catId  = (int)($_POST['category_id'] ?? 0);
            $uid    = (int)$_SESSION['user_id'];
            if ($action === 'add_bank') {
                $stmt = $conn->prepare('INSERT INTO question_banks (name, category_id, description, created_by) VALUES (?,?,?,?)');
                $stmt->bind_param('sisi', $name, $catId, $desc, $uid);
                if ($stmt->execute()) $message = 'Question bank added.';
                else $error = 'Failed (name may already exist in this category).';
                $stmt->close();
            } elseif ($action === 'delete_bank') {
                $stmt = $conn->prepare('DELETE FROM question_banks WHERE id=?');
                $stmt->bind_param('i', $id);
                if ($stmt->execute()) $message = 'Question bank deleted.';
                else $error = 'Cannot delete (has linked questions).';
                $stmt->close();
            }
        }
    }
}

$categories = $conn->query(
    "SELECT c.*, u.full_name AS creator,
            COUNT(DISTINCT qb.id) AS bank_count,
            COUNT(DISTINCT e.id)  AS exam_count
     FROM   categories c
     JOIN   users u ON u.id = c.created_by
     LEFT JOIN question_banks qb ON qb.category_id = c.id
     LEFT JOIN exams e ON e.category_id = c.id
     GROUP  BY c.id ORDER BY c.name"
)->fetch_all(MYSQLI_ASSOC);

$banks = $conn->query(
    "SELECT qb.*, cat.name AS category_name, u.full_name AS creator,
            COUNT(q.id) AS question_count
     FROM   question_banks qb
     JOIN   categories cat ON cat.id = qb.category_id
     JOIN   users u        ON u.id   = qb.created_by
     LEFT JOIN questions q ON q.question_bank_id = qb.id
     GROUP  BY qb.id ORDER BY cat.name, qb.name"
)->fetch_all(MYSQLI_ASSOC);

$conn->close();
$pageTitle = 'Manage Categories';
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

    <h2 class="fw-bold mb-4"><i class="bi bi-tags me-2 text-primary"></i>Categories &amp; Question Banks</h2>

    <div class="row g-4">
        <!-- Categories -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white d-flex justify-content-between align-items-center fw-semibold py-3">
                    <span><i class="bi bi-folder2 me-2 text-warning"></i>Categories</span>
                    <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#catModal">
                        <i class="bi bi-plus"></i> Add
                    </button>
                </div>
                <div class="card-body p-0">
                    <table class="table table-hover mb-0 small">
                        <thead class="table-light">
                            <tr><th>Name</th><th>Banks</th><th>Exams</th><th>Actions</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($categories as $cat): ?>
                            <tr>
                                <td>
                                    <div class="fw-medium"><?= htmlspecialchars($cat['name']) ?></div>
                                    <small class="text-muted"><?= htmlspecialchars($cat['description'] ?? '') ?></small>
                                </td>
                                <td><span class="badge bg-info"><?= $cat['bank_count'] ?></span></td>
                                <td><span class="badge bg-secondary"><?= $cat['exam_count'] ?></span></td>
                                <td>
                                    <button class="btn btn-sm btn-outline-primary me-1"
                                            onclick='editCat(<?= json_encode($cat) ?>)'>
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <form method="POST" class="d-inline"
                                          onsubmit="return confirm('Delete category?')">
                                        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="entity" value="category">
                                        <input type="hidden" name="id"     value="<?= $cat['id'] ?>">
                                        <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($categories)): ?>
                            <tr><td colspan="4" class="text-center text-muted py-3">No categories.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Question Banks -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white d-flex justify-content-between align-items-center fw-semibold py-3">
                    <span><i class="bi bi-journals me-2 text-success"></i>Question Banks</span>
                    <button class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#bankModal">
                        <i class="bi bi-plus"></i> Add Bank
                    </button>
                </div>
                <div class="card-body p-0">
                    <table class="table table-hover mb-0 small">
                        <thead class="table-light">
                            <tr><th>Name</th><th>Category</th><th>Questions</th><th>Actions</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($banks as $bank): ?>
                            <tr>
                                <td>
                                    <div class="fw-medium"><?= htmlspecialchars($bank['name']) ?></div>
                                    <small class="text-muted"><?= htmlspecialchars($bank['description'] ?? '') ?></small>
                                </td>
                                <td><?= htmlspecialchars($bank['category_name']) ?></td>
                                <td><span class="badge bg-primary"><?= $bank['question_count'] ?></span></td>
                                <td>
                                    <form method="POST" class="d-inline"
                                          onsubmit="return confirm('Delete this bank and all its questions?')">
                                        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                                        <input type="hidden" name="action" value="delete_bank">
                                        <input type="hidden" name="entity" value="bank">
                                        <input type="hidden" name="id"     value="<?= $bank['id'] ?>">
                                        <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($banks)): ?>
                            <tr><td colspan="4" class="text-center text-muted py-3">No question banks.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Category Modal -->
<div class="modal fade" id="catModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                <input type="hidden" name="entity"     value="category">
                <input type="hidden" name="action"     id="catAction" value="add">
                <input type="hidden" name="id"         id="catId"     value="">
                <div class="modal-header">
                    <h5 class="modal-title" id="catModalTitle">Add Category</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Name *</label>
                        <input type="text" class="form-control" name="name" id="catName" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="description" id="catDesc" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Bank Modal -->
<div class="modal fade" id="bankModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                <input type="hidden" name="entity"     value="bank">
                <input type="hidden" name="action"     value="add_bank">
                <div class="modal-header">
                    <h5 class="modal-title">Add Question Bank</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Category *</label>
                        <select class="form-select" name="category_id" required>
                            <option value="">-- Select --</option>
                            <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Bank Name *</label>
                        <input type="text" class="form-control" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="description" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Add Bank</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function editCat(c) {
    document.getElementById('catAction').value      = 'edit';
    document.getElementById('catId').value          = c.id;
    document.getElementById('catModalTitle').textContent = 'Edit Category';
    document.getElementById('catName').value        = c.name;
    document.getElementById('catDesc').value        = c.description || '';
    new bootstrap.Modal(document.getElementById('catModal')).show();
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
