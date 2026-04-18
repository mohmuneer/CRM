<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require __DIR__ . "/../../../config/db.php";

// 1. التحقق من وصول المعرف (ID)
if (isset($_GET['id'])) {
    $user_id = $_GET['id'];

    try {
        // بدء عملية (Transaction) لضمان سلامة البيانات
        $pdo->beginTransaction();

        // 2. جلب اسم الصورة لحذفها من المجلد الفيزيائي
        $stmt_img = $pdo->prepare("SELECT file_path FROM users WHERE id = ?");
        $stmt_img->execute([$user_id]);
        $file_path = $stmt_img->fetchColumn();

        if ($file_path) {
            $full_path = __DIR__ . "/../../../uploads/" . $file_path;
            if (file_exists($full_path)) {
                unlink($full_path); // حذف الصورة من السيرفر
            }
        }

        // 3. حذف الأدوار المرتبطة بالمستخدم أولاً (لتجنب مشاكل Foreign Key)
        $delete_perms = $pdo->prepare("DELETE FROM user_permision WHERE user_id = ?");
        $delete_perms->execute([$user_id]);

        // 4. حذف المستخدم من جدول users
        $delete_user = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $delete_user->execute([$user_id]);

        $pdo->commit();

        // 5. إعداد رسالة النجاح باستخدام JavaScript للـ Modal
        echo "<script>
                sessionStorage.setItem('showSuccess', 'تم حذف المستخدم وجميع بياناته بنجاح');
                window.location.href = 'show-users.php';
              </script>";
        exit;
    } catch (Exception $e) {
        // تراجع عن العمليات في حال حدوث خطأ
        $pdo->rollBack();
        die("حدث خطأ أثناء الحذف: " . $e->getMessage());
    }
} else {
    // إذا تم الدخول للملف بدون ID
    header("Location: show-users.php");
    exit;
}
