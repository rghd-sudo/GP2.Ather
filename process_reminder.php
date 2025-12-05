<?php
// إظهار الأخطاء للمساعدة في التصحيح (يمكن إزالتها في مرحلة الإنتاج)
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
// تأكد من تضمين اتصال قاعدة البيانات (يجب أن يوفر المتغير $conn)
include 'index.php'; 
header('Content-Type: text/plain'); 

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

// 1. استرجاع بيانات الطلب، اسم الطالب، واسم ومعرّف الأستاذ

$sql_fetch = "
SELECT 
    p.user_id AS professor_user_id,
    u_prof.name AS professor_name,
    u_stud.name AS student_name  
FROM requests r
JOIN professors p ON r.professor_id = p.professor_id
JOIN users u_prof ON p.user_id = u_prof.id
JOIN users u_stud ON r.user_id = u_stud.id  -- ✅✅ تم تصحيح student_id إلى user_id هنا
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
$student_name = $row['student_name']; // ✅✅ استخراج اسم الطالب
$stmt_fetch->close();


// 2. إنشاء رسالة الإشعار باللغة الإنجليزية مع اسم الطالب
$notification_message = "REMINDER: You have a pending recommendation request (ID: {$request_id}) from student: **{$student_name}**. Please review it."; // ✅✅ الرسالة الجديدة بالإنجليزية واسم الطالب


// 3. إدراج الإشعار في جدول notifications
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
    echo "Reminder successfully sent internally to Professor: {$professor_name}."; // ✅ رسالة تأكيد بالإنجليزية
} else {
    echo "Error: Failed to record reminder notification. " . $stmt_insert->error;
}

$stmt_insert->close();
$conn->close();

?>