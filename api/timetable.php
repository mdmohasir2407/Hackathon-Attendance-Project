<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'save') {
    $class_id = $_POST['class_id'] ?? null;
    $day = $_POST['day'] ?? null;
    $period_number = $_POST['period_number'] ?? null;
    
    if (!$class_id || !$day || !$period_number) {
        echo json_encode(['success' => false, 'message' => 'Missing required fields.']);
        exit;
    }
    
    // Optional fields (if empty, it's a free period)
    $subject_id = !empty($_POST['subject_id']) ? $_POST['subject_id'] : null;
    $teacher_id = !empty($_POST['teacher_id']) ? $_POST['teacher_id'] : null;
    $classroom = isset($_POST['classroom']) ? trim($_POST['classroom']) : null;
    
    $start_time = $_POST['start_time'] ?? null;
    $end_time = $_POST['end_time'] ?? null;

    if (!$start_time || !$end_time) {
        echo json_encode(['success' => false, 'message' => 'Start and end time are required.']);
        exit;
    }

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
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'generate') {
    $class_id = $_POST['class_id'] ?? null;
    
    if (!$class_id) {
        echo json_encode(['success' => false, 'message' => 'Missing class ID.']);
        exit;
    }

    try {
        $pdo->beginTransaction();

        // Get department of the class
        $stmt = $pdo->prepare("SELECT department_id FROM classes WHERE id = ?");
        $stmt->execute([$class_id]);
        $class_info = $stmt->fetch();
        
        if (!$class_info) {
            throw new Exception("Class not found.");
        }
        $dept_id = $class_info['department_id'];

        // Get active subjects for this department
        $stmt = $pdo->prepare("SELECT id FROM subjects WHERE department_id = ? AND status = 1");
        $stmt->execute([$dept_id]);
        $subjects = $stmt->fetchAll(PDO::FETCH_COLUMN);

        // Get teachers for this department
        $stmt = $pdo->prepare("SELECT id FROM teachers WHERE department_id = ?");
        $stmt->execute([$dept_id]);
        $teachers = $stmt->fetchAll(PDO::FETCH_COLUMN);

        if (empty($subjects) || empty($teachers)) {
            throw new Exception("Cannot auto-generate. Ensure there are active subjects and teachers in this department.");
        }

        // Clear existing timetable for this class
        $stmt = $pdo->prepare("DELETE FROM timetable WHERE class_id = ?");
        $stmt->execute([$class_id]);

        $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
        $periods = [1, 2, 3, 4, 5, 6, 7];
        
        $default_timings = [
            1 => ['09:00', '09:50'],
            2 => ['09:50', '10:40'],
            3 => ['10:50', '11:40'],
            4 => ['11:40', '12:30'],
            5 => ['13:30', '14:20'],
            6 => ['14:20', '15:10'],
            7 => ['15:20', '16:10']
        ];

        // Total slots = 6 * 7 = 42
        $all_slots = [];
        foreach ($days as $d) {
            foreach ($periods as $p) {
                $all_slots[] = ['day' => $d, 'period' => $p];
            }
        }
        shuffle($all_slots);

        // Pick 3 test periods
        $test_slots = array_splice($all_slots, 0, 3);
        
        // Pick 3 free periods
        $free_slots = array_splice($all_slots, 0, 3);

        // The rest are subject slots (36 slots)
        $subject_slots = $all_slots;

        // Insert Test Periods
        $insert_stmt = $pdo->prepare("
            INSERT INTO timetable (class_id, day, period_number, subject_id, teacher_id, classroom, start_time, end_time) 
            VALUES (?, ?, ?, NULL, NULL, 'Exam Hall', ?, ?)
        ");
        foreach ($test_slots as $slot) {
            $insert_stmt->execute([
                $class_id, $slot['day'], $slot['period'], 
                $default_timings[$slot['period']][0], 
                $default_timings[$slot['period']][1]
            ]);
        }

        // Assign subjects and teachers to remaining slots
        $insert_stmt = $pdo->prepare("
            INSERT INTO timetable (class_id, day, period_number, subject_id, teacher_id, classroom, start_time, end_time) 
            VALUES (?, ?, ?, ?, ?, 'Standard Room', ?, ?)
        ");
        
        $subject_index = 0;
        $teacher_index = 0;
        $total_subjects = count($subjects);
        $total_teachers = count($teachers);

        foreach ($subject_slots as $slot) {
            $sub_id = $subjects[$subject_index % $total_subjects];
            $teach_id = $teachers[$teacher_index % $total_teachers];
            
            // Basic conflict avoidance loop (try next teacher if conflict)
            $attempts = 0;
            while ($attempts < $total_teachers) {
                $check = $pdo->prepare("SELECT 1 FROM timetable WHERE teacher_id = ? AND day = ? AND period_number = ?");
                $check->execute([$teach_id, $slot['day'], $slot['period']]);
                if (!$check->fetch()) {
                    break; // No conflict
                }
                $teacher_index++;
                $teach_id = $teachers[$teacher_index % $total_teachers];
                $attempts++;
            }

            $insert_stmt->execute([
                $class_id, $slot['day'], $slot['period'], $sub_id, $teach_id,
                $default_timings[$slot['period']][0], 
                $default_timings[$slot['period']][1]
            ]);

            $subject_index++;
            $teacher_index++;
        }

        // Log activity
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
        $log_stmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action, ip_address) VALUES (?, ?, ?)");
        $log_stmt->execute([$_SESSION['user_id'], "Auto-generated timetable for class $class_id", $ip]);

        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'Timetable generated successfully.']);
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid request']);
?>
