<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require __DIR__ . "/../../../config/db.php";

// جلب السجلات من الأحدث إلى الأقدم
try {
    $stmt = $pdo->query("SELECT * FROM system_logs ORDER BY created_at DESC");
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $logs = [];
    $error = "خطأ في قاعدة البيانات: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="utf-8">
    <title>سجل النظام (Logs)</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="../../plugins/fontawesome-free/css/all.min.css">
    <link rel="stylesheet" href="../../dist/css/adminlte.min.css">
    <link rel="stylesheet" href="https://cdn.rtlcss.com/bootstrap/v4.2.1/css/bootstrap.min.css">
    <link rel="stylesheet" href="../../dist/css/custom.css">
    <style>
        body {
            font-family: 'Cairo', sans-serif;
        }

        .table-responsive {
            scrollbar-width: thin;
        }
    </style>
</head>

<body class="hold-transition sidebar-mini">
    <div class="wrapper">

        <?php include(__DIR__ . '/../../main-header.php'); ?>
        <?php include(__DIR__ . '/../../main-sidebar.php'); ?>

        <div class="content-wrapper">
            <section class="content-header">
                <div class="container-fluid">
                    <div class="row mb-2">
                        <div class="col-sm-6">
                            <h1>سجل نشاطات النظام</h1>
                        </div>
                    </div>
                </div>
            </section>

            <section class="content">
                <div class="container-fluid">
                    <div class="card card-dark">
                        <div class="card-header breadcrumb float-sm-right">
                            <h3 class="card-title">مراقبة العمليات الأخيرة</h3>
                        </div>
                        <div class="card-body">
                            <?php if (isset($error)): ?>
                                <div class="alert alert-danger"><?php echo $error; ?></div>
                            <?php endif; ?>

                            <table class="table table-bordered table-striped text-center">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>المستخدم</th>
                                        <th>العملية</th>
                                        <th>الصفحة</th>
                                        <th>IP Address</th>
                                        <th>التاريخ والوقت</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($logs)): ?>
                                        <?php foreach ($logs as $log): ?>
                                            <tr>
                                                <td><?php echo $log['id']; ?></td>
                                                <td><span
                                                        class="badge badge-info"><?php echo htmlspecialchars($log['user_name'] ?? 'غير معروف'); ?></span>
                                                </td>
                                                <td><?php echo htmlspecialchars($log['action']); ?></td>
                                                <td><?php echo htmlspecialchars($log['page_url']); ?>
                                                </td>
                                                <td><?php echo $log['ip_address']; ?></td>
                                                <td><?php echo date('Y-m-d H:i:s', strtotime($log['created_at'])); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="6">لا توجد سجلات حالياً</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </div>

    <script src="../../plugins/jquery/jquery.min.js"></script>
    <script src="../../plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../../dist/js/adminlte.min.js"></script>
</body>

</html>