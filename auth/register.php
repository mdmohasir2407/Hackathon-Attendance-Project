<?php
// auth/register.php
session_start();

if (isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit;
}

require_once '../config/database.php';

$error = '';
$success = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $roll_number = trim($_POST['roll_number'] ?? '');
    
    if (empty($first_name) || empty($last_name) || empty($email) || empty($password) || empty($roll_number)) {
        $error = 'Please fill in all fields.';
    } else {
        try {
            $pdo->beginTransaction();

            // Check if email exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->rowCount() > 0) {
                throw new Exception("Email already registered.");
            }

            // Check if roll number exists
            $stmt = $pdo->prepare("SELECT id FROM students WHERE roll_number = ?");
            $stmt->execute([$roll_number]);
            if ($stmt->rowCount() > 0) {
                throw new Exception("Roll number already registered.");
            }

            // Insert into users
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (email, password_hash, role) VALUES (?, ?, 'student')");
            $stmt->execute([$email, $password_hash]);
            $user_id = $pdo->lastInsertId();

            // Insert into students
            $stmt = $pdo->prepare("INSERT INTO students (id, roll_number, first_name, last_name) VALUES (?, ?, ?, ?)");
            $stmt->execute([$user_id, $roll_number, $first_name, $last_name]);

            $pdo->commit();
            $success = "Registration successful! You can now login.";
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Campus Nova</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" id="theme-stylesheet" href="../assets/css/dark-mode.css">
    <style>
        body, html {
            height: 100%;
            margin: 0;
            background: var(--bg-main);
        }

        .login-wrapper {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            background: radial-gradient(circle at top right, #e0f2fe 0%, #f8fafc 100%);
        }

        .dark-mode .login-wrapper {
            background: radial-gradient(circle at top right, #1e293b 0%, #0f172a 100%);
        }

        .login-card {
            width: 100%;
            max-width: 500px;
            padding: 3rem 2.5rem;
            border-radius: 20px;
            background-color: var(--glass-bg);
            backdrop-filter: blur(15px);
            box-shadow: 0 25px 45px rgba(0, 0, 0, 0.1);
            border: 1px solid var(--glass-border);
        }

        .form-control {
            background-color: rgba(255, 255, 255, 0.5);
            border: 1px solid var(--border-color);
            padding: 0.85rem 1rem;
            border-radius: 10px;
            color: var(--text-primary);
        }

        .dark-mode .form-control {
            background-color: rgba(0, 0, 0, 0.2);
            color: white;
        }

        .form-control:focus {
            box-shadow: 0 0 0 0.25rem rgba(14, 165, 233, 0.25);
            border-color: var(--primary);
        }
    </style>
</head>
<body>
    <div class="login-wrapper">
        <div class="login-card fade-in-up">
            <div class="text-center mb-4">
                <h2 style="font-family: 'Poppins', sans-serif; font-weight: 800; color: var(--primary);">
                    <i class="bi bi-hexagon-fill me-2"></i>Campus Nova
                </h2>
                <p class="text-secondary">Student Registration</p>
            </div>
            
            <?php if(!empty($error)): ?>
                <div class="alert alert-danger" role="alert" style="border-radius: 10px;">
                    <i class="bi bi-exclamation-circle me-2"></i> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <?php if(!empty($success)): ?>
                <div class="alert alert-success" role="alert" style="border-radius: 10px;">
                    <i class="bi bi-check-circle me-2"></i> <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label text-secondary fw-bold small">First Name</label>
                        <input type="text" name="first_name" class="form-control" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label text-secondary fw-bold small">Last Name</label>
                        <input type="text" name="last_name" class="form-control" required>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label text-secondary fw-bold small">Roll Number</label>
                    <input type="text" name="roll_number" class="form-control" required>
                </div>

                <div class="mb-3">
                    <label class="form-label text-secondary fw-bold small">Email Address</label>
                    <input type="email" name="email" class="form-control" required>
                </div>
                
                <div class="mb-4">
                    <label class="form-label text-secondary fw-bold small">Password</label>
                    <input type="password" name="password" class="form-control" required>
                </div>
                
                <button type="submit" class="btn btn-gradient w-100 py-2 fs-5">Register</button>
                
                <div class="mt-4 text-center">
                    <p class="text-secondary small mb-0">Already have an account? <a href="login.php" class="text-primary text-decoration-none fw-bold">Login here</a></p>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
