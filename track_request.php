<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (file_exists(_DIR_ . '/db.php')) {
    require_once _DIR_ . '/db.php'; // يُفترض أن هذا الملف يُعرّف $conn (mysqli)
} else {
    // بديل: إنشاء اتصال هنا 
    $host = "localhost";
    $user = "root";
    $pass = "";
    $dbname = "agdb";
    $conn = new mysqli($host, $user, $pass, $dbname);
    if ($conn->connect_error) {
        die("فشل الاتصال: " . $conn->connect_error);
    }
}

/* ------------------ 2) تأكد تسجيل الدخول ------------------ */
if (!isset($_SESSION['user_id'])) {
    
    die('الرجاء تسجيل الدخول أولاً.');
}
$user_id = intval($_SESSION['user_id']);

$create_sql = "
CREATE TABLE IF NOT EXISTS track_request (
  id INT AUTO_INCREMENT PRIMARY KEY,
  request_id INT NOT NULL,
  user_id INT NOT NULL,
  status VARCHAR(100) NOT NULL,
  note VARCHAR(255) DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX (request_id),
  INDEX (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";
$conn->query($create_sql); // نتجاهل الأخطاء هنا لأن الجدول قد يكون موجودًا ببنية مختلفة

/* ------------------ 4) جلب طلبات المستخدم الحالي ------------------ */
/* نتوقع وجود جدول requests مع عمود id و user_id و (اختياريًا title أو created_at) */
$requests = [];
if ($stmt = $conn->prepare("SELECT id, COALESCE(title, '') AS title, status AS current_status, created_at FROM requests WHERE user_id = ? ORDER BY created_at DESC")) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $requests = $res->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} else {
    // لو استعلام requests غير موجود في DB، نعرض رسالة مصغرة (لاحقًا يمكنك إضافة ملف إنشاء جدول requests)
    // echo "تحذير: لا يوجد جدول requests أو الاستعلام غير صالح.";
}

/* ------------------ 5) دالة مساعدة لعرض التاريخ بصيغة جميلة ------------------ */
function fmt_datetime($dt) {
    // ترجع التاريخ والوقت كما هما؛ ممكن تعديلها لعرض "منذ X دقائق"
    return htmlspecialchars($dt, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <title>Track Request</title>

    <!-- الخطوط والأيقونات -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://netdna.bootstrapcdn.com/font-awesome/4.0.3/css/font-awesome.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">

    <style>
    /* 🔹 General Layout */
    body {
      margin: 0;
      font-family: "Poppins", sans-serif;
      background: #fdfaf6;
      display: flex;
      direction: rtl;
    }

    /* 🔹 Sidebar */
    .sidebar {
      background-color: #c8e4eb;
      width: 230px;
      transition: width 0.3s;
      height: 100vh;
      padding-top: 20px;
      box-shadow: 2px 0 5px rgba(0,0,0,0.1);
      position: fixed;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      right: 0; /* لأن اللغة عربية، نثبت الشريط على اليمين */
    }
    .sidebar.collapsed { width: 70px; }
    .sidebar .logo { text-align: center; margin-bottom: 30px; }
    .sidebar .logo img { width: 80px; }
    .menu-item {
      display: flex;
      align-items: center;
      padding: 12px 20px;
      color: #333;
      text-decoration: none;
      transition: background 0.3s;
    }
    .menu-item:hover { background: #bcd5db; }
    .menu-item i { font-size: 20px; margin-left: 10px; width: 25px; text-align: center; }
    .menu-text { font-size: 15px; white-space: nowrap; }
    .sidebar.collapsed .menu-text { display: none; }
    .bottom-section { margin-bottom: 20px; }

    /* 🔹 Toggle Button */
    .toggle-btn {
      position: absolute;
      top: 20px;
      left: -15px; /* على اليسار لأن الشريط على اليمين */
      background: #003366;
      color: #fff;
      border-radius: 50%;
      border: none;
      width: 30px;
      height: 30px;
      cursor: pointer;
    }

    /* 🔹 Top Bar */
    .top-bar {
      position: fixed;
      top: 0;
      left: 0;
      right: 230px; /* يترك مسافة للشريط الجانبي */
      height: 60px;
      display: flex;
      justify-content: flex-start;
      align-items: center;
      padding: 0 20px;
      transition: right 0.3s;
      z-index: 10;
      background: transparent;
    }
    .sidebar.collapsed ~ .top-bar { right: 70px; }
    .top-icons { display: flex; align-items: center; gap: 20px; margin-right: 10px; }
    .icon-btn { background: none; border: none; cursor: pointer; font-size: 20px; color: #333; text-decoration: none; }
    .icon-btn:hover { color: #003366; }

    /* 🔹 Main Content */
    .main-content {
      margin-right: 230px; /* لأن الشريط يمين */
      margin-top: 70px;
      padding: 30px;
      transition: margin-right 0.3s;
      width: 100%;
    }
    .sidebar.collapsed + .top-bar + .main-content { margin-right: 70px; }
    h2 { font-size: 22px; color: #003366; margin-top: 0; }

    /* 🔹 Request card & timeline */
    .request-card {
      background: #fff;
      border-radius: 10px;
      padding: 18px;
      margin-bottom: 18px;
      box-shadow: 0 2px 6px rgba(0,0,0,0.06);
      border: 1px solid #eef3f6;
    }
    .request-header {
      display:flex;
      justify-content: space-between;
      align-items: center;
      gap: 10px;
    }
    .req-title { font-weight:700; color:#003366; }
    .req-meta { color:#6f6f6f; font-size:13px; }

    .timeline { margin-top:12px; }
    .step {
      display:flex;
      align-items:center;
      gap:12px;
      padding:10px 0;
      border-bottom: 1px dashed #eef3f6;
    }
    .step:last-child { border-bottom: none; }
    .circle {
      width:36px; height:36px; border-radius:50%;
      display:flex; align-items:center; justify-content:center;
      color:#fff; font-size:18px;
    }
    .status-text { font-size:14px; color:#222; }
    .status-time { font-size:12px; color:#888; }

    .no-requests {
      text-align:center;
      padding:26px;
      background:#fff;
      border-radius:8px;
      border:1px dashed #cfd8dc;
      color:#777;
    }

    .back-btn {
      margin-top: 20px;
      background-color: #7adba2;
      border: none;
      padding: 10px 18px;
      border-radius: 8px;
      font-size: 14px;
      cursor: pointer;
      color:#fff;
    }
    </style>
</head>
<body>

  <!-- Sidebar -->
  <div class="sidebar" id="sidebar">
    <button class="toggle-btn" id="toggleBtn"><i class="fas fa-bars"></i></button>
    <div>
      <div class="logo">
        <img src="logo1.jpg" alt="Logo">
      </div>
      <a href="student_profile.php" class="menu-item"><span class="menu-text">Profile</span><i class="fas fa-user"></i></a>
      <a href="new_request.php" class="menu-item"><span class="menu-text">New Request</span><i class="fas fa-plus-square"></i></a>
      <a href="track_request.php" class="menu-item"><span class="menu-text">Track Request</span><i class="fas fa-clock"></i></a>
    </div>

    <div class="bottom-section">
      <a href="setting_s.php" class="menu-item"><span class="menu-text">Notification Settings</span><i class="fas fa-gear"></i></a>
    </div>
  </div>

  <!-- Top bar -->
  <div class="top-bar">
    <div class="top-icons">
      <a class="icon-btn" href="notifications.php" title="Notifications"><i class="fas fa-bell"></i></a>
      <a class="icon-btn" href="logout.html" title="Logout"><i class="fas fa-arrow-right-from-bracket"></i></a>
    </div>
  </div>

  <!-- Main -->
  <div class="main-content">
    <h2>Track Request</h2>

    <?php if (count($requests) === 0): ?>
      <div class="no-requests">لا توجد طلبات بعد.</div>
    <?php else: ?>

      <?php foreach ($requests as $req): 
        $reqId = intval($req['id']);
        $reqTitle = $req['title'] ?: "طلب رقم {$reqId}";
        $reqCreated = $req['created_at'];
        // جلب سجلات التتبع لهذا الطلب
        $tracks = [];
        if ($s2 = $conn->prepare("SELECT status, note, created_at FROM track_request WHERE request_id = ? ORDER BY created_at DESC")) {
            $s2->bind_param("i", $reqId);
            $s2->execute();
            $r2 = $s2->get_result();
            $tracks = $r2->fetch_all(MYSQLI_ASSOC);
            $s2->close();
        }
      ?>
        <div class="request-card">
          <div class="request-header">
            <div>
              <div class="req-title"><?php echo htmlspecialchars($reqTitle, ENT_QUOTES, 'UTF-8'); ?></div>
              <div class="req-meta">انشئ بتاريخ: <?php echo fmt_datetime($reqCreated); ?></div>
            </div>
            <div class="req-meta">الحالة الحالية: <strong><?php echo htmlspecialchars($req['current_status'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></strong></div>
          </div>

          <div class="timeline">
            <?php if (count($tracks) === 0): ?>
              <div style="padding:10px 0;color:#777;">لا توجد تحديثات لهذا الطلب بعد.</div>
            <?php else: ?>
              <?php foreach ($tracks as $t): 
                $st = $t['status'];
                $note = $t['note'] ?? '';
                $created = $t['created_at'];
                // نحدد لون الدائرة حسب الحالة (مثال افتراضي)
                $color = '#7adba2'; // افتراضي أخضر
                if (stripos($st, 'pending') !== false || stripos($st, 'under review') !== false || stripos($st, 'قيد') !== false) $color = '#f3d37a';
                if (stripos($st, 'rejected') !== false || stripos($st, 'رفض') !== false) $color = '#f26b6b';
              ?>
                <div class="step">
                  <div class="circle" style="background: <?php echo $color; ?>;">
                    <i class="fa fa-check" aria-hidden="true" style="font-size:14px;"></i>
                  </div>
                  <div>
                    <div class="status-text"><?php echo htmlspecialchars($st, ENT_QUOTES, 'UTF-8'); ?></div>
                    <?php if (!empty($note)): ?><div class="status-time"><?php echo htmlspecialchars($note, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
                    <div class="status-time"><?php echo fmt_datetime($created); ?></div>
                  </div>
                </div>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>

        </div>
      <?php endforeach; ?>

    <?php endif; ?>

    <button class="back-btn" onclick="window.location.href='req_system.php'">Back to Home</button>
  </div>

<script>
  // Toggle sidebar (يتعامل مع وجود الشريط على اليمين)
  const toggleBtn = document.getElementById("toggleBtn");
  const sidebar = document.getElementById("sidebar");
  toggleBtn.addEventListener("click", () => {
    sidebar.classList.toggle("collapsed");
  });
</script>

</body>
</html>