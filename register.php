<?php
session_start();
include 'index.php';

// Display session message if exists
if (isset($_SESSION['message'])) {
    echo "<script>alert('" . $_SESSION['message'] . "');</script>";
    unset($_SESSION['message']);
}

// Process POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $role        = $_POST['role'] ?? '';
    $name        = trim($_POST['name'] ?? '');
    $email       = trim($_POST['email'] ?? '');
    $password    = trim($_POST['password'] ?? '');
    $confirm_password = trim($_POST['confirm_password'] ?? '');
    $national_id = trim($_POST['national_id'] ?? '');
    $university  = trim($_POST['university'] ?? '');
    $department  = trim($_POST['department'] ?? '');
    $specialization = trim($_POST['specialization'] ?? '');
    
    if($role && $name && $email && $password && $confirm_password){
        if($password !== $confirm_password){
            $_SESSION['message'] = "⚠️ Passwords do not match";
            header("Location: register.php");
            exit;
        }

        $hashed = password_hash($password, PASSWORD_BCRYPT);

        // Check if email exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE email=?");
        $stmt->bind_param("s",$email);
        $stmt->execute();
        $stmt->store_result();
        if($stmt->num_rows > 0){
            $_SESSION['message'] = "⚠️ Email already exists";
            header("Location: register.php");
            exit;
        }
        $stmt->close();

        // Insert into users
        $stmt = $conn->prepare("INSERT INTO users (name,email,paasword,department,National_ID,university,role) VALUES (?,?,?,?,?,?,?)");
        $stmt->bind_param("sssssss",$name,$email,$hashed,$department,$national_id,$university,$role);
        if($stmt->execute()){
            $user_id = $stmt->insert_id;

            // Insert into graduates or professors
            if($role=='graduate'){
                $stmt2 = $conn->prepare("INSERT INTO graduates (user_id) VALUES (?)");
                $stmt2->bind_param("i",$user_id);
                $stmt2->execute();
                $stmt2->close();
            } elseif($role=='professor'){
                $stmt2 = $conn->prepare("INSERT INTO professors (user_id,specialization) VALUES (?,?)");
                $stmt2->bind_param("is",$user_id,$specialization);
                $stmt2->execute();
                $stmt2->close();
            }

            $_SESSION['message'] = "✅ Registration successful!";
            header("Location: login.php");
            exit;
        }
        $stmt->close();
    } else {
        $_SESSION['message'] = "⚠️ Please fill all required fields";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>User Registration - Athar Graduate</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;700&display=swap" rel="stylesheet">
<style>
body{font-family:'Poppins',sans-serif;background:#fdfaf5;margin:0;padding:0;}
.container{max-width:500px;margin:50px auto;padding:20px;position:relative;}
.card{background:#fff;border-radius:15px;padding:30px;box-shadow:0 4px 15px rgba(0,0,0,0.08);}
h2{font-size:2rem;text-align:center;margin-bottom:20px;color:#2c3e50;}
.input-group{margin-bottom:15px;}
.input-group label{display:block;margin-bottom:5px;color:#555;font-weight:600;}
.input-group input, .input-group select{width:100%;padding:12px;border:none;border-radius:10px;background:#e0d9d3;font-size:16px;box-sizing:border-box;}
button{width:100%;padding:15px;background:#ff7f50;color:#fff;border:none;border-radius:50px;font-size:16px;font-weight:700;cursor:pointer;transition:0.3s;}
button:hover{background:#e6603d;transform:translateY(-2px);}
.error-msg{color:red;font-size:0.85rem;display:none;margin-top:5px;}
.small-text{font-size:12px;color:gray;text-align:center;margin-top:15px;}
.shape{position:absolute;width:300px;height:300px;border-radius:50px;transform:rotate(-30deg);}
.shape1{background:#63b999;top:-100px;left:-70px;}
.shape2{background:#adc0d9;top:50px;left:-50px;}
.shape3{background:#f27360;top:200px;left:-150px;}
.shapes{position:absolute;top:0;left:0;width:100%;height:100%;overflow:hidden;z-index:-1;}
@media(max-width:576px){.container{margin:20px;}}
</style>
</head>
<body>
<div class="shapes">
    <div class="shape shape1"></div>
    <div class="shape shape2"></div>
    <div class="shape shape3"></div>
</div>

<div class="container">
<div class="card">
<h2>Register</h2>
<form method="POST" action="register.php" id="regForm">
    <div class="input-group">
        <label>Role:</label>
        <select name="role" id="role" required>
            <option value="">-- Select Role --</option>
            <option value="graduate">Graduate</option>
            <option value="professor">Professor</option>
        </select>
        <div class="error-msg" id="roleError">Please select a role</div>
    </div>

    <div class="input-group">
        <label>Full Name:</label>
        <input type="text" name="name" required>
    </div>

    <div class="input-group">
        <label>Email:</label>
        <input type="email" name="email" id="email" required>
        <div class="error-msg" id="emailExists">Email already exists</div>
    </div>

    <div class="input-group">
        <label>Password:</label>
        <input type="password" name="password" id="password" required>
    </div>

    <div class="input-group">
        <label>Confirm Password:</label>
        <input type="password" name="confirm_password" id="confirm_password" required>
        <div class="error-msg" id="confirmError">Passwords do not match</div>
    </div>

    <div class="input-group graduateFields" style="display:none;">
        <label>National ID:</label>
        <input type="text" name="national_id">
        <label>University:</label>
        <input type="text" name="university">
        <label>Department:</label>
        <input type="text" name="department">
    </div>

    <div class="input-group professorFields" style="display:none;">
        <label>Specialization:</label>
        <input type="text" name="specialization">
    </div>

    <button type="submit">Register</button>
    <p class="small-text">Already have an account? <a href="login.php">Login here</a></p>
</form>
</div>
</div>

<script>
const roleSelect = document.getElementById('role');
const gradFields = document.querySelectorAll('.graduateFields');
const profFields = document.querySelectorAll('.professorFields');

roleSelect.addEventListener('change', ()=>{
    if(roleSelect.value==='graduate'){
        gradFields.forEach(f=>f.style.display='block');
        profFields.forEach(f=>f.style.display='none');
    } else if(roleSelect.value==='professor'){
        gradFields.forEach(f=>f.style.display='none');
        profFields.forEach(f=>f.style.display='block');
    } else{
        gradFields.forEach(f=>f.style.display='none');
        profFields.forEach(f=>f.style.display='none');
    }
});

// Password match check
const form = document.getElementById('regForm');
form.addEventListener('submit', e=>{
    const pass = document.getElementById('password').value;
    const confirm = document.getElementById('confirm_password').value;
    if(pass !== confirm){
        document.getElementById('confirmError').style.display='block';
        e.preventDefault();
    } else { document.getElementById('confirmError').style.display='none'; }
});
</script>
</body>
</html>