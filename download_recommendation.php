<?php
session_start();
include 'index.php'; // ملف الاتصال بقاعدة البيانات

if (!isset($_SESSION['user_id'])) {
    die("❌ Please log in first.");
}

$user_id = $_SESSION['user_id'];

// التحقق من رقم الطلب
if (!isset($_GET['request_id']) || !is_numeric($_GET['request_id'])) {
    die("❌ Invalid request ID.");
}

$request_id = intval($_GET['request_id']);

// جلب بيانات التوصية + حالة الطلب
$sql_fetch_recommendation = "
    SELECT 
        r.content,
        req.status
    FROM recommendations r
    JOIN requests req ON r.request_id = req.id
    WHERE r.request_id = ? AND req.user_id = ?
";

$stmt = $conn->prepare($sql_fetch_recommendation);
if (!$stmt) die("Prepare failed: " . $conn->error);

$stmt->bind_param("ii", $request_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows > 0) {
    $data = $result->fetch_assoc();

    if (strtolower($data['status']) === 'accepted' || strtolower($data['status']) === 'completed') {
        $recommendation_content = $data['content'];

        // ========================
        // توليد PDF باستخدام TCPDF
        // ========================
        require_once('tcpdf/tcpdf.php'); // عدلي المسار إذا مختلف

        $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
        $pdf->SetCreator('University of Baha');
        $pdf->SetAuthor('University of Baha');
        $pdf->SetTitle('Recommendation Letter');
        $pdf->SetMargins(15, 20, 15);
        $pdf->SetAutoPageBreak(true, 15);

        // استخدم خط DejaVu Sans (يدعم الإنجليزية جيدًا)
        $pdf->SetFont('times', '', 14);  // رسمي وأنيق


        $pdf->AddPage();

        // محتوى PDF بالإنجليزية
        $html = '
        <table width="100%">
        <tr>
        <td width="30%"><img src="uniba_logo.png" width="100"></td>
        <td width="70%" style="text-align:center;">
        <h1>Recommendation Letter</h1>
        <p>University of Baha</p>
        </td>
        </tr>
        </table>
        <hr>
        <div style="line-height:1.8; font-size:14px; text-align:left;">
        <p>Dear Sir/Madam,</p>
        <p>' . nl2br($recommendation_content) . '</p>
        <p>We wish the student continued success in their academic journey.</p>
        </div>
        <br><br>
        <div style="text-align:left;">
        <p>Prepared by: Academic Office</p>
        <p>Date: ' . date("d/m/Y") . '</p>
        </div>
        ';

        $pdf->writeHTML($html, true, false, true, false, '');
        $pdf->Output('recommendation_' . $request_id . '.pdf', 'D'); // تحميل PDF
        exit;

    } else {
        die("⚠️ The request is in status " . htmlspecialchars($data['status']) . ". The recommendation cannot be downloaded yet.");
    }

} else {
    die("❌ No recommendation available for this request or the request does not exist.");
}

$stmt->close();
$conn->close();
?>
