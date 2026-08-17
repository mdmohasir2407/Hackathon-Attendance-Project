<?php
require_once 'includes/header.php';

$student_id = $_SESSION['user_id'];

// Handle Mark as Received
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'mark_received') {
    $author_id = $_POST['author_id'];
    $title = $_POST['announcement_title'];
    
    $stu_stmt = $pdo->prepare("SELECT first_name, last_name, roll_number FROM students WHERE id = ?");
    $stu_stmt->execute([$student_id]);
    $student_info = $stu_stmt->fetch();
    
    if ($student_info) {
        $student_name = $student_info['first_name'] . ' ' . $student_info['last_name'] . ' (' . $student_info['roll_number'] . ')';
        $msg = "$student_name has acknowledged and marked your announcement '$title' as Received.";
        
        $ins = $pdo->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (?, ?, ?, ?)");
        $ins->execute([$author_id, 'Announcement Received', $msg, 'Feedback']);
        
        $success_msg = "Successfully marked as received. The author has been notified.";
    }
}

// Get student's class
$stmt = $pdo->prepare("SELECT class_id FROM enrollments WHERE student_id = ?");
$stmt->execute([$student_id]);
$enrollment = $stmt->fetch();
$class_id = $enrollment ? $enrollment['class_id'] : null;

// Fetch global (Admin) announcements AND Class-specific (Teacher) announcements
// For simplicity in this schema, teachers create announcements that generate notifications for their class, 
// but the announcements table itself doesn't strictly link to class_id unless we altered the schema.
// Since we didn't add class_id to announcements table, we will fetch:
// 1. All Admin announcements
// 2. All Teacher announcements where the teacher teaches this student's class
$announcements = [];
if ($class_id) {
    $stmt = $pdo->prepare("
        SELECT DISTINCT a.*, u.role, 
               COALESCE(ad.first_name, t.first_name) as first_name,
               COALESCE(ad.last_name, t.last_name) as last_name
        FROM announcements a
        JOIN users u ON a.user_id = u.id
        LEFT JOIN admins ad ON u.id = ad.id
        LEFT JOIN teachers t ON u.id = t.id
        LEFT JOIN teacher_subjects ts ON t.id = ts.teacher_id
        WHERE u.role = 'admin' OR (u.role = 'teacher' AND ts.class_id = ?)
        ORDER BY a.created_at DESC
    ");
    $stmt->execute([$class_id]);
    $announcements = $stmt->fetchAll();
} else {
    // Only admin announcements if not enrolled
    $stmt = $pdo->prepare("
        SELECT a.*, u.role, ad.first_name, ad.last_name
        FROM announcements a
        JOIN users u ON a.user_id = u.id
        JOIN admins ad ON u.id = ad.id
        WHERE u.role = 'admin'
        ORDER BY a.created_at DESC
    ");
    $stmt->execute();
    $announcements = $stmt->fetchAll();
}
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Announcements Board</h1>
</div>

<?php if(isset($success_msg)): ?>
    <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i> <?php echo $success_msg; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="row">
    <div class="col-12">
        <div class="card shadow-sm border-0">
            <div class="card-body p-0">
                <div class="list-group list-group-flush">
                    <?php foreach($announcements as $a): ?>
                        <div class="list-group-item p-4 border-bottom">
                            <div class="d-flex w-100 justify-content-between align-items-start mb-2">
                                <div>
                                    <?php 
                                        $badge = 'bg-primary';
                                        if($a['category'] == 'Emergency') $badge = 'bg-danger';
                                        if($a['category'] == 'Exam') $badge = 'bg-warning text-dark';
                                    ?>
                                    <span class="badge <?php echo $badge; ?> me-2"><?php echo htmlspecialchars($a['category']); ?></span>
                                    <h5 class="mb-0 d-inline-block fw-bold text-dark"><?php echo htmlspecialchars($a['title']); ?></h5>
                                </div>
                                <small class="text-muted text-nowrap"><i class="bi bi-clock"></i> <?php echo date('M d, Y h:i A', strtotime($a['created_at'])); ?></small>
                            </div>
                            <p class="mb-3 text-secondary" style="line-height: 1.6;"><?php echo nl2br(htmlspecialchars($a['content'])); ?></p>
                            
                            <div class="d-flex align-items-center justify-content-between mt-3">
                                <div class="d-flex align-items-center">
                                    <div class="bg-light rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 32px; height: 32px;">
                                        <i class="bi bi-person-fill text-secondary"></i>
                                    </div>
                                    <small class="text-muted">
                                        Posted by <strong><?php echo htmlspecialchars($a['first_name'] . ' ' . $a['last_name']); ?></strong> 
                                        <span class="badge bg-light text-secondary border ms-1"><?php echo ucfirst($a['role']); ?></span>
                                    </small>
                                </div>
                                <form method="POST" action="announcements.php" class="m-0">
                                    <input type="hidden" name="action" value="mark_received">
                                    <input type="hidden" name="author_id" value="<?php echo $a['user_id']; ?>">
                                    <input type="hidden" name="announcement_title" value="<?php echo htmlspecialchars($a['title']); ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-success">
                                        <i class="bi bi-check2-circle"></i> Mark as Received
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    
                    <?php if(empty($announcements)): ?>
                        <div class="p-5 text-center text-muted">
                            <i class="bi bi-megaphone fs-1 d-block mb-3 opacity-50"></i>
                            <h5>No Announcements</h5>
                            <p>There are currently no announcements for your class.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
