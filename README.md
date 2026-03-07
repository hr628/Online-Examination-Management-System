# Online Examination Management System — DBMS Lab Project

## Project Description
The Online Examination Management System (OEMS) is a role-based web application designed to facilitate online examinations for educational institutions. The system supports three distinct user roles: Admin, Instructor, and Student. Each role provides specific functionalities tailored to their needs, ensuring a comprehensive user experience.

## Technology Stack
| Technology       | Version       |
|------------------|---------------|
| HTML5            | Latest        |
| CSS3             | Latest        |
| Bootstrap 5.3    | Latest        |
| JavaScript       | Latest        |
| PHP              | 8.x           |
| MySQL            | 8.x           |
| Apache           | Latest        |
| Docker           | Latest        |

## Features
### Admin Features
- User management (add, edit, delete users)
- Role assignment (Admin, Instructor, Student)
- View and manage all examinations

### Instructor Features
- Create and manage question banks
- Schedule and oversee exams
- Evaluate student performance and provide feedback

### Student Features
- Register for courses and examinations
- Attempt exams online
- View results and performance analytics

## Database Design
The system consists of 10 tables:
1. **users** - Stores user information and roles.
2. **categories** - Defines question categories.
3. **question_banks** - Contains questions grouped by categories.
4. **questions** - Lists actual questions for the exams.
5. **exams** - Details about scheduled exams.
6. **exam_questions** - Links questions to exams.
7. **student_exams** - Records which students are taking which exams.
8. **student_answers** - Stores answers provided by students during exams.
9. **results** - Records student performance metrics.
10. **audit_log** - Maintains action logs for accountability.

**Constraints:** Unique, foreign keys, checks, etc. are enforced as needed.

## SQL Concepts Demonstrated
The project includes the following SQL files covering various concepts:
1. `01_schema.sql` - Define database schema.
2. `02_insert_data.sql` - Insert initial data.
3. `03_question_management.sql` - Manage questions.
4. `04_exam_management.sql` - Handle exam creation.
5. `05_results_management.sql` - Manage results.
6. `06_complex_queries.sql` - Complex SQL queries for analytics.

## Project Structure Tree
```
Online-Examination-Management-System/
├── Docker/
├── Docs/
├── src/
│   ├── Admin/
│   ├── Instructor/
│   └── Student/
└── SQL/
    ├── 01_schema.sql
    ├── 02_insert_data.sql
    ├── 03_question_management.sql
    ├── 04_exam_management.sql
    ├── 05_results_management.sql
    └── 06_complex_queries.sql
```

## Installation Setup
### Docker
1. Clone the repository.
2. Navigate to the Docker directory.
3. Build and run Docker containers.

### XAMPP/WAMP
1. Install XAMPP/WAMP.
2. Create a new database in phpMyAdmin.
3. Import SQL files in this order:
   - `01_schema.sql`
   - `02_insert_data.sql`
   - `03_question_management.sql`
   - `04_exam_management.sql`
   - `05_results_management.sql`
   - `06_complex_queries.sql`
4. Access the application in your browser.

## Default Login Credentials
| Role          | Username       | Password      |
|---------------|----------------|---------------|
| Admin         | admin          | Admin@123    |
| Instructor 1  | instructor1    | Pass@1234    |
| Student 1     | student1       | Pass@1234    |

## ER Diagram
![ER Diagram](link_to_your_diagram)  
*(Insert your ER diagram here with cardinalities.)*

## Security Features Checklist
- User authentication and role-based access control.
- Data validation and sanitization practices.
- SQL injection prevention measures implemented.

## Engineering Proficiency Alignment
- EP1: Basic Programming
- EP2: System Design
- EP4: Advanced Database Management

## Troubleshooting
- Ensure Apache and MySQL are running.
- Check port configurations if running on Docker.
- Consult the installation setup if facing issues.