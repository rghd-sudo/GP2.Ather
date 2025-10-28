<?php
// الاتصال بقاعدة البيانات
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "agdb";

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

session_start();
$professor_id = 1; // مؤقتاً، لاحقاً من session

// جلب بيانات البروفيسور مع معلومات المستخدم
$sql = "SELECT professors.*, users.name, users.email, users.university, users.department
        FROM professors 
        JOIN users ON professors.user_id = users.id
        WHERE professors.professor_id = $professor_id";

$result = $conn->query($sql);
$professor_data = $result->fetch_assoc();

if (!$professor_data) {
    die('Professor not found.');
}

// عند الحفظ
if (isset($_POST['save'])) {
    $full_name = $_POST['full_name'];
    $email = $_POST['email'];
    $department = $_POST['department'];
    $university = $_POST['university'];
    $specialization = $_POST['specialization'];

    // التعامل مع رفع CV
    $cv_path = $professor_data['cv_path']; // احتفظ بالقديم
    if (!empty($_FILES['cv']['name'])) {
        $cv_name = time() . "_" . basename($_FILES['cv']['name']);
        $target_path = "uploads/" . $cv_name;
        if (move_uploaded_file($_FILES['cv']['tmp_name'], $target_path)) {
            $cv_path = $target_path;
        }
    }

    // تحديث بيانات المستخدم
    $update_user = "UPDATE users 
                    SET name='$full_name', email='$email', department='$department', university='$university'
                    WHERE id={$professor_data['user_id']}";

    // تحديث بيانات البروفيسور
    $update_prof = "UPDATE professors 
                    SET specialization='$specialization', cv_path='$cv_path'
                    WHERE professor_id=$professor_id";

    if ($conn->query($update_user) && $conn->query($update_prof)) {
        echo "<script>alert('Profile updated successfully!');</script>";
        echo "<meta http-equiv='refresh' content='0'>";
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
    background: #fdfaf6;
    display: flex;
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
      <h2><?php echo htmlspecialchars($professor_data['name']); ?></h2>
      <p><?php echo htmlspecialchars($professor_data['email']); ?></p>
      <p><?php echo htmlspecialchars($professor_data['department']) . " - " . htmlspecialchars($professor_data['university']); ?></p>

      <form method="POST" enctype="multipart/form-data">
        <label>Full Name</label>
        <input type="text" name="full_name" value="<?php echo htmlspecialchars($professor_data['name']); ?>" required>

        <label>Email</label>
        <input type="email" name="email" value="<?php echo htmlspecialchars($professor_data['email']); ?>" required>

        <label>Department</label>
        <input type="text" name="department" value="<?php echo htmlspecialchars($professor_data['department']); ?>" required>

        <label>University</label>
        <input type="text" name="university" value="<?php echo htmlspecialchars($professor_data['university']); ?>" required>

        <label>Specialization</label>
        <input type="text" name="specialization" value="<?php echo htmlspecialchars($professor_data['specialization']); ?>" required>

        <label>Upload CV</label>
        <input type="file" name="cv" accept=".pdf,.doc,.docx">

        <?php if (!empty($professor_data['cv_path'])): ?>
          <p>Current CV: <a href="<?php echo htmlspecialchars($professor_data['cv_path']); ?>" target="_blank">View</a></p>
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
  toggleBtn.addEventListener("click", () => sidebar.classList.toggle("collapsed"));
</script>
</body>
</html>
