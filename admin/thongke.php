<?php
session_start();
require_once "../csdl.php";
if (!isset($_SESSION['user'])) { header("Location: ../dangnhap.php"); exit(); }

// Thống kê doanh thu theo tháng
$loai = $_GET['loai'] ?? 'doanhthu';
$nam  = (int)($_GET['nam'] ?? date('Y'));

$doanhThuData  = [];
$nhapHangData  = [];
$tonKhoData    = [];

try {
    // Doanh thu từng tháng
    $stmt = $connection->prepare("
        SELECT MONTH(NgayDat) as thang, SUM(TongTien) as tong
        FROM DonHang
        WHERE YEAR(NgayDat) = ? AND TrangThai = 'Hoàn Thành'
        GROUP BY MONTH(NgayDat) ORDER BY thang
    ");
    $stmt->execute([$nam]);
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $doanhThuData[$row['thang']] = $row['tong'];
    }

    // Nhập hàng từng tháng
    $stmtNH = $connection->prepare("
        SELECT MONTH(NgayLap) as thang, SUM(TongTien) as tong
        FROM phieunhap WHERE YEAR(NgayLap) = ?
        GROUP BY MONTH(NgayLap) ORDER BY thang
    ");
    $stmtNH->execute([$nam]);
    foreach ($stmtNH->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $nhapHangData[$row['thang']] = $row['tong'];
    }

    // Tồn kho: sách sắp hết (<= 10 quyển) và hết hàng (0 quyển)
    $stmtTK = $connection->query("SELECT TenSach, SoLuong FROM sach WHERE SoLuong <= 10 ORDER BY SoLuong ASC LIMIT 20");
    $tonKhoData = $stmtTK->fetchAll(PDO::FETCH_ASSOC);

    // Tổng quan
    $tongDT    = $connection->prepare("SELECT SUM(TongTien) FROM DonHang WHERE YEAR(NgayDat)=? AND TrangThai='Hoàn Thành'"); $tongDT->execute([$nam]); $tongDT = $tongDT->fetchColumn() ?: 0;
    $tongNH    = $connection->prepare("SELECT COUNT(*) FROM DonHang WHERE YEAR(NgayDat)=?"); $tongNH->execute([$nam]); $tongDH = $tongNH->fetchColumn() ?: 0;
    $tongSP    = $connection->query("SELECT COUNT(*) FROM sach")->fetchColumn();
    $tongKH    = $connection->query("SELECT COUNT(*) FROM KhachHang")->fetchColumn();

} catch (PDOException $e) { $tongDT=0; $tongDH=0; $tongSP=0; $tongKH=0; }

$months = ['T1','T2','T3','T4','T5','T6','T7','T8','T9','T10','T11','T12'];
$dtArray = [];
$nhArray = [];
for ($i=1; $i<=12; $i++) {
    $dtArray[] = $doanhThuData[$i] ?? 0;
    $nhArray[] = $nhapHangData[$i] ?? 0;
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8"><title>Thống Kê - Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
        .stat-row { display:grid; grid-template-columns:repeat(4,1fr); gap:15px; margin-bottom:20px; }
        .stat-card { background:#fff; border-radius:8px; padding:20px; box-shadow:0 4px 10px rgba(0,0,0,0.05); display:flex; align-items:center; gap:15px; }
        .stat-icon { width:50px; height:50px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:20px; color:white; flex-shrink:0; }
        .stat-info h4 { margin:0; color:#7f8c8d; font-size:12px; font-weight:500; }
        .stat-info .num { font-size:20px; font-weight:bold; color:#2c3e50; }
        .card { background:#fff; padding:20px; border-radius:8px; box-shadow:0 4px 10px rgba(0,0,0,0.05); margin-bottom:20px; }
        h3 { margin-top:0; color:#2c3e50; }
        .chart-row { display:grid; grid-template-columns:2fr 1fr; gap:20px; margin-bottom:20px; }
        table { width:100%; border-collapse:collapse; font-size:13px; }
        th, td { border:1px solid #ddd; padding:10px; text-align:left; }
        th { background:#3498db; color:white; }
        .stock-low { color:#e74c3c; font-weight:bold; }
        .stock-warn { color:#f39c12; font-weight:bold; }
        .filter-row { display:flex; gap:10px; margin-bottom:20px; align-items:center; }
        .filter-row select, .filter-row input { padding:8px 12px; border:1px solid #ddd; border-radius:5px; font-family:inherit; }
        .filter-row button { padding:8px 16px; background:#3498db; color:white; border:none; border-radius:5px; cursor:pointer; }
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
        <li><a href="quanlynhaphang.php"><i class="fa-solid fa-boxes-stacked"></i> Nhập hàng</a></li>
        <li><a href="thongke.php" class="active"><i class="fa-solid fa-chart-bar"></i> Thống kê</a></li>
        <li><a href="../dangxuat.php"><i class="fa-solid fa-right-from-bracket"></i> Đăng xuất</a></li>
    </ul>
</div>
<div class="main-content">
    <div class="header"><a href="../dangxuat.php"><i class="fa-solid fa-right-from-bracket"></i> Đăng xuất</a></div>
    <div class="content">
        <h2 style="color:#2c3e50;margin-bottom:15px;">Thống Kê & Báo Cáo</h2>

        <form class="filter-row" method="GET">
            <label style="font-weight:600;">Năm:</label>
            <input type="number" name="nam" value="<?php echo $nam; ?>" min="2020" max="2030">
            <button type="submit">Cập nhật</button>
        </form>

        <!-- Thống kê tổng quan -->
        <div class="stat-row">
            <div class="stat-card">
                <div class="stat-icon" style="background:#2ecc71;"><i class="fa-solid fa-money-bill-wave"></i></div>
                <div class="stat-info"><h4>Doanh Thu <?php echo $nam; ?></h4><div class="num"><?php echo number_format($tongDT,0,',','.'); ?> đ</div></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background:#3498db;"><i class="fa-solid fa-cart-shopping"></i></div>
                <div class="stat-info"><h4>Đơn Hàng <?php echo $nam; ?></h4><div class="num"><?php echo number_format($tongDH); ?></div></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background:#f39c12;"><i class="fa-solid fa-book"></i></div>
                <div class="stat-info"><h4>Tổng Đầu Sách</h4><div class="num"><?php echo number_format($tongSP); ?></div></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background:#9b59b6;"><i class="fa-solid fa-users"></i></div>
                <div class="stat-info"><h4>Khách Hàng</h4><div class="num"><?php echo number_format($tongKH); ?></div></div>
            </div>
        </div>

        <!-- Biểu đồ và Tồn kho -->
        <div class="chart-row">
            <div class="card">
                <h3>Biểu Đồ Doanh Thu & Nhập Hàng (<?php echo $nam; ?>)</h3>
                <canvas id="revenueChart" height="100"></canvas>
            </div>
            <div class="card">
                <h3>Sách Sắp Hết Hàng (≤ 10 quyển)</h3>
                <table>
                    <thead><tr><th>Tên Sách</th><th>Tồn</th></tr></thead>
                    <tbody>
                    <?php foreach($tonKhoData as $tk): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($tk['TenSach']); ?></td>
                        <td class="<?php echo $tk['SoLuong']==0 ? 'stock-low' : 'stock-warn'; ?>">
                            <?php echo $tk['SoLuong']==0 ? 'HẾT' : $tk['SoLuong']; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if(empty($tonKhoData)): ?><tr><td colspan="2" style="text-align:center;color:#2ecc71;">✓ Tồn kho đầy đủ!</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
const ctx = document.getElementById('revenueChart').getContext('2d');
new Chart(ctx, {
    type: 'bar',
    data: {
        labels: <?php echo json_encode($months); ?>,
        datasets: [
            {
                label: 'Doanh Thu (đ)',
                data: <?php echo json_encode($dtArray); ?>,
                backgroundColor: 'rgba(46, 204, 113, 0.7)',
                borderColor: '#2ecc71',
                borderWidth: 1
            },
            {
                label: 'Nhập Hàng (đ)',
                data: <?php echo json_encode($nhArray); ?>,
                backgroundColor: 'rgba(52, 152, 219, 0.7)',
                borderColor: '#3498db',
                borderWidth: 1
            }
        ]
    },
    options: {
        responsive: true,
        plugins: { legend: { position: 'top' } },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: function(value) {
                        if (value >= 1000000) return (value/1000000).toFixed(1) + ' tr';
                        return value.toLocaleString('vi-VN');
                    }
                }
            }
        }
    }
});
</script>
</body>
</html>
