<?php
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

// إنشاء جدول التنبيهات إذا ما كان موجود
$conn->query("CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    message VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// جلب التنبيهات
$result = $conn->query("SELECT * FROM notifications ORDER BY created_at DESC");
?>
<?php while($row = $result->fetch_assoc()): ?>
    <div class="notification">
        <p><?= $row['message'] ?></p>
        <p class="time"><?= $row['created_at'] ?></p>
    </div>
<?php endwhile; ?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Notifications</title>
    <style>
        body {
            margin: 0;
            font-family: Arial, sans-serif;
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

        /* المحتوى */
        .content {
            flex: 1;
            background-color: #fdfcf9;
            padding: 30px;
        }

        .content h2 {
            display: flex;
            align-items: center;
            font-size: 24px;
        }

        .content h2 img {
            margin-right: 10px;
        }

        .notification {
            background: #fff;
            padding: 15px;
            margin: 10px 0;
            border-radius: 8px;
            display: flex;
            align-items: center;
            box-shadow: 0px 2px 4px rgba(0,0,0,0.1);
        }

        .notification-icon {
            font-size: 20px;
            margin-right: 15px;
        }

        .time {
            font-size: 12px;
            color: gray;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <!-- القائمة الجانبية -->
    <div class="sidebar">
        <a href="#">Profile</a>
        <a href="#">New Request</a>
        <a href="#">Track Request</a>
        <a href="#">Notifications</a>
    </div>

    <!-- المحتوى -->
    <div class="content">
        <h2>🔔 Notifications</h2>

        <?php if ($result->num_rows > 0): ?>
            <?php while($row = $result->fetch_assoc()): ?>
                <div class="notification">
                    <div class="notification-icon">✅</div>
                    <div>
                        <div><?php echo $row['message']; ?></div>
                        <div class="time"><?php echo $row['created_at']; ?></div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p>No notifications yet.</p>
        <?php endif; ?>
    </div>
</body>
</html>