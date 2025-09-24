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

// ضبط الترميز للغة العربية
$conn->set_charset("utf8mb4");

// إنشاء جدول users إذا لم يكن موجود
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
    $name       = $conn->real_escape_string($_POST['name'] ?? '');
    $email      = $conn->real_escape_string($_POST['email'] ?? '');
    $paasword   = $_POST['paasword'] ?? '';
    $National_ID	 = $conn->real_escape_string($_POST['National_ID'] ?? '');
    $department = $conn->real_escape_string($_POST['department'] ?? '');

    if ($name && $email && $paasword && $department && $National_ID) {
        // تشفير كلمة المرور
        $hashed = password_hash($paasword, PASSWORD_BCRYPT);

        // استخدام prepared statement للتخزين
        $stmt = $conn->prepare("INSERT INTO users (name, email, paasword, department, National_ID) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $name, $email, $hashed, $department, $National_ID);

        if ($stmt->execute()) {
            $message = "تم إنشاء الحساب بنجاح ✅";
        } else {
            $message = "خطأ: " . $stmt->error;
        }
        $stmt->close();
    } else {
        $message = "⚠️ يرجى إدخال جميع الحقول";
    }
}
?>
<!doctype html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="utf-8">
  <title>تسجيل جديد</title>
  <style>
    body{font-family:Arial,sans-serif;background:#f9f9f9;margin:0;padding:0;display:flex;justify-content:center;align-items:center;height:100vh}
    .card{background:#fff;padding:30px;border-radius:12px;box-shadow:0 6px 10px rgba(0,0,0,0.1);width:400px}
    h2{text-align:center;margin-bottom:20px}
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
    <h2>تسجيل حساب جديد</h2>
    <form method="POST">
      <div class="field">
        <label>الاسم</label>
        <input type="text" name="name" required>
      </div>
      <div class="field">
        <label>الإيميل</label>
        <input type="email" name="email" required>
      </div>
      <div class="field">
        <label>الرمز (كلمة المرور)</label>
        <input type="password" name="paasword" required>
      </div>
      <div class="field">
        <label>الكلية</label>
        <input type="text" name="department" required>
      </div>
      <div class="field">
        <label>الهوية</label>
        <input type="text" name="National_ID" required>
      </div>
      <button type="submit" class="btn">تسجيل</button>
    </form>
    <?php if(!empty($message)): ?>
      <div class="message"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>
  </div>
</body>
</html>