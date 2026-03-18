<?php
session_start();
require_once "csdl.php";

if (!isset($_SESSION['user'])) {
    echo "<script>alert('Vui lòng đăng nhập để xem lịch sử đơn hàng!'); window.location.href='dangnhap.php';</script>";
    exit();
}

$user = $_SESSION['user'];
$username = $user['username'];

// Lấy MaKH
$maKH = null;
try {
    $stmt = $connection->prepare("SELECT MaKH FROM KhachHang WHERE TenDangNhap = ?");
    $stmt->execute([$username]);
    if ($kh = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $maKH = $kh['MaKH'];
    }
} catch (PDOException $e) {
    die("Lỗi CSDL: " . $e->getMessage());
}

if (!$maKH) {
    echo "Không tìm thấy thông tin khách hàng.";
    exit;
}

// Lấy danh sách đơn hàng của khách
try {
    $stmtDH = $connection->prepare("SELECT * FROM DonHang WHERE MaKH = ? ORDER BY NgayDat DESC");
    $stmtDH->execute([$maKH]);
    $donHangs = $stmtDH->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Lỗi lấy đơn hàng: " . $e->getMessage());
}

?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Lịch Sử Đơn Hàng - Nhà Sách</title>
    <!-- Google Font -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/index.css">
    <style>
        .container { max-width: 1000px; margin: 40px auto; padding: 20px; background: #fff; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.05); }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 12px; text-align: center; }
        th { background: #3498db; color: white; }
        .status-badge { padding: 5px 10px; border-radius: 20px; font-size: 13px; font-weight: bold; }
        .status-choxuly { background: #f39c12; color: white; }
        .status-hoanthanh { background: #2ecc71; color: white; }
        .status-dahuy { background: #e74c3c; color: white; }
        .btn-view { padding: 6px 15px; background: #3498db; color: #fff; text-decoration: none; border-radius: 4px; font-size: 13px; }
        .btn-view:hover { background: #2980b9; }
        .btn-cancel { padding: 6px 15px; background: #e74c3c; color: #fff; text-decoration: none; border-radius: 4px; font-size: 13px; margin-left:10px; border:none; cursor:pointer;}
        .empty-history { text-align: center; padding: 50px; color: #7f8c8d; }
    </style>
</head>
<body>

<header>
    <div class="logo"><a href="index.php" style="color:white; text-decoration:none;">📚 Nhà Sách</a></div>
    <div class="header-right">
        <a href="index.php" class="club-btn">Trở về Trang Chủ</a>
    </div>
</header>

<div class="container">
    <h2 style="color: #2c3e50; border-bottom: 2px solid #eee; padding-bottom: 10px;">Lịch Sử Đơn Hàng Của Bạn</h2>
    
    <?php if (empty($donHangs)): ?>
        <div class="empty-history">
            <h3>Bạn chưa có đơn hàng nào!</h3>
            <p>Hãy dạo quanh Nhà Sách và chọn những cuốn sách yêu thích nhé.</p>
            <a href="SanPham.php" class="btn btn-view" style="padding:10px 20px;">Mua sắm ngay</a>
        </div>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Mã Đơn</th>
                    <th>Ngày Đặt</th>
                    <th>Người Nhận</th>
                    <th>Tổng Tiền</th>
                    <th>Trạng Thái</th>
                    <th>Chi Tiết</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($donHangs as $dh): ?>
                    <tr>
                        <td><strong>#<?php echo $dh['MaDH']; ?></strong></td>
                        <td><?php echo date('H:i d/m/Y', strtotime($dh['NgayDat'])); ?></td>
                        <td><?php echo htmlspecialchars($dh['HoTenNguoiNhan']); ?></td>
                        <td style="color:#e74c3c; font-weight:bold;"><?php echo number_format($dh['TongTien'], 0, ',', '.'); ?> đ</td>
                        <td>
                            <?php 
                                $statusClass = 'status-choxuly';
                                if($dh['TrangThai'] == 'Hoàn Thành') $statusClass = 'status-hoanthanh';
                                else if($dh['TrangThai'] == 'Đã Hủy') $statusClass = 'status-dahuy';
                            ?>
                            <span class="status-badge <?php echo $statusClass; ?>"><?php echo htmlspecialchars($dh['TrangThai']); ?></span>
                        </td>
                        <td>
                            <a href="chitietdonhang.php?id=<?php echo $dh['MaDH']; ?>" class="btn-view"><i class="fa-solid fa-eye"></i> Xem</a>
                            <?php if ($dh['TrangThai'] == 'Chờ Xử Lý'): ?>
                                <form action="huydonhang.php" method="POST" style="display:inline;">
                                    <input type="hidden" name="madh" value="<?php echo $dh['MaDH']; ?>">
                                    <button type="submit" class="btn-cancel" onclick="return confirm('Bạn có chắc chắn muốn hủy đơn hàng này không?');"><i class="fa-solid fa-ban"></i> Hủy</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

</body>
</html>
