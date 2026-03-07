-- ============================================================
-- Online Examination Management System - Sample Data
-- File: 05_sample_data.sql
-- Description: Seed data for development / demo
-- All passwords are hashed with PHP password_hash()
-- Plain-text passwords:
--   admin     → Admin@123
--   others    → Pass@1234
-- MySQL 8.x compatible
-- ============================================================

USE oems_db;

-- ============================================================
-- USERS  (1 admin + 4 instructors + 15 students = 20)
-- ============================================================
INSERT INTO users (username, email, password_hash, full_name, role, phone) VALUES
-- Admin
('admin',       'admin@oems.edu',         '$2y$10$TKh8H1.PfTYN5RjBFVnQUuNgDcFOqfhHoGMz.1M3OvHTDlWfkFvEO', 'System Administrator', 'admin',      '+1-555-0100'),
-- Instructors
('instructor1', 'instr1@oems.edu',        '$2y$10$TKh8H1.PfTYN5RjBFVnQUuNgDcFOqfhHoGMz.1M3OvHTDlWfkFvEO', 'Dr. Alice Johnson',    'instructor', '+1-555-0101'),
('instructor2', 'instr2@oems.edu',        '$2y$10$TKh8H1.PfTYN5RjBFVnQUuNgDcFOqfhHoGMz.1M3OvHTDlWfkFvEO', 'Prof. Bob Williams',   'instructor', '+1-555-0102'),
('instructor3', 'instr3@oems.edu',        '$2y$10$TKh8H1.PfTYN5RjBFVnQUuNgDcFOqfhHoGMz.1M3OvHTDlWfkFvEO', 'Dr. Carol Martinez',   'instructor', '+1-555-0103'),
('instructor4', 'instr4@oems.edu',        '$2y$10$TKh8H1.PfTYN5RjBFVnQUuNgDcFOqfhHoGMz.1M3OvHTDlWfkFvEO', 'Prof. David Brown',    'instructor', '+1-555-0104'),
-- Students
('student1',  'stu1@student.edu',  '$2y$10$TKh8H1.PfTYN5RjBFVnQUuNgDcFOqfhHoGMz.1M3OvHTDlWfkFvEO', 'Emma Wilson',      'student', '+1-555-0201'),
('student2',  'stu2@student.edu',  '$2y$10$TKh8H1.PfTYN5RjBFVnQUuNgDcFOqfhHoGMz.1M3OvHTDlWfkFvEO', 'James Anderson',   'student', '+1-555-0202'),
('student3',  'stu3@student.edu',  '$2y$10$TKh8H1.PfTYN5RjBFVnQUuNgDcFOqfhHoGMz.1M3OvHTDlWfkFvEO', 'Sophia Taylor',    'student', '+1-555-0203'),
('student4',  'stu4@student.edu',  '$2y$10$TKh8H1.PfTYN5RjBFVnQUuNgDcFOqfhHoGMz.1M3OvHTDlWfkFvEO', 'Oliver Thomas',    'student', '+1-555-0204'),
('student5',  'stu5@student.edu',  '$2y$10$TKh8H1.PfTYN5RjBFVnQUuNgDcFOqfhHoGMz.1M3OvHTDlWfkFvEO', 'Isabella Jackson', 'student', '+1-555-0205'),
('student6',  'stu6@student.edu',  '$2y$10$TKh8H1.PfTYN5RjBFVnQUuNgDcFOqfhHoGMz.1M3OvHTDlWfkFvEO', 'Lucas White',      'student', '+1-555-0206'),
('student7',  'stu7@student.edu',  '$2y$10$TKh8H1.PfTYN5RjBFVnQUuNgDcFOqfhHoGMz.1M3OvHTDlWfkFvEO', 'Mia Harris',       'student', '+1-555-0207'),
('student8',  'stu8@student.edu',  '$2y$10$TKh8H1.PfTYN5RjBFVnQUuNgDcFOqfhHoGMz.1M3OvHTDlWfkFvEO', 'Ethan Martin',     'student', '+1-555-0208'),
('student9',  'stu9@student.edu',  '$2y$10$TKh8H1.PfTYN5RjBFVnQUuNgDcFOqfhHoGMz.1M3OvHTDlWfkFvEO', 'Ava Thompson',     'student', '+1-555-0209'),
('student10', 'stu10@student.edu', '$2y$10$TKh8H1.PfTYN5RjBFVnQUuNgDcFOqfhHoGMz.1M3OvHTDlWfkFvEO', 'Noah Garcia',      'student', '+1-555-0210'),
('student11', 'stu11@student.edu', '$2y$10$TKh8H1.PfTYN5RjBFVnQUuNgDcFOqfhHoGMz.1M3OvHTDlWfkFvEO', 'Charlotte Martinez','student','+1-555-0211'),
('student12', 'stu12@student.edu', '$2y$10$TKh8H1.PfTYN5RjBFVnQUuNgDcFOqfhHoGMz.1M3OvHTDlWfkFvEO', 'Liam Robinson',    'student', '+1-555-0212'),
('student13', 'stu13@student.edu', '$2y$10$TKh8H1.PfTYN5RjBFVnQUuNgDcFOqfhHoGMz.1M3OvHTDlWfkFvEO', 'Amelia Clark',     'student', '+1-555-0213'),
('student14', 'stu14@student.edu', '$2y$10$TKh8H1.PfTYN5RjBFVnQUuNgDcFOqfhHoGMz.1M3OvHTDlWfkFvEO', 'William Lewis',    'student', '+1-555-0214'),
('student15', 'stu15@student.edu', '$2y$10$TKh8H1.PfTYN5RjBFVnQUuNgDcFOqfhHoGMz.1M3OvHTDlWfkFvEO', 'Harper Lee',       'student', '+1-555-0215');

-- ============================================================
-- CATEGORIES (5)
-- ============================================================
INSERT INTO categories (name, description, created_by) VALUES
('Mathematics',       'Algebra, Calculus, Statistics and more',          1),
('Physics',           'Mechanics, Thermodynamics, Electromagnetism',     1),
('Chemistry',         'Organic, Inorganic and Physical Chemistry',       1),
('Computer Science',  'Programming, Data Structures, Algorithms, DBMS',  1),
('English',           'Grammar, Literature, Comprehension',              1);

-- ============================================================
-- QUESTION BANKS (5 – one per category)
-- ============================================================
INSERT INTO question_banks (name, category_id, description, created_by) VALUES
('Mathematics Bank',      1, 'General mathematics questions',             2),
('Physics Bank',          2, 'General physics questions',                 3),
('Chemistry Bank',        3, 'General chemistry questions',               3),
('Computer Science Bank', 4, 'CS fundamentals and programming questions', 4),
('English Language Bank', 5, 'English grammar and comprehension',         5);

-- ============================================================
-- QUESTIONS (32 questions – mix of MCQ and True/False)
-- ============================================================
-- Mathematics (QB 1) ─ 7 questions
INSERT INTO questions
    (question_bank_id, question_text, question_type, difficulty, marks,
     option_a, option_b, option_c, option_d, correct_answer, explanation, created_by)
VALUES
(1, 'What is the derivative of sin(x)?',
    'mcq', 'easy', 1,
    'cos(x)', '-cos(x)', '-sin(x)', 'tan(x)', 'A',
    'The derivative of sin(x) is cos(x).', 2),

(1, 'Solve for x: 2x + 5 = 13',
    'mcq', 'easy', 1,
    'x = 3', 'x = 4', 'x = 5', 'x = 6', 'B',
    '2x = 8, therefore x = 4.', 2),

(1, 'What is the value of log₁₀(1000)?',
    'mcq', 'medium', 2,
    '2', '3', '4', '5', 'B',
    'log₁₀(1000) = log₁₀(10³) = 3.', 2),

(1, 'The integral of 1/x dx is ln|x| + C.',
    'true_false', 'medium', 1,
    'True', 'False', NULL, NULL, 'A',
    'Yes, ∫(1/x)dx = ln|x| + C.', 2),

(1, 'What is the sum of the first 10 natural numbers?',
    'mcq', 'easy', 1,
    '45', '50', '55', '60', 'C',
    'Sum = n(n+1)/2 = 10×11/2 = 55.', 2),

(1, 'The value of √(144) is 12.',
    'true_false', 'easy', 1,
    'True', 'False', NULL, NULL, 'A',
    '12 × 12 = 144, so √144 = 12.', 2),

(1, 'What is the area of a circle with radius 7 cm? (π ≈ 22/7)',
    'mcq', 'medium', 2,
    '144 cm²', '154 cm²', '164 cm²', '174 cm²', 'B',
    'Area = πr² = (22/7)×49 = 154 cm².', 2),

-- Physics (QB 2) ─ 7 questions
(2, 'What is the SI unit of force?',
    'mcq', 'easy', 1,
    'Watt', 'Joule', 'Newton', 'Pascal', 'C',
    'Force is measured in Newtons (N).', 3),

(2, 'An object in free fall accelerates at approximately 9.8 m/s².',
    'true_false', 'easy', 1,
    'True', 'False', NULL, NULL, 'A',
    'Standard gravitational acceleration on Earth is ~9.8 m/s².', 3),

(2, 'What is the formula for kinetic energy?',
    'mcq', 'medium', 2,
    'KE = mgh', 'KE = ½mv²', 'KE = mv', 'KE = F×d', 'B',
    'Kinetic Energy = ½ × mass × velocity².', 3),

(2, 'Sound travels faster in vacuum than in air.',
    'true_false', 'medium', 1,
    'True', 'False', NULL, NULL, 'B',
    'Sound requires a medium; it cannot travel through vacuum.', 3),

(2, 'Which law states that every action has an equal and opposite reaction?',
    'mcq', 'easy', 1,
    "Newton's First Law", "Newton's Second Law", "Newton's Third Law", "Hooke's Law", 'C',
    "Newton's Third Law of Motion.", 3),

(2, 'What is the speed of light in vacuum (approx)?',
    'mcq', 'medium', 2,
    '3×10⁸ m/s', '3×10⁶ m/s', '3×10¹⁰ m/s', '3×10⁴ m/s', 'A',
    'c ≈ 3×10⁸ m/s.', 3),

(2, 'Work done is the dot product of force and displacement.',
    'true_false', 'hard', 2,
    'True', 'False', NULL, NULL, 'A',
    'W = F·d·cos(θ), which is indeed the dot product.', 3),

-- Chemistry (QB 3) ─ 6 questions
(3, 'What is the atomic number of Carbon?',
    'mcq', 'easy', 1,
    '4', '6', '8', '12', 'B',
    'Carbon has atomic number 6.', 3),

(3, 'Water is a compound of Hydrogen and Oxygen.',
    'true_false', 'easy', 1,
    'True', 'False', NULL, NULL, 'A',
    'H₂O = 2 Hydrogen + 1 Oxygen.', 3),

(3, 'What is the pH of a neutral solution at 25°C?',
    'mcq', 'easy', 1,
    '0', '5', '7', '14', 'C',
    'A neutral solution has pH = 7.', 3),

(3, 'Which gas is produced when zinc reacts with dilute HCl?',
    'mcq', 'medium', 2,
    'Oxygen', 'Carbon Dioxide', 'Hydrogen', 'Chlorine', 'C',
    'Zn + 2HCl → ZnCl₂ + H₂↑', 3),

(3, 'Avogadro Number is 6.022 × 10²³.',
    'true_false', 'medium', 1,
    'True', 'False', NULL, NULL, 'A',
    'The Avogadro constant NA = 6.022×10²³ mol⁻¹.', 3),

(3, 'Which is the most electronegative element?',
    'mcq', 'hard', 2,
    'Oxygen', 'Chlorine', 'Fluorine', 'Nitrogen', 'C',
    'Fluorine (F) is the most electronegative element.', 3),

-- Computer Science (QB 4) ─ 7 questions
(4, 'Which data structure uses LIFO order?',
    'mcq', 'easy', 1,
    'Queue', 'Stack', 'Tree', 'Graph', 'B',
    'A Stack follows Last-In-First-Out (LIFO).', 4),

(4, 'What does SQL stand for?',
    'mcq', 'easy', 1,
    'Structured Question Language', 'Simple Query Language',
    'Structured Query Language', 'Sequential Query Logic', 'C',
    'SQL = Structured Query Language.', 4),

(4, 'The time complexity of binary search is O(log n).',
    'true_false', 'medium', 1,
    'True', 'False', NULL, NULL, 'A',
    'Binary search divides the search space in half each step → O(log n).', 4),

(4, 'Which sorting algorithm has the best average-case complexity?',
    'mcq', 'hard', 3,
    'Bubble Sort', 'Selection Sort', 'Merge Sort', 'Insertion Sort', 'C',
    'Merge Sort has average O(n log n) complexity.', 4),

(4, 'In OOP, what is encapsulation?',
    'mcq', 'medium', 2,
    'Inheriting properties from a parent class',
    'Binding data and methods together in a class',
    'Overriding parent methods',
    'Creating multiple instances', 'B',
    'Encapsulation = bundling data and methods together.', 4),

(4, 'A primary key can contain NULL values.',
    'true_false', 'easy', 1,
    'True', 'False', NULL, NULL, 'B',
    'Primary keys must be NOT NULL and unique.', 4),

(4, 'Which protocol is used for secure web communication?',
    'mcq', 'medium', 2,
    'HTTP', 'FTP', 'HTTPS', 'SMTP', 'C',
    'HTTPS (HTTP Secure) uses SSL/TLS encryption.', 4),

-- English (QB 5) ─ 5 questions
(5, 'Which of the following is a synonym for "happy"?',
    'mcq', 'easy', 1,
    'Sad', 'Joyful', 'Angry', 'Tired', 'B',
    '"Joyful" means very happy.', 5),

(5, 'A sentence must have a subject and a predicate.',
    'true_false', 'easy', 1,
    'True', 'False', NULL, NULL, 'A',
    'Every complete sentence needs both a subject and predicate.', 5),

(5, 'Choose the correct spelling:',
    'mcq', 'medium', 1,
    'Accomodate', 'Accommodate', 'Acomodate', 'Accomdate', 'B',
    'The correct spelling is "accommodate" (double c, double m).', 5),

(5, 'An adjective modifies a verb.',
    'true_false', 'medium', 1,
    'True', 'False', NULL, NULL, 'B',
    'Adjectives modify nouns; adverbs modify verbs.', 5),

(5, 'Which literary device is used in "The wind howled angrily"?',
    'mcq', 'hard', 2,
    'Simile', 'Metaphor', 'Personification', 'Alliteration', 'C',
    'Attributing human emotion to wind is personification.', 5);

-- ============================================================
-- EXAMS (8)
-- ============================================================
INSERT INTO exams
    (title, description, category_id, created_by, total_marks, passing_marks,
     duration_minutes, start_time, end_time, is_randomized, max_attempts, status)
VALUES
('Mathematics Fundamentals',   'Basic algebra and calculus',            1, 2, 10, 5, 30,
 DATE_SUB(NOW(), INTERVAL 30 DAY), DATE_SUB(NOW(), INTERVAL 29 DAY), 0, 2, 'completed'),

('Physics Basics',             'Newton laws and kinematics',            2, 3, 10, 5, 45,
 DATE_SUB(NOW(), INTERVAL 20 DAY), DATE_SUB(NOW(), INTERVAL 19 DAY), 1, 1, 'completed'),

('Chemistry Essentials',       'Atomic structure and reactions',        3, 3, 10, 5, 40,
 DATE_SUB(NOW(), INTERVAL 10 DAY), DATE_SUB(NOW(), INTERVAL  9 DAY), 0, 1, 'completed'),

('CS Fundamentals',            'Data structures and algorithms',        4, 4, 14, 7, 60,
 DATE_SUB(NOW(), INTERVAL  5 DAY), DATE_SUB(NOW(), INTERVAL  4 DAY), 1, 2, 'completed'),

('English Grammar Test',       'Grammar, vocabulary and comprehension', 5, 5, 6,  3, 25,
 DATE_SUB(NOW(), INTERVAL  2 DAY), DATE_SUB(NOW(), INTERVAL  1 DAY), 0, 1, 'completed'),

('Advanced Mathematics',       'Integration and differential equations',1, 2, 10, 6, 60,
 DATE_ADD(NOW(), INTERVAL  2 DAY), DATE_ADD(NOW(), INTERVAL  3 DAY), 1, 1, 'active'),

('Physics Advanced Topics',    'Electromagnetism and optics',           2, 3, 10, 5, 50,
 DATE_ADD(NOW(), INTERVAL  5 DAY), DATE_ADD(NOW(), INTERVAL  6 DAY), 0, 1, 'active'),

('Computer Networks Quiz',     'OSI model, protocols, networking',      4, 4,  7, 4, 30,
 DATE_ADD(NOW(), INTERVAL 10 DAY), DATE_ADD(NOW(), INTERVAL 11 DAY), 1, 1, 'draft');

-- ============================================================
-- EXAM_QUESTIONS  (assign questions to exams)
-- ============================================================
-- Exam 1 – Mathematics (questions 1-5)
INSERT INTO exam_questions (exam_id, question_id, marks, order_number) VALUES
(1,1,2,1),(1,2,2,2),(1,3,2,3),(1,4,2,4),(1,5,2,5);

-- Exam 2 – Physics (questions 8-13)
INSERT INTO exam_questions (exam_id, question_id, marks, order_number) VALUES
(2,8,2,1),(2,9,2,2),(2,10,2,3),(2,11,2,4),(2,12,2,5);

-- Exam 3 – Chemistry (questions 15-20)
INSERT INTO exam_questions (exam_id, question_id, marks, order_number) VALUES
(3,15,2,1),(3,16,2,2),(3,17,2,3),(3,18,2,4),(3,19,2,5);

-- Exam 4 – CS (questions 21-27)
INSERT INTO exam_questions (exam_id, question_id, marks, order_number) VALUES
(4,21,2,1),(4,22,2,2),(4,23,2,3),(4,24,2,4),(4,25,2,5),(4,26,2,6),(4,27,2,7);

-- Exam 5 – English (questions 28-32)
INSERT INTO exam_questions (exam_id, question_id, marks, order_number) VALUES
(5,28,2,1),(5,29,2,2),(5,30,2,3);

-- Exam 6 – Advanced Math (questions 1,3,6,7)
INSERT INTO exam_questions (exam_id, question_id, marks, order_number) VALUES
(6,1,2,1),(6,3,3,2),(6,6,2,3),(6,7,3,4);

-- Exam 7 – Physics Advanced (questions 8,10,12,13,14)
INSERT INTO exam_questions (exam_id, question_id, marks, order_number) VALUES
(7,8,2,1),(7,10,2,2),(7,12,2,3),(7,13,2,4),(7,14,2,5);

-- Exam 8 – Networks (questions 22,26,27)
INSERT INTO exam_questions (exam_id, question_id, marks, order_number) VALUES
(8,22,2,1),(8,26,3,2),(8,27,2,3);

-- ============================================================
-- STUDENT_EXAMS  (20+ attempts for completed exams)
-- ============================================================
-- Exam 1 – Math (students 6-12 attempted)
INSERT INTO student_exams (student_id, exam_id, started_at, submitted_at, time_taken_minutes, status, attempt_number) VALUES
(6,  1, DATE_SUB(NOW(),INTERVAL 30 DAY), DATE_SUB(NOW(),INTERVAL 30 DAY) + INTERVAL 25 MINUTE, 25, 'completed', 1),
(7,  1, DATE_SUB(NOW(),INTERVAL 30 DAY), DATE_SUB(NOW(),INTERVAL 30 DAY) + INTERVAL 22 MINUTE, 22, 'completed', 1),
(8,  1, DATE_SUB(NOW(),INTERVAL 30 DAY), DATE_SUB(NOW(),INTERVAL 30 DAY) + INTERVAL 28 MINUTE, 28, 'completed', 1),
(9,  1, DATE_SUB(NOW(),INTERVAL 30 DAY), DATE_SUB(NOW(),INTERVAL 30 DAY) + INTERVAL 20 MINUTE, 20, 'completed', 1),
(10, 1, DATE_SUB(NOW(),INTERVAL 30 DAY), DATE_SUB(NOW(),INTERVAL 30 DAY) + INTERVAL 18 MINUTE, 18, 'completed', 1),

-- Exam 2 – Physics (students 6-11)
(6,  2, DATE_SUB(NOW(),INTERVAL 20 DAY), DATE_SUB(NOW(),INTERVAL 20 DAY) + INTERVAL 40 MINUTE, 40, 'completed', 1),
(7,  2, DATE_SUB(NOW(),INTERVAL 20 DAY), DATE_SUB(NOW(),INTERVAL 20 DAY) + INTERVAL 38 MINUTE, 38, 'completed', 1),
(8,  2, DATE_SUB(NOW(),INTERVAL 20 DAY), DATE_SUB(NOW(),INTERVAL 20 DAY) + INTERVAL 44 MINUTE, 44, 'completed', 1),
(9,  2, DATE_SUB(NOW(),INTERVAL 20 DAY), DATE_SUB(NOW(),INTERVAL 20 DAY) + INTERVAL 35 MINUTE, 35, 'completed', 1),

-- Exam 3 – Chemistry (students 11-15)
(11, 3, DATE_SUB(NOW(),INTERVAL 10 DAY), DATE_SUB(NOW(),INTERVAL 10 DAY) + INTERVAL 35 MINUTE, 35, 'completed', 1),
(12, 3, DATE_SUB(NOW(),INTERVAL 10 DAY), DATE_SUB(NOW(),INTERVAL 10 DAY) + INTERVAL 38 MINUTE, 38, 'completed', 1),
(13, 3, DATE_SUB(NOW(),INTERVAL 10 DAY), DATE_SUB(NOW(),INTERVAL 10 DAY) + INTERVAL 30 MINUTE, 30, 'completed', 1),
(14, 3, DATE_SUB(NOW(),INTERVAL 10 DAY), DATE_SUB(NOW(),INTERVAL 10 DAY) + INTERVAL 40 MINUTE, 40, 'completed', 1),
(15, 3, DATE_SUB(NOW(),INTERVAL 10 DAY), DATE_SUB(NOW(),INTERVAL 10 DAY) + INTERVAL 25 MINUTE, 25, 'completed', 1),

-- Exam 4 – CS (students 6-10)
(6,  4, DATE_SUB(NOW(),INTERVAL 5 DAY), DATE_SUB(NOW(),INTERVAL 5 DAY) + INTERVAL 55 MINUTE, 55, 'completed', 1),
(7,  4, DATE_SUB(NOW(),INTERVAL 5 DAY), DATE_SUB(NOW(),INTERVAL 5 DAY) + INTERVAL 58 MINUTE, 58, 'completed', 1),
(8,  4, DATE_SUB(NOW(),INTERVAL 5 DAY), DATE_SUB(NOW(),INTERVAL 5 DAY) + INTERVAL 50 MINUTE, 50, 'completed', 1),
(9,  4, DATE_SUB(NOW(),INTERVAL 5 DAY), DATE_SUB(NOW(),INTERVAL 5 DAY) + INTERVAL 60 MINUTE, 60, 'completed', 1),
(10, 4, DATE_SUB(NOW(),INTERVAL 5 DAY), DATE_SUB(NOW(),INTERVAL 5 DAY) + INTERVAL 45 MINUTE, 45, 'completed', 1),

-- Exam 5 – English (students 11-13)
(11, 5, DATE_SUB(NOW(),INTERVAL 2 DAY), DATE_SUB(NOW(),INTERVAL 2 DAY) + INTERVAL 20 MINUTE, 20, 'completed', 1),
(12, 5, DATE_SUB(NOW(),INTERVAL 2 DAY), DATE_SUB(NOW(),INTERVAL 2 DAY) + INTERVAL 22 MINUTE, 22, 'completed', 1),
(13, 5, DATE_SUB(NOW(),INTERVAL 2 DAY), DATE_SUB(NOW(),INTERVAL 2 DAY) + INTERVAL 18 MINUTE, 18, 'completed', 1);

-- ============================================================
-- STUDENT_ANSWERS (for all 22 completed attempts above)
-- student_exam_id 1-5 = Exam 1 (questions 1-5, each 2 marks)
-- ============================================================
-- Attempt 1 (student6, exam1): 4/5 correct → 8/10
INSERT INTO student_answers (student_exam_id,question_id,selected_answer,is_correct,marks_obtained) VALUES
(1,1,'A',1,2),(1,2,'B',1,2),(1,3,'B',1,2),(1,4,'A',1,2),(1,5,'B',0,0);

-- Attempt 2 (student7, exam1): 5/5 correct → 10/10
INSERT INTO student_answers (student_exam_id,question_id,selected_answer,is_correct,marks_obtained) VALUES
(2,1,'A',1,2),(2,2,'B',1,2),(2,3,'B',1,2),(2,4,'A',1,2),(2,5,'C',1,2);

-- Attempt 3 (student8, exam1): 3/5 correct → 6/10
INSERT INTO student_answers (student_exam_id,question_id,selected_answer,is_correct,marks_obtained) VALUES
(3,1,'A',1,2),(3,2,'A',0,0),(3,3,'B',1,2),(3,4,'B',0,0),(3,5,'C',1,2);

-- Attempt 4 (student9, exam1): 2/5 correct → 4/10
INSERT INTO student_answers (student_exam_id,question_id,selected_answer,is_correct,marks_obtained) VALUES
(4,1,'B',0,0),(4,2,'A',0,0),(4,3,'B',1,2),(4,4,'A',1,2),(4,5,'A',0,0);

-- Attempt 5 (student10, exam1): 5/5 correct → 10/10
INSERT INTO student_answers (student_exam_id,question_id,selected_answer,is_correct,marks_obtained) VALUES
(5,1,'A',1,2),(5,2,'B',1,2),(5,3,'B',1,2),(5,4,'A',1,2),(5,5,'C',1,2);

-- Exam 2 attempts (se_id 6-9, questions 8-12, each 2 marks)
-- Attempt 6 (student6, exam2): 5/5 → 10
INSERT INTO student_answers (student_exam_id,question_id,selected_answer,is_correct,marks_obtained) VALUES
(6,8,'C',1,2),(6,9,'A',1,2),(6,10,'B',1,2),(6,11,'B',1,2),(6,12,'C',1,2);

-- Attempt 7 (student7, exam2): 4/5 → 8
INSERT INTO student_answers (student_exam_id,question_id,selected_answer,is_correct,marks_obtained) VALUES
(7,8,'C',1,2),(7,9,'A',1,2),(7,10,'B',1,2),(7,11,'A',0,0),(7,12,'C',1,2);

-- Attempt 8 (student8, exam2): 3/5 → 6
INSERT INTO student_answers (student_exam_id,question_id,selected_answer,is_correct,marks_obtained) VALUES
(8,8,'C',1,2),(8,9,'B',0,0),(8,10,'B',1,2),(8,11,'B',1,2),(8,12,'A',0,0);

-- Attempt 9 (student9, exam2): 2/5 → 4
INSERT INTO student_answers (student_exam_id,question_id,selected_answer,is_correct,marks_obtained) VALUES
(9,8,'A',0,0),(9,9,'A',1,2),(9,10,'A',0,0),(9,11,'B',1,2),(9,12,'B',0,0);

-- Exam 3 attempts (se_id 10-14, questions 15-19, each 2 marks)
-- Attempt 10 (student11, exam3): 4/5 → 8
INSERT INTO student_answers (student_exam_id,question_id,selected_answer,is_correct,marks_obtained) VALUES
(10,15,'B',1,2),(10,16,'A',1,2),(10,17,'C',1,2),(10,18,'C',1,2),(10,19,'B',0,0);

-- Attempt 11 (student12, exam3): 5/5 → 10
INSERT INTO student_answers (student_exam_id,question_id,selected_answer,is_correct,marks_obtained) VALUES
(11,15,'B',1,2),(11,16,'A',1,2),(11,17,'C',1,2),(11,18,'C',1,2),(11,19,'A',1,2);

-- Attempt 12 (student13, exam3): 3/5 → 6
INSERT INTO student_answers (student_exam_id,question_id,selected_answer,is_correct,marks_obtained) VALUES
(12,15,'A',0,0),(12,16,'A',1,2),(12,17,'C',1,2),(12,18,'B',0,0),(12,19,'A',1,2);

-- Attempt 13 (student14, exam3): 2/5 → 4
INSERT INTO student_answers (student_exam_id,question_id,selected_answer,is_correct,marks_obtained) VALUES
(13,15,'B',1,2),(13,16,'B',0,0),(13,17,'A',0,0),(13,18,'C',1,2),(13,19,'B',0,0);

-- Attempt 14 (student15, exam3): 4/5 → 8
INSERT INTO student_answers (student_exam_id,question_id,selected_answer,is_correct,marks_obtained) VALUES
(14,15,'B',1,2),(14,16,'A',1,2),(14,17,'A',0,0),(14,18,'C',1,2),(14,19,'A',1,2);

-- Exam 4 attempts (se_id 15-19, questions 21-27, each 2 marks)
-- Attempt 15 (student6): 5/7 → 10
INSERT INTO student_answers (student_exam_id,question_id,selected_answer,is_correct,marks_obtained) VALUES
(15,21,'B',1,2),(15,22,'C',1,2),(15,23,'A',1,2),(15,24,'C',1,2),(15,25,'B',1,2),(15,26,'B',1,2),(15,27,'A',0,0);

-- Attempt 16 (student7): 7/7 → 14
INSERT INTO student_answers (student_exam_id,question_id,selected_answer,is_correct,marks_obtained) VALUES
(16,21,'B',1,2),(16,22,'C',1,2),(16,23,'A',1,2),(16,24,'C',1,2),(16,25,'B',1,2),(16,26,'B',1,2),(16,27,'C',1,2);

-- Attempt 17 (student8): 4/7 → 8
INSERT INTO student_answers (student_exam_id,question_id,selected_answer,is_correct,marks_obtained) VALUES
(17,21,'B',1,2),(17,22,'A',0,0),(17,23,'A',1,2),(17,24,'A',0,0),(17,25,'B',1,2),(17,26,'B',1,2),(17,27,'B',0,0);

-- Attempt 18 (student9): 3/7 → 6
INSERT INTO student_answers (student_exam_id,question_id,selected_answer,is_correct,marks_obtained) VALUES
(18,21,'A',0,0),(18,22,'C',1,2),(18,23,'B',0,0),(18,24,'C',1,2),(18,25,'A',0,0),(18,26,'B',1,2),(18,27,'A',0,0);

-- Attempt 19 (student10): 6/7 → 12
INSERT INTO student_answers (student_exam_id,question_id,selected_answer,is_correct,marks_obtained) VALUES
(19,21,'B',1,2),(19,22,'C',1,2),(19,23,'A',1,2),(19,24,'C',1,2),(19,25,'B',1,2),(19,26,'A',0,0),(19,27,'C',1,2);

-- Exam 5 (se_id 20-22, questions 28-30, each 2 marks, total 6)
-- Attempt 20 (student11): 3/3 → 6
INSERT INTO student_answers (student_exam_id,question_id,selected_answer,is_correct,marks_obtained) VALUES
(20,28,'B',1,2),(20,29,'A',1,2),(20,30,'B',1,2);

-- Attempt 21 (student12): 2/3 → 4
INSERT INTO student_answers (student_exam_id,question_id,selected_answer,is_correct,marks_obtained) VALUES
(21,28,'B',1,2),(21,29,'A',1,2),(21,30,'A',0,0);

-- Attempt 22 (student13): 1/3 → 2
INSERT INTO student_answers (student_exam_id,question_id,selected_answer,is_correct,marks_obtained) VALUES
(22,28,'A',0,0),(22,29,'A',1,2),(22,30,'A',0,0);

-- ============================================================
-- RESULTS – calculated manually to match answers above
-- (The after_student_exam_complete trigger would auto-populate
--  these in a live run; we insert them directly for the seed.)
-- ============================================================
-- total_marks per exam: 1→10, 2→10, 3→10, 4→14, 5→6
-- passing_marks:        1→5,  2→5,  3→5,  4→7,  5→3
INSERT INTO results
    (student_exam_id, student_id, exam_id, total_marks, obtained_marks, percentage, grade, is_passed)
VALUES
-- Exam 1 results
(1,  6,  1, 10,  8, 80.00, 'B+', 1),
(2,  7,  1, 10, 10,100.00, 'A+', 1),
(3,  8,  1, 10,  6, 60.00, 'B',  1),
(4,  9,  1, 10,  4, 40.00, 'F',  0),
(5, 10,  1, 10, 10,100.00, 'A+', 1),
-- Exam 2 results
(6,  6,  2, 10, 10,100.00, 'A+', 1),
(7,  7,  2, 10,  8, 80.00, 'B+', 1),
(8,  8,  2, 10,  6, 60.00, 'B',  1),
(9,  9,  2, 10,  4, 40.00, 'F',  0),
-- Exam 3 results
(10, 11, 3, 10,  8, 80.00, 'B+', 1),
(11, 12, 3, 10, 10,100.00, 'A+', 1),
(12, 13, 3, 10,  6, 60.00, 'B',  1),
(13, 14, 3, 10,  4, 40.00, 'F',  0),
(14, 15, 3, 10,  8, 80.00, 'B+', 1),
-- Exam 4 results
(15,  6, 4, 14, 10, 71.43, 'B+', 1),
(16,  7, 4, 14, 14,100.00, 'A+', 1),
(17,  8, 4, 14,  8, 57.14, 'C',  1),
(18,  9, 4, 14,  6, 42.86, 'F',  0),
(19, 10, 4, 14, 12, 85.71, 'A',  1),
-- Exam 5 results
(20, 11, 5,  6,  6,100.00, 'A+', 1),
(21, 12, 5,  6,  4, 66.67, 'B',  1),
(22, 13, 5,  6,  2, 33.33, 'F',  0);
