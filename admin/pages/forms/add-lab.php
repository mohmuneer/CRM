<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require __DIR__ . "/../../../config/db.php";

if (isset($_POST['add_lab'])) {
    // جلب البيانات من النموذج
    $lab_name = trim($_POST['lab_name']);
    $college_id = $_POST['college_id']; // استلام معرف الكلية

    if (!empty($lab_name) && !empty($college_id)) {

        // 1. التحقق من وجود اسم المعمل مسبقاً (في نفس الكلية فقط) 
        // ملاحظة: يفضل التحقق في نفس الكلية لكي لا نمنع تكرار اسم "معمل حاسوب" في كليات مختلفة
        $checkSql = "SELECT COUNT(*) FROM labs WHERE lab_name = ? AND college_id = ?";
        $checkStmt = $pdo->prepare($checkSql);
        $checkStmt->execute([$lab_name, $college_id]);
        $exists = $checkStmt->fetchColumn();

        if ($exists > 0) {
            // تنبيه الخطأ في حال التكرار
            echo "<script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    icon: 'error',
                    title: 'عذراً...',
                    text: 'اسم المعمل هذا موجود مسبقاً في هذه الكلية!',
                    confirmButtonText: 'حسناً'
                });
            });
        </script>";
        } else {
            // 2. الإدخال في قاعدة البيانات مع ربطه بالكلية
            $sql = "INSERT INTO labs (lab_name, college_id) VALUES (?, ?)";
            $stmt = $pdo->prepare($sql);

            if ($stmt->execute([$lab_name, $college_id])) {
                // تخزين بيانات النجاح وإعادة التوجيه
                echo "<script>
                sessionStorage.setItem('swal_title', 'تمت العملية!');
                sessionStorage.setItem('swal_text', 'تم إضافة المعمل بنجاح وربطه بالكلية');
                sessionStorage.setItem('swal_icon', 'success');
                window.location.href = '../forms/add-lab.php'; 
            </script>";
                exit; // إنهاء التنفيذ بعد التوجيه
            }
        }
    } else {
        // تنبيه في حال كانت الحقول فارغة
        echo "<script>
        Swal.fire({
            icon: 'warning',
            title: 'تنبيه',
            text: 'الرجاء اختيار الكلية وتعبئة اسم المعمل',
            confirmButtonText: 'حسناً'
        });
    </script>";
    }
}
// 2. جلب كافة الفروع لعرضها في الكارد الأسفل
$labsSql = "SELECT * FROM labs ORDER BY id asc";
$labsStmt = $pdo->query($labsSql);
$alllabs = $labsStmt->fetchAll();
// 2. جلب البيانات مع اسم الفرع
$labsSql = "SELECT labs.*, colleges.college_name 
            FROM labs 
            LEFT JOIN colleges ON labs.college_id = colleges.id
            ORDER BY labs.id ASC";
$alllabs = $pdo->query($labsSql)->fetchAll();

// 3. جلب قائمة الفروع للاختيار منها
// استعلام يجلب اسم الكلية واسم الفرع المرتبط بها
$collegesSql = "SELECT 
                    colleges.id AS college_id, 
                    colleges.college_name, 
                    branch.branch_name 
                FROM colleges
                INNER JOIN branch ON colleges.branch_id = branch.id
                ORDER BY branch.branch_name ASC, colleges.college_name ASC";

$allColleges = $pdo->query($collegesSql)->fetchAll();

$labsSql = "SELECT 
                labs.id, 
                labs.lab_name, 
                colleges.college_name, 
                branch.branch_name 
            FROM labs
            INNER JOIN colleges ON labs.college_id = colleges.id
            INNER JOIN branch ON colleges.branch_id = branch.id
            ORDER BY labs.id ASC";

$alllabs = $pdo->query($labsSql)->fetchAll();
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
                            <h1> بيانات المعامل</h1>
                        </div>

                        <div class="col-sm-6">
                            <ol class="breadcrumb float-sm-right">
                                <li class="breadcrumb-item"><a href="../../index.php">الرئيسية</a></li>
                                <li class="breadcrumb-item active">المعامل</li>
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
                                <h3 class="card-title">إضافة معمل جديد</h3>
                            </div>
                            <div class="card-body" style="direction: rtl; text-align: right;">
                                <form method="POST">
                                    <div class="form-group">
                                        <label class="d-block">اختيار الكلية (حسب الفرع)</label>
                                        <select name="college_id" class="form-control" required>
                                            <option value="">-- اختر الكلية --</option>
                                            <?php foreach ($allColleges as $row): ?>
                                            <option value="<?= $row['college_id'] ?>">
                                                <?= htmlspecialchars($row['college_name']) ?> -
                                                (<?= htmlspecialchars($row['branch_name']) ?>)
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label class="d-block">اسم المعمل</label>
                                        <input type="text" name="lab_name" class="form-control"
                                            placeholder="أدخل اسم المعمل هنا" required>
                                    </div>

                                    <div class="form-group">
                                        <button type="submit" name="add_lab" class="btn btn-primary px-5">
                                            إضافة معمل
                                        </button>
                                    </div>
                                </form>
                            </div>

                        </div>

                    </div>
                </div>
                <div class="card card-info mt-4">
                    <div class="card-header">
                        <h3 class="card-title float-right">قائمة المعامل المسجلة</h3>
                        <div class="card-tools float-left">
                            <div class="input-group input-group-sm" style="width: 200px;">
                                <input type="text" id="tableSearch" class="form-control"
                                    placeholder="بحث عن المعامل...">
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
                                        <th style="width: 5%">#ID</th>
                                        <th>اسم المعمل</th>
                                        <th>الكلية</th>
                                        <th>الفرع</th>
                                        <th style="width: 15%">الإجراءات</th>
                                    </tr>
                                </thead>
                                <tbody id="branchTable">
                                    <?php if (count($alllabs) > 0): ?>
                                    <?php $counter = 1;
                                        foreach ($alllabs as $row): ?>
                                    <tr>
                                        <td><?= $counter++; ?></td>
                                        <td><?= htmlspecialchars($row['lab_name']) ?></td>
                                        <td>

                                            <?= htmlspecialchars($row['college_name']) ?>

                                        </td>
                                        <td>

                                            <?= htmlspecialchars($row['branch_name']) ?>

                                        </td>
                                        <td class="py-2">
                                            <div class="btn-group">
                                                <button class="btn btn-sm btn-warning ml-1 edit-btn"
                                                    data-id="<?= $row['id'] ?>"
                                                    data-name="<?= htmlspecialchars($row['lab_name']) ?>"
                                                    data-toggle="modal" data-target="#editModal">
                                                    <i class="fas fa-edit"></i>
                                                </button>

                                                <button class="btn btn-sm btn-danger delete-btn"
                                                    data-id="<?= $row['id'] ?>"
                                                    data-name="<?= htmlspecialchars($row['lab_name']) ?>"
                                                    data-toggle="modal" data-target="#deleteModal">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="text-muted">لا توجد معامل مضافة حالياً</td>
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
                    let rows = document.querySelectorAll('#branchTable tr');

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

    <div class="modal fade" id="editModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="direction: rtl; text-align: right;">
                <form action="edit-lab.php" method="POST">
                    <div class="modal-header bg-warning text-dark"
                        style="display: flex; justify-content: space-between; align-items: center;">
                        <h5 class="modal-title font-weight-bold">تعديل بيانات المعمل</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="margin: 0;">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>

                    <div class="modal-body">
                        <div class="form-group">
                            <label class="d-block">اسم المعمل الجديد</label>
                            <input type="text" name="lab_name" id="edit_name" class="form-control"
                                placeholder="أدخل الاسم الجديد هنا..." required>
                        </div>

                        <div class="form-group">
                            <label class="d-block">الكلية / الفرع التابع له</label>
                            <select name="college_id" id="edit_college_id" class="form-control" required>
                                <?php foreach ($allColleges as $row): ?>
                                <option value="<?= $row['college_id'] ?>">
                                    <?= htmlspecialchars($row['college_name']) ?> -
                                    (<?= htmlspecialchars($row['branch_name']) ?>)
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <input type="hidden" name="lab_id" id="edit_id">
                    <input type="hidden" name="action" value="update">

                    <div class="modal-footer"
                        style="direction: rtl; display: flex; justify-content: space-between; align-items: center; border-top: 1px solid #eee; padding: 15px;">

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
            $("#labsTable tr").filter(function() {
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