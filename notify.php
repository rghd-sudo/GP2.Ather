<?php
function sendNotify($user_id, $message, $type)
{
    include 'index.php';

    // 1) جلب إعدادات المستخدم
    $sql = "SELECT * FROM notification_settings WHERE user_id='$user_id'";
    $res = mysqli_query($conn, $sql);
    $settings = mysqli_fetch_assoc($res);

    // 2) تحقق من إذونات الإشعار
    if (
        ($type == 'new_request' && !$settings['notify_new_request']) ||
        ($type == 'pending' && !$settings['notify_pending']) ||
        ($type == 'rejected' && !$settings['notify_rejected']) ||
        ($type == 'uploaded' && !$settings['notify_uploaded'])
    ) {
        return; // ممنوع — لا نرسل الإشعار
    }

    // 3) حفظ الإشعار بقاعدة البيانات
    $stmt = $conn->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)");
    $stmt->bind_param("is", $user_id, $message);
    $stmt->execute();
}