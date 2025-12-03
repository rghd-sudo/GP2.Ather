<?php
session_start();
include 'index.php'; // ملف الاتصال بقاعدة البيانات

// 1. التحقق من الجلسة
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$message = "";
$is_editing = false;
$current_request_id = null;
$request_data = [];
$student_info = ['name' => '', 'id_number' => ''];

// 2. جلب بيانات الطالب تلقائياً
$student_result = $conn->query("SELECT name, National_id FROM users WHERE id = $user_id");

if ($student_result && $student_result->num_rows > 0) {
    $student_data = $student_result->fetch_assoc();
    $student_info['name'] = $student_data['name'];
    $student_info['id_number'] = $student_data['National_id'] ?? '';
}

// 3. جلب قائمة الدكاترة 
$professors = [];
$prof_result = $conn->query("SELECT p.professor_id, u.name, u.email, u.department FROM professors p JOIN users u ON p.user_id = u.id");
if($prof_result && $prof_result->num_rows > 0){
    while($p = $prof_result->fetch_assoc()){
        $professors[] = $p;
    }
}

// ==========================================================
// 4. منطق التعديل: جلب بيانات الطلب الحالي لملء النموذج (GET)
// ==========================================================
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $current_request_id = intval($_GET['id']);
    
    // 🚀 تم تعديل الاستعلام هنا: إزالة شرط الحالة status='pending' لضمان جلب البيانات
    $sql_fetch = "SELECT * FROM requests WHERE id = '$current_request_id' AND user_id = '$user_id'"; 
    $result_fetch = $conn->query($sql_fetch);

    if ($result_fetch && $result_fetch->num_rows === 1) {
        $request_data = $result_fetch->fetch_assoc();
        $is_editing = true;
    } else {
        // يمكنك إرجاع شرط الحالة هنا إذا أردت منع التعديل بعد القبول/الرفض
        $message = "❌ لم يتم العثور على الطلب أو لا تملك صلاحية تعديله.";
        $current_request_id = null;
    }
} else if (isset($_GET['id'])) {
     $message = "❌ رقم الطلب غير صحيح.";
}


// ==========================================================
// 5. معالجة النموذج (POST) للتحديث
// ==========================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $is_editing) {
    
    // البيانات المدخلة من النموذج
    $major = $conn->real_escape_string($_POST['major'] ?? '');
    $course = $conn->real_escape_string($_POST['course'] ?? '');
    $professor_id = intval($_POST['professor_id'] ?? 0);
    $purpose = $conn->real_escape_string($_POST['purpose'] ?? '');
    $type = $conn->real_escape_string($_POST['type'] ?? '');
    
    // الحفاظ على اسم الملف الحالي في حالة عدم رفع ملف جديد
    $file_name = $request_data['file_name'];
    $grades_file = $request_data['grades_file'];
    
    // تحديد مسار الرفع (يفترض أنه نفس المسار في new_request.php)
    $uploadDir = __DIR__.'/uploads/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

    // رفع الملف الاختياري (CV) - تحديث الملف فقط إذا تم رفع ملف جديد
    if (!empty($_FILES['file']['name'])) {
        // (يجب أن تفكر في حذف الملف القديم إذا تم رفع ملف جديد)
        $safeName = time() . "_" . basename($_FILES['file']['name']);
        $target = $uploadDir . $safeName;
        if (move_uploaded_file($_FILES['file']['tmp_name'], $target)) {
            $file_name = $safeName;
        }
    }

    // رفع سجل الدرجات (إجباري) - تحديث الملف فقط إذا تم رفع ملف جديد
    if (!empty($_FILES['grades']['name'])) {
        // (يجب أن تفكر في حذف الملف القديم إذا تم رفع ملف جديد)
        $safeGradesName = time() . "_grades_" . basename($_FILES['grades']['name']);
        $targetGrades = $uploadDir . $safeGradesName;
        if (move_uploaded_file($_FILES['grades']['tmp_name'], $targetGrades)) {
            $grades_file = $safeGradesName;
        } else {
            $message = "❌ فشل رفع سجل الدرجات الجديد";
        }
    }


    // إدخال البيانات إذا لم يكن هناك رسالة خطأ updated_at = NOW()
    if (!$message) {
        $sql = "UPDATE requests SET 
                major = '$major', 
                course = '$course', 
                professor_id = '$professor_id', 
                purpose = '$purpose',
                type = '$type', 
                file_name = " . ($file_name ? "'$file_name'" : "NULL") . ", 
                grades_file = '$grades_file'
                WHERE id = '$current_request_id' AND user_id = '$user_id'";
    
        if ($conn->query($sql)) {
            $message = "✅ تم تحديث الطلب بنجاح";
            // إعادة تعبئة الـ request_data بالبيانات المحدثة
            $request_data['major'] = $major;
            $request_data['course'] = $course;
            $request_data['professor_id'] = $professor_id;
            $request_data['purpose'] = $purpose;
            $request_data['type'] = $type;
            $request_data['file_name'] = $file_name;
            $request_data['grades_file'] = $grades_file;
            
        } else {
            $message = "❌ خطأ أثناء التحديث: " . $conn->error;
        }
    }
}

// تحديد البيانات التي سيتم استخدامها لملء النموذج: إما البيانات المجلوبة أو بيانات POST في حالة فشل التحديث
$form_data = ($_SERVER['REQUEST_METHOD'] === 'POST' && $is_editing) ? $_POST : $request_data;

// استخدام البيانات الموجودة في $request_data في حال لم يتم إرسال النموذج بعد
if (!$form_data && $is_editing) {
    $form_data = $request_data;
}

?>

<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Edit Recommendation Request</title>
<style>
/* تم نسخ التنسيقات من new_request.php لضمان التوافق */
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
  direction: ltr; 
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

.input[type="text"], textarea, select {
  width:100%; padding:12px; border-radius:5px;
  border:1px solid #ccc; background-color: var(--input-bg);
  font-size:15px; color:var(--main-text); box-sizing:border-box;
}

.input[type="text"]:focus, textarea:focus, select:focus {
  border-color: var(--accent-color);
  box-shadow:0 0 4px rgba(240,121,99,0.4);
}

.textarea { min-height:100px; resize:vertical; }
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
.success {
    background-color:#d4edda;
    border-color:#c3e6cb;
    color:#155724;
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
    <div class="request-title">Edit Recommendation Request #<?= htmlspecialchars($current_request_id ?? '—'); ?></div> 
    <div class="student-info-section">
      <span class="student-info-title">Student Information</span>
      <input type="text" placeholder="Name" name="name_display" form="reqform" value="<?= htmlspecialchars($student_info['name']); ?>" readonly>
      <input type="text" placeholder="ID" name="id_display" form="reqform" value="<?= htmlspecialchars($student_info['id_number']); ?>" readonly>
      </div>
  </div>

  <?php if($message): ?>
  <p class="status-message <?= (strpos($message, '✅') !== false) ? 'success' : '' ?>"><?= htmlspecialchars($message); ?></p>
  <?php endif; ?>

  <?php if ($is_editing): // عرض النموذج فقط إذا كان الطلب جاهزاً للتعديل ?>
  <form id="reqform" class="form-wrap" method="post" enctype="multipart/form-data" onsubmit="return validateForm()">

    <div class="field">
      <label>Major*</label> 
      <input type="text" name="major" value="<?= htmlspecialchars($form_data['major'] ?? '') ?>" required>
    </div>

    <div class="field">
      <label>Course Name*</label>
      <input type="text" name="course" value="<?= htmlspecialchars($form_data['course'] ?? '') ?>" required>
    </div>

    <div class="field">
      <label>Professor*</label>
      <select name="professor_id" required>
        <option value="">-- Select Professor --</option>
        <?php foreach($professors as $p): ?>
          <option value="<?= $p['professor_id'] ?>" <?= (isset($form_data['professor_id']) && $form_data['professor_id'] == $p['professor_id'])?'selected':'' ?>><?= $p['name'] ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="field">
      <label>Purpose of Recommendation*</label>
      <textarea name="purpose" required><?= htmlspecialchars($form_data['purpose'] ?? '') ?></textarea>
    </div>
    
    <div class="field full-width">
      <label>Recommendation Type</label>
      <div class="radios">
        <label><input type="radio" name="type" value="Academic" <?= (!isset($form_data['type']) || $form_data['type']=='Academic')?'checked':'' ?>><span>Academic</span></label>
        <label><input type="radio" name="type" value="Professional" <?= (isset($form_data['type']) && $form_data['type']=='Professional')?'checked':'' ?>><span>Professional</span></label>
      </div>
    </div>

    <div class="field full-width">
      <label>Upload CV (optional) 
        <?php if($form_data['file_name']): ?>(<a href="uploads/<?= htmlspecialchars($form_data['file_name']); ?>" target="_blank">Current File</a>)<?php endif; ?>


</label>
      <input type="file" name="file" accept=".pdf,.doc,.docx">
      <h6>Leave blank to keep the existing file.</h6>
    </div>

    <div class="field full-width">
      <label>Upload Grades* <?php if($form_data['grades_file']): ?>(<a href="uploads/<?= htmlspecialchars($form_data['grades_file']); ?>" target="_blank">Current Grades File</a>)<?php endif; ?>
      </label>
      <input type="file" name="grades" id="grades-file" accept=".pdf,.png,.jpg,.jpeg">
      <h6>Upload new file only if you need to update grades. Current file is required.</h6>
    </div>

    <div class="submit-wrap full-width">
      <button type="submit" class="btn">Save Changes</button>
    </div>
  </form>
  <?php endif; ?>
</div>

<script>
// تم تعديل وظيفة التحقق للسماح بحفظ التعديلات بدون رفع ملف الدرجات مرة أخرى.
function validateForm() {
    <?php if(!($form_data['grades_file'] ?? null)): // تم التأكد من وجود ملف الدرجات في بيانات الطلب الحالية ?>
    const grades = document.getElementById('grades-file');
    if(!grades.value) {
        alert('Uploading Grades is required!');
        return false;
    }
    <?php endif; ?>
    return true;
}
</script>
</body>
</html>