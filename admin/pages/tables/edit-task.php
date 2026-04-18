<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require __DIR__ . "/../../../config/db.php";

// 1. جلب المعرف من الرابط (الممرر هو request_id)
$request_id_url = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$request_id_url) {
    header("Location: show-tasks.php");
    exit;
}

// 2 & 3. جلب بيانات المهمة بناءً على request_id مدمجة مع بيانات البلاغ
$sql_task = "SELECT t.id as task_id, t.assigned_to, t.priority as task_priority, t.status as task_status, 
                    t.deadline, t.details as task_details,
                    r.id as req_id, r.details as req_details,
                    b.branch_name, c.college_name, g.group_name 
             FROM requests r
             LEFT JOIN tasks t ON r.id = t.request_id
             LEFT JOIN branch b ON r.branch_id = b.id
             LEFT JOIN colleges c ON r.college_id = c.id
             LEFT JOIN groups g ON r.issue_type_id = g.id
             WHERE r.id = ?";

$stmt = $pdo->prepare($sql_task);
$stmt->execute([$request_id_url]);
$task = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$task) {
    die("<div class='alert alert-danger text-center'>عذراً، لا يوجد بلاغ بهذا الرقم. (ID: $request_id_url)</div>");
}

// تحديد المتغيرات للعرض (استخدام بيانات المهمة إن وجدت، وإلا قيم افتراضية)
$request_id = $task['req_id'];
$current_assigned = $task['assigned_to'] ?? '';
$current_priority = $task['task_priority'] ?? 'Normal';
$current_status   = $task['task_status']   ?? 'Pending';
$current_details  = $task['task_details']  ?? '';

// 4. جلب قائمة المهندسين
$sql_techs = "SELECT u.id, u.full_name FROM users u 
              INNER JOIN user_permision up ON u.id = up.user_id 
              INNER JOIN roles r ON up.role_id = r.id 
              WHERE LOWER(r.role_code) = 'suptech' AND u.status = 1 
              GROUP BY u.id";
$technicians = $pdo->query($sql_techs)->fetchAll(PDO::FETCH_ASSOC);

// 5. معالجة التحديث أو الإضافة
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $assigned_to = $_POST['assigned_to'];
    $priority    = $_POST['priority'];
    $status      = $_POST['status'];
    $deadline    = $_POST['deadline'];
    $details     = $_POST['details'];

    // التحقق إذا كانت المهمة موجودة مسبقاً (Update) أم جديدة (Insert)
    if (!empty($task['task_id'])) {
        $query = "UPDATE tasks SET assigned_to = ?, priority = ?, status = ?, deadline = ?, details = ?, updated_at = NOW() 
                  WHERE request_id = ?";
        $params = [$assigned_to, $priority, $status, $deadline, $details, $request_id];
    } else {
        $query = "INSERT INTO tasks (assigned_to, priority, status, deadline, details, request_id, created_at) 
                  VALUES (?, ?, ?, ?, ?, ?, NOW())";
        $params = [$assigned_to, $priority, $status, $deadline, $details, $request_id];
    }

    $stmt_save = $pdo->prepare($query);

    if ($stmt_save->execute($params)) {
        echo "<script>
                sessionStorage.setItem('swal_title', 'تمت العملية بنجاح!');
                sessionStorage.setItem('swal_text', 'تم تحديث بيانات المهمة وإسنادها بنجاح.');
                sessionStorage.setItem('swal_icon', 'success');
                window.location.href = 'show-tasks.php'; 
              </script>";
        exit;
    } else {
        $error_msg = "فشل في حفظ البيانات.";
    }
}
?>
<!DOCTYPE html>
<html lang="ar">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>تعديل مهمة | نظام الدعم الفني</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="../../plugins/fontawesome-free/css/all.min.css">
    <link rel="stylesheet" href="../../dist/css/adminlte.min.css">
    <link rel="stylesheet" href="https://cdn.rtlcss.com/bootstrap/v4.2.1/css/bootstrap.min.css">
    <link rel="stylesheet" href="../../dist/css/custom.css">
    <style>
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
    </style>
</head>


<body class="hold-transition sidebar-mini layout-fixed">
    <div class="wrapper">

        <?php include(__DIR__ . '/../../main-header.php'); ?>
        <?php include(__DIR__ . '/../../main-sidebar.php'); ?>

        <div class="content-wrapper">
            <section class="content-header">
                <div class="container-fluid">
                    <div class="row mb-2 card border-0 shadow-none" style="background: transparent;">
                        <div class="col-sm-12">
                            <div class="card-warning d-flex justify-content-between align-items-center p-3 shadow-sm"
                                style="border-radius:0px; background-color: #ffc107; color: #1f2d3d; border-right: 5px solid #edb100;">

                                <div class="header-title">
                                    <h5 class="mb-0" style="font-weight: bold;">
                                        <i class="fas fa-university ml-2"></i>
                                        بوابة الدعم الفني والصيانة
                                    </h5>
                                </div>

                                <nav aria-label="breadcrumb">
                                    <ol class="breadcrumb mb-0" style="background: transparent; padding: 0;">
                                        <li class="breadcrumb-item">
                                            <a href="../../index.php"
                                                style="color: #212529; font-weight: 500;">الرئيسية</a>
                                        </li>
                                        <li class="breadcrumb-item active" style="color: #495057; font-weight: bold;">
                                            المعامل
                                        </li>
                                    </ol>
                                </nav>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section class="content">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-md-12 mb-4">
                            <div class="card card-warning">
                                <div class="card-header">
                                    <h3 class="card-title float-left">بيانات المهمة رقم <?= $request_id ?></h3>
                                </div>

                                <form method="POST">
                                    <div class="card-body">
                                        <?php if (isset($error_msg)): ?><div class="alert alert-danger">
                                                <?= $error_msg ?></div><?php endif; ?>

                                        <div class="form-group">
                                            <label>المهندس المسند له</label>
                                            <select name="assigned_to" class="form-control" required>
                                                <option value="">-- اختر المهندس --</option>
                                                <?php foreach ($technicians as $tech): ?>
                                                    <option value="<?= $tech['id'] ?>"
                                                        <?= ($tech['id'] == $current_assigned) ? 'selected' : '' ?>>
                                                        <?= htmlspecialchars($tech['full_name']) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>الأولوية</label>
                                                    <select name="priority" class="form-control">
                                                        <option value="Normal"
                                                            <?= ($current_priority == 'Normal' ? 'selected' : '') ?>>
                                                            عادي</option>
                                                        <option value="Medium"
                                                            <?= ($current_priority == 'Medium' ? 'selected' : '') ?>>
                                                            متوسط</option>
                                                        <option value="High"
                                                            <?= ($current_priority == 'High' ? 'selected' : '') ?>>عالي
                                                        </option>
                                                        <option value="Critical"
                                                            <?= ($current_priority == 'Critical' ? 'selected' : '') ?>>
                                                            حرج جداً</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>الحالة</label>
                                                    <select name="status" class="form-control">
                                                        <option value="Pending"
                                                            <?= ($current_status == 'Pending' ? 'selected' : '') ?>>قيد
                                                            الانتظار</option>
                                                        <option value="In Progress"
                                                            <?= ($current_status == 'In Progress' ? 'selected' : '') ?>>
                                                            قيد التنفيذ</option>
                                                        <option value="Completed"
                                                            <?= ($current_status == 'Completed' ? 'selected' : '') ?>>تم
                                                            الإنجاز</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="form-group">
                                            <label>تاريخ التسليم المتوقع</label>
                                            <?php
                                            $deadline_val = (!empty($task['deadline']) && $task['deadline'] != '0000-00-00 00:00:00')
                                                ? date('Y-m-d\TH:i', strtotime($task['deadline'])) : date('Y-m-d\TH:i');
                                            ?>
                                            <input type="datetime-local" name="deadline" class="form-control"
                                                value="<?= $deadline_val ?>" required>
                                        </div>

                                        <div class="form-group">
                                            <label>ملاحظات إضافية للفني</label>
                                            <textarea name="details" class="form-control"
                                                rows="3"><?= htmlspecialchars($current_details) ?></textarea>
                                        </div>

                                        <div class="callout callout-info">
                                            <h5><i class="fas fa-info"></i> تفاصيل البلاغ الأصلي:</h5>
                                            <p><?= nl2br(htmlspecialchars($task['req_details'])) ?></p>
                                        </div>
                                    </div>

                                    <div class="card-footer text-left">
                                        <button type="submit" class="btn btn-warning px-5"><b>حفظ التعديلات</b></button>
                                        <a href="show-tasks.php" class="btn btn-default">إلغاء</a>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>

        <footer class="main-footer text-center">
            <strong>جميع الحقوق محفوظة &copy; <?= date('Y') ?></strong>
        </footer>
    </div>

    <script src="../../plugins/jquery/jquery.min.js"></script>
    <script src="../../plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../../dist/js/adminlte.min.js"></script>
</body>

</html>