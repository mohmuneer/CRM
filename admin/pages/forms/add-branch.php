<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require __DIR__ . "/../../../config/db.php";

if (isset($_POST['add_branch'])) {
    $branch_name = trim($_POST['branch_name']);

    if (!empty($branch_name)) {
        // 1. التحقق من وجود الاسم مسبقاً
        $checkSql = "SELECT COUNT(*) FROM BRANCH WHERE branch_name = ?";
        $checkStmt = $pdo->prepare($checkSql);
        $checkStmt->execute([$branch_name]);
        $exists = $checkStmt->fetchColumn();

        if ($exists > 0) {
            // استخدام SweetAlert للخطأ الفوري
            echo "<script>
                document.addEventListener('DOMContentLoaded', function() {
                    Swal.fire({
                        icon: 'error',
                        title: 'عذراً...',
                        text: 'اسم الفرع هذا موجود مسبقاً!',
                        confirmButtonText: 'حسناً'
                    });
                });
            </script>";
        } else {
            // 2. الإدخال في قاعدة البيانات
            $sql = "INSERT INTO BRANCH (branch_name) VALUES (?)";
            $stmt = $pdo->prepare($sql);
            if ($stmt->execute([$branch_name])) {
                // تخزين النجاح في sessionStorage والتحويل
                echo "<script>
                    sessionStorage.setItem('swal_title', 'تمت العملية!');
                    sessionStorage.setItem('swal_text', 'تم إضافة الفرع بنجاح');
                    sessionStorage.setItem('swal_icon', 'success');
                    window.location.href = '../forms/add-branch.php'; 
                </script>";
            }
        }
    } else {
        echo "<script>alert('الرجاء تعبئة اسم الفرع');</script>";
    }
}
// 2. جلب كافة الفروع لعرضها في الكارد الأسفل
$branchesSql = "SELECT * FROM BRANCH ORDER BY id asc";
$branchesStmt = $pdo->query($branchesSql);
$allBranches = $branchesStmt->fetchAll();
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
                            <h1> بيانات الفروع</h1>
                        </div>

                        <div class="col-sm-6">
                            <ol class="breadcrumb float-sm-right">
                                <li class="breadcrumb-item"><a href="../../index.php">الرئيسية</a></li>
                                <li class="breadcrumb-item active">الفروع</li>
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
                                <h3 class="card-title">إضافة فرع جديد</h3>
                            </div>
                            <div class="card-body">

                                <form method="POST">

                                    <div class="form-group">
                                        <label>اسم الفرع</label>
                                        <input type="text" name="branch_name" class="form-control"
                                            placeholder="Enter branch name" required>
                                    </div>



                                    <div clasbs="form-group">
                                        <button type="submit" name="add_branch" class="btn btn-primary">
                                            إضافة فرع
                                        </button>
                                    </div>

                                </form>

                            </div>

                        </div>

                    </div>
                </div>
                <div class="card card-info mt-4">
                    <div class="card-header">
                        <h3 class="card-title float-right">قائمة الفروع المسجلة</h3>
                        <div class="card-tools float-left">
                            <div class="input-group input-group-sm" style="width: 200px;">
                                <input type="text" id="tableSearch" class="form-control" placeholder="بحث عن فرع...">
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
                                        <th>اسم الفرع</th>
                                        <th style="width: 20%">الإجراءات</th>
                                    </tr>
                                </thead>
                                <tbody id="branchesTable">
                                    <?php if (count($allBranches) > 0): ?>
                                        <?php $counter = 1;
                                        foreach ($allBranches as $row): ?>
                                            <tr>
                                                <td><?= $counter++; ?></td>
                                                <td><?= htmlspecialchars($row['branch_name']) ?></td>
                                                <td class="py-2">
                                                    <div class="btn-group">
                                                        <button class="btn btn-sm btn-warning ml-1 edit-branch-btn"
                                                            data-id="<?= $row['id'] ?>"
                                                            data-name="<?= htmlspecialchars($row['branch_name']) ?>"
                                                            data-toggle="modal" data-target="#editBranchModal">
                                                            <i class="fas fa-edit"></i>
                                                        </button>

                                                        <button class="btn btn-sm btn-danger delete-branch-btn"
                                                            data-id="<?= $row['id'] ?>"
                                                            data-name="<?= htmlspecialchars($row['branch_name']) ?>"
                                                            data-toggle="modal" data-target="#deleteBranchModal">
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

    <div class="modal fade" id="deleteBranchModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">تأكيد الحذف</h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form action="edit-branch.php" method="POST">
                    <div class="modal-body text-right">
                        هل أنت متأكد من حذف فرع: <strong id="del_branch_name"></strong>؟
                        <input type="hidden" name="branch_id" id="del_branch_id">
                        <input type="hidden" name="action" value="delete">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">إلغاء</button>
                        <button type="submit" class="btn btn-danger">حذف نهائي</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="editBranchModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header bg-warning">
                    <h5 class="modal-title font-weight-bold">تعديل بيانات الفرع</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form action="edit-branch.php" method="POST">
                    <div class="modal-body text-right">
                        <div class="form-group" style="text-align: right;">
                            <label>اسم الفرع</label>
                            <input type="text" name="branch_name" id="edit_branch_name" class="form-control" required>
                        </div>
                        <input type="hidden" name="branch_id" id="edit_branch_id">
                        <input type="hidden" name="action" value="update">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">إغلاق</button>
                        <button type="submit" class="btn btn-warning">حفظ التغييرات</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    </div>

    <script src="../../plugins/jquery/jquery.min.js"></script>
    <script src="../../plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../../dist/js/adminlte.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        $(document).ready(function() {
            // --- أولاً: معالجة بيانات الموديلات ---

            // عند الضغط على زر التعديل
            $('.edit-branch-btn').on('click', function() {
                const id = $(this).data('id');
                const name = $(this).data('name');
                $('#edit_branch_id').val(id);
                $('#edit_branch_name').val(name);
            });

            // عند الضغط على زر الحذف
            $('.delete-branch-btn').on('click', function() {
                const id = $(this).data('id');
                const name = $(this).data('name');
                $('#del_branch_id').val(id);
                $('#del_branch_name').text(name);
            });

            // --- ثانياً: محرك البحث السريع ---
            $('#tableSearch').on('keyup', function() {
                let value = $(this).val().toLowerCase();
                $("#branchesTable tr").filter(function() {
                    $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
                });
            });

            // --- ثالثاً: عرض رسائل SweetAlert المخزنة في SessionStorage ---
            const title = sessionStorage.getItem('swal_title');
            if (title) {
                Swal.fire({
                    title: title,
                    text: sessionStorage.getItem('swal_text'),
                    icon: sessionStorage.getItem('swal_icon'),
                    confirmButtonText: 'موافق',
                    customClass: {
                        confirmButton: 'btn btn-primary'
                    },
                    buttonsStyling: false
                });
                sessionStorage.removeItem('swal_title');
                sessionStorage.removeItem('swal_text');
                sessionStorage.removeItem('swal_icon');
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
    </script>
</body>

</html>