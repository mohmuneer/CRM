<?php
// تأكد من بدء الجلسة إذا لم تكن قد بدأت
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. جلب البيانات من الجلسة
$userName = $_SESSION['full_name'] ?? 'مستخدم';
$userImg  = $_SESSION['file_path'] ?? '';

// 2. إعداد مسارات الصور
$base_url = '/crm/admin/';
$webUploadsDir = "/crm/uploads/";
$serverUploadsDir = $_SERVER['DOCUMENT_ROOT'] . "/crm/uploads/";

// 3. التحقق من الصورة
if (!empty($userImg) && file_exists($serverUploadsDir . $userImg)) {
    $fullImagePath = $webUploadsDir . $userImg;
} else {
    $fullImagePath = $base_url . "dist/img/avatar5.png";
}

// 4. جلب إعدادات المظهر مع معالجة حالة البيانات الفارغة
$stmt_v = $pdo->query("SELECT * FROM system_visuals LIMIT 1");
$visuals = $stmt_v->fetch();

// إذا لم توجد بيانات، نضع قيم افتراضية لمنع ظهور الخطأ (Trying to access array offset)
if (!$visuals) {
    $visuals = [
        'system_font' => 'Cairo',
        'header_color' => '#ffffff',
        'sidebar_color' => '#343a40'
    ];
}

$header_bg_color = $visuals['header_color'];
$system_font = $visuals['system_font'];

// تحديد كلاس النص بناءً على لون الخلفية (تقريبي)
// يمكنك تحسين المنطق ليعرف إذا كان اللون غامقاً أم فاتحاً
$header_text_class = (in_array(strtolower($header_bg_color), ['#000', '#000000', 'black', 'dark'])) ? 'navbar-dark' : 'navbar-light';
?>
<style>
    @import url('https://fonts.googleapis.com/css2?family=Almarai&family=Cairo&family=Tajawal&display=swap');

    body {
        font-family: '<?php echo $visuals['system_font']; ?>', sans-serif !important;
        overflow-x: hidden !important;
        scrollbar-width: none;
    }
</style>
<!-- Left navbar links -->
<nav class="main-header navbar navbar-expand <?php echo $header_text_class; ?>" style="background-color: <?php echo $header_bg_color; ?> !important; border-bottom: 1px solid #dee2e6;font-family: '<?php echo $system_font; ?>', sans-serif !important; 
            border-bottom: 1px solid #dee2e6;">
    <ul class="navbar-nav">
        <li class="nav-item">
            <a class="nav-link" data-widget="pushmenu" href="#"><i class="fas fa-bars"></i></a>
        </li>
        <li class="nav-item d-none d-sm-inline-block">
            <a href="#" class="nav-link">Home</a>
        </li>
        <li class="nav-item d-none d-sm-inline-block">
            <a href="#" class="nav-link">Contact</a>
        </li>
    </ul>



    <!-- Right navbar links -->
    <ul class="navbar-nav mr-auto-navbav">
        <!-- Messages Dropdown Menu -->
        <li class="nav-item dropdown">
            <a class="nav-link" data-toggle="dropdown" href="#">
                <i class="far fa-comments"></i>
                <span class="badge badge-danger navbar-badge">3</span>
            </a>
            <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
                <a href="#" class="dropdown-item">
                    <!-- Message Start -->
                    <div class="media">
                        <img src="dist/img/user1-128x128.jpg" alt="User Avatar" class="img-size-50 mr-3 img-circle">
                        <div class="media-body">
                            <h3 class="dropdown-item-title">
                                Brad Diesel
                                <span class="float-right text-sm text-danger"><i class="fas fa-star"></i></span>
                            </h3>
                            <p class="text-sm">Call me whenever you can...</p>
                            <p class="text-sm text-muted"><i class="far fa-clock mr-1"></i> 4 Hours Ago</p>
                        </div>
                    </div>
                    <!-- Message End -->
                </a>
                <div class="dropdown-divider"></div>
                <a href="#" class="dropdown-item">
                    <!-- Message Start -->
                    <div class="media">
                        <img src="dist/img/user8-128x128.jpg" alt="User Avatar" class="img-size-50 img-circle mr-3">
                        <div class="media-body">
                            <h3 class="dropdown-item-title">
                                John Pierce
                                <span class="float-right text-sm text-muted"><i class="fas fa-star"></i></span>
                            </h3>
                            <p class="text-sm">I got your message bro</p>
                            <p class="text-sm text-muted"><i class="far fa-clock mr-1"></i> 4 Hours Ago</p>
                        </div>
                    </div>
                    <!-- Message End -->
                </a>
                <div class="dropdown-divider"></div>
                <a href="#" class="dropdown-item">
                    <!-- Message Start -->
                    <div class="media">
                        <img src="dist/img/user3-128x128.jpg" alt="User Avatar" class="img-size-50 img-circle mr-3">
                        <div class="media-body">
                            <h3 class="dropdown-item-title">
                                Nora Silvester
                                <span class="float-right text-sm text-warning"><i class="fas fa-star"></i></span>
                            </h3>
                            <p class="text-sm">The subject goes here</p>
                            <p class="text-sm text-muted"><i class="far fa-clock mr-1"></i> 4 Hours Ago</p>
                        </div>
                    </div>
                    <!-- Message End -->
                </a>
                <div class="dropdown-divider"></div>
                <a href="#" class="dropdown-item dropdown-footer">See All Messages</a>
            </div>
        </li>
        <!-- Notifications Dropdown Menu -->
        <li class="nav-item dropdown">
            <a class="nav-link" data-toggle="dropdown" href="#">
                <i class="far fa-bell"></i>
                <span class="badge badge-warning navbar-badge">15</span>
            </a>
            <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
                <span class="dropdown-item dropdown-header">15 Notifications</span>
                <div class="dropdown-divider"></div>
                <a href="#" class="dropdown-item">
                    <i class="fas fa-envelope mr-2"></i> 4 new messages
                    <span class="float-right text-muted text-sm">3 mins</span>
                </a>
                <div class="dropdown-divider"></div>
                <a href="#" class="dropdown-item">
                    <i class="fas fa-users mr-2"></i> 8 friend requests
                    <span class="float-right text-muted text-sm">12 hours</span>
                </a>
                <div class="dropdown-divider"></div>
                <a href="#" class="dropdown-item">
                    <i class="fas fa-file mr-2"></i> 3 new reports
                    <span class="float-right text-muted text-sm">2 days</span>
                </a>
                <div class="dropdown-divider"></div>
                <a href="#" class="dropdown-item dropdown-footer">See All Notifications</a>
            </div>
        </li>
        <li class="nav-item">
            <a class="nav-link" data-widget="control-sidebar" data-slide="true" href="#">
                <i class="fas fa-th-large"></i>
            </a>
        </li>
    </ul>
</nav>