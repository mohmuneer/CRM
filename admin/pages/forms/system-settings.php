<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require __DIR__ . "/../../../config/db.php";

// 1. جلب البيانات الحالية (للحصول على الـ ID واسم الشعار القديم)
$stmt = $pdo->query("SELECT * FROM system_settings LIMIT 1");
$settings = $stmt->fetch();

// في حال عدم وجود سجل، نقوم بإنشائه افتراضياً لمرة واحدة فقط لضمان وجود ID
if (!$settings) {
    $pdo->exec("INSERT INTO system_settings (system_name) VALUES ('نظام CRM الذكي')");
    $stmt = $pdo->query("SELECT * FROM system_settings LIMIT 1");
    $settings = $stmt->fetch();
}

// --- [أ] منطق استعادة الإعدادات الافتراضية ---


// --- [ب] منطق تحديث البيانات (حفظ التغييرات) ---
if (isset($_POST['update_settings'])) {
    $system_name      = $_POST['system_name'];
    $admin_email      = $_POST['admin_email'];
    $contact_number   = $_POST['contact_number'];
    $address          = $_POST['address'];
    $maintenance_mode = $_POST['maintenance_mode'];

    // معالجة رفع الشعار (Logo)
    $logo_name = $settings['system_logo']; // القيمة القديمة افتراضياً

    if (isset($_FILES['system_logo']) && $_FILES['system_logo']['error'] === UPLOAD_ERR_OK) {
        $uploadFileDir = '../../dist/img/';
        $fileExtension = strtolower(pathinfo($_FILES['system_logo']['name'], PATHINFO_EXTENSION));
        $newFileName   = 'logo_' . time() . '.' . $fileExtension;
        $dest_path     = $uploadFileDir . $newFileName;

        $allowedExtensions = ['png', 'jpg', 'jpeg', 'svg'];
        if (in_array($fileExtension, $allowedExtensions)) {
            if (move_uploaded_file($_FILES['system_logo']['tmp_name'], $dest_path)) {
                $logo_name = $newFileName; // تحديث الاسم بقيمة الملف الجديد
            }
        }
    }

    $sql = "UPDATE system_settings SET 
            system_name = ?, 
            admin_email = ?, 
            contact_number = ?, 
            address = ?, 
            maintenance_mode = ?, 
            system_logo = ? 
            WHERE id = ?";

    $stmt = $pdo->prepare($sql);
    $params = [$system_name, $admin_email, $contact_number, $address, $maintenance_mode, $logo_name, $settings['id']];

    if ($stmt->execute($params)) {
        echo "<script>
            sessionStorage.setItem('swal_title', 'تم الحفظ!');
            sessionStorage.setItem('swal_text', 'تم تحديث إعدادات النظام بنجاح');
            sessionStorage.setItem('swal_icon', 'success');
            window.location.href = 'system-settings.php'; 
        </script>";
        exit;
    }
}
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
        /* @import url('https://fonts.googleapis.com/css2?family=Almarai&family=Cairo&family=Tajawal&display=swap');

    body {
        font-family: '<?php echo $visuals['system_font']; ?>', sans-serif !important;
        overflow-x: hidden !important;
        scrollbar-width: none;
    } */

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
                            <h1>تهيئة النظام</h1>
                        </div>

                        <div class="col-sm-6">
                            <ol class="breadcrumb float-sm-right">
                                <li class="breadcrumb-item"><a href="../../index.php">الرئيسية</a></li>
                                <li class="breadcrumb-item active">الاعدادات العامة</li>
                            </ol>
                        </div>

                    </div>
                </div>
            </section>

            <section class="content">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="card card-primary card-outline">
                                <div class="card-header">
                                    <h3 class="card-title">إعدادات الهوية والاتصال</h3>
                                </div>

                                <form action="" method="POST" enctype="multipart/form-data">
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="system_name">اسم النظام / المؤسسة</label>
                                                    <input type="text" name="system_name" class="form-control"
                                                        id="system_name"
                                                        placeholder="مثلاً: نظام إدارة المعامل بجامعة..."
                                                        value="نظام CRM الذكي">
                                                </div>
                                            </div>

                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="admin_email">البريد الإلكتروني للعنوان</label>
                                                    <input type="email" name="admin_email" class="form-control"
                                                        id="admin_email" placeholder="admin@example.com">
                                                </div>
                                            </div>

                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="contact_number">رقم الهاتف الرسمي</label>
                                                    <input type="text" name="contact_number" class="form-control"
                                                        id="contact_number" placeholder="00966XXXXXXX">
                                                </div>
                                            </div>

                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="address">العنوان الفيزيائي</label>
                                                    <input type="text" name="address" class="form-control" id="address"
                                                        placeholder="المبنى الرئيسي - الطابق الثاني">
                                                </div>
                                            </div>

                                            <div class="col-md-12">
                                                <div class="form-group">
                                                    <label for="system_logo">شعار النظام (Logo)</label>
                                                    <div class="input-group">
                                                        <div class="custom-file">
                                                            <input type="file" name="system_logo"
                                                                class="custom-file-input" id="system_logo">
                                                            <label class="custom-file-label" for="system_logo">اختر
                                                                ملف
                                                                الصورة</label>
                                                        </div>
                                                    </div>
                                                    <small class="text-muted">يفضل استخدام صيغة PNG بخلفية
                                                        شفافة.</small>
                                                </div>
                                            </div>

                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>حالة النظام (الصيانة)</label>
                                                    <select class="form-control" name="maintenance_mode">
                                                        <option value="0">يعمل (نشط)</option>
                                                        <option value="1">وضع الصيانة (مغلق)</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="card-footer text-left">
                                        <button type="submit" name="update_settings" class="btn btn-primary">حفظ
                                            التغييرات</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
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
                        <div class="form-group">
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