<?php
// seed_database.php
// Comprehensive Seeder Script

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

$admin_password_hash = password_hash('password123', PASSWORD_DEFAULT);

function insertUser($pdo, $email, $role, $hash) {
    $stmt = $pdo->prepare("INSERT INTO users (email, password_hash, role) VALUES (?, ?, ?)");
    $stmt->execute([$email, $hash, $role]);
    return $pdo->lastInsertId();
}

// 2. Create Departments
$department_names = ['MCA', 'MBA', 'CSE', 'IT', 'ECE'];
$dept_ids = [];
foreach ($department_names as $code) {
    $name = '';
    if($code == 'MCA') $name = 'Master of Computer Applications';
    if($code == 'MBA') $name = 'Master of Business Administration';
    if($code == 'CSE') $name = 'Computer Science and Engineering';
    if($code == 'IT') $name = 'Information Technology';
    if($code == 'ECE') $name = 'Electronics and Communication';
    
    $stmt = $pdo->prepare("INSERT INTO departments (name, code) VALUES (?, ?)");
    $stmt->execute([$name, $code]);
    $dept_ids[$code] = $pdo->lastInsertId();
}
echo "✓ Departments created.<br>";

// 3. Create Admins
$admin_id = insertUser($pdo, 'admin@nova.edu', 'admin', $admin_password_hash);
$pdo->prepare("INSERT INTO admins (id, first_name, last_name, phone) VALUES (?, ?, ?, ?)")
    ->execute([$admin_id, 'Super', 'Admin', '9876543210']);
echo "✓ Admin created.<br>";

// 4. Create Teachers
$teachers_data = [
    'MCA' => ['manimozhi', 'shaktidevi', 'anbarasan', 'Rajan'],
    'MBA' => ['divya', 'tamizh', 'sekar', 'kannan'],
    'CSE' => ['Keerthi', 'jhonsi', 'Bhaskar', 'maasi'],
    'IT'  => ['basha', 'theantamil', 'meera', 'raju'],
    'ECE' => ['ganesh', 'murugan', 'karthik', 'kumar']
];

$teacher_ids = [];
foreach ($teachers_data as $code => $teachers) {
    $d_id = $dept_ids[$code];
    foreach ($teachers as $tname) {
        $clean_tname = strtolower($tname);
        $email = $code . $clean_tname . "@nova.in";
        $password = $clean_tname . "123";
        $hash = password_hash($password, PASSWORD_DEFAULT);
        
        $u_id = insertUser($pdo, $email, 'teacher', $hash);
        $phone = "9" . rand(100000000, 999999999);
        $pdo->prepare("INSERT INTO teachers (id, first_name, last_name, department_id, phone) VALUES (?, ?, ?, ?, ?)")
            ->execute([$u_id, ucfirst($tname), '', $d_id, $phone]);
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
        ->execute([$d_id, $sem_id, "$code Year 1"]);
    $class_ids[$d_id] = $pdo->lastInsertId();
}

// 6. Create Subjects and map Teachers
$subject_names_map = [
    'MCA' => ['Programming in C', 'Database Management', 'Web Technologies', 'Software Engineering'],
    'MBA' => ['Management Principles', 'Organizational Behavior', 'Managerial Economics', 'Financial Accounting'],
    'CSE' => ['Data Structures', 'Operating Systems', 'Computer Networks', 'Artificial Intelligence'],
    'IT'  => ['Object Oriented Programming', 'Software Testing', 'Cloud Computing', 'Cyber Security'],
    'ECE' => ['Electronic Devices', 'Digital Logic', 'Signals and Systems', 'Microprocessors']
];

$subject_ids = [];
foreach ($dept_ids as $code => $d_id) {
    $dept_teachers = $teacher_ids[$d_id];
    $subjects = $subject_names_map[$code];
    for($i=0; $i<4; $i++) {
        $subject_name = $subjects[$i];
        $pdo->prepare("INSERT INTO subjects (department_id, semester_id, name, code, credits) VALUES (?, ?, ?, ?, ?)")
            ->execute([$d_id, $sem_id, $subject_name, "{$code}10" . ($i+1), 3]);
        $sub_id = $pdo->lastInsertId();
        $subject_ids[$d_id][] = $sub_id;
        
        // Map 1 teacher per subject for simplicity
        $t_id = $dept_teachers[$i];
        $pdo->prepare("INSERT INTO teacher_subjects (teacher_id, subject_id, class_id) VALUES (?, ?, ?)")
            ->execute([$t_id, $sub_id, $class_ids[$d_id]]);
    }
}

// 7. Students (60 students, 15 per dept, sorted alphabetically)
$raw_student_names = "mohasir, harish, Abdul, Balaji, anbu, Sathya, grish, Parthiban, began, mani, magesh, Prasanth, aagash, Dhanush, dharani, sandhiya, Safiya, Priya, premalika, hema, charu, jafren, selvi, udhayan, Deepika, jayapriya, jp, james, dharani, aanand, Sanjay, hari, eyal, arun, Mohamed, aasik, rogan, Siraj, valli, siva, apsal, Karthik, suganya, suresh, kode, sumithra, vishwasri, elizharasi, Ashwin, Rohit, rajesh, sabapathy, tamizh, sharmilan, rakesh, joseph, Vijay, Ajith, Surya, Premji, yash, jagan, mohan, Abdul, rasheddha, parveen, arunthathi, Padmaja, sivani, gayathiri, sujithra, krishnaveni, sowmiya, aysha, raja";

$names_array = array_map('trim', explode(',', $raw_student_names));
$names_array = array_map('strtolower', $names_array);
$names_array = array_unique($names_array); // Remove duplicates
sort($names_array); // Sort alphabetically

$selected_students = array_slice($names_array, 0, 75);

// Pad with extra names if less than 75
while (count($selected_students) < 75) {
    $selected_students[] = "student" . (count($selected_students) + 1);
}

$student_index = 0;
$total_students = 0;
foreach ($dept_ids as $code => $d_id) {
    $cid = $class_ids[$d_id];
    for ($i = 1; $i <= 15; $i++) {
        if ($student_index >= count($selected_students)) break;
        
        $fname = $selected_students[$student_index];
        $roll = $code . "26" . str_pad($i, 3, '0', STR_PAD_LEFT);
        
        // Ensure unique email by matching the credentials.txt format
        $email = $fname . "65@nova.edu";
        $password = $fname . "123";
        $student_hash = password_hash($password, PASSWORD_DEFAULT);
        
        $u_id = insertUser($pdo, $email, 'student', $student_hash);
        
        $student_index++;
        $phone = "9" . rand(100000000, 999999999);
        
        $pdo->prepare("INSERT INTO students (id, roll_number, first_name, last_name, phone) VALUES (?, ?, ?, ?, ?)")
            ->execute([$u_id, $roll, ucfirst($fname), '', $phone]);
            
        $pdo->prepare("INSERT INTO enrollments (student_id, class_id) VALUES (?, ?)")
            ->execute([$u_id, $cid]);
            
        $total_students++;
    }
}
echo "✓ $total_students Students created.<br>";

// 8. Timetable (7 periods)
$days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
foreach ($dept_ids as $code => $d_id) {
    $cid = $class_ids[$d_id];
    foreach($days as $day) {
        for($period=1; $period<=7; $period++) {
            // 15% chance of free period
            if (rand(1, 100) <= 15) {
                continue; 
            }

            // Pick random subject
            $sub_idx = array_rand($subject_ids[$d_id]);
            $sub = $subject_ids[$d_id][$sub_idx];
            
            // Get teacher for this subject
            $stmt = $pdo->prepare("SELECT teacher_id FROM teacher_subjects WHERE subject_id = ? AND class_id = ?");
            $stmt->execute([$sub, $cid]);
            $tid = $stmt->fetchColumn();
            
            // Adjust time for 7 periods (e.g. 8:00 AM to 3:00 PM)
            $start_hour = 7 + $period;
            $start = sprintf("%02d:00:00", $start_hour);
            $end = sprintf("%02d:00:00", $start_hour + 1);
            
            $pdo->prepare("INSERT INTO timetable (day, period_number, subject_id, teacher_id, class_id, classroom, start_time, end_time) VALUES (?, ?, ?, ?, ?, ?, ?, ?)")
                ->execute([$day, $period, $sub, $tid, $cid, "Room {$code}-101", $start, $end]);
        }
    }
}
echo "✓ 7-Period Timetable generated.<br>";

// 9. Operational Data (Attendance, Assignments, Leave, Gate Pass, Feedback, Tests, Materials)
foreach($dept_ids as $code => $d_id) {
    $cid = $class_ids[$d_id];
    
    // Fetch students of this class
    $stmt_stu = $pdo->prepare("SELECT student_id FROM enrollments WHERE class_id = ?");
    $stmt_stu->execute([$cid]);
    $students = $stmt_stu->fetchAll();
    
    $t_id = $teacher_ids[$d_id][0];
    $sub_id = $subject_ids[$d_id][0];

    // Generate 5 sets of operational data per department
    for ($k=1; $k<=5; $k++) {
        // Attendance
        $token = bin2hex(random_bytes(4));
        $date = date('Y-m-d', strtotime("-$k days"));
        $pdo->prepare("INSERT INTO attendance_sessions (teacher_id, subject_id, class_id, date, period, token, expires_at) VALUES (?, ?, ?, ?, ?, ?, ?)")
            ->execute([$t_id, $sub_id, $cid, $date, 1, $token, date('Y-m-d H:i:s', strtotime("-$k days +1 hour"))]);
        $sess_id = $pdo->lastInsertId();

        // Assignment
        $pdo->prepare("INSERT INTO assignments (teacher_id, subject_id, class_id, title, description, deadline) VALUES (?, ?, ?, ?, ?, ?)")
            ->execute([$t_id, $sub_id, $cid, "Assignment $k", "Complete assignment $k", date('Y-m-d H:i:s', strtotime("+" . (7-$k) . " days"))]);
        $assign_id = $pdo->lastInsertId();

        // Test
        $pdo->prepare("INSERT INTO tests (teacher_id, subject_id, class_id, test_period, scheduled_date, title, status) VALUES (?, ?, ?, ?, ?, ?, ?)")
            ->execute([$t_id, $sub_id, $cid, 'Period 1', date('Y-m-d', strtotime("-$k days")), "Unit Test $k", 'Completed']);
        $test_id = $pdo->lastInsertId();
        
        // Study Material
        $pdo->prepare("INSERT INTO study_materials (teacher_id, subject_id, title, file_path) VALUES (?, ?, ?, ?)")
            ->execute([$t_id, $sub_id, "Chapter $k Notes", 'assets/uploads/materials/dummy.pdf']);

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
        }
    }

    // Leave / Gatepass / Feedback outside the 5x loop
    foreach($students as $stu) {
        $sid = $stu['student_id'];
        
        // Leave / Gatepass
        if(rand(1,100) <= 30) {
            $pdo->prepare("INSERT INTO leave_forms (student_id, leave_type, start_date, end_date, reason, status) VALUES (?, ?, ?, ?, ?, ?)")
                ->execute([$sid, 'Pre-Leave', date('Y-m-d', strtotime('+1 day')), date('Y-m-d', strtotime('+2 days')), 'Family function', 'Pending']);
        }
        if(rand(1,100) <= 30) {
            $pdo->prepare("INSERT INTO gate_permissions (student_id, reason, request_date, time_out, expected_time_in, status) VALUES (?, ?, ?, ?, ?, ?)")
                ->execute([$sid, 'Medical checkup', date('Y-m-d'), '10:00:00', '13:00:00', 'Pending']);
        }
        
        // Feedback
        if(rand(1,100) <= 50) {
            $f_type = (rand(1,2)==1) ? 'Excellent' : 'Needs Practice';
            $pdo->prepare("INSERT INTO feedback (teacher_id, student_id, subject_id, feedback_type, note) VALUES (?, ?, ?, ?, ?)")
                ->execute([$t_id, $sid, $sub_id, $f_type, 'Keep up the good work']);
        }
    }
}
echo "✓ Operational Data generated.<br>";

// 10. Create global announcements
$pdo->prepare("INSERT INTO announcements (user_id, title, content, category) VALUES (?, ?, ?, ?)")
    ->execute([$admin_id, 'Welcome to Campus Nova', 'All modules are now fully operational with data.', 'General']);

echo "<h3>Seeding Complete!</h3>";
?>
