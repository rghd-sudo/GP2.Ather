<?php
include 'index.php';
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'graduate') {
  header("Location: login.php");
  exit();
}
$graduate_id = $_SESSION['user_id'];
$rec_id = $_GET['id'];

$stmt = $conn->prepare("SELECT content, file_path FROM recommendations r JOIN requests req ON r.request_id=req.id WHERE r.id=? AND req.user_id=?");
$stmt->bind_param("ii", $rec_id, $graduate_id);
$stmt->execute();
$res = $stmt->get_result();

if($row = $res->fetch_assoc()){
    $file_path = $row['file_path'];
    if(file_exists($file_path)){
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="'.basename($file_path).'"');
        readfile($file_path);
        exit;
    } else {
        echo "❌ الملف غير موجود";
    }
}else{
    echo "❌ غير مسموح بالتحميل";
}
?>