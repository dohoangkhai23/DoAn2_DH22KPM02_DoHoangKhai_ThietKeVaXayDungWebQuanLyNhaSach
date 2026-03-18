<?php
session_start();
require_once __DIR__ . "/csdl.php";

$error = "";
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = isset($_POST["username"]) ? trim($_POST["username"]) : "";
    $password = isset($_POST["password"]) ? (string)$_POST["password"] : "";

    if ($username === "" || $password === "") {
        $error = "Vui lòng nhập đầy đủ thông tin.";
    } else {
        $user = null;

        try {
            $st = $connection->prepare("SELECT TenDangNhap AS username, MatKhau AS password FROM taikhoan WHERE TenDangNhap = :u LIMIT 1");
            $st->execute([":u" => $username]);
            $user = $st->fetch(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {}

        if (!$user) {
            try {
                $st = $connection->prepare("SELECT TenDangNhap AS username, MatKhau AS password FROM khachhang WHERE TenDangNhap = :u LIMIT 1");
                $st->execute([":u" => $username]);
                $user = $st->fetch(PDO::FETCH_ASSOC);
            } catch (Throwable $e) {}
        }

        $ok = false;
        if ($user && isset($user["password"])) {
            $hash = $user["password"];
            if (password_verify($password, $hash) || hash_equals((string)$hash, $password)) {
                $ok = true;
            }
        }

        if ($ok) {
            $_SESSION["user"] = [
                "id" => $user["id"] ?? null,
                "username" => $user["username"] ?? $username
            ];
            header("Location: index.php");
            exit;
        } else {
            $error = "Tài khoản hoặc mật khẩu không đúng.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        body{font-family:'Poppins',sans-serif;background:#f5f6fa;margin:0;display:flex;align-items:center;justify-content:center;min-height:100vh}
        .login-box{width:360px;background:#fff;border:1px solid #eaeaea;border-radius:12px;box-shadow:0 10px 30px rgba(0,0,0,0.06);padding:28px}
        .login-box h2{margin:0 0 18px;font-weight:600;font-size:22px}
        .form-group{margin-bottom:14px}
        .form-group label{display:block;margin-bottom:6px;font-size:13px;color:#444}
        .form-group input{width:100%;padding:10px 12px;border:1px solid #ddd;border-radius:8px;font-size:14px;outline:none}
        .btn{width:100%;padding:12px 16px;border:none;border-radius:8px;background:#1abc9c;color:#fff;font-weight:600;cursor:pointer}
        .btn:hover{background:#16a085}
        .error{background:#ffe8e8;color:#c62828;border:1px solid #ffcdd2;padding:10px 12px;border-radius:8px;margin-bottom:12px;font-size:13px}
        .helper{margin-top:12px;text-align:center;font-size:13px}
        .helper a{text-decoration:none;color:#1abc9c}
    </style>
    </head>
<body>
    <div class="login-box">
        <h2>Đăng nhập</h2>
        <?php if (!empty($_SESSION['register_success'])): ?>
            <div class="error" style="background:#e8fff0;color:#1b5e20;border-color:#c8e6c9;">
                <?php echo htmlspecialchars($_SESSION['register_success'], ENT_QUOTES, 'UTF-8'); unset($_SESSION['register_success']); ?>
            </div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="error"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>
        <form method="post" action="dangnhap.php" autocomplete="off">
            <div class="form-group">
                <label>Tên đăng nhập</label>
                <input type="text" name="username" required>
            </div>
            <div class="form-group">
                <label>Mật khẩu</label>
                <input type="password" name="password" required>
            </div>
            <button class="btn" type="submit">Đăng nhập</button>
        </form>
        <div class="helper">
            <a href="dangky.php">Chưa có tài khoản? Đăng ký</a> • <a href="index.php">Về trang chủ</a>
        </div>
    </div>
</body>
</html>
