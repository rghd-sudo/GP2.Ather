<?php
// الاتصال بقاعدة البيانات
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "agdb";

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    die("فشل الاتصال: " . $conn->connect_error);
}

// إنشاء جدول إذا لم يكن موجود
$conn->query("CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
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
    $department  = $conn->real_escape_string($_POST['department'] ?? '');
 if ($name && $email && $paasword && $department && $National_ID) {
        // تشفير كلمة المرور
        $hashed = password_hash($paasword, PASSWORD_BCRYPT);
        if ($paasword !== $confirm_password) {
            $message = "⚠️ Passwords do not match";
        } 
        $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $check->bind_param("s", $email);
        $check->execute();
        $check->store_result();
        if ($check->num_rows > 0) {
            $message = "⚠️ Email already exists";
        }
        $check->close();

 $stmt = $conn->prepare("INSERT INTO users (name, email, paasword, department, National_ID) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $name, $email, $hashed, $department, $National_ID);
        if ($stmt->execute()) {
            $message = "Successful registration✅";
            header("Location: req_system.php");
        } else {
            $message = "Error: " . $stmt->error;
        }
}}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
    <link rel="stylesheet" ="style.css">
    <style>
    body{ box-sizing: border-box; text-decoration: none; font-family:Arial,sans-serif;background:#fdfaf5;margin:0;padding:0;display:flex;justify-content:center;align-items:center;height: 50%;}
    .card{background:#fff;padding:0px;border-radius:12px;   background: #fdfaf5;;width:200px}
    h2{text-align:center;margin-bottom:30px}
    .field{margin-bottom:15px}
    .field label{display:block;font-weight:bold;margin-bottom:6px}
    .field input{width:100%;padding:10px;border:1px solid #ccc;border-radius:6px}
    .btn{width:100%;padding:12px;background:#007bff;color:white;border:none;border-radius:6px;font-size:16px;cursor:pointer}
    .btn:hover{background:#0056b3}
    .message{margin-top:15px;text-align:center;font-weight:bold;color:#333}
    </style>
</head>
<body>
    <div class="card">
        <h2>Register</h2>
        <form action="register.php" method="POST">
          <p class="msg"><?php echo $message; ?></p>
            <label for="name">Full Name:</label><br>
            <input  type="text" id="name" name="name" required>

           <br><label for="email">Email:</label><br>
            <input type="email" id="email" name="email" required>

               <br> <label for="password">Password:</label><br>
            <input type="password" id="password" name="paasword" required>

            <br> <label for="confirm_password">Confirm Password:</label><br>
            <input type="password" id="confirm_password" name="confirm_password" required>

           <br>    <label for="National_ID">National ID:</label><br>
            <input type="text" id="National_ID" name="National_ID" required>

            <br> <label for="department">Department:</label><br>
            <input type="text" id="department" name="department" required>

           
           <br> <label for="university">University:</label><br>
            <input type="text" id="university" name="university" required>
    <!--<button class="btn-primary" type="submit" onclick="window.location.href='req_system.php'">
      Register 
    </button>-->
          <br> <br><button class="btn-primary" type="submit">Register</button><br>
            <p class="small-text">Already have an account? <a href="login.php">Login here</a></p>
        </form>
    </div>
</body>
</html>

