<?php
session_start();
include 'index.php'; // ملف الاتصال بقاعدة البيانات

// 1. التحقق من الجلسة
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// 2. جلب بيانات الطالب تلقائياً (باستخدام الأعمدة الصحيحة National_id و name)
$student_info = [
    'name' => '',
    'id_number' => '',
];

$user_id = $_SESSION['user_id'];
// الاستعلام لجلب الاسم والرقم الوطني من جدول users
$student_result = $conn->query("SELECT name, National_id FROM users WHERE id = $user_id");

if ($student_result && $student_result->num_rows > 0) {
    $student_data = $student_result->fetch_assoc();
    $student_info['name'] = $student_data['name'];
    // استخدام 'National_id' كما هو موجود في مخطط قاعدة البيانات
    $student_info['id_number'] = $student_data['National_id'] ?? ''; 
}

// 3. إنشاء جدول requests إذا لم يكن موجود
$conn->query("
CREATE TABLE IF NOT EXISTS requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL, -- رقم المستخدم صاحب الطلب
    major VARCHAR(100),
    course VARCHAR(50),
    professor_id INT NOT NULL, -- معرف الدكتور
    purpose TEXT,
    type VARCHAR(50),
    file_name VARCHAR(255),
    grades_file VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

$message = "";

// 4. جلب قائمة الدكاترة من قاعدة البيانات
$professors = [];
$prof_result = $conn->query("SELECT p.professor_id, u.name, u.email, u.department FROM professors p JOIN users u ON p.user_id = u.id");
if($prof_result && $prof_result->num_rows > 0){
    while($p = $prof_result->fetch_assoc()){
        $professors[] = $p;
    }
}

// 5. معالجة النموذج (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // البيانات التلقائية
    $user_id = $_SESSION['user_id'];
    
    // البيانات المدخلة من النموذج
    $major = $conn->real_escape_string($_POST['major'] ?? '');
    $course = $conn->real_escape_string($_POST['course'] ?? '');
    $professor_id = intval($_POST['professor_id'] ?? 0);
    $purpose = $conn->real_escape_string($_POST['purpose'] ?? '');
    $type = $conn->real_escape_string($_POST['type'] ?? '');
    $file_name = NULL;
    $grades_file = NULL;

    // رفع الملف الاختياري (CV)
    if (!empty($_FILES['file']['name'])) {
        $uploadDir =__DIR__.'/uploads/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

        $safeName = time() . "_" . basename($_FILES['file']['name']);
        $target = $uploadDir . $safeName;

        if (move_uploaded_file($_FILES['file']['tmp_name'], $target)) {
            $file_name = $safeName;
        }
    }}

    // رفع سجل الدرجات (إجباري)
    if (!empty($_FILES['grades']['name'])) {
         $uploadDir =__DIR__.'/uploads/';
        $safeGradesName = time() . "_grades_" . basename($_FILES['grades']['name']);
        $targetGrades = $uploadDir . $safeGradesName;

        if (move_uploaded_file($_FILES['grades']['tmp_name'], $targetGrades)) {
            $grades_file = $safeGradesName;
        } else {
            $message = "❌ فشل رفع سجل الدرجات";
        }
    } else {
        $message = "❌ رفع سجل الدرجات إجباري";
    }

    // إدخال البيانات إذا لم يكن هناك رسالة خطأ
   // إدخال البيانات إذا لم يكن هناك رسالة خطأ
if (!$message) {
    $sql = "INSERT INTO requests (user_id, major, course, professor_id, purpose, type, file_name, grades_file)
            VALUES ('$user_id', '$major', '$course', '$professor_id', '$purpose', '$type', " .
            ($file_name ? "'$file_name'" : "NULL") . ", '$grades_file')";
    if ($conn->query($sql)) {
        $message = "✅ تم حفظ الطلب بنجاح";

        // ⭐ بعد نجاح الإدخال — إضافة سجل في track_request
        $newRequestId = $conn->insert_id;  // رقم الطلب الجديد
        $requestId = $newRequestId;
        $studentUserId = $_SESSION['user_id'];
        $status = 'Created';
        $note = 'Student submitted request';

        $stmt = $conn->prepare("INSERT INTO track_request (request_id, status) VALUES (?, ?)");
        $stmt->bind_param("is", $requestId, $status);
        $stmt->execute();
        $stmt->close();
        
        if ($settings['notify_new_request']) {
    $message = "You have a new recommendation request from " . $student_data['name'] . ".";
    
        $notif = $conn->prepare("INSERT INTO notifications (user_id, message, created_at) VALUES (?, ?, NOW())");
        $notif->bind_param("is", $professor_id, $message);
        $notif->execute();
        $notif->close();
}

    } else {
        $message = "❌ خطأ: " . $conn->error;
    }
}
  /* تحقق من إعدادات التنبيهات للأستاذ

  // ✅ بعد إدخال الطلب في قاعدة البيانات — إرسال إشعار للدكتور
include 'notify.php';
sendNotify($professor_id, "New recommendation request submitted by " . $student_info['name'] . ".", "new_request");

  $sql = "SELECT notify_new_request FROM notification_settings WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $professor_id);
$stmt->execute();
$result = $stmt->get_result();
$settings = $result->fetch_assoc();
if ($settings && $settings['notify_new_request']) {
    // إرسال إشعار للأستاذ
    $notif_message = "New recommendation request submitted by " . $student_info['name'] . ".";
    $notif_sql = "INSERT INTO notifications (user_id, message, created_at) VALUES (?, ?, NOW())";
    $notif_stmt = $conn->prepare($notif_sql);
    $notif_stmt->bind_param("is", $professor_id, $notif_message);
    $notif_stmt->execute();*/

?>

<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Recommendation Request</title>
<style>
:root {
  --bg-color: #fbf7f2;
  --header-bg: #cfe7e8;
  --input-bg: #fff;
  --main-text: #2b2b2b;
  --sub-text: #473d57;
  --accent-color: #f07963;
  --accent-hover: #d15a45;
  --shadow: 0 4px 12px rgba(0,0,0,0.08);
  --border-radius: 10px;
  font-family: 'Arial','Tahoma',sans-serif;
}

body {
  margin:0; padding:0;
  background-color: var(--bg-color);
  color: var(--main-text);
display:flex; justify-content:center;
  direction: ltr; /* تم الحفاظ عليها حسب الكود الأصلي */
}

.container {
  width:100%; max-width:720px;
  margin:40px auto;
  padding:20px;
}

.header-card {
  background-color: var(--header-bg);
  border-radius: var(--border-radius);
  padding:20px 25px;
  margin-bottom:25px;
  box-shadow: var(--shadow);
  display:grid;
  grid-template-columns:60px 1fr 1fr;
  gap:15px;
  align-items:center;
}

.icon-container {
  width:60px; height:60px;
  background-color:#3b9196;
  border-radius:50%;
  display:flex; justify-content:center; align-items:center;
}
.icon-container::before {
  content:url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="white" width="30px" height="30px"><path d="M0 0h24v24H0z" fill="none"/><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>');
}

.request-title {
  font-size:22px;
  font-weight:bold;
  grid-column:2/3;
  color:var(--main-text);
}

.student-info-section {
  grid-column:3/4;
  border-right:2px solid rgba(0,0,0,0.1);
  padding-right:15px;
}

.student-info-title {
  font-size:18px; font-weight:700;
  color:var(--sub-text);
  display:block; margin-bottom:10px;
}

.student-info-section input {
  display:block; width:100%;
  border:1px solid #ccc; border-radius:5px;
  background:transparent; padding:6px 10px; margin-bottom:8px;
  font-size:15px; color: var(--main-text);
  outline:none;
}
.student-info-section input:focus {
  border-color: var(--accent-color);
  box-shadow:0 0 4px rgba(240,121,99,0.4);
}

/* التعديل لتمكين عمودين للحقول الرئيسية */
.form-wrap {
  display:grid; 
  grid-template-columns: 1fr 1fr; 
  gap:20px;
}
.full-width {
    grid-column: 1 / -1; /* لجعل حقل يمتد على عرض الصف الكامل */
}

.field { margin-bottom:20px; }
.label { font-weight:700; margin-bottom:6px; display:block; color:var(--sub-text); }

input[type="text"], textarea, select {
  width:100%; padding:12px; border-radius:5px;
  border:1px solid #ccc; background-color: var(--input-bg);
  font-size:15px; color:var(--main-text); box-sizing:border-box;
}

input[type="text"]:focus, textarea:focus, select:focus {
  border-color: var(--accent-color);
  box-shadow:0 0 4px rgba(240,121,99,0.4);
}

textarea { min-height:100px; resize:vertical; }

.radios { display:flex; gap:20px; margin-top:5px; }
.radios label { cursor:pointer; display:flex; align-items:center; font-size:15px; }
.radios input[type="radio"]{ display:none; }
.radios label span::before{
  content:''; width:18px; height:18px; border-radius:50%; border:2px solid var(--sub-text);
  margin-right:8px; display:inline-block; transition: all 0.2s;
}
.radios input[type="radio"]:checked + span::before{
  background-color: var(--accent-color); border-color: var(--accent-color);
  box-shadow: inset 0 0 0 4px white;
}

.submit-wrap { display:flex; justify-content:flex-start; margin-top:20px; }
.btn {
  background: var(--accent-color); color:white; padding:14px 35px;
  border-radius:8px; border:none; font-size:18px; font-weight:700;
  cursor:pointer; box-shadow: var(--shadow);
  transition: background-color 0.3s;
}
.btn:hover { background-color: var(--accent-hover); }

.status-message {
  margin:20px 0;
  padding:15px;
  background-color:#f8d7da;
  border:1px solid #f5c6cb;
  color:#721c24;
  border-radius:6px;
  font-weight:bold;
  text-align:center;
}
.back_btn {
    display: inline-block;
    margin-bottom: 20px;
    font-size: 24px;
    color: #03060a;
    text-decoration: none;
}
</style>
</head>
<body>
   <a href="req_system.php" class="back_btn">&#8592;</a>
<div class="container">
  <div class="header-card">
    <div class="icon-container"></div>
    <div class="request-title">Recommendation Request</div>
    <div class="student-info-section">
      <span class="student-info-title">Personal Information</span>
      <input type="text" placeholder="Name" name="name_display" form="reqform" value="<?= htmlspecialchars($student_info['name']); ?>" readonly>
      <input type="text" placeholder="ID" name="id_display" form="reqform" value="<?= htmlspecialchars($student_info['id_number']); ?>" readonly>

      </div>
  </div>

  <?php if($message): ?>
  <p class="status-message"><?= htmlspecialchars($message); ?></p>
  <?php endif; ?>

  <form id="reqform" class="form-wrap" method="post" enctype="multipart/form-data" onsubmit="return validateForm()">

    <div class="field">
      <label>Major*</label> <input type="text" name="major" value="<?= htmlspecialchars($_POST['major'] ?? '') ?>" required>
    </div>

    <div class="field">
      <label>Course Name*</label>
      <input type="text" name="course" value="<?= htmlspecialchars($_POST['course'] ?? '') ?>" required>
    </div>

    <div class="field">
      <label>Professor*</label>
      <select name="professor_id" required>
        <option value="">-- Select Professor --</option>
        <?php foreach($professors as $p): ?>
          <option value="<?= $p['professor_id'] ?>" <?= (isset($_POST['professor_id']) && $_POST['professor_id']==$p['professor_id'])?'selected':'' ?>><?= $p['name'] ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="field">
      <label>Purpose of Recommendation*</label>
      <textarea name="purpose" required><?= htmlspecialchars($_POST['purpose'] ?? '') ?></textarea>
    </div>
    
    <div class="field full-width">
      <label>Recommendation Type</label>
      <div class="radios">
        <label><input type="radio" name="type" value="Academic" <?= (!isset($_POST['type']) || $_POST['type']=='Academic')?'checked':'' ?>><span>Academic</span></label>
        <label><input type="radio" name="type" value="Professional" <?= (isset($_POST['type']) && $_POST['type']=='Professional')?'checked':'' ?>><span>Professional</span></label>
      </div>
    </div>

    <div class="field full-width">
      <label>Upload CV (optional)</label>
      <input type="file" name="file" accept=".pdf,.doc,.docx">
    </div>

    <div class="field full-width">
      <label>Upload Grades*</label>
      <input type="file" name="grades" id="grades-file" accept=".pdf,.png,.jpg,.jpeg">
    </div>

    <div class="submit-wrap full-width">
      <button type="submit" class="btn">Submit</button>
    </div>
  </form>
</div>

<script>
function validateForm() {
  const grades = document.getElementById('grades-file');
  if(!grades.value) {
    alert('Uploading Grades is required!');
    return false;
  }
  return true;
}
</script>
</body>
</html>