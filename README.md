# Online Examination Management System

A comprehensive full-stack web application for managing online examinations built with **PHP**, **MySQL**, and **Bootstrap 5**.

---

## 📋 Table of Contents

- [Features](#features)
- [Technology Stack](#technology-stack)
- [System Requirements](#system-requirements)
- [Installation Guide](#installation-guide)
  - [Method 1: Docker Setup](#method-1-docker-setup-recommended)
  - [Method 2: XAMPP/WAMP Setup](#method-2-xamppwamp-setup)
- [Usage](#usage)
- [Project Structure](#project-structure)
- [Database Schema](#database-schema)
- [API Routes](#api-routes)
- [Demo Credentials](#demo-credentials)
- [SQL Concepts Demonstrated](#sql-concepts-demonstrated)
- [Security Features](#security-features)
- [Troubleshooting](#troubleshooting)
- [Contributing](#contributing)
- [License](#license)

---

## ✨ Features

### 🔐 Authentication & Authorization
- **Role-based access control** (Admin, Instructor, Student)
- Secure password hashing using bcrypt
- Session management with PHP sessions
- CSRF protection for all forms

### 👨‍💼 Admin Features
- **User Management**: Create, update, and manage users (Admins, Instructors, Students)
- **System Monitoring**: View all exams, questions, categories, and question banks
- **Reports & Analytics**: Comprehensive reporting with views for student performance and exam statistics
- **Category Management**: Organize exams and questions into categories
- **Audit Logs**: Track all major system actions

### 👨‍🏫 Instructor Features
- **Exam Creation**: Create exams with customizable settings:
  - Duration
  - Passing marks
  - Time windows (start/end time)
  - Randomize questions
  - Maximum attempts allowed
- **Question Bank**: Create and manage multiple-choice and true/false questions
- **Exam Builder**: Assign questions to exams with custom marks per question
- **Result Analysis**: View student results, exam leaderboards, and statistics
- **Performance Tracking**: Monitor pass rates and exam difficulty

### 👨‍🎓 Student Features
- **Exam Participation**: Take exams during specified time windows
- **Real-time Timer**: Countdown timer with auto-submit on timeout
- **Result Viewing**: Detailed results with correct/incorrect answers and explanations
- **Performance Tracking**: View all past attempts, scores, and exam history
- **Multiple Attempts**: Controlled by exam settings (max attempts limit)

### 🗄️ Database Features
- **Triggers**: 
  - Validate exam dates and marks before insert/update
  - Auto-calculate results when exam is submitted
- **Views**: 
  - Student performance summary
  - Exam statistics
  - Question usage analytics
- **Stored Procedures**:
  - Create exam with validation
  - Calculate result with grading logic
- **Complex Queries**: 12+ examples demonstrating joins, subqueries, aggregations

---

## 🛠️ Technology Stack

### Backend
- **PHP 7.4/8.x** (94.3%) - Server-side scripting
- **MySQL 8.0** - Relational database
- **MySQLi** - Database driver

### Frontend
- **Bootstrap 5** - Responsive UI framework
- **Bootstrap Icons** - Icon library
- **CSS 3** (3.1%) - Custom styling
- **JavaScript** (2.5%) - Interactive features (timer, form validation)

### Deployment
- **Docker & Docker Compose** (0.1%) - Containerization
- **Apache** - Web server

---

## 📦 System Requirements

### For Manual Setup:
- **PHP**: 7.4 or higher
- **MySQL**: 8.0 or higher
- **Apache**: 2.4 or higher (via XAMPP/WAMP)
- **Web Browser**: Modern browser with JavaScript enabled

### For Docker Setup:
- **Docker**: 20.10 or higher
- **Docker Compose**: 1.29 or higher

---

## 🚀 Installation Guide

### Method 1: Docker Setup (Recommended)

Docker setup ensures consistency across different environments and simplifies installation.

#### Step 1: Install Docker

**On Ubuntu:**
```bash
# Install Docker
curl -fsSL https://get.docker.com -o get-docker.sh
sudo sh get-docker.sh

# Install Docker Compose
sudo apt install docker-compose -y

# Add your user to docker group
sudo usermod -aG docker $USER
newgrp docker
```

**On macOS/Windows:**
- Download and install [Docker Desktop](https://www.docker.com/products/docker-desktop/)

#### Step 2: Clone Repository

```bash
git clone https://github.com/hr628/Online-Examination-Management-System.git
cd Online-Examination-Management-System
```

#### Step 3: Start with Docker Compose

```bash
# Build and start all services
docker-compose up -d

# View logs
docker-compose logs -f web

# Stop all services
docker-compose down

# Stop and remove volumes (deletes database data)
docker-compose down -v
```

The application will be available at: **http://localhost:8080**
phpMyAdmin will be available at: **http://localhost:8081**

#### Docker Commands Reference

```bash
# View running containers
docker-compose ps

# Restart services
docker-compose restart

# Rebuild containers
docker-compose up -d --build

# Access MySQL container
docker exec -it oems_db mysql -u root -p

# Access PHP container shell
docker exec -it oems_web bash

# View application logs
docker-compose logs -f web

# View database logs
docker-compose logs -f db
```

---

### Method 2: XAMPP/WAMP Setup

#### Step 1: Install XAMPP/WAMP

**On Windows:**
1. Download XAMPP from [apachefriends.org](https://www.apachefriends.org/)
2. Install XAMPP to `C:\xampp`
3. Start XAMPP Control Panel

**On Ubuntu/Debian:**
```bash
# Download XAMPP Linux installer
wget https://www.apachefriends.org/xampp-files/8.0.28/xampp-linux-x64-8.0.28-0-installer.run

# Make it executable
chmod +x xampp-linux-x64-8.0.28-0-installer.run

# Run installer
sudo ./xampp-linux-x64-8.0.28-0-installer.run
```

**On macOS:**
- Download XAMPP for macOS from [apachefriends.org](https://www.apachefriends.org/)

#### Step 2: Clone Repository

```bash
# Clone the repository
git clone https://github.com/hr628/Online-Examination-Management-System.git

# Move to xampp htdocs folder
# Windows:
move Online-Examination-Management-System C:\xampp\htdocs\

# Linux/Mac:
sudo mv Online-Examination-Management-System /opt/lampp/htdocs/
```

#### Step 3: Start Services

1. Open XAMPP Control Panel
2. Start **Apache**
3. Start **MySQL**

#### Step 4: Import Database

1. Open **phpMyAdmin** at `http://localhost/phpmyadmin`
2. Click **Import** tab
3. Import SQL files in the following order:
   - `sql/01_schema.sql` - Creates database and tables
   - `sql/02_triggers.sql` - Creates triggers
   - `sql/03_procedures.sql` - Creates stored procedures
   - `sql/04_views.sql` - Creates views
   - `sql/05_sample_data.sql` - Inserts demo data (optional)
   - `sql/06_complex_queries.sql` - Example queries (optional, for reference)

#### Step 5: Configure Database Connection

The application reads database credentials from environment variables. For XAMPP/WAMP, the default values work:

- **DB_HOST**: `localhost`
- **DB_USER**: `root`
- **DB_PASSWORD**: `` (empty by default)
- **DB_NAME**: `oems_db`
- **DB_PORT**: `3306`

If you need to change these, edit `config/database.php`:

```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', ''); // your MySQL password
define('DB_NAME', 'oems_db');
define('DB_PORT', 3306);
```

#### Step 6: Access the Application

- Application: **http://localhost/Online-Examination-Management-System**
- phpMyAdmin: **http://localhost/phpmyadmin**

---

## 📖 Usage

### Demo Accounts

After importing the sample data (`05_sample_data.sql`), you can use these demo accounts:

| Role | Username | Password |
|------|----------|----------|
| **Admin** | admin | Admin@123 |
| **Instructor** | instructor1 | Pass@1234 |
| **Instructor** | instructor2 | Pass@1234 |
| **Student** | student1 | Pass@1234 |
| **Student** | student2 | Pass@1234 |
| **Student** | student3 | Pass@1234 |

> **Note**: All passwords in `05_sample_data.sql` are hashed using PHP's `password_hash()` function with bcrypt.

### Admin Workflow
1. Login as admin at `http://localhost:8080/index.php`
2. Navigate to **Users** to create or manage instructors and students
3. Navigate to **Exams** to view all exams in the system
4. Navigate to **Questions** to manage the question bank
5. Navigate to **Categories** to organize subjects
6. Navigate to **Reports** to view analytics

### Instructor Workflow
1. Login as instructor
2. Navigate to **Dashboard** to see overview
3. Go to **Questions** → **Create Question** to add questions to your question bank
4. Go to **Exams** → **Create Exam** to create a new exam:
   - Fill in exam details (title, description, category, marks, duration, time window)
   - Assign questions from your question bank
   - Set exam status to **Active** when ready
5. Go to **Exams** → **My Exams** to manage your exams
6. Go to **Student Results** to view and analyze student performance

### Student Workflow
1. Login as student
2. Navigate to **Dashboard** to see your stats and available exams
3. Go to **Available Exams** to see active exams
4. Click **Start Exam** to begin (respects time windows and max attempts)
5. Complete the exam within the time limit
6. View your results with detailed breakdown of correct/incorrect answers
7. Go to **Exam History** to see all your past attempts

---

## 📁 Project Structure

```
Online-Examination-Management-System/
├── admin/                          # Admin module
│   ├── dashboard.php              # Admin dashboard with stats
│   ├── manage_categories.php     # Category and question bank management
│   ├── manage_exams.php          # Exam oversight
│   ├── manage_questions.php      # Question bank management
│   ├── manage_users.php          # User management (CRUD)
│   └── reports.php               # Reports and analytics
│
├── instructor/                     # Instructor module
│   ├── dashboard.php              # Instructor dashboard
│   ├── create_exam.php            # Exam creation form
│   ├── create_question.php        # Question creation form
│   ├── student_results.php        # View student results
│   ├── view_exams.php             # List instructor's exams
│   └── view_questions.php         # List instructor's questions
│
├── student/                        # Student module
│   ├── dashboard.php              # Student dashboard
│   ├── available_exams.php        # List available exams
│   ├── take_exam.php              # Exam interface with timer
│   ├── submit_exam.php            # Exam submission handler
│   ├── view_results.php           # View exam results
│   └── exam_history.php           # View exam history
│
├── config/
│   └── database.php               # Database connection and configuration
│
├── includes/                       # Shared components
│   ├── header.php                 # HTML head section
│   ├── navbar.php                 # Role-based navigation bar
│   ├── footer.php                 # Footer section
│   └── functions.php              # Helper functions (auth, CSRF, etc.)
│
├── sql/                            # SQL scripts
│   ├── 01_schema.sql              # Database schema (10+ tables)
│   ├── 02_triggers.sql            # Triggers (3 triggers)
│   ├── 03_procedures.sql          # Stored procedures (2 procedures)
│   ├── 04_views.sql               # Views (4 views)
│   ├── 05_sample_data.sql         # Sample/seed data
│   └── 06_complex_queries.sql     # Complex SQL examples
│
├── assets/                         # Static files
│   ├── css/
│   │   └── style.css              # Custom styles
│   └── js/
│       └── main.js                # Custom JavaScript
│
├── Dockerfile                      # Docker configuration for PHP-Apache
├── docker-compose.yml             # Docker Compose orchestration
├── .dockerignore                  # Docker ignore rules
├── index.php                      # Login page
├── register.php                   # User registration
├── logout.php                     # Logout handler
└── README.md                      # This file
```

---

## 🗃️ Database Schema

### Tables (10+ tables)

1. **users** - System users (admin, instructor, student)
2. **categories** - Subject categories
3. **question_banks** - Groups of questions per category
4. **questions** - Individual questions with options
5. **exams** - Exam details and settings
6. **exam_questions** - Links questions to exams
7. **student_exams** - Student exam attempts
8. **student_answers** - Student answers per question
9. **results** - Calculated results (marks, percentage, grade)
10. **audit_log** - System activity log

### Entity Relationship Diagram (ERD)

```
users (1) ----< (many) exams
users (1) ----< (many) questions
categories (1) ----< (many) question_banks
question_banks (1) ----< (many) questions
exams (1) ----< (many) exam_questions
questions (1) ----< (many) exam_questions
exams (1) ----< (many) student_exams
student_exams (1) ----< (many) student_answers
student_exams (1) ---- (1) results
```

### Database Triggers

1. **before_exam_insert** - Validates exam dates and duration before insert
2. **before_exam_update** - Validates exam dates and duration before update
3. **after_student_exam_complete** - Auto-calculates and inserts results when exam is completed

### Database Views

1. **view_student_performance** - Aggregated statistics per student across all exams
2. **view_exam_statistics** - Aggregated statistics per exam (average score, pass rate)
3. **view_question_usage** - Question usage analytics across exams
4. **view_instructor_exams** - Instructor exams with attempt counts

### Stored Procedures

1. **sp_create_exam** - Creates a new exam with validation and audit logging
2. **sp_calculate_result** - Recalculates result for a given student exam attempt

---

## 🛣️ API Routes

### Authentication Routes
- `GET/POST /index.php` - User login
- `GET /logout.php` - User logout
- `GET/POST /register.php` - User registration

### Admin Routes
- `GET /admin/dashboard.php` - Admin dashboard with system stats
- `GET /admin/manage_users.php` - View and manage all users
- `POST /admin/manage_users.php` - Create/update/toggle user
- `GET /admin/manage_exams.php` - View all exams
- `POST /admin/manage_exams.php` - Change exam status or delete
- `GET /admin/manage_questions.php` - View all questions
- `POST /admin/manage_questions.php` - Toggle/delete question
- `GET /admin/manage_categories.php` - View categories and question banks
- `POST /admin/manage_categories.php` - Create/update/delete category or bank
- `GET /admin/reports.php` - View reports and analytics

### Instructor Routes
- `GET /instructor/dashboard.php` - Instructor dashboard
- `GET /instructor/view_exams.php` - List instructor's exams
- `POST /instructor/view_exams.php` - Change exam status or delete
- `GET /instructor/create_exam.php` - Exam creation form
- `POST /instructor/create_exam.php` - Create exam and assign questions
- `GET /instructor/view_questions.php` - List instructor's questions
- `POST /instructor/view_questions.php` - Toggle/delete question
- `GET /instructor/create_question.php` - Question creation form
- `POST /instructor/create_question.php` - Create question
- `GET /instructor/student_results.php` - View student results

### Student Routes
- `GET /student/dashboard.php` - Student dashboard
- `GET /student/available_exams.php` - List available exams
- `GET /student/take_exam.php` - Start or resume exam
- `POST /student/submit_exam.php` - Submit exam
- `GET /student/view_results.php` - View exam result
- `GET /student/exam_history.php` - View all exam attempts

---

## 🔐 Demo Credentials

| Role | Username | Password | Description |
|------|----------|----------|-------------|
| **Admin** | admin | Admin@123 | Full system access |
| **Instructor** | instructor1 | Pass@1234 | Dr. Alice Johnson |
| **Instructor** | instructor2 | Pass@1234 | Prof. Bob Williams |
| **Instructor** | instructor3 | Pass@1234 | Dr. Carol Martinez |
| **Instructor** | instructor4 | Pass@1234 | Prof. David Brown |
| **Student** | student1 | Pass@1234 | Emma Wilson |
| **Student** | student2 | Pass@1234 | James Anderson |
| **Student** | student3 | Pass@1234 | Sophia Taylor |

> **Note**: Change all passwords after first login in production environments.

---

## 📊 SQL Concepts Demonstrated

### 1. **Normalization**
- Database design follows 3NF (Third Normal Form)
- Separate tables for users, exams, questions, answers
- Junction tables for many-to-many relationships

### 2. **Joins**
- **INNER JOIN**: Retrieve student names with exam results
- **LEFT JOIN**: List all exams with attempt counts (0 for no attempts)
- **RIGHT JOIN**: List all questions with assigned exams (NULL if unassigned)

### 3. **Subqueries**
- **Non-correlated**: Find students who scored above average
- **Correlated**: Find instructors with most exams

### 4. **Aggregate Functions**
- `COUNT()`, `SUM()`, `AVG()`, `MIN()`, `MAX()`
- Used with `GROUP BY` and `HAVING` clauses

### 5. **Triggers**
- Business logic enforcement
- Automatic result calculation
- Data validation

### 6. **Views**
- Pre-built reports for dashboards
- Performance optimization
- Data abstraction

### 7. **Stored Procedures**
- Reusable business logic
- Transaction management
- Error handling

### 8. **Complex Queries**
- Window functions (`ROW_NUMBER()`, `RANK()`)
- Common Table Expressions (CTEs)
- CASE statements
- Date/time functions

---

## 🔒 Security Features

1. **Password Hashing**: Uses PHP's `password_hash()` with bcrypt algorithm
2. **CSRF Protection**: All forms include CSRF tokens to prevent cross-site request forgery
3. **SQL Injection Prevention**: Prepared statements with parameterized queries
4. **XSS Protection**: Input sanitization using `htmlspecialchars()` and `strip_tags()`
5. **Session Security**: Secure session management with regeneration
6. **Role-based Access Control**: Middleware checks user permissions
7. **Input Validation**: Server-side validation for all form inputs
8. **Error Handling**: Proper error logging without exposing sensitive information

---

## 🐛 Troubleshooting

### Common Issues

**Issue: Cannot connect to MySQL database**
```bash
# Check if MySQL is running (XAMPP)
# Open XAMPP Control Panel and ensure MySQL is running

# Check MySQL connection
mysql -u root -p

# Verify database exists
SHOW DATABASES LIKE 'oems_db';
```

**Issue: Port 8080 already in use (Docker)**
```bash
# Find process using port 8080
lsof -i :8080  # macOS/Linux
netstat -ano | findstr :8080  # Windows

# Edit docker-compose.yml to use different port
ports:
  - "8081:80"  # Change 8080 to 8081
```

**Issue: Permission denied (Linux/Docker)**
```bash
# Fix file permissions
sudo chown -R $USER:$USER /path/to/project
sudo chmod -R 755 /path/to/project
```

**Issue: Database tables not created**
```bash
# Manually run schema
mysql -u root -p oems_db < sql/01_schema.sql
mysql -u root -p oems_db < sql/02_triggers.sql
mysql -u root -p oems_db < sql/03_procedures.sql
mysql -u root -p oems_db < sql/04_views.sql
mysql -u root -p oems_db < sql/05_sample_data.sql
```

**Issue: PHP errors showing**
```php
// For production, disable error display in php.ini:
display_errors = Off
log_errors = On
error_log = /path/to/error.log
```

**Issue: Session not persisting**
```bash
# Check session save path has write permissions
sudo chmod 777 /var/lib/php/sessions  # Linux
# Or check session.save_path in php.ini
```

---

## 🤝 Contributing

Contributions are welcome! Please follow these steps:

1. **Fork the repository**
2. **Create a feature branch** 
   ```bash
   git checkout -b feature/AmazingFeature
   ```
3. **Commit your changes**
   ```bash
   git commit -m 'Add some AmazingFeature'
   ```
4. **Push to the branch**
   ```bash
   git push origin feature/AmazingFeature
   ```
5. **Open a Pull Request**

### Coding Standards
- Follow PSR-12 coding style for PHP
- Comment complex logic
- Write descriptive commit messages
- Test your changes before submitting

---

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

---

## 📧 Contact

For questions or support, please open an issue on GitHub.

**Project Link**: [https://github.com/hr628/Online-Examination-Management-System](https://github.com/hr628/Online-Examination-Management-System)

---

**Built with ❤️ using PHP, MySQL, and Bootstrap 5**
