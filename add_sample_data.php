<?php
require_once 'config/database.php';

$today = date('l');

// Update some existing periods for class 3 on Monday to be free periods
$stmt = $pdo->prepare("
    UPDATE timetable 
    SET subject_id = NULL, teacher_id = NULL
    WHERE class_id = 3 AND day = ? AND period_number IN (3, 5)
");
$stmt->execute([$today]);

// Just to be sure they exist if the table was empty for class 3 (which shouldn't be the case if it's 'packed')
$stmt = $pdo->prepare("
    INSERT IGNORE INTO timetable (day, period_number, subject_id, teacher_id, class_id, classroom, start_time, end_time) 
    VALUES 
    (?, 3, NULL, NULL, 3, 'Room 103', '10:50:00', '11:40:00'),
    (?, 5, NULL, NULL, 3, 'Room 103', '13:30:00', '14:20:00')
");
$stmt->execute([$today, $today]);

echo "Free periods updated for class 3 on $today!\n";
