<?php
include 'index.php';
// لتخزين بيانات المستخدم
session_start();

$conn->set_charset("utf8mb4");
$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = $conn->real_escape_string($_POST['email'] ?? '');
    $paasword = $_POST['paasword'] ?? '';

    if ($email && $paasword) {
        $stmt = $conn->prepare("SELECT id, name, paasword, role FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            if (password_verify($paasword, $user['paasword'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['role'] = $user['role']; // حفظ الدور

                // التوجيه حسب الدور
                if ($user['role'] === 'graduate') {
                    header("Location: req_system.php"); // صفحة الخريج
                } elseif ($user['role'] === 'professor') {
                    header("Location: requests.php"); // صفحة الدكتور
                } else {
                    $message = "⚠️ Undefined user role.";
                }
                exit;
            } else {
                $message = "⚠️ The password is incorrect.";
            }
        } else {
            $message = "⚠️ The email does not exist.";
        }
        $stmt->close();
    } else {
        $message = "⚠️ Please enter your email and password.";
    }
}
?>
<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;700&display=swap" rel="stylesheet" href=>
    <link rel="stylesheet" href="tyle.css">
<style>
body{
    font-family:'Poppins',sans-serif;
    background:#fdfaf5;
    margin:0;
    padding:0;
}

/* Background shapes */
.shapes{
    position:absolute;
    top:0;
    left:0;
    width:100%;
    height:100%;
    overflow:hidden;
    z-index:-1;
}
.shape{
    position:absolute;
    width:300px;
    height:300px;
    border-radius:50px;
    transform:rotate(-30deg);
}
.shape1{background:#63b999;top:-100px;left:-70px;}
.shape2{background:#adc0d9;top:50px;left:-50px;}
.shape3{background:#f27360;top:200px;left:-150px;}

/* Card container */
.container{
    max-width:500px;
    margin:80px auto;
    padding:20px;
    position:relative;
}
.card{
    background:#fff;
    border-radius:15px;
    padding:30px;
    box-shadow:0 4px 15px rgba(0,0,0,0.08);
}

/* Form */
h2{
    font-size:2rem;
    text-align:center;
    margin-bottom:25px;
    color:#2c3e50;
}
.input-group{
    margin-bottom:15px;
}
.input-group label{
    display:block;
    margin-bottom:5px;
    color:#555;
    font-weight:600;
}
.input-group input{
    width:100%;
    padding:12px;
    border:none;
    border-radius:10px;
    background:#e0d9d3;
    font-size:16px;
    box-sizing:border-box;
}

button{
    width:100%;
    padding:15px;
    background:#ff7f50;
    color:#fff;
    border:none;
    border-radius:50px;
    font-size:16px;
    font-weight:700;
    cursor:pointer;
    transition:0.3s;
}
button:hover{
    background:#e6603d;
    transform:translateY(-2px);
}

.small-text{
    font-size:12px;
    color:gray;
    text-align:center;
    margin-top:15px;
}

.message{
    margin-top:15px;
    text-align:center;
    color:red;
    font-size:0.9rem;
}

@media(max-width:576px){
    .container{margin:30px 15px;}
}</style>
</head>
<body>
     <div class="shapes">
        <div class="shape shape1"></div>
        <div class="shape shape2"></div>
        <div class="shape shape3"></div>
    </div>
    
<div class="container">
    <div class="card">
        <h2>Login</h2>

        <form action="login.php" method="POST">
            <div class="input-group">
            <label for="email">Email:</label>
            <input type="email" id="email" name="email" required>
            </div>
            <div class="input-group">
            <label for="paasword">Password:</label>
            <input type="password" id="paasword" name="paasword" required>
            </div>

            <button class="btn-primary" type="submit">Login</button>
           <br> <p class="small-text">Don't have an account? <a href="register.php">Register here</a></p>
        </form>

        <?php if(!empty($message)): ?>
            <div class="message"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>
    </div>
</div>

</body>
</html>