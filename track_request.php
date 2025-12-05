<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

/*
  track_request.php
  - Detailed timeline view for user's requests (LTR / English)
  - Requires $conn (mysqli) from db.php; falls back to local credentials if missing
*/

/* ------------------ DB connection ------------------ */
if (file_exists(__DIR__ . '/db.php')) {
    require_once __DIR__ . '/db.php'; // expects $conn (mysqli)
}

 else {
    $host = "localhost";
    $user = "root";
    $pass = "";
    $dbname = "agdb";
    $conn = new mysqli($host, $user, $pass, $dbname);
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
}

/* ------------------ Auth check ------------------ */
if (!isset($_SESSION['user_id'])) {
    // for local testing you may temporarily set: $_SESSION['user_id'] = 1;
    die('Please log in first.');
}
$user_id = intval($_SESSION['user_id']);

/* ------------------ Ensure minimal tables exist (safe) ------------------ */
/* These CREATE statements use IF NOT EXISTS so they do nothing if you already have tables */
$create_requests_sql = "
CREATE TABLE IF NOT EXISTS requests (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  title VARCHAR(255) DEFAULT '',
  purpose TEXT DEFAULT NULL,
  status VARCHAR(100) DEFAULT 'Created',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";
$conn->query($create_requests_sql);

$create_track_sql = "
CREATE TABLE IF NOT EXISTS track_request (
  id INT AUTO_INCREMENT PRIMARY KEY,
  request_id INT NOT NULL,
  user_id INT NOT NULL,
  status VARCHAR(100) NOT NULL,
  note VARCHAR(255) DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX (request_id),
  INDEX (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";
$conn->query($create_track_sql);


/* ------------------ Fetch user's requests (newest first) ------------------ */
$requests = [];
$sql = "SELECT id, COALESCE(purpose,'') AS purpose, COALESCE(purpose,'') AS purpose, COALESCE(status,'') AS current_status, created_at 
        FROM requests WHERE user_id = ? ORDER BY created_at DESC";
if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $requests = $res->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}
if (!$requests) $requests = [];

/* ------------------ Helpers ------------------ */
function safe($s) { return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }
function fmt_dt($dt) { return safe($dt); }

/* Steps order */
$steps_order = [
    'Created'             => 'Created',
    'Under Review'        => 'Under Review',
    'Professor Response'  => 'Professor Response',
    'Recommendation Sent' => 'Recommendation Sent'
];

/* match a track status text to a step key (customize keywords if needed) */
function match_step($trackStatus, $stepKey) {
    $t = strtolower($trackStatus);
    $k = strtolower($stepKey);

    if ($k === 'created') {
        return (strpos($t, 'create') !== false);
    }

    if ($k === 'under review') {
        return (strpos($t, 'under') !== false || strpos($t, 'review') !== false || strpos($t, 'pending') !== false);
    }

    // 🔹 خطوة واحدة لرد الدكتور: قبول أو رفض
    if ($k === 'professor response') {
        return (strpos($t, 'prof') !== false || strpos($t, 'approve') !== false || strpos($t, 'reject') !== false);
    }

    if ($k === 'recommendation sent') {
        return (strpos($t, 'sent') !== false || strpos($t, 'completed') !== false);
    }

    return false;
}


?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Track Requests</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">
<style>/* 🔹 General Layout */
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
.main-content{ margin-left:230px; margin-top:70px; padding:30px; width:100%; }
  .request-card{ background:#fff; border-radius:10px; padding:18px; margin-bottom:18px; border:1px solid #eef3f6; box-shadow:0 2px 6px rgba(0,0,0,0.06);}
  .request-header{ display:flex; justify-content:space-between; align-items:center; gap:10px; flex-wrap:wrap;}
  .req-title{ font-weight:700; color:#003366; }
  .timeline{ margin-top:12px; }
  .step{ display:flex; align-items:flex-start; gap:12px; padding:10px 0; border-bottom:1px dashed #eef3f6;}
  .step:last-child{ border-bottom:none;}
  .circle{ width:36px; height:36px; border-radius:50%; display:flex; align-items:center; justify-content:center; color:#fff; font-size:18px; flex-shrink:0; }
  .green{ background:#7adba2; } .yellow{ background:#f3d37a; color:#333; } .red{ background:#f26b6b; }
  .status-text{ font-size:14px; color:#222; font-weight:500; }
  .status-time{ font-size:12px; color:#888; margin-top:4px; }
  .current{ box-shadow:0 0 0 4px rgba(122,219,162,0.12); }
  .no-requests{ text-align:center; padding:26px; background:#fff; border-radius:8px; border:1px dashed #cfd8dc; color:#777; }
  .btn{ background:#7adba2; color:#fff; padding:8px 12px; border-radius:6px; border:none; cursor:pointer; }
  @media (max-width: 768px) {
    .main-content { margin-left:70px; }
    .sidebar { width:70px; }
  }
</style>
</head>
<body>
  
<div class="sidebar" id="sidebar">
  <button class="toggle-btn" id="toggleBtn"><i class="fas fa-bars"></i></button>
  <div>
    <div class="logo">
      <img src="logobl.PNG" alt="Logo">

    </div>
    <a href="req_system.php" class="menu-item"><i class="fas fa-home"></i><span class="menu-text">Home</span></a>
    <a href="track_request.php" class="menu-item"><i class="fas fa-clock"></i><span class="menu-text">Track Request</span></a>
   <a href="student_profile.php" class="menu-item"><i class="fas fa-user"></i><span class="menu-text">Profile</span></a>
    
  </div>

  <div class="bottom-section">
    <a href="setting_s.php" class="menu-item"><i class="fas fa-gear"></i><span class="menu-text">Notification Settings</span></a>
  </div>
</div>

<div class="top-bar"> 
  <div class="top-icons">
    <button class="icon-btn" title="Notifications" onclick="window.location.href='notifications.php'"><i class="fas fa-bell"></i></button>
    <button class="icon-btn" title="Logout" onclick="window.location.href='logout.html'"><i class="fas fa-arrow-right-from-bracket"></i></button>
  </div>
</div>

<div class="main-content">
  <h2>Track Requests</h2>

  <?php if (empty($requests)): ?>
    <div class="no-requests">No requests yet.</div>
  <?php else: ?>
    <?php foreach ($requests as $req):
      $reqId = intval($req['id']);
      $reqTitle = $req['purpose'] ?: ($req['purpose'] ?: "Request #{$reqId}");
      $reqCreated = $req['created_at'];
      $current = $req['current_status'] ?? '';

      // fetch track entries (ascending) with note
      $tracks = [];
      if ($s2 = $conn->prepare("SELECT status, note, created_at FROM track_request WHERE request_id = ? ORDER BY created_at ASC")) {
          $s2->bind_param("i", $reqId);
          $s2->execute();
          $r2 = $s2->get_result();
          while ($row = $r2->fetch_assoc()) $tracks[] = $row;
          $s2->close();
      }

      // build map: earliest matching entry per step
      $map = [];
      foreach ($tracks as $tr) {
          foreach ($steps_order as $stepKey => $label) {
              if (!isset($map[$label]) && match_step($tr['status'], $stepKey)) {
                  $map[$label] = ['time'=>$tr['created_at'] ?? '', 'note'=>$tr['note'] ?? '', 'raw'=>$tr['status']];
              }
          }
      }

      // determine current step
      $currentStep = null;
      foreach ($steps_order as $stepKey => $label) {
          if ($current && match_step($current, $stepKey)) { $currentStep = $label; break; }
      }
      if (!$currentStep && !empty($tracks)) {
          $lastTrack = end($tracks);
          foreach ($steps_order as $stepKey => $label) {
              if (match_step($lastTrack['status'], $stepKey)) { $currentStep = $label; break; }
          }
      }
    ?>
      <div class="request-card">
        <div class="request-header">
          <div>
            <div class="req-title"><?php echo safe($reqTitle); ?></div>
            <div class="req-meta">Created at: <?php echo fmt_dt($reqCreated); ?></div>
          </div>
          <div class="req-meta">Current status: <strong><?php echo safe($current ?: 'N/A'); ?></strong></div>
        </div>

        <div class="timeline">
          <?php foreach ($steps_order as $stepKey => $label):
              $completed = isset($map[$label]);
              $is_current = ($currentStep === $label);
              if ($label === 'Professor Response' && $completed) {
    if (stripos($map[$label]['raw'], 'reject') !== false) {
        $colorClass = 'red';    // رفض
    } else {
        $colorClass = 'green';  // قبول
    }
} else {
    $colorClass = $completed ? 'green' : 'red';
}

              if (!$completed && stripos($label, 'Under Review') !== false) $colorClass = 'yellow';
              $circleClass = "circle {$colorClass}" . ($is_current ? ' current' : '');
          ?>
            <div class="step">
              <div class="<?php echo $circleClass; ?>"><?php echo $completed ? '✓' : ''; ?></div>
              <div>
                <div class="status-text"><?php echo safe($label); ?></div>
                <?php if ($completed): ?>
                  <div class="status-time"><?php echo fmt_dt($map[$label]['time']); ?><?php if (!empty($map[$label]['note'])) echo ' • ' . safe($map[$label]['note']); ?></div>
                <?php else: ?>
                  <div class="status-time">Not yet</div>
                <?php endif; ?>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>

  <button class="btn" onclick="location.href='req_system.php'">Back to Home</button>
</div>

<script>
// 🔸 Toggle sidebar
const toggleBtn = document.getElementById("toggleBtn");
const sidebar = document.getElementById("sidebar");
toggleBtn.addEventListener("click", () => {
  sidebar.classList.toggle("collapsed");
});
</script>
</html>