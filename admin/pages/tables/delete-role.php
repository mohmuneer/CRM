<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require __DIR__ . "/../../../config/db.php";

if (isset($_GET['id'])) {
    $role_id = $_GET['id'];

    try {
        // 1. التحقق أولاً: هل هذه الصلاحية مرتبطة بمستخدمين؟
        $check_stmt = $pdo->prepare("SELECT COUNT(*) FROM user_permision WHERE role_id = ?");
        $check_stmt->execute([$role_id]);
        $usage_count = $check_stmt->fetchColumn();

        if ($usage_count > 0) {
            // إذا وُجد مستخدمين مرتبطين بهذه الصلاحية، نوقف العملية
            echo "<script>
                    sessionStorage.setItem('showError', 'لا يمكن حذف هذه الصلاحية لأنها ممنوحة لمستخدم أو أكثر حالياً.');
                    window.location.href = 'view-permissions.php';
                  </script>";
            exit;
        }

        // 2. إذا لم تكن مرتبطة، نبدأ عملية الحذف
        $pdo->beginTransaction();

        // حذف الصلاحية من جدول الأدوار (بافتراض اسم الجدول roles)
        $delete_role = $pdo->prepare("DELETE FROM roles WHERE id = ?");
        $delete_role->execute([$role_id]);

        $pdo->commit();

        echo "<script>
                sessionStorage.setItem('showSuccess', 'تم حذف الصلاحية بنجاح');
                window.location.href = 'show-users.php';
              </script>";
        exit;
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        die("حدث خطأ أثناء الحذف: " . $e->getMessage());
    }
} else {
    header("Location: show-users.php");
    exit;
}
