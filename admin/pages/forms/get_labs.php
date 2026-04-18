<?php
require __DIR__ . "/../../../config/db.php";

if (isset($_GET['college_id'])) {
    $college_id = (int)$_GET['college_id'];

    // استعلام لجلب المعامل التابعة للكلية المختارة فقط
    $stmt = $pdo->prepare("SELECT id, lab_name FROM labs WHERE college_id = ? ORDER BY lab_name ASC");
    $stmt->execute([$college_id]);
    $labs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // إرسال البيانات بتنسيق JSON
    header('Content-Type: application/json');
    echo json_encode($labs);
    exit;
}
