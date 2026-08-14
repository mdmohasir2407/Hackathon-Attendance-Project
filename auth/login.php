<?php
// auth/login.php
session_start();

// If already logged in, redirect to dashboard based on role
if (isset($_SESSION['user_id']) && isset($_SESSION['role'])) {
    $role = $_SESSION['role'];
    header("Location: ../{$role}/dashboard.php");
    exit;
}

require_once '../config/database.php';

$error = '';
$selected_role = $_POST['role'] ?? '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? '';

    if (empty($email) || empty($password) || empty($role)) {
        $error = 'Please fill in all fields and select a role.';
    } else {
        $stmt = $pdo->prepare("SELECT id, password_hash, role FROM users WHERE email = :email AND role = :role");
        $stmt->bindParam(':email', $email, PDO::PARAM_STR);
        $stmt->bindParam(':role', $role, PDO::PARAM_STR);
        $stmt->execute();
        
        if ($stmt->rowCount() == 1) {
            $user = $stmt->fetch();
            if (password_verify($password, $user['password_hash'])) {
                // Password is correct, start a new session
                session_regenerate_id(true);
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['role'] = $user['role'];
                
                // Fetch name based on role and log activity
                $name = 'User';
                if ($user['role'] === 'admin') {
                    $s = $pdo->prepare("SELECT first_name, last_name FROM admins WHERE id = ?");
                    $s->execute([$user['id']]);
                    $row = $s->fetch();
                    if($row) $name = $row['first_name'] . ' ' . $row['last_name'];
                    $_SESSION['name'] = $name;
                    log_activity($pdo, $user['id'], "Logged in");
                    header("Location: ../admin/dashboard.php");
                } elseif ($user['role'] === 'teacher') {
                    $s = $pdo->prepare("SELECT first_name, last_name FROM teachers WHERE id = ?");
                    $s->execute([$user['id']]);
                    $row = $s->fetch();
                    if($row) $name = $row['first_name'] . ' ' . $row['last_name'];
                    $_SESSION['name'] = $name;
                    log_activity($pdo, $user['id'], "Logged in");
                    header("Location: ../teacher/dashboard.php");
                } elseif ($user['role'] === 'student') {
                    $s = $pdo->prepare("SELECT first_name, last_name FROM students WHERE id = ?");
                    $s->execute([$user['id']]);
                    $row = $s->fetch();
                    if($row) $name = $row['first_name'] . ' ' . $row['last_name'];
                    $_SESSION['name'] = $name;
                    log_activity($pdo, $user['id'], "Logged in");
                    header("Location: ../student/dashboard.php");
                }
                exit;
            } else {
                $error = 'Invalid email or password.';
            }
        } else {
            $error = 'Invalid email, password, or incorrect role selected.';
        }
    }
}

function log_activity($pdo, $user_id, $action) {
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
    $stmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action, ip_address) VALUES (?, ?, ?)");
    $stmt->execute([$user_id, $action, $ip]);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Campus Nova</title>
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
            height: 100vh;
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
            max-width: 450px;
            padding: 3rem 2.5rem;
            border-radius: 20px;
            background-color: var(--glass-bg);
            backdrop-filter: blur(15px);
            box-shadow: 0 25px 45px rgba(0, 0, 0, 0.1);
            border: 1px solid var(--glass-border);
        }

        .role-selector {
            display: flex;
            gap: 10px;
            margin-bottom: 1.5rem;
        }

        .role-btn {
            flex: 1;
            padding: 10px;
            border: 1px solid var(--border-color);
            border-radius: 10px;
            background: transparent;
            color: var(--text-secondary);
            font-weight: 500;
            transition: all 0.3s;
        }

        .role-btn:hover {
            background: rgba(14, 165, 233, 0.1);
            color: var(--primary);
        }

        .role-btn.active {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
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
                <p class="text-secondary">Please select your role and login.</p>
            </div>
            
            <?php if(!empty($error)): ?>
                <div class="alert alert-danger" role="alert" style="border-radius: 10px;">
                    <i class="bi bi-exclamation-circle me-2"></i> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" id="loginForm">
                
                <div class="role-selector">
                    <button type="button" class="role-btn <?php echo ($selected_role == 'student' || empty($selected_role)) ? 'active' : ''; ?>" data-role="student">
                        <i class="bi bi-mortarboard d-block mb-1 fs-5"></i> Student
                    </button>
                    <button type="button" class="role-btn <?php echo ($selected_role == 'teacher') ? 'active' : ''; ?>" data-role="teacher">
                        <i class="bi bi-person-video3 d-block mb-1 fs-5"></i> Teacher
                    </button>
                    <button type="button" class="role-btn <?php echo ($selected_role == 'admin') ? 'active' : ''; ?>" data-role="admin">
                        <i class="bi bi-shield-lock d-block mb-1 fs-5"></i> Admin
                    </button>
                </div>
                
                <input type="hidden" name="role" id="roleInput" value="<?php echo htmlspecialchars($selected_role ?: 'student'); ?>">

                <div class="mb-3">
                    <label for="email" class="form-label text-secondary fw-bold small">Email Address</label>
                    <input type="email" name="email" class="form-control" id="email" placeholder="Enter your email" required value="<?php echo htmlspecialchars($email ?? ''); ?>">
                </div>
                
                <div class="mb-4">
                    <label for="password" class="form-label text-secondary fw-bold small">Password</label>
                    <div class="position-relative">
                        <input type="password" name="password" class="form-control" id="password" placeholder="Enter your password" required>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-gradient w-100 py-2 fs-5">Sign In</button>
                
                <div class="mt-4 text-center">
                    <p class="text-secondary small mb-0">Don't have an account? <a href="register.php" class="text-primary text-decoration-none fw-bold">Register here</a></p>
                </div>
            </form>
            
            <div class="text-center mt-4">
                <button type="button" class="btn btn-sm btn-outline-secondary rounded-circle" id="theme-toggle" style="width: 35px; height: 35px;">
                    <i class="bi bi-moon"></i>
                </button>
            </div>
        </div>
    </div>

    <script>
        // Role Selection Logic
        const roleBtns = document.querySelectorAll('.role-btn');
        const roleInput = document.getElementById('roleInput');

        roleBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                roleBtns.forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                roleInput.value = btn.getAttribute('data-role');
            });
        });

        // Theme Toggle Logic
        const themeToggleBtn = document.getElementById('theme-toggle');
        const body = document.body;

        const currentTheme = localStorage.getItem('theme');
        if (currentTheme === 'dark') {
            body.classList.add('dark-mode');
            themeToggleBtn.innerHTML = '<i class="bi bi-sun"></i>';
        }

        themeToggleBtn.addEventListener('click', function() {
            body.classList.toggle('dark-mode');
            let theme = 'light';
            if (body.classList.contains('dark-mode')) {
                theme = 'dark';
                themeToggleBtn.innerHTML = '<i class="bi bi-sun"></i>';
            } else {
                themeToggleBtn.innerHTML = '<i class="bi bi-moon"></i>';
            }
            localStorage.setItem('theme', theme);
        });
    </script>
</body>
</html>
