<?php
session_start();
require_once "csdl.php";

if (!isset($_SESSION['user']) || !isset($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$maDH = (int)$_GET['id'];
$username = $_SESSION['user']['username'];

// Kiểm tra xem đơn hàng này có phải của user đang đăng nhập không
$isOwner = false;
$dhInfo = null;

try {
    // Lấy thông tin đơn hàng và user
    $stmt = $connection->prepare("
        SELECT dh.* FROM DonHang dh 
        JOIN KhachHang kh ON dh.MaKH = kh.MaKH 
        WHERE dh.MaDH = ? AND kh.TenDangNhap = ?
    ");
    $stmt->execute([$maDH, $username]);
    if ($dhInfo = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $isOwner = true;
    }
} catch (PDOException $e) {
    die("Lỗi CSDL: " . $e->getMessage());
}

if (!$isOwner) {
    echo "<script>alert('Bạn không có quyền xem đơn hàng này!'); window.location.href='lichsudonhang.php';</script>";
    exit();
}

// Lấy chi tiết đơn hàng
try {
    $stmtCT = $connection->prepare("
        SELECT ct.*, sp.TenSanPham, sp.HinhAnh 
        FROM ChiTietDonHang ct 
        JOIN SanPham sp ON ct.MaSanPham = sp.MaSanPham 
        WHERE ct.MaDH = ?
    ");
    $stmtCT->execute([$maDH]);
    $chiTiet = $stmtCT->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Lỗi CSDL: " . $e->getMessage());
}

?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Chi Tiết Đơn Hàng #<?php echo $maDH; ?></title>
    <!-- Google Font -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { font-family: 'Poppins', sans-serif; background-color: #f4f7f6; margin: 0; padding: 20px; color: #333; }
        .container { max-width: 900px; margin: 20px auto; background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
        .back-link { display: inline-block; margin-bottom: 20px; color: #3498db; text-decoration: none; font-weight: 500; }
        .info-group { margin-bottom: 25px; background: #fafafa; padding: 20px; border-left: 4px solid #3498db; }
        .info-group h3 { margin-top: 0; color: #2c3e50; font-size: 16px; margin-bottom: 10px; }
        .info-group p { margin: 5px 0; font-size: 14px; color: #555; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #ddd; padding: 12px; text-align: left; }
        th { background: #f8f9fa; font-weight: 600; }
        .product-img { width: 50px; height: 70px; object-fit: cover; vertical-align: middle; margin-right: 10px; }
        .total-price { font-size: 18px; font-weight: bold; color: #e74c3c; text-align: right; margin-top: 20px; }
        .status-badge { padding: 5px 10px; border-radius: 20px; font-size: 13px; font-weight: bold; display: inline-block; }
        .status-choxuly { background: #f39c12; color: white; }
        .status-hoanthanh { background: #2ecc71; color: white; }
        .status-dahuy { background: #e74c3c; color: white; }
    </style>
</head>
<body>

<div class="container">
    <a href="lichsudonhang.php" class="back-link"><i class="fa-solid fa-arrow-left"></i> Quay lại lịch sử</a>
    
    <h2 style="border-bottom: 2px solid #eee; padding-bottom: 10px;">Chi Tiết Đơn Hàng #<?php echo $maDH; ?></h2>
    
    <div class="info-group">
        <h3>Thông Tin Giao Hàng</h3>
        <p><strong>Người nhận:</strong> <?php echo htmlspecialchars($dhInfo['HoTenNguoiNhan']); ?></p>
        <p><strong>Số điện thoại:</strong> <?php echo htmlspecialchars($dhInfo['SDTNguoiNhan']); ?></p>
        <p><strong>Địa chỉ:</strong> <?php echo htmlspecialchars($dhInfo['DiaChiGiaoHang']); ?></p>
        <p><strong>Phương thức TT:</strong> <?php echo htmlspecialchars($dhInfo['PhuongThucTT']); ?></p>
        <p><strong>Ghi chú:</strong> <?php echo nl2br(htmlspecialchars($dhInfo['GhiChu'] ?? 'Không có')); ?></p>
    </div>
    
    <div class="info-group" style="border-left-color: #f39c12;">
        <h3>Trạng Thái Đơn Hàng</h3>
        <?php 
            $statusClass = 'status-choxuly';
            if($dhInfo['TrangThai'] == 'Hoàn Thành') $statusClass = 'status-hoanthanh';
            else if($dhInfo['TrangThai'] == 'Đã Hủy') $statusClass = 'status-dahuy';
        ?>
        <p>Ngày đặt: <?php echo date('H:i d/m/Y', strtotime($dhInfo['NgayDat'])); ?></p>
        <p>Tình trạng: <span class="status-badge <?php echo $statusClass; ?>"><?php echo htmlspecialchars($dhInfo['TrangThai']); ?></span></p>
    </div>
    
    <h3>Sản Phẩm Đã Đặt</h3>
    <table>
        <thead>
            <tr>
                <th>Sản phẩm</th>
                <th style="text-align:center;">Đơn giá</th>
                <th style="text-align:center;">Số lượng</th>
                <th style="text-align:right;">Thành tiền</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($chiTiet as $item): ?>
                <tr>
                    <td>
                        <img src="images/<?php echo htmlspecialchars($item['HinhAnh']); ?>" class="product-img"> 
                        <?php echo htmlspecialchars($item['TenSanPham']); ?>
                    </td>
                    <td style="text-align:center;"><?php echo number_format($item['DonGia'], 0, ',', '.'); ?> đ</td>
                    <td style="text-align:center;"><?php echo $item['SoLuong']; ?></td>
                    <td style="text-align:right; font-weight:bold;"><?php echo number_format($item['ThanhTien'], 0, ',', '.'); ?> đ</td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    
    <div class="total-price">
        Tổng Cần Thanh Toán: <?php echo number_format($dhInfo['TongTien'], 0, ',', '.'); ?> đ
    </div>
</div>

</body>
</html>
