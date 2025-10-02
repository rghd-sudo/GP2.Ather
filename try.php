<?php
session_start();
include __DIR__ . "/index.php";

// جلب عدد الإشعارات غير المقروءة
$notify_count = 0;
if (isset($_SESSION['user_id'])) {
    $user_id = intval($_SESSION['user_id']);
    $sql = "SELECT COUNT(*) AS total FROM notifications 
            WHERE user_id = $user_id AND is_read = 0";
    $res = $conn->query($sql);
    if ($res) {
        $row = $res->fetch_assoc();
        $notify_count = $row['total'] ?? 0;
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
<style>
.header-bar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    background-color: #FFEFD5; /* خلفية برتقالي فاتح جداً */
    padding: 12px 20px;
    border-radius: 8px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    margin-bottom: 20px;
}
.header-left {
    font-size: 20px;
    font-weight: bold;
    color: #FF8C42;
}
.header-right {
    display: flex;
    align-items: center;
    gap: 15px;
}
.icon-button {
    position: relative;
    background: none;
    border: none;
    cursor: pointer;
    padding: 5px;
}
.icon-button svg {
    width: 22px;
    height: 22px;
}
.icon-logout svg {
    fill: red; /* خروج باللون الأحمر */
}
.icon-notify svg {
    fill: #FF8C42; /* تنبيهات برتقالي */
}
.notify-count {
    position: absolute;
    top: -5px;
    right: -5px;
    background-color: red;
    color: #fff;
    font-size: 11px;
    font-weight: bold;
    padding: 2px 6px;
    border-radius: 50%;
}
</style>
</style>
</head>
<body>

<div class="header-bar">
    <div class="header-left">
        Recommendation System
    </div>
    <div class="header-right">
        <?php if (isset($_SESSION['user_id'])): ?>
            <!-- أيقونة التنبيهات -->
          <a href="../notifications.php" class="icon-button icon-notify">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512">
                    <path d="M224 512c35.3 0 63.1-28.7 
                    63.1-64H160.9c0 35.3 28.7 
                    64 63.1 64zm215.4-149.7c-20.9-21.4-55.5-52.2-55.5-154.3
                    0-77.7-54.5-139.8-127.1-155.2V32c0-17.7-14.3-32-32-32s-32
                    14.3-32 32v20.9C118.5 68.2 64 130.3 64 208c0
                    102.1-34.6 132.9-55.5 154.3-6 6.1-8.5 14.3-8.5
                    22.5 0 16.8 13.2 32 32 32h383.9c18.8 0 32-15.2
                    32-32 0-8.2-2.6-16.4-8.5-22.5z"/>
                </svg>
                <?php if ($notify_count > 0): ?>
                    <span class="notify-count"><?php echo $notify_count; ?></span>
                <?php endif; ?>
            </a>

            <!-- أيقونة تسجيل الخروج -->
            <form method="post" action="../logout.php" style="display:inline;">
                <button type="submit" class="icon-button icon-logout">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512">
                        <path d="M192 128V96c0-17.67 14.33-32 
                        32-32h144c17.67 0 32 14.33 32 
                        32v320c0 17.67-14.33 
                        32-32 32H224c-17.67 0-32-14.33-32-32v-32"/>
                        <polyline points="288 336 368 256 288 176" 
                        fill="none" stroke="red" stroke-width="32" 
                        stroke-linecap="round" stroke-linejoin="round"/>
                        <line x1="368" y1="256" x2="128" y2="256" 
                        fill="none" stroke="red" stroke-width="32" 
                        stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                </button>
            </form>
        <?php endif; ?>
    </div>
</div>
</body>
</html>