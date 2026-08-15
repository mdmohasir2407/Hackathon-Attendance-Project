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
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/animations.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/animations.css">
    <link rel="stylesheet" id="theme-stylesheet" href="../assets/css/dark-mode.css">
    <link rel="stylesheet" href="../assets/css/premium-ui.css">
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
            position: relative;
        }

        .login-card {
            width: 100%;
            max-width: 500px;
            padding: 3rem 2.5rem;
            z-index: 1;
        }

        .form-floating .form-control {
            background-color: var(--premium-glass);
            border: 1px solid var(--premium-border);
            border-radius: 12px;
            color: var(--premium-text);
            backdrop-filter: blur(10px);
            transition: all 0.3s ease;
        }

        .form-floating label {
            color: var(--premium-text-muted);
            transition: all 0.3s ease;
        }

        .form-floating .form-control:focus {
            box-shadow: 0 0 15px var(--premium-glow), inset 0 0 10px var(--premium-glow);
            border-color: var(--premium-accent);
            background-color: rgba(255, 255, 255, 0.1);
        }
        
        body.dark-mode .form-floating .form-control:focus {
            background-color: rgba(0, 0, 0, 0.3);
        }
    </style>
</head>
<body>
    <div class="login-wrapper">
        <a href="../index.php" class="btn magnetic-btn floating-3d hover-bounce-icon position-absolute" style="top: 30px; left: 30px; z-index: 10; border-radius: 50px; padding: 0.6rem 1.2rem; background: var(--premium-glass); border: 1px solid var(--premium-border); color: var(--premium-text); box-shadow: 0 5px 15px rgba(0,0,0,0.1); backdrop-filter: blur(10px); transition: all 0.3s ease;">
            <i class="bi bi-arrow-left me-2" style="display: inline-block;"></i> Back to Home
        </a>
        <div class="mesh-bg"></div>
        <div class="login-card premium-glass-card tilt-card animate-on-scroll fade-in-up">
            <div class="glare-effect"></div>
            
            <div class="text-center mb-4 hover-bounce-icon">
                <i class="bi bi-hexagon-fill" style="font-size: 3rem; color: var(--premium-accent); filter: drop-shadow(0 0 15px var(--premium-accent));"></i>
                <h2 class="mt-3 fw-bold neon-text premium-text">Join Campus Nova</h2>
                <p class="premium-text-muted">Student Registration</p>
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
                        <div class="form-floating hover-bounce-icon">
                            <input type="text" name="first_name" class="form-control" id="firstName" placeholder="First Name" required>
                            <label for="firstName"><i class="bi bi-person me-1"></i> First Name</label>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <div class="form-floating hover-bounce-icon">
                            <input type="text" name="last_name" class="form-control" id="lastName" placeholder="Last Name" required>
                            <label for="lastName"><i class="bi bi-person me-1"></i> Last Name</label>
                        </div>
                    </div>
                </div>

                <div class="form-floating mb-3 hover-bounce-icon">
                    <input type="text" name="roll_number" class="form-control" id="rollNumber" placeholder="Roll Number" required>
                    <label for="rollNumber"><i class="bi bi-card-text me-1"></i> Roll Number</label>
                </div>

                <div class="form-floating mb-3 hover-bounce-icon">
                    <input type="email" name="email" class="form-control" id="email" placeholder="name@example.com" required>
                    <label for="email"><i class="bi bi-envelope me-1"></i> Email Address</label>
                </div>
                
                <div class="form-floating mb-4 hover-bounce-icon">
                    <input type="password" name="password" class="form-control" id="password" placeholder="Password" required>
                    <label for="password"><i class="bi bi-key me-1"></i> Password</label>
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
