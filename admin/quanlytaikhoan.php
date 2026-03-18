<?php
session_start();
require_once "../csdl.php";
if (!isset($_SESSION['user'])) { header("Location: ../dangnhap.php"); exit(); }

$msg = '';

// Thêm tài khoản nhân viên/admin
if (isset($_POST['add'])) {
    $username = trim($_POST['username']);
    $password = password_hash(trim($_POST['password']), PASSWORD_DEFAULT);
    $vaitro = $_POST['vaitro'];
    $tennv = trim($_POST['tennv']);
    try {
        // Thêm nhân viên trước (lấy MaNV)
        $stmtNV = $connection->prepare("INSERT INTO nhanvien (TenNV, ChucVu) VALUES (?, ?)");
        $stmtNV->execute([$tennv, $vaitro]);
        $maNV = $connection->lastInsertId();
        // Thêm tài khoản
        $stmtTK = $connection->prepare("INSERT INTO taikhoan (TenDangNhap, MatKhau, MaNV, VaiTro) VALUES (?, ?, ?, ?)");
        $stmtTK->execute([$username, $password, $maNV, $vaitro]);
        $msg = 'success:Thêm tài khoản thành công!';
    } catch (PDOException $e) {
        $msg = 'error:Lỗi: ' . $e->getMessage();
    }
}

// Xóa tài khoản
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $username = $_GET['id'];
    try {
        $connection->prepare("DELETE FROM taikhoan WHERE TenDangNhap = ?")->execute([$username]);
        header("Location: quanlytaikhoan.php"); exit();
    } catch (PDOException $e) {}
}

// Cập nhật vai trò
if (isset($_POST['updateRole'])) {
    $username = $_POST['username_update'];
    $vaitro = $_POST['vaitro_update'];
    try {
        $connection->prepare("UPDATE taikhoan SET VaiTro = ? WHERE TenDangNhap = ?")->execute([$vaitro, $username]);
        header("Location: quanlytaikhoan.php?msg=ok"); exit();
    } catch (PDOException $e) {}
}

$keyword = $_GET['keyword'] ?? '';
try {
    if ($keyword) {
        $like = "%$keyword%";
        $stmt = $connection->prepare("SELECT tk.*, nv.TenNV FROM taikhoan tk LEFT JOIN nhanvien nv ON tk.MaNV = nv.MaNV WHERE tk.TenDangNhap LIKE ? OR nv.TenNV LIKE ?");
        $stmt->execute([$like, $like]);
    } else {
        $stmt = $connection->query("SELECT tk.*, nv.TenNV FROM taikhoan tk LEFT JOIN nhanvien nv ON tk.MaNV = nv.MaNV ORDER BY tk.TenDangNhap");
    }
    $taikhoans = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) { $taikhoans = []; }

// Lấy danh sách khách hàng
try {
    if ($keyword) {
        $like = "%$keyword%";
        $stmtKH = $connection->prepare("SELECT * FROM KhachHang WHERE TenKH LIKE ? OR TenDangNhap LIKE ? OR SDT LIKE ?");
        $stmtKH->execute([$like, $like, $like]);
    } else {
        $stmtKH = $connection->query("SELECT * FROM KhachHang ORDER BY MaKH DESC");
    }
    $khachhang = $stmtKH->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) { $khachhang = []; }
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Quản lý Tài khoản - Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * { box-sizing: border-box; }
        body { font-family: 'Poppins', sans-serif; background: #f4f7f6; margin: 0; display: flex; height: 100vh; overflow: hidden; }
        .sidebar { width: 250px; background: #2c3e50; color: #ecf0f1; display: flex; flex-direction: column; flex-shrink: 0; }
        .sidebar-header { padding: 20px; background: #1a252f; text-align: center; font-size: 20px; font-weight: bold; }
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
        th, td { border:1px solid #ddd; padding:10px; text-align:left; }
        th { background:#3498db; color:white; }
        .btn { padding:6px 12px; border-radius:4px; cursor:pointer; text-decoration:none; color:white; border:none; font-size:12px; display:inline-block; }
        .btn-del { background:#e74c3c; }
        .badge { padding:3px 8px; border-radius:10px; font-size:11px; font-weight:bold; }
        .b-admin { background:#9b59b6; color:white; }
        .b-nv { background:#3498db; color:white; }
        .b-kh { background:#2ecc71; color:white; }
        .form-row { display:grid; grid-template-columns:1fr 1fr 1fr; gap:10px; }
        .form-row input, .form-row select { padding:8px; border:1px solid #ddd; border-radius:4px; font-family:inherit; }
        .search-bar { display:flex; gap:10px; margin-bottom:15px; }
        .search-bar input { flex:1; padding:10px; border:1px solid #ddd; border-radius:5px; }
        .search-bar button { padding:10px 20px; background:#3498db; color:white; border:none; border-radius:5px; cursor:pointer; }
        .tabs { display:flex; gap:5px; margin-bottom:20px; }
        .tab { padding:10px 20px; border-radius:6px 6px 0 0; cursor:pointer; background:#ddd; font-weight:bold; border:none; font-family:inherit; font-size:14px; }
        .tab.active { background:#3498db; color:white; }
        .tab-content { display:none; }
        .tab-content.active { display:block; }
    </style>
</head>
<body>
<div class="sidebar">
    <div class="sidebar-header">📚 Admin Panel</div>
    <ul class="sidebar-menu">
        <li><a href="index.php"><i class="fa-solid fa-gauge"></i> Tổng quan</a></li>
        <li><a href="quanlytheloai.php"><i class="fa-solid fa-list"></i> Thể loại</a></li>
        <li><a href="quanlysanpham.php"><i class="fa-solid fa-book"></i> Sách</a></li>
        <li><a href="quanlydonhang.php"><i class="fa-solid fa-cart-shopping"></i> Đơn hàng</a></li>
        <li><a href="quanlytaikhoan.php" class="active"><i class="fa-solid fa-users"></i> Tài khoản</a></li>
        <li><a href="quanlynhacungcap.php"><i class="fa-solid fa-truck"></i> Nhà cung cấp</a></li>
        <li><a href="quanlynhaphang.php"><i class="fa-solid fa-boxes-stacked"></i> Nhập hàng</a></li>
        <li><a href="thongke.php"><i class="fa-solid fa-chart-bar"></i> Thống kê</a></li>
        <li><a href="../dangxuat.php"><i class="fa-solid fa-right-from-bracket"></i> Đăng xuất</a></li>
    </ul>
</div>
<div class="main-content">
    <div class="header"><a href="../dangxuat.php"><i class="fa-solid fa-right-from-bracket"></i> Đăng xuất</a></div>
    <div class="content">
        <h2 style="color:#2c3e50;margin-bottom:20px;">Quản Lý Tài Khoản</h2>
        <?php if(isset($_GET['msg'])): ?><div style="color:#2ecc71;font-weight:bold;margin-bottom:10px;">✓ Cập nhật thành công!</div><?php endif; ?>
        <?php if($msg): ?>
        <div style="color:<?php echo str_starts_with($msg,'success') ? '#2ecc71' : '#e74c3c'; ?>;font-weight:bold;margin-bottom:15px;"><?php echo explode(':',$msg)[1]; ?></div>
        <?php endif; ?>

        <!-- Thêm tài khoản NV/Admin -->
        <div class="card">
            <h3>Thêm Tài Khoản Nhân Viên / Admin</h3>
            <form method="POST">
                <div class="form-row">
                    <div>
                        <label style="display:block;font-size:13px;margin-bottom:5px;">Họ Tên</label>
                        <input type="text" name="tennv" placeholder="Nguyễn Văn A" required>
                    </div>
                    <div>
                        <label style="display:block;font-size:13px;margin-bottom:5px;">Tên Đăng Nhập</label>
                        <input type="text" name="username" placeholder="admin01" required>
                    </div>
                    <div>
                        <label style="display:block;font-size:13px;margin-bottom:5px;">Mật Khẩu</label>
                        <input type="password" name="password" placeholder="••••••••" required>
                    </div>
                    <div>
                        <label style="display:block;font-size:13px;margin-bottom:5px;">Vai Trò</label>
                        <select name="vaitro">
                            <option value="NhanVien">Nhân Viên</option>
                            <option value="Admin">Admin</option>
                        </select>
                    </div>
                    <div style="display:flex;align-items:flex-end;">
                        <button type="submit" name="add" class="btn" style="background:#2ecc71;padding:8px 20px;"><i class="fa-solid fa-plus"></i> Thêm</button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Tabs -->
        <div class="tabs">
            <button class="tab active" onclick="showTab('tab-nv', this)">Tài khoản Nhân viên/Admin (<?php echo count($taikhoans); ?>)</button>
            <button class="tab" onclick="showTab('tab-kh', this)">Tài khoản Khách hàng (<?php echo count($khachhang); ?>)</button>
        </div>

        <form class="search-bar" method="GET">
            <input type="text" name="keyword" placeholder="Tìm theo tên đăng nhập, họ tên..." value="<?php echo htmlspecialchars($keyword); ?>">
            <button type="submit"><i class="fa-solid fa-search"></i> Tìm</button>
        </form>

        <div id="tab-nv" class="tab-content active card">
            <h3>Danh sách Nhân viên / Admin</h3>
            <table>
                <thead><tr><th>Tên Đăng Nhập</th><th>Họ Tên</th><th>Vai Trò</th><th>Cập nhật Vai Trò</th><th>Thao Tác</th></tr></thead>
                <tbody>
                <?php foreach($taikhoans as $tk): ?>
                <tr>
                    <td><?php echo htmlspecialchars($tk['TenDangNhap']); ?></td>
                    <td><?php echo htmlspecialchars($tk['TenNV'] ?? 'N/A'); ?></td>
                    <td>
                        <?php $cls = $tk['VaiTro']=='Admin' ? 'b-admin' : 'b-nv'; ?>
                        <span class="badge <?php echo $cls; ?>"><?php echo $tk['VaiTro']; ?></span>
                    </td>
                    <td>
                        <form method="POST" style="display:flex;gap:5px;align-items:center;">
                            <input type="hidden" name="username_update" value="<?php echo $tk['TenDangNhap']; ?>">
                            <select name="vaitro_update">
                                <option value="NhanVien" <?php if($tk['VaiTro']=='NhanVien') echo 'selected'; ?>>Nhân Viên</option>
                                <option value="Admin" <?php if($tk['VaiTro']=='Admin') echo 'selected'; ?>>Admin</option>
                            </select>
                            <button type="submit" name="updateRole" class="btn" style="background:#f39c12;">Lưu</button>
                        </form>
                    </td>
                    <td>
                        <a href="?action=delete&id=<?php echo urlencode($tk['TenDangNhap']); ?>" class="btn btn-del" onclick="return confirm('Xóa tài khoản này?')"><i class="fa-solid fa-trash"></i> Xóa</a>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div id="tab-kh" class="tab-content card">
            <h3>Danh sách Khách Hàng</h3>
            <table>
                <thead><tr><th>Mã KH</th><th>Tên KH</th><th>SDT</th><th>Địa Chỉ</th><th>Tên Đăng Nhập</th></tr></thead>
                <tbody>
                <?php foreach($khachhang as $kh): ?>
                <tr>
                    <td>#<?php echo $kh['MaKH']; ?></td>
                    <td><?php echo htmlspecialchars($kh['TenKH']); ?></td>
                    <td><?php echo htmlspecialchars($kh['SDT']); ?></td>
                    <td><?php echo htmlspecialchars($kh['DiaChi']); ?></td>
                    <td><?php echo htmlspecialchars($kh['TenDangNhap']); ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<script>
function showTab(id, el) {
    document.querySelectorAll('.tab-content').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
    document.getElementById(id).classList.add('active');
    el.classList.add('active');
}
</script>
</body>
</html>
