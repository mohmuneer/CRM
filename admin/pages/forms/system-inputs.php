<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require __DIR__ . "/../../../config/db.php";

// 1. جلب البيانات الحالية
$stmt = $pdo->query("SELECT * FROM system_visuals LIMIT 1");
$visuals = $stmt->fetch();
// استعادة الإعدادات الافتراضية
if (isset($_POST['def_visuals'])) {
    // نستخدم UPDATE بدلاً من INSERT لضمان تعديل السجل الحالي فقط
    // إذا كان الجدول يحتوي على سجل واحد دائماً بـ ID = 1
    $sql_def = "UPDATE system_visuals SET 
                system_font = 'Cairo', 
                sidebar_color = '#343a40', 
                header_color = '#ffffff', 
                main_color = '#007bff' 
                WHERE id = ?";

    $stmt_def = $pdo->prepare($sql_def);

    // تأكد أن $visuals['id'] معرف لديك من استعلام الجلب في أعلى الصفحة
    if ($stmt_def->execute([$visuals['id']])) {
        echo "<script>
            sessionStorage.setItem('swal_title', 'تمت الاستعادة');
            sessionStorage.setItem('swal_text', 'تم العودة إلى الإعدادات الأصلية بنجاح');
            sessionStorage.setItem('swal_icon', 'info');
            window.location.href = 'system-inputs.php'; 
        </script>";
        exit;
    }
}
// 2. تحديث البيانات عند الإرسال
if (isset($_POST['update_visuals'])) {
    $system_font   = $_POST['system_font'];
    $sidebar_color = $_POST['sidebar_color'];
    $header_color  = $_POST['header_color'];
    $main_color    = $_POST['main_color'];

    $sql = "UPDATE system_visuals SET system_font = ?, sidebar_color = ?, header_color = ?, main_color = ? WHERE id = ?";
    $stmt = $pdo->prepare($sql);

    if ($stmt->execute([$system_font, $sidebar_color, $header_color, $main_color, $visuals['id']])) {
        echo "<script>
            sessionStorage.setItem('swal_title', 'تم الحفظ!');
            sessionStorage.setItem('swal_text', 'تم تحديث إعدادات المظهر بنجاح');
            sessionStorage.setItem('swal_icon', 'success');
            window.location.href = 'system-inputs.php'; 
        </script>";
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>إعدادات مظهر النظام</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link rel="stylesheet" href="../../plugins/fontawesome-free/css/all.min.css">
    <link rel="stylesheet" href="../../dist/css/adminlte.min.css">
    <link rel="stylesheet" href="https://cdn.rtlcss.com/bootstrap/v4.2.1/css/bootstrap.min.css">
    <link rel="stylesheet" href="../../dist/css/custom.css">

    <style>
        /* تطبيق الخط المختار للمعاينة الفورية */
        /* @import url('https://fonts.googleapis.com/css2?family=Almarai&family=Cairo&family=Tajawal&display=swap'); */

        body {
            /* font-family: '<?php echo $visuals['system_font']; ?>', sans-serif !important; */
            overflow-x: hidden !important;
            scrollbar-width: none;
        }

        ::-webkit-scrollbar {
            display: none;
        }

        .color-preview-box {
            height: 38px;
            border-radius: 5px;
            border: 1px solid #ddd;
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
                            <h1>تهيئة مظهر النظام</h1>
                        </div>
                        <div class="col-sm-6">
                            <ol class="breadcrumb float-sm-right">
                                <li class="breadcrumb-item"><a href="../../index.php">الرئيسية</a></li>
                                <li class="breadcrumb-item active">إعدادات المظهر</li>
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
                                    <h3 class="card-title"><i class="fas fa-paint-brush"></i> تخصيصل الألوان والخطوط
                                    </h3>
                                </div>

                                <form action="" method="POST">
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label><i class="fas fa-font"></i> خط النظام</label>
                                                    <select name="system_font" class="form-control">
                                                        <option value="Cairo"
                                                            <?php echo ($visuals['system_font'] == 'Cairo') ? 'selected' : ''; ?>>
                                                            Cairo (الإفتراضي)</option>
                                                        <option value="Tajawal"
                                                            <?php echo ($visuals['system_font'] == 'Tajawal') ? 'selected' : ''; ?>>
                                                            Tajawal</option>
                                                        <option value="Almarai"
                                                            <?php echo ($visuals['system_font'] == 'Almarai') ? 'selected' : ''; ?>>
                                                            Almarai</option>
                                                    </select>
                                                </div>
                                            </div>

                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label><i class="fas fa-columns"></i> لون القائمة
                                                        الجانبية</label>
                                                    <input type="color" name="sidebar_color" class="form-control"
                                                        value="<?php echo $visuals['sidebar_color']; ?>">
                                                </div>
                                            </div>

                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label><i class="fas fa-heading"></i> لون الترويسة
                                                        العليا</label>
                                                    <input type="color" name="header_color" class="form-control"
                                                        value="<?php echo $visuals['header_color']; ?>">
                                                </div>
                                            </div>

                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label><i class="fas fa-mouse-pointer"></i> لون الأزرار
                                                        والهوية</label>
                                                    <input type="color" name="main_color" class="form-control"
                                                        value="<?php echo $visuals['main_color']; ?>">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="card-footer">
                                        <button type="submit" name="update_visuals" class="btn btn-primary float-left">
                                            <i class="fas fa-save"></i> حفظ التغييرات
                                        </button>
                                        <button type="submit" name="def_visuals" class="btn btn-primary float-right">
                                            <i class="fas fa-save"></i>الافتراضي
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>

        <footer class="main-footer">
            <?php include('../../main-footer.php') ?>
        </footer>
    </div>

    <script src="../../plugins/jquery/jquery.min.js"></script>
    <script src="../../plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../../dist/js/adminlte.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        $(document).ready(function() {
            const title = sessionStorage.getItem('swal_title');
            if (title) {
                Swal.fire({
                    title: title,
                    text: sessionStorage.getItem('swal_text'),
                    icon: sessionStorage.getItem('swal_icon'),
                    confirmButtonText: 'موافق'
                });
                sessionStorage.removeItem('swal_title');
                sessionStorage.removeItem('swal_text');
                sessionStorage.removeItem('swal_icon');
            }
        });
    </script>
</body>

</html>