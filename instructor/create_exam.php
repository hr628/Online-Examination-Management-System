<?php
// instructor/create_exam.php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
checkAuth('instructor');

$conn    = getDBConnection();
$uid     = (int)$_SESSION['user_id'];
$message = '';
$error   = '';
$newExamId = 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid CSRF token.';
    } else {
        $action = $_POST['action'] ?? 'create_exam';

        if ($action === 'create_exam') {
            $title        = sanitizeInput($_POST['title'] ?? '');
            $desc         = sanitizeInput($_POST['description'] ?? '');
            $categoryId   = (int)($_POST['category_id'] ?? 0);
            $totalMarks   = (float)($_POST['total_marks'] ?? 0);
            $passingMarks = (float)($_POST['passing_marks'] ?? 0);
            $duration     = (int)($_POST['duration_minutes'] ?? 0);
            $startTime    = sanitizeInput($_POST['start_time'] ?? '');
            $endTime      = sanitizeInput($_POST['end_time'] ?? '');
            $randomized   = isset($_POST['is_randomized']) ? 1 : 0;
            $maxAttempts  = max(1, (int)($_POST['max_attempts'] ?? 1));

            if ($title === '')       $error = 'Exam title is required.';
            elseif ($categoryId<=0)  $error = 'Select a category.';
            elseif ($totalMarks<=0)  $error = 'Total marks must be positive.';
            elseif ($passingMarks>$totalMarks) $error = 'Passing marks cannot exceed total marks.';
            elseif ($duration<=0)    $error = 'Duration must be positive.';
            elseif (strtotime($startTime)===false) $error = 'Invalid start time.';
            elseif (strtotime($endTime)  ===false) $error = 'Invalid end time.';
            elseif (strtotime($endTime) <= strtotime($startTime)) $error = 'End time must be after start time.';
            else {
                $stmt = $conn->prepare(
                    'INSERT INTO exams
                        (title, description, category_id, created_by, total_marks, passing_marks,
                         duration_minutes, start_time, end_time, is_randomized, max_attempts, status)
                     VALUES (?,?,?,?,?,?,?,?,?,?,?,?)'
                );
                $status = 'draft';
                $stmt->bind_param('ssiiddissiis',
                    $title, $desc, $categoryId, $uid,
                    $totalMarks, $passingMarks, $duration,
                    $startTime, $endTime, $randomized, $maxAttempts, $status
                );
                if ($stmt->execute()) {
                    $newExamId = $conn->insert_id;
                    $message   = 'Exam created! Now assign questions below.';
                } else {
                    $error = 'Failed to create exam: ' . $conn->error;
                }
                $stmt->close();
            }
        } elseif ($action === 'assign_questions') {
            $examId     = (int)($_POST['exam_id']          ?? 0);
            $bankId     = (int)($_POST['bank_id']          ?? 0);
            $countToAdd = (int)($_POST['question_count']   ?? 0);
            $newExamId  = $examId;

            if ($examId > 0 && $bankId > 0 && $countToAdd > 0) {
                $stmt = $conn->prepare('CALL sp_assign_random_questions(?,?,?)');
                $stmt->bind_param('iii', $examId, $bankId, $countToAdd);
                if ($stmt->execute()) {
                    $message = "$countToAdd question(s) assigned successfully.";
                } else {
                    $error = 'Failed to assign questions.';
                }
                $stmt->close();
                $conn->next_result(); // flush procedure result set
            }
        } elseif ($action === 'activate') {
            $examId    = (int)($_POST['exam_id'] ?? 0);
            $newExamId = $examId;
            $stmt = $conn->prepare('UPDATE exams SET status=? WHERE id=? AND created_by=?');
            $st = 'active';
            $stmt->bind_param('sii', $st, $examId, $uid);
            $stmt->execute();
            $stmt->close();
            $message = 'Exam activated!';
        }
    }
}

// If we just created / are continuing an exam, show question assignment
$currentExam = null;
if ($newExamId > 0) {
    $stmt = $conn->prepare(
        'SELECT e.*, cat.name AS category_name
         FROM   exams e JOIN categories cat ON cat.id=e.category_id
         WHERE  e.id=? AND e.created_by=?'
    );
    $stmt->bind_param('ii', $newExamId, $uid);
    $stmt->execute();
    $currentExam = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    // Already assigned questions
    if ($currentExam) {
        $assignedQ = $conn->query(
            "SELECT eq.*, q.question_text, q.question_type, q.difficulty
             FROM   exam_questions eq
             JOIN   questions q ON q.id = eq.question_id
             WHERE  eq.exam_id = $newExamId
             ORDER  BY eq.order_number"
        )->fetch_all(MYSQLI_ASSOC);
        $currentExam['assigned'] = $assignedQ;
    }
}

$categories = $conn->query('SELECT * FROM categories ORDER BY name')->fetch_all(MYSQLI_ASSOC);
$banks      = $conn->query(
    "SELECT qb.*, cat.name AS category_name,
            COUNT(q.id) AS question_count
     FROM   question_banks qb
     JOIN   categories cat ON cat.id = qb.category_id
     LEFT JOIN questions q ON q.question_bank_id = qb.id AND q.is_active=1
     GROUP  BY qb.id ORDER BY cat.name, qb.name"
)->fetch_all(MYSQLI_ASSOC);

$conn->close();
$pageTitle = 'Create Exam';
$csrf      = getCSRFToken();
?>
<?php require_once __DIR__ . '/../includes/header.php'; ?>
<?php require_once __DIR__ . '/../includes/navbar.php'; ?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="fw-bold mb-0"><i class="bi bi-file-earmark-plus me-2 text-primary"></i>Create Exam</h2>
        <a href="view_exams.php" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i>My Exams
        </a>
    </div>

    <?php if ($message): ?>
    <div class="alert alert-success alert-dismissible fade show"><?= htmlspecialchars($message) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    <?php endif; ?>
    <?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show"><?= htmlspecialchars($error) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    <?php endif; ?>

    <?php if (!$currentExam): ?>
    <!-- Step 1: Create Exam -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white fw-semibold py-3">Step 1: Exam Details</div>
        <div class="card-body p-4">
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                <input type="hidden" name="action"     value="create_exam">
                <div class="row g-3">
                    <div class="col-md-8">
                        <label class="form-label fw-medium">Exam Title *</label>
                        <input type="text" class="form-control" name="title" required
                               value="<?= htmlspecialchars($_POST['title'] ?? '') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-medium">Category *</label>
                        <select class="form-select" name="category_id" required>
                            <option value="">-- Select --</option>
                            <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="description" rows="2"><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-medium">Total Marks *</label>
                        <input type="number" class="form-control" name="total_marks" min="1" step="0.5"
                               value="<?= htmlspecialchars($_POST['total_marks'] ?? '100') ?>" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-medium">Passing Marks *</label>
                        <input type="number" class="form-control" name="passing_marks" min="1" step="0.5"
                               value="<?= htmlspecialchars($_POST['passing_marks'] ?? '40') ?>" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-medium">Duration (min) *</label>
                        <input type="number" class="form-control" name="duration_minutes" min="1"
                               value="<?= htmlspecialchars($_POST['duration_minutes'] ?? '60') ?>" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-medium">Max Attempts</label>
                        <input type="number" class="form-control" name="max_attempts" min="1"
                               value="<?= htmlspecialchars($_POST['max_attempts'] ?? '1') ?>">
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="is_randomized" id="randomized">
                            <label class="form-check-label" for="randomized">Randomize</label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-medium">Start Time *</label>
                        <input type="datetime-local" class="form-control" name="start_time"
                               value="<?= htmlspecialchars($_POST['start_time'] ?? '') ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-medium">End Time *</label>
                        <input type="datetime-local" class="form-control" name="end_time"
                               value="<?= htmlspecialchars($_POST['end_time'] ?? '') ?>" required>
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary px-5">
                            <i class="bi bi-arrow-right me-1"></i>Create &amp; Continue
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <?php else: /* Step 2 */ ?>
    <!-- Step 2: Assign Questions -->
    <div class="row g-3">
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white fw-semibold py-3">Step 2: Assign Questions</div>
                <div class="card-body">
                    <p class="text-muted small mb-3">
                        <strong><?= htmlspecialchars($currentExam['title']) ?></strong> &mdash;
                        Total marks: <?= $currentExam['total_marks'] ?> &mdash;
                        Status: <span class="badge bg-<?= examStatusBadge($currentExam['status']) ?>"><?= ucfirst($currentExam['status']) ?></span>
                    </p>
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                        <input type="hidden" name="action"     value="assign_questions">
                        <input type="hidden" name="exam_id"    value="<?= $currentExam['id'] ?>">
                        <div class="mb-3">
                            <label class="form-label">Question Bank</label>
                            <select class="form-select" name="bank_id" required>
                                <option value="">-- Select --</option>
                                <?php foreach ($banks as $b): ?>
                                <option value="<?= $b['id'] ?>">
                                    <?= htmlspecialchars($b['name']) ?> (<?= $b['question_count'] ?> questions)
                                    [<?= htmlspecialchars($b['category_name']) ?>]
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Number of Questions to Assign</label>
                            <input type="number" class="form-control" name="question_count" min="1" value="5" required>
                        </div>
                        <button type="submit" class="btn btn-success w-100">
                            <i class="bi bi-shuffle me-1"></i>Add Random Questions
                        </button>
                    </form>

                    <hr>
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                        <input type="hidden" name="action"     value="activate">
                        <input type="hidden" name="exam_id"    value="<?= $currentExam['id'] ?>">
                        <button type="submit" class="btn btn-primary w-100"
                                onclick="return confirm('Activate this exam? Students will be able to see it.')">
                            <i class="bi bi-play-circle me-1"></i>Activate Exam
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white fw-semibold py-3">
                    Assigned Questions
                    <span class="badge bg-primary ms-2"><?= count($currentExam['assigned']) ?></span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0 small">
                            <thead class="table-light">
                                <tr><th>#</th><th>Question</th><th>Type</th><th>Diff.</th><th>Marks</th></tr>
                            </thead>
                            <tbody>
                                <?php foreach ($currentExam['assigned'] as $i => $aq): ?>
                                <tr>
                                    <td><?= $aq['order_number'] ?></td>
                                    <td><?= htmlspecialchars(substr($aq['question_text'], 0, 80)) ?>…</td>
                                    <td><span class="badge bg-secondary"><?= strtoupper($aq['question_type']) ?></span></td>
                                    <td><span class="badge bg-<?= $aq['difficulty']==='easy'?'success':($aq['difficulty']==='medium'?'warning text-dark':'danger') ?>"><?= ucfirst($aq['difficulty']) ?></span></td>
                                    <td><?= $aq['marks'] ?></td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if (empty($currentExam['assigned'])): ?>
                                <tr><td colspan="5" class="text-center text-muted py-3">No questions assigned yet.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
