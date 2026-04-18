<?php
// 1. بدء الجلسة ضروري جداً للتعرف على المرسل (أنت)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. استدعاء ملف الاتصال (تأكد من المسار الصحيح)
require __DIR__ . "/../config/db.php";

// 3. التحقق من أن الطلب تم عبر POST وأن المستخدم مسجل دخول
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_SESSION['user_id'])) {

    // جلب البيانات من الطلب وتطهيرها
    $sender_id   = $_SESSION['user_id'];
    $receiver_id = isset($_POST['receiver_id']) ? intval($_POST['receiver_id']) : 0;
    $message     = isset($_POST['message']) ? trim($_POST['message']) : '';

    // 4. التأكد من أن الرسالة ليست فارغة وأن المستلم موجود
    if (!empty($message) && $receiver_id > 0) {
        try {
            // استخدام PDO (المستخدم في ملف chat.php) لمنع ثغرات SQL Injection
            $stmt = $pdo->prepare("INSERT INTO messages (sender_id, receiver_id, message_text, created_at) VALUES (?, ?, ?, NOW())");
            $success = $stmt->execute([$sender_id, $receiver_id, $message]);

            if ($success) {
                echo "success";
            } else {
                echo "error_database";
            }
        } catch (PDOException $e) {
            // في حال وجود خطأ في قاعدة البيانات (مثلاً عمود ناقص)
            echo "error: " . $e->getMessage();
        }
    } else {
        echo "empty_fields";
    }
} else {
    echo "invalid_request";
}
