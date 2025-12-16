<?php
include 'index.php'; 
$conn->set_charset("utf8mb4");
$message = "";
$token = $_GET['token'] ?? '';

// 1. التحقق من وجود الرمز في الرابط (GET)
if (empty($token)) {
    $message = " Missing reset token in the URL.";
} else {
    
  // هذا تعديل مؤقت لاختبار الرمز فقط - غير آمن في بيئة الإنتاج
    $stmt = $conn->prepare("
        SELECT id FROM users 
        WHERE reset_token = ? 
    ");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        $message = "⚠️ Invalid or expired reset link. Please request a new one.";
    } else {
        $user = $result->fetch_assoc();
        $user_id = $user['id'];

        // 3. معالجة تحديث كلمة المرور (عند إرسال النموذج)
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $new_password = $_POST['paasword'] ?? '';
            $confirm_password = $_POST['confirm_paasword'] ?? '';
            $post_token = $_POST['token'] ?? ''; // الرمز المخفي في النموذج

            if (empty($new_password) || $new_password !== $confirm_password) {
                $message = "⚠️ Passwords do not match or are empty.";
            } elseif ($post_token !== $token) {
                 $message = "⚠️ Token mismatch.";
            } else {
                // 4. تشفير كلمة المرور الجديدة
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

                // 5. تحديث كلمة المرور وتنظيف الرمز
                $update_stmt = $conn->prepare("
                    UPDATE users 
                    SET paasword = ?, reset_token = NULL, token_expiry = NULL 
                    WHERE id = ?
                ");
                $update_stmt->bind_param("si", $hashed_password, $user_id);
                $update_stmt->execute();
                $update_stmt->close();

                $message_type = "success";
                $message = "✅ Password updated successfully. You can now log in.";
                header("Refresh: 3; url=login.php"); // توجيه بعد 3 ثوانٍ
            }
        }
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <title>Reset Password</title>
    <style>
        /* (نسخ الأنماط هنا) */
        body{font-family:'Poppins',sans-serif; background:#fdfaf5;}
        .container{max-width:500px; margin:80px auto; padding:20px;}
        .card{background:#fff; border-radius:15px; padding:30px; box-shadow:0 4px 15px rgba(0,0,0,0.08);}
        .input-group input{width:100%; padding:12px; border:none; border-radius:10px; background:#e0d9d3;}
        button{width:100%; padding:15px; background:#ff7f50; color:#fff; border-radius:50px; font-weight: bold; }
        .message{margin-top:15px; text-align:center; color:red;}
        .message.success{color:green;}
        .card h2 {
         text-align: center;
         font-weight: bold;
         }
         /* هذا الجزء هو المسؤول عن تنسيق الليبلات */
        .input-group label {
            font-weight: bold; /* يجعل الخط غامقاً (Bold) */
            font-size: 0.9em;  /* يصغر حجم الخط */
            display: block;    /* يجعل الليبل يأخذ سطراً كاملاً */
            margin-bottom: 5px; /* يضيف مسافة صغيرة أسفل الليبل */
        }
        .input-group {
        margin-bottom: 20px; /* القيمة الحالية */
         }
 /* 1. تنسيق الفقرة الحاوية للرابط */
p.small-text {
    /* المسافة العلوية */
    margin-top: 15px; 
    /* توسيط الرابط أفقياً */
    text-align: center; 
    /* يمكنك إزالة السطر التالي إذا لم يكن له تأثير واضح */
    font-size: 0.9em; 
}

/* 2. تنسيق الرابط نفسه */
p.small-text a {
    color: #ff7f50; 
    text-decoration: none; /* إزالة الخط السفلي */
    font-weight: bold; 
    padding: 5px; /* مسافة حول النص (اختياري) */
    display: inline-block; /* لضمان تطبيق البادينج بشكل صحيح */
}

/* 3. تأثير عند مرور الماوس (اختياري) */
p.small-text a:hover {
    text-decoration: underline; 
    color: #d1643c; 
}
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <h2>Reset Password</h2>
            
            <?php if(!empty($message)): ?>
                <div class="message <?= $message_type ?? '' ?>">
                    <?= $message ?>
                </div>
            <?php endif; ?>

            <?php if ($result->num_rows === 1 && empty($_POST)): // اعرض النموذج فقط إذا كان الرمز صالحاً ولم يتم إرسال بيانات Form بعد ?>
                <form action="reset_password.php?token=<?= htmlspecialchars($token) ?>" method="POST">
                    <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>"> 

                    <div class="input-group">
                        <label for="paasword">New Password:</label>
                        <input type="password" id="paasword" name="paasword" required>
                    </div>
                    <div class="input-group">
                        <label for="confirm_paasword">Confirm New Password:</label>
                        <input type="password" id="confirm_paasword" name="confirm_paasword" required>
                    </div>
                    <button type="submit">Change Password</button>
                </form>
            <?php endif; ?>
            
        <p class="small-text"> 
         <a href="login.php">Login</a>
        </p>
        </div>
    </div>
</body>
</html>