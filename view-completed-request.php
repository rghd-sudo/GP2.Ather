<?php
session_start();
// التأكد من أن المستخدم مسجل الدخول كأستاذ
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'professor') {
    header("Location: login.php");
    exit;
}

// تضمين ملف الاتصال بقاعدة البيانات
include 'index.php'; 

// التحقق من وجود مُعرّف الطلب (ID)
if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("❌ Missing request ID.");
}

$request_id = (int)$_GET['id'];
$request_data = null;
$error_message = null;

// 1. الاستعلام لجلب تفاصيل الطلب وبيانات الطالب (Graduate)
$sql = "
SELECT 
    r.id AS request_id,
    u.name AS graduate_name,
    u.email AS graduate_email,
    r.major,
    r.course,
    r.purpose,
    r.type,
    r.file_name,
    r.created_at,
    r.status
FROM 
    requests r
JOIN 
    users u ON r.user_id = u.id
WHERE
    r.id = {$request_id} 
    AND r.status = 'completed' /* نعرض الطلبات المكتملة فقط */
";

$result = mysqli_query($conn, $sql);

if ($result && mysqli_num_rows($result) > 0) {
    $request_data = mysqli_fetch_assoc($result);
} else {
    $error_message = "⚠️ Request not found or not marked as 'Completed'.";
}

// ... كود CSS بسيط للعرض ...
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>View Completed Request #<?php echo $request_id; ?></title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f7f6; padding: 20px; }
        .container { max-width: 800px; margin: 0 auto; background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        h2 { border-bottom: 2px solid #4a6fa5; padding-bottom: 10px; color: #4a6fa5; }
        .detail-row { display: flex; margin-bottom: 10px; padding: 5px 0; border-bottom: 1px dotted #ccc; }
        .detail-label { font-weight: bold; width: 150px; color: #555; }
        .detail-value { flex-grow: 1; }
        .back-btn { display: inline-block; margin-bottom: 20px; text-decoration: none; color: #4a6fa5; font-weight: bold; }
        .download-btn { background-color: #3498db; color: white; padding: 10px 15px; border: none; border-radius: 5px; cursor: pointer; text-decoration: none; display: inline-block; margin-top: 20px; }
        .error-box { padding: 15px; background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; border-radius: 5px; }
    </style>
</head>
<body>

    <a href="professor_all_request.php" class="back-btn">&#8592; Back to All Requests</a>

    <div class="container">
        <?php if ($error_message): ?>
            <div class="error-box"><?php echo $error_message; ?></div>
        <?php else: ?>
            <h2>عرض تفاصيل الطلب المكتمل رقم #<?php echo $request_data['request_id']; ?></h2>

            <h3>بيانات الطالب</h3>
            <div class="detail-row">
                <span class="detail-label">الاسم:</span>
                <span class="detail-value"><?php echo htmlspecialchars($request_data['graduate_name']); ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">البريد الإلكتروني:</span>
                <span class="detail-value"><?php echo htmlspecialchars($request_data['graduate_email']); ?></span>
            </div>

            <h3>تفاصيل طلب التوصية</h3>
            <div class="detail-row">
                <span class="detail-label">التخصص:</span>
                <span class="detail-value"><?php echo htmlspecialchars($request_data['major']); ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">المساق/المقرر:</span>
                <span class="detail-value"><?php echo htmlspecialchars($request_data['course']); ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">النوع المطلوب:</span>
                <span class="detail-value"><?php echo htmlspecialchars($request_data['type']); ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">الغرض من الطلب:</span>
                <span class="detail-value"><?php echo nl2br(htmlspecialchars($request_data['purpose'])); ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">تاريخ الطلب:</span>
                <span class="detail-value"><?php echo htmlspecialchars($request_data['created_at']); ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">الحالة:</span>
                <span class="detail-value">
                    <strong style="color: green;">
                        <?php echo htmlspecialchars(ucfirst($request_data['status'])); ?>
                    </strong>
                </span>
            </div>

            <?php if (!empty($request_data['file_name'])): ?>
                <a href="download_recommendation.php?id=<?php echo $request_id; ?>" class="download-btn">
                    ⬇️ Download Recommendation File (<?php echo htmlspecialchars($request_data['file_name']); ?>)
                </a>
            <?php else: ?>
                <p style="color: red;">No file name recorded for this completed request.</p>
            <?php endif; ?>

        <?php endif; ?>
    </div>

</body>
</html>