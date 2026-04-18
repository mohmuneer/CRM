<?php
session_start();
require __DIR__ . "/../../../config/db.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $id = filter_var($_POST['lab_id'], FILTER_SANITIZE_NUMBER_INT);

    try {
        if ($action === 'update') {
            $name = trim($_POST['lab_name']);
            $college_id = filter_var($_POST['college_id'], FILTER_SANITIZE_NUMBER_INT); // استلام معرف الكلية الجديد

            // 1. التأكد من أن اسم المعمل الجديد ليس مكرراً (داخل نفس الكلية وباستثناء السجل الحالي)
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM labs WHERE lab_name = ? AND college_id = ? AND id != ?");
            $stmt->execute([$name, $college_id, $id]);

            if ($stmt->fetchColumn() > 0) {
                $_SESSION['swal_type'] = 'error';
                $_SESSION['swal_title'] = 'خطأ!';
                $_SESSION['swal_text'] = 'اسم المعمل هذا موجود بالفعل في الكلية المختارة.';
            } else {
                // 2. تحديث الاسم ومعرف الكلية في جدول labs
                $stmt = $pdo->prepare("UPDATE labs SET lab_name = ?, college_id = ? WHERE id = ?");
                $stmt->execute([$name, $college_id, $id]);

                $_SESSION['swal_type'] = 'success';
                $_SESSION['swal_title'] = 'تم التحديث!';
                $_SESSION['swal_text'] = 'تم تعديل بيانات المعمل والكلية بنجاح.';
            }
        } elseif ($action === 'delete') {
            // الحذف من جدول labs
            $stmt = $pdo->prepare("DELETE FROM labs WHERE id = ?");
            $stmt->execute([$id]);

            $_SESSION['swal_type'] = 'success';
            $_SESSION['swal_title'] = 'تم الحذف!';
            $_SESSION['swal_text'] = 'تم حذف المعمل نهائياً.';
        }
    } catch (PDOException $e) {
        $_SESSION['swal_type'] = 'error';
        $_SESSION['swal_title'] = 'فشل الإجراء';
        $_SESSION['swal_text'] = 'لا يمكن تنفيذ العملية لوجود بيانات مرتبطة بهذا المعمل (مثل أجهزة أو جداول محاضرات).';
    }

    // إرسال البيانات إلى sessionStorage والعودة لصفحة الإضافة
    echo "<script>
        sessionStorage.setItem('swal_icon', '" . ($_SESSION['swal_type'] ?? 'info') . "');
        sessionStorage.setItem('swal_title', '" . ($_SESSION['swal_title'] ?? '') . "');
        sessionStorage.setItem('swal_text', '" . ($_SESSION['swal_text'] ?? '') . "');
        window.location.href = '../forms/add-lab.php'; 
    </script>";
    exit;
}
