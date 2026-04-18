<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require __DIR__ . "/../../../config/db.php";

// استعلام يجلب البلاغات مع ربط الجداول لجلب الأسماء بدلاً من الـ IDs
$sql = "SELECT r.*, 
               b.branch_name, 
               c.college_name, 
               g.group_name 
        FROM requests r
        LEFT JOIN branch b ON r.branch_id = b.id
        LEFT JOIN colleges c ON r.college_id = c.id
        LEFT JOIN groups g ON r.issue_type_id = g.id
        ORDER BY r.created_at DESC";

$requests = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

// معالجة حذف بلاغ
if (isset($_GET['delete_id'])) {
    $delete_id = $_GET['delete_id'];
    $stmt = $pdo->prepare("DELETE FROM requests WHERE id=?");
    $stmt->execute([$delete_id]);

    $_SESSION['success_msg'] = "تم حذف البلاغ بنجاح";
    header("Location: show-requests.php");
    exit;
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

    .dataTables_filter input {
        border-radius: 20px;
        padding: 5px 15px;
        border: 1px solid #ced4da;
        margin-right: 5px;
    }

    .dt-buttons .btn {
        margin-left: 5px;
    }

    /* تحسين استجابة الجدول على الشاشات الصغيرة */
    @media (max-width: 768px) {
        .card-body {
            padding: 0.5rem;
            /* تقليل الحواف في الجوال */
        }

        .table {
            font-size: 0.85rem;
            /* تصغير الخط قليلاً ليناسب المساحة */
        }

        .btn-sm {
            padding: 0.25rem 0.4rem;
            /* تصغير الأزرار */
        }
    }

    /* إضافة حاوية تسمح بالتمرير الأفقي بسلاسة */
    .table-responsive-custom {
        display: block;
        width: 100%;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }
    </style>
</head>

<body class="hold-transition sidebar-mini layout-fixed">
    <div class="wrapper">


        <?php include(__DIR__ . '/../../main-header.php'); ?>

        <!-- /.navbar -->


        <?php include(__DIR__ . '/../../main-sidebar.php'); ?>




        <!-- Main content -->
        <!-- Content Wrapper -->
        <div class="content-wrapper">
            <section class="content-header">
                <div class="container-fluid">
                    <div class="row mb-2">
                        <div class="col-sm-6">
                            <h1>تقرير المستخدمين</h1>
                        </div>
                        <div class="col-sm-6">
                            <ol class="breadcrumb float-sm-right">
                                <li class="breadcrumb-item"><a href="../../index.php">الرئيسية</a></li>
                                <li class="breadcrumb-item active">تقارير المستخدمين</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Main content -->
            <section class="content">
                <div class="row">
                    <div class="col-12">
                        <!-- الجدول -->
                        <div class="card card-primary">
                            <div class="card-header breadcrumb float-sm-right">
                                <h3 class="card-title">جدول المستخدمين</h3>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive-custom">
                                    <table id="example1"
                                        class="table table-bordered table-hover text-center dt-responsive nowrap"
                                        style="width:100%">
                                        <thead>
                                            <tr>
                                                <th>رقم الطلب</th>
                                                <th>مقدم البلاغ</th>
                                                <th>الفرع/الكلية</th>
                                                <th>المكان</th>
                                                <th>نوع المشكلة</th>
                                                <th>الأولوية</th>
                                                <th>الحالة</th>

                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($requests as $req):
                                                // محاكاة تحويل الأولوية للعربي حسب الصور المرفقة
                                                $priority_ar = 'عادي';
                                                $p_class = 'badge-info';
                                                if ($req['priority'] == 'High' || $req['priority'] == 'Emergency') {
                                                    $priority_ar = 'طارئ (توقف محاضرة)';
                                                    $p_class = 'badge-danger';
                                                } elseif ($req['priority'] == 'Medium' || $req['priority'] == 'Medium') {
                                                    $priority_ar = 'متوسط';
                                                    $p_class = 'badge-warning';
                                                }

                                                // محاكاة تحويل الحالة للعربي
                                                $status_ar = ($req['status'] == 'Pending') ? 'قيد الانتظار' : 'تم الإصلاح';
                                                $status_class = ($req['status'] == 'Pending') ? 'badge-secondary' : 'badge-success';
                                            ?>
                                            <tr>
                                                <td><?= $req['id']; ?></td>
                                                <td><?= htmlspecialchars($req['user_id_number']) ?></td>
                                                <td>
                                                    <small>
                                                        <?= htmlspecialchars($req['branch_name'] ?? 'غير محدد') ?> <br>
                                                        <?= htmlspecialchars($req['college_name'] ?? 'غير محدد') ?>
                                                    </small>
                                                </td>
                                                <td><?= htmlspecialchars($req['location_name']) ?></td>
                                                <td>
                                                    <span class="badge badge-dark">
                                                        <?= htmlspecialchars($req['group_name'] ?? 'غير محدد') ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge <?= $p_class ?>"><?= $priority_ar ?></span>
                                                </td>
                                                <td>
                                                    <span class="badge <?= $status_class ?>"><?= $status_ar ?></span>
                                                </td>

                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
            </section>
        </div>

        <!-- Footer -->
        <footer class="main-footer">
            <?php include(__DIR__ . '/../../main-footer.php'); ?>
        </footer>
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
    $(function() {
        $("#example1").DataTable({
            responsive: true,
            lengthChange: true,
            autoWidth: false,
            searching: true,
            dom: "<'row mb-3'<'col-md-6'B><'col-md-6 text-left'f>>" +
                "<'row'<'col-12'tr>>" +
                "<'row mt-3'<'col-md-5'i><'col-md-7'p>>",

            buttons: [{
                    extend: 'colvis',
                    text: 'إظهار / إخفاء الأعمدة',
                    className: 'btn btn-secondary'
                },
                {
                    extend: 'excel',
                    text: 'تصدير Excel',
                    className: 'btn btn-success'
                },
                {
                    extend: 'pdf',
                    text: 'تصدير PDF',
                    className: 'btn btn-danger'
                },
                {
                    extend: 'print',
                    text: 'طباعة',
                    className: 'btn btn-primary'
                }
            ],

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
    <script>
    $(document).ready(function() {
        $('#reportTable').DataTable({
            responsive: true,
            lengthChange: true,
            autoWidth: false,
            searching: true,
            paging: true,
            pageLength: 5, // عدد السجلات لكل صفحة
            dom: "<'row mb-3'<'col-md-6'B><'col-md-6 text-left'f>>" +
                "<'row'<'col-12'tr>>" +
                "<'row mt-3'<'col-md-5'i><'col-md-7'p>>",
            buttons: [{
                    extend: 'colvis',
                    text: 'إظهار / إخفاء الأعمدة',
                    className: 'btn btn-secondary'
                },
                {
                    extend: 'excel',
                    text: 'تصدير Excel',
                    className: 'btn btn-success'
                },
                {
                    extend: 'pdf',
                    text: 'تصدير PDF',
                    className: 'btn btn-danger'
                },
                {
                    extend: 'print',
                    text: 'طباعة',
                    className: 'btn btn-primary'
                }
            ],
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
    });
    </script>
</body>

</html>