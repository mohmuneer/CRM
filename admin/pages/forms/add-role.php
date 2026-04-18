<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require __DIR__ . "/../../../config/db.php";

if (isset($_POST['add_permission'])) {

    $permission_name = trim($_POST['permission_name']);
    $permission_code = trim($_POST['permission_code']);

    if (!empty($permission_name) && !empty($permission_code)) {

        // 1. التحقق من وجود الكود مسبقاً في قاعدة البيانات
        $checkSql = "SELECT COUNT(*) FROM ROLES WHERE role_code = ?";
        $checkStmt = $pdo->prepare($checkSql);
        $checkStmt->execute([$permission_code]);
        $exists = $checkStmt->fetchColumn();

        if ($exists > 0) {
            // إذا كان الكود موجوداً
            echo "<script>alert('خطأ: كود الصلاحية هذا موجود مسبقاً، يرجى اختيار كود آخر');</script>";
        } else {
            // 2. إذا لم يكن موجوداً، يتم الإدخال
            $sql = "INSERT INTO ROLES (role_name, role_code) VALUES (?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$permission_name, $permission_code]);

            echo "<script>
                sessionStorage.setItem('showSuccess', 'تم إضافة الصلاحية الجديدة  بنجاح');
                window.location.href = '../tables/view-permissions.php';
              </script>";
        }
    } else {
        echo "<script>alert('الرجاء تعبئة جميع الحقول');</script>";
        $permission_name = null;
        $permission_code = null;
    }
}
?>

<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Add Permission</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link rel="stylesheet" href="../../plugins/fontawesome-free/css/all.min.css">
    <link rel="stylesheet" href="../../dist/css/adminlte.min.css">
    <link rel="stylesheet" href="https://cdn.rtlcss.com/bootstrap/v4.2.1/css/bootstrap.min.css">
    <link rel="stylesheet" href="../../dist/css/custom.css">
    <style>
    /* إخفاء شريط التمرير الأفقي والعمودي نهائياً */
    html,
    body {
        overflow-x: hidden !important;
        /* يمنع التمرير العرضي الذي يظهر في الصورة */
        scrollbar-width: none !important;
        /* Firefox */
        -ms-overflow-style: none !important;
        /* IE/Edge */
    }

    /* لمتصفحات Chrome و Safari */
    ::-webkit-scrollbar {
        display: none !important;
        width: 0px !important;
        background: transparent !important;
    }

    /* إخفاء أشرطة مكتبة OverlayScrollbars الخاصة بالقالب */
    .os-scrollbar,
    .os-scrollbar-horizontal,
    .os-scrollbar-vertical {
        display: none !important;
        visibility: hidden !important;
    }

    /* منع ظهور الفراغ الأبيض في أسفل الصفحة */
    .wrapper {
        overflow-x: hidden !important;
    }
    </style>
</head>

<body class="hold-transition sidebar-mini layout-fixed">

    <div class="wrapper">


        <?php include(__DIR__ . '/../../main-header.php'); ?>




        <?php include(__DIR__ . '/../../main-sidebar.php'); ?>


        <div class="content-wrapper">

            <section class="content-header">
                <div class="container-fluid">
                    <div class="row mb-2">

                        <div class="col-sm-6">
                            <h1>إضافة صلاحية</h1>
                        </div>

                        <div class="col-sm-6">
                            <ol class="breadcrumb float-sm-right">
                                <li class="breadcrumb-item"><a href="../../index.php">الرئيسية</a></li>
                                <li class="breadcrumb-item active">الصلاحيات</li>
                            </ol>
                        </div>

                    </div>
                </div>
            </section>

            <section class="content">

                <div class="row">
                    <div class="col-12">

                        <div class="card card-primary">

                            <div class="card-header breadcrumb float-sm-right">
                                <h3 class="card-title">إضافة صلاحية جديدة</h3>
                            </div>

                            <div class="card-body">

                                <form method="POST">

                                    <div class="form-group">
                                        <label>اسم الصلاحية</label>
                                        <input type="text" name="permission_name" class="form-control"
                                            placeholder="Enter role name" required>
                                    </div>

                                    <div class="form-group">
                                        <label>رمز الصلاحية</label>
                                        <input type="text" name="permission_code" class="form-control"
                                            placeholder="Enter role code" required>
                                    </div>

                                    <div class="form-group">
                                        <button type="submit" name="add_permission" class="btn btn-primary">
                                            إضافة صلاحية
                                        </button>
                                    </div>

                                </form>

                            </div>

                        </div>

                    </div>
                </div>

            </section>

        </div>

        <footer class="main-footer">
            <?php include('../../main-footer.php') ?>
        </footer>

    </div>

    <script src="../../plugins/jquery/jquery.min.js"></script>
    <script src="../../plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../../dist/js/adminlte.js"></script>
    <script>
    $(function() {
        $("#example1").DataTable();
        $('#example2').DataTable({
            "paging": true,
            "lengthChange": false,
            "searching": false,
            "ordering": true,
            "info": true,
            "autoWidth": false,
        });
    });
    </script>
    <script>
    window.addEventListener("load", function() {
        if (!sessionStorage.getItem("reloaded")) {
            sessionStorage.setItem("reloaded", "true");
            location.reload();
        } else {
            document.body.style.visibility = "visible";
        }
    });
    </script>

</body>

</html>