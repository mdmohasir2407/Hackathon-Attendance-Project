CREATE DATABASE IF NOT EXISTS smart_education;
USE smart_education;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin', 'teacher', 'student') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE admins (
    id INT PRIMARY KEY,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    FOREIGN KEY (id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE departments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    code VARCHAR(20) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE teachers (
    id INT PRIMARY KEY,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    department_id INT,
    phone VARCHAR(20),
    FOREIGN KEY (id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL
);

CREATE TABLE academic_years (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(20) NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    is_current BOOLEAN DEFAULT FALSE
);

CREATE TABLE semesters (
    id INT AUTO_INCREMENT PRIMARY KEY,
    academic_year_id INT,
    name VARCHAR(20) NOT NULL,
    start_date DATE,
    end_date DATE,
    FOREIGN KEY (academic_year_id) REFERENCES academic_years(id) ON DELETE CASCADE
);

CREATE TABLE classes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    department_id INT NOT NULL,
    semester_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE CASCADE,
    FOREIGN KEY (semester_id) REFERENCES semesters(id) ON DELETE CASCADE
);

CREATE TABLE students (
    id INT PRIMARY KEY,
    roll_number VARCHAR(50) NOT NULL UNIQUE,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    FOREIGN KEY (id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE enrollments (
    student_id INT,
    class_id INT,
    enrollment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (student_id, class_id),
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE
);

CREATE TABLE subjects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(20) NOT NULL UNIQUE,
    name VARCHAR(150) NOT NULL,
    department_id INT,
    semester_id INT,
    credits INT DEFAULT 3,
    status BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL,
    FOREIGN KEY (semester_id) REFERENCES semesters(id) ON DELETE SET NULL
);

CREATE TABLE teacher_subjects (
    teacher_id INT,
    subject_id INT,
    class_id INT,
    PRIMARY KEY (teacher_id, subject_id, class_id),
    FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE CASCADE,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
    FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE
);

CREATE TABLE timetable (
    id INT AUTO_INCREMENT PRIMARY KEY,
    day ENUM('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday') NOT NULL,
    period_number INT NOT NULL, -- 1 to 7
    subject_id INT,
    teacher_id INT,
    class_id INT NOT NULL,
    classroom VARCHAR(50),
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    status BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE SET NULL,
    FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE SET NULL,
    FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE,
    UNIQUE(day, period_number, class_id)
);

CREATE TABLE attendance_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    teacher_id INT NOT NULL,
    class_id INT NOT NULL,
    subject_id INT NOT NULL,
    date DATE NOT NULL,
    period INT NOT NULL,
    token VARCHAR(255) NOT NULL UNIQUE,
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE CASCADE,
    FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE
);

CREATE TABLE attendance (
    session_id INT NOT NULL,
    student_id INT NOT NULL,
    status ENUM('Present', 'Absent', 'Late') NOT NULL,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (session_id, student_id),
    FOREIGN KEY (session_id) REFERENCES attendance_sessions(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
);

CREATE TABLE assignments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    teacher_id INT NOT NULL,
    subject_id INT NOT NULL,
    class_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    deadline DATETIME NOT NULL,
    file_path VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE CASCADE,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
    FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE
);

CREATE TABLE assignment_submissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    assignment_id INT NOT NULL,
    student_id INT NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    submission_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('SUBMITTED', 'LATE') NOT NULL,
    FOREIGN KEY (assignment_id) REFERENCES assignments(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    UNIQUE(assignment_id, student_id)
);

CREATE TABLE study_materials (
    id INT AUTO_INCREMENT PRIMARY KEY,
    teacher_id INT NOT NULL,
    subject_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    unit VARCHAR(100),
    file_path VARCHAR(255) NOT NULL,
    file_type VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE CASCADE,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE
);

CREATE TABLE announcements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    category ENUM('General', 'Assignment', 'Exam', 'Attendance', 'Emergency') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    type VARCHAR(50) NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE feedback (
    id INT AUTO_INCREMENT PRIMARY KEY,
    teacher_id INT NOT NULL,
    student_id INT NOT NULL,
    subject_id INT NOT NULL,
    feedback_type ENUM('Excellent', 'Improving', 'Needs Practice') NOT NULL,
    note VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE
);

CREATE TABLE activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    action VARCHAR(255) NOT NULL,
    ip_address VARCHAR(45),
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE student_achievements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    achievement_name VARCHAR(100) NOT NULL,
    xp_points INT NOT NULL,
    awarded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
);

-- Default passwords are 'password123'
INSERT INTO users (email, password_hash, role) VALUES 
('admin@smartedu.com', '$2y$10$C82H.V/1mS8F.vQf0sQy2OjO/mXNqT7P8KXY.zZg.s3F6pG9aG6/m', 'admin'),
('teacher@smartedu.com', '$2y$10$C82H.V/1mS8F.vQf0sQy2OjO/mXNqT7P8KXY.zZg.s3F6pG9aG6/m', 'teacher'),
('student@smartedu.com', '$2y$10$C82H.V/1mS8F.vQf0sQy2OjO/mXNqT7P8KXY.zZg.s3F6pG9aG6/m', 'student');

INSERT INTO admins (id, first_name, last_name, phone) VALUES 
(1, 'System', 'Admin', '1234567890');

INSERT INTO departments (name, code) VALUES 
('Computer Applications', 'MCA');

INSERT INTO teachers (id, first_name, last_name, department_id, phone) VALUES 
(2, 'Jane', 'Doe', 1, '0987654321');

INSERT INTO academic_years (name, start_date, end_date, is_current) VALUES 
('2026-2027', '2026-08-01', '2027-05-31', TRUE);

INSERT INTO semesters (academic_year_id, name, start_date, end_date) VALUES 
(1, 'Semester I', '2026-08-01', '2026-12-15'),
(1, 'Semester III', '2026-08-01', '2026-12-15');

INSERT INTO classes (department_id, semester_id, name) VALUES 
(1, 2, 'MCA-A');

INSERT INTO students (id, roll_number, first_name, last_name, phone) VALUES 
(3, 'MCA26001', 'John', 'Smith', '1122334455');

INSERT INTO enrollments (student_id, class_id) VALUES 
(3, 1);

INSERT INTO subjects (code, name, department_id, semester_id, credits) VALUES 
('MCA301', 'Python', 1, 2, 3),
('MCA302', 'DBMS', 1, 2, 4),
('MCA303', 'Java', 1, 2, 4),
('MCA304', 'Cloud Computing', 1, 2, 3),
('MCA305', 'Web Technology', 1, 2, 3);

INSERT INTO teacher_subjects (teacher_id, subject_id, class_id) VALUES 
(2, 2, 1); -- Jane Doe teaches DBMS to MCA-A

-- Populate some timetable data for today (Wednesday for instance)
-- Assuming today is a weekday.
INSERT INTO timetable (day, period_number, subject_id, teacher_id, class_id, classroom, start_time, end_time) VALUES
('Monday', 1, 2, 2, 1, 'Room 101', '09:00:00', '09:50:00'),
('Monday', 2, 3, NULL, 1, 'Room 101', '09:50:00', '10:40:00'),
('Monday', 3, 1, NULL, 1, 'Room 101', '10:50:00', '11:40:00'),
('Tuesday', 1, 2, 2, 1, 'Room 101', '09:00:00', '09:50:00');

