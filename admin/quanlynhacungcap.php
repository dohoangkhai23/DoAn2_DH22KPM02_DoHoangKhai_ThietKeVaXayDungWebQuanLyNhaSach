<?php
session_start();
require_once "../csdl.php";
if (!isset($_SESSION['user'])) { header("Location: ../dangnhap.php"); exit(); }

// Thêm NCC
if (isset($_POST['add'])) {
    try {
        $stmt = $connection->prepare("INSERT INTO nhacungcap (TenNCC, SDT, DiaChi) VALUES (?, ?, ?)");
        $stmt->execute([trim($_POST['tenncc']), trim($_POST['sdt']), trim($_POST['diachi'])]);
        header("Location: quanlynhacungcap.php?msg=ok"); exit();
    } catch (PDOException $e) {}
}
// Xóa NCC
if (isset($_GET['action']) && $_GET['action']=='delete') {
    try {
        $connection->prepare("DELETE FROM nhacungcap WHERE MaNCC=?")->execute([(int)$_GET['id']]);
        header("Location: quanlynhacungcap.php"); exit();
    } catch (PDOException $e) {}
}
// Cập nhật NCC
if (isset($_POST['update'])) {
    try {
        $stmt = $connection->prepare("UPDATE nhacungcap SET TenNCC=?, SDT=?, DiaChi=? WHERE MaNCC=?");
        $stmt->execute([trim($_POST['tenncc']), trim($_POST['sdt']), trim($_POST['diachi']), (int)$_POST['mancc']]);
        header("Location: quanlynhacungcap.php?msg=ok"); exit();
    } catch (PDOException $e) {}
}

$keyword = $_GET['keyword'] ?? '';
try {
    if ($keyword) {
        $stmt = $connection->prepare("SELECT * FROM nhacungcap WHERE TenNCC LIKE ? OR SDT LIKE ? ORDER BY MaNCC DESC");
        $like = "%$keyword%";
        $stmt->execute([$like, $like]);
    } else {
        $stmt = $connection->query("SELECT * FROM nhacungcap ORDER BY MaNCC DESC");
    }
    $nccs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) { $nccs = []; }

$sidebar = '<li><a href="index.php"><i class="fa-solid fa-gauge"></i> Tổng quan</a></li>
<li><a href="quanlytheloai.php"><i class="fa-solid fa-list"></i> Thể loại</a></li>
<li><a href="quanlysanpham.php"><i class="fa-solid fa-book"></i> Sách</a></li>
<li><a href="quanlydonhang.php"><i class="fa-solid fa-cart-shopping"></i> Đơn hàng</a></li>
<li><a href="quanlytaikhoan.php"><i class="fa-solid fa-users"></i> Tài khoản</a></li>
<li><a href="quanlynhacungcap.php" class="active"><i class="fa-solid fa-truck"></i> Nhà cung cấp</a></li>
<li><a href="quanlynhaphang.php"><i class="fa-solid fa-boxes-stacked"></i> Nhập hàng</a></li>
<li><a href="thongke.php"><i class="fa-solid fa-chart-bar"></i> Thống kê</a></li>
<li><a href="../dangxuat.php"><i class="fa-solid fa-right-from-bracket"></i> Đăng xuất</a></li>';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8"><title>Quản lý Nhà Cung Cấp</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * { box-sizing:border-box; }
        body { font-family:'Poppins',sans-serif; background:#f4f7f6; margin:0; display:flex; height:100vh; overflow:hidden; }
        .sidebar { width:250px; background:#2c3e50; display:flex; flex-direction:column; flex-shrink:0; }
        .sidebar-header { padding:20px; background:#1a252f; text-align:center; font-size:20px; font-weight:bold; color:#fff; }
        .sidebar-menu { flex:1; padding-top:20px; list-style:none; margin:0; padding-left:0; }
        .sidebar-menu a { display:block; padding:14px 25px; color:#bdc3c7; text-decoration:none; font-size:14px; transition:0.3s; }
        .sidebar-menu a:hover, .sidebar-menu a.active { background:#34495e; color:#fff; border-left:4px solid #3498db; }
        .sidebar-menu i { margin-right:12px; width:18px; text-align:center; }
        .main-content { flex:1; display:flex; flex-direction:column; overflow:hidden; }
        .header { height:60px; background:#fff; display:flex; align-items:center; justify-content:flex-end; padding:0 30px; box-shadow:0 2px 10px rgba(0,0,0,0.08); }
        .header a { color:#e74c3c; text-decoration:none; font-weight:bold; }
        .content { flex:1; padding:25px; overflow-y:auto; }
        .card { background:#fff; padding:20px; border-radius:8px; box-shadow:0 4px 10px rgba(0,0,0,0.05); margin-bottom:20px; }
        h3 { margin-top:0; color:#2c3e50; }
        table { width:100%; border-collapse:collapse; font-size:13px; margin-top:10px; }
        th, td { border:1px solid #ddd; padding:10px; text-align:left; vertical-align:middle; }
        th { background:#3498db; color:white; }
        .btn { padding:6px 12px; border-radius:4px; cursor:pointer; text-decoration:none; color:white; border:none; font-size:12px; }
        .btn-edit { background:#f39c12; } .btn-del { background:#e74c3c; }
        .form-row { display:grid; grid-template-columns:1fr 1fr 1fr; gap:10px; }
        .form-row input { padding:8px; border:1px solid #ddd; border-radius:4px; font-family:inherit; }
        .search-bar { display:flex; gap:10px; margin-bottom:15px; }
        .search-bar input { flex:1; padding:10px; border:1px solid #ddd; border-radius:5px; }
        .search-bar button { padding:10px 20px; background:#3498db; color:white; border:none; border-radius:5px; cursor:pointer; }
        .modal { display:none; position:fixed; z-index:1000; left:0; top:0; width:100%; height:100%; background:rgba(0,0,0,0.5); align-items:center; justify-content:center; }
        .modal-box { background:#fff; padding:25px; border-radius:8px; width:400px; max-width:90%; }
        .modal-box input { width:100%; padding:10px; margin-bottom:10px; border:1px solid #ddd; border-radius:4px; box-sizing:border-box; }
    </style>
</head>
<body>
<div class="sidebar">
    <div class="sidebar-header">📚 Admin Panel</div>
    <ul class="sidebar-menu"><?php echo $sidebar; ?></ul>
</div>
<div class="main-content">
    <div class="header"><a href="../dangxuat.php"><i class="fa-solid fa-right-from-bracket"></i> Đăng xuất</a></div>
    <div class="content">
        <h2 style="color:#2c3e50;margin-bottom:20px;">Quản Lý Nhà Cung Cấp</h2>
        <?php if(isset($_GET['msg'])): ?><div style="color:#2ecc71;font-weight:bold;margin-bottom:10px;">✓ Thao tác thành công!</div><?php endif; ?>
        <div class="card">
            <h3>Thêm Nhà Cung Cấp Mới</h3>
            <form method="POST">
                <div class="form-row">
                    <div><label style="font-size:13px;">Tên NCC</label><input type="text" name="tenncc" placeholder="Công ty ABC" required></div>
                    <div><label style="font-size:13px;">Số Điện Thoại</label><input type="text" name="sdt" placeholder="0901234567"></div>
                    <div><label style="font-size:13px;">Địa Chỉ</label><input type="text" name="diachi" placeholder="123 Nguyễn Văn A, TP.HCM"></div>
                </div>
                <button type="submit" name="add" class="btn" style="background:#2ecc71;margin-top:10px;"><i class="fa-solid fa-plus"></i> Thêm Mới</button>
            </form>
        </div>
        <div class="card">
            <h3>Danh Sách Nhà Cung Cấp</h3>
            <form class="search-bar" method="GET">
                <input type="text" name="keyword" placeholder="Tìm theo tên, SĐT..." value="<?php echo htmlspecialchars($keyword); ?>">
                <button><i class="fa-solid fa-search"></i> Tìm</button>
            </form>
            <table>
                <thead><tr><th>Mã NCC</th><th>Tên NCC</th><th>Số Điện Thoại</th><th>Địa Chỉ</th><th style="width:120px;text-align:center;">Thao Tác</th></tr></thead>
                <tbody>
                <?php foreach($nccs as $ncc): ?>
                <tr>
                    <td>#<?php echo $ncc['MaNCC']; ?></td>
                    <td><?php echo htmlspecialchars($ncc['TenNCC']); ?></td>
                    <td><?php echo htmlspecialchars($ncc['SDT']); ?></td>
                    <td><?php echo htmlspecialchars($ncc['DiaChi']); ?></td>
                    <td style="text-align:center;">
                        <button class="btn btn-edit" onclick="openEdit(<?php echo $ncc['MaNCC']; ?>,'<?php echo addslashes($ncc['TenNCC']); ?>','<?php echo addslashes($ncc['SDT']); ?>','<?php echo addslashes($ncc['DiaChi']); ?>')"><i class="fa-solid fa-edit"></i></button>
                        <a href="?action=delete&id=<?php echo $ncc['MaNCC']; ?>" class="btn btn-del" onclick="return confirm('Xóa NCC này?')"><i class="fa-solid fa-trash"></i></a>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if(empty($nccs)): ?><tr><td colspan="5" style="text-align:center;color:#888;padding:30px;">Chưa có nhà cung cấp nào.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<div id="editModal" class="modal">
    <div class="modal-box">
        <h3 style="margin-top:0;">Sửa Nhà Cung Cấp</h3>
        <form method="POST">
            <input type="hidden" name="mancc" id="edit-id">
            <input type="text" name="tenncc" id="edit-ten" placeholder="Tên NCC" required>
            <input type="text" name="sdt" id="edit-sdt" placeholder="Số ĐT">
            <input type="text" name="diachi" id="edit-dc" placeholder="Địa chỉ">
            <div style="display:flex;gap:10px;margin-top:5px;">
                <button type="submit" name="update" class="btn" style="background:#f39c12;flex:1;">Lưu thay đổi</button>
                <button type="button" onclick="document.getElementById('editModal').style.display='none'" class="btn" style="background:#95a5a6;flex:1;">Hủy</button>
            </div>
        </form>
    </div>
</div>
<script>
function openEdit(id, ten, sdt, dc) {
    document.getElementById('edit-id').value = id;
    document.getElementById('edit-ten').value = ten;
    document.getElementById('edit-sdt').value = sdt;
    document.getElementById('edit-dc').value = dc;
    document.getElementById('editModal').style.display = 'flex';
}
</script>
</body>
</html>
