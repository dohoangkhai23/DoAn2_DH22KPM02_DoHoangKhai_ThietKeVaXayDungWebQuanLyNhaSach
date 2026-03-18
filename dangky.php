<?php
session_start();
require_once __DIR__ . "/csdl.php";

$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = trim($_POST["username"] ?? "");
    $password = (string)($_POST["password"] ?? "");
    $confirm = (string)($_POST["confirm"] ?? "");

    if ($username === "" || $password === "" || $confirm === "") {
        $error = "Vui lòng nhập đầy đủ thông tin.";
    } elseif ($password !== $confirm) {
        $error = "Mật khẩu nhập lại không khớp.";
    } else {
        // Kiểm tra xem tên đăng nhập đã tồn tại chưa
        $st = $connection->prepare("SELECT 1 FROM khachhang WHERE TenDangNhap = :u LIMIT 1");
        $st->execute([":u" => $username]);
        $exists = (bool)$st->fetchColumn();
        
        if ($exists) {
            $error = "Tên đăng nhập đã tồn tại.";
        } else {
            // Thêm khách hàng mới, tạm thời để rỗng thông tin cá nhân bắt buộc
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $st = $connection->prepare("INSERT INTO khachhang (TenDangNhap, MatKhau, TenKH, SDT, DiaChi) VALUES (:u, :p, '', '', '')");
            $st->execute([":u" => $username, ":p" => $hash]);
            $_SESSION["register_success"] = "Đăng ký thành công. Vui lòng đăng nhập.";
            header("Location: dangnhap.php");
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng ký</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        body{font-family:'Poppins',sans-serif;background:#f5f6fa;margin:0;display:flex;align-items:center;justify-content:center;min-height:100vh}
        .login-box{width:380px;background:#fff;border:1px solid #eaeaea;border-radius:12px;box-shadow:0 10px 30px rgba(0,0,0,0.06);padding:28px}
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
        <h2>Đăng ký</h2>
        <?php if ($error): ?>
            <div class="error"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>
        <form method="post" action="dangky.php" autocomplete="off">
            <div class="form-group">
                <label>Tên đăng nhập</label>
                <input type="text" name="username" required>
            </div>
            <div class="form-group">
                <label>Mật khẩu</label>
                <input type="password" name="password" required>
            </div>
            <div class="form-group">
                <label>Nhập lại mật khẩu</label>
                <input type="password" name="confirm" required>
            </div>
            <button class="btn" type="submit">Tạo tài khoản</button>
        </form>
        <div class="helper">
            <a href="dangnhap.php">Đã có tài khoản? Đăng nhập</a>
        </div>
    </div>
</body>
</html>

