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
            color: #f1f5f9; /* Light text for dark background */
            background-color: #0f172a; /* Dark background for 3D effect */
            overflow-x: hidden;
            scroll-behavior: smooth;
        }

        h1, h2, h3, h4, h5, h6, .navbar-brand {
            font-family: 'Poppins', sans-serif;
            color: #ffffff;
        }
        
        .text-muted {
            color: #94a3b8 !important; /* Lighter gray for readability on dark background */
        }

        #vanta-bg {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100vh;
            z-index: -1;
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

        /* Hero Content Styles */

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
            background: transparent;
        }
        .stat-item {
            text-align: center;
            padding: 2rem;
            background: rgba(255, 255, 255, 0.03);
            border-radius: 20px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.05);
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            position: relative;
            overflow: hidden;
            z-index: 1;
        }
        .stat-item::before {
            content: '';
            position: absolute;
            top: 0; left: 0; width: 100%; height: 100%;
            background: linear-gradient(135deg, rgba(14, 165, 233, 0.1), rgba(59, 130, 246, 0.1));
            z-index: -1;
            opacity: 0;
            transition: opacity 0.4s ease;
        }
        .stat-item:hover {
            transform: translateY(-10px) scale(1.05);
            box-shadow: 0 15px 40px rgba(14, 165, 233, 0.3);
            border-color: rgba(56, 189, 248, 0.5);
        }
        .stat-item:hover::before {
            opacity: 1;
        }
        .stat-item:hover .stat-number {
            text-shadow: 0 0 15px rgba(14, 165, 233, 0.6);
            transform: scale(1.1);
            display: inline-block;
        }
        .stat-number {
            font-size: 3rem;
            font-weight: 800;
            color: var(--primary-blue);
            margin-bottom: 0.5rem;
            transition: transform 0.3s ease;
        }

        /* FEATURES SECTION */
        .features-section {
            padding: 100px 0;
            background: transparent;
        }
        .feature-icon-wrapper {
            width: 60px; height: 60px;
            border-radius: 15px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.8rem;
            background: rgba(14, 165, 233, 0.2);
            color: #38bdf8;
            margin-bottom: 1.5rem;
            transition: all 0.4s ease;
        }
        
        .features-section .card {
            background: rgba(255, 255, 255, 0.05) !important;
            backdrop-filter: blur(15px);
            border: 1px solid rgba(255, 255, 255, 0.1) !important;
            color: #f1f5f9;
            transition: all 0.5s cubic-bezier(0.25, 0.8, 0.25, 1);
            position: relative;
            z-index: 1;
            overflow: hidden;
            transform-style: preserve-3d;
        }
        .features-section .card::after {
            content: '';
            position: absolute;
            bottom: 0; left: 0; width: 100%; height: 4px;
            background: linear-gradient(90deg, var(--gradient-start), var(--gradient-end));
            transform: scaleX(0);
            transform-origin: left;
            transition: transform 0.5s ease;
        }
        .features-section .card:hover {
            transform: translateY(-15px) rotateX(5deg) rotateY(-5deg);
            box-shadow: -10px 20px 40px rgba(0, 0, 0, 0.4), 0 0 20px rgba(14, 165, 233, 0.2);
            border: 1px solid rgba(56, 189, 248, 0.4) !important;
            background: rgba(255, 255, 255, 0.08) !important;
        }
        .features-section .card:hover::after {
            transform: scaleX(1);
        }
        .features-section .card:hover .feature-icon-wrapper {
            background: linear-gradient(135deg, #0ea5e9, #3b82f6);
            color: white;
            transform: scale(1.1) rotate(10deg);
            box-shadow: 0 10px 20px rgba(14, 165, 233, 0.4);
        }

        /* HOW IT WORKS SECTION */
        .workflow-section {
            padding: 100px 0;
            background: transparent;
        }
        .workflow-step {
            text-align: center;
            position: relative;
            padding: 20px;
        }
        .step-icon {
            width: 80px; height: 80px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.3);
            display: flex; align-items: center; justify-content: center;
            font-size: 2rem;
            color: #38bdf8;
            margin: 0 auto 1.5rem auto;
            position: relative;
            z-index: 2;
            border: 2px solid rgba(56, 189, 248, 0.5);
            transition: all 0.5s ease;
        }
        .workflow-step:hover .step-icon {
            transform: scale(1.15) rotate(360deg);
            background: rgba(14, 165, 233, 0.2);
            box-shadow: 0 0 25px rgba(14, 165, 233, 0.5);
            border-color: #0ea5e9;
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

        /* FOOTER */
        footer {
            background: transparent;
            color: #94a3b8;
            padding: 80px 0 20px 0;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
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
        footer ul li a { 
            color: #94a3b8; 
            text-decoration: none; 
            transition: color 0.3s;
            position: relative;
            display: inline-block;
        }
        footer ul li a::after {
            content: '';
            position: absolute;
            width: 100%;
            transform: scaleX(0);
            height: 2px;
            bottom: -4px;
            left: 0;
            background: linear-gradient(90deg, var(--gradient-start), var(--gradient-end));
            transform-origin: bottom right;
            transition: transform 0.4s cubic-bezier(0.86, 0, 0.07, 1);
        }
        footer ul li a:hover { color: white; }
        footer ul li a:hover::after {
            transform: scaleX(1);
            transform-origin: bottom left;
        }
        .social-icons a {
            display: inline-flex;
            align-items: center; justify-content: center;
            width: 40px; height: 40px;
            border-radius: 50%;
            background: rgba(255,255,255,0.1);
            color: white;
            margin-right: 10px;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }
        .social-icons a:hover { 
            background: linear-gradient(135deg, #0ea5e9, #3b82f6);
            transform: translateY(-5px) scale(1.1);
            box-shadow: 0 10px 20px rgba(14, 165, 233, 0.4);
        }
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
    <!-- 3D Vanta Background Container -->
    <div id="vanta-bg"></div>

    <!-- HERO SECTION / PORTAL -->
    <section class="hero-section" id="home" style="padding: 0; display: flex; align-items: center; justify-content: center; background: transparent;">
        <div class="animated-bg">
            <div class="cell c1"><i class="bi bi-laptop"></i></div>
            <div class="cell c2"><i class="bi bi-mortarboard-fill"></i></div>
            <div class="cell c3"><i class="bi bi-check2-circle"></i></div>
            <div class="cell c4"><i class="bi bi-calendar2-week"></i></div>
            <div class="cell c5"><i class="bi bi-book"></i></div>
        </div>
        
        <div class="container hero-content text-center floating-3d">
            
            <div class="fade-in-up visible premium-glass-card tilt-card" style="max-width: 700px; margin: 0 auto; padding: 5rem 3rem;">
                <div class="glare-effect"></div>
                <div class="mb-4 hover-bounce-icon">
                    <i class="bi bi-hexagon-fill" style="font-size: 4rem; color: var(--premium-accent); filter: drop-shadow(0 0 20px var(--premium-accent));"></i>
                </div>
                
                <h1 class="hero-title mb-3 neon-text premium-text" style="color: white; text-shadow: 0 0 10px rgba(255,255,255,0.3);">Campus Nova</h1>
                <p class="hero-desc mb-5 premium-text-muted" style="font-size: 1.2rem; color: #cbd5e1 !important;">Next-Generation Smart Education Platform</p>
                
                <div class="d-flex flex-column gap-3 align-items-center position-relative" style="z-index: 10;">
                    <a href="auth/login.php" class="btn btn-gradient btn-lg w-100 mt-2" style="max-width: 320px; padding: 1.2rem; font-size: 1.1rem;">
                        <i class="bi bi-box-arrow-in-right me-2"></i> Enter Portal
                    </a>
                    <a href="auth/register.php" class="btn btn-lg w-100 mt-2" style="max-width: 320px; padding: 1.2rem; font-size: 1.1rem; border: none; background: rgba(255,255,255,0.7); color: black !important; font-weight: 600; box-shadow: 0 4px 15px rgba(0,0,0,0.05); border-radius: 50px;">
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
            <div class="row g-3">
                <div class="col-md-3 col-6 fade-in-up">
                    <div class="stat-item h-100">
                        <div class="stat-number">100+</div>
                        <div class="text-muted fw-bold">Active Students</div>
                    </div>
                </div>
                <div class="col-md-3 col-6 fade-in-up" style="transition-delay: 0.1s;">
                    <div class="stat-item h-100">
                        <div class="stat-number">20+</div>
                        <div class="text-muted fw-bold">Expert Teachers</div>
                    </div>
                </div>
                <div class="col-md-3 col-6 fade-in-up" style="transition-delay: 0.2s;">
                    <div class="stat-item h-100">
                        <div class="stat-number">99%</div>
                        <div class="text-muted fw-bold">Attendance Rate</div>
                    </div>
                </div>
                <div class="col-md-3 col-6 fade-in-up" style="transition-delay: 0.3s;">
                    <div class="stat-item h-100">
                        <div class="stat-number">24/7</div>
                        <div class="text-muted fw-bold">Smart Access</div>
                    </div>
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
                        <li><a href="auth/login.php?role=student">Student Portal</a></li>
                        <li><a href="auth/login.php?role=teacher">Teacher Portal</a></li>
                        <li><a href="auth/login.php?role=admin">Admin Panel</a></li>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/animations.js"></script>
    
    <!-- Three.js and Vanta.js for 3D Background -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r134/three.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/vanta@latest/dist/vanta.net.min.js"></script>
    
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            // Initialize 3D Background
            VANTA.NET({
                el: "#vanta-bg",
                mouseControls: true,
                touchControls: true,
                gyroControls: false,
                minHeight: 200.00,
                minWidth: 200.00,
                scale: 1.00,
                scaleMobile: 1.00,
                color: 0x0ea5e9,
                backgroundColor: 0x0f172a,
                points: 12.00,
                maxDistance: 22.00,
                spacing: 16.00
            });

            const animatedElements = document.querySelectorAll('.fade-in-up');
            setTimeout(() => {
                animatedElements.forEach(el => el.classList.add('visible'));
            }, 100);
        });
    </script>
</body>
</html>
