<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

/* ============ 1) التأكد من تسجيل الدخول ============ */
if (!isset($_SESSION['user_id'])) {
    // لو عندك صفحة تسجيل دخول استبدلي الرابط هنا
    die('الرجاء تسجيل الدخول أولاً.');
}
$user_id = intval($_SESSION['user_id']);

include 'index.php'; // ملف الاتصال بقاعدة البيانات

/* ============ 3) جلب التنبيهات للمستخدم الحالي (الأحدث أولاً) ============ */
$notifications = [];
$stmt = $conn->prepare("SELECT message, created_at FROM notifications WHERE user_id = ? ORDER BY created_at DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $notifications[] = $row;
}
$stmt->close();
$conn->close();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Notifications</title>

    <!-- الخطوط والأيقونات (زي ما أرسلتي) -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://netdna.bootstrapcdn.com/font-awesome/4.0.3/css/font-awesome.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">

    <style>
    /* 🔹 General Layout */
    body {
      margin: 0;
      font-family: "Poppins", sans-serif;
      background: #fdfaf6;
      display: flex;
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
    .sidebar.collapsed { width: 70px; }
    .sidebar .logo { text-align: center; margin-bottom: 30px; }
    .sidebar .logo img { width: 80px; }
    .menu-item {
      display: flex;
      align-items: center;
      padding: 12px 20px;
      color: #333;
      text-decoration: none;
      transition: background 0.3s;
    }
    .menu-item:hover { background: #bcd5db; }
    .menu-item i {
      font-size: 20px;
      margin-right: 10px;
      width: 25px;
      text-align: center;
    }
    .menu-text { font-size: 15px; white-space: nowrap; }
    .sidebar.collapsed .menu-text { display: none; }
    .bottom-section { margin-bottom: 20px; }

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
    .sidebar.collapsed ~ .top-bar { left: 70px; }
    .top-icons { display: flex; align-items: center; gap: 20px; }
    .icon-btn {
      background: none;
      border: none;
      cursor: pointer;
      font-size: 20px;
      color: #333;
      text-decoration: none;
    }
    .icon-btn:hover { color: #003366; }

    /* 🔹 Main Content */
    .main-content {
      margin-left: 230px;
      margin-top: 70px;
      padding: 30px;
      transition: margin-left 0.3s;
      width: 100%;
    }
    .sidebar.collapsed + .top-bar + .main-content { margin-left: 70px; }
    h2 {
      font-size: 22px;
      color: #003366;
      margin-top: 0;
      margin-bottom: 15px;
    }

    /* 🔹 Notification Card */
    .notification {
      background: #fff;
      padding: 14px 16px;
      margin: 10px 0;
      border-radius: 10px;
      display: flex;
      align-items: center;
      box-shadow: 0px 2px 6px rgba(0,0,0,0.08);
      border: 1px solid #eef3f6;
    }
    .notification-icon {
      font-size: 20px;
      margin-right: 12px;
      line-height: 1;
    }
    .notification .msg {
      font-size: 14px;
      color: #222;
    }
    .notification .time {
      font-size: 12px;
      color: #6f6f6f;
      margin-top: 4px;
    }
    .empty {
      color: #777;
      background: #fff;
      border: 1px dashed #cfd8dc;
      border-radius: 10px;
      padding: 18px;
      text-align: center;
    }
    </style>
</head>
<body>

  <!-- 🔸 Sidebar -->
  <div class="sidebar" id="sidebar">
    <button class="toggle-btn" id="toggleBtn"><i class="fas fa-bars"></i></button>
    <div>
      <div class="logo">
       <img src="logobl.PNG" alt="Logo">
      </div>
        <a href="req_system.php" class="menu-item"><i class="fas fa-home"></i><span class="menu-text">Home</span></a>
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
      <a class="icon-btn" href="notifications.php" title="Notifications"><i class="fas fa-bell"></i></a>
      <a class="icon-btn" href="logout.html" title="Logout"><i class="fas fa-arrow-right-from-bracket"></i></a>
    </div>
  </div>

  <!-- 🔸 Main Content -->
  <div class="main-content">
    <h2>Notifications</h2>

    <?php if (count($notifications) > 0): ?>
        <?php foreach ($notifications as $n): ?>
            <div class="notification">
              <div class="notification-icon">🔔</div>
              <div>
                <div class="msg"><?php echo htmlspecialchars($n['message'] ?? '', ENT_QUOTES, 'UTF-8'); ?></div>
                <div class="time"><?php echo htmlspecialchars($n['created_at'] ?? '', ENT_QUOTES, 'UTF-8'); ?></div>
              </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="empty">No notifications yet.</div>
    <?php endif; ?>
  </div>

  <script>
    // 🔸 Toggle sidebar
    const toggleBtn = document.getElementById("toggleBtn");
    const sidebar = document.getElementById("sidebar");
    toggleBtn.addEventListener("click", () => {
      sidebar.classList.toggle("collapsed");
    });
  </script>
</body>
</html>