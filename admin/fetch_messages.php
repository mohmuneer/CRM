<?php
session_start();
include '../config/db.php';

if (!isset($_SESSION['user_id']) || !isset($_GET['user_id'])) {
    exit();
}

$my_id = $_SESSION['user_id'];
$peer_id = intval($_GET['user_id']);

// 1. جلب بيانات الطرف الآخر (الاسم والصورة) لعرضها في المحادثة
$stmt_peer = $pdo->prepare("SELECT full_name, file_path FROM users WHERE id = ?");
$stmt_peer->execute([$peer_id]);
$peer_data = $stmt_peer->fetch();
$peer_name = $peer_data ? $peer_data['full_name'] : 'مستخدم';
$peer_image = (!empty($peer_data['file_path'])) ? "../uploads/" . $peer_data['file_path'] : "dist/img/user2-160x160.jpg";

// 2. جلب بياناتي الشخصية (للصورة والاسم)
$my_image = (!empty($_SESSION['file_path'])) ? "../uploads/" . $_SESSION['file_path'] : "dist/img/user1-128x128.jpg";
$my_name = $_SESSION['full_name'] ?? 'أنا';

// 3. جلب المحادثة بين الطرفين
$stmt = $pdo->prepare("
    SELECT * FROM messages 
    WHERE (sender_id = ? AND receiver_id = ?) 
    OR (sender_id = ? AND receiver_id = ?) 
    ORDER BY created_at ASC
");
$stmt->execute([$my_id, $peer_id, $peer_id, $my_id]);
$messages = $stmt->fetchAll();

foreach ($messages as $msg) {
    $is_me = ($msg['sender_id'] == $my_id);

    // تحديد الصورة والاسم بناءً على المرسل
    $current_name = $is_me ? $my_name : $peer_name;
    $current_image = $is_me ? $my_image : $peer_image;

    echo '
    <div class="direct-chat-msg ' . ($is_me ? 'right' : '') . '">
        <div class="direct-chat-infos clearfix">
            <span class="direct-chat-name float-' . ($is_me ? 'right' : 'left') . '">' . htmlspecialchars($current_name) . '</span>
            <span class="direct-chat-timestamp float-' . ($is_me ? 'left' : 'right') . '">' . date('h:i A', strtotime($msg['created_at'])) . '</span>
        </div>
        
        <img class="direct-chat-img" src="' . $current_image . '" alt="message user image">
        
        <div class="direct-chat-text text-right" style="direction: rtl;">
            ' . htmlspecialchars($msg['message_text']) . '
        </div>
    </div>';
}

// 4. تحديث الرسائل لتصبح "مقروءة" بمجرد فتح المحادثة
$pdo->prepare("UPDATE messages SET is_read = 1 WHERE sender_id = ? AND receiver_id = ? AND is_read = 0")
    ->execute([$peer_id, $my_id]);
