<?php
session_start();
require_once "../csdl.php";

if (!isset($_SESSION['user'])) {
    header("Location: ../dangnhap.php");
    exit();
}

// Xử lý Thêm Thể loại
if (isset($_POST['add'])) {
    $tenTheLoai = trim($_POST['tentheloai']);
    if (!empty($tenTheLoai)) {
        try {
            // Lấy MaTheLoai lớn nhất hiện tại để cộng thêm 1 (hoặc để Auto Increment nếu DB hỗ trợ)
            // Vì MaTheLoai có thể không auto_increment, ta tự tạo nếu cần, nhưng giả định là auto_increment.
            // Nếu lỗi ta sẽ check lại. 
            $stmt = $connection->prepare("INSERT INTO theloai (TenTheLoai, TrangThaiXoa) VALUES (?, 0)");
            $stmt->execute([$tenTheLoai]);
            echo "<script>alert('Thêm thể loại thành công!'); window.location.href='quanlytheloai.php';</script>";
        } catch (PDOException $e) {
            echo "<script>alert('Lỗi: " . $e->getMessage() . "');</script>";
        }
    }
}

// Xử lý Xóa/Khôi phục
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $action = $_GET['action'];
    
    try {
        if ($action == 'delete') {
            $stmt = $connection->prepare("UPDATE theloai SET TrangThaiXoa = 1 WHERE MaTheLoai = ?");
            $stmt->execute([$id]);
        } elseif ($action == 'restore') {
            $stmt = $connection->prepare("UPDATE theloai SET TrangThaiXoa = 0 WHERE MaTheLoai = ?");
            $stmt->execute([$id]);
        } elseif ($action == 'hard_delete') {
            $stmt = $connection->prepare("DELETE FROM theloai WHERE MaTheLoai = ?");
            $stmt->execute([$id]);
        }
        header("Location: quanlytheloai.php");
        exit();
    } catch (PDOException $e) {
        echo "<script>alert('Lỗi: Cần xóa các sách thuộc thể loại này trước khi xóa vĩnh viễn!'); window.location.href='quanlytheloai.php';</script>";
    }
}

// Xử lý Cập nhật
if (isset($_POST['update'])) {
    $id = (int)$_POST['matheloai'];
    $tenTheLoai = trim($_POST['tentheloai']);
    if (!empty($tenTheLoai)) {
        try {
            $stmt = $connection->prepare("UPDATE theloai SET TenTheLoai = ? WHERE MaTheLoai = ?");
            $stmt->execute([$tenTheLoai, $id]);
            echo "<script>alert('Cập nhật thành công!'); window.location.href='quanlytheloai.php';</script>";
        } catch (PDOException $e) {
            echo "<script>alert('Lỗi: " . $e->getMessage() . "');</script>";
        }
    }
}

// Lấy danh sách
try {
    $stmt = $connection->query("SELECT * FROM theloai ORDER BY MaTheLoai DESC");
    $theloais = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Lỗi CSDL: " . $e->getMessage());
}

?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Quản lý Thể Loại - Admin</title>
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
        h3 { margin-top: 0; color: #2c3e50; }
        
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { border: 1px solid #ddd; padding: 12px; text-align: left; }
        th { background: #3498db; color: white; }
        
        .btn { padding: 8px 15px; border: none; border-radius: 4px; cursor: pointer; text-decoration: none; display: inline-block; font-size: 13px; color: white; }
        .btn-add { background: #2ecc71; margin-top: 10px; }
        .btn-edit { background: #f39c12; }
        .btn-delete { background: #e74c3c; }
        .btn-restore { background: #34495e; }
        
        .form-group { margin-bottom: 15px; }
        .form-group input { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box;}
        
        /* Modal */
        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); align-items: center; justify-content: center; }
        .modal-content { background: #fff; padding: 25px; border-radius: 8px; width: 400px; max-width: 90%; }
        .close-modal { float: right; cursor: pointer; font-size: 20px; font-weight: bold; }
    </style>
</head>
<body>

<div class="sidebar">
    <div class="sidebar-header">📚 Admin Panel</div>
    <ul class="sidebar-menu">
        <li><a href="index.php"><i class="fa-solid fa-gauge"></i> Tổng quan</a></li>
        <li><a href="quanlytheloai.php" class="active"><i class="fa-solid fa-list"></i> Quản lý Thể loại</a></li>
        <li><a href="quanlysanpham.php"><i class="fa-solid fa-book"></i> Quản lý Sách</a></li>
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
        <h2 style="color: #2c3e50; margin-bottom: 20px;">Quản Lý Thể Loại</h2>
        
        <div class="card" style="max-width: 500px;">
            <h3>Thêm Thể Loại Mới</h3>
            <form method="POST">
                <div class="form-group">
                    <input type="text" name="tentheloai" placeholder="Nhập tên thể loại..." required>
                </div>
                <button type="submit" name="add" class="btn btn-add"><i class="fa-solid fa-plus"></i> Thêm Mới</button>
            </form>
        </div>
        
        <div class="card">
            <h3>Danh sách Thể Loại</h3>
            <table>
                <thead>
                    <tr>
                        <th>Mã TL</th>
                        <th>Tên Thể Loại</th>
                        <th>Trạng Thái</th>
                        <th style="width: 250px; text-align: center;">Thao Tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($theloais as $tl): ?>
                    <tr>
                        <td><?php echo $tl['MaTheLoai']; ?></td>
                        <td><?php echo htmlspecialchars($tl['TenTheLoai']); ?></td>
                        <td>
                            <?php if(isset($tl['TrangThaiXoa']) && $tl['TrangThaiXoa'] == 1): ?>
                                <span style="color: red; font-weight: bold;">Đã Ẩn</span>
                            <?php else: ?>
                                <span style="color: green; font-weight: bold;">Hoạt Đồng</span>
                            <?php endif; ?>
                        </td>
                        <td style="text-align: center;">
                            <button class="btn btn-edit" onclick="openEditModal(<?php echo $tl['MaTheLoai']; ?>, '<?php echo addslashes(htmlspecialchars($tl['TenTheLoai'])); ?>')"><i class="fa-solid fa-edit"></i> Sửa</button>
                            
                            <?php if(isset($tl['TrangThaiXoa']) && $tl['TrangThaiXoa'] == 1): ?>
                                <a href="?action=restore&id=<?php echo $tl['MaTheLoai']; ?>" class="btn btn-restore" onclick="return confirm('Khôi phục hiển thị?');"><i class="fa-solid fa-rotate-left"></i> Khôi phục</a>
                                <a href="?action=hard_delete&id=<?php echo $tl['MaTheLoai']; ?>" class="btn btn-delete" onclick="return confirm('Chắc chắn xóa vĩnh viễn? Cảnh báo: sẽ gây lỗi nếu có sách thuộc thể loại này!');"><i class="fa-solid fa-trash"></i> Xóa vĩnh viễn</a>
                            <?php else: ?>
                                <a href="?action=delete&id=<?php echo $tl['MaTheLoai']; ?>" class="btn btn-delete" onclick="return confirm('Ẩn thể loại này?');"><i class="fa-solid fa-ban"></i> Ẩn</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Sửa -->
<div id="editModal" class="modal">
    <div class="modal-content">
        <span class="close-modal" onclick="closeEditModal()">&times;</span>
        <h3>Sửa Thể Loại</h3>
        <form method="POST">
            <input type="hidden" name="matheloai" id="edit-id">
            <div class="form-group">
                <input type="text" name="tentheloai" id="edit-name" required>
            </div>
            <button type="submit" name="update" class="btn btn-edit" style="width: 100%;">Lưu Thay Đổi</button>
        </form>
    </div>
</div>

<script>
    function openEditModal(id, name) {
        document.getElementById('edit-id').value = id;
        document.getElementById('edit-name').value = name;
        document.getElementById('editModal').style.display = 'flex';
    }
    function closeEditModal() {
        document.getElementById('editModal').style.display = 'none';
    }
</script>

</body>
</html>
