<?php
session_start();
include 'index.php';
$user_id = 1; 
$graduates = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM graduates WHERE user_id=$user_id"));
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $dept = $_POST['department'];
    $univ = $_POST['university'];
    $uid = $_POST['user_id'];
    $grad = $_POST['graduation_year'];

    $cv = $graduates['cv_path'];
    if (!empty($_FILES['cv_path']['name'])) {
        $cv = "uploads/" . basename($_FILES['cv_path']['name']);
        move_uploaded_file($_FILES['cv_path']['tmp_name'], $cv);
    }

    $sql = "UPDATE graduates 
            SET name='$name', email='$email', department='$dept', university='$univ',
                user_id='$uid', graduation_year='$grad', cv_path='$cv' 
            WHERE id=$user_id";
    mysqli_query($conn, $sql);
    header("Location: profile.php");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Student Profile</title>
    <style>
        /* التنسيقات العامة والهيكل الأساسي */
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #F0F4F8; /* لون خلفية فاتح جداً */
            margin: 0;
            display: flex;
            min-height: 100vh;
            justify-content: center; /* توسيط المحتوى الأفقي */
            align-items: flex-start; /* بدء المحتوى من الأعلى */
        }

        /* حاوية الهيكل بالكامل لتحديد العرض والتوسيط */
        .page-container {
            display: flex; 
            width: 100%; 
            max-width: 1200px; 
            margin: 30px auto; 
            min-height: 80vh;
        }

        /* ------------------------------------------- */
        /* تنسيقات الشريط الجانبي (Sidebar) */
        /* ------------------------------------------- */

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
            background-color: #e6f0f7; /* لون مطابق للخلفية في الصورة */
            width: 80%;
            padding: 20px;
            padding-right: 40px;
            border-radius: 0 0 50px 0; /* زاوية دائرية كبيرة في الأسفل يمين */
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

        /* حاوية الروابط */
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
            color: #4b7495;
            text-decoration: none;
            font-size: 1.1em;
            font-weight: 500;
        }

        .menu-item span {
            font-size: 1.5em; 
            filter: grayscale(100%) brightness(0.7); 
        }
        
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
    </style>
</head>
<body>
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

        <div class="profile-container">
            <div class="profile-card">
                
                <div class="profile-header">
                    <div class="profile-info">
                        <div class="profile-avatar">
                            <span>👤</span>
                        </div>
                        <div class="profile-details">
                            <strong><?= htmlspecialchars($users['name']) ?></strong>
                            <span><?= htmlspecialchars($users['email']) ?></span>
                            <span><?= htmlspecialchars($users['department']) ?></span>
                            <span><?= htmlspecialchars($users['university']) ?></span>
                            <span><?= htmlspecialchars($graduates['user_id'] . ' _ ' . $graduates['graduation_year']) ?></span>
                        </div>
                    </div>
                    <button class="btn-edit" id="editBtn">Edut</button>
                </div>
                <hr class="separator">
                <div class="form-section">
                    <form method="POST" enctype="multipart/form-data">
                        
                        <label>Full name</label>
                        <input type="text" name="name" value=""> 

                        <label>Email</label>
                        <input type="email" name="email" value="">

                        <label>Department</label>
                        <input type="text" name="department" value="">

                        <label>University</label>
                        <input type="text" name="university" value="">

                        <label>Student ID</label>
                        <input type="text" name="user_id" value="">

                        <div class="form-row">
                            <div class="grad-year-group">
                                <label>Graduation Year</label>
                                <select name="graduation">
                                    <option value="" disabled selected>-- Select year --</option>
                                    <?php 
                                    // حلقة توليد السنوات من 2024 إلى 2030
                                    for ($y = 2024; $y <= 2030; $y++): 
                                    ?>
                                        <option value="<?= $y ?>" <?= ($graduates['graduation_year'] == $y) ? "selected" : "" ?>>
                                            <?= $y ?>
                                        </option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            
                            <div class="upload-group">
                                <label>Upload CV</label>
                                <label for="cv-upload" class="upload-icon">
                                    <span>⬆</span>
                                </label>
                                <input type="file" name="cv" id="cv-upload" hidden>
                            </div>
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
</body>
</html>