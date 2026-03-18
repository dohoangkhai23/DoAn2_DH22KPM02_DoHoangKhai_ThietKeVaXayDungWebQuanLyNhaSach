<?php
session_start();
require_once "../csdl.php";
if (!isset($_SESSION['user'])) { header("Location: ../dangnhap.php"); exit(); }

// Lấy danh sách NCC và nhân viên cho dropdown
try { 
    $nccs = $connection->query("SELECT * FROM nhacungcap")->fetchAll(PDO::FETCH_ASSOC); 
    $nvs  = $connection->query("SELECT tk.MaNV, nv.TenNV FROM taikhoan tk LEFT JOIN nhanvien nv ON tk.MaNV=nv.MaNV WHERE tk.VaiTro='NhanVien' OR tk.VaiTro='Admin'")->fetchAll(PDO::FETCH_ASSOC);
    $sachs = $connection->query("SELECT MaSach, TenSach, GiaBan FROM sach ORDER BY TenSach")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) { $nccs=[]; $nvs=[]; $sachs=[]; }

$msg = '';

// Tạo phiếu nhập mới
if (isset($_POST['tao_phieu'])) {
    $mancc = (int)$_POST['mancc'];
    $manv  = (int)$_POST['manv'];
    $ngay  = $_POST['ngaylap'];
    try {
        $stmt = $connection->prepare("INSERT INTO phieunhap (MaNV, MaNCC, NgayLap, TongTien) VALUES (?, ?, ?, 0)");
        $stmt->execute([$manv, $mancc, $ngay]);
        $maPN = $connection->lastInsertId();
        header("Location: quanlynhaphang.php?edit=$maPN&msg=created"); exit();
    } catch (PDOException $e) { $msg = "Lỗi tạo phiếu: " . $e->getMessage(); }
}

// Thêm chi tiết phiếu nhập
if (isset($_POST['them_chitiet'])) {
    $maPN   = (int)$_POST['mapn'];
    $maSach = (int)$_POST['masach'];
    $soLuong = (int)$_POST['soluong'];
    $donGia  = (float)$_POST['dongia'];
    try {
        // Kiểm tra đã có chưa
        $check = $connection->prepare("SELECT * FROM chitietphieunhap WHERE MaPN=? AND MaSach=?");
        $check->execute([$maPN, $maSach]);
        if ($check->rowCount() > 0) {
            $connection->prepare("UPDATE chitietphieunhap SET SoLuongNhap=SoLuongNhap+?, DonGiaNhap=? WHERE MaPN=? AND MaSach=?")->execute([$soLuong, $donGia, $maPN, $maSach]);
        } else {
            $connection->prepare("INSERT INTO chitietphieunhap (MaPN,MaSach,SoLuongNhap,DonGiaNhap) VALUES (?,?,?,?)")->execute([$maPN, $maSach, $soLuong, $donGia]);
        }
        // Cập nhật số lượng tồn kho
        $connection->prepare("UPDATE sach SET SoLuong=SoLuong+? WHERE MaSach=?")->execute([$soLuong, $maSach]);
        // Cập nhật tổng tiền phiếu nhập
        $connection->prepare("UPDATE phieunhap SET TongTien=(SELECT SUM(SoLuongNhap*DonGiaNhap) FROM chitietphieunhap WHERE MaPN=?) WHERE MaPN=?")->execute([$maPN, $maPN]);
        header("Location: quanlynhaphang.php?edit=$maPN&msg=added"); exit();
    } catch (PDOException $e) { $msg = "Lỗi thêm chi tiết: " . $e->getMessage(); }
}

// Xóa chi tiết phiếu nhập
if (isset($_GET['del_ct'])) {
    $maPN   = (int)$_GET['mapn'];
    $maSach = (int)$_GET['del_ct'];
    try {
        // Lấy số lượng để hoàn kho
        $ct = $connection->prepare("SELECT SoLuongNhap FROM chitietphieunhap WHERE MaPN=? AND MaSach=?");
        $ct->execute([$maPN, $maSach]);
        if ($row = $ct->fetch()) {
            $connection->prepare("UPDATE sach SET SoLuong=SoLuong-? WHERE MaSach=?")->execute([$row['SoLuongNhap'], $maSach]);
        }
        $connection->prepare("DELETE FROM chitietphieunhap WHERE MaPN=? AND MaSach=?")->execute([$maPN, $maSach]);
        $connection->prepare("UPDATE phieunhap SET TongTien=(SELECT COALESCE(SUM(SoLuongNhap*DonGiaNhap),0) FROM chitietphieunhap WHERE MaPN=?) WHERE MaPN=?")->execute([$maPN, $maPN]);
        header("Location: quanlynhaphang.php?edit=$maPN"); exit();
    } catch (PDOException $e) { $msg = "Lỗi: " . $e->getMessage(); }
}

$keyword = $_GET['keyword'] ?? '';
$editPN  = (int)($_GET['edit'] ?? 0);

// Lấy danh sách phiếu nhập
try {
    if ($keyword) {
        $like = "%$keyword%";
        $stmt = $connection->prepare("SELECT pn.*, ncc.TenNCC, nv.TenNV FROM phieunhap pn LEFT JOIN nhacungcap ncc ON pn.MaNCC=ncc.MaNCC LEFT JOIN nhanvien nv ON pn.MaNV=nv.MaNV WHERE ncc.TenNCC LIKE ? OR pn.MaPN LIKE ? ORDER BY pn.MaPN DESC");
        $stmt->execute([$like, $like]);
    } else {
        $stmt = $connection->query("SELECT pn.*, ncc.TenNCC, nv.TenNV FROM phieunhap pn LEFT JOIN nhacungcap ncc ON pn.MaNCC=ncc.MaNCC LEFT JOIN nhanvien nv ON pn.MaNV=nv.MaNV ORDER BY pn.MaPN DESC");
    }
    $phieuNhaps = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) { $phieuNhaps = []; }

// Chi tiết phiếu đang chỉnh sửa
$currentPN = null; $chiTietPN = [];
if ($editPN) {
    try {
        $s = $connection->prepare("SELECT pn.*, ncc.TenNCC FROM phieunhap pn LEFT JOIN nhacungcap ncc ON pn.MaNCC=ncc.MaNCC WHERE pn.MaPN=?"); $s->execute([$editPN]);
        $currentPN = $s->fetch(PDO::FETCH_ASSOC);
        $sc = $connection->prepare("SELECT ct.*, s.TenSach FROM chitietphieunhap ct LEFT JOIN sach s ON ct.MaSach=s.MaSach WHERE ct.MaPN=?"); $sc->execute([$editPN]);
        $chiTietPN = $sc->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {}
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8"><title>Quản lý Nhập Hàng</title>
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
        h3, h2 { color:#2c3e50; margin-top:0; }
        table { width:100%; border-collapse:collapse; font-size:13px; margin-top:10px; }
        th, td { border:1px solid #ddd; padding:10px; text-align:left; vertical-align:middle; }
        th { background:#3498db; color:white; }
        .btn { padding:6px 12px; border-radius:4px; cursor:pointer; text-decoration:none; color:white; border:none; font-size:12px; display:inline-block; }
        .btn-green { background:#2ecc71; } .btn-edit { background:#f39c12; } .btn-del { background:#e74c3c; }
        .form-row { display:flex; gap:10px; flex-wrap:wrap; }
        .form-row > div { flex:1; min-width:150px; }
        .form-row label { display:block; font-size:12px; margin-bottom:4px; font-weight:600; }
        .form-row input, .form-row select { width:100%; padding:8px; border:1px solid #ddd; border-radius:4px; font-family:inherit; font-size:13px; }
        .search-bar { display:flex; gap:10px; margin-bottom:10px; } .search-bar input { flex:1; padding:8px; border:1px solid #ddd; border-radius:4px; } .search-bar button { padding:8px 15px; background:#3498db; color:white; border:none; border-radius:4px; cursor:pointer; }
        .two-col { display:grid; grid-template-columns:1fr 1fr; gap:20px; }
        @media(max-width:900px){ .two-col { grid-template-columns:1fr; } }
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
        <li><a href="quanlytaikhoan.php"><i class="fa-solid fa-users"></i> Tài khoản</a></li>
        <li><a href="quanlynhacungcap.php"><i class="fa-solid fa-truck"></i> Nhà cung cấp</a></li>
        <li><a href="quanlynhaphang.php" class="active"><i class="fa-solid fa-boxes-stacked"></i> Nhập hàng</a></li>
        <li><a href="thongke.php"><i class="fa-solid fa-chart-bar"></i> Thống kê</a></li>
        <li><a href="../dangxuat.php"><i class="fa-solid fa-right-from-bracket"></i> Đăng xuất</a></li>
    </ul>
</div>
<div class="main-content">
    <div class="header"><a href="../dangxuat.php"><i class="fa-solid fa-right-from-bracket"></i> Đăng xuất</a></div>
    <div class="content">
        <h2>Quản Lý Nhập Hàng</h2>
        <?php if($msg): ?><div style="color:#e74c3c;font-weight:bold;margin-bottom:10px;"><?php echo $msg; ?></div><?php endif; ?>
        <?php if(isset($_GET['msg'])): ?><div style="color:#2ecc71;font-weight:bold;margin-bottom:10px;">✓ Thao tác thành công!</div><?php endif; ?>
        
        <div class="two-col">
            <!-- Cột trái: Tạo phiếu nhập mới -->
            <div class="card">
                <h3>Tạo Phiếu Nhập Mới</h3>
                <form method="POST">
                    <div class="form-row">
                        <div>
                            <label>Nhà Cung Cấp</label>
                            <select name="mancc" required>
                                <option value="">-- Chọn NCC --</option>
                                <?php foreach($nccs as $ncc): ?>
                                <option value="<?php echo $ncc['MaNCC']; ?>"><?php echo htmlspecialchars($ncc['TenNCC']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label>Nhân Viên Lập</label>
                            <select name="manv" required>
                                <option value="">-- Chọn NV --</option>
                                <?php foreach($nvs as $nv): ?>
                                <option value="<?php echo $nv['MaNV']; ?>"><?php echo htmlspecialchars($nv['TenNV']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label>Ngày Lập</label>
                            <input type="date" name="ngaylap" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                    </div>
                    <button type="submit" name="tao_phieu" class="btn btn-green" style="margin-top:15px;"><i class="fa-solid fa-plus"></i> Tạo Phiếu</button>
                </form>
            </div>

            <!-- Cột phải: Thêm chi tiết phiếu nhập -->
            <?php if($currentPN): ?>
            <div class="card" style="border:2px solid #3498db;">
                <h3>Thêm Sách vào Phiếu <span style="color:#3498db;">#<?php echo $currentPN['MaPN']; ?></span> - <?php echo htmlspecialchars($currentPN['TenNCC']); ?></h3>
                <form method="POST">
                    <input type="hidden" name="mapn" value="<?php echo $currentPN['MaPN']; ?>">
                    <div class="form-row">
                        <div>
                            <label>Sách</label>
                            <select name="masach" required>
                                <?php foreach($sachs as $s): ?>
                                <option value="<?php echo $s['MaSach']; ?>"><?php echo htmlspecialchars($s['TenSach']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div><label>Số Lượng</label><input type="number" name="soluong" min="1" value="1" required></div>
                        <div><label>Đơn Giá Nhập</label><input type="number" name="dongia" min="0" placeholder="25000" required></div>
                    </div>
                    <button type="submit" name="them_chitiet" class="btn btn-green" style="margin-top:10px;"><i class="fa-solid fa-plus"></i> Thêm vào Phiếu</button>
                </form>
                <?php if(!empty($chiTietPN)): ?>
                <table style="margin-top:15px;">
                    <thead><tr><th>Sách</th><th>Số Lượng</th><th>Đơn Giá Nhập</th><th>Thành Tiền</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach($chiTietPN as $ct): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($ct['TenSach']); ?></td>
                        <td><?php echo $ct['SoLuongNhap']; ?></td>
                        <td><?php echo number_format($ct['DonGiaNhap'],0,',','.'); ?> đ</td>
                        <td><?php echo number_format($ct['SoLuongNhap']*$ct['DonGiaNhap'],0,',','.'); ?> đ</td>
                        <td><a href="?del_ct=<?php echo $ct['MaSach']; ?>&mapn=<?php echo $currentPN['MaPN']; ?>" class="btn btn-del" onclick="return confirm('Xóa dòng này?')"><i class="fa-solid fa-trash"></i></a></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <div style="text-align:right;font-weight:bold;margin-top:10px;">Tổng: <?php echo number_format($currentPN['TongTien'],0,',','.'); ?> đ</div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Danh sách phiếu nhập -->
        <div class="card">
            <h3>Danh Sách Phiếu Nhập</h3>
            <form class="search-bar" method="GET">
                <input type="text" name="keyword" placeholder="Tìm theo mã phiếu, tên NCC..." value="<?php echo htmlspecialchars($keyword); ?>">
                <button><i class="fa-solid fa-search"></i> Tìm</button>
            </form>
            <table>
                <thead><tr><th>Mã PN</th><th>Ngày Lập</th><th>Nhà Cung Cấp</th><th>Nhân Viên</th><th>Tổng Tiền</th><th>Thao Tác</th></tr></thead>
                <tbody>
                <?php foreach($phieuNhaps as $pn): ?>
                <tr>
                    <td>#<?php echo $pn['MaPN']; ?></td>
                    <td><?php echo $pn['NgayLap']; ?></td>
                    <td><?php echo htmlspecialchars($pn['TenNCC']); ?></td>
                    <td><?php echo htmlspecialchars($pn['TenNV']); ?></td>
                    <td><?php echo number_format($pn['TongTien'],0,',','.'); ?> đ</td>
                    <td><a href="?edit=<?php echo $pn['MaPN']; ?>" class="btn btn-edit"><i class="fa-solid fa-edit"></i> Chi tiết</a></td>
                </tr>
                <?php endforeach; ?>
                <?php if(empty($phieuNhaps)): ?><tr><td colspan="6" style="text-align:center;color:#888;padding:20px;">Chưa có phiếu nhập.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</body>
</html>
