<?php
// seed_database.php
// Comprehensive Seeder Script with Matching Names & All Modules Data

require_once 'config/database.php';

echo "<h2>Starting Comprehensive Database Seeding...</h2>";

// 1. Wipe Existing Data
try {
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
    $tables = [
        'activity_logs', 'assignment_submissions', 'assignments', 'attendance', 
        'attendance_sessions', 'student_achievements', 'feedback', 'notifications', 
        'gate_permissions', 'leave_forms', 'study_materials', 'test_questions', 
        'test_results', 'tests', 'timetable', 'enrollments', 'teacher_subjects', 
        'subjects', 'classes', 'semesters', 'academic_years', 'announcements',
        'students', 'teachers', 'admins', 'departments', 'users'
    ];
    foreach ($tables as $table) {
        $pdo->exec("TRUNCATE TABLE `$table`");
    }
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    echo "✓ Existing data wiped.<br>";
} catch(PDOException $e) {
    die("Error wiping data: " . $e->getMessage());
}

$password_hash = password_hash('password123', PASSWORD_DEFAULT);

function insertUser($pdo, $email, $role, $hash) {
    $stmt = $pdo->prepare("INSERT INTO users (email, password_hash, role) VALUES (?, ?, ?)");
    $stmt->execute([$email, $hash, $role]);
    return $pdo->lastInsertId();
}

// 2. Create Departments
$departments = ['Computer Science', 'Mechanical', 'Civil', 'Electrical', 'Information Technology'];
$dept_ids = [];
foreach ($departments as $dept) {
    $stmt = $pdo->prepare("INSERT INTO departments (name, code) VALUES (?, ?)");
    $code = strtoupper(substr($dept, 0, 3));
    if($code == 'COM') $code = 'CSE';
    if($code == 'INF') $code = 'IT';
    $stmt->execute([$dept, $code]);
    $dept_ids[$code] = $pdo->lastInsertId();
}
echo "✓ Departments created.<br>";

// 3. Create Admins
$admin_id = insertUser($pdo, 'admin@nova.edu', 'admin', $password_hash);
$pdo->prepare("INSERT INTO admins (id, first_name, last_name, phone) VALUES (?, ?, ?, ?)")
    ->execute([$admin_id, 'Super', 'Admin', '9876543210']);
echo "✓ Admin created.<br>";

// 4. Create Teachers
$teacher_ids = [];
foreach ($dept_ids as $code => $d_id) {
    for ($i = 1; $i <= 3; $i++) {
        $email = strtolower($code) . "_teacher{$i}@nova.edu";
        $u_id = insertUser($pdo, $email, 'teacher', $password_hash);
        $pdo->prepare("INSERT INTO teachers (id, first_name, last_name, department_id, phone) VALUES (?, ?, ?, ?, ?)")
            ->execute([$u_id, "Prof. $code", "Teacher $i", $d_id, "888000111$i"]);
        $teacher_ids[$d_id][] = $u_id;
    }
}
echo "✓ Teachers created.<br>";

// 5. Create Academic Years & Semesters & Classes
$pdo->exec("INSERT INTO academic_years (name, start_date, end_date, is_current) VALUES ('2023-2024', '2023-08-01', '2024-05-31', 1)");
$year_id = $pdo->lastInsertId();
$pdo->exec("INSERT INTO semesters (academic_year_id, name, start_date, end_date) VALUES ($year_id, 'Fall 2023', '2023-08-01', '2023-12-15')");
$sem_id = $pdo->lastInsertId();

$class_ids = [];
foreach ($dept_ids as $code => $d_id) {
    $pdo->prepare("INSERT INTO classes (department_id, semester_id, name) VALUES (?, ?, ?)")
        ->execute([$d_id, $sem_id, "B.Tech $code Year 1"]);
    $class_ids[$d_id] = $pdo->lastInsertId();
}

// 6. Create Subjects and map Teachers
$subject_ids = [];
foreach ($dept_ids as $code => $d_id) {
    for($i=1; $i<=3; $i++) {
        $pdo->prepare("INSERT INTO subjects (department_id, semester_id, name, code, credits) VALUES (?, ?, ?, ?, ?)")
            ->execute([$d_id, $sem_id, "$code Core Subject $i", "{$code}10$i", 3]);
        $sub_id = $pdo->lastInsertId();
        $subject_ids[$d_id][] = $sub_id;
        
        $t_id = $teacher_ids[$d_id][array_rand($teacher_ids[$d_id])];
        $pdo->prepare("INSERT INTO teacher_subjects (teacher_id, subject_id, class_id) VALUES (?, ?, ?)")
            ->execute([$t_id, $sub_id, $class_ids[$d_id]]);
    }
}

// 7. Names Dictionaries (Categorized by Religion)
$hindu_first = ['Ram', 'Raj', 'Dev', 'Jai', 'Sam', 'Adi', 'Siva', 'Hari', 'Mani', 'Bala', 'Arun', 'Gopi', 'Anu', 'Uma', 'Riya', 'Diya', 'Maha', 'Sri'];
$hindu_last = ['Iyer', 'Nair', 'Das', 'Rao', 'Babu', 'Kumar', 'Raj', 'Ram', 'Dev', 'Siva'];

$muslim_first = ['Ali', 'Zaid', 'Umar', 'Raza', 'Saad', 'Adil', 'Sana', 'Hina', 'Zara', 'Safa', 'Zoya', 'Nida', 'Iqra', 'Omar', 'Asad', 'Fiz', 'Rafi'];
$muslim_last = ['Ali', 'Khan', 'Syed', 'Basha', 'Sha', 'Mir', 'Dar', 'Peer', 'Baig', 'Din'];

// Generate exactly 100 unique names (50 Hindu, 50 Muslim)
$generated_names = [];
$unique_combinations = [];

// Generate 50 Hindu
while(count($unique_combinations) < 50) {
    $fn = $hindu_first[array_rand($hindu_first)];
    $ln = $hindu_last[array_rand($hindu_last)];
    $full = $fn . ' ' . $ln;
    if(!isset($unique_combinations[$full])) {
        $unique_combinations[$full] = ['fn' => $fn, 'ln' => $ln, 'rel' => 'hindu'];
    }
}
// Generate 50 Muslim
while(count($unique_combinations) < 100) {
    $fn = $muslim_first[array_rand($muslim_first)];
    $ln = $muslim_last[array_rand($muslim_last)];
    $full = $fn . ' ' . $ln;
    if(!isset($unique_combinations[$full])) {
        $unique_combinations[$full] = ['fn' => $fn, 'ln' => $ln, 'rel' => 'muslim'];
    }
}

// Convert to indexed array and shuffle so they are distributed randomly across departments
$student_pool = array_values($unique_combinations);
shuffle($student_pool);
$student_index = 0;
$used_phones = [];

$total_students = 0;
$student_user_ids = [];
foreach ($dept_ids as $code => $d_id) {
    $cid = $class_ids[$d_id];
    for ($i = 1; $i <= 20; $i++) {
        
        $student = $student_pool[$student_index++];
        $fn = $student['fn'];
        $ln = $student['ln'];
        $roll = $code . "26" . str_pad($i, 3, '0', STR_PAD_LEFT);
        
        $clean_fn = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $fn));
        $clean_ln = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $ln));
        $email = $clean_fn . '.' . $clean_ln . '_' . strtolower($roll) . "@student.nova.edu";
        
        $student_password = $clean_fn . '123';
        $student_hash = password_hash($student_password, PASSWORD_DEFAULT);
        
        $u_id = insertUser($pdo, $email, 'student', $student_hash);
        $student_user_ids[] = $u_id;
        
        // Generate unique phone
        do {
            $phone = "9" . rand(100000000, 999999999);
        } while(isset($used_phones[$phone]));
        $used_phones[$phone] = true;
        
        $pdo->prepare("INSERT INTO students (id, roll_number, first_name, last_name, phone) VALUES (?, ?, ?, ?, ?)")
            ->execute([$u_id, $roll, $fn, $ln, $phone]);
            
        $pdo->prepare("INSERT INTO enrollments (student_id, class_id) VALUES (?, ?)")
            ->execute([$u_id, $cid]);
            
        $total_students++;
    }
}
echo "✓ $total_students Categorized Students created.<br>";

// 8. Timetable
$days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
foreach ($dept_ids as $code => $d_id) {
    $cid = $class_ids[$d_id];
    foreach($days as $day) {
        for($period=1; $period<=4; $period++) {
            $sub = $subject_ids[$d_id][array_rand($subject_ids[$d_id])];
            
            $stmt = $pdo->prepare("SELECT teacher_id FROM teacher_subjects WHERE subject_id = ? AND class_id = ?");
            $stmt->execute([$sub, $cid]);
            $tid = $stmt->fetchColumn();
            if(!$tid) $tid = $teacher_ids[$d_id][0];
            
            $start = sprintf("%02d:00:00", 8 + $period);
            $end = sprintf("%02d:00:00", 9 + $period);
            
            $pdo->prepare("INSERT INTO timetable (day, period_number, subject_id, teacher_id, class_id, classroom, start_time, end_time) VALUES (?, ?, ?, ?, ?, ?, ?, ?)")
                ->execute([$day, $period, $sub, $tid, $cid, "Room {$code}-101", $start, $end]);
        }
    }
}
echo "✓ Timetable generated.<br>";

// 9. Operational Data (Attendance, Assignments, Leave, Gate Pass, Feedback, Tests, Materials)
foreach($dept_ids as $code => $d_id) {
    $cid = $class_ids[$d_id];
    
    // Fetch students of this class
    $stmt_stu = $pdo->prepare("SELECT student_id FROM enrollments WHERE class_id = ?");
    $stmt_stu->execute([$cid]);
    $students = $stmt_stu->fetchAll();
    
    $t_id = $teacher_ids[$d_id][0];
    $sub_id = $subject_ids[$d_id][0];

    // Attendance
    $token = bin2hex(random_bytes(4));
    $pdo->prepare("INSERT INTO attendance_sessions (teacher_id, subject_id, class_id, date, period, token, expires_at) VALUES (?, ?, ?, ?, ?, ?, ?)")
        ->execute([$t_id, $sub_id, $cid, date('Y-m-d'), 1, $token, date('Y-m-d H:i:s', strtotime('+1 hour'))]);
    $sess_id = $pdo->lastInsertId();

    // Assignment
    $pdo->prepare("INSERT INTO assignments (teacher_id, subject_id, class_id, title, description, deadline) VALUES (?, ?, ?, ?, ?, ?)")
        ->execute([$t_id, $sub_id, $cid, 'Mid-term Report', 'Complete the report', date('Y-m-d H:i:s', strtotime('+7 days'))]);
    $assign_id = $pdo->lastInsertId();

    // Test
    $pdo->prepare("INSERT INTO tests (teacher_id, subject_id, class_id, test_period, scheduled_date, title, status) VALUES (?, ?, ?, ?, ?, ?, ?)")
        ->execute([$t_id, $sub_id, $cid, 'Period 1', date('Y-m-d', strtotime('-2 days')), 'Unit Test 1', 'Completed']);
    $test_id = $pdo->lastInsertId();
    
    // Study Material
    $pdo->prepare("INSERT INTO study_materials (teacher_id, subject_id, title, file_path) VALUES (?, ?, ?, ?)")
        ->execute([$t_id, $sub_id, 'Chapter 1 Notes', 'assets/uploads/materials/dummy.pdf']);

    foreach($students as $stu) {
        $sid = $stu['student_id'];
        // Attendance
        $status = (rand(1, 100) <= 85) ? 'present' : 'absent';
        $pdo->prepare("INSERT INTO attendance (session_id, student_id, status) VALUES (?, ?, ?)")
            ->execute([$sess_id, $sid, $status]);
            
        // Assignment Submission
        if(rand(1, 100) <= 70) {
            $pdo->prepare("INSERT INTO assignment_submissions (assignment_id, student_id, file_path, status) VALUES (?, ?, ?, ?)")
                ->execute([$assign_id, $sid, 'assets/uploads/assignments/dummy.pdf', 'SUBMITTED']);
        }
        
        // Test Result
        $pdo->prepare("INSERT INTO test_results (test_id, student_id, score, total_marks) VALUES (?, ?, ?, ?)")
            ->execute([$test_id, $sid, rand(40, 100), 100]);
            
        // Leave / Gatepass
        if(rand(1,100) <= 10) {
            $pdo->prepare("INSERT INTO leave_forms (student_id, leave_type, start_date, end_date, reason, status) VALUES (?, ?, ?, ?, ?, ?)")
                ->execute([$sid, 'Pre-Leave', date('Y-m-d', strtotime('+1 day')), date('Y-m-d', strtotime('+2 days')), 'Family function', 'Pending']);
        }
        if(rand(1,100) <= 10) {
            $pdo->prepare("INSERT INTO gate_permissions (student_id, reason, request_date, time_out, expected_time_in, status) VALUES (?, ?, ?, ?, ?, ?)")
                ->execute([$sid, 'Medical checkup', date('Y-m-d'), '10:00:00', '13:00:00', 'Pending']);
        }
        
        // Feedback
        if(rand(1,100) <= 20) {
            $f_type = (rand(1,2)==1) ? 'Excellent' : 'Needs Practice';
            $pdo->prepare("INSERT INTO feedback (teacher_id, student_id, subject_id, feedback_type, note) VALUES (?, ?, ?, ?, ?)")
                ->execute([$t_id, $sid, $sub_id, $f_type, 'Keep up the good work']);
        }
    }
}
echo "✓ All Modules Operational Data (Leave, Gatepass, Feedback, Tests, Materials) generated.<br>";

// 10. Create global announcements
$pdo->prepare("INSERT INTO announcements (user_id, title, content, category) VALUES (?, ?, ?, ?)")
    ->execute([$admin_id, 'Welcome to Campus Nova', 'All modules are now fully operational with data.', 'General']);

echo "<h3>Seeding Complete!</h3>";
echo "<p><strong>Test Accounts:</strong></p>";
echo "<ul>";
echo "<li>Admin: admin@nova.edu (Password: password123)</li>";
echo "<li>Teacher: cse_teacher1@nova.edu (Password: password123)</li>";
echo "<li>Student: <em>Look at the database <code>users</code> table for generated student emails.</em> Their password will be their <strong>firstname (in lowercase) + 123</strong> (e.g. <code>hari123</code>, <code>safiya123</code>, <code>joseph123</code>).</li>";
echo "</ul>";
?>
