<?php
session_start();
require_once "../csdl.php";
if (!isset($_SESSION['user'])) { header("Location: ../dangnhap.php"); exit(); }

// Cập nhật trạng thái đơn hàng
if (isset($_POST['capnhattrangthai'])) {
    $maDH = (int)$_POST['madh'];
    $trangThai = $_POST['trangthai'];
    try {
        $stmt = $connection->prepare("UPDATE DonHang SET TrangThai = ? WHERE MaDH = ?");
        $stmt->execute([$trangThai, $maDH]);
        header("Location: quanlydonhang.php?msg=ok");
        exit();
    } catch (PDOException $e) {}
}

// Xóa đơn hàng
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $maDH = (int)$_GET['id'];
    try {
        $connection->prepare("DELETE FROM ChiTietDonHang WHERE MaDH = ?")->execute([$maDH]);
        $connection->prepare("DELETE FROM DonHang WHERE MaDH = ?")->execute([$maDH]);
        header("Location: quanlydonhang.php");
        exit();
    } catch (PDOException $e) {}
}

// Lấy danh sách đơn hàng
$keyword = $_GET['keyword'] ?? '';
try {
    if ($keyword) {
        $stmt = $connection->prepare("SELECT dh.*, kh.TenKH FROM DonHang dh LEFT JOIN KhachHang kh ON dh.MaKH = kh.MaKH WHERE dh.MaDH LIKE ? OR kh.TenKH LIKE ? OR dh.HoTenNguoiNhan LIKE ? ORDER BY dh.NgayDat DESC");
        $like = "%$keyword%";
        $stmt->execute([$like, $like, $like]);
    } else {
        $stmt = $connection->query("SELECT dh.*, kh.TenKH FROM DonHang dh LEFT JOIN KhachHang kh ON dh.MaKH = kh.MaKH ORDER BY dh.NgayDat DESC");
    }
    $donhangs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) { $donhangs = []; }
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Quản lý Đơn hàng - Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * { box-sizing: border-box; }
        body { font-family: 'Poppins', sans-serif; background-color: #f4f7f6; margin: 0; display: flex; height: 100vh; overflow: hidden; color: #333; }
        .sidebar { width: 250px; background: #2c3e50; color: #ecf0f1; display: flex; flex-direction: column; flex-shrink: 0; }
        .sidebar-header { padding: 20px; background: #1a252f; text-align: center; font-size: 20px; font-weight: bold; }
        .sidebar-menu { flex: 1; padding-top: 20px; list-style: none; margin: 0; padding-left: 0; }
        .sidebar-menu a { display: block; padding: 14px 25px; color: #bdc3c7; text-decoration: none; font-size: 14px; transition: 0.3s; }
        .sidebar-menu a:hover, .sidebar-menu a.active { background: #34495e; color: #fff; border-left: 4px solid #3498db; }
        .sidebar-menu i { margin-right: 12px; width: 18px; text-align: center; }
        .main-content { flex: 1; display: flex; flex-direction: column; overflow: hidden; }
        .header { height: 60px; background: #fff; display: flex; align-items: center; justify-content: flex-end; padding: 0 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.08); }
        .header a { color: #e74c3c; text-decoration: none; font-weight: bold; }
        .content { flex: 1; padding: 25px; overflow-y: auto; }
        .card { background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.05); margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; font-size: 13px; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: left; vertical-align: middle; }
        th { background: #3498db; color: white; }
        .badge { padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: bold; display: inline-block; }
        .b-cho { background: #f39c12; color: white; }
        .b-hoan { background: #2ecc71; color: white; }
        .b-huy { background: #e74c3c; color: white; }
        .b-giao { background: #3498db; color: white; }
        .btn { padding: 5px 12px; border-radius: 4px; cursor: pointer; text-decoration: none; color: white; border: none; font-size: 12px; }
        .btn-view { background: #3498db; }
        .btn-del { background: #e74c3c; }
        .search-bar { display: flex; gap: 10px; margin-bottom: 15px; }
        .search-bar input { flex: 1; padding: 10px; border: 1px solid #ddd; border-radius: 5px; }
        .search-bar button { padding: 10px 20px; background:#3498db; color:white; border:none; border-radius:5px; cursor:pointer; }
        select { padding: 5px 8px; border: 1px solid #ddd; border-radius: 4px; }
        form.inline { display: inline; }
    </style>
</head>
<body>
<div class="sidebar">
    <div class="sidebar-header">📚 Admin Panel</div>
    <ul class="sidebar-menu">
        <li><a href="index.php"><i class="fa-solid fa-gauge"></i> Tổng quan</a></li>
        <li><a href="quanlytheloai.php"><i class="fa-solid fa-list"></i> Thể loại</a></li>
        <li><a href="quanlysanpham.php"><i class="fa-solid fa-book"></i> Sách</a></li>
        <li><a href="quanlydonhang.php" class="active"><i class="fa-solid fa-cart-shopping"></i> Đơn hàng</a></li>
        <li><a href="quanlytaikhoan.php"><i class="fa-solid fa-users"></i> Tài khoản</a></li>
        <li><a href="quanlynhacungcap.php"><i class="fa-solid fa-truck"></i> Nhà cung cấp</a></li>
        <li><a href="quanlynhaphang.php"><i class="fa-solid fa-boxes-stacked"></i> Nhập hàng</a></li>
        <li><a href="thongke.php"><i class="fa-solid fa-chart-bar"></i> Thống kê</a></li>
        <li><a href="../dangxuat.php"><i class="fa-solid fa-right-from-bracket"></i> Đăng xuất</a></li>
    </ul>
</div>
<div class="main-content">
    <div class="header">
        <a href="../dangxuat.php"><i class="fa-solid fa-right-from-bracket"></i> Đăng xuất</a>
    </div>
    <div class="content">
        <h2 style="color:#2c3e50;margin-bottom:20px;">Quản Lý Đơn Hàng</h2>
        <?php if(isset($_GET['msg'])): ?><div style="color:#2ecc71;font-weight:bold;margin-bottom:10px;">✓ Cập nhật trạng thái thành công!</div><?php endif; ?>
        <div class="card">
            <form class="search-bar" method="GET">
                <input type="text" name="keyword" placeholder="Tìm theo mã đơn, tên khách hàng..." value="<?php echo htmlspecialchars($keyword); ?>">
                <button type="submit"><i class="fa-solid fa-search"></i> Tìm</button>
            </form>
            <table>
                <thead>
                    <tr>
                        <th>Mã ĐH</th>
                        <th>Ngày Đặt</th>
                        <th>Khách Hàng</th>
                        <th>Người Nhận</th>
                        <th>Tổng Tiền</th>
                        <th>Thanh Toán</th>
                        <th>Trạng Thái</th>
                        <th style="width:200px;">Thao Tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($donhangs as $dh): ?>
                    <tr>
                        <td><strong>#<?php echo $dh['MaDH']; ?></strong></td>
                        <td><?php echo date('d/m/Y H:i', strtotime($dh['NgayDat'])); ?></td>
                        <td><?php echo htmlspecialchars($dh['TenKH'] ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($dh['HoTenNguoiNhan']); ?><br><small><?php echo htmlspecialchars($dh['SDTNguoiNhan']); ?></small></td>
                        <td style="color:#e74c3c;font-weight:bold;"><?php echo number_format($dh['TongTien'],0,',','.'); ?>đ</td>
                        <td><small><?php echo htmlspecialchars($dh['PhuongThucTT']); ?></small></td>
                        <td>
                            <?php
                            $cls = 'b-cho';
                            if($dh['TrangThai']=='Hoàn Thành') $cls='b-hoan';
                            elseif($dh['TrangThai']=='Đã Hủy') $cls='b-huy';
                            elseif($dh['TrangThai']=='Đang Giao') $cls='b-giao';
                            ?>
                            <span class="badge <?php echo $cls; ?>"><?php echo $dh['TrangThai']; ?></span>
                        </td>
                        <td>
                            <a href="../chitietdonhang.php?id=<?php echo $dh['MaDH']; ?>" class="btn btn-view" target="_blank"><i class="fa-solid fa-eye"></i></a>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="madh" value="<?php echo $dh['MaDH']; ?>">
                                <select name="trangthai">
                                    <option value="Chờ Xử Lý" <?php if($dh['TrangThai']=='Chờ Xử Lý') echo 'selected'; ?>>Chờ Xử Lý</option>
                                    <option value="Đang Giao" <?php if($dh['TrangThai']=='Đang Giao') echo 'selected'; ?>>Đang Giao</option>
                                    <option value="Hoàn Thành" <?php if($dh['TrangThai']=='Hoàn Thành') echo 'selected'; ?>>Hoàn Thành</option>
                                    <option value="Đã Hủy" <?php if($dh['TrangThai']=='Đã Hủy') echo 'selected'; ?>>Đã Hủy</option>
                                </select>
                                <button type="submit" name="capnhattrangthai" class="btn" style="background:#2ecc71;">✓</button>
                            </form>
                            <a href="?action=delete&id=<?php echo $dh['MaDH']; ?>" class="btn btn-del" onclick="return confirm('Xóa đơn hàng này?')"><i class="fa-solid fa-trash"></i></a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if(empty($donhangs)): ?>
                    <tr><td colspan="8" style="text-align:center;color:#888;padding:30px;">Không có đơn hàng nào.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</body>
</html>
