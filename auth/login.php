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
        $stmt = $pdo->prepare("SELECT id, password_hash, role, profile_pic FROM users WHERE email = :email AND role = :role");
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
                $_SESSION['profile_pic'] = $user['profile_pic'] ?: 'assets/images/default-avatar.png';
                
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

        /* Perfectly Blended Slow Changing Gradient Background */
        .animated-bg {
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            z-index: 0;
            /* Distinct colors so the slow shifting is highly visible (Cyan -> Purple -> Pink -> Mint -> Cyan) */
            background: linear-gradient(-45deg, #7dd3fc, #c4b5fd, #f9a8d4, #a7f3d0, #7dd3fc);
            background-size: 400% 400%;
            animation: gradientBG 15s ease infinite;
            overflow: hidden;
            transition: opacity 0.5s ease;
        }

        body.dark-mode .animated-bg {
            opacity: 0; /* Hide in dark mode */
        }

        @keyframes gradientBG {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        /* 3D Glass Cells */
        .cell {
            position: absolute;
            border-radius: 50%;
            background: radial-gradient(circle at 30% 30%, rgba(255, 255, 255, 0.95), rgba(255, 255, 255, 0.4) 60%, rgba(255, 255, 255, 0.1) 90%);
            box-shadow: 
                inset 10px 10px 30px rgba(14, 165, 233, 0.3), 
                inset -10px -10px 30px rgba(139, 92, 246, 0.2), 
                10px 20px 30px rgba(0, 0, 0, 0.1); 
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.8);
            animation: cellFloat linear infinite alternate;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1;
        }

        /* College Core inside Cells */
        .cell i {
            font-size: 3.5rem;
            color: rgba(14, 165, 233, 0.8);
            filter: drop-shadow(0 5px 15px rgba(14, 165, 233, 0.4));
            animation: pulseIcon 4s infinite alternate;
        }

        /* Different cell sizes and positions */
        .c1 { width: 180px; height: 180px; top: 10%; left: 15%; animation-duration: 12s; }
        .c2 { width: 280px; height: 280px; bottom: 5%; right: 10%; animation-duration: 18s; animation-delay: -5s; }
        .c2 i { font-size: 6rem; color: rgba(139, 92, 246, 0.7); filter: drop-shadow(0 5px 20px rgba(139, 92, 246, 0.4)); }
        .c3 { width: 120px; height: 120px; top: 40%; right: 25%; animation-duration: 9s; animation-delay: -2s; }
        .c3 i { font-size: 2.5rem; color: rgba(16, 185, 129, 0.7); }
        .c4 { width: 200px; height: 200px; bottom: 15%; left: 10%; animation-duration: 15s; animation-delay: -7s; }
        .c4 i { font-size: 4rem; color: rgba(245, 158, 11, 0.7); }
        .c5 { width: 140px; height: 140px; top: 20%; left: 50%; animation-duration: 11s; animation-delay: -4s; }

        @keyframes cellFloat {
            0% { transform: translateY(0) translateX(0) scale(1) rotate(0deg); }
            100% { transform: translateY(-40px) translateX(30px) scale(1.05) rotate(15deg); }
        }
        @keyframes pulseIcon {
            0% { transform: scale(0.9); opacity: 0.7; }
            100% { transform: scale(1.1); opacity: 1; }
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
                <i class="bi bi-hexagon-fill" style="font-size: 3rem; color: var(--premium-accent); filter: drop-shadow(0 0 15px var(--premium-accent));"></i>
                <h2 class="mt-3 fw-bold neon-text premium-text">Welcome Back</h2>
                <p class="premium-text-muted">Sign in to Campus Nova</p>
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
                
                <div class="form-floating mb-4 hover-bounce-icon">
                    <input type="password" name="password" class="form-control" id="password" placeholder="Password" required>
                    <label for="password"><i class="bi bi-key me-1"></i> Password</label>
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
