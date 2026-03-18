document.addEventListener("DOMContentLoaded", function() {
    fetch('control/SanPhamController.php?action=get_hot_products')
        .then(response => response.json())
        .then(data => {
            const hotProductsContainer = document.querySelector('.hot-products');
            let html = '<h2>Sản phẩm hot</h2>';
            data.hot_products.forEach(product => {
                html += `
                    <div class="hot-product-item">
                        <img src="images/${product.hinh}" alt="${product.tensach}">
                        <div class="hot-product-item-info">
                            <p>${product.tensach}</p>
                            <p>${product.dongia} đ</p>
                        </div>
                    </div>
                `;
            });
            hotProductsContainer.innerHTML = html;
        });
});
