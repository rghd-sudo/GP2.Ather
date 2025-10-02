<?php
session_start();
include 'index.php';
$student_id = 1; 
//$student = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM Graduates WHERE id=$student_id"));
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $dept = $_POST['department'];
    $univ = $_POST['university'];
    $sid = $_POST['student_id'];
    $grad = $_POST['graduation'];

    $cv = $student['cv'];
    if (!empty($_FILES['cv']['name'])) {
        $cv = "uploads/" . basename($_FILES['cv']['name']);
        move_uploaded_file($_FILES['cv']['tmp_name'], $cv);
    }

    $sql = "UPDATE Graduates 
            SET full_name='$name', email='$email', department='$dept', university='$univ',
                student_id='$sid', graduation_year='$grad', cv='$cv' 
            WHERE id=$student_id";
    mysqli_query($conn, $sql);
    header("Location: profile.php");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Student Profile</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      background: #fafafa;
      margin: 0;
      display: flex;
    }

    /* ---------- القائمة الجانبية ---------- */
    .sidebar {
      width: 80px;
      background: #cbe2ec;
      min-height: 100vh;
      padding-top: 20px;
      text-align: center;
    }

    .logo img {
      width: 50px;
      margin-bottom: 30px;
      border-radius: 50%;
    }

    .menu-item {
      padding: 20px 0;
      cursor: pointer;
    }

    .menu-item img {
      width: 30px;
      height: 30px;
    }

    .menu-item:hover {
      background: #b2d4e6;
    }

    /* ---------- البروفايل ---------- */
    .profile-container {
      flex: 1;
      padding: 30px;
    }

    .profile-card {
      background: #f0f0f0;
      border-radius: 10px;
      padding: 20px;
      width: 500px;
      box-shadow: 0 2px 6px rgba(0,0,0,0.1);
    }

    .profile-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
    }

    .profile-info {
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .profile-info img {
      width: 50px;
      height: 50px;
      border-radius: 50%;
      background: #0055cc;
    }

    .profile-details {
      line-height: 1.4;
    }

    .btn-edit {
      background: #0023ff;
      color: #fff;
      border: none;
      padding: 6px 14px;
      border-radius: 6px;
      cursor: pointer;
    }

    .form-section {
      margin-top: 20px;
    }

    .form-section label {
      display: block;
      margin: 8px 0 4px;
    }

    .form-section input, 
    .form-section select {
      width: 100%;
      padding: 8px;
      border: 1px solid #ccc;
      border-radius: 6px;
    }

    .actions {
      margin-top: 20px;
      display: flex;
      gap: 10px;
    }

    .actions button {
      flex: 1;
      padding: 10px;
      border-radius: 6px;
      border: none;
      cursor: pointer;
    }

    .save-btn {
      background: #5a8dee;
      color: white;
    }

    .reset-btn {
      background: #ddd;
    }
  </style>
</head>
<body>
  <!-- ---------- Sidebar ---------- -->
  <div class="sidebar">
    <div class="logo">
      <img src="" alt="Logo">
    </div>
    <div class="menu-item" onclick="window.location.href='profile.php'">
      <img src="icons/user.png" alt="Profile">
    </div>
    <div class="menu-item" onclick="window.location.href='new_request.php'">
      <img src="icons/add.png" alt="New Request">
    </div>
    <div class="menu-item" onclick="window.location.href='track_request.php'">
      <img src="icons/track.png" alt="Track">
    </div>
    <div class="menu-item" onclick="window.location.href='notifications.php'">
      <img src="icons/bell.png" alt="Notifications">
    </div>
  </div>

  <!-- ---------- Profile ---------- -->
  <div class="profile-container">
    <div class="profile-card">
      <div class="profile-header">
        <div class="profile-info">
          <img src="" alt="avatar">
          <div class="profile-details">
            <strong id="displayName"></strong><br>
            <span id="displayEmail"></span><br>
            <span id="displayDept"></span><br>
            <span id="displayUniv"></span><br>
            <span id="displayID"></span>
          </div>
        </div>
        <button class="btn-edit" id="editBtn">Edit</button>
      </div>

      <div class="form-section">
        <form method="POST" enctype="multipart/form-data">
          <label>Full name</label>
          <input type="text" name="name" id="fullName" value="<?= $student['full_name'] ?>">

          <label>Email</label>
          <input type="email" name="email" id="email" value="<?= $student['email'] ?>">

          <label>Department</label>
          <input type="text" name="department" id="dept" value="<?= $student['department'] ?>">

          <label>University</label>
          <input type="text" name="university" id="univ" value="<?= $student['university'] ?>">

          <label>Student ID</label>
          <input type="text" name="student_id" id="studentID" value="<?= $student['student_id'] ?>">

          <label>Graduation Year</label>
          <select name="graduation" id="gradYear">
            <option value="" disabled>-- Select year --</option>
            <option <?= $student['graduation_year']=="2020"?"selected":"" ?>>2020</option>
            <option <?= $student['graduation_year']=="2026"?"selected":"" ?>>2026</option>
            <option <?= $student['graduation_year']=="2030"?"selected":"" ?>>2030</option>
          </select>

          <label>Upload CV</label>
          <input type="file" name="cv">

          <div class="actions">
            <button type="submit" class="save-btn">Save changes</button>
            <button type="reset" class="reset-btn">Reset</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</body>
</html>