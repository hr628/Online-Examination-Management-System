-- ============================================================
-- Online Examination Management System - Complex Queries
-- File: 06_complex_queries.sql
-- Description: 12 complex queries demonstrating SQL concepts
-- MySQL 8.x compatible  |  Run AFTER 05_sample_data.sql
-- ============================================================

USE oems_db;

-- ============================================================
-- Query 1 – Basic SELECT with WHERE, ORDER BY, LIMIT
-- Purpose : List the 10 most recently registered active students.
-- ============================================================
SELECT
    id,
    full_name,
    username,
    email,
    created_at
FROM   users
WHERE  role      = 'student'
  AND  is_active = 1
ORDER  BY created_at DESC
LIMIT  10;

-- ============================================================
-- Query 2 – Aggregate Functions with GROUP BY and HAVING
-- Purpose : Find exams where the average student score is below
--           60%, i.e., exams most students failed.
-- ============================================================
SELECT
    e.id                           AS exam_id,
    e.title                        AS exam_title,
    COUNT(r.id)                    AS total_attempts,
    ROUND(AVG(r.percentage), 2)    AS avg_percentage,
    ROUND(MIN(r.percentage), 2)    AS min_percentage,
    ROUND(MAX(r.percentage), 2)    AS max_percentage,
    SUM(r.is_passed)               AS students_passed
FROM   exams e
JOIN   results r ON r.exam_id = e.id
GROUP  BY e.id, e.title
HAVING avg_percentage < 60
ORDER  BY avg_percentage ASC;

-- ============================================================
-- Query 3 – INNER JOIN
-- Purpose : Retrieve student names alongside their exam results.
-- ============================================================
SELECT
    u.full_name                    AS student_name,
    u.username,
    e.title                        AS exam_title,
    r.obtained_marks,
    r.total_marks,
    r.percentage,
    r.grade,
    IF(r.is_passed, 'PASS', 'FAIL') AS result
FROM   users u
INNER JOIN results r       ON r.student_id = u.id
INNER JOIN exams e         ON e.id         = r.exam_id
WHERE  u.role = 'student'
ORDER  BY u.full_name, r.percentage DESC;

-- ============================================================
-- Query 4 – LEFT JOIN
-- Purpose : List ALL exams with the number of students who
--           attempted them (0 for exams with no attempts).
-- ============================================================
SELECT
    e.id,
    e.title,
    e.status,
    e.total_marks,
    e.start_time,
    COUNT(DISTINCT se.student_id) AS student_count,
    COUNT(se.id)                  AS total_attempts
FROM   exams e
LEFT JOIN student_exams se ON se.exam_id = e.id
                          AND se.status  = 'completed'
GROUP  BY e.id, e.title, e.status, e.total_marks, e.start_time
ORDER  BY student_count DESC, e.start_time DESC;

-- ============================================================
-- Query 5 – RIGHT JOIN
-- Purpose : List all questions along with exams they are
--           assigned to (NULL exam if unassigned).
-- ============================================================
SELECT
    q.id                           AS question_id,
    LEFT(q.question_text, 80)      AS question_text,
    q.question_type,
    q.difficulty,
    e.title                        AS exam_title,
    e.status                       AS exam_status,
    eq.marks                       AS question_marks
FROM   exam_questions eq
RIGHT JOIN questions q ON q.id = eq.question_id
LEFT  JOIN exams e     ON e.id = eq.exam_id
ORDER  BY q.id, e.title;

-- ============================================================
-- Query 6 – Nested Subquery (non-correlated)
-- Purpose : Find students whose average exam score is above
--           the overall average score of all students.
-- ============================================================
SELECT
    u.full_name                    AS student_name,
    u.username,
    ROUND(AVG(r.percentage), 2)    AS personal_avg_pct,
    (SELECT ROUND(AVG(percentage), 2) FROM results)  AS global_avg_pct
FROM   users u
JOIN   results r ON r.student_id = u.id
WHERE  u.role = 'student'
GROUP  BY u.id, u.full_name, u.username
HAVING personal_avg_pct >
       (SELECT AVG(percentage) FROM results)
ORDER  BY personal_avg_pct DESC;

-- ============================================================
-- Query 7 – Correlated Subquery
-- Purpose : For each exam, find the best-performing student.
-- ============================================================
SELECT
    e.id                           AS exam_id,
    e.title                        AS exam_title,
    u.full_name                    AS top_student,
    r.percentage                   AS score,
    r.grade
FROM   exams e
JOIN   results r ON r.exam_id = e.id
JOIN   users u   ON u.id      = r.student_id
WHERE  r.percentage = (
    -- Correlated: run once per exam row
    SELECT MAX(r2.percentage)
    FROM   results r2
    WHERE  r2.exam_id = e.id
)
ORDER  BY e.id;

-- ============================================================
-- Query 8 – UNION / UNION ALL
-- Purpose : Produce a unified directory of all instructors and
--           admins with their role labels.
-- ============================================================
SELECT
    full_name,
    username,
    email,
    'Instructor' AS role_label,
    created_at
FROM   users
WHERE  role = 'instructor'
  AND  is_active = 1

UNION ALL

SELECT
    full_name,
    username,
    email,
    'Administrator' AS role_label,
    created_at
FROM   users
WHERE  role = 'admin'
  AND  is_active = 1

ORDER  BY role_label, full_name;

-- ============================================================
-- Query 9 – EXISTS / NOT EXISTS
-- Purpose : Find students who have NOT attempted exam 1 yet.
-- ============================================================
SELECT
    u.id,
    u.full_name,
    u.username,
    u.email
FROM   users u
WHERE  u.role      = 'student'
  AND  u.is_active = 1
  AND  NOT EXISTS (
      SELECT 1
      FROM   student_exams se
      WHERE  se.student_id = u.id
        AND  se.exam_id    = 1
  )
ORDER  BY u.full_name;

-- ============================================================
-- Query 10 – Window Functions
-- Purpose : Rank students by their overall average percentage
--           (dense rank), and show their running cumulative
--           average as more students are considered.
-- ============================================================
SELECT
    u.full_name                                          AS student_name,
    u.username,
    ROUND(AVG(r.percentage), 2)                          AS avg_percentage,
    DENSE_RANK() OVER (
        ORDER BY AVG(r.percentage) DESC
    )                                                    AS overall_rank,
    ROUND(
        AVG(AVG(r.percentage)) OVER (
            ORDER BY AVG(r.percentage) DESC
            ROWS BETWEEN UNBOUNDED PRECEDING AND CURRENT ROW
        ), 2
    )                                                    AS running_avg
FROM   users u
JOIN   results r ON r.student_id = u.id
WHERE  u.role = 'student'
GROUP  BY u.id, u.full_name, u.username
ORDER  BY overall_rank;

-- ============================================================
-- Query 11 – CASE Statement
-- Purpose : Categorise students' overall performance into
--           performance tiers using conditional logic.
-- ============================================================
SELECT
    u.full_name                   AS student_name,
    ROUND(AVG(r.percentage), 2)   AS avg_percentage,
    CASE
        WHEN AVG(r.percentage) >= 90 THEN 'Outstanding'
        WHEN AVG(r.percentage) >= 75 THEN 'Good'
        WHEN AVG(r.percentage) >= 60 THEN 'Average'
        WHEN AVG(r.percentage) >= 40 THEN 'Below Average'
        ELSE                              'Poor'
    END                           AS performance_tier,
    COUNT(r.id)                   AS exams_taken,
    SUM(r.is_passed)              AS exams_passed,
    CONCAT(SUM(r.is_passed), '/', COUNT(r.id)) AS pass_ratio
FROM   users u
JOIN   results r ON r.student_id = u.id
WHERE  u.role = 'student'
GROUP  BY u.id, u.full_name
ORDER  BY avg_percentage DESC;

-- ============================================================
-- Query 12 – Date / Time Functions
-- Purpose : Exam scheduling report: show time until exam
--           starts, duration in h:mm format, and the weekday
--           on which each upcoming exam falls.
-- ============================================================
SELECT
    e.id                                             AS exam_id,
    e.title,
    e.status,
    e.start_time,
    e.end_time,
    -- Human-friendly duration
    CONCAT(
        FLOOR(e.duration_minutes / 60), 'h ',
        LPAD(MOD(e.duration_minutes, 60), 2, '0'), 'm'
    )                                                AS duration_label,
    -- Weekday of the exam
    DAYNAME(e.start_time)                            AS exam_day,
    -- Minutes until exam starts (negative if already started)
    TIMESTAMPDIFF(MINUTE, NOW(), e.start_time)       AS minutes_until_start,
    -- Date part only
    DATE(e.start_time)                               AS exam_date,
    -- Month name
    MONTHNAME(e.start_time)                          AS exam_month,
    -- Exam window in hours
    ROUND(TIMESTAMPDIFF(MINUTE, e.start_time, e.end_time) / 60, 1) AS window_hours
FROM   exams e
ORDER  BY e.start_time ASC;
