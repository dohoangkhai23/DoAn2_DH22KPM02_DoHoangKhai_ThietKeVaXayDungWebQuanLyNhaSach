<?php
session_start();
require_once __DIR__ . "/csdl.php"; // Kết nối CSDL

// Lấy id sản phẩm từ URL
$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($product_id <= 0) {
    die("ID sản phẩm không hợp lệ.");
}

// Truy vấn thông tin sản phẩm
try {
    $stmt = $connection->prepare("SELECT * FROM sach WHERE MaSach = :id");
    $stmt->execute([':id' => $product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Lỗi truy vấn: " . $e->getMessage());
}

// Nếu không tìm thấy sản phẩm
if (!$product) {
    die("Sản phẩm không tồn tại.");
}

// Gán biến cho dễ sử dụng
$product_name = htmlspecialchars($product['TenSach']);
$product_price = (int)$product['GiaBan'];
$product_image = htmlspecialchars($product['HinhAnh']);
$product_description = htmlspecialchars($product['MoTa'] ?? 'Chưa có mô tả cho sản phẩm này.');
$product_brand = htmlspecialchars($product['NhaXuatBan'] ?? 'Chưa rõ');
$product_pages = htmlspecialchars($product['SoTrang'] ?? '...');
$product_year = htmlspecialchars($product['NgayXuatBan'] ?? '...');
$product_size = htmlspecialchars($product['KichThuoc'] ?? '...');
$product_weight = htmlspecialchars($product['TrongLuong'] ?? '...');

?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Chi tiết sản phẩm - <?php echo $product_name; ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Google Font -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="css/index.css">
    <link rel="stylesheet" href="css/chitietsanpham.css">
    <style>
        /* Khắc phục lỗi chữ trắng trên nền sáng */
        body { padding-top: 100px; }
        header { background: #333; }
    </style>
</head>
<body>

<header>
    <div class="logo">📚 Nhà Sách</div>

    <nav>
        <ul>
            <li><a href="index.php">Trang chủ</a></li>
            <li><a href="#">Danh mục</a></li>
            <li><a href="SanPham.php">Sản phẩm</a></li>
            <li><a href="Gioithieu.php">Giới thiệu</a></li>
            <li><a href="lienhe.php">Liên hệ</a></li>
        </ul>
    </nav>
    
    <div class="search-bar">
        <form action="timkiem.php" method="GET">
            <input type="text" name="keyword" placeholder="Tìm kiếm sách...">
            <button type="submit"><i class="fa-solid fa-search"></i></button>
        </form>
    </div>

    <div class="header-right">
        
        <!-- Giỏ hàng -->
        <div class="icon-box">
            <a href="#" onclick="toggleCart()">
                <i class="fa-solid fa-cart-shopping"></i>
                <span class="badge side-cart-count">0</span>
            </a>
        </div>

        <!-- Tài khoản -->
        <div class="icon-box">
            <a href="#"><i class="fa-solid fa-user"></i></a>
        </div>

    </div>
</header>

<div class="container">

<!-- BREADCRUMB -->
<div class="breadcrumb">
Trang chủ &gt; Sản Phẩm &gt; <?php echo $product_name; ?>
</div>

<div class="product-detail">

<!-- ẢNH SẢN PHẨM -->
<div class="product-image">

<img id="main-img" src="images/<?php echo $product_image; ?>">

<div class="thumbnail">
    <!-- Giả sử chỉ có 1 ảnh chính, có thể phát triển thêm để có nhiều ảnh -->
    <img src="images/<?php echo $product_image; ?>" onclick="changeImg(this)">
</div>

</div>


<!-- THÔNG TIN -->
<div class="product-info">

<h1><?php echo strtoupper($product_name); ?></h1>

<p><b>Thương hiệu:</b> <?php echo $product_brand; ?></p>

<p><b>Tình trạng:</b> <span class="stock">Còn hàng</span></p>

<div class="price">
<?php echo number_format($product_price, 0, ',', '.'); ?>₫
</div>

<hr>

<p><b>Khổ sách:</b> <?php echo $product_size; ?></p>
<p><b>Số trang:</b> <?php echo $product_pages; ?></p>
<p><b>Năm:</b> <?php echo $product_year; ?></p>
<p><b>Khối lượng:</b> <?php echo $product_weight; ?></p>

<hr>

<p class="desc">
<?php echo nl2br($product_description); ?>
</p>

<hr>

<!-- SỐ LƯỢNG -->
<div class="quantity">

<button onclick="giam()">-</button>

<input type="text" id="qty" value="1">

<button onclick="tang()">+</button>

</div>


<!-- NÚT MUA -->
<button class="buy" onclick="addToCart(<?php echo $product_id; ?>, '<?php echo addslashes($product_name); ?>', <?php echo $product_price; ?>, 'images/<?php echo $product_image; ?>')">
🛒 THÊM VÀO GIỎ
</button>

</div>

</div>

</div>


<script>

function changeImg(img)
{
document.getElementById("main-img").src = img.src;
}

function tang()
{
let qty = document.getElementById("qty");
qty.value = parseInt(qty.value) + 1;
}

function giam()
{
let qty = document.getElementById("qty");

if(qty.value > 1)
qty.value = parseInt(qty.value) - 1;

}

</script>
<div id="sideCartOverlay" class="overlay"></div>
<div id="sideCart" class="side-cart">
    <div class="side-cart-header">
        <h3>Giỏ hàng của bạn</h3>
        <button class="close-cart" onclick="toggleCart()">&times;</button>
    </div>
    <div id="sideCartBody" class="side-cart-body">
        <!-- Cart items will be rendered here by JavaScript -->
    </div>
    <div id="sideCartFooter" class="side-cart-footer">
        <div class="total-row">
            <span>Tổng tiền:</span>
            <span id="sideCartTotal">0 đ</span>
        </div>
        <div class="btn-group">
            <button class="btn btn-clear" onclick="clearCart()">Xóa hết</button>
            <a href="thanhtoan.php" class="btn btn-checkout">Thanh toán</a>
        </div>
    </div>
</div>
<script src="js/cart.js"></script>

</body>
</html>
