<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit;
}
require_once '../config/database.php';

$success = '';
$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {
    $request_id = $_POST['request_id'];
    $action = $_POST['action'];
    
    if (in_array($action, ['Approved', 'Rejected'])) {
        try {
            $stmt = $pdo->prepare("UPDATE leave_forms SET status = ? WHERE id = ?");
            $stmt->execute([$action, $request_id]);
            $success = "Leave request $action successfully.";
        } catch (PDOException $e) {
            $error = "Failed to update status.";
        }
    }
}

require_once 'includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="fw-bold mb-0">All Student Leave Requests (Admin View)</h2>
</div>

<?php if($success): ?>
    <div class="alert alert-success"><i class="bi bi-check-circle me-2"></i> <?php echo htmlspecialchars($success); ?></div>
<?php endif; ?>
<?php if($error): ?>
    <div class="alert alert-danger"><i class="bi bi-exclamation-circle me-2"></i> <?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<div class="card-modern">
    <div class="table-responsive">
        <table class="table table-modern">
            <thead>
                <tr>
                    <th>Student Name</th>
                    <th>Type</th>
                    <th>Date Range</th>
                    <th>Reason</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php
                // Fetch all leave requests globally
                $stmt = $pdo->prepare("
                    SELECT lf.*, s.first_name, s.last_name, s.roll_number
                    FROM leave_forms lf
                    JOIN students s ON lf.student_id = s.id
                    ORDER BY lf.created_at DESC
                ");
                $stmt->execute();
                $requests = $stmt->fetchAll();

                if(count($requests) > 0): 
                    foreach($requests as $req): 
                ?>
                    <tr>
                        <td>
                            <div class="d-flex align-items-center">
                                <div style="width: 35px; height: 35px; border-radius: 50%; background-color: var(--primary); color: white; display: flex; align-items: center; justify-content: center; font-weight: bold; margin-right: 10px;">
                                    <?php echo substr($req['first_name'], 0, 1); ?>
                                </div>
                                <div>
                                    <div class="fw-bold"><?php echo htmlspecialchars($req['first_name'] . ' ' . $req['last_name']); ?></div>
                                    <div class="small text-muted"><?php echo htmlspecialchars($req['roll_number']); ?></div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="badge bg-light text-dark border">
                                <?php echo htmlspecialchars($req['leave_type']); ?>
                            </span>
                        </td>
                        <td>
                            <div class="small fw-bold"><?php echo date('M d, Y', strtotime($req['start_date'])); ?></div>
                            <div class="small text-muted">to <?php echo date('M d, Y', strtotime($req['end_date'])); ?></div>
                        </td>
                        <td><?php echo htmlspecialchars($req['reason']); ?></td>
                        <td>
                            <?php 
                                $badge = 'bg-warning';
                                if($req['status'] == 'Approved') $badge = 'bg-success';
                                if($req['status'] == 'Rejected') $badge = 'bg-danger';
                            ?>
                            <span class="badge <?php echo $badge; ?> px-3 py-2 rounded-pill"><?php echo htmlspecialchars($req['status']); ?></span>
                        </td>
                        <td>
                            <?php if($req['status'] == 'Pending'): ?>
                                <form action="" method="post" class="d-flex gap-2">
                                    <input type="hidden" name="request_id" value="<?php echo $req['id']; ?>">
                                    <button type="submit" name="action" value="Approved" class="btn btn-sm btn-success rounded-pill" title="Approve"><i class="bi bi-check-lg"></i></button>
                                    <button type="submit" name="action" value="Rejected" class="btn btn-sm btn-danger rounded-pill" title="Reject"><i class="bi bi-x-lg"></i></button>
                                </form>
                            <?php else: ?>
                                <!-- Admin override possible -->
                                <form action="" method="post" class="d-flex gap-2">
                                    <input type="hidden" name="request_id" value="<?php echo $req['id']; ?>">
                                    <?php if($req['status'] == 'Rejected'): ?>
                                        <button type="submit" name="action" value="Approved" class="btn btn-sm btn-outline-success rounded-pill" title="Force Approve">Override</button>
                                    <?php else: ?>
                                        <button type="submit" name="action" value="Rejected" class="btn btn-sm btn-outline-danger rounded-pill" title="Force Reject">Override</button>
                                    <?php endif; ?>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php 
                    endforeach; 
                else: 
                ?>
                    <tr>
                        <td colspan="6" class="text-center py-4 text-muted">No leave requests found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
