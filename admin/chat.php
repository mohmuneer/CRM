<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// التأكد من تسجيل الدخول
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require __DIR__ . "/../config/db.php";

// جلب معرف المستخدم المستهدف من الرابط
$receiver_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;

// التحقق من وجود المستخدم وجلب بياناته
$peer = null;
if ($receiver_id > 0) {
    $stmt_peer = $pdo->prepare("SELECT id, full_name, file_path FROM users WHERE id = ?");
    $stmt_peer->execute([$receiver_id]);
    $peer = $stmt_peer->fetch(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>المحادثات | مركز المراسلة الذكي</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link rel="stylesheet" href="plugins/fontawesome-free/css/all.min.css">
    <link rel="stylesheet" href="dist/css/adminlte.min.css">
    <link rel="stylesheet" href="https://cdn.rtlcss.com/bootstrap/v4.2.1/css/bootstrap.min.css">
    <link href="https://fonts.googleapis.com/css?family=Cairo:400,700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="dist/css/custom.css">

    <style>
    body {
        font-family: 'Cairo', sans-serif;
    }

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

    /* تحسين صندوق الدردشة */
    .direct-chat-messages {
        height: 450px !important;
        padding: 15px;
        background: #f4f6f9;
        scroll-behavior: smooth;
    }

    /* تنسيق فقاعات الدردشة للرسائل الواردة */
    .direct-chat-text {
        border-radius: 15px 15px 0 15px !important;
        background: #ffffff !important;
        border: 1px solid #dee2e6 !important;
        color: #444 !important;
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
    }

    /* تنسيق فقاعات الدردشة للرسائل المرسلة (أنت) */
    .right .direct-chat-text {
        border-radius: 15px 15px 15px 0 !important;
        background: #007bff !important;
        border-color: #007bff !important;
        color: #fff !important;
    }

    .nav-pills .nav-link.active {
        background-color: #007bff;
    }

    .user-panel-item:hover {
        background: rgba(0, 0, 0, 0.05);
        transition: 0.3s;
    }
    </style>
</head>

<body class="hold-transition sidebar-mini layout-fixed">
    <div class="wrapper">

        <?php include(__DIR__ . '/main-header.php'); ?>
        <?php include(__DIR__ . '/main-sidebar.php'); ?>

        <div class="content-wrapper">
            <section class="content-header">


            </section>

            <section class="content">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="card card-outline card-primary shadow-sm">
                                <div class="card-header">
                                    <h3 class="card-title float-right">الزملاء المتصلون</h3>
                                </div>
                                <div class="card-body p-0" style="max-height: 520px; overflow-y: auto;">
                                    <ul class="nav nav-pills flex-column">
                                        <?php
                                        $stmt_u = $pdo->prepare("SELECT id, full_name, file_path FROM users WHERE id != ?");
                                        $stmt_u->execute([$_SESSION['user_id']]);
                                        while ($u = $stmt_u->fetch()) {
                                            $active = ($u['id'] == $receiver_id) ? 'active' : '';
                                            $img = !empty($u['file_path']) ? "../uploads/" . $u['file_path'] : "dist/img/user2-160x160.jpg";
                                            echo "<li class='nav-item user-panel-item'>
                                                    <a href='chat.php?user_id={$u['id']}' class='nav-link $active d-flex align-items-center'>
                                                        <img src='$img' class='img-circle img-size-32 ml-3' alt='User Image' style='border: 1px solid #ddd;'>
                                                        <span>{$u['full_name']}</span>
                                                    </a>
                                                  </li>";
                                        }
                                        ?>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-8">
                            <?php if ($peer): ?>
                            <div class="card card-primary card-outline direct-chat direct-chat-primary shadow-lg">
                                <div class="card-header">
                                    <h3 class="card-title float-right">
                                        <i class="fas fa-comments ml-1"></i>
                                        المحادثة مع: <b><?php echo htmlspecialchars($peer['full_name']); ?></b>
                                    </h3>
                                </div>
                                <div class="card-body">
                                    <div class="direct-chat-messages" id="chat-box">
                                        <div class="text-center mt-5 text-muted">
                                            <i class="fas fa-spinner fa-spin fa-2x"></i>
                                            <p>جاري تحميل الرسائل...</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-footer">
                                    <form id="chat-form">
                                        <div class="input-group">
                                            <input type="hidden" id="receiver_id" value="<?php echo $receiver_id; ?>">
                                            <input type="text" id="message_text" placeholder="اكتب رسالتك هنا..."
                                                class="form-control" autocomplete="off" required>
                                            <span class="input-group-append">
                                                <button type="submit" class="btn btn-primary px-4">
                                                    إرسال <i class="fas fa-paper-plane mr-1"></i>
                                                </button>
                                            </span>
                                        </div>
                                    </form>
                                </div>
                            </div>
                            <?php else: ?>
                            <div class="alert alert-light border text-center shadow-sm py-5">
                                <img src="dist/img/chat-icon.png" alt="Chat" style="width: 80px; opacity: 0.5;"
                                    class="mb-3">
                                <h5>يرجى اختيار مستخدم من القائمة لبدء المراسلة</h5>
                                <p class="text-muted">تواصل مع فريقك وناقش المهام التقنية بسهولة.</p>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

            </section>
        </div>

    </div>

    <script src="plugins/jquery/jquery.min.js"></script>
    <script src="plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="dist/js/adminlte.min.js"></script>

    <script>
    let shouldScroll = true; // متغير للتحقق مما إذا كان يجب التمرير للأسفل

    function fetchMessages() {
        let receiverId = $('#receiver_id').val();
        if (!receiverId) return;

        $.get('fetch_messages.php', {
            user_id: receiverId
        }, function(data) {
            $('#chat-box').html(data);

            if (shouldScroll) {
                scrollToBottom();
            }
        });
    }

    function scrollToBottom() {
        const chatBox = document.getElementById("chat-box");
        chatBox.scrollTop = chatBox.scrollHeight;
    }

    // إرسال الرسالة
    $('#chat-form').on('submit', function(e) {
        e.preventDefault();
        let msg = $('#message_text').val().trim();
        let receiverId = $('#receiver_id').val();

        if (msg !== "") {
            $.post('send_message.php', {
                receiver_id: receiverId,
                message: msg // تأكد أن المفتاح هنا يطابق ما هو في ملف PHP (message)
            }, function(response) {
                $('#message_text').val('');
                shouldScroll = true;
                fetchMessages();
            });
        }
    });

    // تشغيل التحديث التلقائي
    <?php if ($receiver_id > 0): ?>
    $(document).ready(function() {
        fetchMessages();
        setInterval(fetchMessages, 3000); // تحديث كل 3 ثوانٍ
    });
    <?php endif; ?>

    // منع التمرير التلقائي إذا كان المستخدم يقرأ الرسائل القديمة (اختياري)
    $('#chat-box').on('scroll', function() {
        let threshold = 50;
        let isAtBottom = this.scrollHeight - this.scrollTop - this.clientHeight < threshold;
        shouldScroll = isAtBottom;
    });
    </script>
</body>

</html>