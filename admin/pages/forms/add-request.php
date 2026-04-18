<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require __DIR__ . "/../../../config/db.php";

// التحقق من إرسال نموذج البلاغ
if (isset($_POST['add_ticket'])) {
    $user_id_number = trim($_POST['user_id_number']);
    $branch_id      = $_POST['branch_id'];
    $college_id     = $_POST['college_id'];
    $lab_id         = !empty($_POST['lab_id']) ? $_POST['lab_id'] : null;
    $room_number    = trim($_POST['room_number']);
    $priority       = $_POST['priority'];
    $details        = trim($_POST['details']);

    // التأكد من اختيار تصنيف المشكلة وتعيينه في متغير واحد فقط
    $issue_type_id  = isset($_POST['issue_type_id']) ? $_POST['issue_type_id'] : null;

    if ($issue_type_id === null) {
        die("<script>alert('الرجاء اختيار تصنيف المشكلة'); window.history.back();</script>");
    }

    try {
        // تأكد من تطابق أسماء الحقول مع جدول requests في قاعدة بياناتك
        $sql = "INSERT INTO requests (user_id_number, branch_id, college_id, lab_id, location_name, issue_type_id, priority, details, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Pending')";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $user_id_number,
            $branch_id,
            $college_id,
            $lab_id,
            $room_number,
            $issue_type_id, // الآن نرسل الرقم (1 أو 3 أو 4) المتوافق مع جدول groups
            $priority,
            $details
        ]);

        echo "<script>
                    sessionStorage.setItem('swal_title', 'تمت العملية!');
                    sessionStorage.setItem('swal_text', 'تم إضافة الفرع بنجاح');
                    sessionStorage.setItem('swal_icon', 'success');
                    window.location.href = '../tables/show-requests.php'; 
                </script>";
    } catch (PDOException $e) {
        die("خطأ في قاعدة البيانات: " . $e->getMessage());
    }
}

// ---------------------------------------------------------
// الأكواد الخاصة بجلب البيانات للقوائم المنسدلة (كما هي في كودك)
// ---------------------------------------------------------

// جلب جميع الفروع
$branchesSql = "SELECT id, branch_name FROM branch ORDER BY branch_name ASC";
$allBranches = $pdo->query($branchesSql)->fetchAll();

// معالجة طلب AJAX لجلب الكليات (تأكد من وجود هذا الجزء أو نقله لملف get_colleges.php)
if (isset($_GET['get_colleges_ajax'])) {
    $branch_id = filter_var($_GET['branch_id'], FILTER_SANITIZE_NUMBER_INT);
    $stmt = $pdo->prepare("SELECT id, college_name FROM colleges WHERE branch_id = ? ORDER BY college_name ASC");
    $stmt->execute([$branch_id]);
    header('Content-Type: application/json');
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    exit;
}

// جلب تصنيفات المشاكل من جدول المجموعات
$groupsSql = "SELECT id, group_name FROM groups ORDER BY id ASC";
$allGroups = $pdo->query($groupsSql)->fetchAll();
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

        :root {
            --uni-color: #007BFF;
            --uni-accent: #0056b3;
        }

        .main-header-uni {
            background: var(--uni-color);
            color: white;
            text-align: center;
            padding: 10px;
            font-size: 1.2rem;
            border-bottom: 4px solid var(--uni-accent);
        }

        .section-title {
            background: #f8f9fa;
            border-right: 5px solid var(--uni-color);
            padding: 8px;
            margin-bottom: 15px;
            font-weight: bold;
            color: var(--uni-color);
        }

        .card-ticket {
            border: 1px solid #ddd;
            border-top: 3px solid var(--uni-color);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .radio-box {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            background: #fff;
            padding: 10px;
            border: 1px solid #dee2e6;
            border-radius: 5px;
        }

        .priority-urgent {
            color: #d9534f;
            font-weight: bold;
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

                        <div class="col-sm-12">
                            <div class="main-header-uni ">
                                <i class="fas fa-university "></i> بوابة الدعم الفني والصيانة - إدارة تكنولوجيا
                                المعلومات
                                <ol class="breadcrumb float-sm-right">
                                    <li class="breadcrumb-item" style="color:white"><a href="../../index.php"
                                            style="color:white">الرئيسية</a></li>
                                    <li class="breadcrumb-item active" style="color:white">المعامل</li>
                                </ol>
                            </div>

                        </div>

                        <div class="col-sm-6">

                        </div>

                    </div>
                </div>
            </section>



            <section class="content mt-4">
                <div class="container-fluid">
                    <form method="POST">
                        <div class="row">

                            <div class="col-md-5">
                                <div class="card card-ticket">
                                    <div class="card-body">
                                        <div class="section-title">بيانات الموقع والمبلغ</div>
                                        <div class="form-group">
                                            <label>الرقم الجامعي / الوظيفي</label>
                                            <input type="text" name="user_id_number" class="form-control"
                                                placeholder="مثال: 20241010" required>
                                        </div>
                                        <div class="form-group">
                                            <label>اختيار الفرع</label>
                                            <select id="branch_select" name="branch_id" class="form-control" required>
                                                <option value="">-- اختر الفرع --</option>
                                                <?php if (!empty($allBranches)): ?>
                                                    <?php foreach ($allBranches as $branch): ?>
                                                        <option value="<?= $branch['id'] ?>">
                                                            <?= htmlspecialchars($branch['branch_name']) ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                            </select>
                                        </div>

                                        <div class="form-group">
                                            <label class="d-block">اختيار الكلية</label>
                                            <select id="college_select" name="college_id" class="form-control" required
                                                disabled>
                                                <option value="">-- اختر الكلية --</option>
                                            </select>
                                        </div>
                                        <div class="form-group" id="lab_select_container">
                                            <label>اختر المعمل</label>
                                            <select name="lab_id" id="lab_select" class="form-control">
                                                <option value="">-- اختر الكلية أولاً --</option>
                                            </select>
                                        </div>


                                        <div class="form-group">
                                            <label>رقم القاعة / المعمل (أو اسم المكان)</label>
                                            <input type="text" name="room_number" class="form-control"
                                                placeholder="مثال: Lab 04 أو قاعة 201">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-7">
                                <div class="card card-ticket">
                                    <div class="card-body">
                                        <div class="section-title">تفاصيل البلاغ الفني</div>

                                        <div class="form-group">
                                            <label>تصنيف المشكلة</label>
                                            <div class="radio-box">
                                                <?php if (!empty($allGroups)): ?>
                                                    <?php foreach ($allGroups as $group): ?>
                                                        <label>
                                                            <input type="radio" name="issue_type_id" value="<?= $group['id'] ?>"
                                                                required>
                                                            <?= htmlspecialchars($group['group_name']) ?>
                                                        </label>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                            </div>
                                        </div>

                                        <div class="form-group">
                                            <label>درجة الأهمية</label>
                                            <div class="radio-box" style="border-color: #ffc107;">
                                                <label class="text-info"><input type="radio" name="priority"
                                                        value="Low">
                                                    عادي</label>
                                                <label class="text-success"><input type="radio" name="priority"
                                                        value="Medium">
                                                    متوسط</label>
                                                <label class="text-warning"><input type="radio" name="priority"
                                                        value="High">
                                                    طارئ (توقف محاضرة)</label>
                                            </div>
                                        </div>

                                        <div class="form-group">
                                            <label>وصف المشكلة بالتفصيل</label>
                                            <textarea name="details" class="form-control" rows="4"
                                                placeholder="يرجى كتابة ما حدث، مثلاً: جهاز رقم 12 في المعمل لا يقلع.."></textarea>
                                        </div>

                                        <hr>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <p class="small text-muted">سيتم إخطار فنيي الدور فور إرسال البلاغ.</p>
                                            <button type="submit" name="add_ticket" class="btn btn-lg btn-primary">
                                                <i class="fas fa-paper-plane"></i> إرسال البلاغ
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </form>
                </div>
            </section>

        </div>

        <footer class="main-footer">
            <?php include('../../main-footer.php') ?>
        </footer>

    </div>

    <script src="../../plugins/jquery/jquery.min.js"></script>
    <script src="../../plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../../dist/js/adminlte.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            // مراقبة التغيير في قائمة الكليات
            $('#college_select').on('change', function() {
                var collegeId = $(this).val();
                var labSelect = $('#lab_select');

                if (collegeId) {
                    // تفعيل قائمة المعامل وإظهار حالة التحميل
                    labSelect.prop('disabled', false);
                    labSelect.html('<option value="">جاري تحميل المعامل...</option>');

                    $.ajax({
                        url: 'get_labs.php',
                        type: 'GET',
                        data: {
                            college_id: collegeId
                        },
                        dataType: 'json',
                        success: function(data) {
                            labSelect.html('<option value="">-- اختر المعمل --</option>');
                            if (data.length > 0) {
                                $.each(data, function(key, value) {
                                    labSelect.append('<option value="' + value.id +
                                        '">' + value.lab_name + '</option>');
                                });
                            } else {
                                labSelect.append(
                                    '<option value="">لا توجد معامل لهذه الكلية</option>');
                            }
                        },
                        error: function() {
                            labSelect.html('<option value="">خطأ في تحميل البيانات</option>');
                        }
                    });
                } else {
                    // تعطيل القائمة إذا لم يتم اختيار كلية
                    labSelect.prop('disabled', true);
                    labSelect.html('<option value="">-- اختر الكلية أولاً --</option>');
                }
            });
        });
    </script>
    <script>
        $(document).ready(function() {
            $('#branch_select').on('change', function() {
                var branchId = $(this).val();
                var collegeSelect = $('#college_select');

                if (branchId) {
                    // تفعيل القائمة وإظهار رسالة تحميل
                    collegeSelect.prop('disabled', false);
                    collegeSelect.html('<option value="">جاري التحميل...</option>');

                    // طلب AJAX لجلب الكليات
                    $.ajax({
                        url: 'get_colleges.php', // ملف المعالجة
                        type: 'GET',
                        data: {
                            branch_id: branchId
                        },
                        dataType: 'json',
                        success: function(data) {
                            collegeSelect.html('<option value="">-- اختر الكلية --</option>');
                            $.each(data, function(key, value) {
                                collegeSelect.append('<option value="' + value.id +
                                    '">' + value.college_name + '</option>');
                            });
                        }
                    });
                } else {
                    collegeSelect.prop('disabled', true);
                    collegeSelect.html('<option value="">-- اختر الفرع أولاً --</option>');
                }
            });
        });
    </script>
    <script>
        $(function() {
            $("#example1").DataTable();
            $('#example2').DataTable({
                "paging": true,
                "lengthChange": false,
                "searching": false,
                "ordering": true,
                "info": true,
                "autoWidth": false,
            });
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