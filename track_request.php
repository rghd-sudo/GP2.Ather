<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// الاتصال بقاعدة البيانات
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "agdb";

$conn = new mysqli($host, $user, $pass, $dbname);

// التحقق من الاتصال
if ($conn->connect_error) {
    die("فشل الاتصال: " . $conn->connect_error);
}

// إنشاء جدول تتبع الطلبات إذا ما كان موجود
$conn->query("CREATE TABLE IF NOT EXISTS track_request (
    id INT AUTO_INCREMENT PRIMARY KEY,
    status VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// جلب كل الطلبات من الجدول
$result = $conn->query("SELECT * FROM track_request ORDER BY created_at DESC");
?>

<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <title>Track Request</title>
    <style>
        body {
            margin: 0;
            font-family: 'Arial', sans-serif;
            background-color: #fdfaf6;
            display: flex;
        }

        /* القائمة الجانبية */
        .sidebar {
            width: 200px;
            background-color: #c8e4eb;
            height: 100vh;
            padding-top: 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .sidebar a {
            text-decoration: none;
            color: black;
            margin: 20px 0;
            display: block;
            text-align: center;
        }

        /* المحتوى الرئيسي */
        .content {
            flex: 1;
            padding: 40px;
            text-align: center;
        }

        .timeline {
            display: inline-block;
            position: relative;
            text-align: left;
            margin-top: 50px;
        }

        .step {
            display: flex;
            align-items: center;
            margin: 30px 0;
        }

        .circle {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: #f2b5a7;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 20px;
            color: white;
            margin-right: 15px;
        }

        .yellow { background-color: #f3d37a; }
        .green { background-color: #7adba2; }

        .text {
            font-size: 18px;
            color: #444;
        }

        .back-btn {
            margin-top: 40px;
            background-color: #7adba2;
            border: none;
            padding: 10px 20px;
            border-radius: 10px;
            font-size: 16px;
            cursor: pointer;
        }

        .back-btn:hover {
            background-color: #6ac292;
        }

        h2 {
            color: #333;
        }
    </style>
</head>
<body>

    <div class="sidebar">
        <a href="Student_Profile.php">Profile</a>
        <a href="new_request.php">New Request</a>
        <a href="track_request.php">Track Request</a>
        <a href="notifications.php">Notifications</a>
    </div>

    <div class="content">
        <h2>Track Your Requests</h2>

        <?php if ($result->num_rows > 0): ?>
            <?php while($row = $result->fetch_assoc()): ?>
                <div class="timeline">
                    <div class="step">
                        <div class="circle" style="background-color:#f2b5a7;">📄</div>
                        <div class="text">Create Order</div>
                    </div>
                    <div class="step">
                        <div class="circle yellow">⏳</div>
                        <div class="text">Under Review</div>
                    </div>
                    <div class="step">
                        <div class="circle green">✔️</div>
                        <div class="text">Professor Approval</div>
                    </div>
                    <div class="step">
                        <div class="circle green">✉️</div>
                        <div class="text">Recommendation Sent</div>
                    </div>
                    <p style="color:#888;">الحالة الحالية: <strong><?php echo $row['status']; ?></strong></p>
                    <hr>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p>لا توجد طلبات بعد.</p>
        <?php endif; ?>

        <button class="back-btn" onclick="window.location.href='index.php'">Back to Home</button>
    </div>

</body>
</html>