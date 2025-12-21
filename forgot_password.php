<?php
include 'index.php'; 
$conn->set_charset("utf8mb4");
$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $conn->real_escape_string($_POST['email'] ?? '');

    if (empty($email)) {
        $message = "⚠️ Please enter your email address.";
    } else {
     
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();    
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            $user_id = $user['id'];
            $token = bin2hex(random_bytes(32)); 
            $expiry_time = date("Y-m-d H:i:s", time() + 3600); 
            $update_stmt = $conn->prepare("
                UPDATE users 
                SET reset_token = ?, token_expiry = ? 
                WHERE id = ?
            ");
            $update_stmt->bind_param("ssi", $token, $expiry_time, $user_id);
            $update_stmt->execute();
            $update_stmt->close();
            $reset_link = "http://localhost/GP2.ATHER/reset_password.php?token=" . 
            ئurlencode($token);
            $subject = "Password Reset Request";
            $body = "Hello,\n\nYou requested a password reset. Click the link below to set a new password:\n\n{$reset_link}\n\nThis link will expire in one hour.";
            $headers = 'From: noreply@yourdomain.com';

            
            
            // *للاختبار المحلي فقط*
             $message_type = "success";
        $message = "Password reset request successful. Click the link to set your new password: <a href='{$reset_link}'>Reset Password</a>";
            
        } else {
                    $message_type = "success";
            $message = "If this email is registered with us, a password reset link will be sent to it."; // ✅ تم إضافة ";
            } // هذا القوس صحيح، يغلق الشرط السابق
        }
    }


?>
<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <title>Forgot Password</title>
    <style>
       
        body{font-family:'Poppins',sans-serif; background:#fdfaf5;}
        .container{max-width:500px; margin:80px auto; padding:20px;}
            .card{
            background:#fff; 
            border-radius:15px; 
            padding:30px; 
            box-shadow:0 4px 15px rgba(8, 31, 78, 0.08);
            }
         .card h2 {
        text-align: center;
          }   
         
            .input-group label {
                font-weight: bold; 
                font-size: 0.9em; 
                display: block; 
                margin-bottom: 5px; 
            }
            
            button{
                width:100%; 
                padding:15px; 
                background:#ff7f50; 
                color:#fff; 
                border-radius:50px; 
                border: none; 
                cursor: pointer;
                /* 💡 تم إضافة هذا السطر */
                font-weight: bold; 
            }
        .input-group input{width:100%; padding:12px; border:none; border-radius:10px; background:#e0d9d3;}
      
        .message{margin-top:15px; text-align:center; color:red;}
        .message.success{color:green;}
        .input-group {
        margin-bottom: 20px; /* مسافة 20 بكسل أسفل مجموعة الإدخال */
            }
      .input-group input{width:100%; padding:12px; border:none; border-radius:10px; background:#e0d9d3;}
        button{width:100%; padding:15px; background:#ff7f50; color:#fff; border-radius:50px;}

        
        p.small-text {
            /* المسافة العلوية */
            margin-top: 15px; 
            /* 💡 هذا هو السطر المسؤول عن التوسيط */
            text-align: center; 
          
            font-size: 0.9em; 
        }

        /* 2. تنسيق الرابط نفسه */
        p.small-text a {
            color: #bb672fff; /* تغيير لون الرابط */
            text-decoration: none; /* إزالة الخط السفلي */
            font-weight: bold; 
            padding: 5px; 
            display: inline-block; 
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
            <h2>Forgot Password</h2>

            <form action="forgot_password.php" method="POST">

                <div class="input-group">
                    <label for="email">Enter your Email:</label>
                    <input type="email" id="email" name="email" required>
                </div>
                <button type="submit">Reset Password</button>
            </form>
            
            <?php if(!empty($message)): ?>
                <div class="message <?= $message_type ?? '' ?>">
                    <?= $message ?>
                </div>
                    <?php endif; ?>
              <p class="small-text"> 
         <a href="login.php">Login</a>
             </p>
        </div>
    </div>
</body>
</html>