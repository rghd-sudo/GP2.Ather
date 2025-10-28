<?php
include 'index.php';
// لتخزين بيانات المستخدم
session_start();

$conn->set_charset("utf8mb4");
$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = $conn->real_escape_string($_POST['email'] ?? '');
    $paasword = $_POST['paasword'] ?? '';

    if ($email && $paasword) {
        $stmt = $conn->prepare("SELECT id, name, paasword, role FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            if (password_verify($paasword, $user['paasword'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['role'] = $user['role']; // حفظ الدور

                // التوجيه حسب الدور
                if ($user['role'] === 'graduate') {
                    header("Location: req_system.php"); // صفحة الخريج
                } elseif ($user['role'] === 'professor') {
                    header("Location: requests.php"); // صفحة الدكتور
                } else {
                    $message = "⚠️ Undefined user role.";
                }
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
    <link rel="stylesheet" href="tyle.css">
    <style>
         :root {
            --light-peach:#fdfaf5;
            --coral: #ff7f50;
            --dark-gray: #333;
            --light-gray: #777;
            --white: #fff;
        }
 body {
            font-family: Arial, sans-serif;
            background-color: #f7f3ed;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            position: relative;
        }

        .login-container {
            text-align: center;
            padding: 40px;
            width: 400px;
        }

        h1 {
            font-size: 48px;
            color: #333;
            margin-bottom: 40px;
        }

        .input-group {
            margin-bottom: 20px;
        }

        .input-group label {
            display: block;
            text-align: left;
            margin-bottom: 5px;
            color: #555;
        }

        .input-group input {
            width: 100%;
            padding: 15px;
            border: none;
            border-radius: 10px;
            background-color: #e0d9d3;
            box-sizing: border-box;
            font-size: 16px;
        }

        .btn {
            background-color: #ef8b78;
            color: #fff;
            padding: 15px 50px;
            border: none;
            border-radius: 30px;
            font-size: 20px;
            cursor: pointer;
            margin-top: 20px;
        }

        .link-text {
            font-size: 14px;
            margin-top: 20px;
            color: #555;
        }

        .link-text a {
            color: #007bff;
            text-decoration: none;
        }

        .shapes {
            position: absolute;
            top: 0;
            left: 0;
            width: 40%;
            height: 100%;
            overflow: hidden;
            z-index: -1;
        }

        .shape {
            position: absolute;
            width: 300px;
            height: 300px;
            border-radius: 50px;
            transform: rotate(-30deg);
        }

        .shape1 {
            background-color: #63b999;
            top: -100px;
            left: -70px;
        }

        .shape2 {
            background-color: #adc0d9;
            top: 50px;
            left: -50px;
        }

        .shape3 {
            background-color:  #f27360;
            top: 200px;
            left: -150px;
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
        .btn-primary {
  background: #f27360;
  color: white;
  border: none;
  border-radius: 25px;
  padding: 10px 25px;
  cursor: pointer;
  font-size: 16px;
}

.btn-primary:hover {
  background: #dd5d4aff;
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
        .small-text {
  font-size: 12px;
  color: gray;
}

        </style>
</head>
<body>
  <!--  <header class="header">
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
    </header>-->
     <div class="shapes">
        <div class="shape shape1"></div>
        <div class="shape shape2"></div>
        <div class="shape shape3"></div>
    </div>
    <div class="login-container">
        <h1>Login</h1>
        <form action="login.php" method="POST">
            <div class="input-group">
            <label for="email">Email:</label>
            <input type="email" id="email" name="email" required>
            </div>
            <div class="input-group">
            <label for="paasword">Password:</label>
            <input type="password" id="paasword" name="paasword" required>
            </div>

            <button class="btn-primary" type="submit">Login</button>
           <br> <p class="small-text">Don't have an account? <a href="register.php">Register here</a></p>
        </form>

        <?php if(!empty($message)): ?>
            <div class="message"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>
    </div>
</body>
</html>