<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require __DIR__ . "/../../../config/db.php";

if (isset($_POST['add_group'])) {
    $group_name = trim($_POST['group_name']);

    if (!empty($group_name)) {
        // 1. التحقق من وجود الاسم مسبقاً
        $checkSql = "SELECT COUNT(*) FROM groups WHERE group_name = ?";
        $checkStmt = $pdo->prepare($checkSql);
        $checkStmt->execute([$group_name]);
        $exists = $checkStmt->fetchColumn();

        if ($exists > 0) {
            // استخدام SweetAlert للخطأ الفوري
            echo "<script>
                document.addEventListener('DOMContentLoaded', function() {
                    Swal.fire({
                        icon: 'error',
                        title: 'عذراً...',
                        text: 'اسم المجموعة هذه موجود مسبقاً!',
                        confirmButtonText: 'حسناً'
                    });
                });
            </script>";
        } else {
            // 2. الإدخال في قاعدة البيانات
            $sql = "INSERT INTO GROUPS (group_name) VALUES (?)";
            $stmt = $pdo->prepare($sql);
            if ($stmt->execute([$group_name])) {
                // تخزين النجاح في sessionStorage والتحويل
                echo "<script>
                    sessionStorage.setItem('swal_title', 'تمت العملية!');
                    sessionStorage.setItem('swal_text', 'تم إضافة المجموعة بنجاح');
                    sessionStorage.setItem('swal_icon', 'success');
                    window.location.href = '../forms/add-group.php'; 
                </script>";
            }
        }
    } else {
        echo "<script>alert('الرجاء تعبئة اسم المجموعة ');</script>";
    }
}
// 2. جلب كافة الفروع لعرضها في الكارد الأسفل
$groupsSql = "SELECT * FROM groups ORDER BY id asc";
$groupsStmt = $pdo->query($groupsSql);
$allgroups = $groupsStmt->fetchAll();
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
                            <h1> بيانات المجموعات</h1>
                        </div>

                        <div class="col-sm-6">
                            <ol class="breadcrumb float-sm-right">
                                <li class="breadcrumb-item"><a href="../../index.php">الرئيسية</a></li>
                                <li class="breadcrumb-item active">المجموعات</li>
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
                                <h3 class="card-title">إضافة مجموعة جديدة</h3>
                            </div>
                            <div class="card-body">

                                <form method="POST">

                                    <div class="form-group">
                                        <label>اسم المجموعة</label>
                                        <input type="text" name="group_name" class="form-control"
                                            placeholder="Enter group name" required>
                                    </div>



                                    <div clasbs="form-group">
                                        <button type="submit" name="add_group" class="btn btn-primary">
                                            إضافة مجموعة
                                        </button>
                                    </div>

                                </form>

                            </div>

                        </div>

                    </div>
                </div>
                <div class="card card-info mt-4">
                    <div class="card-header">
                        <h3 class="card-title float-right">قائمة المجموعات المسجلة</h3>
                        <div class="card-tools float-left">
                            <div class="input-group input-group-sm" style="width: 200px;">
                                <input type="text" id="tableSearch" class="form-control"
                                    placeholder="بحث عن المجموعات...">
                                <div class="input-group-append">
                                    <button type="submit" class="btn btn-default">
                                        <i class="fas fa-search"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped text-center m-0">
                                <thead>
                                    <tr>
                                        <th style="width: 10%">#ID</th>
                                        <th>اسم المجموعات</th>
                                        <th style="width: 20%">الإجراءات</th>
                                    </tr>
                                </thead>
                                <tbody id="branchesTable">
                                    <?php if (count($allgroups) > 0): ?>
                                        <?php $counter = 1;
                                        foreach ($allgroups as $row): ?>
                                            <tr>
                                                <td><?= $counter++; ?></td>
                                                <td><?= htmlspecialchars($row['group_name']) ?></td>
                                                <td class="py-2">
                                                    <div class="btn-group">
                                                        <button class="btn btn-sm btn-warning ml-1 edit-btn"
                                                            data-id="<?= $row['id'] ?>"
                                                            data-name="<?= htmlspecialchars($row['group_name']) ?>"
                                                            data-toggle="modal" data-target="#editModal">
                                                            <i class="fas fa-edit"></i>
                                                        </button>

                                                        <button class="btn btn-sm btn-danger delete-btn"
                                                            data-id="<?= $row['id'] ?>"
                                                            data-name="<?= htmlspecialchars($row['group_name']) ?>"
                                                            data-toggle="modal" data-target="#deleteModal">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="3" class="text-muted">لا توجد فروع مضافة حالياً</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <script>
                    document.getElementById('tableSearch').addEventListener('keyup', function() {
                        let filter = this.value.toLowerCase();
                        let rows = document.querySelectorAll('#branchesTable tr');

                        rows.forEach(row => {
                            let text = row.textContent.toLowerCase();
                            row.style.display = text.includes(filter) ? '' : 'none';
                        });
                    });
                </script>
        </div>
        </section>

    </div>

    <footer class="main-footer">
        <?php include('../../main-footer.php') ?>
    </footer>

    <div class="modal fade" id="deleteModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form action="edit-group.php" method="POST">
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title">تأكيد الحذف</h5>
                        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body text-right">
                        هل أنت متأكد من حذف المجموعة: <strong id="del_name" class="text-danger"></strong>؟
                        <p class="small text-muted mt-2">ملاحظة: لا يمكن التراجع عن هذا الإجراء.</p>
                    </div>
                    <input type="hidden" name="group_id" id="del_id">
                    <input type="hidden" name="action" value="delete">
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">إلغاء</button>
                        <button type="submit" class="btn btn-danger">تأكيد الحذف نهائياً</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="editModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border: none; border-radius: 8px;">
                <form action="edit-group.php" method="POST">
                    <div class="modal-header bg-warning text-dark"
                        style="direction: rtl; display: flex; justify-content: space-between; align-items: center;">
                        <h5 class="modal-title font-weight-bold" style="margin: 0;">تعديل بيانات المجموعة</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"
                            style="margin: 0; padding: 0;">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>

                    <div class="modal-body" style="direction: rtl; text-align: right; padding: 20px;">
                        <div class="form-group">
                            <label style="font-weight: bold; display: block; margin-bottom: 8px;">اسم المجموعة
                                الجديد</label>
                            <input type="text" name="group_name" id="edit_name" class="form-control"
                                style="text-align: right;" placeholder="أدخل الاسم الجديد هنا..." required>
                        </div>
                    </div>

                    <input type="hidden" name="group_id" id="edit_id">
                    <input type="hidden" name="action" value="update">

                    <div class="modal-footer"
                        style="direction: rtl; display: flex; justify-content: space-between; border-top: 1px solid #eee; padding: 15px;">
                        <button type="submit" class="btn btn-warning font-weight-bold"
                            style="background-color: #ffb300; border: none; padding: 8px 25px; border-radius: 5px;">
                            حفظ التغييرات
                        </button>
                        <button type="button" class="btn btn-secondary" data-dismiss="modal"
                            style="padding: 8px 25px; border-radius: 5px;">
                            إغلاق
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>


    <script src="../../plugins/jquery/jquery.min.js"></script>
    <script src="../../plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../../dist/js/adminlte.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        $(document).ready(function() {
            // --- أولاً: معالجة بيانات الموديلات (نقل البيانات من الجدول للمودال) ---

            // عند الضغط على زر التعديل
            $('.edit-btn').on('click', function() {
                const id = $(this).data('id');
                const name = $(this).data('name');

                // التأكد من مطابقة هذه الـ IDs لما هو موجود في مودال التعديل
                $('#edit_id').val(id);
                $('#edit_name').val(name);
            });

            // عند الضغط على زر الحذف
            $('.delete-btn').on('click', function() {
                const id = $(this).data('id');
                const name = $(this).data('name');

                // التأكد من مطابقة هذه الـ IDs لما هو موجود في مودال الحذف
                $('#del_id').val(id);
                $('#del_name').text(name);
            });

            // --- ثانياً: محرك البحث السريع ---
            $('#tableSearch').on('keyup', function() {
                let value = $(this).val().toLowerCase();
                $("#groupsTable tr").filter(function() {
                    $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
                });
            });

            // --- ثالثاً: عرض رسائل SweetAlert ---
            const swalTitle = sessionStorage.getItem('swal_title');
            if (swalTitle) {
                Swal.fire({
                    title: swalTitle,
                    text: sessionStorage.getItem('swal_text'),
                    icon: sessionStorage.getItem('swal_icon'),
                    confirmButtonText: 'موافق',
                    customClass: {
                        confirmButton: 'btn btn-primary'
                    },
                    buttonsStyling: false
                });
                // تنظيف البيانات بعد العرض
                sessionStorage.removeItem('swal_title');
                sessionStorage.removeItem('swal_text');
                sessionStorage.removeItem('swal_icon');
            }
        });

        // --- رابعاً: معالجة تحديث الصفحة لمرة واحدة (اختياري) ---
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