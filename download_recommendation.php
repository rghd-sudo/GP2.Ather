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

        $html = '
<style>
    .header {
        text-align: center;
        font-size: 16px;
        font-weight: bold;
    }
    .sub-header {
        text-align: center;
        font-size: 13px;
        margin-bottom: 10px;
    }
    .content {
        font-size: 14px;
        line-height: 1.8;
        text-align: justify;
    }
    .footer {
        margin-top: 40px;
        font-size: 13px;
        text-align: left;
    }
</style>

<div class="header">
    Recommendation Letter
</div>


<hr>

<div class="content">
    <p>To Whom It May Concern,</p>

    <p>' . nl2br(htmlspecialchars($recommendation_content)) . '</p>

    <p>
        This letter is issued upon the request of the student for academic
        and professional purposes. We wish the student continued success
        in their future academic and career endeavors.
    </p>
</div>

<div class="footer">
    <p>Sincerely,</p>
    <p>
        Graduate Office<br>
       Athar Graduate <br>
      
    </p>
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
