<?php
require_once 'includes/header.php';

$user_id = $_SESSION['user_id'];

// Handle Add Announcement
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'add') {
    $title = trim($_POST['title']);
    $category = $_POST['category'];
    $content = trim($_POST['content']);
    $notify_all = isset($_POST['notify_all']) ? true : false;
    
    if (!empty($title) && !empty($content)) {
        try {
            $pdo->beginTransaction();
            
            // 1. Insert announcement
            $stmt = $pdo->prepare("INSERT INTO announcements (user_id, title, content, category) VALUES (?, ?, ?, ?)");
            $stmt->execute([$user_id, $title, $content, $category]);
            $announcement_id = $pdo->lastInsertId();
            
            // 2. Notify all students if checked
            if ($notify_all) {
                $stmt = $pdo->query("SELECT id FROM users WHERE role = 'student'");
                $students = $stmt->fetchAll();
                
                $notif_stmt = $pdo->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (?, ?, ?, 'Announcement')");
                
                // For a real app, this should be bulk insert or queued job. Loop is fine for demo.
                foreach($students as $s) {
                    $notif_stmt->execute([$s['id'], "New Announcement: $title", substr($content, 0, 100) . '...']);
                }
            }
            
            // Log
            $ip = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
            $log_stmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action, ip_address) VALUES (?, ?, ?)");
            $log_stmt->execute([$user_id, "Created announcement: $title", $ip]);

            $pdo->commit();
            $success_msg = "Announcement posted successfully.";
        } catch (PDOException $e) {
            $pdo->rollBack();
            $error_msg = "Database error while posting announcement.";
        }
    } else {
        $error_msg = "Title and content are required.";
    }
}

// Handle Delete Announcement
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    try {
        $stmt = $pdo->prepare("DELETE FROM announcements WHERE id = ?");
        $stmt->execute([$id]);
        $success_msg = "Announcement deleted.";
    } catch(PDOException $e) {
        $error_msg = "Cannot delete announcement.";
    }
}

// Fetch announcements
$stmt = $pdo->prepare("
    SELECT a.*, u.role, 
           COALESCE(ad.first_name, t.first_name) as first_name,
           COALESCE(ad.last_name, t.last_name) as last_name
    FROM announcements a
    JOIN users u ON a.user_id = u.id
    LEFT JOIN admins ad ON u.id = ad.id
    LEFT JOIN teachers t ON u.id = t.id
    ORDER BY a.created_at DESC
");
$stmt->execute();
$announcements = $stmt->fetchAll();
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Announcements</h1>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addAnnouncementModal">
        <i class="bi bi-megaphone"></i> Post Announcement
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

<div class="row">
    <div class="col-12">
        <div class="card shadow-sm">
            <div class="card-body p-0">
                <div class="list-group list-group-flush">
                    <?php foreach($announcements as $a): ?>
                        <div class="list-group-item p-4">
                            <div class="d-flex w-100 justify-content-between align-items-start mb-2">
                                <div>
                                    <?php 
                                        $badge = 'bg-primary';
                                        if($a['category'] == 'Emergency') $badge = 'bg-danger';
                                        if($a['category'] == 'Exam') $badge = 'bg-warning text-dark';
                                    ?>
                                    <span class="badge <?php echo $badge; ?> me-2"><?php echo htmlspecialchars($a['category']); ?></span>
                                    <h5 class="mb-0 d-inline-block"><?php echo htmlspecialchars($a['title']); ?></h5>
                                </div>
                                <div>
                                    <small class="text-muted text-nowrap"><?php echo date('M d, Y h:i A', strtotime($a['created_at'])); ?></small>
                                    <a href="?delete=<?php echo $a['id']; ?>" class="btn btn-sm btn-link text-danger ms-2" onclick="return confirm('Delete this announcement?');"><i class="bi bi-trash"></i></a>
                                </div>
                            </div>
                            <p class="mb-2"><?php echo nl2br(htmlspecialchars($a['content'])); ?></p>
                            <small class="text-muted">
                                Posted by: <?php echo htmlspecialchars($a['first_name'] . ' ' . $a['last_name']); ?> 
                                <span class="badge bg-secondary ms-1"><?php echo ucfirst($a['role']); ?></span>
                            </small>
                        </div>
                    <?php endforeach; ?>
                    <?php if(empty($announcements)): ?>
                        <div class="p-5 text-center text-muted">No announcements posted yet.</div>
                    <?php endif; ?>
                </div>
            </div>
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
                                <option value="Attendance">Attendance</option>
                                <option value="Emergency">Emergency</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="content" class="form-label">Content</label>
                        <textarea class="form-control" id="content" name="content" rows="6" required></textarea>
                    </div>

                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="notify_all" name="notify_all" value="1" checked>
                        <label class="form-check-label fw-bold text-primary" for="notify_all">
                            <i class="bi bi-bell-fill"></i> Send push notification to all students
                        </label>
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
