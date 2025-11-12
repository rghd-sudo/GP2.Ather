<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

/* ------------------ إعداد الاتصال بقاعدة البيانات ------------------ */
if (file_exists(__DIR__. '/db.php')) {
    require_once __DIR__ . '/db.php';
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

/* ------------------ تأكد تسجيل الدخول ------------------ */
if (!isset($_SESSION['user_id'])) {
    die('Please log in first.');
}
$user_id = intval($_SESSION['user_id']);

/* ------------------ إنشاء جدول track_request إن لم يكن موجود ------------------ */
$create_sql = "
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
$conn->query($create_sql);

/* ------------------ جلب طلبات المستخدم الحالي ------------------ */
$requests = [];
if ($stmt = $conn->prepare("SELECT id, COALESCE(purpose, '') AS purpose, status AS current_status, created_at FROM requests WHERE user_id = ? ORDER BY created_at DESC")) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $requests = $res->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}
if (!$requests) $requests = [];

/* ------------------ دالة مساعدة لعرض التاريخ ------------------ */
function fmt_datetime($dt) {
    return htmlspecialchars($dt, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Track Request</title>

<!-- Fonts & Icons -->
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://netdna.bootstrapcdn.com/font-awesome/4.0.3/css/font-awesome.css">
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
.menu-item {
  display: flex;
  align-items: center;
  padding: 12px 20px;
  color: #333;
  text-decoration: none;
  transition: background 0.3s;
}
.menu-item:hover { background: #bcd5db; }
.menu-item i { font-size: 20px; margin-right: 10px; width: 25px; text-align: center; }
.menu-text { font-size: 15px; white-space: nowrap; }
.sidebar.collapsed .menu-text { display: none; }
.bottom-section { margin-bottom: 20px; }

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

.main-content {
  margin-left: 230px;
  margin-top: 70px;
  padding: 30px;
  transition: margin-left 0.3s;
  width: 100%;
}
.sidebar.collapsed + .top-bar + .main-content { margin-left: 70px; }
h2 { font-size: 22px; color: #003366; margin-top: 0; }

.request-card {
  background: #fff;
  border-radius: 10px;
  padding: 18px;
  margin-bottom: 18px;
  box-shadow: 0 2px 6px rgba(0,0,0,0.06);
  border: 1px solid #eef3f6;
}
.request-header { display:flex; justify-content: space-between; align-items: center; gap: 10px; flex-wrap: wrap; }
.req-title { font-weight:700; color:#003366; }
.req-meta { color:#6f6f6f; font-size:13px; }

.timeline { margin-top:12px; }
.step { display:flex; align-items:flex-start; gap:12px; padding:10px 0; border-bottom: 1px dashed #eef3f6; }
.step:last-child { border-bottom: none; }
.circle { width:36px; height:36px; border-radius:50%; display:flex; align-items:center; justify-content:center; color:#fff; font-size:18px; flex-shrink: 0; }
.status-text { font-size:14px; color:#222; font-weight:500; }
.status-time { font-size:12px; color:#888; margin-top:2px; }

.status-msg { font-weight:bold; margin-top:2px; }

.no-requests { text-align:center; padding:26px; background:#fff; border-radius:8px; border:1px dashed #cfd8dc; color:#777; }

.back-btn { margin-top: 20px; background-color: #7adba2; border: none; padding: 10px 18px; border-radius: 8px; font-size: 14px; cursor: pointer; color:#fff; }
</style>
</head>
<body>

<div class="sidebar" id="sidebar">
  <button class="toggle-btn" id="toggleBtn"><i class="fas fa-bars"></i></button>
  <div>
    <div class="logo">
      <img src="LOGObl.PNG" alt="Logo">
    </div>
    
      <a href="requests.php" class="menu-item"><i class="fas fa-file-circle-plus"></i><span class="menu-text">New Request</span></a>
      <a href="professor_all_request.php" class="menu-item"><i class="fas fa-list"></i><span class="menu-text">All Requests</span></a>
      <a href="professor-profile.php" class="menu-item"><i class="fas fa-user"></i><span class="menu-text">Profile</span></a>
    </div>
  <div class="bottom-section">
    <a href="setting_s.php" class="menu-item"><i class="fas fa-gear"></i><span class="menu-text">Notification Settings</span></a>
  </div>
</div>

<div class="top-bar">
  <div class="top-icons">
    <a class="icon-btn" href="notifications.php" title="Notifications"><i class="fas fa-bell"></i></a>
    <a class="icon-btn" href="logout.html" title="Logout"><i class="fas fa-arrow-right-from-bracket"></i></a>
  </div>
</div>

<div class="main-content">
<h2>Track Request</h2>

<?php if (count($requests) === 0): ?>
  <div class="no-requests">No requests yet.</div>
<?php else: ?>
  <?php foreach ($requests as $req):
    $reqId = intval($req['id']);
    $reqTitle = $req['purpose'] ?: "Request #{$reqId}";
    $reqCreated = $req['created_at'];

    $tracks = [];
    if ($s2 = $conn->prepare("SELECT status, created_at FROM track_request WHERE request_id = ? ORDER BY created_at DESC")) {
        $s2->bind_param("i", $reqId);
        $s2->execute();
        $r2 = $s2->get_result();
        $tracks = $r2->fetch_all(MYSQLI_ASSOC);
        $s2->close();
    }
  ?>
    <div class="request-card">
      <div class="request-header">
        <div>
          <div class="req-title"><?php echo htmlspecialchars($reqTitle, ENT_QUOTES, 'UTF-8'); ?></div>
          <div class="req-meta">Created at: <?php echo fmt_datetime($reqCreated); ?></div>
        </div>
        <div class="req-meta">Current status: <strong><?php echo htmlspecialchars($req['current_status'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></strong></div>
      </div>

      <div class="timeline">
        <?php if (count($tracks) === 0): ?>
          <div style="padding:10px 0;color:#777;">No updates for this request yet.</div>
        <?php else: ?>
          <?php foreach ($tracks as $t):
            $st = $t['status'];
           $note = $t['note'] ?? '';
            $created = $t['created_at'];

            // اللون والرسالة حسب الحالة
            $color = '#7adba2';
            $status_msg = "Approved ✅";

            if (stripos($st, 'pending') !== false || stripos($st, 'under review') !== false || stripos($st, 'review') !== false) {
                $color = '#f3d37a';
                $status_msg = "Under Review ⏳";
            }
            if (stripos($st, 'rejected') !== false || stripos($st, 'declined') !== false) {
                $color = '#f26b6b';
                $status_msg = "Rejected ❌";
            }
          ?>
            <div class="step">
              <div class="circle" style="background: <?php echo $color; ?>;">
                <i class="fa fa-check" aria-hidden="true" style="font-size:14px;"></i>
              </div>
              <div>
                <div class="status-text"><?php echo htmlspecialchars($st, ENT_QUOTES, 'UTF-8'); ?></div>
                <div class="status-msg" style="color: <?php echo $color; ?>;"><?php echo $status_msg; ?></div>
                <?php if (!empty($note)): ?><div class="status-time"><?php echo htmlspecialchars($note, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
                <div class="status-time"><?php echo fmt_datetime($created); ?></div>
              </div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>

    </div>
  <?php endforeach; ?>
<?php endif; ?>

<button class="back-btn" onclick="window.location.href='req_system.php'">Back to Home</button>
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