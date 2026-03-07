-- ============================================================
-- Online Examination Management System - Views
-- File: 04_views.sql
-- Description: Pre-built views for reporting and dashboards
-- MySQL 8.x compatible
-- ============================================================

USE oems_db;

-- ============================================================
-- View 1: view_student_performance
-- Aggregated statistics per student across all exams
-- ============================================================
DROP VIEW IF EXISTS view_student_performance;
CREATE VIEW view_student_performance AS
SELECT
    u.id                                         AS student_id,
    u.full_name                                  AS student_name,
    u.username,
    u.email,
    COUNT(r.id)                                  AS total_exams_taken,
    COALESCE(ROUND(AVG(r.percentage), 2), 0)     AS avg_percentage,
    COALESCE(MAX(r.percentage), 0)               AS best_percentage,
    COALESCE(MIN(r.percentage), 0)               AS worst_percentage,
    -- Best grade uses the ordering A+>A>B+>B>C>F
    COALESCE(
        MAX(CASE r.grade
            WHEN 'A+' THEN 6
            WHEN 'A'  THEN 5
            WHEN 'B+' THEN 4
            WHEN 'B'  THEN 3
            WHEN 'C'  THEN 2
            ELSE 1
        END), 0
    )                                            AS best_grade_rank,
    CASE
        COALESCE(
            MAX(CASE r.grade
                WHEN 'A+' THEN 6 WHEN 'A' THEN 5 WHEN 'B+' THEN 4
                WHEN 'B'  THEN 3 WHEN 'C' THEN 2 ELSE 1
            END), 0)
        WHEN 6 THEN 'A+'
        WHEN 5 THEN 'A'
        WHEN 4 THEN 'B+'
        WHEN 3 THEN 'B'
        WHEN 2 THEN 'C'
        ELSE 'F'
    END                                          AS best_grade,
    SUM(r.is_passed)                             AS total_passed,
    COUNT(r.id) - SUM(COALESCE(r.is_passed, 0)) AS total_failed
FROM   users u
LEFT JOIN student_exams se ON se.student_id = u.id AND se.status = 'completed'
LEFT JOIN results r        ON r.student_exam_id = se.id
WHERE  u.role = 'student'
  AND  u.is_active = 1
GROUP  BY u.id, u.full_name, u.username, u.email;

-- ============================================================
-- View 2: view_exam_statistics
-- Aggregated statistics per exam
-- ============================================================
DROP VIEW IF EXISTS view_exam_statistics;
CREATE VIEW view_exam_statistics AS
SELECT
    e.id                                         AS exam_id,
    e.title,
    e.status,
    e.total_marks,
    e.passing_marks,
    e.duration_minutes,
    e.start_time,
    e.end_time,
    cat.name                                     AS category_name,
    u.full_name                                  AS created_by_name,
    COUNT(DISTINCT se.id)                        AS total_attempts,
    COUNT(DISTINCT se.student_id)                AS distinct_students,
    COALESCE(ROUND(AVG(r.percentage), 2), 0)     AS avg_score,
    COALESCE(MAX(r.percentage), 0)               AS highest_score,
    COALESCE(MIN(r.percentage), 0)               AS lowest_score,
    COALESCE(ROUND(
        SUM(r.is_passed) / NULLIF(COUNT(r.id), 0) * 100, 2
    ), 0)                                        AS pass_rate
FROM   exams e
JOIN   categories cat ON cat.id = e.category_id
JOIN   users u        ON u.id   = e.created_by
LEFT JOIN student_exams se ON se.exam_id = e.id AND se.status = 'completed'
LEFT JOIN results r        ON r.student_exam_id = se.id
GROUP  BY
    e.id, e.title, e.status, e.total_marks, e.passing_marks,
    e.duration_minutes, e.start_time, e.end_time,
    cat.name, u.full_name;

-- ============================================================
-- View 3: view_question_usage
-- Questions enriched with usage count and exam list
-- ============================================================
DROP VIEW IF EXISTS view_question_usage;
CREATE VIEW view_question_usage AS
SELECT
    q.id                                                  AS question_id,
    LEFT(q.question_text, 120)                            AS question_text,
    q.question_type,
    q.difficulty,
    q.marks,
    qb.name                                               AS question_bank_name,
    cat.name                                              AS category_name,
    COUNT(DISTINCT eq.exam_id)                            AS usage_count,
    GROUP_CONCAT(DISTINCT e.title ORDER BY e.title SEPARATOR ', ') AS exam_list,
    u.full_name                                           AS created_by_name,
    q.is_active
FROM   questions q
JOIN   question_banks qb ON qb.id = q.question_bank_id
JOIN   categories cat    ON cat.id = qb.category_id
JOIN   users u           ON u.id   = q.created_by
LEFT JOIN exam_questions eq ON eq.question_id = q.id
LEFT JOIN exams e           ON e.id = eq.exam_id
GROUP  BY
    q.id, q.question_text, q.question_type, q.difficulty, q.marks,
    qb.name, cat.name, u.full_name, q.is_active;

-- ============================================================
-- View 4: view_upcoming_exams
-- Active/draft exams scheduled in the future
-- ============================================================
DROP VIEW IF EXISTS view_upcoming_exams;
CREATE VIEW view_upcoming_exams AS
SELECT
    e.id,
    e.title,
    e.description,
    e.status,
    e.total_marks,
    e.passing_marks,
    e.duration_minutes,
    e.start_time,
    e.end_time,
    TIMESTAMPDIFF(MINUTE, NOW(), e.start_time)      AS minutes_until_start,
    cat.name                                        AS category_name,
    u.full_name                                     AS instructor_name,
    COUNT(DISTINCT eq.question_id)                  AS question_count,
    COUNT(DISTINCT se.student_id)                   AS registered_students
FROM   exams e
JOIN   categories cat ON cat.id = e.category_id
JOIN   users u        ON u.id   = e.created_by
LEFT JOIN exam_questions eq ON eq.exam_id = e.id
LEFT JOIN student_exams se  ON se.exam_id = e.id
WHERE  e.start_time > NOW()
  AND  e.status IN ('active', 'draft')
GROUP  BY
    e.id, e.title, e.description, e.status, e.total_marks,
    e.passing_marks, e.duration_minutes, e.start_time, e.end_time,
    cat.name, u.full_name
ORDER  BY e.start_time ASC;
