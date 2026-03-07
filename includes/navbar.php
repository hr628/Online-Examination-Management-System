<?php
// includes/navbar.php
// Role-aware navigation bar.
// Expects session variables: user_id, role, full_name
$role     = $_SESSION['role']      ?? '';
$fullName = $_SESSION['full_name'] ?? 'User';
$base     = BASE_URL;
?>
<nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm">
    <div class="container">
        <a class="navbar-brand fw-bold" href="<?= $base ?>/">
            <i class="bi bi-mortarboard-fill me-1"></i>OEMS
        </a>
        <button class="navbar-toggler" type="button"
                data-bs-toggle="collapse" data-bs-target="#mainNav">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="mainNav">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">

                <?php if ($role === 'admin'): ?>
                <li class="nav-item">
                    <a class="nav-link" href="<?= $base ?>/admin/dashboard.php">
                        <i class="bi bi-speedometer2 me-1"></i>Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?= $base ?>/admin/manage_users.php">
                        <i class="bi bi-people me-1"></i>Users
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?= $base ?>/admin/manage_exams.php">
                        <i class="bi bi-file-earmark-text me-1"></i>Exams
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?= $base ?>/admin/manage_questions.php">
                        <i class="bi bi-question-circle me-1"></i>Questions
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?= $base ?>/admin/manage_categories.php">
                        <i class="bi bi-tags me-1"></i>Categories
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?= $base ?>/admin/reports.php">
                        <i class="bi bi-bar-chart me-1"></i>Reports
                    </a>
                </li>

                <?php elseif ($role === 'instructor'): ?>
                <li class="nav-item">
                    <a class="nav-link" href="<?= $base ?>/instructor/dashboard.php">
                        <i class="bi bi-speedometer2 me-1"></i>Dashboard
                    </a>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                        <i class="bi bi-question-circle me-1"></i>Questions
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="<?= $base ?>/instructor/create_question.php">
                            <i class="bi bi-plus-circle me-1"></i>Create Question</a></li>
                        <li><a class="dropdown-item" href="<?= $base ?>/instructor/view_questions.php">
                            <i class="bi bi-list-ul me-1"></i>My Questions</a></li>
                    </ul>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                        <i class="bi bi-file-earmark-text me-1"></i>Exams
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="<?= $base ?>/instructor/create_exam.php">
                            <i class="bi bi-plus-circle me-1"></i>Create Exam</a></li>
                        <li><a class="dropdown-item" href="<?= $base ?>/instructor/view_exams.php">
                            <i class="bi bi-list-ul me-1"></i>My Exams</a></li>
                    </ul>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?= $base ?>/instructor/student_results.php">
                        <i class="bi bi-bar-chart me-1"></i>Results
                    </a>
                </li>

                <?php elseif ($role === 'student'): ?>
                <li class="nav-item">
                    <a class="nav-link" href="<?= $base ?>/student/dashboard.php">
                        <i class="bi bi-speedometer2 me-1"></i>Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?= $base ?>/student/available_exams.php">
                        <i class="bi bi-clipboard-check me-1"></i>Available Exams
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?= $base ?>/student/view_results.php">
                        <i class="bi bi-trophy me-1"></i>My Results
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?= $base ?>/student/exam_history.php">
                        <i class="bi bi-clock-history me-1"></i>History
                    </a>
                </li>
                <?php endif; ?>

            </ul>

            <!-- Right side: user info + logout -->
            <ul class="navbar-nav ms-auto">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                        <i class="bi bi-person-circle me-1"></i>
                        <?= htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8') ?>
                        <span class="badge bg-<?= $role === 'admin' ? 'danger' : ($role === 'instructor' ? 'warning text-dark' : 'success') ?> ms-1">
                            <?= ucfirst($role) ?>
                        </span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><h6 class="dropdown-header">Signed in as <?= htmlspecialchars($_SESSION['username'] ?? '', ENT_QUOTES, 'UTF-8') ?></h6></li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item text-danger" href="<?= $base ?>/logout.php">
                                <i class="bi bi-box-arrow-right me-1"></i>Logout
                            </a>
                        </li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>
