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
    
    <!-- Custom Animations -->
    <link rel="stylesheet" href="assets/css/animations.css">
    
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
            background: transparent;
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
            font-size: 5rem;
            font-weight: 800;
            line-height: 1.1;
            margin-bottom: 1.5rem;
            text-transform: uppercase;
            letter-spacing: -0.05em;
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
            cursor: pointer;
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
        <div class="mesh-bg"></div>
        <div class="orb orb-1" style="background: rgba(0, 240, 255, 0.4);"></div>
        <div class="orb orb-2" style="background: rgba(138, 43, 226, 0.4);"></div>
        
        <div class="container hero-content text-center floating-3d">
            
            <div class="fade-in-up visible premium-glass-card tilt-card" style="max-width: 700px; margin: 0 auto; padding: 5rem 3rem;">
                <div class="glare-effect"></div>
                <div class="mb-4 hover-bounce-icon">
                    <i class="bi bi-hexagon-fill" style="font-size: 4rem; color: var(--premium-accent); filter: drop-shadow(0 0 20px var(--premium-accent));"></i>
                </div>
                
                <h1 class="hero-title mb-3 neon-text premium-text">Campus Nova</h1>
                <p class="hero-desc mb-5 premium-text-muted" style="font-size: 1.2rem;">Next-Generation Smart Education Platform</p>
                
                <div class="d-flex flex-column gap-3 align-items-center">
                    <a href="auth/login.php" class="btn btn-gradient btn-lg w-100 magnetic-btn btn-ripple-container" style="max-width: 320px; padding: 1.2rem; font-size: 1.1rem;">
                        <i class="bi bi-box-arrow-in-right me-2"></i> Enter Portal
                    </a>
                    <a href="auth/register.php" class="btn btn-lg w-100 magnetic-btn btn-ripple-container mt-4" style="max-width: 320px; padding: 1.2rem; font-size: 1.1rem; border: none; background: rgba(255,255,255,0.7); color: black !important; font-weight: 600; box-shadow: 0 4px 15px rgba(0,0,0,0.05); border-radius: 50px;">
                        <i class="bi bi-person-plus me-2"></i> Join as Student
                    </a>
                </div>
                
            </div>
            
            <div class="mt-4 fade-in-up visible" style="transition-delay: 0.2s;">
                <p class="premium-text-muted small">&copy; <?php echo date('Y'); ?> Campus Nova. Designed for the Future.</p>
            </div>
            
        </div>
    </section>

    <!-- STATISTICS SECTION -->
    <section class="stats-section">
        <div class="container">
            <div class="row">
                <div class="col-md-3 col-sm-6 stat-item fade-in-up">
                    <div class="stat-number">10k+</div>
                    <div class="text-muted fw-bold">Active Students</div>
                </div>
                <div class="col-md-3 col-sm-6 stat-item fade-in-up" style="transition-delay: 0.1s;">
                    <div class="stat-number">500+</div>
                    <div class="text-muted fw-bold">Expert Teachers</div>
                </div>
                <div class="col-md-3 col-sm-6 stat-item fade-in-up" style="transition-delay: 0.2s;">
                    <div class="stat-number">99%</div>
                    <div class="text-muted fw-bold">Attendance Rate</div>
                </div>
                <div class="col-md-3 col-sm-6 stat-item fade-in-up" style="transition-delay: 0.3s;">
                    <div class="stat-number">24/7</div>
                    <div class="text-muted fw-bold">Smart Access</div>
                </div>
            </div>
        </div>
    </section>

    <!-- FEATURES SECTION -->
    <section class="features-section">
        <div class="container">
            <div class="text-center mb-5 fade-in-up">
                <h2 class="fw-bold" style="font-size: 2.5rem;">Why Choose Campus Nova?</h2>
                <p class="text-muted" style="max-width: 600px; margin: 0 auto;">Experience a seamless transition into the future of education with our cutting-edge tools designed for modern institutions.</p>
            </div>
            <div class="row g-4">
                <div class="col-md-4 fade-in-up">
                    <div class="card border-0 shadow-sm h-100 p-4" style="border-radius: 20px;">
                        <div class="feature-icon-wrapper">
                            <i class="bi bi-qr-code-scan"></i>
                        </div>
                        <h4 class="fw-bold mb-3">Smart QR Attendance</h4>
                        <p class="text-muted">Say goodbye to roll calls. Mark your attendance in seconds using secure, dynamic QR codes generated by your teachers.</p>
                    </div>
                </div>
                <div class="col-md-4 fade-in-up" style="transition-delay: 0.1s;">
                    <div class="card border-0 shadow-sm h-100 p-4" style="border-radius: 20px;">
                        <div class="feature-icon-wrapper">
                            <i class="bi bi-journal-check"></i>
                        </div>
                        <h4 class="fw-bold mb-3">AI-Driven Analytics</h4>
                        <p class="text-muted">Track your performance and attendance with beautiful charts. Identify your weak areas before the exams.</p>
                    </div>
                </div>
                <div class="col-md-4 fade-in-up" style="transition-delay: 0.2s;">
                    <div class="card border-0 shadow-sm h-100 p-4" style="border-radius: 20px;">
                        <div class="feature-icon-wrapper">
                            <i class="bi bi-file-earmark-check"></i>
                        </div>
                        <h4 class="fw-bold mb-3">Paperless Operations</h4>
                        <p class="text-muted">Apply for leaves, request gate passes, and submit assignments entirely online. Fast approvals and zero paper waste.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- HOW IT WORKS SECTION -->
    <section class="workflow-section">
        <div class="container">
            <div class="text-center mb-5 fade-in-up">
                <h2 class="fw-bold" style="font-size: 2.5rem;">How It Works</h2>
                <p class="text-muted">A simple, intuitive workflow for students and teachers.</p>
            </div>
            <div class="row mt-5">
                <div class="col-md-4 workflow-step fade-in-up">
                    <div class="step-icon"><i class="bi bi-1-circle"></i></div>
                    <div class="step-arrow"></div>
                    <h5 class="fw-bold">Login & Connect</h5>
                    <p class="text-muted small">Enter your portal securely to view your daily schedule and upcoming classes.</p>
                </div>
                <div class="col-md-4 workflow-step fade-in-up" style="transition-delay: 0.1s;">
                    <div class="step-icon"><i class="bi bi-2-circle"></i></div>
                    <div class="step-arrow"></div>
                    <h5 class="fw-bold">Scan & Attend</h5>
                    <p class="text-muted small">Use your mobile to scan the live QR code displayed by your teacher in the classroom.</p>
                </div>
                <div class="col-md-4 workflow-step fade-in-up" style="transition-delay: 0.2s;">
                    <div class="step-icon"><i class="bi bi-3-circle"></i></div>
                    <h5 class="fw-bold">Learn & Grow</h5>
                    <p class="text-muted small">Access study materials, take online tests, and view your detailed performance analytics.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA SECTION -->
    <section class="cta-section text-center">
        <div class="container cta-content fade-in-up">
            <h2 class="fw-bold mb-4" style="font-size: 3rem;">Ready to Transform Your Campus?</h2>
            <p class="lead mb-5" style="max-width: 600px; margin: 0 auto; opacity: 0.9;">Join thousands of students and educators already experiencing the power of Campus Nova.</p>
            <a href="auth/login.php" class="btn btn-light btn-lg px-5 py-3 fw-bold rounded-pill shadow" style="color: var(--primary-blue);">Get Started Now</a>
        </div>
    </section>

    <!-- FOOTER -->
    <footer>
        <div class="container">
            <div class="row fade-in-up">
                <div class="col-lg-4 mb-4">
                    <span class="footer-logo">Campus Nova</span>
                    <p class="small opacity-75">Next-generation education management system designed to make learning and administration seamless, paperless, and smart.</p>
                    <div class="social-icons mt-3">
                        <a href="#"><i class="bi bi-facebook"></i></a>
                        <a href="#"><i class="bi bi-twitter"></i></a>
                        <a href="#"><i class="bi bi-instagram"></i></a>
                        <a href="#"><i class="bi bi-linkedin"></i></a>
                    </div>
                </div>
                <div class="col-lg-2 offset-lg-2 col-md-4 mb-4">
                    <h5>Quick Links</h5>
                    <ul>
                        <li><a href="#">About Us</a></li>
                        <li><a href="#">Features</a></li>
                        <li><a href="#">Pricing</a></li>
                        <li><a href="#">Contact</a></li>
                    </ul>
                </div>
                <div class="col-lg-2 col-md-4 mb-4">
                    <h5>Portals</h5>
                    <ul>
                        <li><a href="auth/login.php">Student Portal</a></li>
                        <li><a href="auth/login.php">Teacher Portal</a></li>
                        <li><a href="auth/login.php">Admin Panel</a></li>
                    </ul>
                </div>
                <div class="col-lg-2 col-md-4 mb-4">
                    <h5>Legal</h5>
                    <ul>
                        <li><a href="#">Privacy Policy</a></li>
                        <li><a href="#">Terms of Service</a></li>
                        <li><a href="#">Cookie Policy</a></li>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom fade-in-up" style="transition-delay: 0.2s;">
                <p>&copy; <?php echo date('Y'); ?> Campus Nova. All rights reserved. Crafted with <i class="bi bi-heart-fill text-danger"></i> for Education.</p>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/animations.js"></script>
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
