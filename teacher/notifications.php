<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: ../auth/login.php");
    exit;
}
require_once '../config/database.php';

$teacher_id = $_SESSION['user_id'];

// Handle Mark as Read Actions
if (isset($_GET['action'])) {
    if ($_GET['action'] == 'read_all') {
        $stmt = $pdo->prepare("UPDATE notifications SET is_read = TRUE WHERE user_id = ?");
        $stmt->execute([$teacher_id]);
    } elseif ($_GET['action'] == 'read' && isset($_GET['id'])) {
        $stmt = $pdo->prepare("UPDATE notifications SET is_read = TRUE WHERE id = ? AND user_id = ?");
        $stmt->execute([$_GET['id'], $teacher_id]);
    }
    // Redirect to clear URL parameters
    header("Location: notifications.php");
    exit;
}

require_once 'includes/header.php';

// Fetch notifications
$stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 50");
$stmt->execute([$teacher_id]);
$notifications = $stmt->fetchAll();
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Notifications</h1>
    <?php if(!empty($notifications)): ?>
        <a href="?action=read_all" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-check2-all"></i> Mark All as Read
        </a>
    <?php endif; ?>
</div>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="list-group shadow-sm mb-4">
            <?php foreach($notifications as $n): ?>
                <div class="list-group-item list-group-item-action d-flex gap-3 py-3 <?php echo $n['is_read'] ? 'bg-light text-muted' : ''; ?>">
                    <?php 
                        $icon = 'bell-fill text-primary';
                        if($n['type'] == 'Feedback') $icon = 'chat-dots-fill text-warning';
                        if($n['type'] == 'Assignment') $icon = 'journal-text text-info';
                        if($n['type'] == 'Attendance Warning') $icon = 'exclamation-triangle-fill text-danger';
                        if($n['type'] == 'Announcement') $icon = 'megaphone-fill text-success';
                    ?>
                    <i class="bi bi-<?php echo $icon; ?> fs-4"></i>
                    <div class="d-flex gap-2 w-100 justify-content-between">
                        <div>
                            <h6 class="mb-0 fw-bold <?php echo $n['is_read'] ? 'text-muted' : ''; ?>"><?php echo htmlspecialchars($n['title']); ?></h6>
                            <p class="mb-0 opacity-75"><?php echo nl2br(htmlspecialchars($n['message'])); ?></p>
                        </div>
                        <div class="text-end text-nowrap">
                            <small class="opacity-50 text-nowrap"><?php echo date('M d, h:i A', strtotime($n['created_at'])); ?></small>
                            <?php if(!$n['is_read']): ?>
                                <div class="mt-2">
                                    <a href="?action=read&id=<?php echo $n['id']; ?>" class="badge rounded-pill bg-primary text-decoration-none">Mark Read</a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
            
            <?php if(empty($notifications)): ?>
                <div class="list-group-item text-center py-5 text-muted">
                    <i class="bi bi-bell-slash fs-1 d-block mb-3"></i>
                    <h5>No Notifications</h5>
                    <p>You're all caught up!</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
