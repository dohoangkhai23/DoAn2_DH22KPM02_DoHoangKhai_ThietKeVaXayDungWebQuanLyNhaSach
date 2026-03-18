<?php
session_start();
require_once "../csdl.php";

if (!isset($_SESSION['user'])) {
    header("Location: ../dangnhap.php");
    exit();
}

// Lấy danh sách thể loại để hiển thị trong form dropdown
try {
    $stmtTL = $connection->query("SELECT * FROM theloai WHERE (TrangThaiXoa IS NULL OR TrangThaiXoa = 0)");
    $theloais = $stmtTL->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) { $theloais = []; }

// Lấy danh sách nhà cung cấp (nếu có)
try {
    $stmtNCC = $connection->query("SELECT * FROM nhacungcap");
    $nhacungcaps = $stmtNCC->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) { $nhacungcaps = []; }

// Xử lý Xóa/Xóa mềm
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $action = $_GET['action'];
    if ($action == 'delete') {
        // Tạm thời xóa cứng hoặc nếu DB có cột trạng thái thì update. 
        // Bảng 'sach' không thấy có trường TrangThaiXoa, ta thực hiện xóa trực tiếp luôn
        try {
            $stmt = $connection->prepare("DELETE FROM sach WHERE MaSach = ?");
            $stmt->execute([$id]);
            header("Location: quanlysanpham.php");
            exit();
        } catch (PDOException $e) {
            echo "<script>alert('Lỗi: Cần xóa các mục liên quan (như chi tiết đơn hàng) chứa sách này trước!'); window.location.href='quanlysanpham.php';</script>";
        }
    }
}

// Lấy danh sách sách
try {
    $stmt = $connection->query("
        SELECT s.*, t.TenTheLoai 
        FROM sach s 
        LEFT JOIN theloai t ON s.MaTheLoai = t.MaTheLoai 
        ORDER BY s.MaSach DESC
    ");
    $sachs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Lỗi CSDL: " . $e->getMessage());
}

?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Quản lý Sách - Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { font-family: 'Poppins', sans-serif; background-color: #f4f7f6; margin: 0; display: flex; height: 100vh; overflow: hidden; }
        .sidebar { width: 250px; background: #2c3e50; color: #ecf0f1; display: flex; flex-direction: column; }
        .sidebar-header { padding: 20px; background: #1a252f; text-align: center; font-size: 20px; font-weight: bold; border-bottom: 1px solid #34495e; }
        .sidebar-menu { flex: 1; padding-top: 20px; list-style: none; margin: 0; padding-left: 0; }
        .sidebar-menu a { display: block; padding: 15px 25px; color: #bdc3c7; text-decoration: none; font-size: 15px; transition: 0.3s; }
        .sidebar-menu a:hover, .sidebar-menu a.active { background: #34495e; color: #fff; border-left: 4px solid #3498db; }
        .sidebar-menu i { margin-right: 15px; width: 20px; text-align: center; }
        .main-content { flex: 1; display: flex; flex-direction: column; overflow: hidden; }
        .header { height: 60px; background: #fff; display: flex; align-items: center; justify-content: flex-end; padding: 0 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .content { flex: 1; padding: 30px; overflow-y: auto; }
        
        .card { background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.05); margin-bottom: 20px; }
        h3 { margin-top: 0; color: #2c3e50; display: flex; justify-content: space-between; align-items: center;}
        
        table { width: 100%; border-collapse: collapse; margin-top: 15px; font-size: 14px; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: left; vertical-align: middle; }
        th { background: #3498db; color: white; }
        
        .btn { padding: 8px 15px; border: none; border-radius: 4px; cursor: pointer; text-decoration: none; display: inline-block; font-size: 13px; color: white; }
        .btn-add { background: #2ecc71; }
        .btn-edit { background: #f39c12; }
        .btn-delete { background: #e74c3c; padding: 5px 10px; font-size: 12px;}
        
        .product-img { width: 50px; height: 70px; object-fit: cover; border-radius: 4px; }
    </style>
</head>
<body>

<div class="sidebar">
    <div class="sidebar-header">📚 Admin Panel</div>
    <ul class="sidebar-menu">
        <li><a href="index.php"><i class="fa-solid fa-gauge"></i> Tổng quan</a></li>
        <li><a href="quanlytheloai.php"><i class="fa-solid fa-list"></i> Quản lý Thể loại</a></li>
        <li><a href="quanlysanpham.php" class="active"><i class="fa-solid fa-book"></i> Quản lý Sách</a></li>
        <li><a href="quanlydonhang.php"><i class="fa-solid fa-cart-shopping"></i> Quản lý Đơn hàng</a></li>
        <li><a href="quanlytaikhoan.php"><i class="fa-solid fa-users"></i> Quản lý Tài khoản</a></li>
    </ul>
</div>

<div class="main-content">
    <div class="header">
        <div class="user-info">
            <a href="../dangxuat.php" style="color: #e74c3c; text-decoration: none; font-weight: bold;"><i class="fa-solid fa-right-from-bracket"></i> Đăng xuất</a>
        </div>
    </div>
    
    <div class="content">
        <h2 style="color: #2c3e50; margin-bottom: 20px;">Quản Lý Sách (Sản Phẩm)</h2>
        
        <div class="card">
            <h3>Danh sách Sách/Truyện 
                <a href="themsach.php" class="btn btn-add"><i class="fa-solid fa-plus"></i> Thêm Sách Mới</a>
            </h3>
            
            <table>
                <thead>
                    <tr>
                        <th style="width: 50px;">Mã</th>
                        <th style="width: 60px;">Ảnh</th>
                        <th>Tên Sách</th>
                        <th>Tác Giả</th>
                        <th>Thể Loại</th>
                        <th>Giá Bán</th>
                        <th>Tồn Kho</th>
                        <th style="width: 150px; text-align: center;">Thao Tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($sachs as $s): ?>
                    <tr>
                        <td>#<?php echo $s['MaSach']; ?></td>
                        <td>
                            <?php if(!empty($s['HinhAnh'])): ?>
                                <img src="../images/<?php echo htmlspecialchars($s['HinhAnh']); ?>" class="product-img">
                            <?php else: ?>
                                <div style="width:50px; height:70px; background:#ddd; display:flex; align-items:center; justify-content:center; border-radius:4px; font-size:10px; color:#888;">No Img</div>
                            <?php endif; ?>
                        </td>
                        <td><strong><?php echo htmlspecialchars($s['TenSach']); ?></strong></td>
                        <td><?php echo htmlspecialchars($s['TacGia']); ?></td>
                        <td><?php echo htmlspecialchars($s['TenTheLoai']); ?></td>
                        <td style="color: #e74c3c; font-weight: bold;"><?php echo number_format($s['GiaBan'], 0, ',', '.'); ?> đ</td>
                        <td><?php echo $s['SoLuong']; ?></td>
                        <td style="text-align: center;">
                            <a href="suasach.php?id=<?php echo $s['MaSach']; ?>" class="btn btn-edit" style="padding: 5px 10px; font-size: 12px;"><i class="fa-solid fa-edit"></i> Sửa</a>
                            <a href="quanlysanpham.php?action=delete&id=<?php echo $s['MaSach']; ?>" class="btn btn-delete" onclick="return confirm('Chắc chắn xóa sách này khỏi cửa hàng?');"><i class="fa-solid fa-trash"></i> Xóa</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

</body>
</html>
