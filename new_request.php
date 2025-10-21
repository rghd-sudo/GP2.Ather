<?php
// recommendation_form.php
session_start(); // افتح السيشن أول شيء

// تحقق من أن المستخدم مسجل دخول
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

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
    user_id INT, -- رقم المستخدم صاحب الطلب
    user_name VARCHAR(255),
    user_id VARCHAR(50),
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
    // خذ بيانات المستخدم من السيشن
    $user_id = $_SESSION['user_id'];
    $name = $_SESSION['user_name']; // أو أي قيمة خزنتها عند تسجيل الدخول

    // باقي البيانات من الفورم
    $user_name = $conn->real_escape_string($_POST['user_name'] ?? '');
    $major = $conn->real_escape_string($_POST['major'] ?? '');
    $course = $conn->real_escape_string($_POST['course'] ?? '');
    $professor = $conn->real_escape_string($_POST['professor'] ?? '');
    $purpose = $conn->real_escape_string($_POST['purpose'] ?? '');
    $type = $conn->real_escape_string($_POST['type'] ?? '');
    $file_name = NULL;

    // رفع الملف
    if (!empty($_FILES['file']['name'])) {
        $uploadDir = __DIR__ . '/uploads/'; // DIR أصح من DIR
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

        $safeName = time() . "_" . basename($_FILES['file']['name']);
        $target = $uploadDir . $safeName;

        if (move_uploaded_file($_FILES['file']['tmp_name'], $target)) {
            $file_name = $safeName;
        }
    }

    // إدخال البيانات بالطلب مع user_id
    $sql = "INSERT INTO requests (user_id, user_name, major, course, professor, purpose, type, file_name)
            VALUES ('$user_id', '$user_name', '$major', '$course', '$professor', '$purpose', '$type', " .
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
    /* المتغيرات (Colors & Fonts) */
    :root {
      --bg-color: #fbf7f2;
      --header-bg: #cfe7e8;
      --input-bg: #e6e0e0; /* لون شبيه بالوردي الفاتح في التصميم */
      --main-text: #2b2b2b;
      --sub-text: #473d57;
      --accent-color: #f07963; /* لون زر الإرسال */
      --shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
      --border-radius: 12px;
      font-family: 'Arial', 'Tahoma', sans-serif;
    }
    
    /* الأساسيات */
    body {
      margin: 0;
      padding: 0;
      background-color: var(--bg-color);
      color: var(--main-text);
      display: flex;
      justify-content: center;
      align-items: flex-start;
      min-height: 100vh;
      direction: rtl; /* الاتجاه من اليمين لليسار */
    }

    .container {
      width: 100%;
      max-width: 900px; /* جعل النموذج أعرض قليلاً */
      margin: 40px auto;
      padding: 20px;
    }
    
    /* قسم الرأس - Student Information */
    .header-card {
      background-color: var(--header-bg);
      border-radius: var(--border-radius);
      padding: 30px;
      margin-bottom: 30px;
      box-shadow: var(--shadow);
      display: grid;
      grid-template-columns: 100px 1fr 1fr; /* أيقونة، عنوان، معلومات طالب */
      gap: 20px;
      align-items: center;
    }

    .icon-container {
        width: 60px;
        height: 60px;
        background-color: #3b9196; /* لون الأيقونة في التصميم */
        border-radius: 50%;
        display: flex;
        justify-content: center;
        align-items: center;
        grid-column: 1 / 2;
    }

    .icon-container::before {
        content: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="white" width="30px" height="30px"><path d="M0 0h24v24H0z" fill="none"/><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>');
        /* استخدمت SVG مضمنة لأيقونة شخص بيضاء */
    }

    .request-title {
        font-size: 24px;
        font-weight: bold;
        color: var(--main-text);
        grid-column: 2 / 3;
        align-self: center;
    }

    .student-info-section {
      grid-column: 3 / 4;
      border-right: 2px solid rgba(0, 0, 0, 0.1);
      padding-right: 20px;
    }

    .student-info-title {
        font-size: 20px;
        font-weight: 700;
        color: var(--sub-text);
        margin-bottom: 10px;
        display: block;
    }

    .student-info-section input {
      display: block;
      width: 100%;
      border: none;
      border-bottom: 1px solid rgba(0, 0, 0, 0.3);
      background: transparent;
      padding: 8px 0;
      margin-bottom: 10px;
      font-size: 16px;
      color: var(--main-text);
      outline: none;
    }
    
    /* جسم النموذج */
    .form-wrap {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 40px;
    }
    
    .field {
      margin-bottom: 30px;
    }
    
    .label {
      font-weight: 700;
      margin-bottom: 8px;
      display: block;
      font-size: 16px;
      color: var(--sub-text);
    }
    
    /* حقول الإدخال النصية والمربعات */
    input[type="text"], 
    textarea,
    .course-dropdown-display,
    .upload-label {
      width: 100%;
      padding: 16px;
      border-radius: 4px;
      border: none;
      background-color: var(--input-bg);
      box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
      font-size: 15px;
      color: var(--main-text);
      box-sizing: border-box; /* لضمان أن العرض يشمل البادينج */
    }

    textarea {
        min-height: 120px;
        resize: vertical;
    }

    /* قائمة اختيار اسم المقرر (Dropdown) */
    .course-select-wrap {
      position: relative;
    }

    .course-select-wrap select {
        /* إخفاء سلكت HTML الأصلي */
        display: none; 
    }

    .course-dropdown-display {
        display: flex;
        justify-content: space-between;
        align-items: center;
        cursor: pointer;
        padding-right: 15px; /* مسافة لرمز السهم */
    }

    .course-dropdown-display::after {
        content: '⌄'; /* رمز السهم لأسفل */
        font-size: 20px;
        line-height: 1;
        transition: transform 0.3s;
    }
    
    .course-select-wrap.open .course-dropdown-display::after {
        transform: rotate(180deg);
    }

    .course-options {
        position: absolute;
        top: 100%;
        left: 0;
        right: 0;
        z-index: 10;
        background-color: white;
        border-radius: 4px;
        box-shadow: var(--shadow);
        margin-top: 5px;
        max-height: 0;
        overflow: hidden;
        transition: max-height 0.3s ease-out, padding 0.3s ease-out;
        padding: 0;
    }

    .course-select-wrap.open .course-options {
        max-height: 200px; 
        padding: 10px 0;
    }

    .course-option {
        padding: 10px 16px;
        cursor: pointer;
        color: var(--main-text);
        transition: background-color 0.2s;
        font-size: 15px;
    }

    .course-option:hover {
        background-color: var(--header-bg);
    }
    
    /* حقول الرفع */
    .upload-label {
        display: flex;
        align-items: center;
        cursor: pointer;
        gap: 10px;
    }

    .upload-label input[type="file"] {
        /* إخفاء حقل الملف الأصلي */
        display: none;
    }
    
    .upload-label span {
        font-size: 20px;
        line-height: 1;
    }

    /* حقول الراديو */
    .radios {
      display: flex;
      gap: 30px;
    }

    .radios label {
        cursor: pointer;
        display: flex;
        align-items: center;
        font-size: 16px;
    }

    .radios input[type="radio"] {
        /* إخفاء الراديو الأصلي */
        display: none;
    }

    /* تصميم الراديو المخصص */
    .radios label::before {
        content: '';
        width: 18px;
        height: 18px;
        border-radius: 50%;
        border: 2px solid var(--sub-text);
        margin-left: 8px;
        display: inline-block;
        transition: all 0.2s;
    }

    .radios input[type="radio"]:checked + span::before {
        background-color: var(--accent-color);
        border-color: var(--accent-color);
        box-shadow: inset 0 0 0 4px white;
    }
    
    /* زر الإرسال */
    .submit-wrap {
      display: flex;
      justify-content: flex-start; /* عكس الاتجاه ليتناسب مع RTL */
      margin-top: 40px;
    }
    
    .btn {
      background: var(--accent-color);
      color: white;
      padding: 18px 50px;
      border-radius: 8px; /* شكل المربع في التصميم */
      border: none;
      font-size: 24px;
      font-weight: 700;
      cursor: pointer;
      box-shadow: var(--shadow);
      transition: background-color 0.3s;
    }

    .btn:hover {
        background-color: #d15a45; /* لون أغمق عند التفاعل */
    }

    /* رسالة الحالة */
    .status-message {
        margin: 20px 0;
        padding: 15px;
        background-color: #e8f5e9; /* أخضر فاتح للنجاح */
        border: 1px solid #c8e6c9;
        color: #388e3c;
        border-radius: 6px;
        font-weight: bold;
        text-align: center;
    }

    /* التجاوب مع الشاشات الصغيرة */
    @media (max-width: 768px) {
      .header-card {
        grid-template-columns: 1fr;
        text-align: center;
      }
      .icon-container, .request-title, .student-info-section {
        grid-column: 1 / -1;
        justify-self: center;
      }
      .student-info-section {
        border-right: none;
        padding-right: 0;
      }
      .form-wrap {
        grid-template-columns: 1fr;
      }
    }
  </style>
</head>
<body>
 <div class="container">
    
    <div class="header-card">
        <div class="icon-container"></div>
        <div class="request-title">Recommendation Request</div>
        
        <div class="student-info-section">
            <span class="student-info-title">Student Information</span>
            <input type="text" placeholder="Name" name="name" form="reqform" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
            <input type="text" placeholder="ID" name="id" form="reqform" value="<?= htmlspecialchars($_POST['id'] ?? '') ?>">
            <input type="text" placeholder="Major" name="major" form="reqform" value="<?= htmlspecialchars($_POST['major'] ?? '') ?>">
        </div>
    </div>

    <?php if($message): ?>
      <p class="status-message"><?= htmlspecialchars($message); ?></p>
    <?php endif; ?>

    <form id="reqform" class="form-wrap" method="post" enctype="multipart/form-data">
      
      <div class="col">
        <div class="field">
          <label class="label">Course name</label>
          <div class="course-select-wrap">
            
            <div class="course-dropdown-display" data-value="<?= htmlspecialchars($_POST['course'] ?? '') ?>">
                <?= htmlspecialchars($_POST['course'] ?? '-- اختر --') ?>
            </div>
            
            <input type="hidden" name="course" value="<?= htmlspecialchars($_POST['course'] ?? '') ?>">

            <div class="course-options">
              <div class="course-option" data-value="DSS">DSS</div>
              <div class="course-option" data-value="DBMS">DBMS</div>
              <div class="course-option" data-value="OS">OS</div>
              <div class="course-option" data-value="CN">CN</div>
              </div>
          </div>
        </div>
        
        <div class="field">
          <label class="label">Professor name*</label>
          <input type="text" name="professor" required value="<?= htmlspecialchars($_POST['professor'] ?? '') ?>">
        </div>
        
        <div class="field">
          <label class="label">Type of Recommendation</label>
          <div class="radios">
            <label>
              <input type="radio" name="type" value="Academic" <?= (!isset($_POST['type']) || $_POST['type'] == 'Academic') ? 'checked' : '' ?>>
              <span>Academic</span>
            </label>
            <label>
              <input type="radio" name="type" value="Professional" <?= (isset($_POST['type']) && $_POST['type'] == 'Professional') ? 'checked' : '' ?>>
              <span>Professional</span>
            </label>
          </div>
        </div>
      </div>

      <div class="col">
        <div class="field">
          <label class="label">Purpose of the Recommendation*</label>
          <textarea name="purpose" required><?= htmlspecialchars($_POST['purpose'] ?? '') ?></textarea>
        </div>
        
        <div class="field">
          <label class="label">Upload CV (optional)</label>
          <label class="upload-label" for="cv-file-input">
            <span>⬆</span>
            <span id="cv-file-name">Upload File (optional)</span>
            <input type="file" name="file" id="cv-file-input" accept=".pdf,.doc,.docx">
          </label>
        </div>

        <div class="field">
          <label class="label">Upload Grades (optional)</label>
          <label class="upload-label" for="grades-file-input">
            <span>⬆</span>
            <span id="grades-file-name">Upload Grades (optional)</span>
            <input type="file" name="grades" id="grades-file-input" accept=".pdf,.png,.jpg,.jpeg">
          </label>
        </div>

        <div class="submit-wrap">
          <button type="submit" class="btn">Submit</button>
        </div>
      </div>
    </form>
  </div>
  
  <script>
    // جافاسكريبت لجعل الأشياء تفاعلية
    document.addEventListener('DOMContentLoaded', () => {
        
        /* 1. التفاعل مع قائمة المقررات المخصصة (Dropdown) */
        const dropdownWrap = document.querySelector('.course-select-wrap');
        const display = dropdownWrap.querySelector('.course-dropdown-display');
        const options = dropdownWrap.querySelector('.course-options');
        const hiddenInput = dropdownWrap.querySelector('input[name="course"]');
        const allOptions = options.querySelectorAll('.course-option');

        // فتح/إغلاق القائمة
        display.addEventListener('click', (e) => {
            e.stopPropagation(); // منع إغلاقها فوراً
            dropdownWrap.classList.toggle('open');
        });

        // اختيار قيمة وتحديثها
        allOptions.forEach(option => {
            option.addEventListener('click', () => {
                const value = option.getAttribute('data-value');
                // تحديث الحقل المخفي (الذي يُرسل)
                hiddenInput.value = value;
                // تحديث العرض (ما يراه المستخدم)
                display.textContent = value;
                display.setAttribute('data-value', value);
                // إغلاق القائمة
                dropdownWrap.classList.remove('open');
            });
        });

        // إغلاق القائمة عند النقر في أي مكان آخر
        document.addEventListener('click', (e) => {
            if (!dropdownWrap.contains(e.target)) {
                dropdownWrap.classList.remove('open');
            }
        });

        /* 2. التفاعل مع حقول رفع الملفات (لإظهار اسم الملف) */
        
        // CV File
        const cvFileInput = document.getElementById('cv-file-input');
        const cvFileNameDisplay = document.getElementById('cv-file-name');
        cvFileInput.addEventListener('change', () => {
            if (cvFileInput.files.length > 0) {
                cvFileNameDisplay.textContent = cvFileInput.files[0].name;
            } else {
                cvFileNameDisplay.textContent = 'Upload File (optional)';
            }
        });

        // Grades File
        const gradesFileInput = document.getElementById('grades-file-input');
        const gradesFileNameDisplay = document.getElementById('grades-file-name');
        gradesFileInput.addEventListener('change', () => {
            if (gradesFileInput.files.length > 0) {
                gradesFileNameDisplay.textContent = gradesFileInput.files[0].name;
            } else {
                gradesFileNameDisplay.textContent = 'Upload Grades (optional)';
            }
        });
    });
  </script>
</body>
</html>