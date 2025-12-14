<?php
session_start();
include 'index.php';
require_once('tcpdf/tcpdf.php'); // نفس المسار القديم عشان ما يصير خطأ في التحميل

// 1) التحقق من أن المستخدم دكتور
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'professor') {
    header("Location: login.php");
    exit;
}

// 2) التأكد من وجود رقم الطلب في الرابط
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("❌ Invalid request ID.");
}

$request_id        = intval($_GET['id']);
$professor_user_id = $_SESSION['user_id'];

// ============================================================
// 3) جلب بيانات الطلب والخريج
// ============================================================
$sql = "SELECT 
            g.graduate_id,
            r.id AS request_id,
            r.user_id,
            r.major, 
            r.purpose, 
            r.type AS recommendation_type, 
            r.status,
            r.course,
            r.grades_file,
            g.gpa,
            g.graduation_year,
            g.cv_path,
            u.name,
            u.email,
            u.department,
            u.National_ID,
            u.university
        FROM requests r
        INNER JOIN users u     ON r.user_id = u.id
        INNER JOIN graduates g ON u.id = g.user_id
        WHERE r.id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $request_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows > 0) {
    $graduate        = $result->fetch_assoc();
    $graduate_id     = $graduate['graduate_id'];
    $student_user_id = $graduate['user_id'];
} else {
    die("❌ Request not found or graduate not found.");
}
$stmt->close();

// ============================================================
// 4) جلب رقم الدكتور (professor_id) من جدول professors
// ============================================================
$get_prof = $conn->prepare("SELECT professor_id FROM professors WHERE user_id = ?");
$get_prof->bind_param("i", $professor_user_id);
$get_prof->execute();
$prof_result   = $get_prof->get_result();
$prof_row      = $prof_result->fetch_assoc();
$professor_id  = $prof_row['professor_id'] ?? 0;
$get_prof->close();

// ============================================================
// 5) جلب مسودة توصية سابقة (إن وجدت) لنفس الطلب والدكتور
// ============================================================
$recommendation = null;

$rec_query = $conn->prepare("
    SELECT * FROM recommendations 
    WHERE request_id = ? AND professor_id = ?
");
$rec_query->bind_param("ii", $request_id, $professor_id);
$rec_query->execute();
$rec_result = $rec_query->get_result();

if ($rec_result->num_rows > 0) {
    $recommendation = $rec_result->fetch_assoc();
}
$rec_query->close();

$message_alert = "";

// ============================================================
// 6) حفظ التوصية عند الإرسال (مسودة أو إرسال نهائي)
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $content_raw = $_POST['recommendation_text'] ?? '';

// --------------------------------------------------------
//7) رفع قالب التوصيه بعد التعديل
// --------------------------------------------------------
if (!empty($_FILES['recommendation_file']['name'])) {

    $word_tmp  = $_FILES['recommendation_file']['tmp_name'];
    $word_name = time() . "_" . basename($_FILES['recommendation_file']['name']);

    $word_dir = __DIR__ . "/uploads/word_files";
    if (!is_dir($word_dir)) { mkdir($word_dir, 0777, true); }
    $word_path = $word_dir . "/" . $word_name;
    move_uploaded_file($word_tmp, $word_path);
   
    // يقرا محتوى ملف الوورد
    require_once 'vendor/autoload.php';
    $phpWord = \PhpOffice\PhpWord\IOFactory::load($word_path);

    $word_content = '';
    foreach ($phpWord->getSections() as $section) {
        foreach ($section->getElements() as $element) {
            if (method_exists($element, 'getText')) {  $word_content .= $element->getText() . "<br>";} } }

    // يسخدم الملف بدال ما يكتب نص
    $content_raw = $word_content;}

    $status      = $_POST['action']              ?? 'draft';

    // تنظيف النص من إضافات Word
    $clean = preg_replace('/<!--\[if.*?<!\[endif\]-->/is', '', $content_raw);
    $clean = preg_replace('/<v:.?<\/v:.?>/is', '', $clean);
    $clean = preg_replace('/<o:p>\s*<\/o:p>/is', '', $clean);
    $clean = preg_replace('/<span[^>]mso-[^>]>/is', '<span>', $clean);
    $clean = preg_replace('/<p[^>]>\s<\/p>/is', '', $clean);

    $content = mb_convert_encoding($clean, 'UTF-8', 'auto');

    // --------------------------------------------------------
    // 6.1 توليد ملف PDF وحفظه في مجلد uploads
    // --------------------------------------------------------
    $pdf = new TCPDF();
    $pdf->SetCreator(PDF_CREATOR);
    $pdf->SetAuthor('University of Baha');
    $pdf->SetTitle('Recommendation Letter');
    $pdf->SetSubject('Recommendation');

    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    $pdf->SetMargins(15, 15, 15);
    $pdf->SetAutoPageBreak(true, 15);
    $pdf->SetFont('times', '', 14);

    $pdf->AddPage();
    $pdf->writeHTML($content, true, false, true, false, '');

    $upload_dir = __DIR__. "/uploads";
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    $pdf_path = $upload_dir . "/recommendation_" . time() . ".pdf";
    $pdf->Output($pdf_path, "F");

    // --------------------------------------------------------
    // 6.2 INSERT أو UPDATE في جدول recommendations
    // --------------------------------------------------------
    $check = $conn->prepare("
        SELECT recommendation_id 
        FROM recommendations 
        WHERE request_id = ? AND professor_id = ?
    ");
    $check->bind_param("ii", $request_id, $professor_id);
    $check->execute();
    $exists = $check->get_result();
    $check->close();

    if ($exists->num_rows > 0) {
        // تحديث توصية موجودة
        $update = $conn->prepare("
            UPDATE recommendations 
            SET content = ?, date_created = NOW(), pdf_path = ?
            WHERE request_id = ? AND professor_id = ?
        ");
        $update->bind_param("ssii", $content, $pdf_path, $request_id, $professor_id);
        $update->execute();
        $update->close();
    } else {
        // إنشاء توصية جديدة
        $insert = $conn->prepare("
            INSERT INTO recommendations 
            (graduate_id, professor_id, content, pdf_path, date_created, request_id)
            VALUES (?, ?, ?, ?, NOW(), ?)
        ");
        $insert->bind_param("iissi", $graduate_id, $professor_id, $content, $pdf_path, $request_id);
        $insert->execute();
        $insert->close();
    }

    // --------------------------------------------------------
    //8)تحديث حالة الطلب + الإشعارات + التتبع
    // --------------------------------------------------------
    if ($status === 'draft') {

        // حالة الطلب: مسودة
        $req_update = $conn->prepare("UPDATE requests SET status = 'draft' WHERE id = ?");
        $req_update->bind_param("i", $request_id);
        $req_update->execute();
        $req_update->close();

        $message_alert = "✅ The recommendation has been saved as a draft.";

    } elseif ($status === 'completed') {

        // حالة الطلب: تم إرسال التوصية
        $req_update = $conn->prepare("UPDATE requests SET status = 'completed' WHERE id = ?");
        $req_update->bind_param("i", $request_id);
        $req_update->execute();
        $req_update->close();
// -------------------- إعدادات إشعار الطالب --------------------
$studentSettings = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT notify_uploaded
    FROM notification_settings 
    WHERE user_id='$student_user_id'
"));

if (!empty($studentSettings['notify_uploaded'])) {
    $msg_student = "Your recommendation has been completed and sent.";
    $notif_stu = $conn->prepare("
        INSERT INTO notifications (user_id, message, created_at) 
        VALUES (?, ?, NOW())
    ");
    $notif_stu->bind_param("is", $student_user_id, $msg_student);
    $notif_stu->execute();
    $notif_stu->close();
}



        // 🕒 إضافة سجل في جدول تتبع الطلبات (Recommendation Sent)
        $profUserId  = $professor_user_id;
        $statusTrack = 'Recommendation Sent';
        $noteTrack   = 'Recommendation Sent';

        $track = $conn->prepare("
            INSERT INTO track_request (request_id, user_id, status, note)
            VALUES (?, ?, ?, ?)
        ");
        $track->bind_param("iiss", $request_id, $profUserId, $statusTrack, $noteTrack);
        $track->execute();
        $track->close();

        $message_alert = "✅ The recommendation has been sent successfully!";
    }
    $profSettings = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT notify_uploaded
    FROM notification_settings 
    WHERE user_id='$profUserId'
"));

if (!empty($profSettings['notify_uploaded'])) {
   $msg_prof = "You have sent a recommendation for \"{$graduate['name']}\" regarding \"{$graduate['purpose']}\".";
 $notif_pr = $conn->prepare("
        INSERT INTO notifications (user_id, message, created_at) 
        VALUES (?, ?, NOW())
    ");
    $notif_pr->bind_param("is", $profUserId, $msg_prof);
    $notif_pr->execute();
    $notif_pr->close();
}

}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Recommendation Writing</title>

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://netdna.bootstrapcdn.com/font-awesome/4.0.3/css/font-awesome.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">

<script src="https://cdn.tiny.cloud/1/goropnqkoqgxvoy948qmqbr51wwmo7t8fbn424oqn5z9y8wg/tinymce/7/tinymce.min.js" referrerpolicy="origin"></script>
<script>
tinymce.init({
  selector: 'textarea',
  height: 300,
  menubar: false,
  plugins: 'lists link image table wordcount',
  toolbar: 'undo redo | bold italic underline | alignleft aligncenter alignright | bullist numlist | link',
  branding: false
});
</script>

<style>
body {
    margin: 0;
    font-family: "Poppins", sans-serif;
    background: #fdfaf6;
    display: flex;
}

h2 {
  font-size: 22px;
  color: #003366;
  margin-top: -19px;
}

/* Sidebar */
.sidebar {
  background-color: #c8e4eb;
  width: 230px;
  transition: width 0.3s;
  height: 100vh;
  padding-top: 20px;
  box-shadow: 2px 0 5px rgba(0,0,0,0.1);
  position: fixed;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
}

.sidebar.collapsed {
  width: 70px;
}

.sidebar .logo {
  text-align: center;
  margin-bottom: 30px;
}

.sidebar .logo img {
  width: 80px;
}

.menu-item {
  display: flex;
  align-items: center;
  padding: 12px 20px;
  color: #333;
  text-decoration: none;
  transition: background 0.3s;
}

.menu-item:hover {
  background: #bcd5db;
}

.menu-item i {
  font-size: 20px;
  margin-right: 10px;
  width: 25px;
  text-align: center;
}

.menu-text {
  font-size: 15px;
  white-space: nowrap;
}

.sidebar.collapsed .menu-text {
  display: none;
}

/* Bottom Section */
.bottom-section {
  margin-bottom: 20px;
}

/* Collapse Button */
.toggle-btn {
  position: absolute;
  top: 20px;
  right: -15px;
  background: #003366;
  color: #fff;
  border-radius: 50%;
  border: none;
  width: 30px;
  height: 30px;
  cursor: pointer;
}

/* Top Icons */
.top-icons {
  position: absolute;
  top: 20px;
  right: 30px;
  display: flex;
  align-items: center;
  gap: 20px;
}

.icon-btn {
  background: none;
  border: none;
  cursor: pointer;
  font-size: 20px;
  color: #333;
}

.icon-btn:hover {
  color: #003366;
}

/* Main Content */
.main-content {
  margin-left: 230px;
  padding: 30px;
  transition: margin-left 0.3s;
  width: 100%;
  position: relative;
}

.sidebar.collapsed + .main-content {
  margin-left: 70px;
}

/* Info box */
.info-box {
    background: #f1f1f1;
    padding: 20px;
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 10px;
    border-radius: 8px;
    margin-bottom: 20px;
}
.info-item { background: #e3e3e3; padding: 10px; border-radius: 6px; }
.info-item b { color: #003366; }

/* Form */
textarea { width: 100%; height: 200px; margin-top: 10px; padding: 10px; border: 1px solid #ccc; border-radius: 6px; resize: none; }
button { margin-top: 15px; padding: 10px 20px; border: none; border-radius: 6px; cursor: pointer; }
.send-btn  { background-color: #003366; color: white; margin-right: 10px; }
.draft-btn { background-color: #f39c12; color: white; margin-right: 10px; }
.cancel-btn{ background-color: #ccc; }

/* رسالة النجاح */
.alert-message {
    background-color:#d4edda; 
    color:#155724; 
    padding:10px; 
    border-radius:6px; 
    margin-bottom:15px;
}

/* 🔹 Responsive */
@media (max-width: 768px) {
  .main-content {
    margin-left: 70px;
  }
  .sidebar {
    width: 70px;
  }
  .menu-text {
    display: none;
  }
}
</style>
</head>
<body>

<!-- Sidebar -->
<div class="sidebar" id="sidebar">
  <button class="toggle-btn" id="toggleBtn"><i class="fas fa-bars"></i></button>

  <div>
    <div class="logo">
      <img src="LOGObl.PNG" alt="Logo">
    </div>

    <a href="requests.php" class="menu-item"><i class="fas fa-file-circle-plus"></i><span class="menu-text">New Request</span></a>
    <a href="professor_all_request.php" class="menu-item"><i class="fas fa-list"></i><span class="menu-text">All Requests</span></a>
    <a href="professor-profile.php" class="menu-item"><i class="fas fa-user"></i><span class="menu-text">Profile</span></a>
  </div>
  <div class="bottom-section">
    <a href="setting_D.php" class="menu-item"><i class="fas fa-gear"></i><span class="menu-text">Notification Settings</span></a>
  </div>
</div>

<div class="main-content">
    <h2>Recommendation Writing</h2>

    <?php if ($message_alert): ?>
        <div class="alert-message"><?= htmlspecialchars($message_alert) ?></div>
    <?php endif; ?>

    <?php if (!empty($graduate)): ?>
        <div class="info-box">
            <div class="info-item"><b>Name:</b> <?= htmlspecialchars($graduate['name']) ?></div>
            <div class="info-item"><b>National ID:</b> <?= htmlspecialchars($graduate['National_ID']) ?></div>
            <div class="info-item"><b>Email:</b> <?= htmlspecialchars($graduate['email']) ?></div>
            <div class="info-item"><b>Graduation Year:</b> <?= htmlspecialchars($graduate['graduation_year']) ?></div>
            <div class="info-item"><b>GPA:</b> <?= htmlspecialchars($graduate['gpa']) ?></div>
            <div class="info-item"><b>Major:</b> <?= htmlspecialchars($graduate['department']) ?></div>
            <div class="info-item"><b>Purpose:</b> <?= htmlspecialchars($graduate['purpose']) ?></div>
            <div class="info-item"><b>Recommendation Type:</b> <?= htmlspecialchars($graduate['recommendation_type']) ?></div>
            <div class="info-item"><b>CV:</b>
                <?php if (!empty($graduate['cv_path'])): ?>
                    <a href="<?= htmlspecialchars($graduate['cv_path']) ?>" target="_blank">View CV</a>
                <?php else: ?>No CV uploaded<?php endif; ?>
            </div>
            <div class="info-item"><b>Transcript:</b>
                <?php if (!empty($graduate['grades_file'])): ?>
                    <a href="uploads/<?= htmlspecialchars($graduate['grades_file']) ?>" target="_blank">View Transcript</a>
                <?php else: ?>No transcript uploaded<?php endif; ?>
            </div>
        </div>

        <form method="POST" enctype="multipart/form-data">
            <textarea name="recommendation_text"><?= htmlspecialchars($recommendation['content'] ?? '') ?></textarea>

            <button type="button" onclick="history.back()" class="cancel-btn">Cancel</button>
            <button type="submit" name="action" value="draft" class="draft-btn">Save Draft</button>
            <button type="submit" name="action" value="completed" class="send-btn">Send Recommendation</button>
            <!-- قالب التوصيه  -->
          <div style="margin-top:15px; display:flex; align-items:center; gap:15px;">
           <!-- زر تحميل قالب التوصية -->
           <a href="Letter_of_Recommendation_Template.docx"
           class="draft-btn" download>
         <i class="fa fa-download"></i> Recommendation Template</a>
           <!-- خانة إرفاق ملف Word -->
          <input type="file" name="recommendation_file" accept=".doc,.docx" style="padding:8px;"> </div>

            <?php if (!empty($recommendation['pdf_path']) && file_exists($recommendation['pdf_path'])): ?>
                <div style="margin-top:15px;">
                    <b>Download PDF:</b>
                    <a href="<?= htmlspecialchars($recommendation['pdf_path']) ?>" download>Download Recommendation PDF</a>
                </div>
            <?php endif; ?>
        </form>
    <?php else: ?>
        <p>No graduate found.</p>
    <?php endif; ?>
</div>

<script>
const toggleBtn = document.getElementById("toggleBtn");
const sidebar  = document.getElementById("sidebar");

toggleBtn.addEventListener("click", () => {
  sidebar.classList.toggle("collapsed");
});
</script>

</body>
</html>
