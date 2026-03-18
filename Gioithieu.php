<?php
session_start();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Giới thiệu - Nhà Sách</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="css/index.css">
    <link rel="stylesheet" href="css/lienhe.css"> <!-- Sử dụng lại CSS từ trang liên hệ -->
</head>
<body>

<header>
    <div class="logo">📚 Nhà Sách</div>
    <nav>
        <ul>
            <li><a href="index.php">Trang chủ</a></li>
            <li><a href="Gioithieu.php">Giới thiệu</a></li>
            <li><a href="SanPham.php">Sản phẩm</a></li>
            <li><a href="lienhe.php">Liên hệ</a></li>
        </ul>
    </nav>
    <div class="header-right">
        <div class="search-bar">
            <form action="timkiem.php" method="GET" style="display: flex;">
                <input type="text" name="keyword" placeholder="Tìm kiếm sách...">
                <button type="submit"><i class="fa-solid fa-search"></i></button>
            </form>
        </div>
        <div class="icon-box">
            <a href="#" onclick="toggleCart()"><i class="fa-solid fa-cart-shopping"></i><span class="badge side-cart-count">0</span></a>
        </div>
        <div class="icon-box">
            <a href="#"><i class="fa-solid fa-user"></i></a>
        </div>
    </div>
</header>

<main class="contact-container">
    <div class="contact-form-wrapper">
        <h1 class="page-title" style="text-align: center; margin-bottom: 30px;">Về Chúng Tôi</h1>
        <div class="about-content" style="text-align: justify; line-height: 1.8;">
            <p>Chào mừng bạn đến với <strong>Nhà Sách</strong>, điểm đến lý tưởng cho những người yêu sách trên khắp Việt Nam. Chúng tôi tự hào là một trong những nhà sách trực tuyến hàng đầu, mang đến một thế giới tri thức và giải trí ngay trong tầm tay của bạn.</p>
            <p>Tại Nhà Sách, chúng tôi tin rằng mỗi cuốn sách là một cuộc phiêu lưu, một bài học, và một người bạn. Sứ mệnh của chúng tôi là lan tỏa niềm đam mê đọc sách, kết nối cộng đồng và mang đến những tác phẩm chất lượng từ khắp nơi trên thế giới đến với độc giả Việt.</p>
            
            <h3 style="margin-top: 20px;">Sản phẩm đa dạng</h3>
            <p>Chúng tôi cung cấp hàng ngàn đầu sách thuộc mọi thể loại, từ sách văn học kinh điển, sách kinh doanh, kỹ năng sống, cho đến truyện tranh, sách thiếu nhi và sách ngoại văn. Tất cả sản phẩm đều được tuyển chọn kỹ lưỡng, đảm bảo chất lượng từ nội dung đến hình thức.</p>

            <h3 style="margin-top: 20px;">Trải nghiệm mua sắm tuyệt vời</h3>
            <p>Với giao diện website thân thiện, dễ sử dụng và quy trình đặt hàng nhanh chóng, chúng tôi cam kết mang lại trải nghiệm mua sắm trực tuyến thuận tiện nhất. Dịch vụ giao hàng toàn quốc, chính sách đổi trả linh hoạt và đội ngũ chăm sóc khách hàng tận tâm luôn sẵn sàng hỗ trợ bạn.</p>

            <p>Hãy cùng Nhà Sách khám phá những chân trời tri thức mới và xây dựng một tương lai tốt đẹp hơn qua từng trang sách!</p>
        </div>
    </div>
</main>

<div id="sideCartOverlay" class="overlay"></div>
<div id="sideCart" class="side-cart">
    <div class="side-cart-header">
        <h3>Giỏ hàng của bạn</h3>
        <button class="close-cart" onclick="toggleCart()">&times;</button>
    </div>
    <div id="sideCartBody" class="side-cart-body"></div>
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
