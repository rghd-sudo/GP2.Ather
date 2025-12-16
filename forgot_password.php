<?php
include 'index.php'; // يفترض أن هذا الملف يحتوي على $conn للاتصال بقاعدة البيانات
$conn->set_charset("utf8mb4");
$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $conn->real_escape_string($_POST['email'] ?? '');

    if (empty($email)) {
        $message = "⚠️ Please enter your email address.";
    } else {
        // 1. البحث عن المستخدم
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        // 2. معالجة الإرسال (بغض النظر عن وجود الإيميل - للأمان)
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            $user_id = $user['id'];
            
            // 3. توليد الرمز (Token)
            $token = bin2hex(random_bytes(32)); 
            
            // 4. تحديد انتهاء الصلاحية (بعد ساعة واحدة من الآن)
            $expiry_time = date("Y-m-d H:i:s", time() + 3600); 

            // 5. حفظ الرمز وانتهاء الصلاحية في قاعدة البيانات
            $update_stmt = $conn->prepare("
                UPDATE users 
                SET reset_token = ?, token_expiry = ? 
                WHERE id = ?
            ");
            $update_stmt->bind_param("ssi", $token, $expiry_time, $user_id);
            $update_stmt->execute();
            $update_stmt->close();

            // 6. إعداد رابط إعادة التعيين
            // تأكد من أن reset_password.php هو العنوان الصحيح
           // 6. إعداد رابط إعادة التعيين
// تم تعديل الرابط ليشمل مسار المجلد الصحيح (GP2.ATHER)
$reset_link = "http://localhost/GP2.ATHER/reset_password.php?token=" . urlencode($token);
            
            // 7. إرسال البريد الإلكتروني (محاكاة)
            $subject = "Password Reset Request";
            $body = "Hello,\n\nYou requested a password reset. Click the link below to set a new password:\n\n{$reset_link}\n\nThis link will expire in one hour.";
            $headers = 'From: noreply@yourdomain.com';

            // إذا كنت على localhost، لن يتم الإرسال فعلياً إلا إذا أعددت خادم بريد
            // للبيئة المحلية: يمكنك تخطي هذا وعرض الرابط كرسالة
            // mail($email, $subject, $body, $headers); 
            
            // *للاختبار المحلي فقط*
             $message_type = "success";
             $message = "تم إرسال رابط إعادة التعيين إلى بريدك الإلكتروني. (الرابط: <a href='{$reset_link}'>اضغط هنا</a>)";
            
        } else {
            // نقطة أمان: رسالة موحدة لتجنب الكشف عن وجود الإيميل
            $message_type = "success";
            $message = "إذا كان هذا البريد الإلكتروني مسجلاً لدينا، سيتم إرسال رابط إعادة التعيين إليه.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <title>Forgot Password</title>
    <style>
        /* (نسخ أنماط body, .shapes, .container, .card, .input-group, button, .message من login.php) */
        /* للمحافظة على التنسيق الموحد */
        /* سأختصر CSS هنا لعدم تكرار الأكواد، ولكن يجب أن تكون موجودة فعلياً */
        body{font-family:'Poppins',sans-serif; background:#fdfaf5;}
        .container{max-width:500px; margin:80px auto; padding:20px;}
        .card{background:#fff; border-radius:15px; padding:30px; box-shadow:0 4px 15px rgba(0,0,0,0.08);}
        .input-group input{width:100%; padding:12px; border:none; border-radius:10px; background:#e0d9d3;}
        button{width:100%; padding:15px; background:#ff7f50; color:#fff; border-radius:50px;}
        .message{margin-top:15px; text-align:center; color:red;}
        .message.success{color:green;}
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <h2>Forgot Password</h2>

            <form action="forgot_password.php" method="POST">
                <div class="input-group">
                    <label for="email">Enter your Email:</label>
                    <input type="email" id="email" name="email" required>
                </div>
                <button type="submit">Send Reset Link</button>
            </form>
            
            <?php if(!empty($message)): ?>
                <div class="message <?= $message_type ?? '' ?>">
                    <?= $message ?>
                </div>
                    <?php endif; ?>
             <p class="small-text" style="margin-top: 15px;">
            <a href="login.php">&#x2190; </a>
             </p>
        </div>
    </div>
</body>
</html>