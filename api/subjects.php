<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$action = $_REQUEST['action'] ?? '';

if ($action == 'list') {
    $stmt = $pdo->query("SELECT s.*, d.name as department_name, sem.name as semester_name 
                         FROM subjects s 
                         JOIN departments d ON s.department_id = d.id 
                         JOIN semesters sem ON s.semester_id = sem.id
                         ORDER BY s.name ASC");
    $subjects = $stmt->fetchAll();
    echo json_encode(['success' => true, 'data' => $subjects]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if ($action == 'add') {
        $code = trim($_POST['code']);
        $name = trim($_POST['name']);
        $department_id = $_POST['department_id'];
        $semester_id = $_POST['semester_id'];
        $credits = $_POST['credits'];

        if (!empty($code) && !empty($name) && !empty($department_id) && !empty($semester_id)) {
            try {
                $stmt = $pdo->prepare("INSERT INTO subjects (code, name, department_id, semester_id, credits) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$code, $name, $department_id, $semester_id, $credits]);
                echo json_encode(['success' => true, 'message' => 'Subject added successfully.']);
                exit;
            } catch (PDOException $e) {
                echo json_encode(['success' => false, 'message' => 'Error adding subject. Code might already exist.']);
                exit;
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'All fields are required.']);
            exit;
        }
    }

    if ($action == 'edit') {
        $id = $_POST['id'];
        $code = trim($_POST['code']);
        $name = trim($_POST['name']);
        $department_id = $_POST['department_id'];
        $semester_id = $_POST['semester_id'];
        $credits = $_POST['credits'];

        if (!empty($id) && !empty($code) && !empty($name) && !empty($department_id) && !empty($semester_id)) {
            try {
                $stmt = $pdo->prepare("UPDATE subjects SET code = ?, name = ?, department_id = ?, semester_id = ?, credits = ? WHERE id = ?");
                $stmt->execute([$code, $name, $department_id, $semester_id, $credits, $id]);
                echo json_encode(['success' => true, 'message' => 'Subject updated successfully.']);
                exit;
            } catch (PDOException $e) {
                echo json_encode(['success' => false, 'message' => 'Error updating subject. Code might already exist.']);
                exit;
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'All fields are required.']);
            exit;
        }
    }

    if ($action == 'toggle_status') {
        $id = $_POST['id'];
        $status_action = $_POST['status_action'];
        $new_status = ($status_action == 'activate') ? 1 : 0;

        try {
            $stmt = $pdo->prepare("UPDATE subjects SET status = ? WHERE id = ?");
            $stmt->execute([$new_status, $id]);
            echo json_encode(['success' => true, 'message' => 'Subject status updated.']);
            exit;
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Error updating status.']);
            exit;
        }
    }

    if ($action == 'delete') {
        $id = $_POST['id'];
        try {
            $stmt = $pdo->prepare("DELETE FROM subjects WHERE id = ?");
            $stmt->execute([$id]);
            echo json_encode(['success' => true, 'message' => 'Subject deleted successfully.']);
            exit;
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Cannot delete subject because it is linked to existing records (like timetable or attendance).']);
            exit;
        }
    }
}

echo json_encode(['success' => false, 'message' => 'Invalid action']);
exit;
?>
