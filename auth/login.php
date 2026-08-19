<?php
// auth/login.php
session_start();

// If already logged in, redirect to dashboard based on role
if (isset($_SESSION['user_id']) && isset($_SESSION['role'])) {
    $requested_role = $_GET['role'] ?? null;
    if ($requested_role && $requested_role !== $_SESSION['role']) {
        // User wants to login as a different role, destroy current session
        session_unset();
        session_destroy();
        session_start();
    } else {
        $role = $_SESSION['role'];
        header("Location: ../{$role}/dashboard.php");
        exit;
    }
}

require_once '../config/database.php';

$error = '';
$selected_role = $_POST['role'] ?? $_GET['role'] ?? '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? '';

    if (empty($email) || empty($password) || empty($role)) {
        $error = 'Please fill in all fields and select a role.';
    } else {
        $stmt = $pdo->prepare("SELECT id, password_hash, role, profile_pic FROM users WHERE email = :email AND role = :role");
        $stmt->bindParam(':email', $email, PDO::PARAM_STR);
        $stmt->bindParam(':role', $role, PDO::PARAM_STR);
        $stmt->execute();
        
        $user = $stmt->fetch();
        if ($user) {
            if (password_verify($password, $user['password_hash'])) {
                // Password is correct, start a new session
                session_regenerate_id(true);
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['profile_pic'] = $user['profile_pic'] ?? 'assets/images/default-avatar.png';
                
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Login - Campus Nova</title>
    <!-- Modern Typography -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
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
            font-family: 'Plus Jakarta Sans', sans-serif;
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
            max-width: 450px;
            padding: 3rem 2.5rem;
            z-index: 1;
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

        /* --- Premium Input Boxes (Optimized for Performance) --- */
        .form-floating .form-control {
            background-color: rgba(255, 255, 255, 0.85); /* Solid enough to avoid needing blur */
            border: 2px solid rgba(14, 165, 233, 0.15);
            border-radius: 16px;
            color: #1e293b;
            font-weight: 600;
            font-size: 1.05rem;
            /* Removed backdrop-filter because overlapping blurs cause massive lag on focus */
            transition: border-color 0.3s ease, box-shadow 0.3s ease, transform 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            height: calc(4rem + 2px);
            padding-left: 1.2rem;
            box-shadow: inset 0 2px 5px rgba(0,0,0,0.02);
        }

        body.dark-mode .form-floating .form-control {
            background-color: rgba(15, 23, 42, 0.9);
            border-color: rgba(255, 255, 255, 0.1);
            color: #f8fafc;
        }

        .form-floating label {
            color: #64748b;
            font-weight: 500;
            padding-left: 1.2rem;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
        }

        .form-floating .form-control:focus {
            box-shadow: 0 0 0 5px rgba(14, 165, 233, 0.15), 0 10px 20px rgba(0,0,0,0.05);
            border-color: #0ea5e9;
            background-color: #ffffff;
            transform: translateY(-3px);
        }

        body.dark-mode .form-floating .form-control:focus {
            background-color: rgba(30, 41, 59, 0.9);
            border-color: #3b82f6;
            box-shadow: 0 0 0 5px rgba(59, 130, 246, 0.2), 0 10px 20px rgba(0,0,0,0.2);
        }

        /* Float Label Adjustments */
        .form-floating .form-control:focus ~ label,
        .form-floating .form-control:not(:placeholder-shown) ~ label {
            transform: scale(0.85) translateY(-1rem) translateX(-0.5rem);
            color: #0ea5e9;
            font-weight: 700;
        }



        .login-wrapper {
            position: relative;
            z-index: 1; /* Keep above the background */
        }
        
        .login-card {
            pointer-events: auto; /* Re-enable clicks for the form */
        }

        /* --- Premium Button Hover Animations --- */
        .btn {
            transition: all 0.4s cubic-bezier(0.25, 0.8, 0.25, 1);
            position: relative;
            overflow: hidden;
            z-index: 1; /* For pseudo-elements */
        }

        /* Sign In Button Glow & Shine */
        .btn-gradient {
            background: linear-gradient(45deg, #0ea5e9, #8b5cf6) !important;
            border: none !important;
            color: white !important;
            font-weight: 700;
            letter-spacing: 0.5px;
            border-radius: 16px !important;
        }
        .btn-gradient:hover {
            transform: translateY(-4px) scale(1.02);
            box-shadow: 0 12px 25px rgba(14, 165, 233, 0.5), 0 0 20px rgba(139, 92, 246, 0.4) !important;
        }
        .btn-gradient::after {
            content: '';
            position: absolute;
            top: 0; left: -100%;
            width: 50%; height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.6), transparent);
            transform: skewX(-25deg);
            transition: all 0.6s ease;
            z-index: 1;
        }
        .btn-gradient:hover::after {
            left: 150%;
        }

        /* Google/Back Buttons */
        .btn-outline-secondary:hover, .btn.magnetic-btn:hover {
            transform: translateY(-3px);
            background: rgba(14, 165, 233, 0.1) !important;
            border-color: #0ea5e9 !important;
            color: #0ea5e9 !important;
            box-shadow: 0 8px 20px rgba(14, 165, 233, 0.2);
        }

        /* Role Buttons Enhancements */
        .role-btn {
            transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275); /* Bouncy transition */
        }
        .role-btn:hover {
            transform: translateY(-6px);
            background: rgba(14, 165, 233, 0.1);
            border-color: #0ea5e9;
            box-shadow: 0 10px 20px rgba(14, 165, 233, 0.25);
            color: #0ea5e9;
        }
        .role-btn:hover i {
            transform: scale(1.2);
            transition: transform 0.3s ease;
        }
        
        .role-btn.active {
            animation: bounceIn 0.5s ease forwards;
            background: linear-gradient(45deg, #0ea5e9, #3b82f6);
            color: white;
            border: none;
            box-shadow: 0 5px 15px rgba(14, 165, 233, 0.4);
        }

        @keyframes bounceIn {
            0% { transform: scale(0.9); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
    </style>
</head>
<body>
    <div class="animated-bg">
        <!-- 3D Research Cells with College Data Cores -->
        <div class="cell c1"><i class="bi bi-laptop"></i></div>
        <div class="cell c2"><i class="bi bi-mortarboard-fill"></i></div>
        <div class="cell c3"><i class="bi bi-check2-circle"></i></div>
        <div class="cell c4"><i class="bi bi-calendar2-week"></i></div>
        <div class="cell c5"><i class="bi bi-book"></i></div>
    </div>
    <div class="login-wrapper">
        <a href="../index.php" class="btn magnetic-btn floating-3d hover-bounce-icon position-absolute" style="top: 30px; left: 30px; z-index: 10; border-radius: 50px; padding: 0.6rem 1.2rem; background: var(--premium-glass); border: 1px solid var(--premium-border); color: var(--premium-text); box-shadow: 0 5px 15px rgba(0,0,0,0.1); backdrop-filter: blur(10px); transition: all 0.3s ease;">
            <i class="bi bi-arrow-left me-2" style="display: inline-block;"></i> Back to Home
        </a>
        <div class="login-card premium-glass-card no-expand tilt-card animate-on-scroll fade-in-up">
            <div class="glare-effect"></div>
            
            <div class="text-center mb-4 hover-bounce-icon">
                <i class="bi bi-hexagon-fill" style="font-size: 3rem; color: #0ea5e9; filter: drop-shadow(0 0 15px rgba(14, 165, 233, 0.5));"></i>
                <h2 class="mt-3 fw-bold" style="background: linear-gradient(135deg, #0ea5e9, #8b5cf6); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">Welcome Back</h2>
                <p class="text-secondary fw-bold">Sign in to Campus Nova</p>
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

                <div class="form-floating mb-3 hover-bounce-icon">
                    <input type="email" name="email" class="form-control" id="email" placeholder="name@example.com" required value="<?php echo htmlspecialchars($email ?? ''); ?>">
                    <label for="email"><i class="bi bi-envelope me-1"></i> Email Address</label>
                </div>
                
                <div class="form-floating mb-4 hover-bounce-icon position-relative">
                    <input type="password" name="password" class="form-control pe-5" id="password" placeholder="Password" required>
                    <label for="password"><i class="bi bi-key me-1"></i> Password</label>
                    <button type="button" class="btn position-absolute top-50 end-0 translate-middle-y me-2 p-0 border-0 text-muted" id="togglePassword" style="z-index: 5;" tabindex="-1">
                        <i class="bi bi-eye-slash fs-5" id="toggleIcon"></i>
                    </button>
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

        // Password Toggle Logic
        const togglePassword = document.getElementById('togglePassword');
        const password = document.getElementById('password');
        const toggleIcon = document.getElementById('toggleIcon');

        if (togglePassword && password && toggleIcon) {
            togglePassword.addEventListener('click', function () {
                const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
                password.setAttribute('type', type);
                toggleIcon.classList.toggle('bi-eye');
                toggleIcon.classList.toggle('bi-eye-slash');
            });
        }
    </script>
</body>
</html>
