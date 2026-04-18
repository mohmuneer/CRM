<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require __DIR__ . "/../../../config/db.php";

try {
    $sql = "SELECT 
    u.id, 
    u.full_name, 
    u.email, 
    u.file_path, 
    u.status, 
    GROUP_CONCAT(DISTINCT r.role_name SEPARATOR ', ') AS all_roles,
    GROUP_CONCAT(DISTINCT b.branch_name SEPARATOR ' - ') AS all_branches
FROM users u
-- استخدمنا INNER JOIN هنا لأننا نشترط وجود الدور 'SupTech'
INNER JOIN user_permision up ON u.id = up.user_id
INNER JOIN roles r ON up.role_id = r.id
-- أبقينا الـ LEFT JOIN هنا لأنه قد يكون هناك موظف ليس له فرع محدد بعد
LEFT JOIN user_branches ub ON u.id = ub.user_id
LEFT JOIN branch b ON ub.branch_id = b.id
WHERE LOWER(r.role_code) = LOWER('SupTech')
  AND u.status =1
GROUP BY u.id;";

    $allUsers = $pdo->query($sql)->fetchAll();

    // استعلام يجلب البلاغات مع ربط الجداول لجلب الأسماء بدلاً من الـ IDs
    $sql = "SELECT r.*, 
               b.branch_name, 
               c.college_name, 
               g.group_name 
        FROM requests r
        LEFT JOIN branch b ON r.branch_id = b.id
        LEFT JOIN colleges c ON r.college_id = c.id
        LEFT JOIN groups g ON r.issue_type_id = g.id
        WHERE r.status = 'Pending'
        ORDER BY r.created_at DESC";

    $requests = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $allUsers = [];
    $requests = [];
    // يمكنك تفعيل هذا السطر للتصحيح: echo $e->getMessage();
}

// 2. معالجة الإسناد
if (isset($_POST['assign_task'])) {
    // $task_title هنا تمثل الـ ID الخاص بالبلاغ المختار من Select2
    $request_id   = !empty($_POST['task_title']) ? $_POST['task_title'] : null;
    $assigned_to  = $_POST['assigned_to'];
    $priority     = $_POST['priority'];
    $deadline     = $_POST['deadline'];
    $details      = trim($_POST['details']);
    $created_by   = $_SESSION['user_id'] ?? 1;

    try {
        // بدء عملية (Transaction) لضمان تنفيذ الخطوتين معاً أو تراجعهما في حال الخطأ
        $pdo->beginTransaction();

        // 1. إدخال المهمة الجديدة في جدول tasks
        $insertSql = "INSERT INTO tasks (request_id, assigned_to, priority, deadline, details, status, created_by, created_at) 
                      VALUES (?, ?, ?, ?, ?, 'Pending', ?, NOW())";
        $stmt = $pdo->prepare($insertSql);
        $stmt->execute([$request_id, $assigned_to, $priority, $deadline, $details, $created_by]);

        // 2. تحديث حالة البلاغ الأصلي في جدول requests ليصبح "قيد التنفيذ"
        if ($request_id) {
            $updateReqSql = "UPDATE requests SET status = 'In Progress' WHERE id = ?";
            $updateStmt = $pdo->prepare($updateReqSql);
            $updateStmt->execute([$request_id]);
        }

        $pdo->commit(); // اعتماد التغييرات

        echo "<script>
                    sessionStorage.setItem('swal_title', 'تمت العملية!');
                    sessionStorage.setItem('swal_text', 'تم إضافة الفرع بنجاح');
                    sessionStorage.setItem('swal_icon', 'success');
                    window.location.href = '../tables/show-tasks.php'; 
                </script>";
    } catch (PDOException $e) {
        $pdo->rollBack(); // التراجع عن التغييرات في حال حدوث أي خطأ
        die("خطأ في قاعدة البيانات: " . $e->getMessage());
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
    <link rel="stylesheet" href="../../plugins/select2/css/select2.min.css">
    <link rel="stylesheet" href="../../plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css">
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
        height: 60px;

    }


    .section-title {
        border-right: 5px solid;
        background: var(--uni-color);
        padding: 0px;
        margin-bottom: 0px;
        font-weight: bold;

    }

    .card-ticket {
        border: 1px solid #ddd;
        border-top: 3px solid;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        background: var(--uni-color);
    }



    .priority-urgent {
        color: #d9534f;
        font-weight: bold;
    }

    /* تعديل مكان زر المسح (Clear button) */
    .select2-container--default .select2-selection--single .select2-selection__clear {
        float: left !important;
        margin-left: 10px !important;
        margin-right: 0 !important;
    }

    /* التأكد من محاذاة النص لليمين داخل حاوية Select2 */
    .select2-container {
        direction: rtl;
        text-align: right;
    }

    /* إصلاح تموضع علامة الـ x عند استخدام ثيم بوتستراب 4 مع RTL */
    .select2-container--bootstrap4 .select2-selection--single .select2-selection__clear {
        float: left !important;
        /* نقلها لليسار */
        margin-right: 0 !important;
        margin-left: 0.5rem !important;
        /* إعطاؤها مساحة بسيطة من الحافة */
        position: relative;
        z-index: 1;
    }

    /* التأكد من أن السهم الصغير يظل في مكانه الصحيح (أقصى اليسار عادة في RTL) */
    .select2-container--bootstrap4[dir="rtl"] .select2-selection--single .select2-selection__arrow {
        left: 3px !important;
        right: auto !important;
    }

    /* إعطاء مساحة للنص حتى لا يغطي على علامة الـ x */
    .select2-container--bootstrap4[dir="rtl"] .select2-selection--single .select2-selection__rendered {
        padding-left: 40px !important;
        padding-right: 8px !important;
    }
    </style>
</head>

<body class="hold-transition sidebar-mini layout-fixed">

    <div class="wrapper">


        <?php include(__DIR__ . '/../../main-header.php'); ?>




        <?php include(__DIR__ . '/../../main-sidebar.php'); ?>



        <div class="content-wrapper">
            <section class="content mt-4">

                <div class="container-fluid">

                    <form method="POST">
                        <div class="row mb-3">
                            <div class="col-sm-12">
                                <div class="main-header-uni d-flex justify-content-between align-items-center p-3 shadow-sm"
                                    style="border-radius: 0px; background-color: var(--uni-color); color: white;">
                                    <div class="header-title">
                                        <h5 class="mb-0">
                                            <i class="fas fa-university ml-2"></i>
                                            بوابة الدعم الفني والصيانة
                                        </h5>
                                    </div>
                                    <nav aria-label="breadcrumb">
                                        <ol class="breadcrumb mb-0" style="background: transparent; padding: 0;">
                                            <li class="breadcrumb-item">
                                                <a href="../../index.php"
                                                    style="color: rgba(255,255,255,0.8);">الرئيسية</a>
                                            </li>
                                            <li class="breadcrumb-item active" style="color: white;">المعامل</li>
                                        </ol>
                                    </nav>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12">
                                <div class="card card-custom p-3">

                                    <div class="form-group mt-3">
                                        <label><i class="fas fa-user-search ml-2"></i> البحث عن موظف دعم فني</label>
                                        <select id="search_employee" name="assigned_to"
                                            class="form-control select2-custom" style="width: 100%;" required>
                                            <option value="">اكتب اسم الموظف هنا...</option>
                                            <?php foreach ($allUsers as $staff): ?>
                                            <option value="<?= $staff['id'] ?>">
                                                <?= htmlspecialchars($staff['full_name']) ?>
                                                - <?= htmlspecialchars($staff['all_roles']) ?>
                                                - <?= htmlspecialchars($staff['all_branches']) ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <div class="form-group mt-3">
                                        <label><i class="fas fa-tasks ml-1"></i> البحث عن مهمة محددة</label>
                                        <select id="search_task" name="task_title" class="form-control select2-custom"
                                            style="width: 100%;" required>
                                            <option value="">اكتب (رقم المهمة، الفرع، أو التفاصيل) للبحث...</option>
                                            <?php foreach ($requests as $task):
                                                $p_text = ($task['priority'] == 'High' || $task['priority'] == 'Emergency') ? '!!! طارئ' : ($task['priority'] == 'Medium' ? 'متوسط' : 'عادي');
                                            ?>
                                            <option value="<?= $task['id'] ?>">
                                                <?= htmlspecialchars($task['id']) ?> - <?= $p_text ?>
                                                - <?= htmlspecialchars($task['branch_name']) ?>
                                                - <?= htmlspecialchars($task['college_name']) ?>
                                                (<?= htmlspecialchars($task['group_name']) ?>) :
                                                <?= mb_strimwidth(htmlspecialchars($task['details']), 0, 50, "...") ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>الأولوية</label>
                                                <select name="priority" class="form-control">
                                                    <option value="Normal">عادي</option>
                                                    <option value="Medium" selected>متوسط</option>
                                                    <option value="High">عالي (مستعجل)</option>
                                                    <option value="Critical">حرج (توقف عمل)</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>تاريخ ووقت التسليم النهائي</label>
                                                <input type="datetime-local" name="deadline" class="form-control"
                                                    required>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="form-group mt-3">
                                        <label>ملاحظات إضافية للفني</label>
                                        <textarea name="details" class="form-control" rows="3"
                                            placeholder="أدخل أي تعليمات إضافية هنا..."></textarea>
                                    </div>

                                    <div class="mt-4 pt-3 border-top text-right">
                                        <button type="submit" name="assign_task" class="btn btn-primary">
                                            <i class="fas fa-paper-plane ml-1"></i> اعتماد وإرسال المهمة
                                        </button>
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
    <script src="../../plugins/select2/js/select2.full.min.js"></script>
    <script>
    $(function() {
        // تفعيل Select2 لجميع العناصر التي تحمل الفئة select2
        $('.select2').select2({
            theme: 'bootstrap4',
            placeholder: "ابدأ بالكتابة للبحث...",
            allowClear: true,
            dir: "rtl", // لضمان ظهور النص والبحث بشكل صحيح باللغة العربية
            language: "ar"
        });
    });
    </script>
    <script>
    $(document).ready(function() {
        // تفعيل البحث في القوائم
        $('.select2-custom').select2({
            theme: 'bootstrap4',
            placeholder: "ابدأ الكتابة للبحث...",
            allowClear: true,
            dir: "rtl", // لدعم الاتجاه العربي في نظامك
            language: "ar"
        });

        // إجراء عند اختيار مهمة (مثلاً الانتقال لتفاصيلها)
        // $('#search_task').on('change', function() {
        //     var taskId = $(this).val();
        //     if (taskId) {
        //         window.location.href = 'task-details.php?id=' + taskId;
        //     }
        // });
    });
    </script>
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