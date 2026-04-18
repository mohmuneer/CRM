<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. جلب البيانات من الجلسة
$userName = $_SESSION['full_name'] ?? 'مستخدم';
$userImg  = $_SESSION['file_path'] ?? '';

// 2. إعداد المسارات بشكل مرن
// استخدم مسارات نسبية داخل الموقع لضمان عمل الروابط
$web_base = "/admin/";
$webUploadsDir = "/uploads/";

// 3. التحقق من الصورة باستخدام المسار الفيزيائي للسيرفر
// $_SERVER['DOCUMENT_ROOT'] هو الحل الأفضل في الاستضافات الحية
$serverUploadsDir = $_SERVER['DOCUMENT_ROOT'] . $webUploadsDir;

if (!empty($userImg) && file_exists($serverUploadsDir . $userImg)) {
    $fullImagePath = $webUploadsDir . $userImg;
} else {
    // تأكد أن هذا المسار صحيح في مدير الملفات لديك
    $fullImagePath = $web_base . "dist/img/avatar5.png";
}

// 4. جلب إعدادات المظهر
try {
    $stmt_v = $pdo->query("SELECT * FROM system_visuals LIMIT 1");
    $visuals = $stmt_v->fetch();
} catch (Exception $e) {
    $visuals = false;
}

if (!$visuals) {
    $visuals = [
        'system_font' => 'Cairo',
        'header_color' => '#ffffff',
        'sidebar_color' => '#343a40'
    ];
}

$header_bg_color = $visuals['header_color'];
$system_font = $visuals['system_font'];

// دالة بسيطة لتحديد لون النص (أبيض أو أسود) بناءً على خلفية الهيدر
function getContrastColor($hexColor)
{
    $hexColor = str_replace('#', '', $hexColor);
    if (strlen($hexColor) < 6) return 'navbar-light';
    $r = hexdec(substr($hexColor, 0, 2));
    $g = hexdec(substr($hexColor, 2, 2));
    $b = hexdec(substr($hexColor, 4, 2));
    // معادلة السطوع
    $yiq = (($r * 299) + ($g * 587) + ($b * 114)) / 1000;
    return ($yiq >= 128) ? 'navbar-light' : 'navbar-dark';
}

$header_text_class = getContrastColor($header_bg_color);

// / جلب عدد البلاغات غير المكتملة (أو الجديدة)
$stmt_count = $pdo->prepare("SELECT COUNT(*) FROM requests WHERE status = 'pending'");
$stmt_count->execute();
$total_requests = $stmt_count->fetchColumn();

// جلب آخر 5 بلاغات لعرضها في القائمة المنسدلة
$stmt_list = $pdo->prepare("SELECT id, created_at FROM requests ORDER BY created_at DESC LIMIT 5");
$stmt_list->execute();
$recent_requests = $stmt_list->fetchAll();
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
            <a class="nav-link" data-widget="pushmenu" href="#">
                <i class="fas fa-bars"></i></a>
        </li>
        <li class="nav-item d-none d-sm-inline-block">
            <a href="index.php" class="nav-link">Home</a>
        </li>
        <li class="nav-item d-none d-sm-inline-block">
            <a href="/crm/admin/chat.php" class="nav-link">Contact</a>
        </li>
    </ul>



    <!-- Right navbar links -->
    <ul class="navbar-nav mr-auto-navbav">
        <!-- Messages Dropdown Menu -->
        <li class="nav-item dropdown">
            <?php
            // جلب عدد الرسائل غير المقروءة الموجهة للمستخدم الحالي
            $my_id = $_SESSION['user_id'];
            $stmt_count = $pdo->prepare("SELECT COUNT(*) FROM messages WHERE receiver_id = ? AND is_read = 0");
            $stmt_count->execute([$my_id]);
            $unread_count = $stmt_count->fetchColumn();

            // جلب آخر 3 رسائل مع بيانات المرسل (بافتراض وجود جدول users)
            $stmt_msg = $pdo->prepare("
        SELECT m.*, u.full_name, u.file_path 
        FROM messages m 
        JOIN users u ON m.sender_id = u.id 
        WHERE m.receiver_id = ? 
        ORDER BY m.created_at DESC LIMIT 3
    ");
            $stmt_msg->execute([$my_id]);
            $latest_messages = $stmt_msg->fetchAll();
            ?>

            <a class="nav-link" data-toggle="dropdown" href="#">
                <i class="far fa-comments"></i>
                <?php if ($unread_count > 0): ?>
                <span class="badge badge-danger navbar-badge"><?php echo $unread_count; ?></span>
                <?php endif; ?>
            </a>

            <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
                <?php if (count($latest_messages) > 0): ?>
                <?php foreach ($latest_messages as $msg):
                        $sender_img = !empty($msg['file_path']) ? $webUploadsDir . $msg['file_path'] : $web_base . "dist/img/avatar5.png";
                    ?>
                <a href="chat.php?user_id=<?php echo $msg['sender_id']; ?>" class="dropdown-item">
                    <div class="media">
                        <img src="<?php echo $sender_img; ?>" alt="User Avatar" class="img-size-50 mr-3 img-circle">
                        <div class="media-body">
                            <h3 class="dropdown-item-title">
                                <?php echo $msg['full_name']; ?>
                                <span
                                    class="float-right text-sm <?php echo ($msg['is_read'] == 0) ? 'text-danger' : 'text-muted'; ?>">
                                    <i class="fas fa-star"></i>
                                </span>
                            </h3>
                            <p class="text-sm"><?php echo mb_strimwidth($msg['message_text'], 0, 30, "..."); ?></p>
                            <p class="text-sm text-muted">
                                <i class="far fa-clock mr-1"></i>
                                <?php echo date('h:i A', strtotime($msg['created_at'])); ?>
                            </p>
                        </div>
                    </div>
                </a>
                <div class="dropdown-divider"></div>
                <?php endforeach; ?>
                <a href="all_messages.php" class="dropdown-item dropdown-footer">عرض كافة الرسائل</a>
                <?php else: ?>
                <p class="text-center p-3">لا توجد رسائل جديدة</p>
                <?php endif; ?>
            </div>
        </li>
        <!-- Notifications Dropdown Menu -->
        <li class="nav-item dropdown">
            <a class="nav-link" data-toggle="dropdown" href="#">
                <i class="far fa-bell"></i>
                <?php if ($total_requests > 0): ?>
                <span class="badge badge-warning navbar-badge"><?php echo $total_requests; ?></span>
                <?php endif; ?>
            </a>
            <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
                <span class="dropdown-item dropdown-header"><?php echo $total_requests; ?> بلاغات جديدة</span>

                <div class="dropdown-divider"></div>

                <?php if (count($recent_requests) > 0): ?>
                <?php foreach ($recent_requests as $req): ?>
                <a href="/crm/admin/pages/forms/view-request.php?id=<?php echo $req['id']; ?>" class="dropdown-item">
                    <i class="fas fa-file-alt mr-2"></i>

                    <span class="float-right text-muted text-sm">
                        <?php
                                // حساب الوقت المنقضي (تبسيط)
                                echo date('H:i', strtotime($req['created_at']));
                                ?>
                    </span>
                </a>
                <div class="dropdown-divider"></div>
                <?php endforeach; ?>
                <?php else: ?>
                <a href="#" class="dropdown-item text-center">لا توجد بلاغات حالياً</a>
                <?php endif; ?>

                <a href="/crm/admin/pages/tables/show-requests.php" class="dropdown-item dropdown-footer">عرض كافة
                    البلاغات</a>
            </div>
        </li>
        <a class="nav-link" href="/crm/admin/logout.php" onclick="return confirm('هل أنت متأكد من تسجيل الخروج؟')">
            <i class="fas fa-sign-out-alt"></i>
        </a>
    </ul>
</nav>

<script>
function updateUnreadCount() {
    $.get("get_unread_count.php", function(count) {
        if (count > 0) {
            $('.navbar-badge').first().text(count).show();
        } else {
            $('.navbar-badge').first().hide();
        }
    });
}

// تحديث العدد كل 10 ثوانٍ لتقليل الضغط على السيرفر
setInterval(updateUnreadCount, 10000);
// وظيفة إرسال الرسالة
function sendMessage(receiverId, text) {
    $.post("send_message.php", {
        receiver_id: receiverId,
        message: text
    }, function(data) {
        $('#chat-input').val(''); // مسح الحقل بعد الإرسال
        loadMessages(receiverId); // تحديث الشات
    });
}

// وظيفة تحديث الرسائل تلقائياً كل 3 ثوانٍ
setInterval(function() {
    if (currentChatId) {
        loadMessages(currentChatId);
    }
}, 3000);
</script>