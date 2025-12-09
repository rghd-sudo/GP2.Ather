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

    $uploadDir = __DIR__. '/uploads/';
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

        // ------------------------------------------------------
        // ⭐⭐ أول خطوة تتبع — Created ⭐⭐
        // ------------------------------------------------------
        $status = 'Created';
        $note = 'Student submitted the request';

        $trackStmt = $conn->prepare("
            INSERT INTO track_request (request_id, user_id, status, note)
            VALUES (?, ?, ?, ?)
        ");

        $trackStmt->bind_param("iiss", $newRequestId, $user_id, $status, $note);
        $trackStmt->execute();
        $trackStmt->close();

        // ------------------------------------------------------
        // ⭐⭐ إشعار للدكتور ⭐⭐
        // ------------------------------------------------------
        $s = $conn->prepare("SELECT notify_new_request FROM notification_settings WHERE user_id = ? LIMIT 1");
        $s->bind_param("i", $professor_id);
        $s->execute();
        $settings = $s->get_result()->fetch_assoc() ?? [];
        $s->close();

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
?>

<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Recommendation Request</title>
<style>
body {
  margin: 0;
  font-family: "Poppins", sans-serif;
  background: #fdfaf6;
  display: flex;
  justify-content: center;
}

.container {
  max-width: 720px;
  width: 95%;
  margin: 40px 0;
  padding: 25px;
  background: #ffffffff;
  border-radius: 15px;
  box-shadow: 0 5px 20px rgba(0,0,0,0.1);
}

/* Header Card */
.header-card {
  display: grid;
  grid-template-columns: 60px 1fr 1fr;
  gap: 15px;
  align-items: center;
  background: #c8e4eb;
  padding: 20px 25px;
  border-radius: 15px;
  box-shadow: 0 4px 12px rgba(0,0,0,0.08);
}

.icon-container {
  width: 60px;
  height: 60px;
  background-color: #3b9196;
  border-radius: 50%;
  display: flex;
  justify-content: center;
  align-items: center;
}

.icon-container::before {
  content: "🎓"; /* رمز بديل للتوصية */
  font-size: 28px;
}

.request-title {
  font-size: 22px;
  font-weight: bold;
  color: #003366;
}

.student-info-section {
  grid-column: 3/4;
  border-right: 2px solid rgba(0,0,0,0.1);
  padding-right: 15px;
}

.student-info-title {
  font-size: 16px;
  font-weight: 700;
  color: #555;
  margin-bottom: 10px;
  display: block;
}

.student-info-section input {
  width: 100%;
  padding: 8px 10px;
  margin-bottom: 8px;
  border-radius: 8px;
  border: 1px solid #ccc;
  outline: none;
}

.student-info-section input:focus {
  border-color:  #f07963;
  box-shadow: 0 0 4px rgba(240,121,99,0.4);
}

/* Form Fields */
.form-wrap {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 15px;
  margin-top: 20px;
}

.field {
  display: flex;
  flex-direction: column;
}

.label {
  font-weight: 500;
  margin-bottom: 8px;
  color: #333;
}

input[type="text"], select, textarea, input[type="file"] {
  width:95%;
  padding: 10px;
  border-radius: 8px;
  border: 1px solid #ccc;
  font-size: 14px;
}

input[type="text"]:focus, select:focus, textarea:focus, input[type="file"]:focus {
  border-color: #f07963;
  box-shadow: 0 0 4px rgba(240,121,99,0.4);
}

textarea { min-height: 100px; resize: vertical; }

.radios {
  display: flex;
  gap: 15px;
  margin-top: 5px;
}
.radios input[type="radio"] { display: none; }
.radios label {
  display: flex; align-items: center; cursor: pointer; font-size: 15px;
}
.radios label span::before {
  content: '';
  width: 16px; height: 16px;
  border-radius: 50%;
  border: 2px solid #333;
  margin-right: 6px;
  display: inline-block;
  transition: all 0.2s;
}
.radios input[type="radio"]:checked + span::before {
  background-color: #f07963;
  border-color: #f07963B;
  box-shadow: inset 0 0 0 4px white;
}
.field.full-width {
  grid-column: 1 / -1;
}
/* Submit Button */
.submit-wrap {
  display: flex;
  justify-content: center;
  margin-top: 20px;
}
.btn {
  background: #f07963;
  color: #fff;
  padding: 12px 30px;
  border-radius: 8px;
  border: none;
  font-size: 16px;
  font-weight: bold;
  cursor: pointer;
  transition: background 0.3s;
}
.btn:hover { background: #f07963; }

/* Status Message */
.status-message {
  background: #d1f7d6;
  color: #2d7a32;
  border: 1px solid #9de5a2;
  padding: 10px 15px;
  border-radius: 8px;
  text-align: center;
  font-weight: bold;
  margin-bottom: 15px;
}

.back_btn {
    display: inline-block;
    margin-bottom: 20px;
    font-size: 24px;
    color: #03060a;
    text-decoration: none;
    font-weight: bold;

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
      <input type="text" value="<?= htmlspecialchars($student_info['name']); ?>" readonly>
      <input type="text" value="<?= htmlspecialchars($student_info['id_number']); ?>" readonly>
      <input type="text" value="<?= htmlspecialchars($student_info['department']); ?>" readonly>
    </div>
  </div>

  <?php if($message): ?>
  <p class="status-message"><?= htmlspecialchars($message); ?></p>
  <?php endif; ?>

  <form class="form-wrap" method="post" enctype="multipart/form-data">
    <div class="field">
      <label class="label">Course Name*</label>
      <input type="text" name="course" required>
    </div>
    <div class="field">
      <label class="label">Professor*</label>
      <select name="professor_id" required>
        <option value="">-- Select Professor --</option>
        <?php foreach($professors as $p): ?>
        <option value="<?= $p['professor_id'] ?>"><?= $p['name'] ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="field full-width">
      <label class="label">Purpose of Recommendation*</label>
      <textarea name="purpose" required></textarea>
    </div>
    <div class="field">
      <label class="label">Recommendation Type</label>
      <div class="radios">
        <label><input type="radio" name="type" value="Academic" checked><span>Academic</span></label>
        <label><input type="radio" name="type" value="Professional"><span>Professional</span></label>
      </div>
    </div>
    <div class="field">
      <label class="label">Upload CV (optional)</label>
      <input type="file" name="file" accept=".pdf,.doc,.docx">
    </div>
    <div class="field full-width">
      <label class="label">Upload Grades*</label>
      <input type="file" name="grades" accept=".pdf,.png,.jpg,.jpeg" required>
    </div>

    <div class="submit-wrap full-width">
      <button type="submit" class="btn">Submit Request</button>
    </div>
  </form>
</div>

<script>
  document.getElementById('cvFile').addEventListener('change', function() {
    document.getElementById('cvFileName').textContent = this.files[0]?.name || '';
  });
  document.getElementById('gradesFile').addEventListener('change', function() {
    document.getElementById('gradesFileName').textContent = this.files[0]?.name || '';
  });
</script>
</div>
</body>
