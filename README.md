# Online Examination Management System — DBMS Lab Project

A complete, full-stack **Online Examination Management System (OEMS)** built as a DBMS Lab project, demonstrating advanced MySQL 8.x features alongside a PHP 8 + Bootstrap 5 web application.

---

## 📋 Table of Contents
- [Project Description](#project-description)
- [Technology Stack](#technology-stack)
- [Features](#features)
- [Database Design](#database-design)
- [SQL Concepts Demonstrated](#sql-concepts-demonstrated)
- [Project Structure](#project-structure)
- [Installation & Setup](#installation--setup)
- [Default Login Credentials](#default-login-credentials)
- [ER Diagram Description](#er-diagram-description)
- [Security](#security)

---

## Project Description

OEMS is a role-based web application that allows:
- **Administrators** to manage users, categories, exams, and view reports.
- **Instructors** to create question banks, author questions, build and activate exams, and monitor student results.
- **Students** to view and take available exams, receive instant graded results, and review their exam history.

---

## Technology Stack

| Layer      | Technology                     |
|------------|-------------------------------|
| Frontend   | HTML5, CSS3, Bootstrap 5.3    |
| Icons      | Bootstrap Icons 1.11          |
| JavaScript | Vanilla ES6+                  |
| Backend    | PHP 8.x (OOP-style, no framework) |
| Database   | MySQL 8.x                     |
| Server     | Apache (XAMPP / WAMP / LAMP)  |

---

## Features

### Admin
- Dashboard with system-wide statistics
- User management (CRUD, activate/deactivate, role assignment)
- Exam management (status control, delete)
- Question management (filter, toggle, delete)
- Category & question bank management
- Reports: student performance, exam statistics, question usage (uses DB views)

### Instructor
- Dashboard with personal statistics
- Create / edit / delete questions (MCQ and True/False)
- Create exams with date/time scheduling
- Assign random questions from question banks via stored procedure
- View per-exam student results with analytics

### Student
- Dashboard with personal stats and quick links
- Browse and filter available exams
- Timed exam-taking interface with auto-submit on expiry
- Instant result with answer breakdown and explanations
- Full exam history with grade and pass/fail status

---

## Database Design

### Tables

| Table            | Purpose                                              |
|------------------|------------------------------------------------------|
| `users`          | All system users (admin / instructor / student)      |
| `categories`     | Subject categories (Math, Physics, etc.)             |
| `question_banks` | Grouped sets of questions per category               |
| `questions`      | Individual MCQ / True-False questions                |
| `exams`          | Exam definitions with scheduling & marking details   |
| `exam_questions` | Many-to-many: questions assigned to exams            |
| `student_exams`  | Each student's exam attempt session                  |
| `student_answers`| Answers submitted per attempt                        |
| `results`        | Computed grade and pass/fail per completed attempt   |
| `audit_log`      | Immutable audit trail of user actions                |

### Constraints Applied
- PRIMARY KEY on every table
- FOREIGN KEY with appropriate ON DELETE actions
- CHECK constraints (e.g., `end_time > start_time`, `passing_marks <= total_marks`)
- UNIQUE constraints (username, email, exam_question pairs)
- NOT NULL on all mandatory columns

---

## SQL Concepts Demonstrated

| File                    | Concepts                                                              |
|-------------------------|-----------------------------------------------------------------------|
| `01_schema.sql`         | DDL, CREATE TABLE, constraints, indexes                               |
| `02_triggers.sql`       | BEFORE/AFTER triggers, SIGNAL SQLSTATE, conditional logic            |
| `03_procedures.sql`     | Stored procedures, IN/OUT params, transactions, CALL                 |
| `04_views.sql`          | Views with aggregation, JOINs, CASE expressions, GROUP_CONCAT        |
| `05_sample_data.sql`    | DML, bulk INSERT, referential integrity                               |
| `06_complex_queries.sql`| SELECT, WHERE, ORDER BY, LIMIT, Aggregates, INNER/LEFT/RIGHT JOIN,   |
|                         | Subqueries, Correlated subqueries, UNION, EXISTS/NOT EXISTS,         |
|                         | Window Functions (DENSE_RANK, running AVG), CASE, Date functions     |

---

## Project Structure

```
/
├── index.php                  # Login / landing page
├── register.php               # Self-registration
├── logout.php                 # Session destroy + redirect
│
├── config/
│   └── database.php           # DB credentials & connection factory
│
├── sql/
│   ├── 01_schema.sql          # Database & table definitions
│   ├── 02_triggers.sql        # 6 business-logic triggers
│   ├── 03_procedures.sql      # 5 stored procedures
│   ├── 04_views.sql           # 4 reporting views
│   ├── 05_sample_data.sql     # Seed data (20 users, 32 questions, 8 exams)
│   └── 06_complex_queries.sql # 12 annotated complex queries
│
├── admin/
│   ├── dashboard.php
│   ├── manage_users.php
│   ├── manage_exams.php
│   ├── manage_questions.php
│   ├── manage_categories.php
│   └── reports.php
│
├── instructor/
│   ├── dashboard.php
│   ├── create_question.php
│   ├── view_questions.php
│   ├── create_exam.php
│   ├── view_exams.php
│   └── student_results.php
│
├── student/
│   ├── dashboard.php
│   ├── available_exams.php
│   ├── take_exam.php
│   ├── submit_exam.php
│   ├── view_results.php
│   └── exam_history.php
│
├── includes/
│   ├── header.php
│   ├── footer.php
│   ├── navbar.php
│   └── functions.php
│
├── assets/
│   ├── css/style.css
│   ├── js/main.js
│   └── js/exam_timer.js
│
└── docs/                      # (placeholder for additional docs)
```

---

## Installation & Setup

### Prerequisites
- **XAMPP** (Windows/macOS/Linux) or **WAMP** / **LAMP**
- PHP 8.0 or higher
- MySQL 8.0 or higher
- Web browser

### Steps

1. **Clone / Copy the project**
   ```bash
   git clone <repo-url>
   # or copy the folder to:
   # Windows: C:\xampp\htdocs\oems
   # Linux:   /var/www/html/oems
   ```

2. **Start services**
   - Open XAMPP Control Panel
   - Start **Apache** and **MySQL**

3. **Import the database** (in order)

   **Option A – phpMyAdmin:**
   - Open `http://localhost/phpmyadmin`
   - Click **Import** and import each file in order:
     1. `sql/01_schema.sql`
     2. `sql/02_triggers.sql`
     3. `sql/03_procedures.sql`
     4. `sql/04_views.sql`
     5. `sql/05_sample_data.sql`

   **Option B – MySQL CLI:**
   ```bash
   mysql -u root -p < sql/01_schema.sql
   mysql -u root -p oems_db < sql/02_triggers.sql
   mysql -u root -p oems_db < sql/03_procedures.sql
   mysql -u root -p oems_db < sql/04_views.sql
   mysql -u root -p oems_db < sql/05_sample_data.sql
   ```

4. **Configure database credentials** (if needed)
   - Edit `config/database.php`
   - Update `DB_USER` and `DB_PASS` to match your MySQL setup

5. **Access the application**
   ```
   http://localhost/oems/
   ```

---

## Default Login Credentials

| Role       | Username     | Password    |
|------------|-------------|-------------|
| Admin      | `admin`     | `Admin@123` |
| Instructor | `instructor1`| `Pass@1234` |
| Student    | `student1`  | `Pass@1234` |

> **Note:** All 20 seeded users share the password `Pass@1234` except admin (`Admin@123`).

---

## ER Diagram Description

```
users ──< categories ──< question_banks ──< questions
  │                              │
  │            exams >──────< exam_questions
  │              │
  └── student_exams ──< student_answers
              │
           results
              
audit_log ── users (nullable FK)
```

**Cardinalities:**
- One `user` can create many `categories`, `question_banks`, `questions`, `exams`
- One `category` can have many `question_banks` and `exams`
- One `question_bank` can have many `questions`
- One `exam` can have many `exam_questions` (and vice versa — M:N)
- One `student` (user) can have many `student_exams`
- One `student_exam` has many `student_answers` and exactly one `result`

---

## Security

- ✅ All DB queries use **prepared statements** (MySQLi)
- ✅ Passwords hashed with **`password_hash(PASSWORD_BCRYPT)`**
- ✅ **CSRF tokens** on every form
- ✅ **Session-based authentication** with role checks on every protected page
- ✅ Session ID regenerated on login (prevents session fixation)
- ✅ Input sanitised with `htmlspecialchars` + `strip_tags`
- ✅ Role-based access control (RBAC) — students cannot access admin/instructor routes

---

## License

This project is released for academic/educational purposes as a DBMS Lab submission.
