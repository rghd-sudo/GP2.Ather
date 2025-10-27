<?php
include 'index.php'; // ملف الاتصال بقاعدة البيانات

if (isset($_POST['id'])) {
    $id = intval($_POST['id']);
    $sql = "DELETE FROM requests WHERE id = $id";

    if (mysqli_query($conn, $sql)) {
        echo "success";
    } else {
        // 👇 غيري هذا السطر مؤقتًا عشان نعرف الخطأ الحقيقي
        echo mysqli_error($conn);
    }
} else {
    echo "no_id";
}
?>