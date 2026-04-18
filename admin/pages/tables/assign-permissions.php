<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require __DIR__ . "/../../../config/db.php";

// جلب جميع المستخدمين
$users = $pdo->query("SELECT id, full_name FROM users")->fetchAll();

// جلب جميع الصلاحيات (الأدوار)
$permission_all = "SELECT r.role_name, r.id as role_id, r.role_code FROM roles r";
$permissions = $pdo->query($permission_all)->fetchAll(PDO::FETCH_ASSOC);

// جلب الصلاحيات المعينة للمستخدم المختار
$assigned_permissions = [];
if (isset($_POST['user_id']) && $_POST['user_id'] != "") {
    $user_id = $_POST['user_id'];
    // تصحيح اسم الجدول: user_permision
    $stmt = $pdo->prepare("SELECT role_id FROM user_permision WHERE user_id=?");
    $stmt->execute([$user_id]);
    $assigned_permissions = $stmt->fetchAll(PDO::FETCH_COLUMN);
}

// معالجة الحفظ
if (isset($_POST['save_permissions'])) {
    try {
        $user_id = $_POST['user_id'];
        $selected_permissions = $_POST['permissions'] ?? [];

        $pdo->beginTransaction();

        // 1. حذف الصلاحيات التي تم إلغاء تحديدها
        $to_delete = array_diff($assigned_permissions, $selected_permissions);
        if (!empty($to_delete)) {
            $placeholders = implode(',', array_fill(0, count($to_delete), '?'));
            // تصحيح اسم الجدول والحقول: user_permision و role_id
            $stmt = $pdo->prepare("DELETE FROM user_permision WHERE user_id = ? AND role_id IN ($placeholders)");
            $stmt->execute(array_merge([$user_id], $to_delete));
        }

        // 2. إضافة الصلاحيات الجديدة
        $to_add = array_diff($selected_permissions, $assigned_permissions);
        if (!empty($to_add)) {
            // تصحيح اسم الجدول والحقول: user_permision و role_id
            $stmt = $pdo->prepare("INSERT INTO user_permision (user_id, role_id) VALUES (?, ?)");
            foreach ($to_add as $role_id) {
                $stmt->execute([$user_id, $role_id]);
            }
        }

        // ... داخل كود معالجة الحفظ بعد الـ commit ...
        $pdo->commit();

        $_SESSION['success_msg'] = "تم تحديث صلاحيات المستخدم بنجاح!";
        header("Location: " . $_SERVER['PHP_SELF'] . "?user_id=" . $user_id);
        exit;
    } catch (Exception $e) {
        $pdo->rollBack();
        echo "خطأ في التحديث: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Show-Users</title>
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
    <!-- DataTables -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap4.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.bootstrap4.min.css">
    <style>
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

        .dataTables_filter {
            text-align: right !important;
        }

        .dataTables_filter input {
            width: 30%;
            /* غيّر الرقم كما تريد */
            border-radius: 20px;
            padding: 5px 15px;
        }
    </style>
    </style>
</head>

<body class="hold-transition sidebar-mini layout-fixed">
    <div class="wrapper">


        <?php include(__DIR__ . '/../../main-header.php'); ?>

        <!-- /.navbar -->


        <?php include(__DIR__ . '/../../main-sidebar.php'); ?>


        <!-- Content Wrapper. Contains page content -->
        <div class="content-wrapper">
            <section class="content-header">
                <div class="container-fluid">
                    <div class="row mb-2">
                        <div class="col-sm-6">
                            <h1>تعيين صلاحيات المستخدمين</h1>
                        </div>
                        <div class="col-sm-6">
                            <ol class="breadcrumb float-sm-right">
                                <li class="breadcrumb-item"><a href="../../index.php">الرئيسية</a></li>
                                <li class="breadcrumb-item active">تعيين الصلاحيات</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </section>

            <section class="content">
                <div class="row">
                    <div class="col-12">
                        <div class="card card-primary">
                            <div class="card-header">
                                <h3 class="card-title">اختر المستخدم والصلاحيات</h3>
                            </div>

                            <div class="card-body">
                                <form method="POST">
                                    <!-- اختيار المستخدم -->
                                    <div class="form-group">
                                        <label>اختر المستخدم</label>
                                        <select name="user_id" class="form-control" onchange="this.form.submit()">
                                            <option value="">-- اختر مستخدم --</option>
                                            <?php foreach ($users as $user) : ?>
                                                <option value="<?= $user['id'] ?>"
                                                    <?= (isset($_POST['user_id']) && $_POST['user_id'] == $user['id']) ? 'selected' : '' ?>>
                                                    <?= $user['full_name'] ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <!-- عرض الصلاحيات -->
                                    <?php if (isset($_POST['user_id']) && $_POST['user_id'] != "") : ?>
                                        <div class="form-group">
                                            <label>الصلاحيات (حدد أو ألغِ تحديد أي صلاحية)</label>
                                            <div class="row">
                                                <?php foreach ($permissions as $perm) : ?>
                                                    <div class="col-md-3">
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="checkbox" name="permissions[]"
                                                                value="<?= $perm['role_id'] ?>" id="perm<?= $perm['role_id'] ?>"
                                                                <?= in_array($perm['role_id'], $assigned_permissions) ? 'checked' : '' ?>>
                                                            <label class="form-check-label" for="perm<?= $perm['role_id'] ?>">
                                                                <?= $perm['role_name'] ?>
                                                            </label>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>

                                        <div class="form-group">
                                            <button type="submit" name="save_permissions" class="btn btn-success">
                                                حفظ التعيين
                                            </button>
                                        </div>
                                    <?php endif; ?>
                                </form>
                            </div>

                        </div>
                    </div>
                </div>
            </section>
        </div>


        <div class="modal fade" id="successModal" tabindex="-1" role="dialog" aria-labelledby="successModalLabel"
            aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content">
                    <div class="modal-header bg-success text-white">
                        <h5 class="modal-title" id="successModalLabel">تم بنجاح</h5>
                        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body text-center">
                        <i class="fas fa-check-circle fa-4x text-success mb-3"></i>
                        <p id="modalMessage"><?php echo $_SESSION['success_msg'] ?? ''; ?></p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">إغلاق</button>
                    </div>
                </div>
            </div>
        </div>
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
        <!-- DataTables -->
        <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
        <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap4.min.js"></script>

        <!-- Buttons Extension -->
        <script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
        <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.bootstrap4.min.js"></script>

        <!-- Export -->
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>

        <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
        <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.print.min.js"></script>
        <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.colVis.min.js"></script>
        <script>
            $("#example1").DataTable({
                responsive: false,
                lengthChange: false,
                autoWidth: false,
                searching: true,
                dom: '<"row"<"col-md-12"l><"col-md-12 text-right"f>>rtip',

                language: {
                    search: "بحث:",
                    lengthMenu: "عرض _MENU_ سجل",
                    info: "عرض _START_ إلى _END_ من _TOTAL_ سجل",
                    paginate: {
                        first: "الأول",
                        last: "الأخير",
                        next: "التالي",
                        previous: "السابق"
                    }
                }
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
            $(document).ready(function() {
                <?php if (isset($_SESSION['success_msg'])): ?>
                    // إظهار المودال
                    $('#successModal').modal('show');

                    // مسح الرسالة من الجلسة حتى لا تظهر مرة أخرى عند تحديث الصفحة
                    <?php unset($_SESSION['success_msg']); ?>
                <?php endif; ?>
            });
        </script>
</body>

</html>