<?php
include 'index.php';

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

