<?php
include 'index.php';
$users1_id = 1; 
$users1 = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM users1 WHERE id=$users1_id"));
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $dept = $_POST['department'];
    $univ = $_POST['university'];
    $sid = $_POST['student_id'];
    $grad = $_POST['graduation'];

    $cv = $users1['cv'];
    if (!empty($_FILES['cv']['name'])) {
        $cv = "uploads/" . basename($_FILES['cv']['name']);
        move_uploaded_file($_FILES['cv']['tmp_name'], $cv);
    }

    $sql = "UPDATE users1 SET full_name='$name', email='$email', department='$dept', university='$univ',
            student_id='$sid', graduation_year='$grad', cv='$cv' WHERE id=$users1_id";
    mysqli_query($conn, $sql);
    header("Location: Student_Profile.php");
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
      width: 220px;
      background: #cbe2ec;
      min-height: 100vh;
      padding-top: 20px;
      text-align: center;
    }

    .logo img {
      width: 120px;
      margin-bottom: 20px;
    }
    .logo h2 {
      font-size: 14px;
      margin: 0;
      font-weight: bold;
    }
    .logo p {
      font-size: 10px;
      margin: 0;
    }

    .sidebar div.menu-item {
      padding: 15px;
      cursor: pointer;
      text-align: left;
      padding-left: 30px;
    }
    .sidebar div.menu-item:hover {
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
      <h2>ATHER GRADUATE</h2>
      <p>ONE CLICK, ENDLESS POSSIBILITIES</p>
    </div>
    <div class="menu-item">Profile</div>
    <div class="menu-item">New Request</div>
    <div class="menu-item">Track Request</div>
    <div class="menu-item">Notifications</div>
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
        <label>Full name</label>
        <input type="text" id="fullName" value="" disabled>

        <label>Email</label>
        <input type="email" id="email" value="" disabled>

        <label>Department</label>
        <input type="text" id="dept" value="" disabled>

        <label>University</label>
        <input type="text" id="univ" value="" disabled>

        <label>Student ID</label>
        <input type="text" id="studentID" value="" disabled>

        <label>Graduation Year</label>
        <select id="gradYear" disabled>
          <option value="" selected disabled>-- Select year --</option>
          <option>2020</option>
          <option>2026</option>
          <option>2030</option>
        </select>
      </div>

      <div class="actions">
        <button class="save-btn" id="saveBtn" disabled>Save changes</button>
        <button class="reset-btn" id="resetBtn" disabled>Reset</button>
      </div>
    </div>
  </div>

  <script>
    const editBtn = document.getElementById("editBtn");
    const saveBtn = document.getElementById("saveBtn");
    const resetBtn = document.getElementById("resetBtn");
    const inputs = document.querySelectorAll("input, select");

    // البيانات الأصلية تبدأ فارغة
    const originalData = {
      fullName: "",
      email: "",
      dept: "",
      univ: "",
      studentID: "",
      gradYear: ""
    };

    // تفعيل التعديل
    editBtn.onclick = () => {
      inputs.forEach(inp => inp.disabled = false);
      saveBtn.disabled = false;
      resetBtn.disabled = false;
    };

    // حفظ البيانات
    saveBtn.onclick = () => {
      document.getElementById("displayName").textContent = document.getElementById("fullName").value;
      document.getElementById("displayEmail").textContent = document.getElementById("email").value;
      document.getElementById("displayDept").textContent = document.getElementById("dept").value;
      document.getElementById("displayUniv").textContent = document.getElementById("univ").value;
      document.getElementById("displayID").textContent = document.getElementById("studentID").value;

      inputs.forEach(inp => inp.disabled = true);
      saveBtn.disabled = true;
      resetBtn.disabled = true;
    };

    // إعادة التعيين للقيم الفارغة
    resetBtn.onclick = () => {
      document.getElementById("fullName").value = originalData.fullName;
      document.getElementById("email").value = originalData.email;
      document.getElementById("dept").value = originalData.dept;
      document.getElementById("univ").value = originalData.univ;
      document.getElementById("studentID").value = originalData.studentID;
      document.getElementById("gradYear").value = originalData.gradYear;

      document.getElementById("displayName").textContent = "";
      document.getElementById("displayEmail").textContent = "";
      document.getElementById("displayDept").textContent = "";
      document.getElementById("displayUniv").textContent = "";
      document.getElementById("displayID").textContent = "";
    };
  </script>
</body>
