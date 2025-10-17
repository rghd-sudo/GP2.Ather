<?php
// نفترض أن المستخدم مسجل دخول
// هنا حطيت بيانات ثابتة للتجربة فقط
$user = [
'name' => 'Shahd Ahmedc',
'university_id' => '11258905',
'department' => 'Information System',
'purpose' => 'internship_Huawei',
'recommendation_type' => 'Academic'
];

// عند الضغط على الإرسال أو الحفظ كمسودة
$message = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
$recommendation_text = $_POST['recommendation_text'] ?? '';
$recommendation_type = $_POST['recommendation_type'] ?? 'Academic';
$action = $_POST['action'] ?? 'send';

if ($action === 'send' && empty($recommendation_text)) {
$message = "❌ يجب كتابة نص التوصية قبل الإرسال.";
} else {
$status = ($action === 'draft') ? "تم الحفظ كمسودة ✅" : "تم إرسال التوصية بنجاح ✅";
$message = $status;
}
}
function e($txt){ return htmlspecialchars($txt, ENT_QUOTES, 'UTF-8'); }
?>
<!doctype html>
<html lang="ar">
<head>
<meta charset="utf-8">
<title>Recommendation Writing</title>
<style>
body { font-family: Arial; background:#fbf9f6; direction:rtl; }
.card { background:#f0f0f0; padding:18px; border-radius:12px; display:flex; gap:20px; margin-bottom:20px; }
.col { flex:1; }
.label { font-weight:bold; }
.small { margin-top:6px; color:#333; }
.container { background:#fff; padding:18px; border-radius:12px; box-shadow:0 0 4px rgba(0,0,0,0.1); }
textarea { width:100%; min-height:150px; padding:10px; }
.footer { display:flex; justify-content:space-between; margin-top:12px; }
.btn { padding:8px 14px; border:none; border-radius:6px; cursor:pointer; }
.btn-grey { background:#d9d9d9; }
.btn-primary { background:#2f6bff; color:#fff; }
.btn-cancel { background:#aaa; color:#fff; }
.msg { padding:10px; margin-bottom:12px; border-radius:6px; }
.success { background:#e7f6e9; color:#135e2a; }
.error { background:#ffecec; color:#8a1d1d; }
</style>
</head>
<body>

<h2>Recommendation Writing</h2>

<?php if($message): ?>
<div class="msg <?php echo (strpos($message,"❌")!==false?"error":"success"); ?>">
<?php echo e($message); ?>
</div>
<?php endif; ?>

<div class="card">
<div class="col">
<div class="label">Student name</div>
<div class="small"><?php echo e($user['name']); ?></div>
</div>
<div class="col">
<div class="label">ID</div>
<div class="small"><?php echo e($user['university_id']); ?></div>
</div>
<div class="col">
<div class="label">Department</div>
<div class="small"><?php echo e($user['department']); ?></div>
</div>
<div class="col">
<div class="label">Purpose</div>
<div class="small"><?php echo e($user['purpose']); ?></div>
</div>
</div>

<div class="container">
<form method="post">
<label>Recommendation Type: </label>
<select name="recommendation_type">
<option <?php if($user['recommendation_type']=="Academic") echo "selected"; ?>>Academic</option>
<option>Professional</option>
<option>Scholarship</option>
</select>

<div style="margin-top:10px;">
<textarea name="recommendation_text" placeholder="اكتب نص التوصية هنا..."><?php echo e($_POST['recommendation_text'] ?? ''); ?></textarea>
</div>

<div class="footer">
<button type="submit" name="action" value="draft" class="btn btn-grey">Save as Draft</button>
<div>
<button type="reset" class="btn btn-cancel">Cancel</button>
<button type="submit" name="action" value="send" class="btn btn-primary">Send Recommendation</button>
</div>
</div>
</form>
</div>

</body>
</html>