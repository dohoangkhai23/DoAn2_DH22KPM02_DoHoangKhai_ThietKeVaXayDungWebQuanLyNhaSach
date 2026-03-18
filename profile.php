<?php
session_start();
require_once "csdl.php";

if (!isset($_SESSION['user'])) {
    echo "<script>alert('Vui lòng đăng nhập trước!'); window.location.href='dangnhap.php';</script>";
    exit();
}

$username = $_SESSION['user']['username'];
$msg = '';

// Lấy thông tin khách hàng
$kh = null;
try {
    $stmt = $connection->prepare("SELECT * FROM KhachHang WHERE TenDangNhap = ?");
    $stmt->execute([$username]);
    $kh = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Lỗi: " . $e->getMessage());
}

// Cập nhật hồ sơ
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['capnhat'])) {
    $tenKH  = trim($_POST['tenKH']);
    $sdt    = trim($_POST['sdt']);
    $diaChi = trim($_POST['diaChi']);
    
    try {
        if ($kh) {
            $stmt = $connection->prepare("UPDATE KhachHang SET TenKH=?, SDT=?, DiaChi=? WHERE TenDangNhap=?");
            $stmt->execute([$tenKH, $sdt, $diaChi, $username]);
        } else {
            $stmt = $connection->prepare("INSERT INTO KhachHang (TenKH, SDT, DiaChi, TenDangNhap) VALUES (?,?,?,?)");
            $stmt->execute([$tenKH, $sdt, $diaChi, $username]);
        }
        $msg = 'success';
        // Làm mới thông tin
        $stmt2 = $connection->prepare("SELECT * FROM KhachHang WHERE TenDangNhap = ?");
        $stmt2->execute([$username]);
        $kh = $stmt2->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $msg = 'error:' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Hồ Sơ Cá Nhân - Nhà Sách</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/index.css">
    <style>
        .profile-container { max-width: 700px; margin: 50px auto; background: #fff; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); overflow: hidden; }
        .profile-header { background: linear-gradient(135deg, #2c3e50, #3498db); color: white; padding: 40px; text-align: center; }
        .profile-avatar { width: 80px; height: 80px; border-radius: 50%; background: rgba(255,255,255,0.2); display: flex; align-items: center; justify-content: center; margin: 0 auto 15px; font-size: 36px; }
        .profile-header h2 { margin: 0; font-size: 22px; }
        .profile-header p { margin: 5px 0 0; opacity: 0.8; font-size: 14px; }
        .profile-body { padding: 35px; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 600; font-size: 14px; color: #555; }
        .form-group input, .form-group textarea { width: 100%; padding: 12px 15px; border: 1px solid #ddd; border-radius: 6px; font-family: inherit; font-size: 14px; box-sizing: border-box; transition: 0.3s; }
        .form-group input:focus, .form-group textarea:focus { border-color: #3498db; outline: none; box-shadow: 0 0 0 3px rgba(52,152,219,0.1); }
        .form-group .disabled-field { background: #f4f4f4; color: #888; cursor: not-allowed; }
        .btn-save { display: block; width: 100%; padding: 14px; background: linear-gradient(135deg, #2ecc71, #27ae60); color: white; border: none; border-radius: 6px; font-size: 16px; font-weight: bold; cursor: pointer; transition: 0.3s; margin-top: 10px; }
        .btn-save:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(46,204,113,0.4); }
        .links { display: flex; gap: 10px; margin-top: 20px; padding-top: 20px; border-top: 1px solid #eee; justify-content: center; }
        .links a { color: #3498db; text-decoration: none; font-size: 14px; padding: 8px 15px; border: 1px solid #3498db; border-radius: 5px; transition: 0.3s; }
        .links a:hover { background: #3498db; color: white; }
        .success-msg { background: #d5f5e3; color: #1e8449; border: 1px solid #82e0aa; padding: 12px 15px; border-radius: 6px; margin-bottom: 20px; font-weight: 500; }
    </style>
</head>
<body>
<header>
    <div class="logo"><a href="index.php" style="color:white;text-decoration:none;">📚 Nhà Sách</a></div>
    <div class="header-right">
        <a href="lichsudonhang.php" class="club-btn" style="margin-right:10px;background:#3498db;">Đơn Hàng Của Tôi</a>
        <a href="dangxuat.php" class="club-btn" style="background:#e74c3c;">Đăng Xuất</a>
    </div>
</header>

<div class="profile-container">
    <div class="profile-header">
        <div class="profile-avatar">👤</div>
        <h2><?php echo htmlspecialchars($kh['TenKH'] ?? $username); ?></h2>
        <p>@<?php echo htmlspecialchars($username); ?></p>
    </div>
    <div class="profile-body">
        <?php if ($msg == 'success'): ?>
        <div class="success-msg"><i class="fa-solid fa-circle-check"></i> Cập nhật hồ sơ thành công!</div>
        <?php elseif (str_starts_with($msg, 'error')): ?>
        <div style="color:#e74c3c;margin-bottom:15px;">Lỗi: <?php echo explode(':', $msg, 2)[1] ?? ''; ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label><i class="fa-solid fa-user"></i> Tên Đăng Nhập</label>
                <input type="text" value="<?php echo htmlspecialchars($username); ?>" class="disabled-field" readonly>
            </div>
            <div class="form-group">
                <label><i class="fa-solid fa-id-card"></i> Họ Và Tên</label>
                <input type="text" name="tenKH" value="<?php echo htmlspecialchars($kh['TenKH'] ?? ''); ?>" placeholder="Nhập họ và tên..." required>
            </div>
            <div class="form-group">
                <label><i class="fa-solid fa-phone"></i> Số Điện Thoại</label>
                <input type="text" name="sdt" value="<?php echo htmlspecialchars($kh['SDT'] ?? ''); ?>" placeholder="0901234567">
            </div>
            <div class="form-group">
                <label><i class="fa-solid fa-location-dot"></i> Địa Chỉ Giao Hàng</label>
                <input type="text" name="diaChi" value="<?php echo htmlspecialchars($kh['DiaChi'] ?? ''); ?>" placeholder="Số nhà, đường, phường, quận, tỉnh/thành phố...">
            </div>
            <button type="submit" name="capnhat" class="btn-save"><i class="fa-solid fa-floppy-disk"></i> Lưu Thay Đổi</button>
        </form>
        
        <div class="links">
            <a href="doimatkhau.php"><i class="fa-solid fa-key"></i> Đổi Mật Khẩu</a>
            <a href="lichsudonhang.php"><i class="fa-solid fa-receipt"></i> Lịch Sử Đơn Hàng</a>
            <a href="index.php"><i class="fa-solid fa-house"></i> Trang Chủ</a>
        </div>
    </div>
</div>
</body>
</html>
