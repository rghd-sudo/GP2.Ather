<?php
session_start();
// تأكد من أن index.php يقوم بتضمين اتصال قاعدة البيانات ($conn)
include 'index.php'; 

// تحقق من تسجيل الدخول
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'graduate') {
  header("Location: login.php");
  exit();
}
$user_id = $_SESSION['user_id'];

// اجلب اسم المستخدم
$sql_user = "SELECT name FROM users WHERE id = '$user_id'";
$result_user = $conn->query($sql_user);
$user_name = "User";

if ($result_user && $result_user->num_rows > 0) {
    $row_user = $result_user->fetch_assoc();
    $user_name = htmlspecialchars($row_user['name']);
}

// استعلام الطلبات مع اسم الأستاذ والتوصية (إن وجدت)
$sql = "
SELECT 
    r.*,
    u.name AS professor_name,
    rec.recommendation_id,
    rec.pdf_path,
    rec.content
FROM requests r
JOIN professors p ON r.professor_id = p.professor_id
JOIN users u ON p.user_id = u.id
LEFT JOIN recommendations rec ON rec.request_id = r.id
WHERE r.user_id = $user_id
ORDER BY r.id DESC
";

// تنفيذ الاستعلام
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Recommendation System</title>
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

/* 🔹 Main Content */
.main-content {
  margin-left: 230px;
  margin-top: 70px;
  padding: 30px;
  transition: margin-left 0.3s;
  width: 100%;
}
.sidebar.collapsed + .top-bar + .main-content {
  margin-left: 70px;
}
h2 {
  font-size: 22px;
  color: #003366;
  margin-top: 0;
}

/* 🔹 Buttons */
.btn {
  background: #48b29c;
  border: none;
  padding: 10px 18px;
  border-radius: 20px;
  color: #fff;
  cursor: pointer;
  font-size: 16px;
  transition: 0.3s;
}
.btn:hover {
  background: #3b9a86;
}

/* 🔹 Table */
table {
  width: 100%;
  border-collapse: collapse;
  margin-top: 20px;
  background: #fff;
  border-radius: 10px;
  overflow: hidden;
}
table, th, td {
  border: 1px solid #ddd;
}
th, td {
  padding: 12px;
  text-align: center;
}
th {
  background: #f5f5f5;
  color: #333;
}
.pending {
  color: orange;
  font-weight: bold;
}
.accepted {
  color:  #3b9a86;
  font-weight: bold;
}
.rejected {
  color: red;
  font-weight: bold;
}
.draft {
  color: gray;
  font-weight: bold;
}
.Completed{
  color: green;
  font-weight: bold;
}
.actions button {
  border: none;
  padding: 6px 10px;
  margin: 0 3px;
  border-radius: 6px;
  cursor: pointer;
  font-weight: bold;
}
.delete {
background: #f8a5a5;
}
.edit {
  background: #a5d8f8;
}

/* 🔹 Responsive */
@media (max-width: 768px) {
  .main-content {
    margin-left: 70px;
  }
  .sidebar {
    width: 70px;
  }
  .menu-text {
    display: none;
  }
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
  <h2>Welcome, <?php echo $user_name; ?></h2>

  <button class="btn" onclick="window.location.href='new_request.php'">
    + New Recommendation Request
  </button>

  <h3>My Requests</h3>

  <table>
    <tr>
      <th>#</th>
      <th>Professor</th>
      <th>Date</th>
      <th>Status</th>
      <th>Actions</th>
    </tr>
    <?php
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {

        $professor_name = $row['professor_name'] ?? '—'; 
        $status = strtolower($row['status']);

        if ($status == "draft") {
            $display_status = "Under Process";
            $class = "draft";
        } elseif ($status == "pending") {
            $display_status = "Pending";
            $class = "pending";
        } elseif ($status == "accepted") {
            $display_status = "Accepted";
            $class = "accepted";
        } elseif ($status == "completed") {
            $display_status = "Completed";
            $class = "completed";
        } elseif ($status == "rejected") {
            $display_status = "Rejected";
            $class = "rejected";
        } else {
            $display_status = ucfirst($row['status']);
            $class = "completed";
        }

        echo "<tr>
                <td>".$row['id']."</td>
                <td>".$professor_name."</td>
                <td>".$row['created_at']."</td>
                <td class='".$class."'>".$display_status."</td>
                
                <td class='actions'>";

        // ✔ زر Edit
        echo "<button class='edit' onclick=\"editRequest(".$row['id'].")\">✏️ Edit</button>";

        // ✔ زر Delete
        echo "<button class='delete' onclick=\"deleteRequest(".$row['id'].", this)\">🗑 Delete</button>";

        // ⭐ زر Download يظهر فقط إذا الطلب Completed
        if ($status == "completed" || $status == "completed") {
            echo "<a href='download_recommendation.php?id=".$row['recommendation_id']."'>⬇ Download</a>";
        }

        echo "</td>
              </tr>";
    }
} else {
    echo "<tr><td colspan='5'>No requests found</td></tr>";
}
?>

  </table>
</div>

<script>
// 🔸 Toggle sidebar
const toggleBtn = document.getElementById("toggleBtn");
const sidebar = document.getElementById("sidebar");
toggleBtn.addEventListener("click", () => {
  sidebar.classList.toggle("collapsed");
});

// 🔸 Buttons (تم تفعيلها للتوجيه لملفات المعالجة)

function editRequest(id) {
  // 🚀 يتم التوجيه لصفحة التعديل، يجب إنشاء ملف edit_request.php
  window.location.href = "edit_req.php?id=" + id;
}


function deleteRequest(id, btn) {
  if (!confirm("Are you sure you want to delete this request?")) return;

  fetch("delete_request.php", {
    method: "POST",
    headers: { "Content-Type": "application/x-www-form-urlencoded" },
    body: "id=" + id
  })
  .then(response => response.text())
  .then(result => {
    if (result.trim() === "success") {
  const row = btn.closest("tr");
  row.style.transition = "opacity 0.5s";
  row.style.opacity = "0";
    }
  setTimeout(() => {
    row.remove();
    updateStats(); // ← هذا السطر يضاف هنا
  }, 500);

  alert("✅ تم حذف الطلب بنجاح");
})
  .catch(error => {
    alert("⚠️ فشل الاتصال بالسيرفر");
    console.error(error);
  });

}
</script>
</body>
</html>