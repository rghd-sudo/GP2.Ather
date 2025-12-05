<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'professor') {
    header("Location: login.php");
    exit;
}

// إعدادات قاعدة البيانات
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "agdb"; 

// الاتصال بقاعدة البيانات
$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_id'], $_POST['action'])) {
    $request_id = intval($_POST['request_id']);
    $action = $_POST['action'];

    if ($action === 'accept' || $action === 'reject') {

        // ⭐⭐ التعديل المهم لنظام التتبع ⭐⭐
        $newStatus = ($action === 'accept') ? 'Professor Approval' : 'Professor Rejection';

        // تحديث حالة الطلب
        $stmt = $conn->prepare("UPDATE requests SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $newStatus, $request_id);
        $stmt->execute();
        $stmt->close();

        // جلب بيانات الطالب لإرسال الإشعار
        $res = $conn->prepare("
            SELECT u.id AS student_user_id, u.name AS student_name, r.purpose 
            FROM requests r 
            JOIN users u ON r.user_id = u.id 
            WHERE r.id = ?
        ");
        $res->bind_param("i", $request_id);
        $res->execute();
        $row = $res->get_result()->fetch_assoc();
        $student_user_id = $row['student_user_id'];
        $student_name = $row['student_name'];
        $purpose = $row['purpose'];
        $res->close();

        // إرسال إشعار للطالب
        $message = "Your request " . $purpose . " has been " . $newStatus . ".";
        $notif = $conn->prepare("INSERT INTO notifications (user_id, message, created_at) VALUES (?, ?, NOW())");
        $notif->bind_param("is", $student_user_id, $message);
        $notif->execute();
        $notif->close();

        // ⭐ إضافة سجل في جدول التتبع ⭐
        $profUserId = $_SESSION['user_id']; 
        $statusTrack = $newStatus;
        $noteTrack = ($action === 'accept') ? 'Approved by Professor' : 'Rejected by Professor';

        $track = $conn->prepare("
            INSERT INTO track_request (request_id, user_id, status, note)
            VALUES (?, ?, ?, ?)
        ");
        $track->bind_param("iiss", $request_id, $profUserId, $statusTrack, $noteTrack);
        $track->execute();
        $track->close();

        echo ($action === 'accept') ? 'accepted' : 'rejected';
        exit;
    }
}

// جلب قائمة الطلبات الخاصة بأستاذ المادة
$user_id = $_SESSION['user_id'];
$result = $conn->query("SELECT professor_id FROM professors WHERE user_id = $user_id");
$row = $result->fetch_assoc();
$professor_id = $row['professor_id'] ?? 0;

$list_q = $conn->prepare("
    SELECT 
        r.id,
        u.name AS graduate_name,
        r.created_at,
        r.type,
        r.purpose,
        r.status
    FROM 
        requests r
    JOIN 
        users u ON r.user_id = u.id
    WHERE 
        r.professor_id = ?
        AND r.status IN ('pending', 'submitted', 'Created', 'Professor Approval', 'Professor Rejection')
    ORDER BY 
        r.created_at DESC
");
$list_q->bind_param("i", $professor_id);
$list_q->execute();
$list_res = $list_q->get_result();

// جلب آخر 10 إشعارات للبروفيسور
$user_id = $professor_id;
$notif_q = $conn->prepare("
    SELECT message, created_at 
    FROM notifications 
    WHERE user_id = ? 
    ORDER BY created_at DESC 
    LIMIT 10
");
$notif_q->bind_param("i", $user_id);
$notif_q->execute();
$notif_res = $notif_q->get_result();
?>


<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Incoming Recommendation Requests</title>

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://netdna.bootstrapcdn.com/font-awesome/4.0.3/css/font-awesome.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">

<style>
body {
    margin: 0;
    font-family: "Poppins", sans-serif;
    background: #fdfaf6;
    display: flex;
}
h2 {
  margin-top: 80px;
  font-size: 22px;
  color: #003366;
  margin-top: -19px;
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
.toggle-btn { position: absolute; top: 20px; right: -15px; background: #003366; color: #fff; border-radius: 50%; border: none; width: 30px; height: 30px; cursor: pointer; }

.top-icons { position: absolute; top: 20px; right: 30px; display: flex; align-items: center; gap: 20px; }
.icon-btn { background:none; border:none; cursor:pointer; font-size:20px; color:#333; }
.icon-btn:hover { color:#003366; }

.main-content {
  margin-left: 230px;
  padding: 30px;
  transition: margin-left 0.3s;
  width: 100%;
  position: relative;
}
.sidebar.collapsed + .main-content { margin-left: 70px; }

/* Cards */
.cards {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
  gap: 26px;
  margin-top: 40px;
}
.card {
  background: #fff;
  border: 1px solid #ddd;
  border-radius: 8px;
  padding: 18px;
  min-height: 170px;
  position: relative;
  box-shadow: 0 4px 10px rgba(0,0,0,0.05);
}
.card-actions {
  position: absolute;
  right: 16px;
  bottom: 14px;
  display:flex;
  gap:10px;
}
.btn-accept {
  background:#7fcfbd;
  border:none;
  padding:8px 14px;
  border-radius:20px;
  cursor:pointer;
  color:#0b3b2e;
  font-weight:700;
}
.btn-reject {
  background:#f3a59a;
  border:none;
  padding:8px 14px;
  border-radius:20px;
  cursor:pointer;
  color:#6b0f0f;
  font-weight:700;
}
.btn-delet {
  background:#f3a59a;
  border:none;
  padding:8px 15px;
  border-radius:20px;
  cursor:pointer;
  color:#6b0f0f;
  font-weight:750;
}

.status-box {
  padding:8px 12px;
  border-radius:18px;
  font-weight:700;
  font-size:14px;
}
.accepted { background:#d4edda; color:#155724; }
.rejected { background:#f8d7da; color:#721c24; }
</style>
</head>

<body>

<!-- Sidebar -->
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

<!-- Main Content -->
<div class="main-content">

  <div class="top-icons">
    <button class="icon-btn" title="Notifications" onclick="window.location.href='prof_notifications.php'">
        <i class="fas fa-bell"></i>
    </button>
    <button class="icon-btn" title="Logout" onclick="window.location.href='logout.html'">
        <i class="fas fa-arrow-right-from-bracket"></i>
    </button>
  </div>

  <h2>Incoming Recommendation Requests</h2>

  <section class="cards">
    <?php
      if ($list_res->num_rows === 0) {
        echo "<div style='grid-column:1/-1;text-align:center;color:#666;'>No requests found.</div>";
      } else {
        while ($r = $list_res->fetch_assoc()):
          $rid = (int)$r['id'];
          $graduate_name = htmlspecialchars($r['graduate_name']);
          $date = htmlspecialchars($r['created_at']);
          $type = htmlspecialchars($r['type']);
          $purpose = htmlspecialchars($r['purpose']);
          $status = $r['status'] ?? '';
    ?>
      <div class="card">
        <h3><?= $graduate_name ?></h3>
        <p><strong>Date:</strong> <?= $date ?></p>
        <p><strong>Type:</strong> <?= $type ?></p>
        <p><strong>Purpose:</strong> <?= $purpose ?></p>

        <div class="card-actions" id="request-<?= $rid ?>">
          <?php if ($status === 'Professor Approval'): ?>
            <div class="status-box accepted">Accepted</div>
            <a href="recommendation-writing.php?id=<?= $rid ?>" class="btn-accept" style="margin-left:10px;">✏️</a>

          <?php elseif ($status === 'Professor Rejection'): ?>
            <div class="status-box rejected">Rejected</div>
            <button type="button" class="btn-delet" style="margin-left:10px;" onclick="deleteCard(<?= $rid ?>)">🗑</button>

          <?php else: ?>
            <button type="button" class="btn-accept" onclick="updateStatus(<?= $rid ?>, 'accept')">Accept</button>
            <button type="button" class="btn-reject" onclick="updateStatus(<?= $rid ?>, 'reject')">Reject</button>
          <?php endif; ?>
        </div>
      </div>
    <?php endwhile; } ?>
  </section>
</div>


<script>
const toggleBtn = document.getElementById("toggleBtn");
const sidebar = document.getElementById("sidebar");
toggleBtn.addEventListener("click", () => {
  sidebar.classList.toggle("collapsed");
});

// تحديث حالة الطلب بدون إعادة تحميل الصفحة
function updateStatus(requestId, action) {
  const formData = new FormData();
  formData.append('request_id', requestId);
  formData.append('action', action);

  fetch('', { method: 'POST', body: formData })
    .then(response => response.text())
    .then(status => {
      const container = document.getElementById('request-' + requestId);
      if (status === 'accepted') {
        container.innerHTML = `
          <div class="status-box accepted">Accepted</div>
          <a href="recommendation-writing.php?id=${requestId}" class="btn-accept" style="margin-left:10px;">✏️</a>`;
      } else if (status === 'rejected') {
        container.innerHTML = `
          <div class="status-box rejected">Rejected</div>
          <button type="button" class="btn-delet" style="margin-left:10px;" onclick="deleteCard(${requestId})">🗑</button>`;
      }
    })
    .catch(error => console.error('Error:', error));
}

// حذف الكارد
function deleteCard(requestId) {
  document.getElementById('request-' + requestId).parentElement.remove();
}
</script>
</body>
</html>