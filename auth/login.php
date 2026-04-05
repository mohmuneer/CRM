<?php
session_start();
require "../config/db.php";

$message = "";
$alertType = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($email) || empty($password)) {
        $message = "الرجاء إدخال البريد الإلكتروني وكلمة المرور";
        $alertType = "warning";
    } else {
        try {
            $stmt = $pdo->prepare("
                SELECT u.*, r.role_name 
                FROM users u 
                JOIN roles r ON r.id = u.role_id 
                WHERE email = ?
            ");
            $stmt->execute([$email]);
            $u = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($u && password_verify($password, $u['password'])) {
                session_regenerate_id(true);

                $_SESSION['user_id'] = $u['id'];
                $_SESSION['role']    = $u['role_name'];

                if (password_needs_rehash($u['password'], PASSWORD_DEFAULT)) {
                    $newHash = password_hash($password, PASSWORD_DEFAULT);
                    $updateStmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                    $updateStmt->execute([$newHash, $u['id']]);
                }

                header("Location: ../dashboard/admin/index.php");
                exit;
            } else {
                $message = "البريد الإلكتروني أو كلمة المرور غير صحيحة";
                $alertType = "danger";
            }
        } catch (PDOException $e) {
            $message = "خطأ في الاتصال بقاعدة البيانات";
            $alertType = "danger";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <title>تسجيل الدخول</title>

    <!-- مهم للجوال -->
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">

    <div class="container d-flex justify-content-center align-items-center min-vh-100">
        <form method="post" class="card p-4 shadow w-100" style="max-width: 360px;">
            <h4 class="mb-3 text-center">تسجيل الدخول</h4>

            <?php if (!empty($message)) : ?>
            <div class="alert alert-<?= htmlspecialchars($alertType) ?> text-center">
                <?= htmlspecialchars($message) ?>
            </div>
            <?php endif; ?>

            <input type="email" name="email" class="form-control mb-2" placeholder="البريد الإلكتروني"
                value="<?= htmlspecialchars($email ?? '') ?>" required>

            <input type="password" name="password" class="form-control mb-3" placeholder="كلمة المرور" required>

            <button class="btn btn-primary w-100">دخول</button>
        </form>
    </div>

</body>

</html>