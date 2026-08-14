<?php
require_once __DIR__ . '/../config/database.php';

try {
    // Check teacher (id 2) and subject (id 2 DBMS), class (id 1)
    $teacher_id = 2;
    $subject_id = 2;
    $class_id = 1;

    // Check if tests already exist
    $stmt = $pdo->query("SELECT COUNT(*) FROM tests");
    $count = $stmt->fetchColumn();

    if ($count == 0) {
        // Insert Weekly Test Period 1
        $stmt = $pdo->prepare("INSERT INTO tests (teacher_id, subject_id, class_id, test_period, scheduled_date, start_time, end_time, title, description, duration_minutes, status) VALUES (?, ?, ?, 'Period 1', ?, '09:00:00', '10:00:00', 'DBMS Weekly Test 1 - SQL Queries', 'Questions on SELECT, JOINs, and AGGREGATE functions.', 15, 'Active')");
        $stmt->execute([$teacher_id, $subject_id, $class_id, date('Y-m-d')]);
        $test1_id = $pdo->lastInsertId();

        // Questions for Period 1
        $q_stmt = $pdo->prepare("INSERT INTO test_questions (test_id, question_text, option_a, option_b, option_c, option_d, correct_option, marks) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $q_stmt->execute([$test1_id, 'Which SQL command is used to retrieve data from a database table?', 'INSERT', 'SELECT', 'UPDATE', 'DELETE', 'B', 1]);
        $q_stmt->execute([$test1_id, 'Which keyword is used to sort the result-set in SQL?', 'SORT BY', 'ORDER BY', 'GROUP BY', 'ALIGN BY', 'B', 1]);
        $q_stmt->execute([$test1_id, 'Which clause is used to filter records in a SQL query?', 'WHERE', 'HAVING', 'FILTER', 'CONDITION', 'A', 1]);

        // Insert Weekly Test Period 2 (Scheduled)
        $stmt = $pdo->prepare("INSERT INTO tests (teacher_id, subject_id, class_id, test_period, scheduled_date, start_time, end_time, title, description, duration_minutes, status) VALUES (?, ?, ?, 'Period 2', ?, '11:00:00', '12:00:00', 'DBMS Weekly Test 2 - Normalization', 'Questions on 1NF, 2NF, 3NF, and BCNF concepts.', 20, 'Scheduled')");
        $stmt->execute([$teacher_id, $subject_id, $class_id, date('Y-m-d', strtotime('+2 days'))]);
        $test2_id = $pdo->lastInsertId();

        $q_stmt->execute([$test2_id, 'What is the main objective of database normalization?', 'To increase redundancy', 'To eliminate data redundancy', 'To slow down query speed', 'To format text fields', 'B', 1]);
        $q_stmt->execute([$test2_id, 'A relation is in 1NF if all attributes contain only...', 'Atomic values', 'Composite values', 'Multivalued lists', 'Nested tables', 'A', 1]);

        // Insert Weekly Test Period 3 (Scheduled)
        $stmt = $pdo->prepare("INSERT INTO tests (teacher_id, subject_id, class_id, test_period, scheduled_date, start_time, end_time, title, description, duration_minutes, status) VALUES (?, ?, ?, 'Period 3', ?, '14:00:00', '15:00:00', 'DBMS Weekly Test 3 - Transactions & ACID', 'Questions on Atomicity, Consistency, Isolation, and Durability.', 30, 'Scheduled')");
        $stmt->execute([$teacher_id, $subject_id, $class_id, date('Y-m-d', strtotime('+4 days'))]);
        $test3_id = $pdo->lastInsertId();

        $q_stmt->execute([$test3_id, 'What does the "A" in ACID properties stand for?', 'Accuracy', 'Atomicity', 'Algorithm', 'Availability', 'B', 1]);

        echo "Demo weekly tests seeded successfully!\n";
    } else {
        echo "Tests table already contains data.\n";
    }
} catch (Exception $e) {
    echo "Seeding failed: " . $e->getMessage() . "\n";
}
