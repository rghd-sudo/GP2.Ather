<?php
// Database config
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "agdh"; 

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

// Fetch requests (يمكن تغييره لإظهار كل الحالات أو فقط pending)
$stmt = $conn->prepare("SELECT id, graduate_name, request_date, type, purpose, status FROM requests ORDER BY request_date DESC");
$stmt->execute();
$result = $stmt->get_result();
$requests = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

?>
<!DOCTYPE html>
<html lang="ar">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Incoming Recommendation Requests</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">
<style>
  /* --- (تصاميمك الأساسية) --- */
  body {
    margin: 0;
    font-family: "Poppins", sans-serif;
    background: #f9f9f9;
    display: flex;
  }
  /* Sidebar */
  .sidebar {
    background-color: #cde3e8;
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
  .sidebar .logo { text-align: center; margin-bottom: 30px; }
  .sidebar .logo img { width: 80px; }
  .menu-item { display: flex; align-items: center; padding: 12px 20px; color: #333; text-decoration: none; transition: background 0.3s; }
  .menu-item:hover { background: #bcd5db; }
  .menu-item i { font-size: 20px; margin-right: 10px; width: 25px; text-align: center; }
  .menu-text { font-size: 15px; white-space: nowrap; }
  .toggle-btn { position: absolute; top: 20px; right: -15px; background: #003366; color: #fff; border-radius: 50%; border: none; width: 30px; height: 30px; cursor: pointer; }
  .top-icons { position: absolute; top: 20px; right: 30px; display: flex; align-items: center; gap: 20px; }
  .icon-btn { background: none; border: none; cursor: pointer; font-size: 20px; color: #333; }
  .icon-btn:hover { color: #003366; }
  .main-content { margin-left: 230px; padding: 30px; transition: margin-left 0.3s; width: 100%; position: relative; }
  .username { font-size: 18px; color: #003366; font-weight: 600; margin-top: 70px; margin-bottom: 10px; }

  /* Requests grid */
  .requests-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 20px;
    margin-top: 60px;
  }

  .request-card {
    background: #f2f2f2;
    border: 2px solid rgba(0,0,0,0.08);
    border-radius: 8px;
    padding: 18px;
    box-shadow: 0 2px 6px rgba(0,0,0,0.03);
    position: relative;
  }

  .request-header {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 8px;
  }
  .avatar {
    width: 42px;
    height: 42px;
    border-radius: 6px;
    background: #e8f3f7;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #2b7a90;
    font-size: 18px;
  }
  .request-title { font-weight: 700; color: #003366; font-size: 18px; }

  .request-body { color: #222; line-height: 1.4; margin-top: 8px; white-space: pre-wrap; }
  .meta { margin-top: 8px; color: #444; font-size: 14px; }

  .card-actions {
    position: absolute;
    bottom: 12px;
    right: 12px;
    display: flex;
    gap: 8px;
  }

  .btn {
    border: none;
    padding: 8px 14px;
    border-radius: 18px;
    cursor: pointer;
    font-weight: 600;
  }
  .btn-accept { background: #7fc4b8; color: #063; }
  .btn-reject { background: #f5a6a6; color: #700; }

  .status-badge {
    position: absolute;
    top: 12px;
    left: 12px;
    padding: 6px 10px;
    border-radius: 12px;
    font-weight: 600;
    font-size: 13px;
  }
  .status-pending { background: #fff0b3; color: #7a5f00; border: 1px solid rgba(0,0,0,0.05); }
  .status-accepted { background: #e6fbef; color: #0b6b3a; border: 1px solid rgba(0,0,0,0.05); }
  .status-rejected { background: #fdeeee; color: #7a1b1b; border: 1px solid rgba(0,0,0,0.05); }

  /* Responsive */
  @media (max-width: 800px) {
    .sidebar { width: 70px; }
    .main-content { margin-left: 70px; }
  }
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

      <a href="#" class="menu-item"><i class="fas fa-inbox"></i><span class="menu-text">New Requests</span></a>
      <a href="#" class="menu-item"><i class="fas fa-list"></i><span class="menu-text">All Requests</span></a>
      <a href="#" class="menu-item"><i class="fas fa-pen-nib"></i><span class="menu-text">Write Recommendation</span></a>
      <a href="#" class="menu-item"><i class="fas fa-user"></i><span class="menu-text">Profile</span></a>
    </div>

    <div class="bottom-section">
      <a href="#" class="menu-item"><i class="fas fa-gear"></i><span class="menu-text">Notification Settings</span></a>
    </div>
  </div>

  <!-- Main Content -->
  <div class="main-content">
    <!-- Top Icons -->
    <div class="top-icons">
      <button class="icon-btn"><i class="fas fa-bell"></i></button>
      <button class="icon-btn" title="Logout"><i class="fas fa-arrow-right-from-bracket"></i></button>
    </div>

    <h2>Incoming Recommendation Requests</h2>

    <div class="requests-grid">
      <?php if (empty($requests)): ?>
        <p>لا يوجد طلبات حالياً.</p>
      <?php else: ?>
        <?php foreach ($requests as $r): 
          $id = intval($r['id']);
          $name = htmlspecialchars($r['graduate_name']);
          $rawDate = $r['request_date'];
          $dateStr = $rawDate ? date("d/m/Y", strtotime($rawDate)) : '-';
          $type = htmlspecialchars($r['type']);
          $purpose = htmlspecialchars($r['purpose']);
          $status = strtolower($r['status'] ?? 'pending');
          $statusClass = $status === 'accepted' ? 'status-accepted' : ($status === 'rejected' ? 'status-rejected' : 'status-pending');
          $statusLabel = $status === 'accepted' ? 'Accepted' : ($status === 'rejected' ? 'Rejected' : 'Pending');
        ?>
        <div class="request-card">
          <div class="status-badge <?php echo $statusClass; ?>"><?php echo $statusLabel; ?></div>

          <div class="request-header">
            <div class="avatar"><i class="fas fa-user"></i></div>
            <div>
              <div class="request-title">Request #<?php echo $id; ?> — <?php echo $name; ?></div>
              <div style="font-size:13px;color:#666;margin-top:4px;"><?php echo $dateStr; ?> · <?php echo $type; ?></div>
            </div>
          </div>

          <div class="request-body">
            <strong>Purpose:</strong>
            <div class="meta"><?php echo $purpose; ?></div>
          </div>

          <div class="card-actions">
            <?php if ($status !== 'accepted'): ?>
              <form method="post" style="display:inline;">
                <input type="hidden" name="request_id" value="<?php echo $id; ?>">
                <input type="hidden" name="action" value="accept">
                <button class="btn btn-accept" type="submit">Accept</button>
              </form>
            <?php endif; ?>

            <?php if ($status !== 'rejected'): ?>
              <form method="post" style="display:inline;">
                <input type="hidden" name="request_id" value="<?php echo $id; ?>">
                <input type="hidden" name="action" value="reject">
                <button class="btn btn-reject" type="submit">Reject</button>
              </form>
            <?php endif; ?>
          </div>
        </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
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
