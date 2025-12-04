<?php
session_start();
include 'index.php';

// 1. التحقق من الجلسة
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// 2. جلب بيانات الطالب تلقائياً
$student_info = [
    'name' => '',
    'id_number' => '',
];

$user_id = $_SESSION['user_id'];
$student_result = $conn->query("SELECT name, National_id FROM users WHERE id = $user_id");

if ($student_result && $student_result->num_rows > 0) {
    $student_data = $student_result->fetch_assoc();
    $student_info['name'] = $student_data['name'];
    $student_info['id_number'] = $student_data['National_id'] ?? '';
}

// 3. إنشاء جدول الطلبات إذا لم يكن موجود
$conn->query("
CREATE TABLE IF NOT EXISTS requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    major VARCHAR(100),
    course VARCHAR(50),
    professor_id INT NOT NULL,
    purpose TEXT,
    type VARCHAR(50),
    file_name VARCHAR(255),
    grades_file VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

$message = "";

// 4. جلب الدكاترة
$professors = [];
$prof_result = $conn->query("SELECT p.professor_id, u.name FROM professors p JOIN users u ON p.user_id = u.id");
if($prof_result && $prof_result->num_rows > 0){
    while($p = $prof_result->fetch_assoc()){
        $professors[] = $p;
    }
}

// 5. معالجة النموذج
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $major = $conn->real_escape_string($_POST['major'] ?? '');
    $course = $conn->real_escape_string($_POST['course'] ?? '');
    $professor_id = intval($_POST['professor_id'] ?? 0);
    $purpose = $conn->real_escape_string($_POST['purpose'] ?? '');
    $type = $conn->real_escape_string($_POST['type'] ?? '');

    $file_name = NULL;
    $grades_file = NULL;

  $uploadDir = __DIR__ . '/uploads/';
if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);


    // CV optional
    if (!empty($_FILES['file']['name'])) {
        $safeName = time() . "_" . basename($_FILES['file']['name']);
        $target = $uploadDir . $safeName;

        if (move_uploaded_file($_FILES['file']['tmp_name'], $target)) {
            $file_name = $safeName;
        }
    }

    // Grades required
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

    // إدخال الطلب
    if (!$message) {
        $sql = "INSERT INTO requests (user_id, major, course, professor_id, purpose, type, file_name, grades_file)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ississss", $user_id, $major, $course, $professor_id, $purpose, $type, $file_name, $grades_file);

        if ($stmt->execute()) {
            $newRequestId = $stmt->insert_id;
            $stmt->close();

            $message = "✅ Request submitted successfully!";

            // ⬇️ أول خطوة تتبع للطلب
            $status = 'Created';
            $note = 'Student submitted the request';
            $trackStmt = $conn->prepare("
                INSERT INTO track_request (request_id, user_id, status, note)
                VALUES (?, ?, ?, ?)
            ");
            $trackStmt->bind_param("iiss", $newRequestId, $user_id, $status, $note);
            $trackStmt->execute();
            $trackStmt->close();

            // جلب إعدادات إشعارات الدكتور
            $s = $conn->prepare("SELECT notify_new_request FROM notification_settings WHERE user_id = ? LIMIT 1");
            $s->bind_param("i", $professor_id);
            $s->execute();
            $settings = $s->get_result()->fetch_assoc() ?? [];
            $s->close();

            // إرسال إشعار للدكتور إذا مفعل
            if (!empty($settings['notify_new_request'])) {
                $notifMessage = "You have a new recommendation request from " . ($student_data['name'] ?? 'Student');
                $notifStmt = $conn->prepare("INSERT INTO notifications (user_id, message, created_at) VALUES (?, ?, NOW())");
                $notifStmt->bind_param("is", $professor_id, $notifMessage);
                $notifStmt->execute();
                $notifStmt->close();
            }

        } else {
            $message = "❌ Error: " . $stmt->error;
        }
    }
}
?>

<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Recommendation Request</title>
<style>
/* نفس التصميم بالكامل بدون أي تغيير عندك */
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
    </div>
  </div>

  <?php if($message): ?>
  <p class="status-message"><?= htmlspecialchars($message); ?></p>
  <?php endif; ?>

  <form id="reqform" class="form-wrap" method="post" enctype="multipart/form-data">
    <div class="field">
      <label>Major*</label>
      <input type="text" name="major" required>
    </div>

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