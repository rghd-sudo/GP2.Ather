<?php
function sendNotify($user_id, $message, $type)
{
include 'index.php';
// 1) جلب إعدادات المستخدم
$sql = "SELECT * FROM notification_settings WHERE user_id='$user_id'";
$res = mysqli_query($conn, $sql);
$settings = mysqli_fetch_assoc($res);
// 2) تحقق من إذونات الإشعار - (الشرط الموحد)
$permission_field = 'notify_' . $type;
// تأكد من وجود إعدادات قبل التحقق
if ($settings && array_key_exists($permission_field, $settings)) {
// الشرط: إذا كان الإشعار معطلاً (قيمته 0)، توقف عن الإرسال
// نستخدم != 1 لضمان أن 0 هو المعطل، وهذا يغطي جميع أنواع الإشعارات
if ($settings[$permission_field] != 1) {
return; // ممنوع — لا نرسل الإشعار (لأي نوع معطل)
}
}
// 3) حفظ الإشعار بقاعدة البيانات
$stmt = $conn->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)");
$stmt->bind_param("is", $user_id, $message);
$stmt->execute();
}