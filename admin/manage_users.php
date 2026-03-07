<?php
// admin/manage_users.php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
checkAuth('admin');

$conn    = getDBConnection();
$message = '';
$error   = '';

// ── Handle POST Actions ──────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid CSRF token.';
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'add' || $action === 'edit') {
            $id        = (int)($_POST['id'] ?? 0);
            $username  = sanitizeInput($_POST['username']  ?? '');
            $email     = sanitizeInput($_POST['email']     ?? '');
            $full_name = sanitizeInput($_POST['full_name'] ?? '');
            $role      = sanitizeInput($_POST['role']      ?? '');
            $phone     = sanitizeInput($_POST['phone']     ?? '') ?: null;
            $password  = $_POST['password'] ?? '';

            if (!in_array($role, ['admin','instructor','student'], true)) {
                $error = 'Invalid role.';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error = 'Invalid email address.';
            } else {
                if ($action === 'add') {
                    if (strlen($password) < 8) {
                        $error = 'Password must be at least 8 characters.';
                    } else {
                        $hash = password_hash($password, PASSWORD_BCRYPT);
                        $stmt = $conn->prepare(
                            'INSERT INTO users (username,email,password_hash,full_name,role,phone)
                             VALUES (?,?,?,?,?,?)'
                        );
                        $stmt->bind_param('ssssss', $username, $email, $hash, $full_name, $role, $phone);
                        if ($stmt->execute()) {
                            $message = 'User created successfully.';
                        } else {
                            $error = 'Failed to create user. Username or email may already exist.';
                        }
                        $stmt->close();
                    }
                } else { // edit
                    if ($password !== '') {
                        if (strlen($password) < 8) {
                            $error = 'New password must be at least 8 characters.';
                        } else {
                            $hash = password_hash($password, PASSWORD_BCRYPT);
                            $stmt = $conn->prepare(
                                'UPDATE users SET username=?,email=?,password_hash=?,full_name=?,role=?,phone=?
                                 WHERE id=?'
                            );
                            $stmt->bind_param('ssssssi', $username,$email,$hash,$full_name,$role,$phone,$id);
                            $stmt->execute();
                            $stmt->close();
                            $message = 'User updated successfully.';
                        }
                    } else {
                        $stmt = $conn->prepare(
                            'UPDATE users SET username=?,email=?,full_name=?,role=?,phone=? WHERE id=?'
                        );
                        $stmt->bind_param('sssssi', $username,$email,$full_name,$role,$phone,$id);
                        $stmt->execute();
                        $stmt->close();
                        $message = 'User updated successfully.';
                    }
                }
            }
        } elseif ($action === 'toggle') {
            $id = (int)($_POST['id'] ?? 0);
            $stmt = $conn->prepare('UPDATE users SET is_active = NOT is_active WHERE id=? AND id != ?');
            $adminId = (int)$_SESSION['user_id'];
            $stmt->bind_param('ii', $id, $adminId);
            $stmt->execute();
            $stmt->close();
            $message = 'User status updated.';
        } elseif ($action === 'delete') {
            $id = (int)($_POST['id'] ?? 0);
            if ($id === (int)$_SESSION['user_id']) {
                $error = 'You cannot delete your own account.';
            } else {
                $stmt = $conn->prepare('DELETE FROM users WHERE id=?');
                $stmt->bind_param('i', $id);
                if ($stmt->execute()) {
                    $message = 'User deleted.';
                } else {
                    $error = 'Cannot delete user (may have associated records).';
                }
                $stmt->close();
            }
        }
    }
}

// ── Pagination & Listing ─────────────────────────────────────
$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 15;
$offset  = ($page - 1) * $perPage;
$search  = sanitizeInput($_GET['search'] ?? '');
$roleFilter = sanitizeInput($_GET['role_filter'] ?? '');

$where  = 'WHERE 1=1';
$params = [];
$types  = '';

if ($search !== '') {
    $where   .= ' AND (u.username LIKE ? OR u.email LIKE ? OR u.full_name LIKE ?)';
    $like     = "%$search%";
    $params   = [$like, $like, $like];
    $types   .= 'sss';
}
if ($roleFilter !== '') {
    $where   .= ' AND u.role = ?';
    $params[] = $roleFilter;
    $types   .= 's';
}

$countSql = "SELECT COUNT(*) FROM users u $where";
$stmt = $conn->prepare($countSql);
if ($types) $stmt->bind_param($types, ...$params);
$stmt->execute();
$totalUsers = $stmt->get_result()->fetch_row()[0];
$stmt->close();
$totalPages = max(1, (int)ceil($totalUsers / $perPage));

$listSql = "SELECT u.* FROM users u $where ORDER BY u.created_at DESC LIMIT ? OFFSET ?";
$stmt = $conn->prepare($listSql);
$allParams = array_merge($params, [$perPage, $offset]);
$allTypes  = $types . 'ii';
$stmt->bind_param($allTypes, ...$allParams);
$stmt->execute();
$users = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$conn->close();

$pageTitle = 'Manage Users';
$csrf      = getCSRFToken();
?>
<?php require_once __DIR__ . '/../includes/header.php'; ?>
<?php require_once __DIR__ . '/../includes/navbar.php'; ?>

<div class="container-fluid py-4">
    <?php if ($message): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="fw-bold mb-0"><i class="bi bi-people me-2 text-primary"></i>Manage Users</h2>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#userModal" onclick="resetForm()">
            <i class="bi bi-person-plus me-1"></i>Add User
        </button>
    </div>

    <!-- Filters -->
    <form method="GET" class="row g-2 mb-3">
        <div class="col-md-5">
            <input type="text" name="search" class="form-control" placeholder="Search name, username, email…"
                   value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>">
        </div>
        <div class="col-md-3">
            <select name="role_filter" class="form-select">
                <option value="">All Roles</option>
                <option value="admin"      <?= $roleFilter==='admin'      ?'selected':'' ?>>Admin</option>
                <option value="instructor" <?= $roleFilter==='instructor' ?'selected':'' ?>>Instructor</option>
                <option value="student"    <?= $roleFilter==='student'    ?'selected':'' ?>>Student</option>
            </select>
        </div>
        <div class="col-auto">
            <button type="submit" class="btn btn-outline-primary">
                <i class="bi bi-search me-1"></i>Search
            </button>
            <a href="manage_users.php" class="btn btn-outline-secondary ms-1">Reset</a>
        </div>
    </form>

    <!-- Users Table -->
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>#</th>
                            <th>Full Name</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Joined</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $u): ?>
                        <tr>
                            <td><?= $u['id'] ?></td>
                            <td><?= htmlspecialchars($u['full_name'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><code><?= htmlspecialchars($u['username'], ENT_QUOTES, 'UTF-8') ?></code></td>
                            <td><?= htmlspecialchars($u['email'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td>
                                <span class="badge bg-<?= $u['role']==='admin' ? 'danger' : ($u['role']==='instructor' ? 'warning text-dark' : 'success') ?>">
                                    <?= ucfirst($u['role']) ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-<?= $u['is_active'] ? 'success' : 'secondary' ?>">
                                    <?= $u['is_active'] ? 'Active' : 'Inactive' ?>
                                </span>
                            </td>
                            <td><?= formatDate($u['created_at'], 'd M Y') ?></td>
                            <td>
                                <button class="btn btn-sm btn-outline-primary me-1"
                                        onclick='editUser(<?= json_encode($u) ?>)'>
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <?php if ($u['id'] !== (int)$_SESSION['user_id']): ?>
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                                    <input type="hidden" name="action" value="toggle">
                                    <input type="hidden" name="id" value="<?= $u['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-<?= $u['is_active'] ? 'warning' : 'success' ?> me-1"
                                            title="<?= $u['is_active'] ? 'Deactivate' : 'Activate' ?>">
                                        <i class="bi bi-<?= $u['is_active'] ? 'pause' : 'play' ?>"></i>
                                    </button>
                                </form>
                                <form method="POST" class="d-inline"
                                      onsubmit="return confirm('Delete this user permanently?')">
                                    <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= $u['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($users)): ?>
                        <tr><td colspan="8" class="text-center text-muted py-4">No users found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
        <div class="card-footer bg-white d-flex justify-content-between align-items-center">
            <small class="text-muted">
                Showing <?= $offset + 1 ?>–<?= min($offset + $perPage, $totalUsers) ?> of <?= $totalUsers ?> users
            </small>
            <nav>
                <ul class="pagination pagination-sm mb-0">
                    <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                    <li class="page-item <?= $p === $page ? 'active' : '' ?>">
                        <a class="page-link" href="?page=<?= $p ?>&search=<?= urlencode($search) ?>&role_filter=<?= urlencode($roleFilter) ?>"><?= $p ?></a>
                    </li>
                    <?php endfor; ?>
                </ul>
            </nav>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Add/Edit User Modal -->
<div class="modal fade" id="userModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                <input type="hidden" name="action"     id="formAction" value="add">
                <input type="hidden" name="id"         id="userId"     value="">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Add User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Full Name *</label>
                        <input type="text" class="form-control" name="full_name" id="mFullName" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Username *</label>
                            <input type="text" class="form-control" name="username" id="mUsername" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Role *</label>
                            <select class="form-select" name="role" id="mRole" required>
                                <option value="student">Student</option>
                                <option value="instructor">Instructor</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email *</label>
                        <input type="email" class="form-control" name="email" id="mEmail" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Phone</label>
                        <input type="tel" class="form-control" name="phone" id="mPhone">
                    </div>
                    <div class="mb-3">
                        <label class="form-label" id="passwordLabel">Password *</label>
                        <input type="password" class="form-control" name="password" id="mPassword"
                               placeholder="Leave blank to keep current (edit mode)">
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

<script>
function resetForm() {
    document.getElementById('formAction').value = 'add';
    document.getElementById('userId').value     = '';
    document.getElementById('modalTitle').textContent = 'Add User';
    document.getElementById('mFullName').value  = '';
    document.getElementById('mUsername').value  = '';
    document.getElementById('mEmail').value     = '';
    document.getElementById('mPhone').value     = '';
    document.getElementById('mRole').value      = 'student';
    document.getElementById('mPassword').value  = '';
    document.getElementById('passwordLabel').textContent = 'Password *';
}
function editUser(u) {
    document.getElementById('formAction').value  = 'edit';
    document.getElementById('userId').value      = u.id;
    document.getElementById('modalTitle').textContent = 'Edit User';
    document.getElementById('mFullName').value   = u.full_name;
    document.getElementById('mUsername').value   = u.username;
    document.getElementById('mEmail').value      = u.email;
    document.getElementById('mPhone').value      = u.phone || '';
    document.getElementById('mRole').value       = u.role;
    document.getElementById('mPassword').value   = '';
    document.getElementById('passwordLabel').textContent = 'New Password (leave blank to keep current)';
    new bootstrap.Modal(document.getElementById('userModal')).show();
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
