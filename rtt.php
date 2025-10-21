<?php
// 1️ افتح السيشن أول شيء
session_start();
// 4️ خزن معرف المستخدم من السيشن
$user_id = $_SESSION['user_id'];
// 2️ تحقق إذا المستخدم مسجل دخول
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
// 3️ استدعاء الاتصال بقاعدة البيانات
include 'index.php';
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>لوحة التحكم | طلباتي</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    /* المتغيرات والألوان المستخدمة في التصميم */
    :root {
      --bg-color: #F0F4F8; /* الخلفية الرئيسية الفاتحة */
      --sidebar-bg: #e6f0f7; /* لون الخلفية في القسم العلوي من القائمة */
      --main-text: #1a4d6e; /* النص الرئيسي */
      --sub-text: #4b7495; /* النص الثانوي */
      --accent-green: #48b29c; /* الزر الأخضر في التصميم */
      --table-bg: #ffffff; /* خلفية الجدول */
      --active-link-bg: #F0F4F8; /* خلفية العنصر النشط (مطابقة لخلفية الصفحة) */
    }

    /* التنسيقات العامة والهيكل الأساسي */
    body {
      font-family: 'Arial', 'Tahoma', sans-serif;
      background-color: var(--bg-color);
      margin: 0;
      display: flex;
      min-height: 100vh;
      direction: ltr; /* لضبط اتجاه العناصر بالانجليزية */
    }

    .page-container {
      display: flex; 
      width: 100%; 
      margin: 0 auto; 
    }
    
    /* ------------------------------------------- */
    /* تنسيقات الشريط الجانبي (Sidebar) - المأخوذة من الكود الثاني */
    /* ------------------------------------------- */

    .sidebar {
        width: 300px;
        min-width: 280px;
        background-color: transparent;
        min-height: 100vh;
        padding: 30px 0;
        display: flex;
        flex-direction: column;
        align-items: flex-start; /* لضبط الاتجاه في LTR */
        position: relative;
    }

    .logo-box {
        background-color: var(--sidebar-bg); /* لون مطابق للخلفية في الصورة */
        width: 100%; /* تعديل ليغطي العرض بالكامل في LTR */
        padding: 20px 40px;
        border-radius: 0 50px 0 0; /* زاوية دائرية في الأسفل يسار */
        position: absolute;
        top: 0;
        left: 0;
        text-align: left; /* لضبط الاتجاه في LTR */
        line-height: 1.2;
        color: var(--main-text);
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
    }
    
    .logo-box strong { font-size: 1.1em; display: block; }
    .logo-box small { font-size: 0.7em; display: block; }
    .logo-box svg { float: left; margin-left: 10px;} /* لضبط اتجاه الأيقونة في LTR */

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
        color: var(--sub-text);
        text-decoration: none;
        font-size: 1.1em;
        font-weight: 500;
    }

    .menu-item i { /* استخدام أيقونات FontAwesome بدلًا من <span> */
        font-size: 1.5em; 
        color: var(--sub-text); 
    }
    
    /* تنسيق العنصر النشط */
    .menu-item.active {
        color: var(--main-text); 
        background-color: var(--active-link-bg); 
        border-radius: 0 50px 50px 0; /* شكل دائري من الجهة اليمنى */
        position: relative;
        font-weight: bold;
    }
    
    .menu-item.active i {
        color: var(--main-text); 
    }

    /* لإنشاء الشكل البيضاوي للقسم النشط */
    .menu-item.active::before {
        content: '';
        position: absolute;
        top: -30px;
        left: 0;
        width: 30px;
        height: 30px;
        background-color: transparent;
        box-shadow: -15px 15px 0 0 var(--active-link-bg); /* عكس الظل ليتناسب مع LTR */
    }

    .menu-item.active::after {
        content: '';
        position: absolute;
        bottom: -30px;
        left: 0;
width: 30px;
        height: 30px;
        background-color: transparent;
        box-shadow: -15px -15px 0 0 var(--active-link-bg); /* عكس الظل ليتناسب مع LTR */
    }

    /* ------------------------------------------- */
    /* تنسيقات المحتوى الرئيسي (الجدول والتنبيهات) */
    /* ------------------------------------------- */
    .content {
      margin-left: 280px; /* المسافة المخصصة للقائمة الجانبية */
      flex-grow: 1;
      padding: 30px;
    }
    
    .header-controls {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 30px;
        padding: 10px;
    }

    .btn-new {
      background: var(--accent-green);
      border: none;
      padding: 12px 20px;
      border-radius: 25px;
      color: #fff;
      cursor: pointer;
      font-size: 16px;
      font-weight: bold;
      box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
    }
    .btn-new i { margin-right: 5px; }

    .user-controls a {
        color: var(--main-text);
        font-size: 24px;
        margin-left: 15px;
        text-decoration: none;
    }

    /* تنسيق الجرس (Notifications Icon) */
    .bell-icon {
        position: relative;
        color: #f69466; /* لون مشابه للجرس في التصميم */
    }

    .bell-icon::after {
        content: '';
        position: absolute;
        top: -2px;
        right: -2px;
        width: 8px;
        height: 8px;
        background-color: red;
        border-radius: 50%;
        display: none; /* يتم إظهارها عبر PHP أو JS إذا كانت هناك تنبيهات */
    }

    h3 {
      color: var(--main-text);
      font-size: 1.5em;
      margin: 20px 0 15px 0;
    }

    /* تنسيق الجدول */
    .table-container {
      background: var(--table-bg);
      border-radius: 12px;
      box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
      overflow: hidden;
      margin-bottom: 30px;
    }

    table {
      width: 100%;
      border-collapse: collapse;
    }
    
    th, td {
      padding: 15px;
      text-align: left;
      border-bottom: 1px solid #eee;
    }
    th {
      background: #f5f5f5;
      color: var(--sub-text);
      font-weight: 600;
    }

    /* تنسيقات الحالة (Status) */
    .status-pending {
      color: #ff9800; /* برتقالي لـ Pending */
      font-weight: bold;
    }
    .status-accepted {
      color: #4CAF50; /* أخضر لـ Accepted */
      font-weight: bold;
    }
    
    .actions button {
      border: none;
      padding: 8px 10px;
      margin: 0 3px;
      border-radius: 6px;
      cursor: pointer;
      font-size: 16px;
      line-height: 1;
    }
    
    /* زر الحذف */
    .delete-btn {
      background: #f8a5a5; /* لون أحمر فاتح */
      color: #a51a1a;
    }
    /* زر التعديل */
    .edit-btn {
      background: #a5d8f8; /* لون أزرق فاتح */
      color: #1a60a5;
    }

    /* قسم التنبيهات */
    .notifications-section {
        margin-top: 30px;
    }
    .notification-card {
        background: var(--table-bg);
        padding: 20px;
        border-radius: 12px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .notification-card span {
        color: var(--accent-green);
        font-weight: 600;
    }

    .btn-track {
        background: #48b29c; /* الزر الأخضر في التصميم */
        color: white;
        padding: 10px 20px;
        border-radius: 20px;
        border: none;
        cursor: pointer;
    }
  </style>
</head>
<body>
  <div class="page-container">
    
    <div class="sidebar">
        <div class="logo-box">
            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#1a4d6e" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M12 2l10 5v10l-10 5l-10-5V7l10-5z"></path>
                <path d="M12 18v-6l-4-2.5"></path>
                <path d="M12 12l4-2.5"></path>
                <path d="M12 12l-4-2.5"></path>
            </svg>
            <strong>ATHER GRADUATE</strong>
            <small>ONE CLICK. ONE REGIST. <br> ENDLESS RESOURCES.</small>
        </div>
<div class="menu-links">
            <a href="profile.php" class="menu-item">
                <i class="fa-solid fa-user"></i>
                Profile
            </a>
            <a href="new_request.php" class="menu-item active">
                <i class="fa-solid fa-plus"></i>
                New Request
            </a>
            <a href="track_request.php" class="menu-item">
                <i class="fa-solid fa-clock"></i>
                Track Request
            </a>
            <a href="notifications.php" class="menu-item">
                <i class="fa-solid fa-bell"></i>
                Notifcations
            </a>
        </div>
    </div>

    <div class="content">
        
        <div class="header-controls">
             <button class="btn-new" onclick="window.location.href='new_request.php'">
              <i class="fa-solid fa-plus-circle"></i> New Recommendation Request
            </button>
            
            <div class="user-controls">
                <a href="notifications.php" class="bell-icon"><i class="fa-solid fa-bell"></i></a>
                <a href="logout.php"><i class="fa-solid fa-arrow-right-from-bracket"></i></a>
            </div>
        </div>
        
        <h3>My Request</h3>
        <div class="table-container">
            <table>
              <thead>
                <tr>
                    <th>#</th>
                    <th>Professor</th>
                    <th>Date</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php
                // استخدام created_at بدلاً من date كما في مخطط قاعدة البيانات
                // يجب أيضاً تصفية الطلبات للمستخدم الحالي ($user_id)
                $sql = "SELECT id, professor, created_at, status FROM requests WHERE user_id = $user_id ORDER BY id DESC";
                $result = $conn->query($sql);

                if ($result && $result->num_rows > 0) {
                    while($row = $result->fetch_assoc()) {
                        $status_class = strtolower($row['status']) == "pending" ? "status-pending" : "status-accepted";
                        $display_date = date('m/d/Y', strtotime($row['created_at']));
                        
                        echo "<tr>
                                <td>".$row['id']."</td>
                                <td>".$row['professor']."</td>
                                <td>".$display_date."</td>
                                <td class='".$status_class."'>".$row['status']."</td>
                                <td class='actions'>
                                    <button class='delete-btn'>
                                        <i class='fa-solid fa-trash-can'></i>
                                    </button>
                                    <button class='edit-btn'>
                                        <i class='fa-solid fa-pen-to-square'></i>
                                    </button>
                                </td>
                            </tr>";
                    }
                } else {
                    echo "<tr><td colspan='5'>لا توجد طلبات بعد</td></tr>";
                }
                ?>
              </tbody>
            </table>
        </div>
        
        <h3>Notifications</h3>
        <div class="notification-card">
            <span>Accepuets</span>
            <button class="btn-track">Track Request</button>
        </div>

    </div>
  </div>
</body>
</html>