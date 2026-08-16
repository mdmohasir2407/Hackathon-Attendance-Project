<?php
require_once 'includes/header.php';

$user_id = $_SESSION['user_id'];

// Handle Add Announcement
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'add') {
    $title = trim($_POST['title']);
    $category = $_POST['category'];
    $content = trim($_POST['content']);
    $notify_class_id = $_POST['notify_class_id'];
    
    if (!empty($title) && !empty($content) && !empty($notify_class_id)) {
        try {
            $pdo->beginTransaction();
            
            // 1. Insert announcement
            $stmt = $pdo->prepare("INSERT INTO announcements (user_id, title, content, category) VALUES (?, ?, ?, ?)");
            $stmt->execute([$user_id, $title, $content, $category]);
            
            // 2. Notify students in specific class
            $stmt = $pdo->prepare("SELECT student_id FROM enrollments WHERE class_id = ?");
            $stmt->execute([$notify_class_id]);
            $students = $stmt->fetchAll();
            
            $notif_stmt = $pdo->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (?, ?, ?, 'Announcement')");
            foreach($students as $s) {
                $notif_stmt->execute([$s['student_id'], "Class Announcement: $title", substr($content, 0, 100) . '...']);
            }

            $pdo->commit();
            $success_msg = "Announcement posted to class successfully.";
        } catch (PDOException $e) {
            $pdo->rollBack();
            $error_msg = "Database error while posting announcement.";
        }
    } else {
        $error_msg = "Title, content, and class are required.";
    }
}

// Fetch classes taught by this teacher for the form
$stmt = $pdo->prepare("
    SELECT DISTINCT c.id, c.name, d.name as dept_name 
    FROM teacher_subjects ts
    JOIN classes c ON ts.class_id = c.id
    JOIN departments d ON c.department_id = d.id
    WHERE ts.teacher_id = ?
");
$stmt->execute([$user_id]);
$my_classes = $stmt->fetchAll();

// Fetch teacher's own announcements
$stmt = $pdo->prepare("
    SELECT * FROM announcements WHERE user_id = ? ORDER BY created_at DESC
");
$stmt->execute([$user_id]);
$announcements = $stmt->fetchAll();
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Announcements</h1>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addAnnouncementModal">
        <i class="bi bi-megaphone"></i> Post to Class
    </button>
</div>

<?php if(isset($success_msg)): ?>
    <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
        <?php echo $success_msg; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<?php if(isset($error_msg)): ?>
    <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
        <?php echo $error_msg; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="card shadow-sm">
    <div class="card-header">
        <i class="bi bi-clock-history me-1"></i> My Announcements
    </div>
    <div class="card-body p-0">
        <div class="list-group list-group-flush">
            <?php foreach($announcements as $a): ?>
                <div class="list-group-item p-4 bg-transparent text-light">
                    <div class="d-flex w-100 justify-content-between align-items-start mb-2">
                        <div>
                            <span class="badge bg-secondary me-2"><?php echo htmlspecialchars($a['category']); ?></span>
                            <h5 class="mb-0 d-inline-block"><?php echo htmlspecialchars($a['title']); ?></h5>
                        </div>
                        <small class="text-muted text-nowrap"><?php echo date('M d, Y h:i A', strtotime($a['created_at'])); ?></small>
                    </div>
                    <p class="mb-0"><?php echo nl2br(htmlspecialchars($a['content'])); ?></p>
                </div>
            <?php endforeach; ?>
            <?php if(empty($announcements)): ?>
                <div class="p-5 text-center text-muted">You haven't posted any announcements.</div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Add Modal -->
<div class="modal fade" id="addAnnouncementModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Post New Announcement</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="announcements.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add">
                    
                    <div class="mb-3">
                        <label for="notify_class_id" class="form-label">Target Class</label>
                        <select class="form-select" id="notify_class_id" name="notify_class_id" required>
                            <option value="">-- Choose Class to Notify --</option>
                            <?php foreach ($my_classes as $c): ?>
                                <option value="<?php echo $c['id']; ?>">
                                    <?php echo htmlspecialchars($c['dept_name'] . ' - ' . $c['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text text-primary"><i class="bi bi-info-circle"></i> Students in this class will receive a notification.</div>
                    </div>

                    <div class="row">
                        <div class="col-md-8 mb-3">
                            <label for="title" class="form-label">Title</label>
                            <input type="text" class="form-control" id="title" name="title" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="category" class="form-label">Category</label>
                            <select class="form-select" id="category" name="category" required>
                                <option value="General">General</option>
                                <option value="Assignment">Assignment</option>
                                <option value="Exam">Exam</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="content" class="form-label">Content</label>
                        <textarea class="form-control" id="content" name="content" rows="5" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Post Announcement</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
