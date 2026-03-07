# Online Examination Management System

## Project Description
This project is an Online Examination Management System designed to enable easy management of online exams, candidate registrations, and results. The system provides a user-friendly interface for both candidates and instructors to interact with the exam process efficiently.

## Technology Stack
- **PHP**: Versions 7.4/8.x
- **MySQL**: Version 8.0
- **Bootstrap**: Version 5
- **Docker**: For containerized deployment

## Features
- User roles for admin, instructors, and students
- Robust examination and question management
- Result generation and analytics
- Secure access management and data integrity

## Installation & Setup
Choose one of the following options for installation:

### Option 1: Docker
#### Prerequisites
- Docker installed on your machine.
- Basic knowledge of Docker commands.

#### Steps to Setup Docker
1. Clone the repository:
   ```bash
   git clone https://github.com/hr628/Online-Examination-Management-System.git
   cd Online-Examination-Management-System
   ```
2. Build the Docker containers:
   ```bash
   docker-compose up -d
   ```
3. Access the application at `http://localhost:8080`
4. Access phpMyAdmin at `http://localhost:8081`

### Option 2: XAMPP/WAMP
#### Prerequisites
- XAMPP or WAMP installed.
- Basic knowledge of XAMPP/WAMP setup.

#### Steps to Setup XAMPP/WAMP
1. Clone the repository:
   ```bash
   git clone https://github.com/hr628/Online-Examination-Management-System.git
   ```
2. Move the cloned repository to the `htdocs` folder of XAMPP:
   ```bash
   mv Online-Examination-Management-System /path/to/xampp/htdocs/
   ```
3. Start XAMPP Control Panel and run the following services:
   - Apache
   - MySQL
4. Open `phpMyAdmin` in your browser at `http://localhost/phpmyadmin`
5. Import the SQL files in the following order:
   - `01_schema.sql`
   - `02_triggers.sql`
   - `03_procedures.sql`
   - `04_views.sql`
   - `05_sample_data.sql`
6. Configure database credentials:
   Edit the `config/database.php` file to set your database credentials.
7. Access the application at `http://localhost/Online-Examination-Management-System`

## Access URLs
- **Docker**: 
  - Application: `http://localhost:8080`
  - phpMyAdmin: `http://localhost:8081`
- **XAMPP**: 
  - Application: `http://localhost/Online-Examination-Management-System`

## Default Login Credentials
| Role      | Username  | Password |
|-----------|-----------|----------|
| Admin     | admin     | admin    |
| Instructor | instructor | instructor |
| Student   | student   | student  |

## Database Design Overview
The database consists of 10 tables, including users, exams, questions, results, etc.

## SQL Concepts Demonstrated
- Normalization
- Joins and relationships between tables
- Triggers and stored procedures

## Security Features
- Hashing of passwords
- Role-based access control
- Validation and sanitization of input data

## Engineering Proficiency Alignment
- EP1: Problem identification and solving
- EP2: Development of software solutions
- EP4: Testing and validation

## Project Structure
- `src/`: Source code
- `config/`: Configuration files
- `docs/`: Documentation

## Troubleshooting Tips
- Ensure all services are running in XAMPP.
- Verify that database credentials are correct in `config/database.php`.
- Check Docker logs if containers fail to start.

---

For any inquiries or issues, feel free to open an issue in the repository!