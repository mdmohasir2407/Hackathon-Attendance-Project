<?php
// index.php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Campus Nova - Smart Education Platform</title>
    <!-- Fonts: Google Fonts (Inter & Poppins) -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&family=Poppins:wght@500;600;700;800&display=swap" rel="stylesheet">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    
    <style>
        :root {
            --primary-blue: #0d6efd;
            --secondary-blue: #0b5ed7;
            --light-blue: #e0f2fe;
            --gradient-start: #0ea5e9;
            --gradient-end: #3b82f6;
            --text-dark: #0f172a;
            --text-muted: #64748b;
            --glass-bg: rgba(255, 255, 255, 0.7);
            --glass-border: rgba(255, 255, 255, 0.5);
        }

        body {
            font-family: 'Inter', sans-serif;
            color: var(--text-dark);
            background-color: #f8fafc;
            overflow-x: hidden;
            scroll-behavior: smooth;
        }

        h1, h2, h3, h4, h5, h6, .navbar-brand {
            font-family: 'Poppins', sans-serif;
        }

        /* Glassmorphism Utilities */
        .glass-card {
            background: var(--glass-bg);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid var(--glass-border);
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .glass-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
        }

        /* Gradient Text */
        .text-gradient {
            background: linear-gradient(135deg, var(--gradient-start), var(--gradient-end));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        /* NAVBAR */
        .navbar {
            background: rgba(255, 255, 255, 0.85) !important;
            backdrop-filter: blur(15px);
            border-bottom: 1px solid rgba(0,0,0,0.05);
            padding: 1rem 0;
            transition: all 0.3s ease;
        }
        
        .navbar-brand {
            font-weight: 800;
            font-size: 1.5rem;
            color: var(--text-dark) !important;
        }

        .nav-link {
            font-weight: 500;
            color: var(--text-muted) !important;
            margin: 0 10px;
            transition: color 0.3s ease;
        }

        .nav-link:hover {
            color: var(--primary-blue) !important;
        }

        .btn-gradient {
            background: linear-gradient(135deg, var(--gradient-start), var(--gradient-end));
            color: white;
            border: none;
            border-radius: 50px;
            padding: 0.6rem 1.5rem;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(14, 165, 233, 0.3);
        }

        .btn-gradient:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(14, 165, 233, 0.4);
            color: white;
        }

        /* HERO SECTION */
        .hero-section {
            padding: 120px 0 80px 0;
            background: radial-gradient(circle at top right, #e0f2fe 0%, #f8fafc 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            position: relative;
        }

        /* Ambient glowing orbs */
        .orb {
            position: absolute;
            border-radius: 50%;
            filter: blur(80px);
            z-index: 0;
        }
        .orb-1 { width: 400px; height: 400px; background: rgba(56, 189, 248, 0.3); top: -100px; right: -100px; animation: float 6s infinite alternate ease-in-out; }
        .orb-2 { width: 300px; height: 300px; background: rgba(129, 140, 248, 0.3); bottom: -50px; left: -50px; animation: float 8s infinite alternate-reverse ease-in-out; }
        
        @keyframes float {
            0% { transform: translate(0, 0); }
            100% { transform: translate(30px, 40px); }
        }

        .hero-content {
            position: relative;
            z-index: 1;
        }

        .hero-title {
            font-size: 4rem;
            font-weight: 800;
            line-height: 1.1;
            margin-bottom: 1.5rem;
        }

        .hero-desc {
            font-size: 1.25rem;
            color: var(--text-muted);
            margin-bottom: 2.5rem;
            line-height: 1.6;
        }

        .social-proof {
            display: flex;
            align-items: center;
            margin-top: 2rem;
            gap: 15px;
        }

        .avatars img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            border: 3px solid white;
            margin-left: -15px;
        }
        .avatars img:first-child { margin-left: 0; }

        /* DASHBOARD MOCKUP */
        .dashboard-mockup {
            background: rgba(255,255,255,0.9);
            border-radius: 20px;
            padding: 1.5rem;
            box-shadow: 0 25px 50px rgba(0,0,0,0.1);
            border: 1px solid rgba(255,255,255,0.6);
            backdrop-filter: blur(10px);
            position: relative;
            z-index: 1;
            transform: perspective(1000px) rotateY(-5deg) rotateX(5deg);
            transition: transform 0.5s ease;
            animation: float-mockup 6s infinite alternate ease-in-out;
        }

        @keyframes float-mockup {
            0% { transform: perspective(1000px) rotateY(-5deg) rotateX(5deg) translateY(0); }
            100% { transform: perspective(1000px) rotateY(-5deg) rotateX(5deg) translateY(-20px); }
        }

        .dashboard-mockup:hover {
            transform: perspective(1000px) rotateY(0deg) rotateX(0deg);
            animation-play-state: paused;
        }

        .mock-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            border-bottom: 1px solid #f1f5f9;
            padding-bottom: 1rem;
        }

        .mock-stat-card {
            background: #f8fafc;
            border-radius: 12px;
            padding: 1rem;
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 1rem;
            transition: all 0.3s;
        }
        .mock-stat-card:hover { background: #f1f5f9; transform: scale(1.02); }

        .mock-stat-icon {
            width: 40px; height: 40px;
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.2rem;
            background: #e0f2fe;
            color: var(--primary-blue);
        }

        .mock-chart {
            height: 120px;
            background: linear-gradient(180deg, #e0f2fe 0%, rgba(255,255,255,0) 100%);
            border-radius: 12px;
            border-bottom: 2px solid var(--primary-blue);
            position: relative;
            margin-bottom: 1.5rem;
        }
        .mock-chart-line {
            position: absolute;
            bottom: 0; left: 0; right: 0; height: 100%;
            background-image: url('data:image/svg+xml;utf8,<svg viewBox="0 0 100 30" xmlns="http://www.w3.org/2000/svg"><path d="M0,30 L10,20 L30,25 L50,10 L70,15 L90,5 L100,10 L100,30 Z" fill="%230ea5e9" opacity="0.2"/></svg>');
            background-size: cover;
            background-position: bottom;
        }

        .mock-progress-bar {
            height: 8px;
            background: #e2e8f0;
            border-radius: 4px;
            margin-top: 8px;
            overflow: hidden;
        }
        .mock-progress-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--gradient-start), var(--gradient-end));
            width: 75%;
            border-radius: 4px;
        }

        /* STATISTICS SECTION */
        .stats-section {
            padding: 60px 0;
            background: white;
        }
        .stat-item {
            text-align: center;
            padding: 2rem;
        }
        .stat-number {
            font-size: 3rem;
            font-weight: 800;
            color: var(--primary-blue);
            margin-bottom: 0.5rem;
        }

        /* FEATURES SECTION */
        .features-section {
            padding: 100px 0;
            background: #f8fafc;
        }
        .feature-icon-wrapper {
            width: 60px; height: 60px;
            border-radius: 15px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.8rem;
            background: linear-gradient(135deg, #e0f2fe, #bae6fd);
            color: var(--primary-blue);
            margin-bottom: 1.5rem;
        }

        /* HOW IT WORKS SECTION */
        .workflow-section {
            padding: 100px 0;
            background: white;
        }
        .workflow-step {
            text-align: center;
            position: relative;
            padding: 20px;
        }
        .step-icon {
            width: 80px; height: 80px;
            border-radius: 50%;
            background: white;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            display: flex; align-items: center; justify-content: center;
            font-size: 2rem;
            color: var(--primary-blue);
            margin: 0 auto 1.5rem auto;
            position: relative;
            z-index: 2;
            border: 2px solid #e0f2fe;
        }
        .step-arrow {
            position: absolute;
            top: 60px;
            right: -25%;
            width: 50%;
            height: 2px;
            background: dashed 2px #cbd5e1;
            z-index: 1;
        }
        @media(max-width: 991px) {
            .step-arrow { display: none; }
            .workflow-step { margin-bottom: 2rem; }
        }

        /* CTA SECTION */
        .cta-section {
            padding: 100px 0;
            background: linear-gradient(135deg, #0ea5e9, #3b82f6);
            color: white;
            position: relative;
            overflow: hidden;
        }
        .cta-content {
            position: relative;
            z-index: 2;
        }
        .cta-section::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background: url('data:image/svg+xml;utf8,<svg viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg"><circle cx="20" cy="20" r="40" fill="white" opacity="0.1"/><circle cx="80" cy="80" r="50" fill="white" opacity="0.1"/></svg>');
            background-size: cover;
            z-index: 1;
        }

        /* FOOTER */
        footer {
            background: #0f172a;
            color: #94a3b8;
            padding: 80px 0 20px 0;
        }
        .footer-logo {
            font-family: 'Poppins', sans-serif;
            font-weight: 800;
            font-size: 1.5rem;
            color: white;
            margin-bottom: 1rem;
            display: inline-block;
        }
        footer h5 {
            color: white;
            font-weight: 600;
            margin-bottom: 1.5rem;
        }
        footer ul { list-style: none; padding: 0; }
        footer ul li { margin-bottom: 0.8rem; }
        footer a { color: #94a3b8; text-decoration: none; transition: color 0.3s; }
        footer a:hover { color: white; }
        .social-icons a {
            display: inline-flex;
            align-items: center; justify-content: center;
            width: 40px; height: 40px;
            border-radius: 50%;
            background: rgba(255,255,255,0.1);
            color: white;
            margin-right: 10px;
            transition: all 0.3s;
        }
        .social-icons a:hover { background: var(--primary-blue); transform: translateY(-3px); }
        .footer-bottom {
            border-top: 1px solid rgba(255,255,255,0.1);
            margin-top: 40px;
            padding-top: 20px;
            text-align: center;
            font-size: 0.9rem;
        }

        /* Animation Classes */
        .fade-in-up {
            opacity: 0;
            transform: translateY(30px);
            transition: opacity 0.8s ease-out, transform 0.8s ease-out;
        }
        .fade-in-up.visible {
            opacity: 1;
            transform: translateY(0);
        }
    </style>
</head>
<body>
    <!-- HERO SECTION / PORTAL -->
    <section class="hero-section" id="home" style="padding: 0; display: flex; align-items: center; justify-content: center;">
        <div class="orb orb-1"></div>
        <div class="orb orb-2"></div>
        <div class="container hero-content text-center">
            
            <div class="fade-in-up visible" style="max-width: 600px; margin: 0 auto; background: var(--glass-bg); padding: 4rem 2rem; border-radius: 20px; border: 1px solid var(--glass-border); backdrop-filter: blur(15px); box-shadow: 0 20px 40px rgba(0,0,0,0.05);">
                
                <div class="mb-4">
                    <i class="bi bi-hexagon-fill" style="font-size: 4rem; color: var(--primary-blue);"></i>
                </div>
                
                <h1 class="hero-title mb-3" style="font-size: 3rem;">Campus Nova</h1>
                <p class="hero-desc mb-5" style="font-size: 1.1rem;">Smart Education Platform</p>
                
                <div class="d-flex flex-column gap-3 align-items-center">
                    <a href="auth/login.php" class="btn btn-gradient btn-lg w-100" style="max-width: 300px; padding: 1rem;">
                        <i class="bi bi-box-arrow-in-right me-2"></i> Login to Portal
                    </a>
                    <a href="auth/register.php" class="btn btn-light btn-lg w-100 shadow-sm" style="max-width: 300px; color: var(--text-dark); border: 1px solid #e2e8f0; padding: 1rem;">
                        <i class="bi bi-person-plus me-2"></i> Create an Account
                    </a>
                </div>
                
            </div>
            
            <div class="mt-5 fade-in-up visible" style="transition-delay: 0.2s;">
                <p class="text-muted small">&copy; <?php echo date('Y'); ?> Campus Nova. All Rights Reserved.</p>
            </div>
            
        </div>
    </section>

    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const animatedElements = document.querySelectorAll('.fade-in-up');
            setTimeout(() => {
                animatedElements.forEach(el => el.classList.add('visible'));
            }, 100);
        });
    </script>
</body>
</html>
