<?php
include 'index.php';

$msg = '';
if (isset($_POST['submit'])) {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $select1 = "SELECT * FROM `users` WHERE email='$email' AND password='$password'";
    $select_user = mysqli_query($conn, $select1);
    if (mysqli_num_rows($select_user) > 0) {
        $row1 = mysqli_fetch_assoc($select_user);
        if ($row1['email'] == $email) {
            $_SESSION['user_id'] = $row1['id'];
            header('location:home.php');
        } elseif (password_verify($password, $row1['password'])) {
            $_SESSION['user_id'] = $row1['id'];
            header('location:home.php');
        } else {
            $msg = 'incorrect password!';}
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
           <br> <label for="email">Email:</label><br>
            <input type="email" id="email" name="email" required>

               <label for="password">Password:</label>
            <input type="password" id="password" name="password" required>

            <button class="btn-primary" type="submit">login now</button>
            <p class="small-text ">Don't have an account? <a href="register.php">Register here</a></p>
        </form>
    </div>
</body>
</html>