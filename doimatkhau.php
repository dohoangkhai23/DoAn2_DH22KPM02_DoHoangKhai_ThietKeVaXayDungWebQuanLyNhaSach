<?php
session_start();
require_once __DIR__ . "/csdl.php";

// Yêu cầu đăng nhập
if (!isset($_SESSION['user'])) {
    header("Location: dangnhap.php");
    exit;
}

$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $current_password = (string)($_POST["current_password"] ?? "");
    $new_password = (string)($_POST["new_password"] ?? "");
    $confirm_password = (string)($_POST["confirm_password"] ?? "");
    $username = $_SESSION['user']['username'];

    if ($current_password === "" || $new_password === "" || $confirm_password === "") {
        $error = "Vui lòng nhập đầy đủ thông tin.";
    } elseif ($new_password !== $confirm_password) {
        $error = "Mật khẩu mới không khớp.";
    } else {
        $user = null;
        $tableName = "taikhoan";
        $passwordCol = "MatKhau";
        $idCol = "TenDangNhap"; // Dùng chính TenDangNhap làm khoá nếu không có ID rõ ràng

        // Tìm kiếm trong bảng taikhoan
        try {
            $st = $connection->prepare("SELECT TenDangNhap AS id, TenDangNhap AS username, MatKhau AS password FROM taikhoan WHERE TenDangNhap = :u LIMIT 1");
            $st->execute([":u" => $username]);
            $user = $st->fetch(PDO::FETCH_ASSOC);
            if ($user) { $tableName = "taikhoan"; $passwordCol = "MatKhau"; $idCol = "TenDangNhap"; }
        } catch (Throwable $e) {}

        // Tìm kiếm trong bảng khachhang
        if (!$user) {
            try {
                $st = $connection->prepare("SELECT TenDangNhap AS id, TenDangNhap AS username, MatKhau AS password FROM khachhang WHERE TenDangNhap = :u LIMIT 1");
                $st->execute([":u" => $username]);
                $user = $st->fetch(PDO::FETCH_ASSOC);
                if ($user) { $tableName = "khachhang"; $passwordCol = "MatKhau"; $idCol = "TenDangNhap"; }
            } catch (Throwable $e) {}
        }

        if (!$user) {
            $error = "Lỗi hệ thống: Không tìm thấy tài khoản để đổi mật khẩu.";
        } else {
            // Xác minh current password
            $hash = $user["password"];
            $ok = false;
            // password_verify cho mã băm, hash_equals cho plain text (nếu có sẵn plain text)
            if (password_verify($current_password, $hash) || hash_equals((string)$hash, $current_password)) {
                $ok = true;
            }

            if (!$ok) {
                $error = "Mật khẩu hiện tại không đúng.";
            } else {
                // Băm mật khẩu mới và cập nhật
                $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
                $updateSt = $connection->prepare("UPDATE {$tableName} SET {$passwordCol} = :p WHERE {$idCol} = :id");
                $updateSt->execute([":p" => $new_hash, ":id" => $user["id"]]);
                $success = "Đổi mật khẩu thành công!";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đổi mật khẩu</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        body{font-family:'Poppins',sans-serif;background:#f5f6fa;margin:0;display:flex;align-items:center;justify-content:center;min-height:100vh}
        .login-box{width:380px;background:#fff;border:1px solid #eaeaea;border-radius:12px;box-shadow:0 10px 30px rgba(0,0,0,0.06);padding:28px}
        .login-box h2{margin:0 0 18px;font-weight:600;font-size:22px;text-align:center;}
        .form-group{margin-bottom:14px}
        .form-group label{display:block;margin-bottom:6px;font-size:13px;color:#444}
        .form-group input{width:100%;padding:10px 12px;border:1px solid #ddd;border-radius:8px;font-size:14px;outline:none;box-sizing:border-box;}
        .btn{width:100%;padding:12px 16px;border:none;border-radius:8px;background:#ff8c00;color:#fff;font-weight:600;cursor:pointer;margin-top:10px;}
        .btn:hover{background:#e67e22}
        .error{background:#ffe8e8;color:#c62828;border:1px solid #ffcdd2;padding:10px 12px;border-radius:8px;margin-bottom:12px;font-size:13px}
        .success{background:#e8fff0;color:#1b5e20;border:1px solid #c8e6c9;padding:10px 12px;border-radius:8px;margin-bottom:12px;font-size:13px}
        .helper{margin-top:16px;text-align:center;font-size:14px}
        .helper a{text-decoration:none;color:#555;transition:0.3s;}
        .helper a:hover{color:#ff8c00;}
    </style>
</head>
<body>
    <div class="login-box">
        <h2>Đổi mật khẩu</h2>
        
        <?php if ($error): ?>
            <div class="error"><i class="fa-solid fa-triangle-exclamation"></i> <?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="success"><i class="fa-solid fa-check-circle"></i> <?php echo htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>

        <form method="post" action="doimatkhau.php">
            <div class="form-group">
                <label>Mật khẩu hiện tại</label>
                <input type="password" name="current_password" required>
            </div>
            <div class="form-group">
                <label>Mật khẩu mới</label>
                <input type="password" name="new_password" required>
            </div>
            <div class="form-group">
                <label>Nhập lại mật khẩu mới</label>
                <input type="password" name="confirm_password" required>
            </div>
            <button class="btn" type="submit">Cập nhật mật khẩu</button>
        </form>
        <div class="helper">
            <a href="index.php"><i class="fa-solid fa-arrow-left"></i> Về trang chủ</a>
        </div>
    </div>
</body>
</html>
