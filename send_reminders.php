<?php
// 1. تأكدي من أن المسار صحيح لملف اتصال قاعدة البيانات
include 'index.php';

// التحقق من أن الاتصال موجود
if (!isset($conn)) {
    die("خطأ: لم يتم تحميل اتصال قاعدة البيانات بشكل صحيح.");
}

class AutoReminderSystem {
    private $conn;
    
    public function __construct($connection) {
        $this->conn = $connection;
    }
    
    // الدالة الرئيسية التي تشتغل تلقائياً
    public function check_and_send_reminders() {
        // 1. جلب جميع الطلاب الذين لديهم إعدادات مفعلة
        $students = $this->get_students_with_reminders();
        $results = [];
        
        foreach ($students as $student) {
            $student_results = $this->process_student_reminders($student);
            $results = array_merge($results, $student_results);
        }
        
        // تسجيل النتائج
        $this->log_results($results);
        return $results;
    }
    
    private function get_students_with_reminders() {
        $sql = "SELECT u.id, u.name, u.email, 
                            ns.via_email, ns.via_in_app, ns.reminder_days,
                            ns.notify_pending, ns.notify_rejected, ns.notify_uploaded
                FROM users u 
                JOIN notification_settings ns ON u.id = ns.user_id 
                WHERE u.role = 'graduate' 
                AND (ns.via_email = 1 OR ns.via_in_app = 1)";
        
        $result = $this->conn->query($sql);
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    
    private function process_student_reminders($student) {
        $results = [];
        
        // 🔔 التحقق من الطلبات المعلقة التي لم يُرسل لها تذكير بعد
        if ($student['notify_pending']) {
            $pending_requests = $this->get_pending_requests($student['id']);
            foreach ($pending_requests as $request) {
                // نتحقق إذا مر عدد الأيام المطلوب للتذكير
                if ($this->should_remind($request['created_at'], $student['reminder_days'])) {
                    $this->send_reminder($student, $request, 'pending');
                    // تحديث عمود reminder_sent لمنع التكرار في التشغيل التالي
                    $this->update_request_reminder_status($request['id']); 
                    $results[] = "تم تذكير {$student['name']} (ID: {$student['id']}) بطلب معلق رقم {$request['id']}";
                }
            }
        }
        
        // ❌ التحقق من الطلبات المرفوضة (تم التعديل لمنع التكرار)
        if ($student['notify_rejected']) {
            $rejected_requests = $this->get_rejected_requests($student['id']);
            foreach ($rejected_requests as $request) {
                $this->send_reminder($student, $request, 'rejected');
                
                // ✅ تحديث reminder_sent لمنع تكرار إشعار الرفض في التشغيل التالي
                $this->update_request_reminder_status($request['id']); 
                
                $results[] = "تم إشعار {$student['name']} (ID: {$student['id']}) برفض الطلب رقم {$request['id']}";
            }
        }
        
        // ✅ التحقق من التوصيات المرفوعة
        if ($student['notify_uploaded']) {
            $uploaded_recommendations = $this->get_uploaded_recommendations($student['id']);
            foreach ($uploaded_recommendations as $recommendation) {
                $this->send_reminder($student, $recommendation, 'uploaded');
                
                // ✅ تحديث reminder_sent لمنع تكرار إشعار الرفع في التشغيل التالي
                $this->update_request_reminder_status($recommendation['id']); 
                
                $results[] = "تم إشعار {$student['name']} (ID: {$student['id']}) برفع التوصية رقم {$recommendation['id']}";
            }
        }
        
        return $results;
    }
    
    private function send_reminder($student, $item, $type) {
        $messages = [
            'pending' => [
                'title' => 'طلب معلق يحتاج متابعة',
                'message' => 'طلبك لا يزال قيد الانتظار. يرجى المتابعة مع الدكتور.'
            ],
            'rejected' => [
                'title' => 'تم رفض طلبك',
                'message' => 'نأسف لإعلامك أنه تم رفض طلبك.'
            ],
            'uploaded' => [
                'title' => 'تم رفع التوصية',
                'message' => 'تم رفع توصية الدكتور بنجاح.'
            ]
        ];
        
        $message = $messages[$type];
        
        if ($student['via_email']) {
            $this->send_email($student, $message);
        }
        
        if ($student['via_in_app']) {
            $this->add_in_app_notification($student, $message);
        }
    }
    
    private function log_results($results) {
        if (!empty($results)) {
            $log_message = "--- Cron Job Run: " . date('Y-m-d H:i:s') . " ---\n" . implode("\n", $results) . "\n\n";
            file_put_contents('reminder_logs.txt', $log_message, FILE_APPEND);
        }
    }
    
    // ----------------------------------------------------------------
    // دوال المساعدة وتفاعل قاعدة البيانات 
    // ----------------------------------------------------------------
    
    private function send_email($student, $message) {
        $log = "
        ✉️ إيميل تم إرساله
        إلى: {$student['email']}
        الموضوع: {$message['title']}
        المحتوى: {$message['message']}
        الوقت: " . date('Y-m-d H:i:s') . "
        --------------------------
        ";
        file_put_contents('email_logs.txt', $log, FILE_APPEND);
    }
    
    private function add_in_app_notification($student, $message) {
        // تم تصحيح: حذف 'title' من الاستعلام واستخدام عمود 'message' فقط
        $sql = "INSERT INTO notifications (user_id, message) VALUES (?, ?)"; 
        
        $stmt = $this->conn->prepare($sql);
        
        if ($stmt === false) {
            die("SQL Prepare Failed (In-App Notification): " . $this->conn->error . " Query: " . $sql);
        }
        
        $notification_content = $message['title'] . ": " . $message['message'];
        
        $stmt->bind_param("is", $student['id'], $notification_content);
        $stmt->execute();
    }
    
    private function should_remind($request_date, $reminder_days) {
        $request_time = strtotime($request_date);
        $current_time = time();
        $days_passed = floor(($current_time - $request_time) / (60 * 60 * 24));
        
        return $days_passed >= $reminder_days;
    }
    
    // جلب الطلبات المعلقة (يعمل بشكل صحيح)
    private function get_pending_requests($student_id) {
        $sql = "SELECT id, created_at FROM requests WHERE user_id = ? AND status = 'pending' AND reminder_sent = 0";
        
        $stmt = $this->conn->prepare($sql); 
        
        if ($stmt === false) {
            die("SQL Prepare Failed (Pending): " . $this->conn->error . " Query: " . $sql); 
        }
        
        $stmt->bind_param("i", $student_id); 
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    
    // تحديث عمود `reminder_sent` لمنع تكرار إرسال التذكير
    private function update_request_reminder_status($request_id) {
        $sql = "UPDATE requests SET reminder_sent = 1 WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $request_id);
        $stmt->execute();
    }
    
    // جلب الطلبات المرفوضة (تم التعديل لمنع التكرار)
    private function get_rejected_requests($user_id) {
        // ✅ تمت إضافة التصفية بـ AND reminder_sent = 0
        $sql = "SELECT id, created_at FROM requests WHERE user_id = ? AND status = 'rejected' AND reminder_sent = 0"; 
        
        $stmt = $this->conn->prepare($sql);
        if ($stmt === false) {
            die("SQL Prepare Failed (Rejected): " . $this->conn->error . " Query: " . $sql); 
        }
        
        $stmt->bind_param("i", $user_id); 
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    
    // جلب التوصيات المرفوعة (يعمل بشكل صحيح)
    private function get_uploaded_recommendations($user_id) { 
        $sql = "SELECT id, created_at FROM requests WHERE user_id = ? AND status = 'accepted' AND reminder_sent = 0"; 
        
        $stmt = $this->conn->prepare($sql);
        
        if ($stmt === false) {
            die("SQL Prepare Failed (Uploaded): " . $this->conn->error . " Query: " . $sql); 
        }
        
        $stmt->bind_param("i", $user_id); 
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

} 

// ----------------------------------------------------------------
// التشغيل الفعلي (في المجال العام)
// ----------------------------------------------------------------

$reminder_system = new AutoReminderSystem($conn);
$results = $reminder_system->check_and_send_reminders();

// لعرض النتائج عند التشغيل اليدوي
echo "<h3>نتائج تشغيل التذكيرات:</h3>";
if (empty($results)) {
    echo "<p>✅ لم يتم العثور على أي تذكيرات للمعالجة حالياً. (أو لا يوجد طلاب مفعلين).</p>";
} else {
    foreach ($results as $result) {
        echo "<p>🔔 $result</p>";
    }
}
?>