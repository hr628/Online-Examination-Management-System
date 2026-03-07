-- ============================================================
-- Online Examination Management System - Stored Procedures
-- File: 03_procedures.sql
-- Description: Reusable stored procedures for core logic
-- MySQL 8.x compatible
-- ============================================================

USE oems_db;

DELIMITER $$

-- ============================================================
-- Procedure 1: sp_create_exam
-- Creates a new exam after validating all inputs.
-- OUT p_exam_id returns the new exam's id (0 on failure).
-- ============================================================
DROP PROCEDURE IF EXISTS sp_create_exam$$
CREATE PROCEDURE sp_create_exam(
    IN  p_title        VARCHAR(200),
    IN  p_description  TEXT,
    IN  p_category_id  INT UNSIGNED,
    IN  p_created_by   INT UNSIGNED,
    IN  p_total_marks  DECIMAL(7,2),
    IN  p_passing_marks DECIMAL(7,2),
    IN  p_duration     INT UNSIGNED,
    IN  p_start_time   DATETIME,
    IN  p_end_time     DATETIME,
    IN  p_is_randomized TINYINT(1),
    OUT p_exam_id      INT UNSIGNED
)
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        SET p_exam_id = 0;
        ROLLBACK;
    END;

    -- Validations
    IF p_title IS NULL OR TRIM(p_title) = '' THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Exam title is required';
    END IF;

    IF p_total_marks <= 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'total_marks must be positive';
    END IF;

    IF p_passing_marks > p_total_marks THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'passing_marks cannot exceed total_marks';
    END IF;

    IF p_duration <= 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'duration must be positive';
    END IF;

    IF p_end_time <= p_start_time THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'end_time must be after start_time';
    END IF;

    START TRANSACTION;

    INSERT INTO exams
        (title, description, category_id, created_by,
         total_marks, passing_marks, duration_minutes,
         start_time, end_time, is_randomized, status)
    VALUES
        (p_title, p_description, p_category_id, p_created_by,
         p_total_marks, p_passing_marks, p_duration,
         p_start_time, p_end_time, p_is_randomized, 'draft');

    SET p_exam_id = LAST_INSERT_ID();

    INSERT INTO audit_log (user_id, action, table_name, record_id, details)
    VALUES (p_created_by, 'EXAM_CREATED', 'exams', p_exam_id,
            CONCAT('Title: ', p_title));

    COMMIT;
END$$

-- ============================================================
-- Procedure 2: sp_calculate_result
-- Recalculates the result for a given student_exam_id.
-- Grades: A+>=95, A>=85, B+>=75, B>=65, C>=50, F<50
-- ============================================================
DROP PROCEDURE IF EXISTS sp_calculate_result$$
CREATE PROCEDURE sp_calculate_result(
    IN  p_student_exam_id INT UNSIGNED,
    OUT p_percentage      DECIMAL(5,2),
    OUT p_grade           VARCHAR(3),
    OUT p_is_passed       TINYINT(1)
)
BEGIN
    DECLARE v_total_marks    DECIMAL(7,2);
    DECLARE v_obtained_marks DECIMAL(7,2);
    DECLARE v_passing_marks  DECIMAL(7,2);
    DECLARE v_student_id     INT UNSIGNED;
    DECLARE v_exam_id        INT UNSIGNED;

    -- Fetch exam parameters
    SELECT e.total_marks, e.passing_marks, se.student_id, se.exam_id
    INTO   v_total_marks, v_passing_marks, v_student_id, v_exam_id
    FROM   student_exams se
    JOIN   exams e ON e.id = se.exam_id
    WHERE  se.id = p_student_exam_id;

    -- Sum answers
    SELECT COALESCE(SUM(marks_obtained), 0)
    INTO   v_obtained_marks
    FROM   student_answers
    WHERE  student_exam_id = p_student_exam_id;

    -- Percentage
    IF v_total_marks > 0 THEN
        SET p_percentage = ROUND((v_obtained_marks / v_total_marks) * 100, 2);
    ELSE
        SET p_percentage = 0.00;
    END IF;

    -- Grade
    SET p_grade = CASE
        WHEN p_percentage >= 95 THEN 'A+'
        WHEN p_percentage >= 85 THEN 'A'
        WHEN p_percentage >= 75 THEN 'B+'
        WHEN p_percentage >= 65 THEN 'B'
        WHEN p_percentage >= 50 THEN 'C'
        ELSE 'F'
    END;

    -- Pass/fail
    SET p_is_passed = IF(v_obtained_marks >= v_passing_marks, 1, 0);

    -- Upsert result row
    INSERT INTO results
        (student_exam_id, student_id, exam_id,
         total_marks, obtained_marks, percentage, grade, is_passed, calculated_at)
    VALUES
        (p_student_exam_id, v_student_id, v_exam_id,
         v_total_marks, v_obtained_marks, p_percentage, p_grade, p_is_passed, NOW())
    ON DUPLICATE KEY UPDATE
        total_marks    = v_total_marks,
        obtained_marks = v_obtained_marks,
        percentage     = p_percentage,
        grade          = p_grade,
        is_passed      = p_is_passed,
        calculated_at  = NOW();
END$$

-- ============================================================
-- Procedure 3: sp_get_student_report
-- Returns a comprehensive report of all exam attempts for
-- a given student, ordered by most recent first.
-- ============================================================
DROP PROCEDURE IF EXISTS sp_get_student_report$$
CREATE PROCEDURE sp_get_student_report(IN p_student_id INT UNSIGNED)
BEGIN
    SELECT
        u.full_name                             AS student_name,
        u.username,
        e.title                                 AS exam_title,
        cat.name                                AS category,
        se.attempt_number,
        se.started_at,
        se.submitted_at,
        se.time_taken_minutes,
        se.status                               AS attempt_status,
        r.total_marks,
        r.obtained_marks,
        r.percentage,
        r.grade,
        r.is_passed,
        r.calculated_at
    FROM   student_exams se
    JOIN   users u   ON u.id   = se.student_id
    JOIN   exams e   ON e.id   = se.exam_id
    JOIN   categories cat ON cat.id = e.category_id
    LEFT JOIN results r ON r.student_exam_id = se.id
    WHERE  se.student_id = p_student_id
    ORDER  BY se.started_at DESC;
END$$

-- ============================================================
-- Procedure 4: sp_get_exam_analytics
-- Returns aggregate statistics for one exam.
-- ============================================================
DROP PROCEDURE IF EXISTS sp_get_exam_analytics$$
CREATE PROCEDURE sp_get_exam_analytics(IN p_exam_id INT UNSIGNED)
BEGIN
    SELECT
        e.id                                         AS exam_id,
        e.title,
        e.total_marks,
        e.passing_marks,
        COUNT(r.id)                                  AS total_attempts,
        COALESCE(ROUND(AVG(r.percentage), 2), 0)     AS avg_score,
        COALESCE(MAX(r.percentage), 0)               AS highest_score,
        COALESCE(MIN(r.percentage), 0)               AS lowest_score,
        COALESCE(ROUND(
            SUM(r.is_passed) / NULLIF(COUNT(r.id), 0) * 100, 2
        ), 0)                                        AS pass_rate,
        COUNT(DISTINCT r.student_id)                 AS distinct_students
    FROM   exams e
    LEFT JOIN results r ON r.exam_id = e.id
    WHERE  e.id = p_exam_id
    GROUP  BY e.id, e.title, e.total_marks, e.passing_marks;
END$$

-- ============================================================
-- Procedure 5: sp_assign_random_questions
-- Randomly assigns p_count questions from a question bank
-- to an exam (skips duplicates).
-- ============================================================
DROP PROCEDURE IF EXISTS sp_assign_random_questions$$
CREATE PROCEDURE sp_assign_random_questions(
    IN p_exam_id          INT UNSIGNED,
    IN p_question_bank_id INT UNSIGNED,
    IN p_count            INT UNSIGNED
)
BEGIN
    DECLARE v_max_order INT UNSIGNED DEFAULT 0;

    -- Get current highest order_number in this exam
    SELECT COALESCE(MAX(order_number), 0)
    INTO   v_max_order
    FROM   exam_questions
    WHERE  exam_id = p_exam_id;

    -- Insert random questions not already in the exam
    INSERT INTO exam_questions (exam_id, question_id, marks, order_number)
    SELECT
        p_exam_id,
        q.id,
        q.marks,
        v_max_order + ROW_NUMBER() OVER (ORDER BY RAND())
    FROM   questions q
    WHERE  q.question_bank_id = p_question_bank_id
      AND  q.is_active = 1
      AND  q.id NOT IN (
               SELECT question_id FROM exam_questions WHERE exam_id = p_exam_id
           )
    ORDER  BY RAND()
    LIMIT  p_count;
END$$

DELIMITER ;
