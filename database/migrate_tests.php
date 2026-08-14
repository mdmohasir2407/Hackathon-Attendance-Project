<?php
require_once __DIR__ . '/../config/database.php';

try {
    // Create tests table if not existing
    $pdo->exec("CREATE TABLE IF NOT EXISTS tests (
        id INT AUTO_INCREMENT PRIMARY KEY,
        teacher_id INT NOT NULL,
        subject_id INT NOT NULL,
        class_id INT NOT NULL,
        test_period ENUM('Period 1', 'Period 2', 'Period 3') DEFAULT 'Period 1',
        scheduled_date DATE DEFAULT NULL,
        start_time TIME DEFAULT '09:00:00',
        end_time TIME DEFAULT '10:00:00',
        title VARCHAR(255) NOT NULL,
        description TEXT,
        duration_minutes INT DEFAULT 30,
        status ENUM('Scheduled', 'Active', 'Completed') DEFAULT 'Scheduled',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Check existing columns in tests
    $stmt = $pdo->query("DESCRIBE tests");
    $cols = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (!in_array('test_period', $cols)) {
        $pdo->exec("ALTER TABLE tests ADD COLUMN test_period ENUM('Period 1', 'Period 2', 'Period 3') DEFAULT 'Period 1' AFTER class_id");
    }
    if (!in_array('scheduled_date', $cols)) {
        $pdo->exec("ALTER TABLE tests ADD COLUMN scheduled_date DATE DEFAULT NULL AFTER test_period");
    }
    if (!in_array('start_time', $cols)) {
        $pdo->exec("ALTER TABLE tests ADD COLUMN start_time TIME DEFAULT '09:00:00' AFTER scheduled_date");
    }
    if (!in_array('end_time', $cols)) {
        $pdo->exec("ALTER TABLE tests ADD COLUMN end_time TIME DEFAULT '10:00:00' AFTER start_time");
    }
    if (!in_array('status', $cols)) {
        $pdo->exec("ALTER TABLE tests ADD COLUMN status ENUM('Scheduled', 'Active', 'Completed') DEFAULT 'Scheduled' AFTER duration_minutes");
    }

    // Create test_questions if not exists
    $pdo->exec("CREATE TABLE IF NOT EXISTS test_questions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        test_id INT NOT NULL,
        question_text TEXT NOT NULL,
        option_a VARCHAR(255) NOT NULL,
        option_b VARCHAR(255) NOT NULL,
        option_c VARCHAR(255) NOT NULL,
        option_d VARCHAR(255) NOT NULL,
        correct_option ENUM('A', 'B', 'C', 'D') NOT NULL,
        marks INT DEFAULT 1,
        FOREIGN KEY (test_id) REFERENCES tests(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Create test_results if not exists
    $pdo->exec("CREATE TABLE IF NOT EXISTS test_results (
        id INT AUTO_INCREMENT PRIMARY KEY,
        test_id INT NOT NULL,
        student_id INT NOT NULL,
        score INT NOT NULL,
        total_marks INT NOT NULL,
        submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (test_id) REFERENCES tests(id) ON DELETE CASCADE,
        FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
        UNIQUE (test_id, student_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    echo "Migration completed successfully!\n";
} catch (Exception $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
}
