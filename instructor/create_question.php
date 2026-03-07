<?php
// instructor/create_question.php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
checkAuth('instructor');

$conn    = getDBConnection();
$message = '';
$error   = '';
$uid     = (int)$_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid CSRF token.';
    } else {
        $bankId    = (int)($_POST['question_bank_id'] ?? 0);
        $qtext     = sanitizeInput($_POST['question_text'] ?? '');
        $qtype     = sanitizeInput($_POST['question_type'] ?? '');
        $difficulty= sanitizeInput($_POST['difficulty'] ?? '');
        $marks     = (float)($_POST['marks'] ?? 1);
        $optA      = sanitizeInput($_POST['option_a'] ?? '');
        $optB      = sanitizeInput($_POST['option_b'] ?? '');
        $optC      = $qtype === 'mcq' ? sanitizeInput($_POST['option_c'] ?? '') : null;
        $optD      = $qtype === 'mcq' ? sanitizeInput($_POST['option_d'] ?? '') : null;
        $correct   = sanitizeInput($_POST['correct_answer'] ?? '');
        $explain   = sanitizeInput($_POST['explanation'] ?? '') ?: null;

        if ($bankId <= 0) $error = 'Select a question bank.';
        elseif ($qtext === '') $error = 'Question text is required.';
        elseif (!in_array($qtype, ['mcq','true_false'], true)) $error = 'Invalid question type.';
        elseif (!in_array($difficulty, ['easy','medium','hard'], true)) $error = 'Invalid difficulty.';
        elseif ($marks <= 0) $error = 'Marks must be positive.';
        elseif ($optA === '' || $optB === '') $error = 'Options A and B are required.';
        elseif (!in_array($correct, ['A','B','C','D'], true)) $error = 'Select correct answer.';
        elseif ($qtype === 'mcq' && ($optC === '' || $optD === '')) $error = 'All 4 options required for MCQ.';
        else {
            $stmt = $conn->prepare(
                'INSERT INTO questions
                    (question_bank_id, question_text, question_type, difficulty, marks,
                     option_a, option_b, option_c, option_d, correct_answer, explanation, created_by)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?,?)'
            );
            $stmt->bind_param('isssdssssssi',
                $bankId, $qtext, $qtype, $difficulty, $marks,
                $optA, $optB, $optC, $optD, $correct, $explain, $uid
            );
            if ($stmt->execute()) {
                $message = 'Question created successfully!';
            } else {
                $error = 'Failed to save question. Please try again.';
            }
            $stmt->close();
        }
    }
}

// Get question banks accessible to this instructor (their own + from their categories)
$banks = $conn->query(
    "SELECT qb.*, cat.name AS category_name
     FROM   question_banks qb
     JOIN   categories cat ON cat.id = qb.category_id
     ORDER  BY cat.name, qb.name"
)->fetch_all(MYSQLI_ASSOC);

$conn->close();
$pageTitle = 'Create Question';
$csrf      = getCSRFToken();
?>
<?php require_once __DIR__ . '/../includes/header.php'; ?>
<?php require_once __DIR__ . '/../includes/navbar.php'; ?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="fw-bold mb-0"><i class="bi bi-plus-circle me-2 text-primary"></i>Create Question</h2>
        <a href="view_questions.php" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i>Back to Questions
        </a>
    </div>

    <?php if ($message): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <i class="bi bi-check-circle me-1"></i><?= htmlspecialchars($message) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>
    <?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <i class="bi bi-exclamation-triangle me-1"></i><?= htmlspecialchars($error) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-4">
            <form method="POST" id="questionForm">
                <input type="hidden" name="csrf_token" value="<?= $csrf ?>">

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-medium">Question Bank <span class="text-danger">*</span></label>
                        <select class="form-select" name="question_bank_id" required>
                            <option value="">-- Select Question Bank --</option>
                            <?php
                            $lastCat = '';
                            foreach ($banks as $bank):
                                if ($bank['category_name'] !== $lastCat):
                                    if ($lastCat !== '') echo '</optgroup>';
                                    echo '<optgroup label="' . htmlspecialchars($bank['category_name']) . '">';
                                    $lastCat = $bank['category_name'];
                                endif;
                            ?>
                            <option value="<?= $bank['id'] ?>"
                                <?= (int)($_POST['question_bank_id'] ?? 0) === $bank['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($bank['name']) ?>
                            </option>
                            <?php endforeach; ?>
                            <?php if ($lastCat !== '') echo '</optgroup>'; ?>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label fw-medium">Type <span class="text-danger">*</span></label>
                        <select class="form-select" name="question_type" id="questionType" required onchange="toggleOptions()">
                            <option value="mcq"        <?= ($_POST['question_type'] ?? '') === 'mcq'        ? 'selected' : '' ?>>MCQ</option>
                            <option value="true_false" <?= ($_POST['question_type'] ?? '') === 'true_false' ? 'selected' : '' ?>>True / False</option>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label fw-medium">Difficulty <span class="text-danger">*</span></label>
                        <select class="form-select" name="difficulty" required>
                            <option value="easy"   <?= ($_POST['difficulty'] ?? '') === 'easy'   ? 'selected' : '' ?>>Easy</option>
                            <option value="medium" <?= ($_POST['difficulty'] ?? '') === 'medium' ? 'selected' : '' ?>>Medium</option>
                            <option value="hard"   <?= ($_POST['difficulty'] ?? '') === 'hard'   ? 'selected' : '' ?>>Hard</option>
                        </select>
                    </div>

                    <div class="col-md-1">
                        <label class="form-label fw-medium">Marks <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" name="marks" min="0.5" step="0.5"
                               value="<?= htmlspecialchars($_POST['marks'] ?? '1') ?>" required>
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-medium">Question Text <span class="text-danger">*</span></label>
                        <textarea class="form-control" name="question_text" rows="3" required
                                  placeholder="Enter your question here…"><?= htmlspecialchars($_POST['question_text'] ?? '') ?></textarea>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Option A <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="option_a"
                               value="<?= htmlspecialchars($_POST['option_a'] ?? '') ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Option B <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="option_b"
                               value="<?= htmlspecialchars($_POST['option_b'] ?? '') ?>" required>
                    </div>

                    <div id="mcqOptions">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Option C <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="option_c"
                                       value="<?= htmlspecialchars($_POST['option_c'] ?? '') ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Option D <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="option_d"
                                       value="<?= htmlspecialchars($_POST['option_d'] ?? '') ?>">
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-medium">Correct Answer <span class="text-danger">*</span></label>
                        <select class="form-select" name="correct_answer" id="correctAnswer" required>
                            <option value="">-- Select --</option>
                            <option value="A" <?= ($_POST['correct_answer'] ?? '') === 'A' ? 'selected' : '' ?>>A</option>
                            <option value="B" <?= ($_POST['correct_answer'] ?? '') === 'B' ? 'selected' : '' ?>>B</option>
                            <option value="C" <?= ($_POST['correct_answer'] ?? '') === 'C' ? 'selected' : '' ?>>C</option>
                            <option value="D" <?= ($_POST['correct_answer'] ?? '') === 'D' ? 'selected' : '' ?>>D</option>
                        </select>
                    </div>

                    <div class="col-12">
                        <label class="form-label">Explanation <small class="text-muted">(optional)</small></label>
                        <textarea class="form-control" name="explanation" rows="2"
                                  placeholder="Explain why the correct answer is correct…"><?= htmlspecialchars($_POST['explanation'] ?? '') ?></textarea>
                    </div>

                    <div class="col-12">
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="bi bi-save me-1"></i>Save Question
                        </button>
                        <button type="reset" class="btn btn-outline-secondary ms-2">
                            <i class="bi bi-x-circle me-1"></i>Reset
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function toggleOptions() {
    const type    = document.getElementById('questionType').value;
    const mcqDiv  = document.getElementById('mcqOptions');
    const correct = document.getElementById('correctAnswer');
    const isTF    = type === 'true_false';

    mcqDiv.style.display = isTF ? 'none' : 'block';

    // Remove/add C and D options from correct answer select
    const existingC = correct.querySelector('option[value="C"]');
    const existingD = correct.querySelector('option[value="D"]');
    if (isTF) {
        if (existingC) existingC.remove();
        if (existingD) existingD.remove();
        if (correct.value === 'C' || correct.value === 'D') correct.value = '';
    } else {
        if (!existingC) {
            correct.insertAdjacentHTML('beforeend', '<option value="C">C</option>');
            correct.insertAdjacentHTML('beforeend', '<option value="D">D</option>');
        }
    }

    // Pre-fill True/False options
    if (isTF) {
        document.querySelector('[name="option_a"]').value = 'True';
        document.querySelector('[name="option_b"]').value = 'False';
    }
}
// Run on page load to handle server-side re-post
toggleOptions();
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
