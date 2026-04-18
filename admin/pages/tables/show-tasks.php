<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require __DIR__ . "/../../../config/db.php";

// استعلام يجلب البلاغات مع ربط الجداول لجلب الأسماء بدلاً من الـ IDs
// استعلام يجلب المهام المسندة مع كافة التفاصيل المرتبطة
$sql = "SELECT t.*, 
               r.user_id_number, r.location_name, r.details as req_details,
               b.branch_name, 
               c.college_name, 
               g.group_name,
               u.full_name as technician_name
        FROM tasks t
        LEFT JOIN requests r ON t.request_id = r.id
        LEFT JOIN branch b ON r.branch_id = b.id
        LEFT JOIN colleges c ON r.college_id = c.id
        LEFT JOIN groups g ON r.issue_type_id = g.id
        LEFT JOIN users u ON t.assigned_to = u.id
        ORDER BY t.created_at DESC";

$tasks = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

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
                                                <th>رقم المهمة</th>
                                                <th>المهندس المسند له</th>
                                                <th>الفرع/الكلية</th>
                                                <th>نوع المشكلة</th>
                                                <th>الأولوية</th>
                                                <th>الحالة</th>
                                                <th>تاريخ التسليم</th>
                                                <th>تعديل</th>
                                                <!-- <th>حذف</th> -->
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($tasks as $task):
                                                // تنسيق الأولوية
                                                $priority_map = [
                                                    'Critical' => ['text' => 'حرج جداً', 'class' => 'badge-danger'],
                                                    'High'     => ['text' => 'عالي', 'class' => 'badge-warning'],
                                                    'Medium'   => ['text' => 'متوسط', 'class' => 'badge-info'],
                                                    'Normal'   => ['text' => 'عادي', 'class' => 'badge-secondary']
                                                ];
                                                $p_attr = $priority_map[$task['priority']] ?? $priority_map['Normal'];

                                                // تنسيق الحالة
                                                $status_map = [
                                                    'Pending'     => ['text' => 'قيد الانتظار', 'class' => 'badge-secondary'],
                                                    'In Progress' => ['text' => 'قيد التنفيذ', 'class' => 'badge-primary'],
                                                    'Completed'   => ['text' => 'تم الإنجاز', 'class' => 'badge-success']
                                                ];
                                                $s_attr = $status_map[$task['status']] ?? $status_map['Pending'];
                                            ?>
                                                <tr>
                                                    <td><?= $task['id']; ?></td>
                                                    <td>
                                                        <b
                                                            class="text-primary"><?= htmlspecialchars($task['technician_name'] ?? 'غير محدد') ?></b>
                                                    </td>
                                                    <td>
                                                        <small>
                                                            <?= htmlspecialchars($task['branch_name'] ?? '---') ?> <br>
                                                            <?= htmlspecialchars($task['college_name'] ?? '---') ?>
                                                        </small>
                                                    </td>
                                                    <td>
                                                        <span class="badge badge-dark">
                                                            <?= htmlspecialchars($task['group_name'] ?? 'عام') ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <span
                                                            class="badge <?= $p_attr['class'] ?>"><?= $p_attr['text'] ?></span>
                                                    </td>
                                                    <td>
                                                        <span
                                                            class="badge <?= $s_attr['class'] ?>"><?= $s_attr['text'] ?></span>
                                                    </td>
                                                    <td>
                                                        <small><?= date('Y-m-d H:i', strtotime($task['deadline'])) ?></small>
                                                    </td>
                                                    <td>
                                                        <a class="btn btn-sm" style="background-color:#ffc107; color:black"
                                                            href="edit-task.php?id=<?= $task['request_id'] ?>">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                    </td>
                                                    <!-- <td>
                                                    <a href="#" class="btn btn-danger btn-sm delete-btn"
                                                        data-id="<?= $task['id'] ?>">
                                                        <i class="fas fa-trash"></i>
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
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                // التحقق من وجود رسالة مخزنة في الـ sessionStorage
                const title = sessionStorage.getItem('swal_title');
                const text = sessionStorage.getItem('swal_text');
                const icon = sessionStorage.getItem('swal_icon');

                if (title && text && icon) {
                    Swal.fire({
                        title: title,
                        text: text,
                        icon: icon,
                        confirmButtonText: 'حسناً',
                        confirmButtonColor: '#ffc107' // لون يتناسب مع طابع الـ warning في نظامك
                    });

                    // حذف البيانات من الذاكرة لكي لا تظهر الرسالة مرة أخرى عند تحديث الصفحة
                    sessionStorage.removeItem('swal_title');
                    sessionStorage.removeItem('swal_text');
                    sessionStorage.removeItem('swal_icon');
                }
            });
        </script>
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