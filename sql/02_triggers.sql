-- ============================================================
-- Online Examination Management System - Triggers
-- File: 02_triggers.sql
-- Description: Business-logic triggers
-- MySQL 8.x compatible
-- ============================================================

USE oems_db;

DELIMITER $$

-- ============================================================
-- Trigger 1: before_exam_insert
-- Validates exam dates and duration before insert
-- ============================================================
DROP TRIGGER IF EXISTS before_exam_insert$$
CREATE TRIGGER before_exam_insert
BEFORE INSERT ON exams
FOR EACH ROW
BEGIN
    IF NEW.end_time <= NEW.start_time THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'end_time must be after start_time';
    END IF;

    IF NEW.duration_minutes <= 0 THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'duration_minutes must be greater than 0';
    END IF;

    IF NEW.passing_marks > NEW.total_marks THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'passing_marks cannot exceed total_marks';
    END IF;
END$$

-- ============================================================
-- Trigger 2: before_exam_update
-- Same validations on UPDATE
-- ============================================================
DROP TRIGGER IF EXISTS before_exam_update$$
CREATE TRIGGER before_exam_update
BEFORE UPDATE ON exams
FOR EACH ROW
BEGIN
    IF NEW.end_time <= NEW.start_time THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'end_time must be after start_time';
    END IF;

    IF NEW.duration_minutes <= 0 THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'duration_minutes must be greater than 0';
    END IF;

    IF NEW.passing_marks > NEW.total_marks THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'passing_marks cannot exceed total_marks';
    END IF;
END$$

-- ============================================================
-- Trigger 3: after_student_exam_complete
-- Auto-calculates and inserts/updates results when a
-- student_exam row transitions to status='completed'
-- ============================================================
DROP TRIGGER IF EXISTS after_student_exam_complete$$
CREATE TRIGGER after_student_exam_complete
AFTER UPDATE ON student_exams
FOR EACH ROW
BEGIN
    DECLARE v_total_marks    DECIMAL(7,2);
    DECLARE v_obtained_marks DECIMAL(7,2);
    DECLARE v_percentage     DECIMAL(5,2);
    DECLARE v_grade          VARCHAR(3);
    DECLARE v_is_passed      TINYINT(1);
    DECLARE v_passing_marks  DECIMAL(7,2);

    -- Only act when status changes to 'completed'
    IF NEW.status = 'completed' AND OLD.status != 'completed' THEN

        -- Get total marks and passing marks for the exam
        SELECT total_marks, passing_marks
        INTO   v_total_marks, v_passing_marks
        FROM   exams
        WHERE  id = NEW.exam_id;

        -- Sum marks obtained by student in this attempt
        SELECT COALESCE(SUM(marks_obtained), 0)
        INTO   v_obtained_marks
        FROM   student_answers
        WHERE  student_exam_id = NEW.id;

        -- Calculate percentage
        IF v_total_marks > 0 THEN
            SET v_percentage = ROUND((v_obtained_marks / v_total_marks) * 100, 2);
        ELSE
            SET v_percentage = 0.00;
        END IF;

        -- Determine grade
        SET v_grade = CASE
            WHEN v_percentage >= 95 THEN 'A+'
            WHEN v_percentage >= 85 THEN 'A'
            WHEN v_percentage >= 75 THEN 'B+'
            WHEN v_percentage >= 65 THEN 'B'
            WHEN v_percentage >= 50 THEN 'C'
            ELSE 'F'
        END;

        -- Determine pass/fail
        SET v_is_passed = IF(v_obtained_marks >= v_passing_marks, 1, 0);

        -- Insert or update the result record
        INSERT INTO results
            (student_exam_id, student_id, exam_id,
             total_marks, obtained_marks, percentage, grade, is_passed, calculated_at)
        VALUES
            (NEW.id, NEW.student_id, NEW.exam_id,
             v_total_marks, v_obtained_marks, v_percentage, v_grade, v_is_passed, NOW())
        ON DUPLICATE KEY UPDATE
            total_marks    = v_total_marks,
            obtained_marks = v_obtained_marks,
            percentage     = v_percentage,
            grade          = v_grade,
            is_passed      = v_is_passed,
            calculated_at  = NOW();

        -- Audit entry
        INSERT INTO audit_log (user_id, action, table_name, record_id, details)
        VALUES (NEW.student_id, 'EXAM_COMPLETED', 'student_exams', NEW.id,
                CONCAT('Exam ID: ', NEW.exam_id,
                       ' | Score: ', v_obtained_marks, '/', v_total_marks,
                       ' | Grade: ', v_grade));
    END IF;
END$$

-- ============================================================
-- Trigger 4: before_question_delete
-- Prevents deletion of questions assigned to active exams
-- ============================================================
DROP TRIGGER IF EXISTS before_question_delete$$
CREATE TRIGGER before_question_delete
BEFORE DELETE ON questions
FOR EACH ROW
BEGIN
    DECLARE v_active_count INT DEFAULT 0;

    SELECT COUNT(*)
    INTO   v_active_count
    FROM   exam_questions eq
    JOIN   exams e ON e.id = eq.exam_id
    WHERE  eq.question_id = OLD.id
      AND  e.status IN ('active', 'draft');

    IF v_active_count > 0 THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Cannot delete question: it is assigned to one or more active/draft exams';
    END IF;
END$$

-- ============================================================
-- Trigger 5: after_user_insert
-- Creates an audit log entry whenever a new user registers
-- ============================================================
DROP TRIGGER IF EXISTS after_user_insert$$
CREATE TRIGGER after_user_insert
AFTER INSERT ON users
FOR EACH ROW
BEGIN
    INSERT INTO audit_log (user_id, action, table_name, record_id, details)
    VALUES (NEW.id, 'USER_REGISTERED', 'users', NEW.id,
            CONCAT('Username: ', NEW.username, ' | Role: ', NEW.role));
END$$

-- ============================================================
-- Trigger 6: before_answer_update
-- Prevents modification of answers after the exam is submitted
-- ============================================================
DROP TRIGGER IF EXISTS before_answer_update$$
CREATE TRIGGER before_answer_update
BEFORE UPDATE ON student_answers
FOR EACH ROW
BEGIN
    DECLARE v_exam_status VARCHAR(20);

    SELECT status
    INTO   v_exam_status
    FROM   student_exams
    WHERE  id = OLD.student_exam_id;

    IF v_exam_status = 'completed' THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Cannot modify answers after exam has been submitted';
    END IF;
END$$

DELIMITER ;
