<?php
// recommendation_form.php

// الاتصال بقاعدة البيانات
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "agdb";

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    die("فشل الاتصال: " . $conn->connect_error);
}

// إنشاء جدول إذا لم يكن موجود
$conn->query("CREATE TABLE IF NOT EXISTS requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_name VARCHAR(255),
    student_id VARCHAR(50),
    major VARCHAR(100),
    course VARCHAR(50),
    professor VARCHAR(255),
    purpose TEXT,
    type VARCHAR(50),
    file_name VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

$message = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $conn->real_escape_string($_POST['name'] ?? '');
    $student_id = $conn->real_escape_string($_POST['id'] ?? '');
    $major = $conn->real_escape_string($_POST['major'] ?? '');
    $course = $conn->real_escape_string($_POST['course'] ?? '');
    $professor = $conn->real_escape_string($_POST['professor'] ?? '');
    $purpose = $conn->real_escape_string($_POST['purpose'] ?? '');
    $type = $conn->real_escape_string($_POST['type'] ?? '');
    $file_name = NULL;

    if (!empty($_FILES['file']['name'])) {
        $uploadDir = __DIR__ . '/uploads/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

        $safeName = time() . "_" . basename($_FILES['file']['name']);
        $target = $uploadDir . $safeName;

        if (move_uploaded_file($_FILES['file']['tmp_name'], $target)) {
            $file_name = $safeName;
        }
    }

    $sql = "INSERT INTO requests (student_name, student_id, major, course, professor, purpose, type, file_name)
            VALUES ('$name', '$student_id', '$major', '$course', '$professor', '$purpose', '$type', " .
            ($file_name ? "'$file_name'" : "NULL") . ")";
    if ($conn->query($sql)) {
        $message = "تم حفظ الطلب بنجاح ✅";
    } else {
        $message = "خطأ: " . $conn->error;
    }
}
?>

<!doctype html>
<html lang="ar">
<head>
  <meta charset="utf-8">
  <title>Recommendation Request</title>
  <style>
    :root{
      --bg:#fbf7f2;
      --header:#cfe7e8;
      --input:#ecdcdc;
      --card:#efeef0;
      --accent:#f07963;
      --text:#2b2b2b;
      --shadow:0 6px 10px rgba(0,0,0,0.08);
      font-family: Arial, sans-serif;
    }
    body{margin:0;background:var(--bg);color:var(--text);display:flex}
    .sidebar{width:80px;background:var(--header);min-height:100vh;display:flex;flex-direction:column;align-items:center;padding:20px 0;gap:20px}
    .sidebar a{width:50px;height:50px;background:var(--accent);color:white;display:flex;align-items:center;justify-content:center;border-radius:12px;text-decoration:none;font-size:20px;box-shadow:var(--shadow)}
    .container{flex:1;max-width:1150px;margin:30px;padding:30px}
    .header{display:flex;justify-content:space-between;align-items:center;background:var(--header);padding:28px;border-radius:28px;box-shadow:var(--shadow);}
    .brand{display:flex;align-items:center;gap:18px}
    .avatar{width:84px;height:84px;border-radius:50%;overflow:hidden}
    .avatar img{width:100%;height:100%;object-fit:cover}
    .title{font-size:28px;font-weight:700}
    .student-info{font-family:monospace;color:#473d57}
    .student-info input{display:block;width:380px;border:none;border-bottom:2px solid rgba(0,0,0,0.08);background:transparent;padding:8px 6px}
    .form-wrap{display:flex;gap:40px;margin-top:40px}
    .col{flex:1}
    .field{margin-bottom:30px}
    .label{font-weight:700;margin-bottom:10px}
    .select-style{background:var(--input);padding:18px;border-radius:6px;box-shadow:var(--shadow)}
    select{width:100%;padding:10px;border:none;background:transparent;font-size:16px}
    input[type="text"], textarea{width:100%;padding:16px;border-radius:6px;border:none;background:var(--input);box-shadow:var(--shadow);font-size:15px}
    textarea{min-height:120px}
    .upload{display:flex;align-items:center;gap:12px;background:var(--input);padding:14px;border-radius:6px;box-shadow:var(--shadow)}
    .radios{display:flex;gap:22px;align-items:center}
    .submit-wrap{display:flex;justify-content:flex-end}
    .btn{background:var(--accent);color:white;padding:22px 48px;border-radius:50px;border:none;font-size:24px;font-weight:700;cursor:pointer}
  </style>
</head>
<body>
  <!-- القائمة الجانبية -->
  <div class="sidebar">
    <a href="recommendation_form.php">🏠</a>
    <a href="my_requests.php">📑</a>
    <a href="settings.php">⚙</a>
  </div>

  <div class="container">
    <div class="header">
      <div class="brand">
        <div class="avatar">
          <img src="avatar.tif" alt="avatar">
        </div>
        <div class="title">Recommendation Request</div>
      </div>
      <div class="student-info">
        <label>Student information</label>
        <input type="text" placeholder="Name" name="name" form="reqform">
        <input type="text" placeholder="ID" name="id" form="reqform">
        <input type="text" placeholder="Major" name="major" form="reqform">
      </div>
    </div>

    <?php if($message): ?>
      <p style="margin:20px 0;padding:12px;background:#fff8e6;border-radius:6px;box-shadow:var(--shadow)"><?php echo $message; ?></p>
    <?php endif; ?>

    <form id="reqform" class="form-wrap" method="post" enctype="multipart/form-data">
      <div class="col">
        <div class="field">
          <div class="label">Course name</div>
          <div class="select-style">
            <select name="course">
              <option value="">-- اختر --</option>
              <option>DSS</option>
              <option>DBMS</option>
              <option>OS</option>
              <option>CN</option>
            </select>
          </div>
        </div>
        
        <div class="field">
          <div class="label">Professor name*</div>
          <input type="text" name="professor" required>
        </div>
        <div class="field">
          <div class="label">Type of Recommendation</div>
          <div class="radios">
            <label><input type="radio" name="type" value="Academic" checked> Academic</label>
            <label><input type="radio" name="type" value="Professional"> Professional</label>
          </div>
        </div>
      </div>

      <div class="col">
        <div class="field">
          <div class="label">Purpose of the Recommendation*</div>
          <textarea name="purpose" required></textarea>
        </div>
        
        <div class="field">
          <div class="label">Upload CV (optional)</div>
          <label class="upload">
            <span>⬆</span>
            <input type="file" name="file">
          </label>
        </div>

        <div class="field">
          <div class="label">Upload Grades (optional)</div>
          <label class="upload">
            <span>⬆</span>
            <input type="file" name="grades">
          </label>
        </div>

        <div class="submit-wrap">
          <button type="submit" class="btn">Submit</button>
        </div>
      </div>
    </form>
  </div>
</body>
</html>