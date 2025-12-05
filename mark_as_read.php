<?php
// تشغيل الجلسات لقراءة معرّف المستخدم
session_start();

// إعدادات عرض الأخطاء (للتصحيح فقط)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 1. التحقق من أن المستخدم مسجل الدخول وصلاحيته أستاذ
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'professor') {
    // إرجاع رمز خطأ HTTP 403 (Forbidden)
    http_response_code(403);
    echo "Error: Access denied or user not logged in.";
    exit();
}

$user_id = intval($_SESSION['user_id']);

// 2. الاتصال بقاعدة البيانات
// يمكنك تعديل هذا الجزء ليتطابق مع طريقة اتصالك بقاعدة البيانات (db.php أو مباشرة)
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "agdb";
$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    http_response_code(500);
    echo "Error: Database connection failed.";
    exit();
}

// 3. تحديث حالة الإشعارات
// يتم تحديث جميع إشعارات هذا المستخدم (user_id) من 'unread' إلى 'read'
$sql = "UPDATE notifications SET status = 'read' WHERE user_id = ? AND status = 'unread'";

if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("i", $user_id);

    if ($stmt->execute()) {
        // الرد بنجاح (HTTP 200 OK)
        http_response_code(200);
        echo "Successfully marked notifications as read.";
    } else {
        http_response_code(500);
        echo "Error executing statement: " . $stmt->error;
    }

    $stmt->close();
} else {
    http_response_code(500);
    echo "Error preparing statement: " . $conn->error;
}

$conn->close();
?>