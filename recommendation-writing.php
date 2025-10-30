<?php
// الاتصال بقاعدة البيانات
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "agdb";

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    die("فشل الاتصال بقاعدة البيانات: " . $conn->connect_error);
}

// الحصول على معرف الخريج من الرابط
session_start();

if (!isset($_SESSION['user_id'])) {
  header("Location: login.php");
  exit();
}

$professor_id = $_SESSION['user_id'];
$request_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$graduate = null;
$requests = null;

// 🟩 جلب بيانات الخريج والطلب
if ($request_id > 0) {
  $sql = "
    SELECT 
      u.name, u.National_ID, u.department,
      g.graduation_year, g.gpa,
      r.purpose, r.type, r.major,
      g.user_id AS graduate_user_id
    FROM requests r
    JOIN graduates g ON r.user_id = g.user_id
    JOIN users u ON g.user_id = u.id
    WHERE r.id = ? AND r.professor_id = ?
  ";

  $stmt = $conn->prepare($sql);
  $stmt->bind_param("ii", $request_id, $professor_id);
  $stmt->execute();
  $result = $stmt->get_result();

  if ($result->num_rows > 0) {
    $graduate = $result->fetch_assoc();
  }
}

// 🟦 حفظ التوصية (مسودة أو إرسال)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
  $action = $_POST['action'];
  $text = trim($_POST['recommendation_text']);

  if ($graduate && !empty($text)) {
    // إدخال التوصية الجديدة
    $insert_sql = "
      INSERT INTO recommendations (content, professor_id, graduate_id, date_created, request_id)
      VALUES (?, ?, ?, NOW(), ?)
    ";
    $stmt = $conn->prepare($insert_sql);
    $stmt->bind_param("siii", $text, $professor_id, $graduate['graduate_user_id'], $request_id);
    $stmt->execute();

    // تحديث حالة الطلب
    $status = ($action === 'draft') ? 'draft' : 'sent';
    $update_sql = "UPDATE requests SET status = ? WHERE id = ?";
    $stmt2 = $conn->prepare($update_sql);
    $stmt2->bind_param("si", $status, $request_id);
    $stmt2->execute();

    echo "<script>alert('Recommendation $status saved successfully!'); window.location='professor_main.php';</script>";
    exit();
  } else {
    echo "<script>alert('Please write a recommendation before saving.');</script>";
  }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Recommendation Writing</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">
<style>
body {
    margin: 0;
    font-family: "Poppins", sans-serif;
    background: #fdfaf6;
    display: flex;
}

/* Sidebar */
.sidebar {
  background-color: #c8e4eb;
  width: 230px;
  height: 100vh;
  position: fixed;
  padding-top: 20px;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
}

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

.main-content {
    margin-left: 230px;
    padding: 40px;
    width: 100%;
}

/* Info box */
.info-box {
    background: #f1f1f1;
    padding: 20px;
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 10px;
    border-radius: 8px;
    margin-bottom: 20px;
}
.info-item { background: #e3e3e3; padding: 10px; border-radius: 6px; }
.info-item b { color: #003366; }

/* Form */
textarea { width: 100%; height: 200px; margin-top: 10px; padding: 10px; border: 1px solid #ccc; border-radius: 6px; resize: none; }
button { margin-top: 15px; padding: 10px 20px; border: none; border-radius: 6px; cursor: pointer; }
.send-btn { background-color: #003366; color: white; margin-right: 10px; }
.draft-btn { background-color: #f39c12; color: white; margin-right: 10px; }
.cancel-btn { background-color: #ccc; }
</style>
</head>
<body>

<div class="sidebar">
    <div>
        <div class="logo" style="text-align:center; margin-bottom:30px;">
            <img src="LOGObl.PNG" width="80">
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
    <h2>Recommendation Writing</h2>

<?php if ($graduate): ?>
    <div class="info-box">
        <div class="info-item"><b>Name:</b> <?= htmlspecialchars($graduate['name']) ?></div>
        <div class="info-item"><b>National ID:</b> <?= htmlspecialchars($graduate['National_ID']) ?></div>
        <div class="info-item"><b>Department:</b> <?= htmlspecialchars($graduate['department']) ?></div>
        <div class="info-item"><b>Graduation Year:</b> <?= htmlspecialchars($graduate['graduation_year']) ?></div>
        <div class="info-item"><b>GPA:</b> <?= htmlspecialchars($graduate['gpa']) ?></div>
        <div class="info-item"><b>Major:</b> <?= htmlspecialchars($requests['major'] ?? '-') ?></div>
        <div class="info-item"><b>Purpose:</b> <?= htmlspecialchars($requests['purpose'] ?? '-') ?></div>
        <div class="info-item"><b>Recommendation Type:</b> <?= htmlspecialchars($requests['type'] ?? '-') ?></div>
    </div>

    <form method="POST">
        <textarea name="recommendation_text" placeholder="Write your recommendation here..." required></textarea>
        <br>
        <button type="button" class="cancel-btn" onclick="history.back()">Cancel</button>
        <button type="submit" name="action" value="draft" class="draft-btn">Save Draft</button>
        <button type="submit" name="action" value="sent" class="send-btn">Send Recommendation</button>
    </form>
<?php else: ?>
    <p>No graduate found.</p>
<?php endif; ?>

</div>
</body>
</html>




