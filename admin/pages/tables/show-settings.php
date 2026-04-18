<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require __DIR__ . "/../../../config/db.php";
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// 1. تعريف الدالة في البداية لتكون جاهزة للاستخدام
function logAction($pdo, $userName, $action)
{
    try {
        $stmt = $pdo->prepare("INSERT INTO system_logs (user_name, action, page_url, ip_address) VALUES (?, ?, ?, ?)");
        $stmt->execute([
            $userName,
            $action,
            $_SERVER['REQUEST_URI'],
            $_SERVER['REMOTE_ADDR']
        ]);
    } catch (Exception $e) {
        // نضعها في catch حتى لا يتوقف النظام إذا فشل السجل
    }
}

// الحصول على اسم المستخدم من الجلسة (افترضنا أن اسمه 'user_name')
$current_user = $_SESSION['full_name'] ?? 'Admin';

// 2. معالجة تحديث البيانات
if (isset($_POST['update_settings'])) {
    $name = $_POST['system_name'];
    $email = $_POST['admin_email'];
    $phone = $_POST['contact_number'];
    $addr = $_POST['address'];
    $mode = $_POST['maintenance_mode'];

    $stmt = $pdo->query("SELECT id, system_logo FROM system_settings LIMIT 1");
    $current = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($current) {
        $id = $current['id'];
        $logo_name = $current['system_logo'] ?? '';

        if (!empty($_FILES['system_logo']['name'])) {
            $logo_name = time() . '_' . $_FILES['system_logo']['name'];
            $upload_path = "../../dist/img/" . $logo_name;
            if (!move_uploaded_file($_FILES['system_logo']['tmp_name'], $upload_path)) {
                $logo_name = $current['system_logo'];
            }
        }

        $update = $pdo->prepare("UPDATE system_settings SET 
            system_name = ?, admin_email = ?, contact_number = ?, 
            address = ?, maintenance_mode = ?, system_logo = ? 
            WHERE id = ?");

        if ($update->execute([$name, $email, $phone, $addr, $mode, $logo_name, $id])) {

            // --- استدعاء الدالة هنا لتسجيل العملية ---
            logAction($pdo, $current_user, "قام بتحديث إعدادات النظام العام");

            echo "<script>sessionStorage.setItem('swal_title', 'تم التحديث بنجاح'); sessionStorage.setItem('swal_icon', 'success'); window.location.href='show-settings.php';</script>";
            exit;
        }
    } else {
        // حالة الإدخال لأول مرة
        $insert = $pdo->prepare("INSERT INTO system_settings (system_name, admin_email, contact_number, address, maintenance_mode) VALUES (?, ?, ?, ?, ?)");
        if ($insert->execute([$name, $email, $phone, $addr, $mode])) {

            // --- استدعاء الدالة هنا لتسجيل العملية ---
            logAction($pdo, $current_user, "قام بضبط إعدادات النظام لأول مرة");

            header("Location: show-settings.php");
            exit;
        }
    }
}

// 3. جلب البيانات للعرض
$stmtm = "SELECT * FROM system_settings LIMIT 1";
$users = $pdo->query($stmtm)->fetchAll(PDO::FETCH_ASSOC);

if (empty($users)) {
    $users = [[
        'system_name' => '',
        'admin_email' => '',
        'contact_number' => '',
        'address' => '',
        'maintenance_mode' => 0,
        'system_logo' => ''
    ]];
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
            <!-- Content Header -->
            <section class="content-header">
                <div class="container-fluid">
                    <div class="row mb-2">
                        <div class="col-sm-6">
                            <h1>عرض بيانات المستخدمين</h1>
                        </div>
                        <div class="col-sm-6">
                            <ol class="breadcrumb float-sm-right">
                                <li class="breadcrumb-item"><a href="../../index.php">الرئيسية</a></li>
                                <li class="breadcrumb-item active">المستخدمين</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Main content -->
            <section class="content">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="card card-primary card-outline">
                                <div class="card-header">
                                    <h3 class="card-title float-right">هوية المؤسسة والاتصال</h3>
                                    <form action="" method="POST" class="float-left">
                                        <button type="submit" name="def_settings"
                                            class="btn btn-sm btn-outline-secondary">
                                            <i class="fas fa-undo"></i> استعادة الافتراضي
                                        </button>
                                    </form>
                                </div>
                                <?php foreach ($users as $setting): ?>
                                <form action="" method="POST" enctype="multipart/form-data">
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>اسم النظام / المؤسسة</label>
                                                    <input type="text" name="system_name" class="form-control"
                                                        value="<?php echo htmlspecialchars($setting['system_name'] ?? ''); ?>"
                                                        required>
                                                </div>
                                            </div>

                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>البريد الإلكتروني الرسمي</label>
                                                    <input type="email" name="admin_email" class="form-control"
                                                        value="<?php echo htmlspecialchars($setting['admin_email'] ?? ''); ?>"
                                                        required>
                                                </div>
                                            </div>

                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>رقم الهاتف</label>
                                                    <input type="text" name="contact_number" class="form-control"
                                                        value="<?php echo htmlspecialchars($setting['contact_number'] ?? ''); ?>">
                                                </div>
                                            </div>

                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>العنوان</label>
                                                    <input type="text" name="address" class="form-control"
                                                        value="<?php echo htmlspecialchars($setting['address'] ?? ''); ?>">
                                                </div>
                                            </div>

                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>حالة النظام</label>
                                                    <select class="form-control" name="maintenance_mode">
                                                        <option value="0"
                                                            <?php echo (isset($setting['maintenance_mode']) && $setting['maintenance_mode'] == 0) ? 'selected' : ''; ?>>
                                                            نشط (يعمل)
                                                        </option>
                                                        <option value="1"
                                                            <?php echo (isset($setting['maintenance_mode']) && $setting['maintenance_mode'] == 1) ? 'selected' : ''; ?>>
                                                            وضع الصيانة
                                                        </option>
                                                    </select>
                                                </div>
                                            </div>

                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>شعار النظام الحالي</label><br>
                                                    <?php if (!empty($setting['system_logo'])): ?>
                                                    <img src="../../dist/img/<?php echo $setting['system_logo']; ?>"
                                                        class="logo-preview" style="max-width: 150px;">
                                                    <?php else: ?>
                                                    <div class="p-3 bg-light text-muted border">لا يوجد شعار حالياً
                                                    </div>
                                                    <?php endif; ?>

                                                    <div class="custom-file mt-2">
                                                        <input type="file" name="system_logo" class="custom-file-input"
                                                            id="logoInput">
                                                        <label class="custom-file-label" for="logoInput">تغيير
                                                            الشعار...</label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="card-footer">
                                        <button type="submit" name="update_settings" class="btn btn-primary px-5">حفظ
                                            التغييرات</button>
                                    </div>
                                </form>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>
        <!-- ./wrapper -->

        <script src="../../plugins/jquery/jquery.min.js"></script>
        <script src="../../plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
        <script src="../../dist/js/adminlte.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <script>
        // إظهار اسم الملف المختار في حقل الـ File Input
        $(".custom-file-input").on("change", function() {
            var fileName = $(this).val().split("\\").pop();
            $(this).siblings(".custom-file-label").addClass("selected").html(fileName);
        });

        // رسائل SweetAlert
        const title = sessionStorage.getItem('swal_title');
        if (title) {
            Swal.fire({
                title: title,
                text: sessionStorage.getItem('swal_text'),
                icon: sessionStorage.getItem('swal_icon'),
                confirmButtonText: 'موافق'
            });
            sessionStorage.clear();
        }
        </script>

</body>

</html>