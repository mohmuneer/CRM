<?php
$base_url = '/crm/admin/';
$current_page = basename($_SERVER['PHP_SELF']);
$base_url = '/crm/admin/';
$current_page = basename($_SERVER['PHP_SELF']);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. جلب البيانات من الجلسة
$userName = $_SESSION['full_name'] ?? 'مستخدم';
$userImg  = $_SESSION['file_path'] ?? '';

// 2. تحديد المسارات
// المسار الرابط (للمتصفح): يبدأ من جذر الموقع /crm/
$webUploadsDir = "/crm/uploads/";
// المسار الفيزيائي (للسيرفر): للتحقق من وجود الملف
$serverUploadsDir = $_SERVER['DOCUMENT_ROOT'] . "/crm/uploads/";

// 3. التحقق المنطقي من الصورة
if (!empty($userImg) && file_exists($serverUploadsDir . $userImg)) {
    // إذا كانت الصورة موجودة في مجلد uploads
    $fullImagePath = $webUploadsDir . $userImg;
} else {
    // صورة افتراضية في حال عدم رفع صورة أو فقدان الملف
    // تأكد من أن هذا المسار يؤدي فعلاً لصورة القالب الافتراضية
    $fullImagePath = "/crm/admin/dist/img/avatar5.png";
}

$stmt = $pdo->query("SELECT system_name, system_logo FROM system_settings LIMIT 1");
$settings = $stmt->fetch();
// تحديد مسار الصورة: إذا كان هناك لوجو في القاعدة نستخدمه، وإلا نستخدم اللوجو الافتراضي
$logoPath = (!empty($settings['system_logo']))
    ? "../../dist/img/" . $settings['system_logo']
    : "../../dist/img/AdminLTELogo.png";

$systemName = (!empty($settings['system_name']))
    ? $settings['system_name']
    : "STS-UST-TAIZ";
// قائمة صفحات بيانات المستخدمين
$users_pages = [
    'add-user.php',
    'show-users.php',
    'reports-users.php'
];

// قائمة صفحات صلاحيات المستخدمين
$permissions_pages = [
    'add-role.php',
    'assign-permissions.php',
    'view-permissions.php'
];
// قائمة صفحات القاعات 
$university_pages = [
    'add-branch.php',
    'add-college.php',
    'add-group.php',
    'add-lab.php'
];
// قائمة صفحات القاعات 
$request_pages = [
    'add-request.php',
    'show-requests.php',
    'report-requests.php',
];
// قائمة صفحات لبلاغات 
$task_pages = [
    'add-task.php',
    'show-tasks.php',
    'report-tasks.php',
];

// قائمة صفحات تهيئة النظام
$settings_pages = [
    'system-settings.php',
    'system-inputs.php',
    'system-buckup.php',
    'show-settings.php',
    'show-logs.php'
];

// قائمة صفحات المهام 
$send_pages = [
    'add-send.php',
    'show-sends.php',
    'report-sends.php',
];
?>
<aside class="main-sidebar sidebar-dark-primary elevation-4"
    style="background-color: <?php echo $visuals['sidebar_color']; ?> !important;">
    <a href="../../index.php" class="brand-link">
        <img src="<?php echo $logoPath; ?>" alt="System Logo" class="brand-image img-circle elevation-3"
            style="opacity: .8; width: 33px; height: 33px; object-fit: cover;">
        <span class="brand-text font-weight-light"><?php echo htmlspecialchars($systemName); ?></span>
    </a>
    <div class="sidebar">
        <div class="user-panel mt-3 pb-3 mb-3 d-flex">
            <div class="image">
                <img src="<?php echo $fullImagePath; ?>" class="img-circle elevation-2" alt="User Image"
                    style="width: 2.1rem; height: 2.1rem; object-fit: cover; border: 1px solid #adb5bd;">
            </div>
            <div class="info">
                <a href="#" class="d-block">
                    <?php echo htmlspecialchars($userName); ?>
                </a>
            </div>
        </div>


        <nav class="mt-2">
            <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
                <li class="nav-item has-treeview <?= in_array($current_page, $settings_pages) ? 'menu-open' : ''; ?>">
                    <a href="#" class="nav-link <?= in_array($current_page, $settings_pages) ? 'active' : ''; ?>">
                        <i class="nav-icon fas fa-cogs"></i>
                        <p>
                            تهيئة النظام
                            <i class="right fas fa-angle-left"></i>
                        </p>
                    </a>

                    <ul class="nav nav-treeview">
                        <li class="nav-item">
                            <a href="<?= $base_url ?>pages/forms/system-settings.php"
                                class="nav-link <?= ($current_page == 'system-settings.php') ? 'active' : ''; ?>">
                                <i class="far fa-circle nav-icon"></i>
                                <p>الإعدادات العامة</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="<?= $base_url ?>pages/tables/show-settings.php"
                                class="nav-link <?= ($current_page == 'show-settings.php') ? 'active' : ''; ?>">
                                <i class="far fa-circle nav-icon"></i>
                                <p> عرض الإعدادات العامة</p>
                            </a>
                        </li>

                        <li class="nav-item">
                            <a href="<?= $base_url ?>pages/forms/system-inputs.php"
                                class="nav-link <?= ($current_page == 'system-inputs.php') ? 'active' : ''; ?>">
                                <i class="far fa-circle nav-icon"></i>
                                <p>تهيئة المدخلات</p>
                            </a>
                        </li>

                        <li class="nav-item">
                            <a href="<?= $base_url ?>pages/forms/system-buckup.php"
                                class="nav-link <?= ($current_page == 'system-buckup.php') ? 'active' : ''; ?>">
                                <i class="far fa-circle nav-icon"></i>
                                <p>النسخ الاحتياطي</p>
                            </a>
                        </li>

                        <li class="nav-item">
                            <a href="<?= $base_url ?>pages/tables/show-logs.php"
                                class="nav-link <?= ($current_page == 'show-logs.php') ? 'active' : ''; ?>">
                                <i class="far fa-circle nav-icon"></i>
                                <p>سجل النظام (Logs)</p>
                            </a>
                        </li>
                    </ul>
                </li>
                <!-- بيانات المستخدمين -->
                <li class="nav-item has-treeview <?= in_array($current_page, $users_pages) ? 'menu-open' : ''; ?>">
                    <a href="#" class="nav-link <?= in_array($current_page, $users_pages) ? 'active' : ''; ?>">
                        <i class="nav-icon fas fa-chart-pie"></i>
                        <p>
                            بيانات المستخدمين
                            <i class="right fas fa-angle-left"></i>
                        </p>
                    </a>

                    <ul class="nav nav-treeview">
                        <li class="nav-item">
                            <a href="<?= $base_url ?>pages/forms/add-user.php"
                                class="nav-link <?= ($current_page == 'add-user.php') ? 'active' : ''; ?>">
                                <i class="far fa-circle nav-icon"></i>
                                <p>إضافة مستخدم</p>
                            </a>
                        </li>

                        <li class="nav-item">
                            <a href="<?= $base_url ?>pages/tables/show-users.php"
                                class="nav-link <?= ($current_page == 'show-users.php') ? 'active' : ''; ?>">
                                <i class="far fa-circle nav-icon"></i>
                                <p>عرض المستخدمين</p>
                            </a>
                        </li>



                        <li class="nav-item">
                            <a href="<?= $base_url ?>pages/tables/reports-users.php"
                                class="nav-link <?= ($current_page == 'reports-users.php') ? 'active' : ''; ?>">
                                <i class="far fa-circle nav-icon"></i>
                                <p>تقارير المستخدمين</p>
                            </a>
                        </li>
                    </ul>
                </li>

                <!-- صلاحيات المستخدمين -->
                <li
                    class="nav-item has-treeview <?= in_array($current_page, $permissions_pages) ? 'menu-open' : ''; ?>">
                    <a href="#" class="nav-link <?= in_array($current_page, $permissions_pages) ? 'active' : ''; ?>">
                        <i class="nav-icon fas fa-user-shield"></i>
                        <p>
                            صلاحيات المستخدمين
                            <i class="right fas fa-angle-left"></i>
                        </p>
                    </a>

                    <ul class="nav nav-treeview">
                        <li class="nav-item">
                            <a href="<?= $base_url ?>pages/forms/add-role.php"
                                class="nav-link <?= ($current_page == 'add-role.php') ? 'active' : ''; ?>">
                                <i class="far fa-circle nav-icon"></i>
                                <p>إضافة صلاحية</p>
                            </a>
                        </li>

                        <li class="nav-item">
                            <a href="<?= $base_url ?>pages/tables/assign-permissions.php"
                                class="nav-link <?= ($current_page == 'assign-permissions.php') ? 'active' : ''; ?>">
                                <i class="far fa-circle nav-icon"></i>
                                <p>تعيين الصلاحيات</p>
                            </a>
                        </li>

                        <li class="nav-item">
                            <a href="<?= $base_url ?>pages/tables/view-permissions.php"
                                class="nav-link <?= ($current_page == 'view-permissions.php') ? 'active' : ''; ?>">
                                <i class="far fa-circle nav-icon"></i>
                                <p>عرض الصلاحيات</p>
                            </a>
                        </li>
                    </ul>
                </li>
                <!--  بيانات الجامعة  -->
                <li class="nav-item has-treeview <?= in_array($current_page, $university_pages) ? 'menu-open' : ''; ?>">
                    <a href="#" class="nav-link <?= in_array($current_page, $university_pages) ? 'active' : ''; ?>">
                        <i class="nav-icon fas fa-university"></i>
                        <p>إدارة المعامل
                            <i class="right fas fa-angle-left"></i>
                        </p>
                    </a>

                    <ul class="nav nav-treeview">
                        <li class="nav-item">
                            <a href="<?= $base_url ?>pages/forms/add-branch.php"
                                class="nav-link <?= ($current_page == 'add-branch.php') ? 'active' : ''; ?>">
                                <i class="far fa-circle nav-icon"></i>
                                <p>إضافة فرع</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="<?= $base_url ?>pages/forms/add-college.php"
                                class="nav-link <?= ($current_page == 'add-college.php') ? 'active' : ''; ?>">
                                <i class="far fa-circle nav-icon"></i>
                                <p>إضافة كلية</p>
                            </a>
                        </li>

                        <li class="nav-item">
                            <a href="<?= $base_url ?>pages/forms/add-group.php"
                                class="nav-link <?= ($current_page == 'add-group.php') ? 'active' : ''; ?>">
                                <i class="far fa-circle nav-icon"></i>
                                <p>اضافة مجموعة</p>
                            </a>
                        </li>

                        <li class="nav-item">
                            <a href="<?= $base_url ?>pages/forms/add-lab.php"
                                class="nav-link <?= ($current_page == 'add-lab.php') ? 'active' : ''; ?>">
                                <i class="far fa-circle nav-icon"></i>
                                <p>اضافة معمل</p>
                            </a>
                        </li>
                    </ul>
                </li>
                <!--  بيانات البلاغات  -->
                <li class="nav-item has-treeview <?= in_array($current_page, $request_pages) ? 'menu-open' : ''; ?>">
                    <a href="#" class="nav-link <?= in_array($current_page, $request_pages) ? 'active' : ''; ?>">
                        <i class="nav-icon fas fa-tools"></i>
                        <p>إدارة البلاغات
                            <i class="right fas fa-angle-left"></i>
                        </p>
                    </a>

                    <ul class="nav nav-treeview">
                        <li class="nav-item">
                            <a href="<?= $base_url ?>pages/forms/add-request.php"
                                class="nav-link <?= ($current_page == 'add-request.php') ? 'active' : ''; ?>">
                                <i class="far fa-circle nav-icon"></i>
                                <p>إضافة بلاغ</p>
                            </a>
                        </li>

                        <li class="nav-item">
                            <a href="<?= $base_url ?>pages/tables/show-requests.php"
                                class="nav-link <?= ($current_page == 'show-requests.php') ? 'active' : ''; ?>">
                                <i class="far fa-circle nav-icon"></i>
                                <p>عرض البلاغات</p>
                            </a>
                        </li>

                        <li class="nav-item">
                            <a href="<?= $base_url ?>pages/tables/report-requests.php"
                                class="nav-link <?= ($current_page == 'report-requests.php') ? 'active' : ''; ?>">
                                <i class="far fa-circle nav-icon"></i>
                                <p>تقارير البلاغات</p>
                            </a>
                        </li>
                    </ul>
                </li>
                <!--  بيانات المهام  -->
                <li class="nav-item has-treeview <?= in_array($current_page, $task_pages) ? 'menu-open' : ''; ?>">
                    <a href="#" class="nav-link <?= in_array($current_page, $task_pages) ? 'active' : ''; ?>">
                        <i class="nav-icon fas fa-sitemap"></i>
                        <p>قائمة المهام للفنيين
                            <i class="right fas fa-angle-left"></i>
                        </p>
                    </a>

                    <ul class="nav nav-treeview">
                        <li class="nav-item">
                            <a href="<?= $base_url ?>pages/forms/add-task.php"
                                class="nav-link <?= ($current_page == 'add-task.php') ? 'active' : ''; ?>">
                                <i class="far fa-circle nav-icon"></i>
                                <p>إضافة مهمة</p>
                            </a>
                        </li>

                        <li class="nav-item">
                            <a href="<?= $base_url ?>pages/tables/show-tasks.php"
                                class="nav-link <?= ($current_page == 'show-tasks.php') ? 'active' : ''; ?>">
                                <i class="far fa-circle nav-icon"></i>
                                <p>عرض المهام</p>
                            </a>
                        </li>

                        <li class="nav-item">
                            <a href="<?= $base_url ?>pages/tables/report-tasks.php"
                                class="nav-link <?= ($current_page == 'report-tasks.php') ? 'active' : ''; ?>">
                                <i class="far fa-circle nav-icon"></i>
                                <p>تقارير المهام</p>
                            </a>
                        </li>
                    </ul>
                </li>


            </ul>
        </nav>
    </div>
</aside>