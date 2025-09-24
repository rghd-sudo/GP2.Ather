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
                $message = "⚠️ كلمة المرور غير صحيحة";
            }
        } else {
            $message = "⚠️ الإيميل غير موجود";
        }
        $stmt->close();
    } else {
        $message = "⚠️ يرجى إدخال الإيميل والرمز";
    }
}
?>
<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
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