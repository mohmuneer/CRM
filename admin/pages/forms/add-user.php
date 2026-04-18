<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require __DIR__ . "/../../../config/db.php";

// جلب الأدوار لعرضها في القائمة
$roles_query = $pdo->query("SELECT id, branch_name FROM branch");
$roles = $roles_query->fetchAll(PDO::FETCH_ASSOC);

if (isset($_POST['add_user'])) {
    $full_name = $_POST['full_name'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $branch_id = $_POST['branch_id'] ?? 2;
    $file_path = null;

    // معالجة رفع الصورة
    if (isset($_FILES['file_input']) && $_FILES['file_input']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = __DIR__ . "/../../../uploads/";
        if (!file_exists($upload_dir)) mkdir($upload_dir, 0777, true);
        $filename = time() . "_" . preg_replace("/[^a-zA-Z0-9.]/", "_", basename($_FILES['file_input']['name']));
        if (move_uploaded_file($_FILES['file_input']['tmp_name'], $upload_dir . $filename)) {
            $file_path = $filename;
        }
    }

    try {
        $pdo->beginTransaction();

        $sql_user = "INSERT INTO users (full_name, email, password, file_path) VALUES (?, ?, ?, ?)";
        $stmt_user = $pdo->prepare($sql_user);
        $stmt_user->execute([$full_name, $email, $password, $file_path]);

        $last_user_id = $pdo->lastInsertId();

        $sql_perm = "INSERT INTO user_branches (user_id, branch_id) VALUES (?, ?)";
        $stmt_perm = $pdo->prepare($sql_perm);
        $stmt_perm->execute([$last_user_id, $branch_id]);

        $pdo->commit();

        // التوجيه مع استخدام sessionStorage لعرض الموديل في الصفحة التالية
        echo "<script>
                sessionStorage.setItem('showSuccess', 'تم إضافة المستخدم الجديد ومنحه الصلاحيات بنجاح');
                window.location.href = '../tables/show-users.php';
              </script>";
        exit;
    } catch (Exception $e) {
        $pdo->rollBack();
        echo "خطأ في الإدخال: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Add-Users</title>
    <!-- Tell the browser to be responsive to screen width -->
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="../../plugins/fontawesome-free/css/all.min.css">
    <!-- Ionicons -->
    <link rel="stylesheet" href="https://code.ionicframework.com/ionicons/2.0.1/css/ionicons.min.css">
    <!-- Tempusdominus Bbootstrap 4 -->
    <link rel="stylesheet" href="../../plugins/tempusdominus-bootstrap-4/css/tempusdominus-bootstrap-4.min.css">
    <!-- iCheck -->
    <link rel="stylesheet" href="../../plugins/icheck-bootstrap/icheck-bootstrap.min.css">
    <!-- JQVMap -->
    <link rel="stylesheet" href="../../plugins/jqvmap/jqvmap.min.css">
    <!-- Theme style -->
    <link rel="stylesheet" href="../../dist/css/adminlte.min.css">
    <!-- overlayScrollbars -->
    <link rel="stylesheet" href="../../plugins/overlayScrollbars/css/OverlayScrollbars.min.css">
    <!-- Daterange picker -->
    <link rel="stylesheet" href="../../plugins/daterangepicker/daterangepicker.css">
    <!-- summernote -->
    <link rel="stylesheet" href="../../plugins/summernote/summernote-bs4.css">
    <!-- Google Font: Source Sans Pro -->
    <link href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700" rel="stylesheet">
    <!-- Bootstrap 4 RTL -->
    <link rel="stylesheet" href="https://cdn.rtlcss.com/bootstrap/v4.2.1/css/bootstrap.min.css">
    <!-- Custom style for RTL -->
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

        <!-- /.navbar -->


        <?php include(__DIR__ . '/../../main-sidebar.php'); ?>


        <!-- محتوى الفورم داخل content-wrapper -->
        <div class="content-wrapper">
            <section class="content-header">
                <div class="container-fluid">
                    <div class="row mb-2">
                        <div class="col-sm-6 ">
                            <h1>إضافة مستخدم جديد</h1>
                        </div>
                        <div class="col-sm-6">
                            <ol class="breadcrumb float-sm-right">
                                <li class="breadcrumb-item"><a href="../../index.php">الرئيسية</a></li>
                                <li class="breadcrumb-item active">إضافة مستخدم</li>
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
                                <h3 class="card-title">أدخل بيانات المستخدم</h3>
                            </div>

                            <div class="card-body">
                                <form method="POST" enctype="multipart/form-data">
                                    <div class="form-group">
                                        <label>اسم المستخدم</label>
                                        <input type="text" name="full_name" class="form-control" placeholder="Full Name"
                                            required>
                                    </div>

                                    <div class="form-group">
                                        <label>البريد الإلكتروني</label>
                                        <input type="email" name="email" class="form-control"
                                            placeholder="Email address" required>
                                    </div>

                                    <div class="form-group">
                                        <label>كلمة المرور</label>
                                        <input type="password" name="password" class="form-control"
                                            placeholder="Password" required>
                                    </div>

                                    <div class="form-group">
                                        <label>رفع ملف (اختياري)</label>
                                        <input type="file" name="file_input" class="form-control">
                                    </div>

                                    <div class="form-group">
                                        <label>نوع الدور</label>
                                        <select name="branch_id" class="form-control" required>
                                            <option value="">-- اختر الفرع --</option>
                                            <?php foreach ($roles as $role): ?>
                                                <option value="<?= $role['id'] ?>"
                                                    <?= ($role['id'] == 2) ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($role['branch_name']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <button type="submit" name="add_user" class="btn btn-primary">إضافة
                                            مستخدم</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>
        <!-- ./wrapper -->

        <!-- jQuery -->
        <script src="../../plugins/jquery/jquery.min.js"></script>
        <!-- jQuery UI 1.11.4 -->
        <script src="../../plugins/jquery-ui/jquery-ui.min.js"></script>
        <!-- Resolve conflict in jQuery UI tooltip with Bootstrap tooltip -->
        <script>
            $.widget.bridge('uibutton', $.ui.button)
        </script>
        <!-- Bootstrap 4 rtl -->
        <script src="https://cdn.rtlcss.com/bootstrap/v4.2.1/js/bootstrap.min.js"></script>
        <!-- Bootstrap 4 -->
        <script src="../../plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
        <!-- ChartJS -->
        <script src="../../plugins/chart.js/Chart.min.js"></script>
        <!-- Sparkline -->
        <script src="../../plugins/sparklines/sparkline.js"></script>
        <!-- JQVMap -->
        <script src="../../plugins/jqvmap/jquery.vmap.min.js"></script>
        <script src="../../plugins/jqvmap/maps/jquery.vmap.world.js"></script>
        <!-- jQuery Knob Chart -->
        <script src="../../plugins/jquery-knob/jquery.knob.min.js"></script>
        <!-- daterangepicker -->
        <script src="../../plugins/moment/moment.min.js"></script>
        <script src="../../plugins/daterangepicker/daterangepicker.js"></script>
        <!-- Tempusdominus Bootstrap 4 -->
        <script src="../../plugins/tempusdominus-bootstrap-4/js/tempusdominus-bootstrap-4.min.js"></script>
        <!-- Summernote -->
        <script src="../../plugins/summernote/summernote-bs4.min.js"></script>
        <!-- overlayScrollbars -->
        <script src="../../plugins/overlayScrollbars/js/jquery.overlayScrollbars.min.js"></script>
        <!-- AdminLTE App -->
        <script src="../../dist/js/adminlte.js"></script>
        <!-- AdminLTE dashboard demo (This is only for demo purposes) -->
        <script src="../../dist/js/pages/dashboard.js"></script>
        <!-- AdminLTE for demo purposes -->
        <script src="../../dist/js/demo.js"></script>
        <!-- page script -->
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