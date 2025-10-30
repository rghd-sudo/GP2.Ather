<?php
session_start();
include 'index.php'; // الاتصال بقاعدة البيانات

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$professor_id = $_SESSION['user_id'];


if (isset($_POST['ajax_action']) && isset($_POST['request_id'])) {
    $request_id = $_POST['request_id'];
    $action = $_POST['ajax_action']; // "accept" أو "reject"

    if ($action === 'accept') {
        $status = 'accepted';
    } elseif ($action === 'reject') {
        $status = 'rejected';
    } else {
        exit('invalid action');
    }

    $update = $conn->prepare("UPDATE requests SET status = ? WHERE id = ? AND professor = ?");
    $update->bind_param("sii", $status, $request_id, $professor_id);
    if ($update->execute()) {
        echo $status;
    } else {
        echo 'error';
    }
    exit;
}

// جلب الطلبات الخاصة بالبروفيسور
$query = "
    SELECT 
        requests.id,
        requests.created_at,
        requests.type,
        requests.purpose,
        requests.status,
        graduates.gpa,
        users.name
    FROM requests
    INNER JOIN users ON requests.user_id = users.id
    INNER JOIN graduates ON graduates.user_id = users.id
    WHERE requests.professor = ?
    ORDER BY requests.created_at DESC
";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $professor_id);
$stmt->execute();
$result = $stmt->get_result();
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

<div class="sidebar" id="sidebar">
  <button class="toggle-btn" id="toggleBtn"><i class="fas fa-bars"></i></button>
  <div>
    <div class="logo"><img src="IMG_1786.PNG" alt="Logo"></div>
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

  <?php while ($request = $result->fetch_assoc()): ?>
    <div class="request-card" id="request-<?php echo $request['id']; ?>">
      <div class="request-info">
        <div class="profile-pic"></div>
        <div class="request-details">
          <h3>Request #<?php echo $request['id']; ?></h3>
          <p><strong>Name:</strong> <?php echo htmlspecialchars($request['name']); ?></p>
          <p><strong>Date:</strong> <?php echo htmlspecialchars($request['created_at']); ?></p>
          <p><strong>Type:</strong> <?php echo htmlspecialchars($request['type']); ?></p>
          <p><strong>GPA:</strong> <?php echo htmlspecialchars($request['gpa']); ?></p>
          <p><strong>Purpose:</strong> <?php echo htmlspecialchars($request['purpose']); ?></p>
        </div>
      </div>
      <div class="action-buttons">
        <?php if ($request['status'] === 'accepted'): ?>
          <div class="accepted">Accepted</div>
        <?php elseif ($request['status'] === 'rejected'): ?>
          <div class="rejected">Rejected</div>
        <?php else: ?>
          <button class="accept-btn" onclick='updateStatus(<?php echo $request["id"]; ?>,"accept")'>Accept</button>
          <button class="reject-btn" onclick='updateStatus(<?php echo $request["id"]; ?>,"reject")'>Reject</button>
        <?php endif; ?>
      </div>
    </div>
  <?php endwhile; ?>
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
