<?php
session_start();

include 'index.php';

//  التحقق من تسجيل الدخول
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'graduate') {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];


// جلب اسم الطالب من جدول users باستخدام user_id
$query = "SELECT name FROM users WHERE id = '$user_id'";
$result = mysqli_query($conn, $query);
if (!$result) {
    die("Database error: " . mysqli_error($conn));
}
$row = mysqli_fetch_assoc($result);
$student_name = $row['name'] ?? 'Student';



// ✅ التحقق من إرسال النموذج وجلب القيم من الحقول

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // جلب القيم
    $notify_new_request = isset($_POST['notify_new_request']) ? 1 : 0;
    $notify_pending = isset($_POST['notify_pending']) ? 1 : 0;
    $notify_rejected = isset($_POST['notify_rejected']) ? 1 : 0;
    $notify_uploaded = isset($_POST['notify_uploaded']) ? 1 : 0;
    $via_email = isset($_POST['via_email']) ? 1 : 0;
    $via_in_app = isset($_POST['via_in_app']) ? 1 : 0;
    $reminder_days = isset($_POST['reminder_days']) ? intval($_POST['reminder_days']) : 0;

    // تحقق إذا عنده سجل
    $check = mysqli_query($conn, "SELECT * FROM notification_settings WHERE user_id='$user_id'")
        or die("Database error (check): " . mysqli_error($conn));

    if (mysqli_num_rows($check) > 0) {
        mysqli_query($conn, "UPDATE notification_settings SET 
            notify_new_request='$notify_new_request',
            notify_pending='$notify_pending',
            notify_rejected='$notify_rejected',
            notify_uploaded='$notify_uploaded',
            via_email='$via_email',
            via_in_app='$via_in_app',
            reminder_days='$reminder_days'
            WHERE user_id='$user_id'")
            or die("Update error: " . mysqli_error($conn));
    } else {
        mysqli_query($conn, "INSERT INTO notification_settings 
            (user_id, notify_new_request, notify_pending, notify_rejected, notify_uploaded, via_email, via_in_app, reminder_days)
            VALUES 
            ('$user_id', '$notify_new_request', '$notify_pending', '$notify_rejected', '$notify_uploaded', '$via_email', '$via_in_app', '$reminder_days')")
            or die("Insert error: " . mysqli_error($conn));
    }

    // إعادة تحميل نظيفة
    header("Location: " . $_SERVER['PHP_SELF'] . "?saved=1");
    exit();
}

// ⚙️ جلب الإعدادات الحالية بعد أي حفظ أو تحميل
$result = mysqli_query($conn, "SELECT * FROM notification_settings WHERE user_id='$user_id'")
    or die("Database error (settings): " . mysqli_error($conn));

$settings = mysqli_fetch_assoc($result) ?: [];




    // 📝 تحقق إذا يوجد سجل مسبق للطالب
   // $check = mysqli_query($conn, "SELECT * FROM notification_settings WHERE user_id='$user_id'");
    //if(!$check){
      //  die("Database error (check): " . mysqli_error($conn)); }
    // if (mysqli_num_rows($check) > 0) {
        // ✅ تحديث السجل
       // $update = mysqli_query($conn, "UPDATE notification_settings SET 
         //   notify_new_request='$notify_new_request',
           // notify_pending='$notify_pending',
            //notify_rejected='$notify_rejected',
            //notify_uploaded='$notify_uploaded',
            //via_email='$via_email',
            //via_in_app='$via_in_app',
            //reminder_days='$reminder_days'
            //WHERE user_id='$user_id'");
            // if(!$update){
            //die("Update error: " . mysqli_error($conn));  }
   // } else {
        // ✅ إدخال سجل جديد
      //  $insert = mysqli_query($conn, "INSERT INTO notification_settings 
           // (user_id, notify_new_request, notify_pending, notify_rejected, notify_uploaded, via_email, via_in_app, reminder_days)
            //VALUES 
           // ('$user_id', '$notify_new_request', '$notify_pending', '$notify_rejected', '$notify_uploaded', '$via_email', '$via_in_app', '$reminder_days')");

        //if(!$insert){
          //  die("Insert error: " . mysqli_error($conn));
     //   }
  //  }

    ?>





<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>setting</title>
    <link rel="stylesheet" href="setting_style.css">
</head>
<body>

<?php if (isset($_GET['saved'])) { echo "<p>Settings saved successfully!</p>"; } ?>


<!-- Back Button -->
<a href="req_system.php" class="back_btn">&#8592;</a>

 <!-- Header -->
<header class="header">
    <h4>Welcome, <span class="student_name"><?php echo $student_name; ?></span></h4>
</header>

<!-- رسالة نجاح -->
<?php if (isset($message)) { echo "<p style='color:green; text-align:center;'>$message</p>"; } ?>



    <!--main content الخيارات حق الاعدادت-->
    <form action="" method="POST" class="content">

    <!-- Request Pending -->
    <div class="item">
        <h3>Request Pending Reminder</h3>
        <p class="desc">Send reminder if the request is still pending</p>
        <label class="switch">
            <input type="checkbox" name="notify_pending" value="1" <?= ($settings['notify_pending'] ?? 0) ? 'checked' : '' ?>>
            <span class="slider"></span>
        </label>
    </div>

    <!-- Request Rejected -->
    <div class="item">
        <h3>Request Rejected</h3>
        <p class="desc">Notify when a request is rejected</p>
        <label class="switch">
            <input type="checkbox" name="notify_rejected" value="1" <?= ($settings['notify_rejected'] ?? 0) ? 'checked' : '' ?>>
            <span class="slider"></span>
        </label>
    </div>

    <!-- Recommendation Uploaded -->
    <div class="item">
        <h3>Recommendation Uploaded</h3>
        <p class="desc">Notify when the professor uploaded recommendation</p>
        <label class="switch">
            <input type="checkbox" name="notify_uploaded" value="1" <?= ($settings['notify_uploaded'] ?? 0) ? 'checked' : '' ?>>
            <span class="slider"></span>
        </label>
    </div>

 

    <!-- Save Button -->
    <button type="submit" class="save">Save Notification Settings</button>
</form>


<script src="jave/settings_sd.js"></script>
</body>
</html>