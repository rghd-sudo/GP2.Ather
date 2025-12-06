<?php
session_start();
// Check if the user is logged in and has the 'professor' role.
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'professor') {
    header("Location: login.php");
    exit;
}

// Include the database connection file.
include 'index.php'; 

// Validate the presence and type of the Request ID.
if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("❌ Missing request ID.");
}

$request_id = (int)$_GET['id'];
$request_data = null;
$error_message = null;

// 1. Query to fetch request details, graduate data, and the final PDF path.
// Joins 'requests', 'users', and 'recommendations' tables.
$sql = "
SELECT 
    r.id AS request_id,
    u.name AS graduate_name,
    u.email AS graduate_email,
    r.major,
    r.course,
    r.purpose,
    r.type,
    r.file_name AS student_cv,      /* Student CV file (uploaded by student) */
    r.grades_file AS student_grades, /* Student grades file (uploaded by student) */
    r.created_at,
    r.status,
    rec.pdf_path                     /* Final Recommendation PDF path (saved by professor) */
FROM 
    requests r
JOIN 
    users u ON r.user_id = u.id
LEFT JOIN 
    recommendations rec ON r.id = rec.request_id 
WHERE
    r.id = {$request_id} 
    AND r.status = 'completed' /* Only show requests marked as completed */
";

$result = mysqli_query($conn, $sql);

if ($result && mysqli_num_rows($result) > 0) {
    $request_data = mysqli_fetch_assoc($result);
} else {
    $error_message = "⚠️ Request not found or not marked as 'Completed'.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>View Completed Request #<?php echo $request_id; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        /* Basic CSS styling for readability and structure */
        body { font-family: Arial, sans-serif; background-color: #f4f7f6; padding: 20px; }
        .container { max-width: 800px; margin: 0 auto; background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        h2, h3 { border-bottom: 2px solid #4a6fa5; padding-bottom: 5px; color: #4a6fa5; margin-top: 25px; }
        .detail-row { display: flex; margin-bottom: 10px; padding: 5px 0; border-bottom: 1px dotted #ccc; }
        .detail-label { font-weight: bold; width: 180px; color: #555; }
        .detail-value { flex-grow: 1; }
        .back-btn { display: inline-block; margin-bottom: 20px; text-decoration: none; color: #090c11ff; font-weight: bold; }
        .download-btn { background-color:#48f38aff; color:#050101ff ; padding: 12px 20px; border: none; border-radius: 5px; cursor: pointer; text-decoration: none; display: inline-block; margin-top: 20px; font-weight: bold; }
        .error-box { padding: 15px; background-color: #050101ff; color: #721c24; border: 1px solid #f5c6cb; border-radius: 5px; }
    </style>
</head>
<body>

<!-- Back Button -->

    <a href="professor_all_request.php" class="back-btn">&#8592;</a>

    <div class="container">
        <?php if ($error_message): ?>
            <div class="error-box"><?php echo $error_message; ?></div>
        <?php else: ?>
            <h2>Completed Request Details #<?php echo $request_data['request_id']; ?></h2>

            <h3>Student Information</h3>
            <div class="detail-row">
                <span class="detail-label">Name:</span>
                <span class="detail-value"><?php echo htmlspecialchars($request_data['graduate_name']); ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Email:</span>
                <span class="detail-value"><?php echo htmlspecialchars($request_data['graduate_email']); ?></span>
            </div>

            <h3>Recommendation Request Details</h3>
            <div class="detail-row">
                <span class="detail-label">Major:</span>
                <span class="detail-value"><?php echo htmlspecialchars($request_data['major']); ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Course:</span>
                <span class="detail-value"><?php echo htmlspecialchars($request_data['course']); ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Type:</span>
                <span class="detail-value"><?php echo htmlspecialchars($request_data['type']); ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Purpose of Request:</span>
                <span class="detail-value"><?php echo nl2br(htmlspecialchars($request_data['purpose'])); ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Request Date:</span>
                <span class="detail-value"><?php echo htmlspecialchars($request_data['created_at']); ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Status:</span>
                <span class="detail-value">
                    <strong style="color: #06b629ff;">
                        <?php echo htmlspecialchars(ucfirst($request_data['status'])); ?>
                    </strong>
                </span>
            </div>

            <hr style="margin: 20px 0;">

            <h3>Recommendation Files and Attachments</h3>
            <div class="detail-row">
                <span class="detail-label">Student CV:</span>
                <span class="detail-value">
                    <?php if (!empty($request_data['student_cv'])): ?>
                        <a href="uploads/<?php echo htmlspecialchars($request_data['student_cv']); ?>" target="_blank">View CV</a>
                    <?php else: ?>
                        No CV attached.
                    <?php endif; ?>
                </span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Grades File:</span>
                <span class="detail-value">
                    <?php if (!empty($request_data['student_grades'])): ?>
                        <a href="uploads/<?php echo htmlspecialchars($request_data['student_grades']); ?>" target="_blank">View Grades</a>
                    <?php else: ?>
                        No grades file attached.
                    <?php endif; ?>
                </span>
            </div>

            <?php if (!empty($request_data['pdf_path'])): ?>
                <a href="<?php echo htmlspecialchars($request_data['pdf_path']); ?>" download class="download-btn">
                    ⬇ Download  Recommendation 
                </a>
            <?php else: ?>
                <p style="color: red; margin-top: 20px;">❌ Completed recommendation PDF file not found. (Check 'recommendations' table)</p>
            <?php endif; ?>

        <?php endif; ?>
    </div>

</body>
</html>