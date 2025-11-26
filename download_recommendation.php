<?php
include 'index.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'graduate') {
    die("❌ غير مسموح");
}

$graduate_id = $_SESSION['user_id'];
$rec_id = intval($_GET['id']); // حماية

$stmt = $conn->prepare("
    SELECT pdf_path 
    FROM recommendations 
    WHERE recommendation_id = ? AND graduate_id = ?
");
$stmt->bind_param("ii", $rec_id, $graduate_id);
$stmt->execute();
$res = $stmt->get_result();

if($row = $res->fetch_assoc()){
    $pdf_path = __DIR__ . "/uploads/" . $row['pdf_path']; // استخدام __DIR__ للتأكد من المسار الكامل

    if(file_exists($pdf_path)){
        header('Content-Description: File Transfer');
        header('Content-Type: application/pdf'); // افترضنا PDF
        header('Content-Disposition: attachment; filename="'.basename($pdf_path).'"');
        header('Content-Transfer-Encoding: binary');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($pdf_path));
        ob_clean(); // تنظيف أي بيانات مخزنة
        flush();
        readfile($pdf_path);
        exit;
    } else {
        die("❌ الملف غير موجود: $pdf_path");
    }
}else{
    die("❌ غير مسموح بالتحميل أو التوصية غير موجودة");
}
?>
