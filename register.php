<?php
session_start();
include 'index.php';
/*
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'phpmailer/Exception.php';
require 'phpmailer/PHPMailer.php';
require 'phpmailer/SMTP.php';
*/
// عرض الرسالة إذا موجودة
if (isset($_SESSION['message'])) {
    echo "<script>alert('" . $_SESSION['message'] . "');</script>";
    unset($_SESSION['message']);
}

// إنشاء الجداول (اختياري في أول مرة فقط)
$conn->query("CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    paasword VARCHAR(255) NOT NULL,
    department VARCHAR(255),
    National_ID VARCHAR(50),
    university VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

$conn->query("CREATE TABLE IF NOT EXISTS graduates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    graduation_year YEAR NULL,
    gpa DECIMAL(3,2) NULL,
    cv_path VARCHAR(500) NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name        = trim($_POST['name'] ?? '');
    $email       = trim($_POST['email'] ?? '');
    $paasword    = trim($_POST['paasword'] ?? '');
    $confirm_password = trim($_POST['confirm_password'] ?? '');
    $National_ID = trim($_POST['National_ID'] ?? '');
    $university  = trim($_POST['university'] ?? '');
    $department  = trim($_POST['department'] ?? '');

    if ($name && $email && $paasword && $department && $National_ID && $university) {
        if ($paasword !== $confirm_password) {
            $_SESSION['message'] = "⚠️ Passwords do not match";
            header("Location: register.php");
            exit;
        }

        // تشفير كلمة المرور
        $hashed = password_hash($paasword, PASSWORD_BCRYPT);

        // التحقق من أن البريد غير مسجل مسبقًا
        $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $check->bind_param("s", $email);
        $check->execute();
        $check->store_result();
        if ($check->num_rows > 0) {
            $_SESSION['message'] = "⚠️ Email already exists";
            header("Location: register.php");
            exit;
        }
        $check->close();

        // تخزين بيانات المستخدم في جدول users
        $stmt = $conn->prepare("INSERT INTO users (name, email, paasword, department, National_ID, university) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssss", $name, $email, $hashed, $department, $National_ID, $university);

        if ($stmt->execute()) {
            $user_id = $stmt->insert_id;

            // إدخال الخريج تلقائيًا في جدول graduates
            $graduation_year = NULL;
            $gpa = NULL;
            $cv_path = NULL;
            
            $stmt2 = $conn->prepare("INSERT INTO graduates (user_id, graduation_year, gpa, cv_path) VALUES (?, ?, ?, ?)");
            $stmt2->bind_param("iids", $user_id, $graduation_year, $gpa, $cv_path);
            $stmt2->execute();
            $stmt2->close();

            $_SESSION['message'] = "✅ Registration successful!";
            header("Location: req_system.php");
            exit;
        } else {
            $_SESSION['message'] = "❌ Error: " . $stmt->error;
        }
        $stmt->close();
    } else {
        $_SESSION['message'] = "⚠️ Please fill all fields";
    }
}
/*
        // إرسال رمز التحقق عبر PHPMailer
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'yourgmail@gmail.com'; // بريدك
            $mail->Password = 'app-password';       // كلمة مرور التطبيقات من Google
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;

            $mail->setFrom('yourgmail@gmail.com', 'نظام التوصيات');
            $mail->addAddress($email, $name);

            $mail->isHTML(true);
            $mail->Subject = 'رمز التحقق من البريد الإلكتروني';
            $mail->Body = "مرحباً <b>$name</b>،<br>رمز التحقق الخاص بك هو: <b>$verify_code</b>";

            $mail->send();
     // تحويل المستخدم لصفحة إدخال 
     header("Location: verify.php?email=" . urlencode($email));
            exit;
        } catch (Exception $e) {
            $_SESSION['message'] = "❌ حدث خطأ أثناء إرسال البريد: {$mail->ErrorInfo}";
            header("Location: register.php");
            exit;
        }
    } else {
        $_SESSION['message'] = "❌ Error: " . $stmt->error;
    }
    $stmt->close();
}
عرض الرسالة إذا موجودة
if (isset($_SESSION['message'])) {
    echo "<script>alert('" . $_SESSION['message'] . "');</script>";
    unset($_SESSION['message']); // مسح الرسالة بعد عرضها مرة واحدة
}
      // 2. إدخال البيانات الخاصة بالخريج في graduates
                $stmt = $conn->prepare("INSERT INTO graduates (user_id,graduation_year,gpa,cv_path) VALUES (?,?,?,?)");
                $stmt->bind_param("iids", $user_id, $graduation_year, $gpa, $cv_path);
                $stmt->execute();
                $stmt->close();
// إنشاء جدول إذا لم يكن موجود
$conn->query("CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    paasword VARCHAR(255) NOT NULL,
    department VARCHAR(255),
    National_ID VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");
$message = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name        = $conn->real_escape_string($_POST['name'] ?? '');
    $email       = $conn->real_escape_string($_POST['email'] ?? '');
    $paasword    = $conn->real_escape_string($_POST['paasword'] ?? '');
    $confirm_password = $conn->real_escape_string($_POST['confirm_password'] ?? '');
    $National_ID = $conn->real_escape_string($_POST['National_ID'] ?? '');
    $university = $conn->real_escape_string($_POST['university'] ?? '');
    $department  = $conn->real_escape_string($_POST['department'] ?? '');
 if ($name && $email && $paasword && $department && $National_ID && $university) {
        // تشفير كلمة المرور
        $hashed = password_hash($paasword, PASSWORD_BCRYPT);
        if ($paasword !== $confirm_password) {
            $_SESSION['message'] = "⚠️ Passwords do not match";
        } 
        $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $check->bind_param("s", $email);
        $check->execute();
        $check->store_result();
        if ($check->num_rows > 0) {
            $_SESSION['message'] = "⚠️ Email already exists";
        } else {
            $check->close();
        // استخدام prepared statement للتخزين
       $stmt = $conn->prepare("INSERT INTO users (name, email, paasword, department, National_ID, university) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssss", $name, $email, $hashed, $department, $National_ID, $university);
         if ($stmt->execute()) {
            $_SESSION['message'] = "Successful registration✅";
            header("Location: req_system.php");
        } else {
            $_SESSION['message'] = "Error: " . $stmt->error;
        }
    $stmt->close();
 }
 }
}*/
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
      <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;700&display=swap" rel="stylesheet">
    <title>Register</title>
    <style>
    body{ margin: 0;
  padding: 0;
  text-decoration: none;
  font-family: 'preconnect', sans-serif;
  background: #fdfaf5;display:flex;justify-content:center;align-items:center;}
    .card{background:#fff;padding:0px;border-radius:10px;background: #fdfaf5;width:400px}
    h2{text-align:center;margin-bottom:0px}
    .field{margin-bottom:8px}
    .field label{display:block;margin-bottom:5px;font-weight:regular;font-size:14px}
    .field input{width:99%;padding:9px;border:0px solid #ccc;border-radius:5px}
    /* أزرار */
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
  background: #e45a46;
}
.small-text {
  font-size: 12px;
  color: gray;
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

    .message{margin-top:15px;text-align:left;font-weight:bold;color:#333}
    </style>
</head>
<body>
     <div class="shapes">
        <div class="shape shape1"></div>
        <div class="shape shape2"></div>
        <div class="shape shape3"></div>
    </div>
    <div class="card">
        
        <h2>Register</h2>
    <!-- <sage): ?>
      <p style="margin:20px 0;padding:12px;background:#fff8e6;border-radius:6px;box-shadow:var(--shadow)"><></p>
     ?> -->
        <div class="container">
        <form action="register.php" method="POST">
           <div class="input-group">
            <label for="name">Full Name:</label>
            <input  type="text" id="name" name="name" required>
            </div>
            <div class="input-group">
           <label for="email">Email:</label>
            <input type="email" id="email" name="email" required>
           </div>
           <div class="input-group">
               <label for="paasword">Password:</label>
            <input type="password" id="paasword" name="paasword" required>
        </div>
<div class="input-group">
            <label for="confirm_password">Confirm Password:</label>
            <input type="password" id="confirm_password" name="confirm_password" required>
</div>
<div class="input-group">
           <label for="National_ID">National ID:</label>
            <input type="text" id="National_ID" name="National_ID" required>
</div>
<div class="input-group">

           <label for="university">University:</label>
            <input type="text" id="university" name="university" required>
            </div>
<div class="input-group">
            <label for="department">Department:</label>
            <input type="text" id="department" name="department" required>
</div>

          <button class="btn-primary" type="submit">Register</button> 
          <p class="small-text">Already have an account? <a href="login.php">Login here</a></p>
        </form>
        </div>
    </div>
</body>
</html>

