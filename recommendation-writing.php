<?php
// الاتصال بقاعدة البيانات
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "agdb";

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

// الحصول على معرف الخريج من الرابط
$graduate_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// جلب بيانات الخريج والطلب المرتبط به
$sql = "SELECT g.*, u.name, u.email, u.department, u.National_ID,
               r.id AS request_id, r.major, r.purpose, r.type AS recommendation_type, r.status
        FROM graduates g
        JOIN users u ON g.user_id = u.id
        LEFT JOIN requests r ON g.user_id = r.user_id
        WHERE g.graduate_id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $graduate_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $graduate = $result->fetch_assoc();
} else {
    die("Graduate not found.");
}

// جلب مسودة سابقة (إن وجدت)
$recommendation = null;
$rec_query = $conn->prepare("SELECT * FROM recommendations WHERE graduate_id = ? AND professor_id = 1");
$rec_query->bind_param("i", $graduate_id);
$rec_query->execute();
$rec_result = $rec_query->get_result();
if ($rec_result->num_rows > 0) {
    $recommendation = $rec_result->fetch_assoc();
}

$message_alert = ''; // متغير الرسالة

// حفظ التوصية عند الإرسال أو المسودة
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $content = $_POST['recommendation_text'];
    $status = $_POST['action']; // draft أو sent
    $professor_id = 1; // لاحقًا من session
    $request_id = $graduate['request_id'];
    $student_user_id = $graduate['user_id'];

    // تحقق إذا كانت التوصية موجودة مسبقًا
    $check = $conn->prepare("SELECT recommendation_id FROM recommendations WHERE graduate_id = ? AND professor_id = ?");
    $check->bind_param("ii", $graduate_id, $professor_id);
    $check->execute();
    $exists = $check->get_result();

    if ($exists->num_rows > 0) {
        // تحديث التوصية الموجودة
        $update = $conn->prepare("UPDATE recommendations SET content = ?, date_created = NOW(), request_id = ? WHERE graduate_id = ? AND professor_id = ?");
        $update->bind_param("siii", $content, $request_id, $graduate_id, $professor_id);
        $update->execute();
    } else {
        // إنشاء توصية جديدة
        $insert = $conn->prepare("INSERT INTO recommendations (graduate_id, professor_id, content, date_created, request_id) VALUES (?, ?, ?, NOW(), ?)");
        $insert->bind_param("iisi", $graduate_id, $professor_id, $content, $request_id);
        $insert->execute();
    }

    // تحديث حالة الطلب
    if ($status === 'draft') {
        $req_update = $conn->prepare("UPDATE requests SET status = 'draft' WHERE id = ?");
        $req_update->bind_param("i", $request_id);
        $req_update->execute();

        $message_alert = "✅ The recommendation has been saved as a draft.";
    } elseif ($status === 'sent') {
        $req_update = $conn->prepare("UPDATE requests SET status = 'sent' WHERE id = ?");
        $req_update->bind_param("i", $request_id);
        $req_update->execute();

        // إرسال إشعار للطالب
        $message = "Your recommendation has been sent by the professor.";
        $notif = $conn->prepare("INSERT INTO notifications (user_id, message, created_at) VALUES (?, ?, NOW())");
        $notif->bind_param("is", $student_user_id, $message);
        $notif->execute();

        $message_alert = "✅ The recommendation has been sent successfully!";
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

/* Sidebar */
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

.menu-item {
    display: flex;
    align-items: center;
    padding: 12px 20px;
    color: #333;
    text-decoration: none;
    transition: background 0.3s;
}
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
textarea { width: 100%; height: 200px; margin-top: 10px; padding: 10px; border: 1px solid #ccc; border-radius: 6px; resize: none; }
button { margin-top: 15px; padding: 10px 20px; border: none; border-radius: 6px; cursor: pointer; }
.send-btn { background-color: #003366; color: white; margin-right: 10px; }
.draft-btn { background-color: #f39c12; color: white; margin-right: 10px; }
.cancel-btn { background-color: #ccc; }

/* رسالة النجاح */
.alert-message {
    background-color:#d4edda; 
    color:#155724; 
    padding:10px; 
    border-radius:6px; 
    margin-bottom:15px;
}
</style>
</head>
<body>

<div class="sidebar">
    <div>
        <div class="logo" style="text-align:center; margin-bottom:30px;">
            <img src="LOGObl.PNG" width="80">
        </div>
        <a href="requests.php" class="menu-item"><i class="fas fa-home"></i><span class="menu-text">Home</span></a>
        <a href="professor_all_request.php" class="menu-item"><i class="fas fa-list"></i><span>All Requests</span></a>
        <a href="professor-profile.php" class="menu-item"><i class="fas fa-user"></i><span>Profile</span></a>
    </div>
    <div class="bottom-section">
        <a href="setting_D.php" class="menu-item"><i class="fas fa-gear"></i><span>Notification Settings</span></a>
    </div>
</div>

<div class="main-content">
    <h2>Recommendation Writing</h2>

    <?php if ($graduate): ?>
        <?php if ($message_alert): ?>
            <div class="alert-message">
                <?= htmlspecialchars($message_alert) ?>
            </div>
        <?php endif; ?>

        <div class="info-box">
            <div class="info-item"><b>Name:</b> <?= htmlspecialchars($graduate['name']) ?></div>
            <div class="info-item"><b>National ID:</b> <?= htmlspecialchars($graduate['National_ID']) ?></div>
            <div class="info-item"><b>Department:</b> <?= htmlspecialchars($graduate['department']) ?></div>
            <div class="info-item"><b>Graduation Year:</b> <?= htmlspecialchars($graduate['graduation_year']) ?></div>
            <div class="info-item"><b>GPA:</b> <?= htmlspecialchars($graduate['gpa']) ?></div>
            <div class="info-item"><b>Major:</b> <?= htmlspecialchars($graduate['major'] ?? '-') ?></div>
            <div class="info-item"><b>Purpose:</b> <?= htmlspecialchars($graduate['purpose'] ?? '-') ?></div>
            <div class="info-item"><b>Recommendation Type:</b> <?= htmlspecialchars($graduate['recommendation_type'] ?? '-') ?></div>
        </div>

        <form method="POST">
            <textarea name="recommendation_text" placeholder="Write your recommendation here..." required><?= htmlspecialchars($recommendation['content'] ?? '') ?></textarea>
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
