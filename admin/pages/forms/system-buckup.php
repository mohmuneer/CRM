<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require __DIR__ . "/../../../config/db.php";

// 1. جلب البيانات الحالية للمظهر
$stmt = $pdo->query("SELECT * FROM system_visuals LIMIT 1");
$visuals = $stmt->fetch();

if (!$visuals) {
    $visuals = ['id' => 1, 'system_font' => 'Cairo', 'sidebar_color' => '#343a40', 'header_color' => '#ffffff', 'main_color' => '#007bff'];
}

// --- منطق إعدادات المظهر ---
// (كما هو في كودك الأصلي...)

// --- منطق النسخ الاحتياطي (المحسّن) ---

// تصدير القاعدة
if (isset($_POST['export_db'])) {
    $fileName = 'backup_' . date('Y-m-d_H-i-s') . '.sql';
    $tables = [];
    $result = $pdo->query("SHOW TABLES");
    while ($row = $result->fetch(PDO::FETCH_NUM)) {
        $tables[] = $row[0];
    }

    $sqlScript = "SET FOREIGN_KEY_CHECKS=0;\n\n";
    foreach ($tables as $table) {
        // إضافة DROP TABLE لضمان نجاح الاستيراد مستقبلاً حتى لو الجداول موجودة
        $sqlScript .= "DROP TABLE IF EXISTS `$table`;\n";

        $query = $pdo->query("SHOW CREATE TABLE `$table` ");
        $row = $query->fetch(PDO::FETCH_NUM);
        $sqlScript .= "\n\n" . $row[1] . ";\n\n";

        $query = $pdo->query("SELECT * FROM `$table` ");
        $columnCount = $query->columnCount();
        while ($row = $query->fetch(PDO::FETCH_NUM)) {
            $sqlScript .= "INSERT INTO `$table` VALUES(";
            for ($j = 0; $j < $columnCount; $j++) {
                if (isset($row[$j])) {
                    $row[$j] = str_replace("\n", "\\n", addslashes($row[$j]));
                    $sqlScript .= '"' . $row[$j] . '"';
                } else {
                    $sqlScript .= 'NULL';
                }
                if ($j < ($columnCount - 1)) $sqlScript .= ',';
            }
            $sqlScript .= ");\n";
        }
    }
    $sqlScript .= "\nSET FOREIGN_KEY_CHECKS=1;";

    header('Content-Type: application/octet-stream');
    header("Content-disposition: attachment; filename=\"" . $fileName . "\"");
    echo $sqlScript;
    exit;
}

if (isset($_POST['import_db'])) {
    $file = $_FILES['sql_file']['tmp_name'];

    if (!empty($file)) {
        try {
            // قراءة محتوى ملف SQL
            $sql = file_get_contents($file);

            if ($sql === false) {
                throw new Exception("تعذر قراءة محتوى الملف.");
            }

            // تحسينات PDO للتعامل مع الاستعلامات المتعددة والكبيرة
            $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, 1);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // التنفيذ: تعطيل القيود -> تشغيل الملف -> إعادة تفعيل القيود
            // ملاحظة: تم وضع SET FOREIGN_KEY_CHECKS داخل الاستعلام لضمان التنفيذ المتسلسل
            $fullQuery = "SET FOREIGN_KEY_CHECKS=0;\n" . $sql . "\nSET FOREIGN_KEY_CHECKS=1;";

            $pdo->exec($fullQuery);

            $success = "تم استعادة قاعدة البيانات بنجاح! تم استبدال الجداول القديمة بالبيانات الجديدة.";
        } catch (Exception $e) {
            $error = "فشل الاستيراد: " . $e->getMessage();
        }
    } else {
        $error = "يرجى اختيار ملف SQL أولاً.";
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

        .backup-card {
            transition: 0.3s;
            border-radius: 15px;
        }

        .backup-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
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
                    <?php if (isset($success)): ?> <div class="alert alert-success"><?php echo $success; ?></div>
                    <?php endif; ?>
                    <?php if (isset($error)): ?> <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="card backup-card card-outline card-primary">
                                <div class="card-header text-center">
                                    <h3><i class="fas fa-download text-primary"></i> تصدير البيانات</h3>
                                </div>
                                <div class="card-body">
                                    <p class="text-muted">قم بتحميل نسخة كاملة من قاعدة البيانات الحالية بصيغة SQL.</p>
                                    <form method="POST" target="_blank">
                                        <button type="submit" name="export_db" id="exportBtn"
                                            class="btn btn-primary btn-block btn-lg">
                                            <i class="fas fa-file-export"></i> إنشاء نسخة احتياطية الآن
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="card backup-card card-outline card-danger">
                                <div class="card-header text-center">
                                    <h3><i class="fas fa-upload text-danger"></i> استيراد البيانات</h3>
                                </div>
                                <div class="card-body">
                                    <p class="text-muted text-danger">⚠️ تحذير: سيتم استبدال البيانات الحالية بالكامل!
                                    </p>
                                    <form method="POST" enctype="multipart/form-data">
                                        <div class="form-group">
                                            <div class="custom-file">
                                                <input type="file" name="sql_file" class="custom-file-input"
                                                    id="customFile" accept=".sql">
                                                <label class="custom-file-label" for="customFile">اختر ملف
                                                    SQL...</label>
                                            </div>
                                        </div>
                                        <button type="submit" name="import_db" class="btn btn-danger btn-block btn-lg"
                                            onclick="return confirm('هل أنت متأكد؟')">
                                            <i class="fas fa-file-import"></i> رفع واستعادة النسخة
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>


        <footer class="main-footer">
            <?php include('../../main-footer.php') ?>
        </footer>

        <div class="col-md-6">
            <div class="card backup-card card-outline card-primary">
                <div class="card-header text-center">
                    <h3><i class="fas fa-download text-primary"></i> تصدير البيانات</h3>
                </div>
                <div class="card-body">
                    <p class="text-muted">قم بتحميل نسخة كاملة من قاعدة البيانات الحالية بصيغة SQL لحفظها في مكان آمن.
                    </p>
                    <form method="POST" target="_blank">
                        <button type="submit" name="export_db" id="exportBtn" class="btn btn-primary btn-block btn-lg">
                            <i class="fas fa-file-export"></i> إنشاء نسخة احتياطية الآن
                        </button>
                    </form>
                </div>
            </div>
        </div>
        <div class="card backup-card card-outline card-danger">
            <div class="card-header text-center">
                <h3><i class="fas fa-upload text-danger"></i> استيراد البيانات</h3>
            </div>
            <div class="card-body">
                <p class="text-muted text-danger">⚠️ تنبيه: سيتم حذف الجداول الحالية واستبدالها بالكامل من ملف النسخة
                    الاحتياطية.</p>
                <form method="POST" enctype="multipart/form-data">
                    <div class="form-group">
                        <div class="custom-file">
                            <input type="file" name="sql_file" class="custom-file-input" id="customFile" accept=".sql"
                                required>
                            <label class="custom-file-label" for="customFile">اختر ملف SQL المصدّر مسبقاً...</label>
                        </div>
                    </div>
                    <button type="submit" name="import_db" class="btn btn-danger btn-block btn-lg"
                        onclick="return confirm('هل أنت متأكد؟ سيتم فقدان البيانات الحالية وتعويضها ببيانات الملف.')">
                        <i class="fas fa-file-import"></i> بدء عملية الاستعادة الآن
                    </button>
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
            // 1. تحديث اسم الملف عند الاختيار
            $(".custom-file-input").on("change", function() {
                var fileName = $(this).val().split("\\").pop();
                $(this).siblings(".custom-file-label").addClass("selected").html(fileName);
            });

            // 2. تنبيه عند الضغط على تصدير (النسخ الاحتياطي)
            $('#exportBtn').on('click', function() {
                setTimeout(function() {
                    Swal.fire({
                        title: 'تم بدء النسخ!',
                        text: 'يتم الآن تجهيز ملف القاعدة وتحميله لجهازك.',
                        icon: 'success',
                        confirmButtonText: 'موافق'
                    });
                }, 1000);
            });

            // 3. عرض رسائل sessionStorage (المظهر والاستعادة)
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
        });
    </script>
</body>

</html>