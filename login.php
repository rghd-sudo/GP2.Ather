<?php
include 'index.php';
session_start(); // لتخزين بيانات الجلسة

$conn->set_charset("utf8mb4");
$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = $conn->real_escape_string($_POST['email'] ?? '');
    $paasword = $_POST['paasword'] ?? '';

    if ($email && $paasword) {
        // جلب المستخدم من القاعدة
        $stmt = $conn->prepare("SELECT id, name, paasword FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $users = $result->fetch_assoc();
            // التحقق من كلمة المرور
            if (password_verify($paasword, $users['paasword'])) {
                // نجاح → حفظ بيانات المستخدم في الجلسة
                $_SESSION['user_id'] = $users['id'];
                $_SESSION['user_name'] = $users['name'];
                header("Location: req_system.php"); // صفحة الترحيب أو لوحة التحكم
                exit;
            } else {
                $message = "⚠️ The password is incorrect.";
            }
        } else {
            $message = "⚠️ The email does not exist.";
        }
        $stmt->close();
    } else {
        $message = "⚠️ Please enter your email and password.";
    }
}
?>
<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;700&display=swap" rel="stylesheet" href=>
    <link rel="stylesheet" href="style.css">
    <style>
         :root {
            --light-peach:#fdfaf5;
            --coral: #ff7f50;
            --dark-gray: #333;
            --light-gray: #777;
            --white: #fff;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

         .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        /* --- Header & Navigation Bar --- */
        .header {
            background-color: var(--white);
            padding: 15px 0;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
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
            height: 40px;
        }

        .logo-text {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--coral);
        }
         .nav-links {
            list-style: none;
            display: flex;
            gap: 30px;
        }

        .nav-links a {
            text-decoration: none;
            color: var(--dark-gray);
            font-weight: 400;
            transition: color 0.3s ease;
        }

        .nav-links a:hover {
            color: var(--coral);
        }

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
                    <li><a href="#about">About Us</a></li>
                    <li><a href="#faq">FAQ</a></li>
                    <li><a href="#contact">Contact Us</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <div class="login-container">
        <h2>Login</h2>
        <form action="login.php" method="POST">
            <label for="email">Email:</label><br>
            <input type="email" id="email" name="email" required><br>

            <label for="paasword">Password:</label><br>
            <input type="password" id="paasword" name="paasword" required><br>

            <button class="btn-primary" type="submit">Login now</button>
            <p class="small-text">Don't have an account? <a href="register.php">Register here</a></p>
        </form>

        <?php if(!empty($message)): ?>
            <div class="message"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>
    </div>
</body>
</html>