<?php

$host = "localhost";
$user = "root";
$pass = "";
$dbname = "graduate_system";

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
$fullname = $_POST['fullname'];
$email = $_POST['email'];
$dept = $_POST['dept'];
$university = $_POST['university'];

$cvName = null;
if (isset($_FILES['cv']) && $_FILES['cv']['error'] == 0) {
$cvName = time() . "_" . $_FILES['cv']['name'];
move_uploaded_file($_FILES['cv']['tmp_name'], "uploads/" . $cvName);
}

$stmt = $conn->prepare("INSERT INTO doctors (fullname, email, dept, university, cv) VALUES (?, ?, ?, ?, ?)");
$stmt->bind_param("sssss", $fullname, $email, $dept, $university, $cvName);
if ($stmt->execute()) {
$message = "تم حفظ البيانات بنجاح!";
} else {
$message = "حدث خطأ أثناء حفظ البيانات.";
}
$stmt->close();
}

$conn->close();
?>


<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Profile Page</title>

<style>
body {
margin: 0;
font-family: Arial, sans-serif;
background-color: #f7f7f7;
}


.sidebar {
position: fixed;
top: 0;
left: 0;
width: 200px;
height: 100%;
background-color: #cfe0e9;
padding-top: 20px;
}

.sidebar img {
display: block;
margin: 0 auto 20px auto;
width: 120px;
}

.sidebar a {
display: block;
padding: 15px;
color: #000;
text-decoration: none;
font-size: 16px;
}

.sidebar a:hover {
background-color: #b5cbd7;
border-radius: 5px;
}


.content {
margin-left: 220px;
padding: 20px;
}

.profile-card {
background: #fff;
padding: 20px;
border-radius: 8px;
box-shadow: 0 0 10px rgba(0,0,0,0.1);
max-width: 600px;
}

.profile-header {
display: flex;
align-items: center;
margin-bottom: 20px;
}

.profile-header img {
width: 70px;
height: 70px;
border-radius: 50%;
background-color: #3cb371;
margin-right: 15px;
}

.profile-header h2 {
margin: 0;
}

.profile-header p {
margin: 3px 0;
color: #555;
}

.edit-btn {
margin-left: auto;
background: #3cb371;
color: #fff;
border: none;
padding: 8px 15px;
border-radius: 20px;
cursor: pointer;
}

.form-group {
margin-bottom: 15px;
}

.form-group label {
display: block;
margin-bottom: 5px;
color: #333;
}

.form-group input {
width: 100%;
padding: 8px;
border: 1px solid #ccc;
border-radius: 5px;
}

.buttons {
margin-top: 20px;
display: flex;
gap: 10px;
}

.save-btn {
background: #5b8df7;
color: white;
border: none;
padding: 10px 20px;
border-radius: 5px;
cursor: pointer;
}

.reset-btn {
background: #eee;
border: none;
padding: 10px 20px;
border-radius: 5px;
cursor: pointer;
}

.upload-cv {
display: flex;
align-items: center;
gap: 10px;
}
</style>
</head>
<body>



<div class="sidebar">
<img src="logo.png" alt="Logo">
<a href="#">Profile</a>
<a href="#">Requests</a>
<a href="#">Recommendations</a>
<a href="#"> <?php echo $professorName; ?></a>
</div>



<div class="content">
<div class="profile-card">
<div class="profile-header">
<img src="user-icon.png" alt="User">
<div>
<h2><?php echo $professorName; ?></h2>
<p><?php echo $professorEmail; ?></p>
<p><?php echo $professorDept . " - " . $professorUni; ?></p>
</div>
<button class="edit-btn">Edit</button>
</div>

<form>
<div class="form-group">
<label for="fullname">Full name</label>
<input type="text" id="fullname" name="fullname" placeholder="Full name">
</div>

<div class="form-group">
<label for="email">Email</label>
<input type="email" id="email" name="email" placeholder="Email">
</div>

<div class="form-group">
<label for="dept">Department</label>
<input type="text" id="dept" name="dept" placeholder="Department">
</div>

<div class="form-group">
<label for="university">University</label>
<input type="text" id="university" name="university" placeholder="University">
</div>

<div class="form-group upload-cv">
<label for="cv">Upload CV:</label>
<input type="file" id="cv" name="cv">
</div>

<div class="buttons">
<button type="submit" class="save-btn">Save changes</button>
<button type="reset" class="reset-btn">Reset</button>
</div>
</form>
</div>
</div>

</body>
</html>