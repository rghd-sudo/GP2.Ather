<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

/*
  prof_notifications.php
  - Show professor's notifications (display only, like student notifications page)
  - LTR layout, left sidebar, Poppins + FontAwesome
*/

/* ------------------ 1) DB connection ------------------ */
if (file_exists(_DIR_. '/db.php')) {
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

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'professor') {
    header("Location: login.php");
    exit;
}

$user_id = intval($_SESSION['user_id']);

/* ------------------ 3) Ensure settings table exists ------------------ */
$create_sql = "
CREATE TABLE IF NOT EXISTS notification_settings (
  user_id INT NOT NULL,
  role ENUM('student','professor') NOT NULL DEFAULT 'student',
  notify_new_request TINYINT(1) DEFAULT 1,
  notify_pending TINYINT(1) DEFAULT 1,
  notify_rejected TINYINT(1) DEFAULT 1,
  notify_uploaded TINYINT(1) DEFAULT 1,
  via_email TINYINT(1) DEFAULT 0,
  via_in_app TINYINT(1) DEFAULT 1,
  reminder_days INT DEFAULT 2,
  PRIMARY KEY (user_id, role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";
$conn->query($create_sql); // ignore errors here

/* ------------------ 4) Handle POST (save settings) ------------------ */
$success_msg = '';
$error_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $notify_new_request = isset($_POST['notify_new_request']) ? 1 : 0;
    $notify_pending = isset($_POST['notify_pending']) ? 1 : 0;
    $notify_rejected = isset($_POST['notify_rejected']) ? 1 : 0;
    $notify_uploaded = isset($_POST['notify_uploaded']) ? 1 : 0;
    $via_email = isset($_POST['via_email']) ? 1 : 0;
    $via_in_app = isset($_POST['via_in_app']) ? 1 : 0;
    $reminder_days = isset($_POST['reminder_days']) ? intval($_POST['reminder_days']) : 2;

    $sql = "INSERT INTO notification_settings 
        (user_id, role, notify_new_request, notify_pending, notify_rejected, notify_uploaded, via_email, via_in_app, reminder_days)
        VALUES (?, 'professor', ?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
          notify_new_request = VALUES(notify_new_request),
          notify_pending = VALUES(notify_pending),
          notify_rejected = VALUES(notify_rejected),
          notify_uploaded = VALUES(notify_uploaded),
          via_email = VALUES(via_email),
          via_in_app = VALUES(via_in_app),
          reminder_days = VALUES(reminder_days)";

    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("iiiiiiii", $user_id, $notify_new_request, $notify_pending, $notify_rejected, $notify_uploaded, $via_email, $via_in_app, $reminder_days);
        if ($stmt->execute()) {
            $success_msg = "Settings saved successfully.";
        } else {
            $error_msg = "Failed to save settings: " . $stmt->error;
        }
        $stmt->close();
    } else {
        $error_msg = "Prepare failed: " . $conn->error;
    }
}

/* ------------------ 5) Load current settings ------------------ */
$settings = [
    'notify_new_request' => 1,
    'notify_pending' => 1,
    'notify_rejected' => 1,
    'notify_uploaded' => 1,
    'via_email' => 0,
    'via_in_app' => 1,
    'reminder_days' => 2
];

if ($stmt = $conn->prepare("SELECT notify_new_request, notify_pending, notify_rejected, notify_uploaded, via_email, via_in_app, reminder_days FROM notification_settings WHERE user_id = ? ")) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) {
        $settings['notify_new_request'] = intval($row['notify_new_request']);
        $settings['notify_pending'] = intval($row['notify_pending']);
        $settings['notify_rejected'] = intval($row['notify_rejected']);
        $settings['notify_uploaded'] = intval($row['notify_uploaded']);
        $settings['via_email'] = intval($row['via_email']);
        $settings['via_in_app'] = intval($row['via_in_app']);
        $settings['reminder_days'] = intval($row['reminder_days']);
    }
    $stmt->close();
}

/* ------------------ 6) Load professor notifications (THE FIX) ------------------ */

$notifications = [];

$sql = "SELECT message, created_at 
        FROM notifications 
        WHERE professor_id = ? 
        ORDER BY created_at DESC";

if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $res = $stmt->get_result();

    while ($row = $res->fetch_assoc()) {
        $notifications[] = $row;
    }

    $stmt->close();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Professor Notifications</title>

  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">

  <style>
  body {
    margin: 0;
    font-family: "Poppins", sans-serif;
    background: #fdfaf6;
    display: flex;
    direction: ltr;
  }

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

  .top-icons {
    position: absolute;
    top: 20px;
    right: 30px;
    display: flex;
    align-items: center;
    gap: 20px;
  }
  .icon-btn { background: none; border: none; cursor: pointer; font-size: 20px; color: #333; }

  .main-content {
    margin-left: 230px;
    margin-top: 70px;
    padding: 30px;
    transition: margin-left 0.3s;
    width: 100%;
  }

  h2 { font-size: 22px; color: #003366; margin-top: 0; }

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
  .notification-icon { font-size: 20px; margin-right: 12px; }
  .notification .msg { font-size: 14px; color: #222; }
  .notification .time { font-size: 12px; color: #6f6f6f; margin-top: 6px; }

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

<div class="sidebar" id="sidebar">
    <button class="toggle-btn" id="toggleBtn"><i class="fas fa-bars"></i></button>
    <div>
      <div class="logo"><img src="LOGObl.PNG" alt="Logo"></div>
      <a href="requests.php" class="menu-item"><i class="fas fa-file-circle-plus"></i><span class="menu-text">New Request</span></a>
      <a href="professor_all_request.php" class="menu-item"><i class="fas fa-list"></i><span class="menu-text">All Requests</span></a>
      <a href="professor-profile.php" class="menu-item"><i class="fas fa-user"></i><span class="menu-text">Profile</span></a>
    </div>

    <div class="bottom-section">
      <a href="setting_D.php" class="menu-item"><i class="fas fa-gear"></i><span class="menu-text">Notification Settings</span></a>
    </div>
</div>

<div class="main-content">
  <div class="top-icons">
    <button class="icon-btn"title="Notifications" onclick="window.location.href='prof_notifications.php'"><i class="fas fa-bell"></i></button>
    <button class="icon-btn" title="Logout" onclick="window.location.href='logout.html'"><i class="fas fa-arrow-right-from-bracket"></i></button>
  </div>

  <h2>Notifications</h2>

  <?php if (!empty($notifications)): ?>
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
const toggleBtn = document.getElementById("toggleBtn");
const sidebar = document.getElementById("sidebar");
toggleBtn.addEventListener("click", () => {
  sidebar.classList.toggle("collapsed");
});
</script>

</body>
</html>