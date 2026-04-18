<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require __DIR__ . "/../config/db.php";
$systemStmt = $pdo->query("SELECT * FROM system_settings LIMIT 1"); // افترضنا اسم الجدول settings
$systemData = $systemStmt->fetch(PDO::FETCH_ASSOC);

// جلب إجمالي المستخدمين
$stmtUsers = $pdo->query("SELECT COUNT(*) FROM users");
$totalUsers = $stmtUsers->fetchColumn();

// جلب إجمالي الطلبات (بناءً على جدول tickets أو requests في الصورة)
$stmtTickets = $pdo->query("SELECT COUNT(*) FROM tickets");
$totalTickets = $stmtTickets->fetchColumn();

// جلب إجمالي الكليات أو المعامل (حسب اختيارك للصناديق)
$stmtColleges = $pdo->query("SELECT COUNT(*) FROM colleges");
$totalColleges = $stmtColleges->fetchColumn();

// مثال لحساب نسبة معينة (مثلاً البلاغات المكتملة)
$stmtClosed = $pdo->query("SELECT COUNT(*) FROM tickets WHERE status = 'closed'");
$closedCount = $stmtClosed->fetchColumn();
$rate = ($totalTickets > 0) ? round(($closedCount / $totalTickets) * 100) : 0;

// جلب السجلات من الأحدث إلى الأقدم
try {
    $stmt = $pdo->query("SELECT * FROM system_logs ORDER BY created_at DESC");
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $logs = [];
    $error = "خطأ في قاعدة البيانات: " . $e->getMessage();
}
// دالة ذكية لجلب التاريخ الهجري بأمان
function get_hijri_date()
{
    if (class_exists('IntlDateFormatter')) {
        $fmt = new IntlDateFormatter('ar_SA@calendar=islamic-uma', IntlDateFormatter::FULL, IntlDateFormatter::NONE, 'Asia/Riyadh', IntlDateFormatter::TRADITIONAL);
        return $fmt->format(new DateTime());
    }
    return "يرجى تفعيل إضافة intl لرؤية التاريخ الهجري";
}
// استعلام لجلب عدد البلاغات/الطلبات حسب الحالة (كمثال)
$stmtChart = $pdo->query("SELECT status, COUNT(*) as count FROM tickets GROUP BY status");
$chartLabels = [];
$chartCounts = [];

while ($row = $stmtChart->fetch(PDO::FETCH_ASSOC)) {
    $chartLabels[] = $row['status']; // مثل: جديد، قيد المعالجة، مكتمل
    $chartCounts[] = $row['count'];
}

// تحويل المصفوفات إلى صيغة JSON ليقرأها الجافا سكربت
$jsLabels = json_encode($chartLabels);
$jsData = json_encode($chartCounts);
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Main-Admin</title>
    <!-- Tell the browser to be responsive to screen width -->
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="plugins/fontawesome-free/css/all.min.css">
    <!-- Ionicons -->
    <link rel="stylesheet" href="https://code.ionicframework.com/ionicons/2.0.1/css/ionicons.min.css">
    <!-- Tempusdominus Bbootstrap 4 -->
    <link rel="stylesheet" href="plugins/tempusdominus-bootstrap-4/css/tempusdominus-bootstrap-4.min.css">
    <!-- iCheck -->
    <link rel="stylesheet" href="plugins/icheck-bootstrap/icheck-bootstrap.min.css">
    <!-- JQVMap -->
    <link rel="stylesheet" href="plugins/jqvmap/jqvmap.min.css">
    <!-- Theme style -->
    <link rel="stylesheet" href="dist/css/adminlte.min.css">
    <!-- overlayScrollbars -->
    <link rel="stylesheet" href="plugins/overlayScrollbars/css/OverlayScrollbars.min.css">
    <!-- Daterange picker -->
    <link rel="stylesheet" href="plugins/daterangepicker/daterangepicker.css">
    <!-- summernote -->
    <link rel="stylesheet" href="plugins/summernote/summernote-bs4.css">
    <!-- Google Font: Source Sans Pro -->
    <link href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700" rel="stylesheet">
    <!-- Bootstrap 4 RTL -->
    <link rel="stylesheet" href="https://cdn.rtlcss.com/bootstrap/v4.2.1/css/bootstrap.min.css">
    <!-- Custom style for RTL -->
    <link rel="stylesheet" href="dist/css/custom.css">
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

    .calendar-card {
        border: none;
        border-radius: 15px;
        overflow: hidden;
        background: #fff;
    }

    .calendar-header {
        background: linear-gradient(45deg, #038ed3, #024b70);
        padding: 20px;
        color: white;
    }

    .calendar-info {
        display: flex;
        align-items: center;
    }

    .calendar-icon {
        font-size: 2.5rem;
        margin-left: 15px;
        opacity: 0.8;
    }

    .date-titles h3 {
        margin: 0;
        font-size: 1.2rem;
    }

    .sub-title {
        font-size: 0.8rem;
        opacity: 0.7;
    }

    .date-display {
        display: flex;
        justify-content: space-around;
        align-items: center;
        padding: 20px;
        background: #f8f9fa;
        border-bottom: 1px solid #eee;
        text-align: center;
    }

    .miladi-box .day-name {
        display: block;
        color: #038ed3;
        font-weight: bold;
        text-transform: uppercase;
    }

    .miladi-box .day-num {
        display: block;
        font-size: 2.2rem;
        font-weight: 800;
        line-height: 1;
        color: #333;
    }

    .miladi-box .month-year {
        font-size: 0.9rem;
        color: #777;
    }

    .divider {
        width: 1px;
        height: 50px;
        background: #ddd;
    }

    .hijri-box .hijri-text {
        display: block;
        font-size: 1.1rem;
        color: #28a745;
        font-weight: bold;
        margin-bottom: 5px;
    }

    .hijri-box .hijri-label {
        font-size: 0.8rem;
        color: #999;
        background: #e9ecef;
        padding: 2px 10px;
        border-radius: 10px;
    }

    /* تحسين شكل أرقام التقويم الافتراضي */
    calendar .fc-header-toolbar {
        padding: 10px;
    }

    /* تحسينات إضافية للخطوط */
    .font-weight-bold {
        font-weight: 700 !important;
    }

    .card {
        transition: transform 0.3s;
    }

    .card:hover {
        transform: translateY(-5px);
    }
    </style>
</head>

<body class="hold-transition sidebar-mini layout-fixed">
    <div class="wrapper">

        <!-- Navbar -->
        <?php include(__DIR__ . '/../admin/main-header.php'); ?>
        <!-- /.navbar -->

        <!-- Main Sidebar Container -->
        <?php include(__DIR__ . '/../admin/main-sidebar.php'); ?>

        <!-- Content Wrapper. Contains page content -->
        <div class="content-wrapper">
            <!-- Content Header (Page header) -->
            <div class="content-header">
                <div class="container-fluid">
                    <div class="row mb-2">

                        <div class="col-sm-6">

                        </div><!-- /.col -->
                    </div><!-- /.row -->
                </div><!-- /.container-fluid -->
            </div>
            <!-- /.content-header -->

            <!-- Main content -->
            <section class="content">
                <div class="container-fluid">
                    <!-- Small boxes (Stat box) -->
                    <div class="row">
                        <div class="col-lg-3 col-6">
                            <div class="small-box bg-info" style="background-color: #f39c12 !important;">
                                <div class="inner">
                                    <h3><?php echo $totalUsers; ?></h3>
                                    <p>المستخدمين المسجلين</p>
                                </div>
                                <div class="icon"><i class="fa fa-users"></i></div>
                                <a href="/crm/admin/pages/tables/show-users.php" class=" small-box-footer">تفاصيل أكثر
                                    <i class="fa fa-arrow-circle-left"></i></a>
                            </div>
                        </div>

                        <div class="col-lg-3 col-6">
                            <div class="small-box bg-success" style="background-color: #00c0ef !important;">
                                <div class="inner">
                                    <h3><?php echo $totalTickets; ?></h3>
                                    <p>إجمالي البلاغات</p>
                                </div>
                                <div class="icon"><i class="fa fa-shopping-cart"></i></div>
                                <a href="/crm/admin/pages/tables/report-tickets.php" class="small-box-footer">تفاصيل
                                    أكثر <i class="fa fa-arrow-circle-left"></i></a>
                            </div>
                        </div>

                        <div class="col-lg-3 col-6">
                            <div class="small-box bg-warning" style="background-color: #dd4b39 !important;">
                                <div class="inner">
                                    <h3><?php echo $totalColleges; ?></h3>
                                    <p>عدد الكليات</p>
                                </div>
                                <div class="icon"><i class="fa fa-university"></i></div>
                                <a href="/crm/admin/pages/forms/add-college.php" class="small-box-footer">تفاصيل أكثر <i
                                        class="fa fa-arrow-circle-left"></i></a>
                            </div>
                        </div>

                        <div class="col-lg-3 col-6">
                            <div class="small-box bg-danger" style="background-color: #00a65a !important;">
                                <div class="inner">
                                    <h3><?php echo $rate; ?><sup style="font-size: 20px">%</sup></h3>
                                    <p>نسبة الإنجاز</p>
                                </div>
                                <div class="icon"><i class="fa fa-chart-pie"></i></div>
                                <a href="#" class="small-box-footer">المزيد <i class="fa fa-arrow-circle-left"></i></a>
                            </div>
                        </div>
                    </div>
                    <!-- /.row -->
                    <!-- Main row -->
                    <div class="row">
                        <section class="col-lg-6">
                            <div class="card shadow-lg border-0" style="border-radius: 0px; overflow: hidden;">
                                <div class="card-header text-white text-center"
                                    style="background: linear-gradient(135deg, #038ed3 0%, #024b70 100%); padding: 30px;">
                                    <i class="fas fa-calendar-check fa-3x mb-3"></i>
                                    <h4 class="mb-0 text-bold">أجندة اليوم</h4>
                                </div>

                                <div class="card-body p-0">
                                    <div
                                        class="d-flex align-items-center justify-content-between p-4 border-bottom bg-light">
                                        <div class="text-right">
                                            <P class="text-primary mb-1 font-weight-bold"><?php echo date('l'); ?></P>
                                            <p class="text-muted mb-0"><?php echo date('d M Y'); ?> م</p>
                                        </div>
                                        <div class="display-4 font-weight-bold text-secondary" style="opacity: 0.2;">
                                            <?php echo date('d'); ?>
                                        </div>
                                    </div>

                                    <div class="p-4 text-center" style="background: #fdfdfd;">
                                        <div class="badge badge-success px-3 py-2 mb-2"
                                            style="font-size: 14px; border-radius: 50px;">
                                            <i class="fa fa-moon mr-1"></i> التاريخ الهجري
                                        </div>
                                        <h5 class="text-dark font-weight-bold" style="line-height: 1.6;">
                                            <?php echo get_hijri_date(); ?>
                                        </h5>
                                    </div>
                                </div>

                                <div class="card-footer bg-white text-center py-3">
                                    <small class="text-muted">نظام الإدارة الذكي - <?php echo date('Y'); ?></small>
                                </div>
                            </div>
                        </section>
                        <div class="col-lg-6">
                            <div class="card shadow-lg border-0" style="border-radius: 20px;">
                                <div class="card-header bg-white py-3">
                                    <h5 class="card-title text-bold mb-0">
                                        <i class="fas fa-chart-bar text-primary mr-2"></i> إحصائيات البلاغات
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <canvas id="mainDashboardChart"
                                        style="min-height: 310px; height: 310px; max-height: 310px; max-width: 100%;"></canvas>
                                </div>
                                <div class="card-footer bg-white">
                                    <small class="text-muted"><i class="fa fa-sync-alt"></i> يتم التحديث تلقائياً من
                                        قاعدة البيانات</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- /.row (main row) -->
                </div><!-- /.container-fluid -->
            </section>
            <!-- /.content -->
        </div>
        <!-- /.content-wrapper -->
        <footer class="main-footer">
            <?php include('main-footer.php') ?>
        </footer>

        <!-- Control Sidebar -->
        <aside class="control-sidebar control-sidebar-dark">
            <!-- Control sidebar content goes here -->
        </aside>
        <!-- /.control-sidebar -->
    </div>
    <!-- ./wrapper -->

    <!-- jQuery -->
    <script src="plugins/jquery/jquery.min.js"></script>
    <!-- jQuery UI 1.11.4 -->
    <script src="plugins/jquery-ui/jquery-ui.min.js"></script>
    <!-- Resolve conflict in jQuery UI tooltip with Bootstrap tooltip -->
    <script>
    $.widget.bridge('uibutton', $.ui.button)
    </script>
    <!-- Bootstrap 4 rtl -->
    <script src="https://cdn.rtlcss.com/bootstrap/v4.2.1/js/bootstrap.min.js"></script>
    <!-- Bootstrap 4 -->
    <script src="plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
    <!-- ChartJS -->
    <script src="plugins/chart.js/Chart.min.js"></script>
    <!-- Sparkline -->
    <script src="plugins/sparklines/sparkline.js"></script>
    <!-- JQVMap -->
    <script src="plugins/jqvmap/jquery.vmap.min.js"></script>
    <script src="plugins/jqvmap/maps/jquery.vmap.world.js"></script>
    <!-- jQuery Knob Chart -->
    <script src="plugins/jquery-knob/jquery.knob.min.js"></script>
    <!-- daterangepicker -->
    <script src="plugins/moment/moment.min.js"></script>
    <script src="plugins/daterangepicker/daterangepicker.js"></script>
    <!-- Tempusdominus Bootstrap 4 -->
    <script src="plugins/tempusdominus-bootstrap-4/js/tempusdominus-bootstrap-4.min.js"></script>
    <!-- Summernote -->
    <script src="plugins/summernote/summernote-bs4.min.js"></script>
    <!-- overlayScrollbars -->
    <script src="plugins/overlayScrollbars/js/jquery.overlayScrollbars.min.js"></script>
    <!-- AdminLTE App -->
    <script src="dist/js/adminlte.js"></script>
    <!-- AdminLTE dashboard demo (This is only for demo purposes) -->
    <script src="dist/js/pages/dashboard.js"></script>
    <!-- AdminLTE for demo purposes -->
    <script src="dist/js/demo.js"></script>
    <script>
    document.addEventListener("DOMContentLoaded", function() {
        var ctx = document.getElementById('mainDashboardChart').getContext('2d');

        var myChart = new Chart(ctx, {
            type: 'bar', // يمكنك تغييره إلى 'line' أو 'doughnut'
            data: {
                labels: <?php echo $jsLabels; ?>,
                datasets: [{
                    label: 'عدد الطلبات',
                    data: <?php echo $jsData; ?>,
                    backgroundColor: [
                        'rgba(3, 142, 211, 0.7)',
                        'rgba(40, 167, 69, 0.7)',
                        'rgba(255, 193, 7, 0.7)',
                        'rgba(220, 53, 69, 0.7)'
                    ],
                    borderColor: [
                        '#038ed3',
                        '#28a745',
                        '#ffc107',
                        '#dc3545'
                    ],
                    borderWidth: 2,
                    borderRadius: 10 // جعل حواف الأعمدة دائرية
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    } // إخفاء وسيلة الإيضاح العلوية
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            display: false
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
    });
    </script>
</body>

</html>