<?php
// admin/profile.php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

require_once '../config/database.php';

$user_id = $_SESSION['user_id'];
$success_msg = '';
$error_msg = '';

// Handle Form īīī
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    
    // Handle Profile Picture Upload
    $profile_pic_path = $_SESSION['profile_pic'] ?? 'assets/images/default-avatar.png';
    if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../assets/uploads/profiles/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
        
        $file_info = pathinfo($_FILES['profile_pic']['name']);
        $ext = strtolower($file_info['extension']);
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        
        if (in_array($ext, $allowed)) {
            $new_filename = 'user_' . $user_id . '_' . time() . '.' . $ext;
            $target_file = $upload_dir . $new_filename;
            
            if (move_uploaded_file($_FILES['profile_pic']['tmp_name'], $target_file)) {
                $profile_pic_path = 'assets/uploads/profiles/' . $new_filename;
                
                // Update users table
                $stmt = $pdo->prepare("UPDATE users SET profile_pic = ? WHERE id = ?");
                $stmt->execute([$profile_pic_path, $user_id]);
                $_SESSION['profile_pic'] = $profile_pic_path;
            } else {
                $error_msg = "Failed to upload image.";
            }
        } else {
            $error_msg = "Invalid image format. Allowed: JPG, PNG, GIF, WEBP.";
        }
    }
    
    // Update admins table
    if (empty($error_msg) && !empty($first_name)) {
        try {
            $stmt = $pdo->prepare("UPDATE admins SET first_name = ?, last_name = ?, phone = ? WHERE id = ?");
            $stmt->execute([$first_name, $last_name, $phone, $user_id]);
            $_SESSION['name'] = $first_name . ' ' . $last_name;
            $success_msg = "Profile updated successfully!";
        } catch(PDOException $e) {
            $error_msg = "Error updating profile: " . $e->getMessage();
        }
    }
}

// Fetch current user data
$stmt = $pdo->prepare("
    SELECT u.email, u.profile_pic, a.first_name, a.last_name, a.phone
    FROM users u 
    JOIN admins a ON u.id = a.id 
    WHERE u.id = ?
");
$stmt->execute([$user_id]);
$user_data = $stmt->fetch();
?>
<?php include 'includes/header.php'; ?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="fw-bold premium-text">My Profile</h2>
            <p class="text-muted">Manage your personal information and settings</p>
        </div>
    </div>

    <?php if(!empty($success_msg)): ?>
        <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i> <?php echo htmlspecialchars($success_msg); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    
    <?php if(!empty($error_msg)): ?>
        <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i> <?php echo htmlspecialchars($error_msg); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-md-4 mb-4">
            <div class="card premium-glass-card border-0 shadow-sm text-center">
                <div class="card-body py-5">
                    <img src="../<?php echo htmlspecialchars($user_data['profile_pic'] ?? 'assets/images/default-avatar.png'); ?>" alt="Profile Picture" class="rounded-circle img-thumbnail shadow-sm mb-3" style="width: 150px; height: 150px; object-fit: cover; border: 4px solid var(--premium-accent);">
                    <h4 class="fw-bold mb-1"><?php echo htmlspecialchars($user_data['first_name'] . ' ' . $user_data['last_name']); ?></h4>
                    <p class="text-muted mb-3"><i class="bi bi-shield-lock-fill me-2 text-danger"></i>System Administrator</p>
                    <p class="text-muted small mb-0"><i class="bi bi-envelope-fill me-2"></i><?php echo htmlspecialchars($user_data['email']); ?></p>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card premium-glass-card border-0 shadow-sm">
                <div class="card-header bg-transparent border-0 pt-4 pb-0 px-4">
                    <h5 class="fw-bold mb-0">Edit Details</h5>
                </div>
                <div class="card-body p-4">
                    <form action="" method="post" enctype="multipart/form-data">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-medium">First Name</label>
                                <input type="text" name="first_name" class="form-control premium-input" value="<?php echo htmlspecialchars($user_data['first_name']); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-medium">Last Name</label>
                                <input type="text" name="last_name" class="form-control premium-input" value="<?php echo htmlspecialchars($user_data['last_name']); ?>" required>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-medium">Phone Number</label>
                                <input type="text" name="phone" class="form-control premium-input" value="<?php echo htmlspecialchars($user_data['phone'] ?? ''); ?>">
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-medium">Profile Picture</label>
                            <input type="file" name="profile_pic" class="form-control premium-input" accept="image/png, image/jpeg, image/jpg, image/webp">
                            <small class="text-muted">Leave empty to keep current picture. Max size: 2MB.</small>
                        </div>

                        <button type="submit" class="btn btn-gradient px-4 py-2"><i class="bi bi-save me-2"></i> Save Changes</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .premium-input {
        border: 2px solid rgba(14, 165, 233, 0.1);
        border-radius: 12px;
        padding: 0.8rem 1rem;
        transition: all 0.3s ease;
        background: rgba(255,255,255,0.8);
    }
    .premium-input:focus {
        border-color: #0ea5e9;
        box-shadow: 0 0 0 4px rgba(14, 165, 233, 0.15);
        background: #fff;
    }
    body.dark-mode .premium-input {
        background: rgba(15, 23, 42, 0.6);
        border-color: rgba(255,255,255,0.1);
        color: #f8fafc;
    }
    body.dark-mode .premium-input:focus {
        background: rgba(30, 41, 59, 0.8);
        border-color: #3b82f6;
    }
</style>

<?php include 'includes/footer.php'; ?>
