-- ============================================================
-- Online Examination Management System - Database Schema
-- File: 01_schema.sql
-- Description: Creates the oems_db database and all tables
-- MySQL 8.x compatible
-- ============================================================

DROP DATABASE IF EXISTS oems_db;
CREATE DATABASE oems_db
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE oems_db;

-- ============================================================
-- Table: users
-- Stores all system users (admin, instructor, student)
-- ============================================================
CREATE TABLE users (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username      VARCHAR(50)  NOT NULL UNIQUE,
    email         VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    full_name     VARCHAR(100) NOT NULL,
    role          ENUM('admin','instructor','student') NOT NULL DEFAULT 'student',
    phone         VARCHAR(20)  DEFAULT NULL,
    created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    is_active     TINYINT(1)   NOT NULL DEFAULT 1,
    CONSTRAINT chk_users_email CHECK (email LIKE '%@%.%'),
    CONSTRAINT chk_users_phone CHECK (phone IS NULL OR LENGTH(phone) >= 7)
) ENGINE=InnoDB;

CREATE INDEX idx_users_role      ON users(role);
CREATE INDEX idx_users_is_active ON users(is_active);
CREATE INDEX idx_users_email     ON users(email);

-- ============================================================
-- Table: categories
-- Subject categories for exams and question banks
-- ============================================================
CREATE TABLE categories (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(100) NOT NULL UNIQUE,
    description TEXT         DEFAULT NULL,
    created_by  INT UNSIGNED NOT NULL,
    created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_categories_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE INDEX idx_categories_created_by ON categories(created_by);

-- ============================================================
-- Table: question_banks
-- Groups of questions organised per category
-- ============================================================
CREATE TABLE question_banks (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(150) NOT NULL,
    category_id INT UNSIGNED NOT NULL,
    description TEXT         DEFAULT NULL,
    created_by  INT UNSIGNED NOT NULL,
    created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_qbanks_category   FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE RESTRICT,
    CONSTRAINT fk_qbanks_created_by FOREIGN KEY (created_by)  REFERENCES users(id)      ON DELETE RESTRICT,
    CONSTRAINT uq_qbank_name_cat    UNIQUE (name, category_id)
) ENGINE=InnoDB;

CREATE INDEX idx_qbanks_category_id ON question_banks(category_id);
CREATE INDEX idx_qbanks_created_by  ON question_banks(created_by);

-- ============================================================
-- Table: questions
-- Individual exam questions with answer options
-- ============================================================
CREATE TABLE questions (
    id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    question_bank_id INT UNSIGNED  NOT NULL,
    question_text    TEXT          NOT NULL,
    question_type    ENUM('mcq','true_false') NOT NULL DEFAULT 'mcq',
    difficulty       ENUM('easy','medium','hard') NOT NULL DEFAULT 'medium',
    marks            DECIMAL(5,2)  NOT NULL DEFAULT 1.00,
    option_a         VARCHAR(500)  NOT NULL,
    option_b         VARCHAR(500)  NOT NULL,
    option_c         VARCHAR(500)  DEFAULT NULL,
    option_d         VARCHAR(500)  DEFAULT NULL,
    correct_answer   ENUM('A','B','C','D') NOT NULL,
    explanation      TEXT          DEFAULT NULL,
    created_by       INT UNSIGNED  NOT NULL,
    created_at       DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    is_active        TINYINT(1)    NOT NULL DEFAULT 1,
    CONSTRAINT fk_questions_bank       FOREIGN KEY (question_bank_id) REFERENCES question_banks(id) ON DELETE RESTRICT,
    CONSTRAINT fk_questions_created_by FOREIGN KEY (created_by)       REFERENCES users(id)          ON DELETE RESTRICT,
    CONSTRAINT chk_questions_marks     CHECK (marks > 0),
    CONSTRAINT chk_questions_true_false CHECK (
        question_type != 'true_false' OR (option_c IS NULL AND option_d IS NULL)
    )
) ENGINE=InnoDB;

CREATE INDEX idx_questions_bank_id    ON questions(question_bank_id);
CREATE INDEX idx_questions_type       ON questions(question_type);
CREATE INDEX idx_questions_difficulty ON questions(difficulty);
CREATE INDEX idx_questions_is_active  ON questions(is_active);
CREATE INDEX idx_questions_created_by ON questions(created_by);

-- ============================================================
-- Table: exams
-- Exam definitions created by instructors/admin
-- ============================================================
CREATE TABLE exams (
    id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title            VARCHAR(200) NOT NULL,
    description      TEXT         DEFAULT NULL,
    category_id      INT UNSIGNED NOT NULL,
    created_by       INT UNSIGNED NOT NULL,
    total_marks      DECIMAL(7,2) NOT NULL,
    passing_marks    DECIMAL(7,2) NOT NULL,
    duration_minutes INT UNSIGNED NOT NULL,
    start_time       DATETIME     NOT NULL,
    end_time         DATETIME     NOT NULL,
    is_randomized    TINYINT(1)   NOT NULL DEFAULT 0,
    max_attempts     INT UNSIGNED NOT NULL DEFAULT 1,
    status           ENUM('draft','active','completed','cancelled') NOT NULL DEFAULT 'draft',
    created_at       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_exams_category   FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE RESTRICT,
    CONSTRAINT fk_exams_created_by FOREIGN KEY (created_by)  REFERENCES users(id)      ON DELETE RESTRICT,
    CONSTRAINT chk_exams_marks      CHECK (passing_marks <= total_marks AND total_marks > 0),
    CONSTRAINT chk_exams_duration   CHECK (duration_minutes > 0),
    CONSTRAINT chk_exams_max_att    CHECK (max_attempts >= 1),
    CONSTRAINT chk_exams_times      CHECK (end_time > start_time)
) ENGINE=InnoDB;

CREATE INDEX idx_exams_category_id ON exams(category_id);
CREATE INDEX idx_exams_created_by  ON exams(created_by);
CREATE INDEX idx_exams_status      ON exams(status);
CREATE INDEX idx_exams_start_time  ON exams(start_time);
CREATE INDEX idx_exams_end_time    ON exams(end_time);

-- ============================================================
-- Table: exam_questions
-- Many-to-many: questions assigned to exams
-- ============================================================
CREATE TABLE exam_questions (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    exam_id      INT UNSIGNED NOT NULL,
    question_id  INT UNSIGNED NOT NULL,
    marks        DECIMAL(5,2) NOT NULL,
    order_number INT UNSIGNED NOT NULL DEFAULT 1,
    CONSTRAINT fk_eq_exam     FOREIGN KEY (exam_id)     REFERENCES exams(id)     ON DELETE CASCADE,
    CONSTRAINT fk_eq_question FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE RESTRICT,
    CONSTRAINT uq_eq_exam_question UNIQUE (exam_id, question_id),
    CONSTRAINT chk_eq_marks   CHECK (marks > 0)
) ENGINE=InnoDB;

CREATE INDEX idx_eq_exam_id     ON exam_questions(exam_id);
CREATE INDEX idx_eq_question_id ON exam_questions(question_id);

-- ============================================================
-- Table: student_exams
-- Tracks each student's exam attempt session
-- ============================================================
CREATE TABLE student_exams (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_id          INT UNSIGNED NOT NULL,
    exam_id             INT UNSIGNED NOT NULL,
    started_at          DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    submitted_at        DATETIME     DEFAULT NULL,
    time_taken_minutes  INT UNSIGNED DEFAULT NULL,
    status              ENUM('in_progress','completed','abandoned') NOT NULL DEFAULT 'in_progress',
    attempt_number      INT UNSIGNED NOT NULL DEFAULT 1,
    CONSTRAINT fk_se_student FOREIGN KEY (student_id) REFERENCES users(id)  ON DELETE CASCADE,
    CONSTRAINT fk_se_exam    FOREIGN KEY (exam_id)    REFERENCES exams(id)   ON DELETE CASCADE,
    CONSTRAINT chk_se_attempt CHECK (attempt_number >= 1)
) ENGINE=InnoDB;

CREATE INDEX idx_se_student_id ON student_exams(student_id);
CREATE INDEX idx_se_exam_id    ON student_exams(exam_id);
CREATE INDEX idx_se_status     ON student_exams(status);
CREATE INDEX idx_se_started_at ON student_exams(started_at);

-- ============================================================
-- Table: student_answers
-- Individual answers submitted by students
-- ============================================================
CREATE TABLE student_answers (
    id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_exam_id  INT UNSIGNED NOT NULL,
    question_id      INT UNSIGNED NOT NULL,
    selected_answer  ENUM('A','B','C','D') DEFAULT NULL,
    is_correct       TINYINT(1)   DEFAULT NULL,
    marks_obtained   DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    CONSTRAINT fk_sa_student_exam FOREIGN KEY (student_exam_id) REFERENCES student_exams(id) ON DELETE CASCADE,
    CONSTRAINT fk_sa_question     FOREIGN KEY (question_id)     REFERENCES questions(id)     ON DELETE RESTRICT,
    CONSTRAINT uq_sa_attempt_question UNIQUE (student_exam_id, question_id),
    CONSTRAINT chk_sa_marks CHECK (marks_obtained >= 0)
) ENGINE=InnoDB;

CREATE INDEX idx_sa_student_exam_id ON student_answers(student_exam_id);
CREATE INDEX idx_sa_question_id     ON student_answers(question_id);
CREATE INDEX idx_sa_is_correct      ON student_answers(is_correct);

-- ============================================================
-- Table: results
-- Final computed result for each completed exam attempt
-- ============================================================
CREATE TABLE results (
    id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_exam_id  INT UNSIGNED NOT NULL UNIQUE,
    student_id       INT UNSIGNED NOT NULL,
    exam_id          INT UNSIGNED NOT NULL,
    total_marks      DECIMAL(7,2) NOT NULL,
    obtained_marks   DECIMAL(7,2) NOT NULL DEFAULT 0.00,
    percentage       DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    grade            VARCHAR(3)   NOT NULL DEFAULT 'F',
    is_passed        TINYINT(1)   NOT NULL DEFAULT 0,
    calculated_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_results_student_exam FOREIGN KEY (student_exam_id) REFERENCES student_exams(id) ON DELETE CASCADE,
    CONSTRAINT fk_results_student     FOREIGN KEY (student_id)       REFERENCES users(id)         ON DELETE CASCADE,
    CONSTRAINT fk_results_exam        FOREIGN KEY (exam_id)          REFERENCES exams(id)         ON DELETE CASCADE,
    CONSTRAINT chk_results_marks      CHECK (obtained_marks >= 0 AND obtained_marks <= total_marks),
    CONSTRAINT chk_results_pct        CHECK (percentage >= 0 AND percentage <= 100)
) ENGINE=InnoDB;

CREATE INDEX idx_results_student_id ON results(student_id);
CREATE INDEX idx_results_exam_id    ON results(exam_id);
CREATE INDEX idx_results_percentage ON results(percentage);
CREATE INDEX idx_results_is_passed  ON results(is_passed);

-- ============================================================
-- Table: audit_log
-- Immutable audit trail for user actions
-- ============================================================
CREATE TABLE audit_log (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id    INT UNSIGNED  DEFAULT NULL,
    action     VARCHAR(100)  NOT NULL,
    table_name VARCHAR(64)   DEFAULT NULL,
    record_id  INT UNSIGNED  DEFAULT NULL,
    details    TEXT          DEFAULT NULL,
    created_at DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_audit_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE INDEX idx_audit_user_id    ON audit_log(user_id);
CREATE INDEX idx_audit_action     ON audit_log(action);
CREATE INDEX idx_audit_table_name ON audit_log(table_name);
CREATE INDEX idx_audit_created_at ON audit_log(created_at);
