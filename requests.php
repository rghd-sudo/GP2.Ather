<?php
// Database config
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "agdb"; 

// Connect
$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle accept/reject action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['request_id'])) {
    $action = $_POST['action']; // "accept" or "reject"
    $request_id = intval($_POST['request_id']);

    if ($action === 'accept' || $action === 'reject') {
        $newStatus = ($action === 'accept') ? 'accepted' : 'rejected';
        $stmt = $conn->prepare("UPDATE requests SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $newStatus, $request_id);
        $stmt->execute();
        $stmt->close();
        // بعد التحديث نعيد التحميل حتى تظهر الحالة المحدثة
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }
}
$stmt = $conn->prepare("
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
    ORDER BY 
        r.created_at DESC
");
$list_q->bind_param("i", $professor_id);
$list_q->execute();
$list_res = $list_q->get_result();

// جلب آخر 10 إشعارات للبروفيسور
$notif_q = $conn->prepare("SELECT message, created_at FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 10");
$notif_q->bind_param("i", $user_id);
$notif_q->execute();
$notif_res = $notif_q->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Professor Requests</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">
<style>
 body { margin: 0; font-family: "Poppins", sans-serif; background: #f9f9f9; display: flex; }
h2 { margin-top: -19px; font-size: 22px; color: #003366; }
.sidebar { background-color: #cde3e8; width: 230px; transition: width 0.3s; height: 100vh; padding-top: 20px; box-shadow: 2px 0 5px rgba(0,0,0,0.1); position: fixed; display: flex; flex-direction: column; justify-content: space-between; }
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
.icon-btn { background: none; border: none; cursor: pointer; font-size: 20px; color: #333; }
.icon-btn:hover { color: #003366; }
.main-content { margin-left: 230px; padding: 30px; transition: margin-left 0.3s; width: 100%; position: relative; }
.sidebar.collapsed + .main-content { margin-left: 70px; }
.username { font-size: 18px; color: #003366; font-weight: 600; margin-top: 70px; margin-bottom: 10px; }
.request-card { background: #fff; padding: 20px; margin-bottom: 20px; border-radius: 12px; box-shadow: 0 2px 6px rgba(0,0,0,0.1); display: flex; align-items: center; justify-content: space-between; }
.request-info { display: flex; align-items: center; gap: 15px; }
.profile-pic { width: 60px; height: 60px; border-radius: 50%; background: #ccc; }
.request-details { display: flex; flex-direction: column; }
.request-details h3 { margin: 0; color: #003366; font-size: 16px; font-weight: bold; }
.request-details p { margin: 2px 0; color: #555; font-size: 14px; }
.action-buttons button { padding: 8px 15px; border: none; border-radius: 6px; cursor: pointer; font-size: 14px; transition: 0.3s; }
.accept-btn { background-color: #4CAF50; color: white; }
.reject-btn { background-color: #E74C3C; color: white; }
.accepted, .rejected { padding: 8px 15px; border-radius: 6px; font-size: 14px; font-weight: bold; }
.accepted { background: #C8E6C9; color: #2E7D32; }
.rejected { background: #FADBD8; color: #C0392B; }
</style>
</head>
<body>

  <!-- Sidebar -->
  <div class="sidebar" id="sidebar">
    <button class="toggle-btn" id="toggleBtn"><i class="fas fa-bars"></i></button>

    <div>
      <div class="logo">
        <img src="IMG_1786.PNG" alt="Logo">
      </div>

      <a href="requests.php" class="menu-item"><i class="fas fa-home"></i><span class="menu-text">Home</span></a>
      <a href="professor_all_request.php" class="menu-item"><i class="fas fa-list"></i><span>All Requests</span></a>
      <a href="professor-profile.php" class="menu-item"><i class="fas fa-user"></i><span>Profile</span></a>
    </div>
    <div class="bottom-section">
        <a href="setting_D.php" class="menu-item"><i class="fas fa-gear"></i><span>Notification Settings</span></a>
    </div>
</div>

<div class="main-content">
  <div class="top-icons">
    <button class="icon-btn"><i class="fas fa-bell"></i></button>
    <button class="icon-btn" title="Logout"><i class="fas fa-arrow-right-from-bracket"></i></button>
  </div>

  <h2>Incoming Recommendation Requests</h2>

    <section class="cards">
      <?php
        if ($list_res->num_rows === 0) {
          echo "<div style='grid-column:1/-1;text-align:center;color:#666;'>No requests found.</div>";
        } else {
          while ($r = $list_res->fetch_assoc()):
            $rid = (int)$r['id'];
            $student_name = htmlspecialchars($r['student_name']);
            $date = htmlspecialchars($r['created_at']);
            $type = htmlspecialchars($r['type']);
            $purpose = htmlspecialchars($r['purpose']);
            $status = $r['status'] ?? '';
      ?>
        <div class="card">
          <h3><?= $student_name ?></h3>
          <p><strong>Date:</strong> <?= $date ?></p>
          <p><strong>Type:</strong> <?= $type ?></p>
          <p><strong>Purpose:</strong> <?= $purpose ?></p>

          <div class="card-actions">
            <?php if (strtolower($status) === 'accepted'): ?>
              <div class="status-box accepted">Accepted</div>
            <?php elseif (strtolower($status) === 'rejected'): ?>
              <div class="status-box rejected">Rejected</div>
            <?php else: ?>
              <form method="POST">
                <input type="hidden" name="request_id" value="<?= $rid ?>">
                <input type="hidden" name="action" value="accept">
                <button type="submit" class="btn-accept">Accept</button>
              </form>
              <form method="POST">
                <input type="hidden" name="request_id" value="<?= $rid ?>">
                <input type="hidden" name="action" value="reject">
                <button type="submit" class="btn-reject">Reject</button>
              </form>
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

//  لتحديث الحالة بدون إعادة تحميل الصفحة
function updateStatus(requestId, action) {
  const formData = new FormData();
  formData.append('request_id', requestId);
  formData.append('ajax_action', action);

  fetch('', { method: 'POST', body: formData })
    .then(response => response.text())
    .then(status => {
      const container = document.getElementById('request-' + requestId);
      if (status === 'accepted') {
        container.querySelector('.action-buttons').innerHTML = '<div class="accepted">Accepted</div>';
      } else if (status === 'rejected') {
        container.querySelector('.action-buttons').innerHTML = '<div class="rejected">Rejected</div>';
      }
    });
}
</script>
</body>
</html>
