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
        $uploadDir = DIR . '/uploads/';
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
    body{margin:0;background:var(--bg);color:var(--text);}
    .container{max-width:900px;margin:30px auto;padding:30px;}
    .header{display:flex;justify-content:space-between;align-items:center;background:var(--header);padding:20px;border-radius:20px;box-shadow:var(--shadow);}
    .brand{display:flex;align-items:center;gap:14px}
    .avatar{width:70px;height:70px;border-radius:50%;overflow:hidden}
    .avatar img{width:100%;height:100%;object-fit:cover}
    .title{font-size:24px;font-weight:700}
    .student-info{font-family:monospace;color:#473d57}
    .student-info input{display:block;width:240px;border:none;border-bottom:2px solid rgba(0,0,0,0.08);background:transparent;padding:6px 4px;margin-bottom:6px}
    .form-wrap{display:flex;gap:30px;margin-top:30px}
    .col{flex:1}
    .field{margin-bottom:20px}
    .label{font-weight:700;margin-bottom:8px}
    .select-style{background:var(--input);padding:10px;border-radius:6px;box-shadow:var(--shadow)}
    select{width:100%;max-width:280px;padding:8px;border:none;background:transparent;font-size:15px}
    input[type="text"], textarea, input[type="file"]{
      width:100%;
      max-width:280px;
      padding:10px;
      border-radius:6px;
      border:none;
      background:var(--input);
      box-shadow:var(--shadow);
      font-size:14px
    }
    textarea{min-height:90px}
    .upload{display:flex;align-items:center;gap:10px;background:var(--input);padding:10px;border-radius:6px;box-shadow:var(--shadow)}
    .radios{display:flex;gap:16px;align-items:center}
    .submit-wrap{display:flex;justify-content:flex-end}
    .btn{background:var(--accent);color:white;padding:16px 36px;border-radius:40px;border:none;font-size:18px;font-weight:700;cursor:pointer}
  </style>
</head>
<body>
  
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
          <div class="label">Upload File (optional)</div>
          <label class="upload">
            <span>⬆</span>
            <input type="file" name="file">
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