<?php
session_start();
require_once "../csdl.php";

// Kiểm tra quyền truy cập (Giả sử chỉ có role 'Admin' hoặc có MaNV trong taikhoan mới được vào)
// Hiện tại trong csdl: bảng taikhoan (TenDangNhap, MatKhau, MaNV, VaiTro)
if (!isset($_SESSION['user'])) {
    header("Location: ../dangnhap.php");
    exit();
}

// Kiểm tra role: Nếu bạn chưa set cứng role lúc login, tạm thời cho phép hoặc check VaiTro
// Trong dangnhap.php, $_SESSION['user'] = ['username' => ..., 'role' => ...];
$role = $_SESSION['user']['role'] ?? '';
if ($role !== 'Admin' && $role !== 'NhanVien') {
    // Nếu chưa thiết lập VaiTro, tạm thời cho ai cũng vào được hoặc thông báo lỗi
    // Để chạy được demo, mình tạm mở cho mọi user. Khi bạn phân quyền rõ, hãy bật lại dòng dưới:
    // echo "<script>alert('Bạn không có quyền truy cập trang quản trị!'); window.location.href='../index.php';</script>"; exit();
}

// Lấy thống kê cơ bản
$tongDonHang = 0;
$tongDoanhThu = 0;
$tongKhachHang = 0;
$tongSach = 0;

try {
    // Tổng đơn hàng
    $stmt1 = $connection->query("SELECT COUNT(*) FROM DonHang");
    $tongDonHang = $stmt1->fetchColumn();
    
    // Tổng doanh thu (Chỉ tính đơn đã hoàn thành, hoặc lấy tất cả tuỳ ý bạn)
    $stmt2 = $connection->query("SELECT SUM(TongTien) FROM DonHang WHERE TrangThai='Hoàn Thành'");
    $tongDoanhThu = $stmt2->fetchColumn() ?: 0;
    
    // Tổng khách hàng
    $stmt3 = $connection->query("SELECT COUNT(*) FROM KhachHang");
    $tongKhachHang = $stmt3->fetchColumn();
    
    // Tổng sách
    $stmt4 = $connection->query("SELECT COUNT(*) FROM SanPham");
    $tongSach = $stmt4->fetchColumn();
    
} catch (PDOException $e) {
    // Bỏ qua lỗi thống kê nếu bảng chưa có dữ liệu
}

?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard - Nhà Sách</title>
    <!-- Google Font -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { font-family: 'Poppins', sans-serif; background-color: #f4f7f6; margin: 0; padding: 0; color: #333; display: flex; height: 100vh; overflow: hidden; }
        
        /* Sidebar */
        .sidebar { width: 250px; background: #2c3e50; color: #ecf0f1; display: flex; flex-direction: column; }
        .sidebar-header { padding: 20px; background: #1a252f; text-align: center; font-size: 20px; font-weight: bold; border-bottom: 1px solid #34495e; }
        .sidebar-menu { flex: 1; overflow-y: auto; padding-top: 20px; list-style: none; margin: 0; padding-left: 0; }
        .sidebar-menu li { margin-bottom: 5px; }
        .sidebar-menu a { display: block; padding: 15px 25px; color: #bdc3c7; text-decoration: none; font-size: 15px; transition: 0.3s; }
        .sidebar-menu a:hover, .sidebar-menu a.active { background: #34495e; color: #fff; border-left: 4px solid #3498db; }
        .sidebar-menu i { margin-right: 15px; width: 20px; text-align: center; }
        
        /* Main Content */
        .main-content { flex: 1; display: flex; flex-direction: column; overflow: hidden; }
        
        /* Header */
        .header { height: 60px; background: #fff; box-shadow: 0 2px 10px rgba(0,0,0,0.1); display: flex; align-items: center; justify-content: space-between; padding: 0 30px; }
        .user-info { display: flex; align-items: center; gap: 15px; }
        .user-info a { color: #e74c3c; text-decoration: none; font-weight: bold; }
        
        /* Content Area */
        .content { flex: 1; padding: 30px; overflow-y: auto; background: #f4f7f6; }
        .page-title { margin-top: 0; color: #2c3e50; font-size: 24px; margin-bottom: 30px; }
        
        /* Stats Dashboard */
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: #fff; border-radius: 8px; padding: 25px; box-shadow: 0 4px 10px rgba(0,0,0,0.05); display: flex; align-items: center; gap: 20px; }
        .stat-icon { width: 60px; height: 60px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 24px; color: white; }
        .icon-blue { background: #3498db; }
        .icon-green { background: #2ecc71; }
        .icon-orange { background: #f39c12; }
        .icon-red { background: #e74c3c; }
        .stat-info h4 { margin: 0; font-size: 14px; color: #7f8c8d; font-weight: 500; }
        .stat-info .number { font-size: 24px; font-weight: bold; color: #2c3e50; margin-top: 5px; display: block; }
        
    </style>
</head>
<body>

<!-- Sidebar -->
<div class="sidebar">
    <div class="sidebar-header">
        📚 Admin Panel
    </div>
    <ul class="sidebar-menu">
        <li><a href="index.php" class="active"><i class="fa-solid fa-gauge"></i> Tổng quan</a></li>
        <li><a href="quanlytheloai.php"><i class="fa-solid fa-list"></i> Quản lý Thể loại</a></li>
        <li><a href="quanlysanpham.php"><i class="fa-solid fa-book"></i> Quản lý Sách</a></li>
        <li><a href="quanlydonhang.php"><i class="fa-solid fa-cart-shopping"></i> Quản lý Đơn hàng</a></li>
        <li><a href="quanlytaikhoan.php"><i class="fa-solid fa-users"></i> Quản lý Tài khoản</a></li>
        <li><a href="../index.php" target="_blank"><i class="fa-solid fa-globe"></i> Xem Trang chủ</a></li>
    </ul>
</div>

<!-- Main Content -->
<div class="main-content">
    <div class="header">
        <div class="toggle-btn"><i class="fa-solid fa-bars" style="font-size:20px; cursor:pointer;"></i></div>
        <div class="user-info">
            <span>Xin chào, <strong><?php echo htmlspecialchars($_SESSION['user']['username']); ?></strong></span>
            <a href="../dangxuat.php"><i class="fa-solid fa-right-from-bracket"></i> Đăng xuất</a>
        </div>
    </div>
    
    <div class="content">
        <h2 class="page-title">Tổng quan (Dashboard)</h2>
        
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon icon-blue">
                    <i class="fa-solid fa-cart-shopping"></i>
                </div>
                <div class="stat-info">
                    <h4>Tổng Đơn Hàng</h4>
                    <span class="number"><?php echo number_format($tongDonHang); ?></span>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon icon-green">
                    <i class="fa-solid fa-money-bill-wave"></i>
                </div>
                <div class="stat-info">
                    <h4>Doanh Thu</h4>
                    <span class="number"><?php echo number_format($tongDoanhThu, 0, ',', '.'); ?> đ</span>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon icon-orange">
                    <i class="fa-solid fa-users"></i>
                </div>
                <div class="stat-info">
                    <h4>Khách Hàng</h4>
                    <span class="number"><?php echo number_format($tongKhachHang); ?></span>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon icon-red">
                    <i class="fa-solid fa-book"></i>
                </div>
                <div class="stat-info">
                    <h4>Tổng Sản Phẩm</h4>
                    <span class="number"><?php echo number_format($tongSach); ?></span>
                </div>
            </div>
        </div>

        <div style="background: #fff; padding: 25px; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.05); margin-top: 20px;">
            <h3>Hướng dẫn quản trị</h3>
            <p>Sử dụng menu bên trái để điều hướng đến các chức năng quản lý danh mục, sách và theo dõi đơn hàng của khách.</p>
            <ul>
                <li><strong>Quản lý thể loại:</strong> Thêm, Sửa, Xóa danh mục sách.</li>
                <li><strong>Quản lý sách:</strong> Thêm sách mới, cập nhật giá, hình ảnh, số lượng tồn kho.</li>
                <li><strong>Quản lý đơn hàng:</strong> Duyệt đơn hàng mới, cập nhật trạng thái giao hàng, in hóa đơn.</li>
                <li><strong>Quản lý tài khoản:</strong> Cấp quyền nhân viên/admin.</li>
            </ul>
        </div>
        
    </div>
</div>

</body>
</html>
