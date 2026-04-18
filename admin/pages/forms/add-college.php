<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require __DIR__ . "/../../../config/db.php";

// 1. معالجة إضافة كلية جديدة
if (isset($_POST['add_college'])) {
    $college_name = trim($_POST['college_name']);
    $branch_id    = $_POST['branch_id']; // استلام رقم الفرع من القائمة المنسدلة

    if (!empty($college_name) && !empty($branch_id)) {

        // التحقق من وجود الكلية مسبقاً "في نفس الفرع" لمنع التكرار
        $checkSql = "SELECT COUNT(*) FROM colleges WHERE college_name = ? AND branch_id = ?";
        $checkStmt = $pdo->prepare($checkSql);
        $checkStmt->execute([$college_name, $branch_id]);
        $exists = $checkStmt->fetchColumn();

        if ($exists > 0) {
            echo "<script>
                document.addEventListener('DOMContentLoaded', function() {
                    Swal.fire({
                        icon: 'error',
                        title: 'عذراً...',
                        text: 'هذه الكلية موجودة مسبقاً في هذا الفرع!',
                        confirmButtonText: 'حسناً'
                    });
                });
            </script>";
        } else {
            // 2. الإدخال في جدول الكليات
            $sql = "INSERT INTO colleges (college_name, branch_id) VALUES (?, ?)";
            $stmt = $pdo->prepare($sql);

            if ($stmt->execute([$college_name, $branch_id])) {
                echo "<script>
                    sessionStorage.setItem('swal_title', 'تمت العملية!');
                    sessionStorage.setItem('swal_text', 'تم إضافة الكلية بنجاح');
                    sessionStorage.setItem('swal_icon', 'success');
                    window.location.href = '../forms/add-college.php'; 
                </script>";
            }
        }
    } else {
        echo "<script>alert('الرجاء تعبئة كافة الحقول');</script>";
    }
}

// 2. جلب كافة الفروع (لعرضها في القائمة المنسدلة Select)
$branchesSql = "SELECT * FROM branch ORDER BY branch_name ASC";
$allBranches = $pdo->query($branchesSql)->fetchAll();

// 3. جلب كافة الكليات مع أسماء فروعها (لعرضها في الجدول الأسفل)
$collegesSql = "SELECT c.*, b.branch_name 
                FROM colleges c 
                JOIN branch b ON c.branch_id = b.id 
                ORDER BY c.id DESC";
$allColleges = $pdo->query($collegesSql)->fetchAll();
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
                            <h1> بيانات الكليات</h1>
                        </div>

                        <div class="col-sm-6">
                            <ol class="breadcrumb float-sm-right">
                                <li class="breadcrumb-item"><a href="../../index.php">الرئيسية</a></li>
                                <li class="breadcrumb-item active">الكليات</li>
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
                                <h3 class="card-title">إضافة كلية جديدة</h3>
                            </div>
                            <div class="card-body">

                                <form method="POST">
                                    <div class="form-group">
                                        <label>الفرع</label>
                                        <select class="form-control" name="branch_id" required>
                                            <option value="" disabled selected>اختر الفرع من القائمة...</option>
                                            <?php foreach ($allBranches as $branch): ?>
                                                <option value="<?= htmlspecialchars($branch['id']) ?>">
                                                    <?= htmlspecialchars($branch['branch_name']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label>اسم الكلية</label>
                                        <input type="text" name="college_name" class="form-control"
                                            placeholder="أدخل اسم الكلية هنا..." required>
                                    </div>

                                    <div class="form-group">
                                        <button type="submit" name="add_college" class="btn btn-primary">
                                            إضافة كلية
                                        </button>
                                    </div>
                                </form>

                            </div>

                        </div>

                    </div>
                </div>
                <div class="card card-info mt-4">
                    <div class="card-header">
                        <h3 class="card-title float-right">قائمة الكليات المسجلة</h3>
                        <div class="card-tools float-left">
                            <div class="input-group input-group-sm" style="width: 200px;">
                                <input type="text" id="tableSearch" class="form-control" placeholder="بحث عن كلية...">
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
                            <table class="table table-bordered table-striped text-center">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>اسم الكلية</th>
                                        <th>الفرع</th>
                                        <th>الإجراءات</th>
                                    </tr>
                                </thead>
                                <tbody id="collegesTable">
                                    <?php $counter = 1;
                                    foreach ($allColleges as $row): ?>
                                        <tr>
                                            <td><?= $counter++; ?></td>
                                            <td><?= htmlspecialchars($row['college_name']) ?></td>
                                            <td><?= htmlspecialchars($row['branch_name']) ?>
                                            </td>
                                            <td>
                                                <button class="btn btn-sm btn-warning edit-college-btn"
                                                    data-id="<?= $row['id'] ?>"
                                                    data-name="<?= htmlspecialchars($row['college_name']) ?>"
                                                    data-branch="<?= $row['branch_id'] ?>" data-toggle="modal"
                                                    data-target="#editCollegeModal">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="btn btn-sm btn-danger delete-college-btn"
                                                    data-id="<?= $row['id'] ?>"
                                                    data-name="<?= htmlspecialchars($row['college_name']) ?>"
                                                    data-toggle="modal" data-target="#deleteCollegeModal">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
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

    <div class="modal fade" id="deleteCollegeModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border: none; border-radius: 8px;">
                <form action="edit-college.php" method="POST">
                    <div class="modal-header bg-danger text-white" style="direction: rtl;">
                        <h5 class="modal-title">تأكيد الحذف</h5>
                        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close"
                            style="margin: -1rem auto -1rem -1rem;">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body text-right" style="padding: 2rem; direction: rtl;">
                        <p style="font-size: 1.1rem;">هل أنت متأكد من حذف كلية: <strong id="del_name"
                                class="text-danger"></strong>؟</p>
                        <input type="hidden" name="college_id" id="del_id">
                        <input type="hidden" name="action" value="delete">
                    </div>
                    <div class="modal-footer"
                        style="border-top: 1px solid #eee; direction: rtl; justify-content: flex-start;">
                        <button type="submit" class="btn btn-danger"
                            style="border-radius: 5px; padding: 8px 25px;">حذف</button>
                        <button type="button" class="btn btn-secondary" data-dismiss="modal"
                            style="border-radius: 5px; padding: 8px 25px;">إلغاء</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="editCollegeModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border: none; border-radius: 10px; overflow: hidden;">
                <form action="edit-college.php" method="POST">
                    <div class="modal-header bg-warning"
                        style="direction: rtl; text-align: right; display: flex; justify-content: space-between; align-items: center;">
                        <h5 class="modal-title" style="font-weight: bold; margin: 0;">تعديل الكلية</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"
                            style="margin: 0; padding: 0;">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>

                    <div class="modal-body" style="direction: rtl; text-align: right; padding: 20px;">
                        <div class="form-group">
                            <label style="font-weight: bold; display: block; margin-bottom: 8px;">اسم الكلية</label>
                            <input type="text" name="college_name" id="edit_name" class="form-control"
                                style="text-align: right;" required>
                        </div>
                        <div class="form-group">
                            <label style="font-weight: bold; display: block; margin-bottom: 8px;">الفرع</label>
                            <select name="branch_id" id="edit_branch_id" class="form-control"
                                style="text-align: right; direction: rtl;">
                                <?php foreach ($allBranches as $b): ?>
                                    <option value="<?= $b['id'] ?>"><?= $b['branch_name'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <input type="hidden" name="college_id" id="edit_id">
                        <input type="hidden" name="action" value="update">
                    </div>

                    <div class="modal-footer"
                        style="direction: rtl; display: flex; justify-content: space-between; border-top: 1px solid #eee; padding: 15px;">

                        <button type="submit" class="btn btn-warning"
                            style="background-color: #ffb300; border: none; font-weight: bold; padding: 8px 25px; border-radius: 5px;">
                            حفظ التغييرات
                        </button>

                        <button type="button" class="btn btn-secondary" data-dismiss="modal"
                            style="background-color: #6c757d; border: none; padding: 8px 25px; border-radius: 5px;">
                            إلغاء
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
            // تعبئة موديل التعديل
            $('.edit-college-btn').on('click', function() {
                $('#edit_id').val($(this).data('id'));
                $('#edit_name').val($(this).data('name'));
                $('#edit_branch_id').val($(this).data('branch'));
            });

            // تعبئة موديل الحذف
            $('.delete-college-btn').on('click', function() {
                $('#del_id').val($(this).data('id'));
                $('#del_name').text($(this).data('name'));
            });

            // البحث
            $('#tableSearch').on('keyup', function() {
                let val = $(this).val().toLowerCase();
                $("#collegesTable tr").filter(function() {
                    $(this).toggle($(this).text().toLowerCase().indexOf(val) > -1)
                });
            });

            // رسائل SweetAlert
            if (sessionStorage.getItem('swal_title')) {
                Swal.fire({
                    title: sessionStorage.getItem('swal_title'),
                    text: sessionStorage.getItem('swal_text'),
                    icon: sessionStorage.getItem('swal_icon'),
                    confirmButtonText: 'موافق'
                });
                sessionStorage.clear();
            }
            $('body').css('visibility', 'visible');
        });
    </script>
</body>

</html>