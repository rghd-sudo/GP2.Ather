<?php
include 'index.php';
$conn->query("CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(255),
    email VARCHAR(100),
    password VARCHAR(255),
    National_ID VARCHAR(50),
    department VARCHAR(100),
    university VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)");
");
$message = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $conn->real_escape_string($_POST['name'] ?? '');
    $email = $conn->real_escape_string($_POST['email'] ?? '');
    $password = $conn->real_escape_string($_POST['password'] ?? '');
    $confirm_password = $conn->real_escape_string($_POST['confirm_password'] ?? '');
    $National_ID = $conn->real_escape_string($_POST['National_ID'] ?? '');
    $department = $conn->real_escape_string($_POST['department'] ?? '');
    $university = $conn->real_escape_string($_POST['university'] ?? '');
    $type = $conn->real_escape_string($_POST['type'] ?? '');
    $file_name = NULL;

$msg='';
if(isset($_POST['submit'])){
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $National_ID = $_POST['National_ID'];
    $department = $_POST['department'];
    $university = $_POST['university'];
    $graduation = $_POST['graduation'];
    $cv = $_FILES['cv']['name'];
    $cv_tmp = $_FILES['cv']['tmp_name'];
    move_uploaded_file($cv_tmp, "uploads/$cv");
    $select1 = "SELECT * FROM `users` WHERE email='$email'";
    $select_user = mysqli_query($conn, $select1);
    if(mysqli_num_rows($select_user) > 0){
        $msg = 'user already exist!';
    }else{
        $insert1="INSERT INTO `users`(full_name, email, password, National_ID, department, university, graduation_year, cv) VALUES('$name','$email','$password','$National_ID','$department','$university','$graduation','$cv')";
        mysqli_query($conn, $insert1);
        header('location:login.php');
}
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="login-container">
        <h2>Register</h2>
        <form action="register.php" method="POST">
           <p class="msg"><?php echo $msg; ?></p>
            <label for="name">Full Name:</label>
            <input  type="text" id="name" name="name" required>

           <label for="email">Email:</label>
            <input type="email" id="email" name="email" required>

                <label for="password">Password:</label>
            <input type="password" id="password" name="password" required>

             <label for="confirm_password">Confirm Password:</label>
            <input type="password" id="confirm_password" name="confirm_password" required>

               <label for="National_ID">National ID:</label>
            <input type="text" id="National_ID" name="National_ID" required>

             <label for="department">Department:</label>
            <input type="text" id="department" name="department" required>

           
            <label for="university">University:</label>
            <input type="text" id="university" name="university" required>
          
            <label for="graduation">Graduation Year:</label>
            <input type="text" id="graduation" name="graduation" required>

           <label for="cv">Upload CV:</label>
            <input type="file" id="cv" name="cv" accept=".pdf,.doc,.docx">

            <button class="btn-primary" type="submit">Register</button>
            <p class="small-text">Already have an account? <a href="login.php">Login here</a></p>
        </form>
    </div>
</body>
</html>

