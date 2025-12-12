<?php
session_start();
include 'index.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'professor') {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
// 📝 جلب student_id من السيشن
$user_id = $_SESSION['user_id'];

// جلب الاسم الكامل عدليها حسب الاعندك الجدوال 
$result = mysqli_query($conn, "SELECT name FROM users WHERE id='$user_id'");
$row = mysqli_fetch_assoc($result);
$user_name = $row['name'];

// 📝 معالجة الفورم عند الضغط على Save
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $notify_new_request = isset($_POST['notify_new_request']) ? 1 : 0;
    $notify_pending = isset($_POST['notify_pending']) ? 1 : 0;
    $notify_rejected = isset($_POST['notify_rejected']) ? 1 : 0;
    $notify_uploaded = isset($_POST['notify_uploaded']) ? 1 : 0;
   // 📝 تحقق إذا يوجد سجل مسبق للطالب
    $check = mysqli_query($conn, "SELECT * FROM notification_settings WHERE user_id='$user_id'");
    if (mysqli_num_rows($check) > 0) {
        // تحديث السجل
        mysqli_query($conn, "UPDATE notification_settings SET 
            notify_new_request='$notify_new_request',
            notify_pending='$notify_pending',
            notify_rejected='$notify_rejected',
            notify_uploaded='$notify_uploaded'
            
            WHERE user_id='$user_id'");
    } else {
          // إدخال سجل جديد
        mysqli_query($conn, "INSERT INTO notification_settings 
            (user_id, notify_new_request, notify_pending, notify_rejected, notify_uploaded)
            VALUES 
            ('$user_id', '$notify_new_request', '$notify_pending', '$notify_rejected', '$notify_uploaded')");
    }

    $message = "Settings saved successfully!";
}

// 📝 جلب الإعدادات الحالية للعرض
$result = mysqli_query($conn, "SELECT * FROM notification_settings WHERE user_id='$user_id'");
$settings = mysqli_fetch_assoc($result);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
     <link rel="stylesheet" href="setting_style.css">
    <title>setting</title>
   
</head>
<body>
     
<!-- Back Button -->
<a href="requests.php" class="back_btn">&#8592;</a>
<!-- Header -->
<header class="header">                 <!-- يتغير ع جدول الدكتور -->
    <h4>Welcome, <span class="student_name"><?php echo $user_name; ?></span></h4>
  

<!-- رسالة نجاح -->
<?php if (isset($message)) { echo "<p style='color:green; text-align:center;'>$message</p>"; } ?>

   <!--main content الخيارات حق الاعدادت-->
<form method="POST">
 
    <div class="content">

       <!-- New Request -->
    <div class="item">
        <h3>New Request Submitted</h3>
        <p class="desc">Notify when a new recommendation request is submitted</p>
        <label class="switch">
            <input type="checkbox" name="notify_new_request" value="1" <?= isset($settings['notify_new_request']) && $settings['notify_new_request'] ? 'checked' : '' ?>>
            <span class="slider"></span>
        </label>
    </div>
  

         <!-- Request Pending -->
    <div class="item">
        <h3>Request Pending Reminder</h3>
        <p class="desc">Send reminder if the request is still pending</p>
        <label class="switch">
            <input type="checkbox" name="notify_pending" value="1" <?= isset($settings['notify_pending']) && $settings['notify_pending'] ? 'checked' : '' ?>>
            <span class="slider"></span>
        </label>
    </div>



     <!-- Request Rejected -->
    <div class="item">
        <h3>Request Rejected</h3>
        <p class="desc">Notify when a request is rejected</p>
        <label class="switch">
            <input type="checkbox" name="notify_rejected" value="1" <?= isset($settings['notify_rejected']) && $settings['notify_rejected'] ? 'checked' : '' ?>>
            <span class="slider"></span>
        </label>
    </div>


  <!-- Recommendation Uploaded -->
    <div class="item">
        <h3>Recommendation Uploaded</h3>
        <p class="desc">Notify when the professor uploaded recommendation</p>
        <label class="switch">
            <input type="checkbox" name="notify_uploaded" value="1" <?= isset($settings['notify_uploaded']) && $settings['notify_uploaded'] ? 'checked' : '' ?>>
            <span class="slider"></span>
        </label>
    </div>

                    
    
    
                  
                  <button class="save">  Save Notitfcation setting</button>
</form>
                  <script src="jave/settings_sd.js"></script>
</body>
</html>