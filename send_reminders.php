<?php

// 1. إعدادات الاتصال بقاعدة البيانات (يجب تعديلها لتناسب إعداداتك)
$servername = "localhost";
$username = "root"; // اسم المستخدم الافتراضي لـ XAMPP
$password = "";     // كلمة المرور الافتراضية لـ XAMPP
$dbname = "agdb"; // غير هذا إلى اسم قاعدة بيانات مشروعك

// إنشاء اتصال
$conn = new mysqli($servername, $username, $password, $dbname);

// التحقق من الاتصال
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// 2. تحديد التاريخ الحالي والماضي
$today = time(); 

// 3. استعلام قاعدة البيانات لجلب الطلبات المعلقة 
// نفترض أن لديك عمود 'reminder_duration' يحتوي على 1 أو 2 أو 3 في جدول الطلبات
// إذا لم يكن موجوداً، يجب إضافة عمود لربط المدة المختارة في الواجهة بالطلب.

// ملاحظة: إذا لم يكن لديك عمود 'reminder_duration' في جدول 'requests'، يجب عليك جلب قيمة المدة 
// من جدول إعدادات التنبيهات (مثل 'settings') باستخدام مُعرّف الطالب.
// لتبسيط الكود، سنفترض أن القيمة (مثلاً 3 أيام) هي قيمة ثابتة للاختبار.

// لتشغيل التذكير على أساس '3 أيام' كما اخترت للطالب Jay، يجب أن تكون المدة 3 أيام.
// تأكد من قراءة قيمة المدة الديناميكية من قاعدة البيانات في حالتك الحقيقية.
$duration = 3; // وضعنا 3 أيام للاختبار، يجب أن يكون هذا ديناميكياً في التطبيق الحقيقي

$sql = "
    SELECT id, created_at, professor_user_id, grades_reminder_sent
    FROM requests 
    WHERE status = 'pending'
    AND professor_user_id IS NOT NULL 
    AND professor_user_id != 0
";

$result = $conn->query($sql);

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        
        $request_date = strtotime($row['created_at']); 
        $last_reminder_timestamp = ($row['grades_reminder_sent'] !== NULL) ? strtotime($row['grades_reminder_sent']) : 0;
        $recipient_id = $row['professor_user_id'];
        
        // حساب الفترة الزمنية بالثواني
        $reminder_interval_seconds = $duration * 24 * 60 * 60;
        
        // حساب متى يجب إرسال التذكير التالي (تاريخ الطلب + المدة)
        $next_reminder_time = $request_date + $reminder_interval_seconds;
        
        // الشرط: هل حان وقت التذكير؟
        // 1. هل الوقت الحالي أكبر من وقت التذكير التالي؟ (هل مر 3 أيام؟)
        // 2. هل لم يتم إرسال تذكير في آخر 24 ساعة؟ (لتجنب التكرار في نفس اليوم)
        
        if ($today >= $next_reminder_time && $today > ($last_reminder_timestamp + (24 * 60 * 60))) {
            
            //  4. تنفيذ إرسال التذكير (يجب إكمال هذا الجزء)
            
            // جلب إيميل الدكتور من جدول المستخدمين (نفترض أن لديك جدول 'users')
            $doctor_query = $conn->query("SELECT email FROM users WHERE id = $recipient_id");
            if ($doctor_query->num_rows > 0) {
                $doctor_email = $doctor_query->fetch_assoc()['email'];

                // يجب عليك استبدال هذا بسكربت الإرسال الفعلي (مثل استخدام مكتبة PHPMailer)
                // mail($doctor_email, "تذكير بطلب توصية معلق", "الرجاء إكمال طلب التوصية.");

                echo "Reminder Sent to Professor ID: " . $recipient_id . " for Request ID: " . $row['id'] . "\n";
                
                //  5. تحديث قاعدة البيانات لتسجيل أن التذكير قد أُرسل 
                $update_sql = "UPDATE requests SET grades_reminder_sent = NOW() WHERE id = " . $row['id'];
                $conn->query($update_sql);

            } else {
                 echo "Error: Could not find email for Professor ID: " . $recipient_id . "\n";
            }
        }
    }
} else {
    echo "No pending requests found.\n";
}

$conn->close();

?>