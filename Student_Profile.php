<?php
session_start();
include 'index.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// جلب بيانات المستخدم والخريج
$query = "
    SELECT 
        users.id AS uid,
        users.name, 
        users.email, 
        users.department, 
        users.university,
        graduates.gpa, 
        graduates.cv_path, 
        graduates.graduation_year
    FROM users
    LEFT JOIN graduates ON users.id = graduates.user_id
    WHERE users.id = $user_id
";
$result = mysqli_query($conn, $query);
$graduate = mysqli_fetch_assoc($result);

// حفظ التعديلات عند إرسال النموذج
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name  = $_POST['name'];
    $email = $_POST['email'];
    $dept  = $_POST['department'];
    $univ  = $_POST['university'];
    $gpa   = $_POST['gpa'];
    $grad  = $_POST['graduation_year'];

    $cv_path = $graduate['cv_path'] ?? null;

    if (!empty($_FILES['cv_path']['name'])) {
        $upload_dir = "uploads/";
        $cv_path = $upload_dir . basename($_FILES['cv_path']['name']);
        move_uploaded_file($_FILES['cv_path']['tmp_name'], $cv_path);
    }

    // تحديث users
    mysqli_query($conn, "UPDATE users 
                         SET name='$name', email='$email', department='$dept', university='$univ' 
                         WHERE id=$user_id");

    // التحقق إذا يوجد سجل في graduates
    $grad_check = mysqli_query($conn, "SELECT * FROM graduates WHERE user_id=$user_id");
    if (mysqli_num_rows($grad_check) > 0) {
        // تحديث
        mysqli_query($conn, "UPDATE graduates 
                             SET gpa='$gpa', cv_path='$cv_path', graduation_year='$grad' 
                             WHERE user_id=$user_id");
    } else {
        // إدراج جديد
        mysqli_query($conn, "INSERT INTO graduates (user_id, gpa, cv_path, graduation_year) 
                             VALUES ($user_id, '$gpa', '$cv_path', '$grad')");
    }

    header("Location: Student_Profile.php");
    exit;
}
/*session_start();
include 'index.php';

// ✅ التحقق من الجلسة
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// ✅ جلب بيانات المستخدم والخريج معًا
$query = "
    SELECT 
        users.id AS uid,
        users.name, 
        users.email, 
        users.department, 
        users.university,
        graduates.gpa, 
        graduates.cv_path, 
        graduates.graduation_year
    FROM users
    LEFT JOIN graduates ON users.id = graduates.user_id
    WHERE users.id = $user_id
";
$result = mysqli_query($conn, $query);
$graduate = mysqli_fetch_assoc($result);

// ✅ حفظ التعديلات عند إرسال النموذج
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name  = $_POST['name'];
    $email = $_POST['email'];
    $dept  = $_POST['department'];
    $univ  = $_POST['university'];
    $gpa   = $_POST['gpa'];
    $grad  = $_POST['graduation_year'];

    // 📂 معالجة رفع السيرة الذاتية
$cv_path = $graduate['cv_path'] ?? null;

if (!empty($_FILES['cv_path']['name'])) {
    $upload_dir = "uploads/";
    $cv_path = $upload_dir . basename($_FILES['cv_path']['name']);
    move_uploaded_file($_FILES['cv_path']['tmp_name'], $cv_path);
}

// ثم استخدام $cv_path في INSERT أو UPDATE كما ذكرت أعلاه 
    // ✅ تحديث جدول users
    $sql_user = "UPDATE users 
                 SET name='$name', email='$email', department='$dept', university='$univ' 
                 WHERE id=$user_id";
    mysqli_query($conn, $sql_user);

    // ✅ إذا الخريج موجود مسبقًا → حدّث، وإلا أضف سجل جديد
    if ($graduate['gid']) {
        $sql_grad = "UPDATE graduates 
                     SET gpa='$gpa', cv_path='$cv', graduation_year='$grad' 
                     WHERE user_id=$user_id";
    } else {
        $sql_grad = "INSERT INTO graduates (user_id, gpa, cv_path, graduation_year) 
                     VALUES ($user_id, '$gpa', '$cv', '$grad')";
    }
    
    mysqli_query($conn, $sql_grad);

    // 🔁 إعادة تحميل الصفحة بعد الحفظ
    header("Location: Student_Profile.php");
    exit;
}*/
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">
    <title>Student Profile</title>
    <style>
        /* التنسيقات العامة والهيكل الأساسي */
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #fdfaf6; /* لون خلفية فاتح جداً */
            margin: 0;
            display: flex;
            min-height: 100vh;
          /*    justify-content: center;توسيط المحتوى الأفقي */
            align-items: flex-start; /* بدء المحتوى من الأعلى */
        }

        /* حاوية الهيكل بالكامل لتحديد العرض والتوسيط 
        .page-container {
            display: flex; 
            width: 100%; 
            max-width: 1200px; 
            margin: 30px auto; 
            min-height: 80vh;
        }*/

        /* ------------------------------------------- */
        /* تنسيقات الشريط الجانبي (Sidebar) */
        /* ------------------------------------------- 

        .sidebar {
            width: 300px;
            min-width: 280px;
            background-color: transparent;
            min-height: 100vh;
            padding: 30px 0;
            display: flex;
            flex-direction: column;
            align-items: flex-end; 
            position: relative;
        }

        .logo-box {
            background-color: #e6f0f7; /* لون مطابق للخلفية في الصورة 
            width: 80%;
            padding: 20px;
            padding-right: 40px;
            border-radius: 0 0 50px 0; /* زاوية دائرية كبيرة في الأسفل يمين 
            position: absolute;
            top: 0;
            left: 0;
            text-align: right;
            line-height: 1.2;
            color: #1a4d6e;
        }
        
        .logo-box strong {
            font-size: 1.1em;
            display: block;
        }
        
        .logo-box small {
            font-size: 0.7em;
            display: block;
        }

        /* حاوية الروابط 
        .menu-links {
            position: absolute;
            top: 150px; 
            width: 100%;
        }
        
        .menu-item {
            display: flex;
            align-items: center;
            justify-content: flex-start;
            gap: 15px;
            padding: 15px 30px;
            cursor: pointer;
            transition: background-color 0.3s, color 0.3s;
            color: #2373b5ff;
            text-decoration: none;
            font-size: 1.1em;
            font-weight: 500;
        }

        .menu-item span {
            font-size: 1.5em; 
            
        }
        */
        /* تنسيق العنصر النشط */
        .menu-item.active {
            color: #1a4d6e; 
            background-color: #f0f4f8; 
            border-radius: 50px 0 0 50px; 
            position: relative;
            font-weight: bold;
        }
        
        .menu-item.active span {
            filter: none; 
        }

        /* لإنشاء الشكل البيضاوي للقسم النشط */
        .menu-item.active::before {
            content: '';
            position: absolute;
            top: -30px;
            right: 0;
            width: 30px;
            height: 30px;
            background-color: transparent;
            box-shadow: 15px 15px 0 0 #f0f4f8; 
        }

        .menu-item.active::after {
            content: '';
            position: absolute;
            bottom: -30px;
            right: 0;
            width: 30px;
            height: 30px;
            background-color: transparent;
            box-shadow: 15px -15px 0 0 #f0f4f8; 
        }

        /* ------------------------------------------- */
        /* تنسيقات المحتوى الرئيسي (Profile Card) */
        /* ------------------------------------------- */

        .profile-container {
            flex-grow: 1; 
            display: flex;
            justify-content: center; 
            padding: 30px;
            padding-left: 50px; 
        }

        .profile-card {
            background: white;
            border-radius: 20px; 
            padding: 30px;
            width: 100%;
            max-width: 650px; 
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
            align-self: flex-start; 
        }

        /* الجزء العلوي - معلومات المستخدم */
        .profile-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding-bottom: 20px;
        }

        .profile-info {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        /* أيقونة المستخدم (div) */
        .profile-avatar {
            width: 68px; 
            height: 68px; 
            border-radius: 50%; 
            background-color: #3b82f6; 
            border: 4px solid #dbeafe; 
            color: white; 
            display: flex; 
            justify-content: center; 
            align-items: center; 
            font-size: 30px;
        }

        /* التفاصيل الشخصية */
        .profile-details {
            line-height: 1.5;
            color: #333;
        }
        
        .profile-details strong {
            font-size: 1.2em;
            font-weight: 600;
            display: block;
        }

        .profile-details span {
            font-size: 0.9em;
            color: #555;
            display: block;
        }

        /* زر Edit */
        .btn-edit {
            background-color: #3b82f6; 
            color: #fff;
            border: none;
            padding: 8px 20px;
            border-radius: 10px;
            cursor: pointer;
            font-weight: bold;
            transition: background-color 0.2s;
        }

        /* خط فاصل */
        .separator {
            border: 0;
            height: 1px;
            background-color: #e0e0e0;
            margin: 10px 0 30px 0;
        }

        /* قسم النموذج */
        .form-section label {
            display: block;
            margin: 15px 0 5px;
            font-weight: 500;
            color: #555;
            font-size: 0.95em;
        }

        .form-section input,
        .form-section select {
            width: 100%;
            padding: 10px 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            box-sizing: border-box;
            background-color: #f9f9f9;
            transition: border-color 0.2s;
        }

        .form-section input:focus,
        .form-section select:focus {
            border-color: #3b82f6;
            outline: none;
            background-color: white;
        }

        /* تنسيق سنة التخرج ورفع الـ CV في سطر واحد */
        .form-row {
            display: flex;
            justify-content: space-between;
            align-items: flex-end; 
            margin-top: 20px;
            gap: 20px;
        }

        .grad-year-group {
            flex: 1;
        }

        .grad-year-group select {
            width: 100%;
        }

        .upload-group {
            text-align: right;
            display: flex;
            flex-direction: column;
            align-items: flex-end;
        }

        .upload-group label[for="cv-upload"] {
            margin: 0;
        }

        .upload-icon {
            font-size: 20px;
            background-color: #f9f9f9;
            border: 1px solid #ddd;
            padding: 8px 12px;
            border-radius: 8px;
            cursor: pointer;
            transition: background-color 0.2s;
            line-height: 1; 
            margin-top: 5px;
        }
        
        .upload-icon:hover {
            background-color: #eee;
        }
        
        /* إخفاء حقل file الأصلي واستخدام الأيقونة */
        input[type="file"] {
            display: none; 
        }

        /* أزرار الحفظ والإلغاء */
        .actions {
            margin-top: 30px;
            display: flex;
            justify-content: center; /* توسيط الأزرار */
            gap: 20px;
        }

        .actions button {
            width: 150px; 
            padding: 12px;
            border-radius: 10px;
            font-size: 1em;
            font-weight: bold;
            transition: opacity 0.2s;
        }

        .save-btn {
            background: #7DAAFB; 
            color: white;
            border: none;
        }

        .reset-btn {
            background: white;
            border: 1px solid #ddd;
            color: #555;
        }
        
        .actions button:hover {
            opacity: 0.9;
        }
        /* 🔹 Sidebar */
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
.bottom-section {
  margin-bottom: 20px;
}

/* 🔹 Toggle Button */
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

/* 🔹 Top Bar */
.top-bar {
  position: fixed;
  top: 0;
  right: 0;
  left: 230px;
  height: 60px;
  display: flex;
  justify-content: flex-end;
  align-items: center;
  padding: 0 20px;
  transition: left 0.3s;
  z-index: 10;
}
.sidebar.collapsed ~ .top-bar {
  left: 70px;
}
.top-icons {
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

/* 🔹 Main Content */
.main-content {
  margin-left: 230px;
  margin-top: 70px;
  padding: 30px;
  transition: margin-left 0.3s;
  width: 100%;
}
.sidebar.collapsed + .top-bar + .main-content {
  margin-left: 70px;
}
h2 {
  font-size: 22px;
  color: #003366;
  margin-top: 0;
}

/* 🔹 Buttons */
.btn {
  background: #48b29c;
  border: none;
  padding: 10px 18px;
  border-radius: 20px;
  color: #fff;
  cursor: pointer;
  font-size: 16px;
  transition: 0.3s;
}
.btn:hover {
  background: #3b9a86;
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
  }}
    </style>
</head>
<body><!--
    <div class="page-container">
        
        <div class="sidebar">
            <div class="logo-box">
                <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#1a4d6e" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-bottom: 5px; display: block; float: right;">
                    <path d="M12 2l10 5v10l-10 5l-10-5V7l10-5z"></path>
                    <path d="M12 18v-6l-4-2.5"></path>
                    <path d="M12 12l4-2.5"></path>
                    <path d="M12 12l-4-2.5"></path>
                </svg>
                <strong>ATHER GRADUATE</strong>
                <small>ONE CLICK. ONE REGIST. <br> ENDLESS RESOURCES.</small>
            </div>
            
            <div class="menu-links">
                <a href="Student_profile.php" class="menu-item active">
                    <span>👤</span>
                    Profile
                </a>
                <a href="new_request.php" class="menu-item">
                    <span>➕</span>
                    New Request
                </a>
                <a href="track_request.php" class="menu-item">
                    <span>❗</span>
                    Track Request
                </a>
                <a href="notifications.php" class="menu-item">
                    <span>🔔</span>
                    Notifcations
                </a>
            </div>
        </div>
-->
<!-- 🔸 Sidebar -->
<div class="sidebar" id="sidebar">
  <button class="toggle-btn" id="toggleBtn"><i class="fas fa-bars"></i></button>
  <div>
    <div class="logo">
      <img src="logo1.jpg" alt="Logo">
    </div>
    <a href="student_profile.php" class="menu-item"><i class="fas fa-user"></i><span class="menu-text">Profile</span></a>
    <a href="new_request.php" class="menu-item"><i class="fas fa-plus-square"></i><span class="menu-text">New Request</span></a>
    <a href="track_request.php" class="menu-item"><i class="fas fa-clock"></i><span class="menu-text">Track Request</span></a>
  
  </div>

  <div class="bottom-section">
    <a href="setting_s.php" class="menu-item"><i class="fas fa-gear"></i><span class="menu-text">Notification Settings</span></a>
  </div>
</div>

<!-- 🔸 Top Bar -->
<div class="top-bar">
  <div class="top-icons">
    <button class="icon-btn"><a href="notifications.php"><i class="fas fa-bell"></i></a></button>
    <button class="icon-btn" title="Logout"><a href="logout.html"><i class="fas fa-arrow-right-from-bracket"></i></a></button>
  </div>
</div>
        <div class="profile-container">
            <div class="profile-card">
                
                <div class="profile-header">
                    <div class="profile-info">
                        <div class="profile-avatar">
                            <span>👤</span>
                        </div>
                         <div class="profile-details">
                        <strong><?= htmlspecialchars($graduate['name'] ?? '') ?></strong>
                        <span><?= htmlspecialchars($graduate['email'] ?? '') ?></span>
                        <span><?= htmlspecialchars($graduate['department'] ?? '') ?></span>
                        <span><?= htmlspecialchars($graduate['university'] ?? '') ?></span>
                        <span><?= htmlspecialchars($graduate['gpa'] ?? '') ?></span>
                        <span><?= htmlspecialchars($graduate['graduation_year'] ?? '') ?></span>
                    </div>
                    </div>
                    <button class="btn-edit" id="editBtn">Edut</button>
                </div>
                <hr class="separator">
                <div class="form-section">
                    <form method="POST" enctype="multipart/form-data">
                        <label>Full name</label>
                    <input type="text" name="name" value="<?= htmlspecialchars($graduate['name'] ?? '') ?>">

                    <label>Email</label>
                    <input type="email" name="email" value="<?= htmlspecialchars($graduate['email'] ?? '') ?>">

                    <label>Department</label>
                    <input type="text" name="department" value="<?= htmlspecialchars($graduate['department'] ?? '') ?>">

                    <label>University</label>
                    <input type="text" name="university" value="<?= htmlspecialchars($graduate['university'] ?? '') ?>">

                    <label>GPA</label>
                    <input type="text" name="gpa" value="<?= htmlspecialchars($graduate['gpa'] ?? '') ?>">

                        <div class="form-row">
                            <div class="grad-year-group">
                                <label>Graduation Year</label>
                                <select name="graduation_year">
                        <option disabled selected>-- Select year --</option>
                        <?php for ($y = 2020; $y <= 2030; $y++): ?>
                            <option value="<?= $y ?>" <?= ($graduate['graduation_year'] == $y) ? 'selected' : '' ?>><?= $y ?></option>
                        <?php endfor; ?>
                    </select>
                            </div>
                            <!-- عرض CV الحالي -->
                             <label>CV: </label>
                             <?php if (!empty($graduate['cv_path'])): ?>
                               <label><a href="<?= htmlspecialchars($graduate['cv_path']) ?>" target="_blank">CV</a></label>
                                <?php else: ?>
                                    <span>No CV uploaded</span>
                                    <?php endif; ?>
                                    <!-- رفع CV جديد -->
                                     <label for="cv-upload" class="upload-icon">⬆️ Upload CV</label>
                                     <input type="file" name="cv_path" id="cv-upload" hidden>
                                
                                <!--<input type="file" name="cv" id="cv-upload" hidden>
                            </div>-->
                        </div>

                        <div class="actions">
                            <button type="submit" class="save-btn">Save changes</button>
                            <button type="reset" class="reset-btn">Reset</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <script>
// 🔸 Toggle sidebar
const toggleBtn = document.getElementById("toggleBtn");
const sidebar = document.getElementById("sidebar");
toggleBtn.addEventListener("click", () => {
  sidebar.classList.toggle("collapsed");
});

// 🔸 Buttons (temporary JS actions)
function editRequest(id) {
  alert("Edit request #" + id);
  // window.location.href = "edit_request.php?id=" + id;
  }

</script>
</body>
</html>