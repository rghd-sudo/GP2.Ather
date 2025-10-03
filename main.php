<?php
session_start();
include 'index.php'; // الاتصال بقاعدة البيانات

// جلب الإحصائيات
$sql = "
SELECT 
    (SELECT COUNT(*) FROM requests WHERE status='approved') AS completed_requests,
    (SELECT COUNT(*) FROM users) AS total_users,
    (SELECT COUNT(*) FROM visitors) AS total_visitors
";
$result = $conn->query($sql);
$stats = $result->fetch_assoc();
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
<link rel="stylesheet" href="https://netdna.bootstrapcdn.com/font-awesome/4.0.3/css/font-awesome.css">
<style>
:root {
    --light-peach:#fdfaf5;
    --coral:#ff7f50;
    --dark-gray:#333;
    --light-gray:#777;
    --white:#fff;
}
*{box-sizing:border-box;margin:0;padding:0;}
body{font-family:'Poppins',sans-serif;background:var(--light-peach);color:var(--dark-gray);}
.container{max-width:1200px;margin:0 auto;padding:0 20px;}
.header{background:#fff;padding:15px 0;box-shadow:0 2px 4px rgba(0,0,0,0.1);}
.navbar{display:flex;justify-content:space-between;align-items:center;}
.logo{display:flex;align-items:center;gap:10px;}
.logo img{height:40px;}
.logo-text{font-size:1.5rem;font-weight:700;color:var(--coral);}
.nav-links{list-style:none;display:flex;gap:30px;}
.nav-links a{text-decoration:none;color:var(--dark-gray);transition:0.3s;}
.nav-links a:hover{color:var(--coral);}
.hero-section{padding:100px 0;text-align:center;}
.hero-section h1{font-size:3rem;color:var(--coral);margin-bottom:20px;}
.hero-section p{font-size:1.2rem;max-width:600px;margin:0 auto 40px;}
.cta-button{background:var(--coral);color:#fff;padding:15px 40px;border-radius:50px;text-decoration:none;font-size:1.2rem;font-weight:700;box-shadow:0 4px 6px rgba(0,0,0,0.1);}
.cta-button:hover{background:#e6603d;}

/* Statistics */
  /* Statistics Section */
        .stats-section {background:#fff; padding:50px 20px; border-radius:20px; margin-bottom:50px; box-shadow:0 4px 10px rgba(0,0,0,0.1);}
        .stats-grid {display:flex; flex-wrap:wrap; gap:30px; justify-content:center; text-align:center;}
        .stat-box {flex:1 1 200px; background:var(--light-peach); border-radius:15px; padding:25px; box-shadow:0 2px 6px rgba(0,0,0,0.1); transition: transform 0.3s;}
        .stat-box:hover {transform: translateY(-5px);}
        .stat-box .icon {font-size:40px; margin-bottom:15px; color:var(--coral);}
        .stat-box h3 {font-size:2rem; margin-bottom:10px; color:var(--coral);}
        .stat-box p {color:var(--dark-gray); font-weight:500;}
          /* Services */
        .services-grid {display:flex; flex-wrap:wrap; justify-content:center; gap:30px;}
        .service-box {flex:1 1 250px; background:#fff; border-radius:15px; padding:30px; box-shadow:0 2px 6px rgba(0,0,0,0.1); transition: transform 0.3s;}
        .service-box:hover {transform: translateY(-5px);}
        .service-icon {font-size:40px;color:var(--coral);margin-bottom:15px;}
        .service-box h3 {margin-bottom:10px;}
        .service-box p {color:var(--dark-gray);}
  /* Services 
        .services-grid {display:flex; flex-wrap:wrap; justify-content:center; gap:30px;}
        .service-box {flex:1 1 250px; background:#fff; border-radius:15px; padding:30px; box-shadow:0 2px 6px rgba(0,0,0,0.1); transition: transform 0.3s;}
        .service-box:hover {transform: translateY(-5px);}
        .service-icon {font-size:40px;color:var(--coral);margin-bottom:15px;}
        .service-box h3 {margin-bottom:10px;}
        .service-box p {color:var(--dark-gray);}*/
        /* FAQ */
        .faq-item {margin-bottom:15px; border-radius:10px; background:#fff; box-shadow:0 2px 6px rgba(0,0,0,0.1);}
        .faq-question {width:100%; text-align:left; padding:15px 20px; font-size:1.1rem; border:none; background:var(--light-peach); cursor:pointer; font-weight:600; transition: background 0.3s ease; border-radius:10px;}
        .faq-question:hover {background:var(--coral); color:#fff;}
        .faq-answer {padding:15px 20px; display:none; color:var(--dark-gray);}
/* Services 
.services{display:flex;justify-content:center;gap:40px;flex-wrap:wrap;}
.service-box{background:#fff;padding:30px;border-radius:15px;box-shadow:0 2px 6px rgba(0,0,0,0.1);min-width:200px;transition:transform 0.3s;text-align:center;}
.service-box i{font-size:40px;margin-bottom:15px;color:var(--coral);transition:transform 0.3s;}
.service-box:hover i{transform:scale(1.2);}
.service-box h3{margin-bottom:10px;}
.service-box p{color:var(--dark-gray);}

/* FAQ Accordion 
.faq{max-width:800px;margin:50px auto;}
.faq-item{background:#fff;padding:20px;margin-bottom:15px;border-radius:10px;box-shadow:0 2px 6px rgba(0,0,0,0.1);}
.faq-item h3{cursor:pointer;position:relative;padding-right:30px;color:var(--dark-gray);}
.faq-item h3::after{content:'\002B';position:absolute;right:0;top:0;font-size:1.5rem;color:var(--coral);transition:transform 0.3s;}
.faq-item.active h3::after{content:'\2212';transform:rotate(180deg);}
.faq-item p{margin-top:10px;display:none;color:var(--dark-gray);line-height:1.5;}
.faq-item:hover h3{color:var(--coral);}*/

/* Footer */
.footer{background:var(--dark-gray);color:#fff;padding:40px 0;}
.footer-content{display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;}
.footer-col{flex:1;min-width:200px;margin-bottom:20px;}
.footer-col h4{font-size:1.2rem;color:#fff;margin-bottom:15px;}
.footer-col a{color:#fff;text-decoration:none;display:block;margin-bottom:8px;transition:0.3s;}
.footer-col a:hover{color:var(--coral);}
.footer-bottom{margin-top:30px;padding-top:20px;border-top:1px solid #777;font-size:0.9rem;text-align:center;}
</style>
</head>
<body>
<header class="header">
<div class="container">
<nav class="navbar">
<div class="logo">
<img src="logo.jpg" alt="Athar Logo">
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
<h1>Athar Graduate</h1>
<p>"A digital platform specialized in facilitating the request and management of academic recommendation letters."</p>
<a href="login.php" class="cta-button">Get Started</a>
</div>
</section>
   <!-- Statistics Section -->
        <section id="statistics" class="stats-section container">
            <h2 style="text-align:center; margin-bottom:40px;">Key Statistics</h2>
            <div class="stats-grid">
                <div class="stat-box">
                    <div class="icon">✅</div>
                    <h3 class="counter" data-target="<?= $stats['completed_requests'] ?>">0</h3>
                    <p>Completed Requests</p>
                </div>
                <div class="stat-box">
                    <div class="icon">👥</div>
                    <h3 class="counter" data-target="<?= $stats['total_users'] ?>">0</h3>
                    <p>Users</p>
                </div>
                <div class="stat-box">
                    <div class="icon">🌐</div>
                    <h3 class="counter" data-target="<?= $stats['total_visitors'] ?>">0</h3>
                    <p>Visitors</p>
                </div>
            </div>
        </section>
<!-- Statistics 
<section id="statistics" class="container" style="padding:50px 0;text-align:center;">
<h2 class="section-title">Key Statistics</h2>
<div class="stats">
    <div class="stat-box"><div class="icon">👤</div><h3 class="counter" data-target="<?= $totalUsers ?>">0</h3><p>Registered Users</p></div>
    <div class="stat-box"><div class="icon">🌐</div><h3 class="counter" data-target="<?= $totalVisitors ?>">0</h3><p>Visitors</p></div>
    <div class="stat-box"><div class="icon">✅</div><h3 class="counter" data-target="<?= $completedRequests ?>">0</h3><p>Completed Requests</p></div>
</div>
</section>-->

<!-- Services -->
<section id="services" class="container" style="padding:50px 0;text-align:center;">
<h2 class="section-title">Our Services</h2>
<div class="services-grid">
<div class="service-box"><i class="service-icon fa fa-envelope"></i><h3>Recommendation Letters</h3><p>Easily request and manage academic recommendation letters.</p></div>
<div class="service-box"><i class="service-icon fa fa-user"></i><h3>User Profiles</h3><p>Manage your profile and track your requests efficiently.</p></div>
<div class="service-box"><i class="service-icon fa fa-bell"></i><h3>Notifications</h3><p>Receive alerts for new requests, updates, or rejections.</p></div>
</div>
</section>

<!-- FAQ -->
 <section id="faq" class="container" style="padding:50px 0;">
            <h2 style="text-align:center; margin-bottom:40px;">Frequently Asked Questions</h2>
<!--<h2 class="section-title">Frequently Asked Questions</h2>-->
<div class="faq-item"><button class="faq-question"><h3>How do I request a recommendation letter?</h3></button>
<div class="faq-answer"><p>After logging in, navigate to "New Request" and complete the form accurately with all required details.</p></div></div>
<div class="faq-item"><button class="faq-question"><h3>Can I track my requests?</h3></button>
<div class="faq-answer"><p>Yes. Access "Track Request" to monitor your submissions, view status updates, and download completed letters.</p></div></div>
<div class="faq-item"><button class="faq-question"><h3>Will I receive notifications?</h3></button>
<div class="faq-answer"><p>Absolutely. Notifications will alert you of request approvals, rejections, or any important updates in real-time.</p></div></div>
</section>
</main>

<footer class="footer">
<div class="container footer-content">
<div class="footer-col">
<h4>Quick Links</h4>
<a href="#services">Services</a>
<a href="#faq">FAQ</a>
<a href="#contact">Contact Us</a>
</div>
<div class="footer-col">
<h4>Support</h4>
<a href="#">Privacy Policy</a>
<a href="#">Terms of Use</a>
</div>
<div class="footer-col">
<h4>Connect With Us</h4>
</div>
</div>
<div class="footer-bottom">&copy; 2025 Athar Graduate. All Rights Reserved.</div>
</footer>

<script>
// Counter animation
const counters = document.querySelectorAll('.counter');
counters.forEach(counter => {
    const updateCount = () => {
        const target = +counter.getAttribute('data-target');
        let count = +counter.innerText;
        const increment = target / 100;
        if(count < target){
            counter.innerText = Math.ceil(count + increment);
            setTimeout(updateCount, 20);
        } else {
            counter.innerText = target;
        }
    };
    updateCount();
});
        // FAQ Accordion
        const faqItems = document.querySelectorAll('.faq-item');
        faqItems.forEach(item => {
            const question = item.querySelector('.faq-question');
            question.addEventListener('click', () => {
                const answer = item.querySelector('.faq-answer');
                const isOpen = answer.style.display === 'block';
                document.querySelectorAll('.faq-answer').forEach(a => a.style.display = 'none');
                if(!isOpen) answer.style.display = 'block';
            });
        });
    </script>
/* FAQ accordion
const faqItems = document.querySelectorAll('.faq-item');
faqItems.forEach(item => {
    item.querySelector('h3').addEventListener('click', () => {
const open = document.querySelector('.faq-item.active');
        if(open && open !== item) open.classList.remove('active');
        item.classList.toggle('active');
        const answer = item.querySelector('p');
        answer.style.display = item.classList.contains('active') ? 'block' : 'none';
    });
});*/
<script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
</body>
</html>