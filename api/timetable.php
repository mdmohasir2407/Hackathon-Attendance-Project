<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'save') {
    $class_id = $_POST['class_id'];
    $day = $_POST['day'];
    $period_number = $_POST['period_number'];
    
    // Optional fields (if empty, it's a free period)
    $subject_id = !empty($_POST['subject_id']) ? $_POST['subject_id'] : null;
    $teacher_id = !empty($_POST['teacher_id']) ? $_POST['teacher_id'] : null;
    $classroom = trim($_POST['classroom']);
    
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];

    try {
        // Check if teacher is already assigned somewhere else at this time
        if ($teacher_id) {
            $stmt = $pdo->prepare("SELECT c.name as class_name FROM timetable t JOIN classes c ON t.class_id = c.id WHERE t.teacher_id = ? AND t.day = ? AND t.period_number = ? AND t.class_id != ?");
            $stmt->execute([$teacher_id, $day, $period_number, $class_id]);
            if ($conflict = $stmt->fetch()) {
                echo json_encode(['success' => false, 'message' => "Conflict: Teacher is already assigned to {$conflict['class_name']} on $day Period $period_number."]);
                exit;
            }
        }

        // Insert or Update (Upsert)
        $stmt = $pdo->prepare("
            INSERT INTO timetable (class_id, day, period_number, subject_id, teacher_id, classroom, start_time, end_time) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
                subject_id = VALUES(subject_id), 
                teacher_id = VALUES(teacher_id), 
                classroom = VALUES(classroom),
                start_time = VALUES(start_time),
                end_time = VALUES(end_time)
        ");
        $stmt->execute([$class_id, $day, $period_number, $subject_id, $teacher_id, $classroom, $start_time, $end_time]);
        
        // Log activity
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
        $log_stmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action, ip_address) VALUES (?, ?, ?)");
        $log_stmt->execute([$_SESSION['user_id'], "Updated timetable for class $class_id ($day Period $period_number)", $ip]);

        echo json_encode(['success' => true, 'message' => 'Timetable updated.']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid request']);
?>
