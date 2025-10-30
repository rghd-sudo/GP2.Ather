<?php
session_start();
include 'index.php';



// Fetch professor and user info  professors.cv_path
$query = "
    SELECT 
        users.id AS uid,
        users.name, 
        users.email, 
        users.department, 
        users.university,
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
    $name  = $_POST['name'];
    $email = $_POST['email'];
    $dept  = $_POST['department'];
    $univ  = $_POST['university'];

    $cv_path = $professor['cv_path'] ?? null;

    if (!empty($_FILES['cv_path']['name'])) {
        $upload_dir = "uploads/";
        $cv_path = $upload_dir . basename($_FILES['cv_path']['name']);
        move_uploaded_file($_FILES['cv_path']['tmp_name'], $cv_path);
    }

    $conn->query("UPDATE users SET name='$name', email='$email' WHERE id=$user_id");
   // $conn->query("UPDATE professors SET department='$dept', university='$univ', cv_path='$cv_path' WHERE user_id=$user_id");

    $success_message = "Profile updated successfully!";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Professor Profile</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">
<style>
body {
    margin: 0;
    font-family: "Poppins", sans-serif;
    background: #f9f9f9;
    display: flex;
}

h2 {
  font-size: 22px;
  color: #003366;
  margin-top: -19px;
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

   <a href="requests.php" class="menu-item"><i class="fas fa-home"></i><span class="menu-text">Home</span></a>
      <a href="professor_all_request.php" class="menu-item"><i class="fas fa-list"></i><span>All Requests</span></a>
      <a href="professor-profile.php" class="menu-item"><i class="fas fa-user"></i><span>Profile</span></a>
    </div>
    <div class="bottom-section">
        <a href="setting_D.php" class="menu-item"><i class="fas fa-gear"></i><span>Notification Settings</span></a>
    </div>
</div>

<!-- Main Content -->
<div class="main-content">
  <div class="top-icons">
    <button class="icon-btn"><i class="fas fa-bell"></i></button>
    <button class="icon-btn" title="Logout"><i class="fas fa-arrow-right-from-bracket"></i></button>
  </div>

  <div class="profile-card">
    <?php if (!empty($success_message)): ?>
      <div class="success-message" id="successMessage"><?= htmlspecialchars($success_message) ?></div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
      <label>Full Name</label>
      <input type="text" name="name" value="<?= htmlspecialchars($professor['name'] ?? '') ?>">

      <label>Email</label>
      <input type="email" name="email" value="<?= htmlspecialchars($professor['email'] ?? '') ?>">

      <label>Department</label>
      <input type="text" name="department" value="<?= htmlspecialchars($professor['department'] ?? '') ?>">

      <label>University</label>
      <input type="text" name="university" value="<?= htmlspecialchars($professor['university'] ?? '') ?>">

      <label>CV:</label>
      <input type="file" name="cv_path">

      <div class="actions">
        <button type="submit" class="save-btn">Save changes</button>
        <button type="reset" class="reset-btn">Reset</button>
      </div>
    </form>
  </div>
</div>

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
