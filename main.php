<?php
session_start();
// Include the database connection file
// NOTE: Ensure 'index.php' correctly establishes the $conn variable
include 'index.php'; 

// Fetch statistics
try {
    // Attempt to connect and execute the query
    $sql = "
    SELECT 
        (SELECT COUNT(*) FROM requests WHERE status='approved') AS completed_requests,
        (SELECT COUNT(*) FROM users) AS total_users,
        (SELECT COUNT(*) FROM visitors) AS total_visitors
    ";
    // Check if connection exists before executing
    if (isset($conn)) {
        $result = $conn->query($sql);
        $stats = $result->fetch_assoc();
    } else {
        // Set default values if connection/include fails
        $stats = ['completed_requests' => 0, 'total_users' => 0, 'total_visitors' => 0];
    }
} catch (Exception $e) {
    // Set default values in case of a query error
    $stats = ['completed_requests' => 0, 'total_users' => 0, 'total_visitors' => 0];
    // error_log($e->getMessage()); // Log error if needed
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Athar Graduate</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css"> 

<style>
/* 🎨 Colors and Variables */
:root {
    --light-peach: #fdfaf5; /* Very light peach - Main background */
    --coral: #ff7f50;      /* Coral - Primary accent color */
    --dark-gray: #2c3e50;  /* Modern dark gray - For text */
    --light-gray: #ecf0f1; /* Light gray - For component backgrounds */
    --white: #fff;
    --shadow-light: 0 4px 15px rgba(0, 0, 0, 0.08); 
    --transition-speed: 0.3s;
    --border-radius-large: 15px;
}
/* ✒️ Fonts and Basics */
*, *::before, *::after {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
}
body {
    font-family: 'Poppins', sans-serif; 
    background: var(--light-peach);
    color: var(--dark-gray);
    line-height: 1.6;
}
.container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 25px; 
}
a {
    text-decoration: none;
    color: var(--dark-gray);
    transition: color var(--transition-speed);
}
a:hover {
    color: var(--coral);
}

/* 🏷️ Header (Navbar) */
.header {
    background: var(--white);
    padding: 15px 0;
    box-shadow: 0 3px 10px rgba(0, 0, 0, 0.05); 
    position: sticky; 
    top: 0;
    z-index: 1000;
}
.navbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.logo {
    display: flex;
    align-items: center;
    gap: 10px;
}
.logo img {
    height: 45px; 
    border-radius: 50%; 
    box-shadow: 0 0 5px rgba(255, 127, 80, 0.5);
}
.logo-text {
    font-size: 1.6rem;
    font-weight: 700;
    color: var(--coral);
    letter-spacing: 0.5px;
}
.nav-links {
    list-style: none;
    display: flex;
    gap: 35px; 
}
.nav-links a {
    font-weight: 600;
    position: relative;
    padding-bottom: 5px;
}
.nav-links a::after { 
    content: '';
    position: absolute;
    width: 0;
    height: 2px;
    background: var(--coral);
    left: 50%;
    bottom: 0;
    transition: all var(--transition-speed) ease;
}
.nav-links a:hover::after, .nav-links a.active::after {
    width: 100%;
    left: 0;
}

/* 🌟 Hero Section */
.hero-section {
    padding: 120px 0 80px; 
    text-align: center;
    background: linear-gradient(135deg, var(--light-peach) 0%, var(--white) 100%);
}
.hero-section h1 {
    font-size: 3.5rem;
    color: var(--dark-gray);
    margin-bottom: 15px;
    font-weight: 700;
    position: relative;
}
.hero-section h1 span {
    color: var(--coral);
    position: relative;
}
.hero-section p {
    font-size: 1.3rem;
    max-width: 700px;
    margin: 0 auto 50px;
    color: var(--dark-gray);
}
.cta-button {
    background: var(--coral);
    color: var(--white);
    padding: 15px 45px;
    border-radius: 50px;
    text-decoration: none;
    font-size: 1.3rem;
    font-weight: 700;
    box-shadow: 0 8px 25px rgba(255, 127, 80, 0.4);
    transition: all var(--transition-speed) ease;
    border: 2px solid transparent;
}
.cta-button:hover {
    background: #e6603d;
    transform: translateY(-3px);
    box-shadow: 0 10px 30px rgba(255, 127, 80, 0.5);
    border: 2px solid var(--white);
}

/* 📊 Statistics Section */
.section-title {
    font-size: 2.5rem;
    font-weight: 700;
    color: var(--dark-gray);
    margin-bottom: 50px;
    text-align: center;
    position: relative;
}
.section-title::after {
    content: '';
    position: absolute;
    width: 80px;
    height: 4px;
    background: var(--coral);
    bottom: -10px;
    left: 50%;
    transform: translateX(-50%);
    border-radius: 2px;
}
.stats-section {
    background: var(--white); 
    padding: 60px 20px;
    border-radius: var(--border-radius-large);
    margin: 50px auto;
    box-shadow: var(--shadow-light);
}
.stats-grid {
    display: flex;
    flex-wrap: wrap;
    gap: 30px;
    justify-content: space-around; 
    text-align: center;
}
.stat-box {
    flex: 1 1 250px; 
    background: var(--light-peach);
    border-radius: var(--border-radius-large);
    padding: 30px 20px;
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
    transition: transform var(--transition-speed), box-shadow var(--transition-speed);
    position: relative;
    overflow: hidden; 
}
.stat-box:hover {
    transform: translateY(-8px);
    box-shadow: 0 10px 20px rgba(255, 127, 80, 0.15);
}
.stat-box .icon {
    font-size: 45px;
    margin-bottom: 15px;
    color: var(--coral);
    background: rgba(255, 127, 80, 0.1); 
    border-radius: 50%;
    width: 70px;
    height: 70px;
    display: inline-flex;
    justify-content: center;
    align-items: center;
}
.stat-box h3 {
    font-size: 2.5rem; 
    margin-bottom: 5px;
    color: var(--dark-gray);
    font-weight: 700;
}
.stat-box p {
    color: var(--coral); 
    font-weight: 600;
    font-size: 1.1rem;
}

/* ⚙️ Services Section */
.services-section {
    padding: 80px 0;
    text-align: center;
}
.services-grid {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    gap: 30px;
}
.service-box {
    flex: 1 1 300px;
    background: var(--white);
    border-radius: var(--border-radius-large);
    padding: 40px 30px;
    box-shadow: var(--shadow-light);
    transition: all var(--transition-speed);
    text-align: center;
    border-top: 5px solid var(--coral); 
}
.service-box:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
    background: var(--light-peach);
}
.service-icon {
    font-size: 50px;
    color: var(--coral);

CIS rr, [19/05/47 01:46 ص]
margin-bottom: 20px;
}
.service-box h3 {
    margin-bottom: 10px;
    font-size: 1.5rem;
    color: var(--dark-gray);
}
.service-box p {
    color: #555;
    font-size: 1rem;
}

/* ❓ FAQ Section */
.faq-section {
    padding: 80px 0;
    max-width: 900px; 
    margin: 0 auto;
}
.faq-item {
    margin-bottom: 15px;
    border-radius: 10px;
    background: var(--white);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
    overflow: hidden;
}
.faq-question {
    width: 100%;
    text-align: left; 
    padding: 18px 25px;
    font-size: 1.15rem;
    border: none;
    background: var(--light-peach);
    cursor: pointer;
    font-weight: 600;
    transition: background var(--transition-speed), color var(--transition-speed);
    border-radius: 10px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    color: var(--dark-gray);
}
.faq-question:hover {
    background: var(--coral);
    color: var(--white);
}
.faq-question i {
    transition: transform var(--transition-speed);
    font-size: 1rem;
}
.faq-question.active {
    background: var(--coral);
    color: var(--white);
    border-bottom-left-radius: 0;
    border-bottom-right-radius: 0;
}
.faq-question.active i {
    transform: rotate(180deg);
}
.faq-answer {
    padding: 15px 25px;
    display: none;
    color: var(--dark-gray);
    border-top: 1px solid #eee;
}

/* 🦶 Footer */
.footer {
    background: var(--dark-gray);
    color: var(--light-gray);
    padding: 50px 0 20px;
}
.footer-content {
    display: flex;
    justify-content: space-around;
    align-items: flex-start;
    flex-wrap: wrap;
    padding-bottom: 30px;
    border-bottom: 1px solid #444;
}
.footer-col {
    flex: 1 1 200px;
    margin-bottom: 20px;
    padding: 0 15px;
}
.footer-col h4 {
    font-size: 1.3rem;
    color: var(--white);
    margin-bottom: 20px;
    position: relative;
    padding-bottom: 5px;
}
.footer-col h4::after {
    content: '';
    position: absolute;
    width: 40px;
    height: 3px;
    background: var(--coral);
    bottom: 0;
    left: 0; 
}
.footer-col a {
    color: var(--light-gray);
    text-decoration: none;
    display: block;
    margin-bottom: 10px;
    transition: color var(--transition-speed);
    font-size: 1rem;
}
.footer-col a:hover {
    color: var(--coral);
    padding-left: 5px; 
}
.social-icons a {
    display: inline-block;
    color: var(--white);
    font-size: 1.5rem;
    margin-right: 15px;
    transition: color var(--transition-speed), transform var(--transition-speed);
}
.social-icons a:hover {
    color: var(--coral);
    transform: scale(1.1);
}
.footer-bottom {
    margin-top: 20px;
    font-size: 0.9rem;
    text-align: center;
    color: #888;
}

/* 📱 Media Queries */
@media (max-width: 992px) {
    .nav-links {
        gap: 20px;
    }
    .hero-section h1 {
        font-size: 3rem;
    }
    .stats-grid, .services-grid {
        flex-direction: column;
        align-items: center;
    }
    .stat-box, .service-box {
        flex: 1 1 90%;
        max-width: 400px;
    }
    .footer-content {
        justify-content: space-around;
    }
    .footer-col {
        flex: 1 1 45%;
        text-align: center;
    }
    .footer-col h4::after {
        left: 50%;
        transform: translateX(-50%);
    }
    .social-icons {
        text-align: center;
    }
}
@media (max-width: 576px) {
    .navbar {
        flex-direction: column;
        gap: 15px;
    }
    .nav-links {
        flex-direction: column;
        gap: 10px;
        align-items: center;
    }
    .hero-section h1 {
        font-size: 2.5rem;
    }
    .cta-button {
        padding: 12px 30px;
        font-size: 1.1rem;
    }
    .footer-col {
        flex: 1 1 100%;
    }
}
</style>
</head>
<body>
<header class="header">
<div class="container">
<nav class="navbar">
<div class="logo">
<img src="LOGObl.png" alt="Athar Logo">
<span class="logo-text">Athar Graduate</span>
</div>
<ul class="nav-links">
<li><a href="#statistics">Statistics</a></li>
<li><a href="#services">Services</a></li>
<li><a href="#faq">FAQ</a></li>
<li><a href="#contact">Contact Us</a></li>
</ul>
</nav>
</div>
</header>

<main>
    <section class="hero-section">
        <div class="container">
            <h1>The <span>Athar Graduate</span> Platform</h1>
            <p>"A digital platform specialized in facilitating the request and management of academic recommendation letters between students and faculty."</p>
            <a href="login.php" class="cta-button">Get Started Now</a>
        </div>
    </section>

    <section id="statistics" class="stats-section container">
        <h2 class="section-title">Our Key Statistics</h2>
        <div class="stats-grid">
            <div class="stat-box">
                <div class="icon"><i class="fas fa-check-circle"></i></div>
                <h3 class="counter" data-target="<?= htmlspecialchars($stats['completed_requests']) ?>">0</h3>
                <p>Completed Requests</p>
            </div>
            <div class="stat-box">
                <div class="icon"><i class="fas fa-users"></i></div>
                <h3 class="counter" data-target="<?= htmlspecialchars($stats['total_users']) ?>">0</h3>
                <p>Total Users</p>
            </div>
            <div class="stat-box">
                <div class="icon"><i class="fas fa-globe"></i></div>
                <h3 class="counter" data-target="<?= htmlspecialchars($stats['total_visitors']) ?>">0</h3>
                <p>Total Visitors</p>
            </div>
        </div>
    </section>

    <section id="services" class="services-section container">
        <h2 class="section-title">Our Distinctive Services</h2>
        <div class="services-grid">
            <div class="service-box">
                <i class="service-icon fas fa-envelope-open-text"></i>
                <h3>Recommendation Letters</h3>
                <p>Seamless management of academic recommendation requests, from submission to delivery.</p>
            </div>
            <div class="service-box">
                <i class="service-icon fas fa-user-tie"></i>
                <h3>User Profiles</h3>
                <p>An integrated profile for every student and faculty member to organize data and request history.</p>
            </div>
            <div class="service-box">
                <i class="service-icon fas fa-bell"></i>
                <h3>Instant Notifications</h3>
                <p>Real-time alerts for new requests, approvals, rejections, and any important updates.</p>
            </div>
        </div>
    </section>

    <section id="faq" class="faq-section container">
        <h2 class="section-title">Frequently Asked Questions</h2>
        <div class="faq-list">
            <div class="faq-item">
                <button class="faq-question">How do I request a recommendation letter? <i class="fas fa-chevron-down"></i></button>
                <div class="faq-answer"><p>After logging in, navigate to "New Request" and accurately complete the form, including all required details and the recommender's information.</p></div>
            </div>
            <div class="faq-item">
                <button class="faq-question">Can I track the status of my request? <i class="fas fa-chevron-down"></i></button>
                <div class="faq-answer"><p>Yes, you can access "Track Requests" to monitor your request's progress, view status updates, and download the completed letter.</p></div>
            </div>
            <div class="faq-item">
                <button class="faq-question">Will I receive notifications? <i class="fas fa-chevron-down"></i></button>
                <div class="faq-answer"><p>Absolutely. You will receive instant notifications via the platform and email regarding any changes in your request status.</p></div>
            </div>
        </div>
    </section>

    <section id="contact" class="container" style="padding: 20px 0; text-align: center;">
        </section>
</main>

<footer class="footer">
    <div class="container footer-content">
        <div class="footer-col">
            <h4>Quick Links</h4>
            <a href="#services">Services</a>
            <a href="#faq">FAQ</a>
            <a href="login.php">Login</a>
        </div>
        <div class="footer-col">
            <h4>Support & Legal</h4>
            <a href="#">Privacy Policy</a>
            <a href="#">Terms of Use</a>
            <a href="#">Help Center</a>
        </div>
        <div class="footer-col">
            <h4>Connect With Us</h4>
            <div class="social-icons">
                <a aria-label="Facebook">+0123456789</a>
                <a href="mailto:athergraduate@gmail.com" aria-label="Email"><i class="fas fa-envelope"></i></a>
            </div>
        </div>
    </div>
    <div class="footer-bottom">&copy; 2025 Athar Graduate. All Rights Reserved.</div>
</footer>

<script>
    // Counter animation
    const counters = document.querySelectorAll('.counter');
    const animateCounter = () => {
        counters.forEach(counter => {
            const target = +counter.getAttribute('data-target');
            let count = +counter.innerText;
            const increment = target / 100;
            if(count < target){
                counter.innerText = Math.ceil(count + increment);
                setTimeout(() => animateCounter(), 20); 
            } else {
                counter.innerText = target;
            }
        });
    };

    // Run counter when the section is visible
    const statsSection = document.getElementById('statistics');
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                animateCounter();
                observer.unobserve(statsSection); 
            }
        });
    }, { threshold: 0.5 }); 

    observer.observe(statsSection);


    // FAQ Accordion
    const faqItems = document.querySelectorAll('.faq-item');
    faqItems.forEach(item => {
        const question = item.querySelector('.faq-question');
        question.addEventListener('click', () => {
            const answer = item.querySelector('.faq-answer');
            const icon = question.querySelector('i');
            const isOpen = answer.style.display === 'block';

            // Close all open answers
            document.querySelectorAll('.faq-answer').forEach(a => a.style.display = 'none');
            document.querySelectorAll('.faq-question').forEach(q => q.classList.remove('active'));
            document.querySelectorAll('.faq-question i').forEach(i => i.style.transform = 'rotate(0deg)');

            if(!isOpen) {
                // Open the clicked answer
                answer.style.display = 'block';
                question.classList.add('active');
                icon.style.transform = 'rotate(180deg)';
            }
        });
    });
</script>
</body>
</html>