<?php
// الاتصال بقاعدة البيانات
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "agdb";

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    die("فشل الاتصال بقاعدة البيانات: " . $conn->connect_error);
}

// الحصول على معرف الخريج من الرابط (مثلاً RecommendationWriting.php?id=5)
$graduate_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// جلب بيانات الخريج واليوزر
$sql = "SELECT g.*, u.name AS student_name, u.department 
        FROM graduates g 
        JOIN users u ON g.user_id = u.id 
        WHERE g.id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $graduate_id);
$stmt->execute();
$result = $stmt->get_result();
$graduate = $result->fetch_assoc();

// حفظ التوصية عند الإرسال
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $recommendation_text = $_POST['recommendation_text'];
    $type = $_POST['recommendation_type'];
    $professor_id = 1; // مبدئيًا ثابت، لاحقًا تستبدل بـ session الخاصة بالبروفسور

    $insert = $conn->prepare("INSERT INTO recommendations (graduate_id, professor_id, recommendation_text, recommendation_type, created_at) VALUES (?, ?, ?, ?, NOW())");
    $insert->bind_param("iiss", $graduate_id, $professor_id, $recommendation_text, $type);
    $insert->execute();

    echo "<script>alert('تم إرسال التوصية بنجاح!');</script>";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Recommendation Writing</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">
<style>
body {
    margin: 0;
    font-family: "Poppins", sans-serif;
    background: #f9f9f9;
    display: flex;
}

/* Sidebar */
.sidebar {
    background-color: #cde3e8;
    width: 230px;
    height: 100vh;
    position: fixed;
    padding-top: 20px;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
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

.main-content {
    margin-left: 230px;
    padding: 40px;
    width: 100%;
}

/* Info box */
.info-box {
    background: #f1f1f1;
    padding: 20px;
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 10px;
    border-radius: 8px;
    margin-bottom: 20px;
}
.info-item {
    background: #e3e3e3;
    padding: 10px;
    border-radius: 6px;
}
.info-item b {
    color: #003366;
}

/* Editor box */
textarea {
    width: 100%;
    height: 200px;
    margin-top: 10px;
    padding: 10px;
    border: 1px solid #ccc;
    border-radius: 6px;
    resize: none;
}

button {
    margin-top: 15px;
    padding: 10px 20px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
}

.send-btn {
    background-color: #003366;
    color: white;
}
.cancel-btn {
    background-color: #ccc;
}

</style>
</head>
<body>

<!-- Sidebar -->
<div class="sidebar">
    <div>
        <div class="logo" style="text-align:center; margin-bottom:30px;">
            <img src="IMG_1786.PNG" width="80">
        </div>
        <a href="#" class="menu-item"><i class="fas fa-inbox"></i><span>New Requests</span></a>
        <a href="#" class="menu-item"><i class="fas fa-list"></i><span>All Requests</span></a>
        <a href="#" class="menu-item"><i class="fas fa-pen-nib"></i><span>Write Recommendation</span></a>
        <a href="#" class="menu-item"><i class="fas fa-user"></i><span>Profile</span></a>
    </div>
    <div class="bottom-section">
        <a href="#" class="menu-item"><i class="fas fa-gear"></i><span>Notification Settings</span></a>
    </div>
</div>

<!-- Main Content -->
<div class="main-content">
    <h2>Recommendation Writing</h2>

    <?php if ($graduate): ?>
    <div class="info-box">
        <div class="info-item"><b>Student name:</b> <?= htmlspecialchars($graduate['student_name']) ?></div>
        <div class="info-item"><b>ID:</b> <?= htmlspecialchars($graduate['user_id']) ?></div>
        <div class="info-item"><b>Department:</b> <?= htmlspecialchars($graduate['department']) ?></div>
        <div class="info-item"><b>Purpose:</b> <?= htmlspecialchars($graduate['purpose']) ?></div>
        <div class="info-item"><b>Recommendation Type:</b> <?= htmlspecialchars($graduate['recommendation_type']) ?></div>
    </div>

    <form method="POST">
        <label><b>Recommendation Template</b></label>
        <select name="recommendation_type">
            <option value="Academic-Graduate Studies">Academic-Graduate Studies</option>
            <option value="Professional-Internship/Job">Professional-Internship/Job</option>
            <option value="Scholarship/Exchange Programs">Scholarship/Exchange Programs</option>
        </select>

        <textarea name="recommendation_text" placeholder="Write your recommendation here..."></textarea>

        <br>
        <button type="button" class="cancel-btn">Cancel</button>
        <button type="submit" class="send-btn">Send Recommendation</button>
    </form>
    <?php else: ?>
        <p>No graduate found.</p>
    <?php endif; ?>
</div>

</body>
</html>
