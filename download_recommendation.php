<?php
session_start();
include 'index.php';

if (!isset($_SESSION['user_id'])) {
    die("❌ Please login first.");
}

$user_id = $_SESSION['user_id'];

/* تحقق من request_id */
if (!isset($_GET['request_id']) || !is_numeric($_GET['request_id'])) {
    die("❌ Invalid request ID.");
}

$request_id = intval($_GET['request_id']);

/* الاستعلام */
$sql = "
SELECT r.pdf_path
FROM recommendations r
JOIN requests req ON r.request_id = req.id
WHERE r.request_id = ?
AND req.user_id = ?
AND LOWER(req.status) = 'completed'
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $request_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {

    $relative_path = $row['pdf_path']; // Word file
    $full_path = __DIR__ . '/' . $relative_path;

    if (!empty($relative_path) && file_exists($full_path)) {

        $ext = strtolower(pathinfo($full_path, PATHINFO_EXTENSION));

        // تحديد نوع الملف
        if ($ext === 'pdf') {
    $contentType = 'application/pdf';
} elseif ($ext === 'docx') {
    $contentType = 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';
} elseif ($ext === 'doc') {
    $contentType = 'application/msword';
} else {
    die("❌ Unsupported file type.");
}


        header('Content-Type: ' . $contentType);
        header('Content-Disposition: attachment; filename="recommendation.' . $ext . '"');
        header('Content-Length: ' . filesize($full_path));

        readfile($full_path);
        exit;

    } else {
        die("❌ Recommendation file not found.");
    }

} else {
    die("⚠️ Recommendation not available yet.");
}

$stmt->close();
$conn->close();
?>