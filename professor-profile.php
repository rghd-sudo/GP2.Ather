<?php
session_start();
include 'index.php';

// تأكد من تسجيل الدخول
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'professor') {
    header("Location: login.php");
    exit();
}

// ------------------
//  🔥 حل المشكلة هنا
// ------------------
$user_id = $_SESSION['user_id'];


// Fetch professor and user info
$query = "
    SELECT 
        users.id AS uid,
        users.name, 
        users.email, 
        users.department, 
        users.university,
        professors.specialization,
        professors.cv_path
    FROM professors
    JOIN users ON professors.user_id = users.id
    WHERE users.id = ?
";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$professor = $result->fetch_assoc();

$success_message = "";

// Update professor data when form submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name  = $conn->real_escape_string($_POST['name']);
    $email = $conn->real_escape_string($_POST['email']);
    $dept  = $conn->real_escape_string($_POST['department']);
    $univ  = $conn->real_escape_string($_POST['university']);
    $spec  = $conn->real_escape_string($_POST['specialization']);

    $cv_path = $professor['cv_path'] ?? null;

    // رفع أو تحديث الـCV
    if (isset($_FILES['cv_path']) && $_FILES['cv_path']['error'] === 0) {

    $upload_dir = "uploads/";
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    $ext = pathinfo($_FILES['cv_path']['name'], PATHINFO_EXTENSION);
    $cv_name = "CV_" . $user_id . "_" . time() . "." . $ext;
    $cv_path = $upload_dir . $cv_name;

    move_uploaded_file($_FILES['cv_path']['tmp_name'], $cv_path);
}


    // تحديث بيانات المستخدم
    $conn->query("UPDATE users SET name='$name', email='$email', department='$dept', university='$univ' WHERE id=$user_id");

    // تحديث بيانات البروفسور
    $conn->query("UPDATE professors SET cv_path='$cv_path', specialization='$spec' WHERE user_id=$user_id");

    $professor['cv_path'] = $cv_path;

    $success_message = "Profile updated successfully!";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Professor Profile</title>
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

/* Bottom Section */
.bottom-section {
  margin-bottom: 20px;
}

/* Collapse Button */
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

/* Top Icons */
.top-icons {
  position: absolute;
  top: 20px;
  right: 30px;
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

/* Main Content */
.main-content {
  margin-left: 230px;
  padding: 30px;
  transition: margin-left 0.3s;
  width: 100%;
  position: relative;
}

.sidebar.collapsed + .main-content {
  margin-left: 70px;
}

/* Profile Card */
.profile-card {
  background: white;
  border-radius: 15px;
  padding: 30px;
  box-shadow: 0 5px 20px rgba(0,0,0,0.1);
  max-width: 700px;
  margin: 120px auto;
}

/* Success Message */
.success-message {
  background: #d1f7d6;
  color: #2d7a32;
  border: 1px solid #9de5a2;
  padding: 10px 15px;
  border-radius: 8px;
  text-align: center;
  font-weight: bold;
  margin-bottom: 15px;
  display: none;
}

/* Form styling */
form label {
  display: block;
  margin: 10px 0 5px;
  font-weight: 500;
  color: #333;
}

form input {
  width: 100%;
  padding: 8px;
  border-radius: 8px;
  border: 1px solid #ccc;
}

.actions {
  text-align: center;
  margin-top: 20px;
}
.actions button {
  margin: 10px;
  padding: 10px 20px;
  border-radius: 8px;
  border: none;
  font-weight: bold;
}
.save-btn {
  background: #7DAAFB;
  color: white;
}
.reset-btn {
  background: white;
  border: 1px solid #ddd;
  color: #555;
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
  }}
</style>
</head>
<body>

<!-- Sidebar -->
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
    <a href="setting_D.php" class="menu-item"><i class="fas fa-gear"></i><span class="menu-text">Notification Settings</span></a>
  </div>
</div>

<!-- Main Content -->
<div class="main-content">
  <div class="top-icons">
    <button class="icon-btn"title="Notifications" onclick="window.location.href='prof_notifications.php'"><i class="fas fa-bell"></i></button>
    <button class="icon-btn" title="Logout"onclick="window.location.href='logout.html'"><i class="fas fa-arrow-right-from-bracket"></i></button>
  </div><!-- ====== TOP PROFILE SUMMARY CARD (MATCHED WITH YOUR THEME) ====== -->
<div class="top-profile-card">
    <h2>
        <?= htmlspecialchars($professor['name'] ?? 'Professor Name') ?>
    </h2>

    <p>
        <i class="fa-solid fa-envelope"></i> 
        <?= htmlspecialchars($professor['email'] ?? 'email@example.com') ?>
    </p>

    <p>
        <i class="fa-solid fa-building-columns"></i> 
        <?= htmlspecialchars($professor['university'] ?? 'University') ?>
    </p>
</div>


<style>
/* === Matched Profile Summary Card === */
.profile-summary {
    background: linear-gradient(135deg, #7DAAFB, #003366);
    color: white;
    padding: 25px;
    border-radius: 15px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.12);
    margin-top: 110px;
    margin-bottom: 25px;
    width: 80%;
    max-width: 850px;
}

.profile-summary h2 {
    margin: 0;
    font-size: 22px;
    font-weight: 700;
}

.profile-summary p {
    margin: 4px 0;
    font-size: 14px;
    opacity: 0.95;
}



/* === FORM MATCHING YOUR PAGE === */
.prof-form {
    background: white;
    padding: 25px;
    border-radius: 15px;
    box-shadow: 0 4px 18px rgba(0,0,0,0.08);
    width: 95%;
    max-width: 850px;
}

.prof-form label {
    font-weight: 600;
    color: #003366;
}

.prof-form input {
    width: 80%;
    padding: 10px;
    border-radius: 10px;
    border: 1px solid #cdd6e2;
    background: #fafafa;
    font-size: 15px;
}

/* Fields layout */
.prof-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 18px;
}

/* Buttons */
.prof-actions {
    text-align: center;
    margin-top: 25px;
}

.prof-actions button {
    padding: 10px 25px;
    border-radius: 10px;
    font-weight: bold;
    border: none;
    font-size: 15px;
    cursor: pointer;
}

.prof-save {
    background: #7DAAFB;
    color: white;
}

.prof-reset {
    background: white;
    border: 1px solid #ccc;
    color: #555;
}
.top-profile-card {
  width: 95%;
    background: #c8e4eb; /* نفس لون السايد بار */
    padding: 25px 18px;
    border-radius: 15px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.08);
    margin: 8px auto 20px ; /* بدون مسافة فوق */
    margin-top: 25px; 
    border-left: 6px solid #003366; /* لمسة احترافية */
}

.top-profile-card h2 {
    margin: 0;
    font-size: 20px;
    font-weight: 700;
    color: #003366;
}

.top-profile-card p {
    margin: 5px 0 0;
    font-size: 14px;
    color: #003366;
    opacity: 0.9;
}
.spec{

    grid-column: span 2;
    width: 100%;
}
/* Responsive */
@media (max-width: 768px) {
    .prof-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<!-- =================== PROFESSIONAL FORM =================== -->
<form method="POST" enctype="multipart/form-data" class="prof-form">

    <div class="prof-grid">
        <!-- Full Name -->
        <div>
            <label>Full Name</label>
            <input type="text" name="name"
                value="<?= htmlspecialchars($professor['name'] ?? '') ?>">
        </div>

        <!-- Email -->
        <div>
            <label>Email</label>
            <input type="email" name="email"
                value="<?= htmlspecialchars($professor['email'] ?? '') ?>">
        </div>

        <!-- Department -->
        <div>
            <label>Department</label>
            <input type="text" name="department"
                value="<?= htmlspecialchars($professor['department'] ?? '') ?>">
        </div>

        <!-- University -->
        <div>
            <label>University</label>
            <input type="text" name="university"
                value="<?= htmlspecialchars($professor['university'] ?? '') ?>">
        </div>
    
        <!-- University -->
        <div class="spec">
            <label>specialization</label>
            <input type="text" name="specialization"
                value="<?= htmlspecialchars($professor['specialization'] ?? '') ?>">
        </div>
    </div>

    <!-- CV UPLOAD -->
    <!-- Upload CV -->
    <div style="margin-top: 18px;">
        <label>Upload CV</label>
        <input type="file" name="cv_path" accept=".pdf,.doc,.docx">
    </div>

    <?php if (!empty($professor['cv_path'])): ?>
        <a href="<?= htmlspecialchars($professor['cv_path']) ?>" target="_blank"
           style="
               display:inline-block;
               margin-top:10px;
               background:#003366;
               color:white;
               padding:8px 15px;
               border-radius:8px;
               font-weight:600;
               text-decoration:none;">
            <i class="fa-solid fa-file-lines"></i> View Current CV
        </a>
    <?php endif; ?>


    <div class="prof-actions">
        <button class="prof-save">Save Changes</button>
        <button type="reset" class="prof-reset">Reset</button>
    </div>
</form>

<script>
const toggleBtn = document.getElementById("toggleBtn");
const sidebar = document.getElementById("sidebar");
toggleBtn.addEventListener("click", () => {
  sidebar.classList.toggle("collapsed");
});

// show success message with fade out
const msg = document.getElementById("successMessage");
if (msg) {
  msg.style.display = "block";
  setTimeout(() => {
    msg.style.opacity = "0";
    msg.style.transition = "opacity 1s ease";
    setTimeout(() => msg.remove(), 1000);
  }, 3000);
}
</script>
</body>
</html>
