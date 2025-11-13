<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

/*
  prof_notifications.php
  - Show professor's notifications (display only, like student notifications page)
  - LTR layout, left sidebar, Poppins + FontAwesome
*/

/* ---------- DB connection (use db.php if exists) ---------- */
if (file_exists(_DIR_ . '/db.php')) {
    require_once _DIR_ . '/db.php'; // expects $conn (mysqli)
} else {
    $host = "localhost";
    $user = "root";
    $pass = "";
    $dbname = "agdb";
    $conn = new mysqli($host, $user, $pass, $dbname);
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
}

/* ---------- Auth & role check ---------- */
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    die('Please log in.');
}
if ($_SESSION['role'] !== 'professor') {
    die('Access denied: this page is for professors only.');
}
$user_id = intval($_SESSION['user_id']);

/* ---------- Fetch notifications for this professor (newest first) ---------- */
// ensure variable exists and is an array
$notifications = [];

if ($stmt = $conn->prepare("SELECT id, message, created_at FROM notifications WHERE user_id = ? ORDER BY created_at DESC")) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $notifications[] = $row;
    }
    $stmt->close();
}
// if something went wrong above, $notifications remains an array (empty)

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Professor Notifications</title>

  <!-- Fonts & Icons -->
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">

  <style>
  /* General Layout */
  body {
    margin: 0;
    font-family: "Poppins", sans-serif;
    background: #fdfaf6;
    display: flex;
    direction: ltr;
  }

  /* Sidebar */
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
    left: 0;
  }
  .sidebar.collapsed { width: 70px; }
  .sidebar .logo { text-align: center; margin-bottom: 30px; }
  .sidebar .logo img { width: 80px; }
  .menu-item { display: flex; align-items: center; padding: 12px 20px; color: #333; text-decoration: none; transition: background 0.3s; }
  .menu-item:hover { background: #bcd5db; }
  .menu-item i { font-size: 20px; margin-right: 10px; width: 25px; text-align: center; }
  .menu-text { font-size: 15px; white-space: nowrap; }
  .sidebar.collapsed .menu-text { display: none; }
  .bottom-section { margin-bottom: 20px; }

  /* Toggle Button */
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

  /* Top Bar */
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
    background: transparent;
  }
  .sidebar.collapsed ~ .top-bar { left: 70px; }
  .top-icons { display: flex; align-items: center; gap: 20px; }
  .icon-btn { background: none; border: none; cursor: pointer; font-size: 20px; color: #333; text-decoration: none; }
  .icon-btn:hover { color: #003366; }

  /* Main Content */
  .main-content {
    margin-left: 230px;
    margin-top: 70px;
    padding: 30px;
    transition: margin-left 0.3s;
    width: 100%;
  }
  .sidebar.collapsed + .top-bar + .main-content { margin-left: 70px; }
  h2 { font-size: 22px; color: #003366; margin-top: 0; }

  /* Notification card */
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
  .notification-icon { font-size: 20px; margin-right: 12px; line-height: 1; }
  .notification .msg { font-size: 14px; color: #222; }
  .notification .time { font-size: 12px; color: #6f6f6f; margin-top: 6px; }

  .empty { color: #777; background: #fff; border: 1px dashed #cfd8dc; border-radius: 10px; padding: 18px; text-align: center; }

  </style>
</head>
<body>

  <!-- Sidebar -->
  <div class="sidebar" id="sidebar">
    <button class="toggle-btn" id="toggleBtn"><i class="fas fa-bars"></i></button>
    <div>
      <div class="logo">
        <img src="logo1.jpg" alt="Logo">
      </div>
      <a href="prof_profile.php" class="menu-item"><i class="fas fa-user"></i><span class="menu-text">Profile</span></a>
      <a href="prof_requests.php" class="menu-item"><i class="fas fa-list"></i><span class="menu-text">Requests</span></a>
      <a href="prof_notifications.php" class="menu-item"><i class="fas fa-bell"></i><span class="menu-text">Notifications</span></a>
    </div>

    <div class="bottom-section">
      <a href="logout.php" class="menu-item"><i class="fas fa-arrow-right-from-bracket"></i><span class="menu-text">Logout</span></a>
    </div>
  </div>

  <!-- Top bar -->
  <div class="top-bar">
    <div class="top-icons">
      <a class="icon-btn" href="prof_notifications.php" title="Notifications"><i class="fas fa-bell"></i></a>
    </div>
  </div>

  <!-- Main content -->
  <div class="main-content">
    <h2>Notifications</h2>

    <?php if (!empty($notifications)): ?>
        <?php foreach ((array)$notifications as $n): ?>
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
  // Toggle sidebar
  const toggleBtn = document.getElementById("toggleBtn");
  const sidebar = document.getElementById("sidebar");
  toggleBtn.addEventListener("click", () => {
    sidebar.classList.toggle("collapsed");
  });
</script>

</body>
</html>