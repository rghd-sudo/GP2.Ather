<?php
session_start();
include 'index.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

// 1. التحقق من الجلسة
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// 2. جلب بيانات الطالب تلقائياً
$student_info = [
    'name' => '',
    'id_number' => '',
    'department' => ' ',
];

$user_id = $_SESSION['user_id'];
$student_result = $conn->query("SELECT name, National_id, department FROM users WHERE id = $user_id");

if ($student_result && $student_result->num_rows > 0) {
    $student_data = $student_result->fetch_assoc();
    $student_info['name'] = $student_data['name'];
    $student_info['id_number'] = $student_data['National_id'] ?? '';
    $student_info['department'] = $student_data['department'] ?? '';
}

$message = "";

// 4. جلب الدكاترة
$professors = [];
$prof_result = $conn->query("
    SELECT p.professor_id, u.id AS user_id, u.name
    FROM professors p 
    JOIN users u ON p.user_id = u.id
");
if($prof_result && $prof_result->num_rows > 0){
    while($p = $prof_result->fetch_assoc()){
        $professors[] = $p;
    }
}

// 5. معالجة النموذج
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $major        = $conn->real_escape_string($_POST['major'] ?? '');
    $course       = $conn->real_escape_string($_POST['course'] ?? '');
    $professor_id = intval($_POST['professor_id'] ?? 0);
    $purpose      = $conn->real_escape_string($_POST['purpose'] ?? '');
    $type         = $conn->real_escape_string($_POST['type'] ?? '');

    $file_name   = NULL;
    $grades_file = NULL;

    // مجلد رفع الملفات
  $uploadDir = __DIR__ . '/uploads/'; 
if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

    // CV اختياري
    if (!empty($_FILES['file']['name'])) {
        $safeName = time() . "_" . basename($_FILES['file']['name']);
        $target = $uploadDir . $safeName;

        if (move_uploaded_file($_FILES['file']['tmp_name'], $target)) {
            $file_name = $safeName;
        }
    }

    // سجل الدرجات إجباري
    if (!empty($_FILES['grades']['name'])) {
        $safeGradesName = time() . "grades" . basename($_FILES['grades']['name']);
        $targetGrades = $uploadDir . $safeGradesName;

        if (move_uploaded_file($_FILES['grades']['tmp_name'], $targetGrades)) {
            $grades_file = $safeGradesName;
        } else {
            $message = "❌ Failed to upload grades file!";
        }
    } else {
        $message = "❌ Grades file is required!";
    }

    // ⭐ أهم تعديل — جلب user_id الخاص بالدكتور
    $pstmt = $conn->prepare("SELECT user_id FROM professors WHERE professor_id = ? LIMIT 1");
    $pstmt->bind_param("i", $professor_id);
    $pstmt->execute();
    $pRow = $pstmt->get_result()->fetch_assoc();
    $prof_user_id = $pRow['user_id'] ?? 0;   // هذا هو المستخدم اللي لازم يوصله الإشعار
    $pstmt->close();

    // إدخال الطلب
    $stmt = $conn->prepare("
        INSERT INTO requests (user_id, major, course, professor_id, purpose, type, file_name, grades_file)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $stmt->bind_param(
        "ississss",
        $user_id,
        $major,
        $course,
        $professor_id,
        $purpose,
        $type,
        $file_name,
        $grades_file
    );

    if ($stmt->execute()) {

        $newRequestId = $stmt->insert_id;
        $stmt->close();

        $message = "✅ Request submitted successfully!";

        // ⭐ أول خطوة تتبع — Created
        $status = 'Created';
        $note   = 'Student submitted the request';

        $trackStmt = $conn->prepare("
            INSERT INTO track_request (request_id, user_id, status, note)
            VALUES (?, ?, ?, ?)
        ");

        $trackStmt->bind_param("iiss", $newRequestId, $user_id, $status, $note);
        $trackStmt->execute();
        $trackStmt->close();

        // ⭐ جلب إعدادات الدكتور
        $s = $conn->prepare("SELECT notify_new_request FROM notification_settings WHERE user_id = ? LIMIT 1");
        $s->bind_param("i", $prof_user_id);
        $s->execute();
        $settings = $s->get_result()->fetch_assoc() ?? [];
        $s->close();

        // ⭐ إرسال إشعار للدكتور باستخدام user_id (التعديل الأساسي)
        if (!empty($settings['notify_new_request'])) {

            $notifMessage = "You have a new recommendation request from " . ($student_data['name'] ?? 'Student');

            $notifStmt = $conn->prepare("
                INSERT INTO notifications (user_id, message, created_at)
                VALUES (?, ?, NOW())
            ");

            $notifStmt->bind_param("is", $prof_user_id, $notifMessage);
            $notifStmt->execute();
            $notifStmt->close();
        }

    } else {
        $message = "❌ Error: " . $stmt->error;
    }
}

?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Recommendation Request</title>

<style>
/* نفس الـ CSS حقك بالكامل بدون أي تعديل */
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
      <input type="text" value="<?= htmlspecialchars($student_info['name']); ?>" readonly>
      <input type="text" value="<?= htmlspecialchars($student_info['id_number']); ?>" readonly>
      <input type="text" value="<?= htmlspecialchars($student_info['department']); ?>" readonly>
    </div>
  </div>

  <?php if($message): ?>
  <p class="status-message"><?= htmlspecialchars($message); ?></p>
  <?php endif; ?>

  <form id="reqform" class="form-wrap" method="post" enctype="multipart/form-data">

    <div class="field">
      <label>Course Name*</label>
      <input type="text" name="course" required>
    </div>

    <div class="field">
      <label>Professor*</label>
      <select name="professor_id" required>
        <option value="">-- Select Professor --</option>
        <?php foreach($professors as $p): ?>
        <option value="<?= $p['professor_id'] ?>"><?= $p['name'] ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="field">
      <label>Purpose*</label>
      <textarea name="purpose" required></textarea>
    </div>

    <div class="field full-width">
      <label>Recommendation Type</label>
      <label><input type="radio" name="type" value="Academic" checked> Academic</label>
      <label><input type="radio" name="type" value="Professional"> Professional</label>
    </div>

    <div class="field full-width">
      <label>Upload CV (optional)</label>
      <input type="file" name="file" accept=".pdf,.doc,.docx">
    </div>

    <div class="field full-width">
      <label>Upload Grades*</label>
      <input type="file" name="grades" accept=".pdf,.png,.jpg,.jpeg" required>
    </div>

    <div class="submit-wrap full-width">
      <button type="submit" class="btn">Submit</button>
    </div>
  </form>

</div>
</body>
</html>