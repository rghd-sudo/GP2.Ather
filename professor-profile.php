<?php
// الاتصال بقاعدة البيانات
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "agdb";

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }

session_start();
$professor_id = 1; // مؤقتاً، لاحقاً خليه من session

// جلب بيانات البروفيسور
$sql = "SELECT * FROM professors WHERE professor_id = $professor_id";
$result = $conn->query($sql);
$professor_data = $result->fetch_assoc();

// تعديل البيانات عند الحفظ
if (isset($_POST['save'])) {
  $full_name = $_POST['full_name'];
  $email = $_POST['email'];
  $department = $_POST['department'];
  $university = $_POST['university'];

  // رفع CV جديد إذا تم اختياره
  $cv_path = $professor_data['cv_path'];
  if (!empty($_FILES['cv']['name'])) {
      $cv_name = basename($_FILES['cv']['name']);
      $target_path = "uploads/" . $cv_name;
      move_uploaded_file($_FILES['cv']['tmp_name'], $target_path);
      $cv_path = $target_path;
  }

  $update_sql = "UPDATE professors 
                 SET full_name='$full_name', email='$email', department='$department', university='$university', cv_path='$cv_path'
                 WHERE professor_id=$professor_id";
  if ($conn->query($update_sql)) {
      echo "<script>alert('Profile updated successfully!');</script>";
  } else {
      echo "<script>alert('Error updating profile');</script>";
  }
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
    background:  #fdfaf6;;
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
    background-color:  #c8e4eb;
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

  /* Profile Box */
  .profile-box {
    background: #fff;
    padding: 30px;
    border-radius: 12px;
    width: 600px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
  }

  .profile-box h2 {
    color: #003366;
    margin-bottom: 5px;
  }

  .profile-box p {
    color: gray;
    margin-top: 0;
  }

  form label {
    display: block;
    margin-top: 12px;
    font-weight: 600;
  }

  form input[type="text"],
  form input[type="email"],
  form input[type="file"] {
    width: 100%;
    padding: 10px;
    border: 1px solid #ccc;
    border-radius: 5px;
  }

  .buttons {
    margin-top: 20px;
  }

  .buttons button {
    padding: 10px 15px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
  }

  .save-btn {
    background: #4a7dfc;
    color: #fff;
  }

  .reset-btn {
    background: #eee;
    margin-left: 10px;
  }
</style>
</head>
<body>

  <!-- Sidebar -->
  <div class="sidebar" id="sidebar">
    <button class="toggle-btn" id="toggleBtn"><i class="fas fa-bars"></i></button>

    <div>
      <div class="logo">
        <img src="logobl.PNG" alt="Logo">
      </div>

       <a href="requests.php" class="menu-item"><i class="fas fa-home"></i><span class="menu-text">Home</span></a>
      <a href="professor_all_request.php" class="menu-item"><i class="fas fa-list"></i><span>All Requests</span></a>
        <a href="professor-profile.php" class="menu-item"><i class="fas fa-user"></i><span>Profile</span></a>
    </div>

    <div class="bottom-section">
      <a href="setting_D.php" class="menu-item"><i class="fas fa-gear"></i><span class="menu-text">Notification Settings</span></a>
    </div>
  </div>

  <!-- Main Content -->
  <div class="main-content">
    <div class="top-icons">
    <button class="icon-btn" title="Notifications" onclick="window.location.href='notifications.php'"><i class="fas fa-bell"></i></button>
    <button class="icon-btn" title="Logout" onclick="window.location.href='logout.html'"><i class="fas fa-arrow-right-from-bracket"></i></button>
    </div>

    <div class="profile-box">
      <h2><?php echo $professor_data['full_name']; ?></h2>
      <p><?php echo $professor_data['email']; ?></p>
      <p><?php echo $professor_data['department'] . " - " . $professor_data['university']; ?></p>

      <form method="POST" enctype="multipart/form-data">
        <label>Full Name</label>
        <input type="text" name="full_name" value="<?php echo $professor_data['full_name']; ?>" required>

        <label>Email</label>
        <input type="email" name="email" value="<?php echo $professor_data['email']; ?>" required>

        <label>Department</label>
        <input type="text" name="department" value="<?php echo $professor_data['department']; ?>" required>

        <label>University</label>
        <input type="text" name="university" value="<?php echo $professor_data['university']; ?>" required>

        <label>Upload CV</label>
        <input type="file" name="cv" accept=".pdf,.doc,.docx">

        <?php if(!empty($professor_data['cv_path'])): ?>
          <p>Current CV: <a href="<?php echo $professor_data['cv_path']; ?>" target="_blank">View</a></p>
        <?php endif; ?>

        <div class="buttons">
          <button type="submit" name="save" class="save-btn">Save Changes</button>
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
</script>
</body>
</html>
