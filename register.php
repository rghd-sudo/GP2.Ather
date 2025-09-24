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
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="login-container">
        <h2>Register</h2>
        <form action="register.php" method="POST">
          <p class="msg"><?php echo $message; ?></p>
            <label for="name">Full Name:</label>
            <input  type="text" id="name" name="name" required>

           <label for="email">Email:</label>
            <input type="email" id="email" name="email" required>

                <label for="password">Password:</label>
            <input type="password" id="password" name="paasword" required>

             <label for="confirm_password">Confirm Password:</label>
            <input type="password" id="confirm_password" name="confirm_password" required>

               <label for="National_ID">National ID:</label>
            <input type="text" id="National_ID" name="National_ID" required>

             <label for="department">Department:</label>
            <input type="text" id="department" name="department" required>

           
            <label for="university">University:</label>
            <input type="text" id="university" name="university" required>
    <!--<button class="btn-primary" type="submit" onclick="window.location.href='req_system.php'">
      Register 
    </button>-->
           <button class="btn-primary" type="submit">Register</button>
            <p class="small-text">Already have an account? <a href="login.php">Login here</a></p>
        </form>
    </div>
</body>
</html>

