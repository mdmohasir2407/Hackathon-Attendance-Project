<?php
require_once 'includes/header.php';

// Fetch Logs
$stmt = $pdo->prepare("
    SELECT a.*, u.role, u.email, 
           COALESCE(ad.first_name, t.first_name, s.first_name) as first_name,
           COALESCE(ad.last_name, t.last_name, s.last_name) as last_name
    FROM activity_logs a
    LEFT JOIN users u ON a.user_id = u.id
    LEFT JOIN admins ad ON u.id = ad.id
    LEFT JOIN teachers t ON u.id = t.id
    LEFT JOIN students s ON u.id = s.id
    ORDER BY a.timestamp DESC
    LIMIT 100
");
$stmt->execute();
$logs = $stmt->fetchAll();
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">System Activity Logs</h1>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Timestamp</th>
                        <th>User</th>
                        <th>Role</th>
                        <th>Action</th>
                        <th>IP Address</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($logs as $log): ?>
                    <tr>
                        <td class="text-nowrap"><small class="text-muted"><?php echo date('M d, Y H:i:s', strtotime($log['timestamp'])); ?></small></td>
                        <td>
                            <?php if($log['user_id']): ?>
                                <strong><?php echo htmlspecialchars($log['first_name'] . ' ' . $log['last_name']); ?></strong><br>
                                <small class="text-muted"><?php echo htmlspecialchars($log['email']); ?></small>
                            <?php else: ?>
                                <span class="text-muted fst-italic">System</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if($log['role']): ?>
                                <span class="badge bg-secondary"><?php echo ucfirst($log['role']); ?></span>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($log['action']); ?></td>
                        <td><small class="font-monospace text-muted"><?php echo htmlspecialchars($log['ip_address']); ?></small></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if(empty($logs)): ?>
                    <tr><td colspan="5" class="text-center py-4 text-muted">No activity logs found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
