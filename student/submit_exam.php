<?php
// student/submit_exam.php
// Handles exam submission via POST from take_exam.php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
checkAuth('student');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirectTo(BASE_URL . '/student/available_exams.php');
}

if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    setFlash('danger', 'Invalid request. Please try again.');
    redirectTo(BASE_URL . '/student/available_exams.php');
}

$conn          = getDBConnection();
$uid           = (int)$_SESSION['user_id'];
$studentExamId = (int)($_POST['student_exam_id'] ?? 0);
$examId        = (int)($_POST['exam_id'] ?? 0);

if ($studentExamId <= 0 || $examId <= 0) {
    redirectTo(BASE_URL . '/student/available_exams.php');
}

// Verify the attempt belongs to this student and is still in progress
$stmt = $conn->prepare(
    "SELECT se.*, e.total_marks, e.passing_marks
     FROM   student_exams se
     JOIN   exams e ON e.id = se.exam_id
     WHERE  se.id = ? AND se.student_id = ? AND se.status = 'in_progress'"
);
$stmt->bind_param('ii', $studentExamId, $uid);
$stmt->execute();
$attempt = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$attempt) {
    setFlash('danger', 'Invalid or already submitted attempt.');
    redirectTo(BASE_URL . '/student/available_exams.php');
}

// Load exam questions with correct answers
$stmt = $conn->prepare(
    "SELECT q.id, q.correct_answer, eq.marks
     FROM   exam_questions eq
     JOIN   questions q ON q.id = eq.question_id
     WHERE  eq.exam_id = ?"
);
$stmt->bind_param('i', $examId);
$stmt->execute();
$questions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// ── Transaction: save answers + update status ────────────────
$conn->begin_transaction();
try {
    $insertAnswer = $conn->prepare(
        'INSERT INTO student_answers
             (student_exam_id, question_id, selected_answer, is_correct, marks_obtained)
         VALUES (?, ?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE
             selected_answer = VALUES(selected_answer),
             is_correct      = VALUES(is_correct),
             marks_obtained  = VALUES(marks_obtained)'
    );

    foreach ($questions as $q) {
        $qid      = $q['id'];
        $selected = isset($_POST["answer_$qid"]) ? strtoupper(sanitizeInput($_POST["answer_$qid"])) : null;
        if ($selected && !in_array($selected, ['A','B','C','D'], true)) $selected = null;

        $isCorrect     = ($selected !== null && $selected === $q['correct_answer']) ? 1 : 0;
        $marksObtained = $isCorrect ? (float)$q['marks'] : 0.0;

        $insertAnswer->bind_param('iisid', $studentExamId, $qid, $selected, $isCorrect, $marksObtained);
        $insertAnswer->execute();
    }
    $insertAnswer->close();

    // Calculate time taken
    $startedAt   = strtotime($attempt['started_at']);
    $timeTaken   = (int)ceil((time() - $startedAt) / 60);

    // Mark as completed (trigger will auto-calculate result)
    $stmt = $conn->prepare(
        "UPDATE student_exams
         SET    status = 'completed', submitted_at = NOW(), time_taken_minutes = ?
         WHERE  id = ? AND student_id = ?"
    );
    $stmt->bind_param('iii', $timeTaken, $studentExamId, $uid);
    $stmt->execute();
    $stmt->close();

    $conn->commit();
} catch (Exception $e) {
    $conn->rollback();
    error_log('Exam submission error: ' . $e->getMessage());
    setFlash('danger', 'Failed to submit exam. Please contact support.');
    redirectTo(BASE_URL . '/student/available_exams.php');
}

$conn->close();

// Redirect to results
redirectTo(BASE_URL . '/student/view_results.php?exam_id=' . $examId . '&se_id=' . $studentExamId);
