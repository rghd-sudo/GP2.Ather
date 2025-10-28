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

// الحصول على معرف الخريج من الرابط
$graduate_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// جلب بيانات الخريج
$sql = "SELECT g.*, u.name, u.email, u.department , u.National_ID,
               r.subject, r.purpose
        FROM graduates g
        JOIN users u ON g.user_id = u.id
        LEFT JOIN recommendations r ON g.graduate_id = r.graduate_id
        WHERE g.graduate_id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $graduate_id);
$stmt->execute();
$result = $stmt->get_result();
$graduate = $result->fetch_assoc();

// حفظ التوصية عند الإرسال
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $content = $_POST['recommendation_text'];
    $status = $_POST['action']; // "draft" أو "sent"
    $professor_id = 1; // لاحقًا من session
    $subject = $graduate['subject'] ?? '';
    $purpose = $graduate['purpose'] ?? '';
    $type = $_POST['recommendation_type']; // من الـ dropdown

    $insert = $conn->prepare("INSERT INTO recommendations (graduate_id, professor_id, content, recommendation_type, subject, purpose, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
    $insert->bind_param("iisssss", $graduate_id, $professor_id, $content, $type, $subject, $purpose, $status);
    $insert->execute();

    if($status === 'sent'){
        echo "<script>alert('The Recommendation has been sent successfully!');</script>";
    } else {
        echo "<script>alert('The Recommendation has been saved as a draft!');</script>";
    }
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
    background: #fdfaf6;
    display: flex;
}

.sidebar {
  background-color: #c8e4eb;
  width: 230px;
  height: 100vh;
  position: fixed;
  padding-top: 20px;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
}

.menu-item { display: flex; align-items: center; padding: 12px 20px; color: #333; text-decoration: none; transition: background 0.3s; }
.menu-item:hover { background: #bcd5db; }
.menu-item i { font-size: 20px; margin-right: 10px; width: 25px; text-align: center; }

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
.info-item { background: #e3e3e3; padding: 10px; border-radius: 6px; }
.info-item b { color: #003366; }

/* Form */
select, textarea { width: 100%; margin-top: 10px; padding: 10px; border: 1px solid #ccc; border-radius: 6px; box-sizing: border-box; }
textarea { height: 200px; resize: none; }
button { margin-top: 15px; padding: 10px 20px; border: none; border-radius: 6px; cursor: pointer; }
.send-btn { background-color: #003366; color: white; margin-right: 10px; }
.draft-btn { background-color: #f39c12; color: white; margin-right: 10px; }
.cancel-btn { background-color: #ccc; }
</style>
</head>
<body>

<div class="sidebar">
    <div>
        <div class="logo" style="text-align:center; margin-bottom:30px;">
            <img src="LOGObl.PNG" width="80">
        </div>
        <a href="requests.php" class="menu-item"><i class="fas fa-list"></i><span>All Requests</span></a>
        <a href="recommendation-Writing.php" class="menu-item"><i class="fas fa-pen-nib"></i><span>Write Recommendation</span></a>
        <a href="professor-profile.php" class="menu-item"><i class="fas fa-user"></i><span>Profile</span></a>
    </div>
    <div class="bottom-section">
        <a href="setting_D.php" class="menu-item"><i class="fas fa-gear"></i><span>Notification Settings</span></a>
    </div>
</div>

<div class="main-content">
    <h2>Recommendation Writing</h2>

<?php if ($graduate): ?>
    <div class="info-box">
        <div class="info-item"><b>Name:</b> <?= htmlspecialchars($graduate['name']) ?></div>
        <div class="info-item"><b>National ID:</b> <?= htmlspecialchars($graduate['National_ID']) ?></div>
        <div class="info-item"><b>Department:</b> <?= htmlspecialchars($graduate['department']) ?></div>
        <div class="info-item"><b>Graduation Year:</b> <?= htmlspecialchars($graduate['graduation_year']) ?></div>
        <div class="info-item"><b>GPA:</b> <?= htmlspecialchars($graduate['gpa']) ?></div>
        <div class="info-item"><b>Subject:</b> <?= htmlspecialchars($graduate['subject'] ?? '-') ?></div>
        <div class="info-item"><b>Purpose:</b> <?= htmlspecialchars($graduate['purpose'] ?? '-') ?></div>
    </div>

    
    <label><b>Recommenation Type </b></label>
    <select name="recommendation_type" required>
        <option value="Academic-Graduate Studies">Academic - Graduate Studies</option>
        <option value="Professional-Internship/Job">Professional - Internship / Job</option>
        <option value="Scholarship/Exchange Programs">Scholarship / Exchange Programs</option>
    </select>

    
    <form method="POST">
        <textarea name="recommendation_text" placeholder="Write your recommendation here..." required></textarea>
        <br>
        <button type="button" class="cancel-btn" onclick="history.back()">Cancel</button>
        <button type="submit" name="action" value="draft" class="draft-btn">Save Draft</button>
        <button type="submit" name="action" value="sent" class="send-btn">Send Recommendation</button>
    </form>

<?php else: ?>
    <p>No graduate found.</p>
<?php endif; ?>

</div>
</body>
</html>
