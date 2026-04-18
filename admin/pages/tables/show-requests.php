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
    <title>عرض البلاغات</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="../../plugins/fontawesome-free/css/all.min.css">
    <link rel="stylesheet" href="../../dist/css/adminlte.min.css">
    <link rel="stylesheet" href="https://cdn.rtlcss.com/bootstrap/v4.2.1/css/bootstrap.min.css">
    <link rel="stylesheet" href="../../dist/css/custom.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap4.min.css">

    <style>
        /* الحفاظ على نفس استايل الصفحة الأصلي */
        html,
        body {
            overflow-x: hidden !important;
            scrollbar-width: none !important;
        }

        ::-webkit-scrollbar {
            display: none !important;
        }

        .dataTables_filter input {
            width: 30%;
            border-radius: 20px;
            padding: 5px 15px;
        }

        .table-responsive-custom {
            display: block;
            width: 100%;
            overflow-x: auto;
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
                            <h1>عرض تفاصيل البلاغات</h1>
                        </div>
                        <div class="col-sm-6">
                            <ol class="breadcrumb float-sm-right">
                                <li class="breadcrumb-item"><a href="../../index.php">الرئيسية</a></li>
                                <li class="breadcrumb-item active">البلاغات</li>
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
                                <a href="../forms/add-request.php" class="btn btn-primary btn-sm"
                                    style="font-weight: bold;">
                                    <i class="fas fa-plus"></i> إضافة بلاغ جديد
                                </a>
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
                                                <!-- <th>تعديل</th> -->
                                                <!-- <th>حذف</th> -->
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($requests as $req):
                                                // 1. منطق تحديد الأولوية (نص ولون)
                                                $priority_ar = 'عادي';
                                                $p_class = 'badge-info';
                                                if ($req['priority'] == 'High' || $req['priority'] == 'Emergency') {
                                                    $priority_ar = 'طارئ (توقف محاضرة)';
                                                    $p_class = 'badge-danger';
                                                } elseif ($req['priority'] == 'Medium') {
                                                    $priority_ar = 'متوسط';
                                                    $p_class = 'badge-warning';
                                                }

                                                // 2. منطق تحديد الحالة بناءً على طلبك الجديد
                                                if ($req['status'] == 'In Progress') {
                                                    $status_ar = 'قيد التنفيذ';
                                                    $status_class = 'badge-primary';
                                                } elseif ($req['status'] == 'Finished' || $req['status'] == 'Completed') {
                                                    $status_ar = 'تم الإصلاح';
                                                    $status_class = 'badge-success';
                                                } else {
                                                    // الحالة الافتراضية (Pending)
                                                    $status_ar = 'قيد الانتظار';
                                                    $status_class = 'badge-secondary';
                                                }
                                            ?>
                                                <tr>
                                                    <td><?= $req['id']; ?></td>

                                                    <td>
                                                        <div class="text-bold">
                                                            <?= htmlspecialchars($req['user_id_number']) ?></div>
                                                        <?php if (!empty($req['technician_name'])): ?>
                                                            <small class="text-primary"><i class="fas fa-tools mr-1"></i> الفني:
                                                                <?= htmlspecialchars($req['technician_name']) ?></small>
                                                        <?php endif; ?>
                                                    </td>

                                                    <td>
                                                        <small>
                                                            <strong>الفرع:</strong>
                                                            <?= htmlspecialchars($req['branch_name'] ?? 'غير محدد') ?> <br>
                                                            <strong>الكلية:</strong>
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

                                                    <!-- <td>
                                                    <a class="btn btn-sm" style="background-color:#ffc107; color:black"
                                                        href="edit-request.php?id=<?= $req['id'] ?>">
                                                        <i class="fas fa-edit"></i> <span
                                                            style="margin-right: 4px">تعديل</span>
                                                    </a>
                                                </td> -->

                                                    <!-- <td>
                                                    <a href="#" class="btn btn-danger btn-sm delete-btn"
                                                        data-id="<?= $req['id'] ?>">
                                                        <i class="fas fa-trash"></i> حذف
                                                    </a>
                                                </td> -->
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>

        <div class="modal fade" id="deleteModal" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title">تأكيد حذف البلاغ</h5>
                        <button type="button" class="close text-white"
                            data-dismiss="modal"><span>&times;</span></button>
                    </div>
                    <div class="modal-body text-right">هل أنت متأكد من حذف هذا البلاغ نهائياً؟</div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">إلغاء</button>
                        <a href="#" id="confirmDeleteBtn" class="btn btn-danger">حذف نهائي</a>
                    </div>
                </div>
            </div>
        </div>

        <script src="../../plugins/jquery/jquery.min.js"></script>
        <script src="../../plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
        <script src="../../dist/js/adminlte.js"></script>
        <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
        <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap4.min.js"></script>

        <script>
            $(document).ready(function() {
                $("#example1").DataTable({
                    responsive: true,
                    language: {
                        search: "بحث سريع:",
                        paginate: {
                            next: "التالي",
                            previous: "السابق"
                        }
                    }
                });

                // تفعيل حذف البلاغ
                $('.delete-btn').on('click', function(e) {
                    e.preventDefault();
                    var id = $(this).data('id');
                    $('#confirmDeleteBtn').attr('href', 'show-requests.php?delete_id=' + id);
                    $('#deleteModal').modal('show');
                });
            });
        </script>
    </div>
</body>

</html>