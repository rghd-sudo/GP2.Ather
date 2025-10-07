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
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Recommendation System</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      margin: 0;
      background: #fafafa;
    }

    /* ---------- القائمة الجانبية ---------- */
    .sidebar {
      width: 200px;
      background: #cbe2ec;
      height: 100vh;
      position: fixed;
      top: 0;
      left: 0;
      padding-top: 20px;
    }
    .sidebar a {
      display: block;
      padding: 12px;
      color: #000;
      text-decoration: none;
      font-size: 16px;
      margin: 5px 0;
    }
    .sidebar a:hover {
      background: #b2d3e6;
      border-radius: 8px;
    }

    /* ---------- المحتوى ---------- */
    .content {
      margin-left: 220px;
      padding: 20px;
    }

    .logo {
      display: flex;
      align-items: center;
      gap: 10px;
      margin-bottom: 20px;
    }
    .logo img {
      width: 60px;
    }

    .btn {
      background: #48b29c;
      border: none;
      padding: 12px 20px;
      border-radius: 20px;
      color: #fff;
      cursor: pointer;
      font-size: 16px;
    }
    .btn:hover {
      background: #3b9a86;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 15px;
      background: #fff;
    }
    table, th, td {
      border: 1px solid #ccc;
    }
    th, td {
      padding: 12px;
      text-align: center;
    }
    th {
      background: #f5f5f5;
    }
    .pending {
      color: orange;
      font-weight: bold;
    }
    .accepted {
      color: green;
      font-weight: bold;
    }
    .actions button {
      border: none;
      padding: 6px 10px;
      margin: 0 3px;
      border-radius: 6px;
      cursor: pointer;
    }
    .delete {
      background: #f8a5a5;
    }
    .edit {
      background: #a5d8f8;
    }
 
.top_bar {
  display: flex;
  align-items: center;
  width: 100%;
  flex-wrap: nowrap;
  padding: 3px 5px;
  box-sizing: border-box;
}

/* مجموعة الأزرار */
.right_buttons {
  display: flex;
  align-items: center;
  gap: 14px;
  margin-left: auto;
  white-space: nowrap;
}

/* أيقونات */
.icon_btn, .logout_btn {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 28px;
  height: 28px;
  cursor: pointer;
  background: transparent;
  border: none;
  padding: 10;
  text-decoration: none;
}
.icon_btn svg{
   width: 22px;
  height: 22px;
  fill: #ffde3bff; 
}
.logout_btn svg {
  width: 22px;
  height: 22px;
  fill: #03060a;
}

/* للهواتف */
@media (max-width: 480px) {
  .back_btn { font-size: 20px; }
  .icon_btn svg,
  .logout_btn svg { width: 20px; height: 20px; }
}
  </style>
</head>
<body>
<div class="top_bar">
  <div class="right_buttons">
    <!-- جرس التنبيهات -->
    <a href="notifications.php" class="icon_btn" aria-label="الإشعارات">
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512">
        <path d="M224 512c35.3 0 63.1-28.7 
                 63.1-64H160.9c0 35.3 28.7 64 
                 63.1 64zm215.4-149.7c-20.9-21.4-55.5-52.2-55.5-154.3
                 0-77.7-54.5-139.8-127.1-155.2V32c0-17.7-14.3-32-32-32s-32
                 14.3-32 32v20.9C118.5 68.2 64 130.3 64 208c0
                 102.1-34.6 132.9-55.5 154.3-6 6.1-8.5 14.3-8.5
                 22.5 0 16.8 13.2 32 32 32h383.9c18.8 0 32-15.2
                 32-32 0-8.2-2.6-16.4-8.5-22.5z"/>
      </svg>
    </a>

    <!-- زر تسجيل الخروج -->
    <a href="logout.html" class="logout_btn" aria-label="تسجيل خروج">
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512">
        <path d="M377.9 105.9c-12.5-12.5-32.8-12.5-45.3 
                 0s-12.5 32.8 0 45.3L402.7 221H160c-17.7 
                 0-32 14.3-32 32s14.3 32 32 32h242.7l-70.1 
                 69.9c-12.5 12.5-12.5 32.8 0 45.3s32.8 
                 12.5 45.3 0l128-128c12.5-12.5 
                 12.5-32.8 0-45.3l-128-128zM96 
                 64c-35.3 0-64 28.7-64 64v256c0 
                 35.3 28.7 64 64 64h96c17.7 0 
                 32-14.3 32-32s-14.3-32-32-32H96V128h96c17.7 
                 0 32-14.3 32-32s-14.3-32-32-32H96z"/>
      </svg>
    </a>
  </div>
</div>
  <!-- القائمة الجانبية -->
  <div class="sidebar">
    <a href="Student_profile.php">profile</a>
    <a href="new_request.php">New Request</a>
    <a href="track_request.php">Track Request</a>
    <a href="notifications.php">Notifications</a>
  </div>

  <!-- المحتوى -->
  <div class="content">
    <div class="logo">
      <img src="logo.png" alt="Logo">
      <h3>ATHER GRADUATE</h3>
    </div>

    <button class="btn" onclick="window.location.href='new_request.php'">
      + New Recommendation Request
    </button>

    <h3>My Request</h3>
    <table>
      <tr>
        <th>#</th>
        <th>Professor</th>
        <th>Date</th>
        <th>Status</th>
        <th>Actions</th>
      </tr>
      <?php
      // 5️⃣ اجلب فقط الطلبات الخاصة بالمستخدم الحالي
      $sql = "SELECT * FROM requests WHERE user_id = $user_id ORDER BY id DESC";
      $result = $conn->query($sql);
    

      if ($result->num_rows > 0) {
          while($row = $result->fetch_assoc()) {
              echo "<tr>
                      <td>".$row['id']."</td>
                      <td>".$row['professor']."</td>
                      <td>".$row['created_at']."</td>
                      <td class='".($row['status']=="Pending"?"pending":"accepted")."'>".$row['status']."</td>
                      <td class='actions'>
                        <button class='delete'>🗑</button>
                        <button class='edit'>✏</button>
                      </td>
                    </tr>";
          }
      } else {
          echo "<tr><td colspan='5'>لا توجد طلبات بعد</td></tr>";
      }
      ?>
    </table>
  </div>
</body>
</html>