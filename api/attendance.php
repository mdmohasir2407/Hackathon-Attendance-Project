<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$action = $_REQUEST['action'] ?? '';

// ==========================================
// TEACHER ACTIONS
// ==========================================
if ($_SESSION['role'] === 'teacher') {
    
    if ($action === 'create_session') {
        $teacher_id = $_SESSION['user_id'];
        $class_id = $_POST['class_id'];
        $subject_id = $_POST['subject_id'];
        $period = $_POST['period'];
        $duration = (int)$_POST['duration']; // minutes

        if (!$class_id || !$subject_id || !$period) {
            echo json_encode(['success' => false, 'message' => 'Missing required fields.']);
            exit;
        }

        // Generate secure unique token
        $token = bin2hex(random_bytes(16));
        $date = date('Y-m-d');
        // Add duration to current time
        $expires_at_time = time() + ($duration * 60);
        $expires_at = date('Y-m-d H:i:s', $expires_at_time);

        try {
            // Check if there is already an active session for this specific class, subject, date, period
            $stmt = $pdo->prepare("SELECT id FROM attendance_sessions WHERE teacher_id = ? AND class_id = ? AND subject_id = ? AND date = ? AND period = ?");
            $stmt->execute([$teacher_id, $class_id, $subject_id, $date, $period]);
            if ($existing = $stmt->fetch()) {
                // Update existing session instead of duplicating
                $update_stmt = $pdo->prepare("UPDATE attendance_sessions SET token = ?, expires_at = ? WHERE id = ?");
                $update_stmt->execute([$token, $expires_at, $existing['id']]);
            } else {
                // Insert new session
                $insert_stmt = $pdo->prepare("INSERT INTO attendance_sessions (teacher_id, class_id, subject_id, date, period, token, expires_at) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $insert_stmt->execute([$teacher_id, $class_id, $subject_id, $date, $period, $token, $expires_at]);
            }
            
            // Log activity
            $ip = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
            $log_stmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action, ip_address) VALUES (?, ?, ?)");
            $log_stmt->execute([$teacher_id, "Generated QR Session for class $class_id", $ip]);

            echo json_encode([
                'success' => true,
                'token' => $token,
                'expires_at' => $expires_at,
                'expires_timestamp' => $expires_at_time,
                'duration_seconds' => $duration * 60
            ]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        }
        exit;
    }

    if ($action === 'get_scan_count') {
        $token = $_GET['token'];
        $stmt = $pdo->prepare("
            SELECT COUNT(*) FROM attendance a
            JOIN attendance_sessions s ON a.session_id = s.id
            WHERE s.token = ?
        ");
        $stmt->execute([$token]);
        $count = $stmt->fetchColumn();
        echo json_encode(['success' => true, 'count' => $count]);
        exit;
    }

    if ($action === 'end_session') {
        $token = $_POST['token'];
        // Expire immediately
        $stmt = $pdo->prepare("UPDATE attendance_sessions SET expires_at = NOW() WHERE token = ? AND teacher_id = ?");
        $stmt->execute([$token, $_SESSION['user_id']]);
        echo json_encode(['success' => true]);
        exit;
    }
}

// ==========================================
// STUDENT ACTIONS
// ==========================================
if ($_SESSION['role'] === 'student') {
    
    if ($action === 'mark_attendance') {
        $student_id = $_SESSION['user_id'];
        $token = trim($_POST['token']);

        if (empty($token)) {
            echo json_encode(['success' => false, 'message' => 'Token is required.']);
            exit;
        }

        try {
            // 1. Validate Token and Session Expiration
            $stmt = $pdo->prepare("
                SELECT s.*, sub.name as subject_name 
                FROM attendance_sessions s
                JOIN subjects sub ON s.subject_id = sub.id
                WHERE s.token = ?
            ");
            $stmt->execute([$token]);
            $session = $stmt->fetch();

            if (!$session) {
                echo json_encode(['success' => false, 'message' => 'Invalid QR Code.']);
                exit;
            }

            if (strtotime($session['expires_at']) < time()) {
                echo json_encode(['success' => false, 'message' => 'Attendance session expired.']);
                exit;
            }

            // 2. Validate Student Enrollment in that class
            $stmt = $pdo->prepare("SELECT * FROM enrollments WHERE student_id = ? AND class_id = ?");
            $stmt->execute([$student_id, $session['class_id']]);
            if (!$stmt->fetch()) {
                echo json_encode(['success' => false, 'message' => 'You are not enrolled in this class.']);
                exit;
            }

            // 3. Prevent Duplicate Attendance
            $stmt = $pdo->prepare("SELECT * FROM attendance WHERE session_id = ? AND student_id = ?");
            $stmt->execute([$session['id'], $student_id]);
            if ($stmt->fetch()) {
                echo json_encode(['success' => false, 'message' => 'Attendance already marked.']);
                exit;
            }

            // 4. Mark Attendance
            $stmt = $pdo->prepare("INSERT INTO attendance (session_id, student_id, status) VALUES (?, ?, 'Present')");
            $stmt->execute([$session['id'], $student_id]);

            // Award XP for Attendance
            $xp_stmt = $pdo->prepare("INSERT INTO student_achievements (student_id, achievement_name, xp_points) VALUES (?, ?, 10)");
            $xp_stmt->execute([$student_id, "Attended: " . $session['subject_name']]);

            // Log activity
            $ip = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
            $log_stmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action, ip_address) VALUES (?, ?, ?)");
            $log_stmt->execute([$student_id, "Marked attendance via QR for session " . $session['id'], $ip]);

            echo json_encode([
                'success' => true, 
                'subject_name' => $session['subject_name'],
                'time' => date('h:i A')
            ]);
            
            // NOTE: In Phase 8 (Analytics + risk detector), this is where we would ideally trigger 
            // recalculation of the student's risk status, or do it on-the-fly when loading dashboards.

        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Database error while processing attendance.']);
        }
        exit;
    }
}

echo json_encode(['success' => false, 'message' => 'Invalid action or unauthorized role.']);
?>
