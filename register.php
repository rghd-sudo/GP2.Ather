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
session_start();

// عرض الرسالة إذا موجودة
if (isset($_SESSION['message'])) {
    echo "<script>alert('" . $_SESSION['message'] . "');</script>";
    unset($_SESSION['message']); // مسح الرسالة بعد عرضها مرة واحدة
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
}
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
    .field input{width:99%;padding:9px;border:1px solid #ccc;border-radius:5px}
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
    .message{margin-top:15px;text-align:levt;font-weight:bold;color:#333}
    </style>
</head>
<body>
    <div class="card">
        
     <?php if($message): ?>
      <p style="margin:20px 0;padding:12px;background:#fff8e6;border-radius:6px;box-shadow:var(--shadow)"><?= $message; ?></p>
    <?php endif; ?>
    <h2>Register</h2>
       
        <form action="register.php" method="POST">
          <div class="field">
            <label for="name">Full Name:</label>
            <input  type="text" id="name" name="name" required>
            </div>
            <div class="field">
           <label for="email">Email:</label>
            <input type="email" id="email" name="email" required>
</div>
<div class="field">
               <label for="password">Password:</label>
            <input type="password" id="password" name="paasword" required>
</div>
<div class="field">
            <label for="confirm_password">Confirm Password:</label>
            <input type="password" id="confirm_password" name="confirm_password" required>
</div>
<div class="field">
           <label for="National_ID">National ID:</label>
            <input type="text" id="National_ID" name="National_ID" required>
</div>
<div class="field">
           
           <label for="university">University:</label>
            <input type="text" id="university" name="university" required>
            </div>
<div class="field">
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

