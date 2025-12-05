<?php
// إظهار الأخطاء للمساعدة في التصحيح (يمكن إزالتها في مرحلة الإنتاج)
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
// تأكد من تضمين اتصال قاعدة البيانات (يجب أن يوفر المتغير $conn)
include 'index.php'; 
header('Content-Type: text/plain'); // للتعامل مع الرد كنص عادي في JavaScript

// التحقق من أن المستخدم مسجل الدخول
if (!isset($_SESSION['user_id'])) {
    echo "Error: User not logged in.";
    exit();
}

// التحقق من استقبال رقم الطلب
if (!isset($_POST['request_id']) || empty($_POST['request_id'])) {
    echo "Error: Request ID not provided.";
    exit();
}

$request_id = (int)$_POST['request_id'];

// 1. استرجاع بيانات الطلب واسم الأستاذ ومعرّفه
$sql_fetch = "
SELECT 
    r.professor_id,
    u.id AS professor_user_id,
    u.name AS professor_name
FROM requests r
JOIN professors p ON r.professor_id = p.professor_id
JOIN users u ON p.user_id = u.id
WHERE r.id = ?
";

$stmt_fetch = $conn->prepare($sql_fetch);
if (!$stmt_fetch) {
    echo "Error preparing statement (fetch): " . $conn->error;
    exit();
}
$stmt_fetch->bind_param("i", $request_id);
$stmt_fetch->execute();
$result_fetch = $stmt_fetch->get_result();

if ($result_fetch->num_rows === 0) {
    echo "Error: Request not found or professor not linked.";
    $stmt_fetch->close();
    exit();
}

$row = $result_fetch->fetch_assoc();
$professor_user_id = $row['professor_user_id'];
$professor_name = $row['professor_name'];
$stmt_fetch->close();


// 2. إنشاء رسالة الإشعار
$notification_message = "تذكير: لديك طلب توصية معلّق (رقم: {$request_id}) من طالب، يرجى مراجعته.";


// 3. إدراج الإشعار في جدول notifications
// ملاحظة: يتم تعيين status كـ 'unread' تلقائياً
$sql_insert = "
INSERT INTO notifications (user_id, message, status, created_at)
VALUES (?, ?, 'unread', NOW())
";

$stmt_insert = $conn->prepare($sql_insert);
if (!$stmt_insert) {
    echo "Error preparing statement (insert): " . $conn->error;
    exit();
}
// ربط user_id الأستاذ بالرسالة
$stmt_insert->bind_param("is", $professor_user_id, $notification_message);

if ($stmt_insert->execute()) {
    echo "تم إرسال التذكير بنجاح كإشعار داخلي للأستاذ الدكتور: {$professor_name}";
} else {
    echo "Error: Failed to record reminder notification. " . $stmt_insert->error;
}

$stmt_insert->close();
$conn->close();

?>