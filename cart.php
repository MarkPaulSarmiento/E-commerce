<?php
// Start session
session_start();

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: index.php");
    exit();
}

// Database connection
$conn = new mysqli('localhost', 'root', '', 'dyna_shop');

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Initialize cart if not exists
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Handle AJAX requests
if (isset($_POST['action'])) {
    $action = $_POST['action'];
    
    // Update quantity
    if ($action == 'update') {
        $id = $_POST['id'];
        $qty = $_POST['qty'];
        
        foreach ($_SESSION['cart'] as &$item) {
            if ($item['id'] == $id) {
                $item['quantity'] = $qty;
                break;
            }
        }
        
        // Calculate totals
        $subtotal = 0;
        foreach ($_SESSION['cart'] as $item) {
            $subtotal += $item['price'] * $item['quantity'];
        }
        $tax = $subtotal * 0.1;
        $shipping = $subtotal > 0 ? 5 : 0;
        $total = $subtotal + $tax + $shipping;
        
        echo json_encode([
            'subtotal' => number_format($subtotal, 2),
            'tax' => number_format($tax, 2),
            'shipping' => number_format($shipping, 2),
            'total' => number_format($total, 2)
        ]);
        exit();
    }
    
    // Remove item
    if ($action == 'remove') {
        $id = $_POST['id'];
        
        foreach ($_SESSION['cart'] as $key => $item) {
            if ($item['id'] == $id) {
                unset($_SESSION['cart'][$key]);
                break;
            }
        }
        $_SESSION['cart'] = array_values($_SESSION['cart']);
        
        echo json_encode(['success' => true]);
        exit();
    }
    
    // Clear cart
    if ($action == 'clear') {
        $_SESSION['cart'] = [];
        echo json_encode(['success' => true]);
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dyna Shop - Shopping Cart</title>
    
    <!-- CSS Files -->
    <link href="vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/fontawesome.css">
    <link rel="stylesheet" href="assets/css/templatemo-lugx-gaming.css">
    <link rel="stylesheet" href="assets/css/owl.css">
    <link rel="stylesheet" href="assets/css/animate.css">
    <link rel="stylesheet" href="https://unpkg.com/swiper@7/swiper-bundle.min.css"/>
    
    <style>
    /* Cart Page Specific Styles */
    .cart-page {
        padding: 80px 0; 
    }
    .cart-table {
        background: #fff;
        border-radius: 23px; 
        overflow: hidden; 
        box-shadow: 0 0 20px rgba(0,0,0,0.05); 
    }
    .cart-table th {
        background: #ee626b; 
        color: #fff; 
        padding: 15px 20px; 
    }
    .cart-table td { 
        padding: 20px; 
        vertical-align: middle; 
        border-bottom: 1px solid #eee; 
    }
    .cart-product-img { 
        width: 80px; 
        height: 80px; 
        border-radius: 15px; 
        overflow: hidden; 
        display: inline-block; 
        margin-right: 15px; 
    }
    .cart-product-img img { 
        width: 100%; 
        height: 100%; 
        object-fit: cover; 
    }
    .cart-product-title { 
        font-weight: 600; 
        color: #1e1e1e; 
    }
    .cart-price { 
        font-weight: 700; 
        color: #ee626b; 
        font-size: 18px; 
    }
    .quantity-input { 
        width: 70px; 
        text-align: center; 
        border: 1px solid #e5e5e5; 
        border-radius: 10px; 
        padding: 8px; 
    }
    .update-btn { 
        background: #ee626b; 
        border: none; 
        color: white; 
        padding: 8px 15px; 
        border-radius: 10px; 
        margin-left: 5px; 
        cursor: pointer; 
        transition: all 0.3s;
    }
    .update-btn:hover {
        background: #d94c1a;
        transform: translateY(-2px);
    }
    .cart-remove { 
        color: #ff4444; 
        cursor: pointer; 
        background: none; 
        border: none; 
        font-size: 18px;
        transition: all 0.3s;
    }
    .cart-remove:hover {
        color: #cc0000;
        transform: scale(1.1);
    }
    .cart-summary { 
        background: #f7f7f7; 
        border-radius: 23px; 
        padding: 30px; 
        margin-top: 30px; 
    }
    .summary-item { 
        display: flex; 
        justify-content: space-between; 
        padding: 12px 0; 
        border-bottom: 1px solid #e5e5e5; 
    }
    .summary-total { 
        display: flex; 
        justify-content: space-between; 
        padding: 15px 0; 
        font-size: 20px; 
        font-weight: 700; 
        color: #ee626b; 
    }
    .checkout-btn, .clear-cart-btn { 
        background: #ee626b; 
        color: #fff; 
        border: none; 
        padding: 15px; 
        border-radius: 25px; 
        width: 100%; 
        margin-top: 20px; 
        cursor: pointer; 
        transition: all 0.3s;
    }
    .checkout-btn:hover {
        background: #d94c1a;
        transform: translateY(-2px);
    }
    .clear-cart-btn { 
        background: #666; 
        margin-top: 10px; 
    }
    .clear-cart-btn:hover {
        background: #555;
        transform: translateY(-2px);
    }
    .empty-cart { 
        text-align: center; 
        padding: 60px; 
        background: #fff; 
        border-radius: 23px; 
    }
    .empty-cart i { 
        font-size: 80px; 
        color: #ddd; 
        margin-bottom: 20px; 
    }
    .continue-shopping { 
        background: #ee626b; 
        color: #fff; 
        padding: 12px 30px; 
        border-radius: 25px; 
        display: inline-block; 
        margin-top: 20px; 
        text-decoration: none; 
        transition: all 0.3s;
    }
    .continue-shopping:hover {
        background: #d94c1a;
        transform: translateY(-2px);
        color: #fff;
    }
    .notification { 
        position: fixed; 
        top: 20px; 
        right: 20px; 
        background: #ee626b; 
        color: #fff; 
        padding: 12px 24px; 
        border-radius: 25px; 
        z-index: 9999; 
        animation: slideIn 0.3s; 
    }
    @keyframes slideIn { 
        from { transform: translateX(100%); opacity: 0; } 
        to { transform: translateX(0); opacity: 1; } 
    }
    @keyframes fadeOut { 
        from { opacity: 1; } 
        to { opacity: 0; } 
    }
    </style>
</head>
<body>

    <!-- Header -->
    <header class="header-area header-sticky">
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <nav class="main-nav">
                        <!-- Logo -->
                        <a href="home.php" class="logo">
                            <img src="assets/images/Logo.png" alt="" style="width: 158px;">
                        </a>
                        <!-- Menu -->
                        <ul class="nav">
                            <li><a href="home.php">Home</a></li>
                            <li><a href="shop_page1.php">Our Shop</a></li>
                            <li><a href="contact.php">Contact Us</a></li>
                            <li><a href="cart.php" class="active">Cart</a></li>
                            <li><a href="index.php">Logout</a></li>
                        </ul>
                        <a class='menu-trigger'>
                            <span>Menu</span>
                        </a>
                    </nav>
                </div>
            </div>
        </div>
    </header>

    <!-- Page Title -->
    <div class="page-heading header-text">
        <div class="container">
            <div class="row">
                <div class="col-lg-12">
                    <h3>Shopping Cart</h3>
                    <span class="breadcrumb"><a href="home.php">Home</a> > Cart</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Cart Content -->
    <div class="cart-page">
        <div class="container">
            <div class="row">
                <div class="col-lg-12">
                    <?php if (empty($_SESSION['cart'])): ?>
                        <!-- Empty Cart -->
                        <div class="empty-cart">
                            <i class="fa fa-shopping-cart"></i>
                            <h3>Your cart is empty</h3>
                            <p>Looks like you haven't added any items to your cart yet.</p>
                            <a href="shop_page1.php" class="continue-shopping">Continue Shopping</a>
                        </div>
                    <?php else: ?>
                        <!-- Cart Table -->
                        <div class="cart-table">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th>Price</th>
                                        <th>Quantity</th>
                                        <th>Total</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody id="cart-items">
                                    <?php 
                                    $subtotal = 0;
                                    foreach ($_SESSION['cart'] as $item): 
                                        $item_total = $item['price'] * $item['quantity'];
                                        $subtotal += $item_total;
                                    ?>
                                    <tr id="item-<?php echo $item['id']; ?>">
                                        <td>
                                            <div style="display: flex; align-items: center;">
                                                <div class="cart-product-img">
                                                    <img src="<?php echo $item['image']; ?>" alt="<?php echo $item['name']; ?>">
                                                </div>
                                                <div class="cart-product-title"><?php echo $item['name']; ?></div>
                                            </div>
                                        </td>
                                        <td class="cart-price" data-price="<?php echo $item['price']; ?>">₱<?php echo number_format($item['price'], 2); ?></td>
                                        <td>
                                            <input type="number" class="quantity-input" id="qty-<?php echo $item['id']; ?>" value="<?php echo $item['quantity']; ?>" min="1" max="99">
                                            <button class="update-btn" onclick="updateItem(<?php echo $item['id']; ?>, <?php echo $item['price']; ?>)">Update</button>
                                        </td>
                                        <td class="cart-price" id="total-<?php echo $item['id']; ?>">₱<?php echo number_format($item_total, 2); ?></td>
                                        <td>
                                            <button class="cart-remove" onclick="removeItem(<?php echo $item['id']; ?>)">
                                                <i class="fa fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Cart Summary -->
                        <div class="cart-summary">
                            <div class="row">
                                <div class="col-lg-6 offset-lg-6">
                                    <h4>Cart Summary</h4>
                                    <?php 
                                    $tax = $subtotal * 0.10;
                                    $shipping = $subtotal > 0 ? 5 : 0;
                                    $total = $subtotal + $tax + $shipping;
                                    ?>
                                    <div class="summary-item">
                                        <span>Subtotal</span>
                                        <span id="subtotal">₱<?php echo number_format($subtotal, 2); ?></span>
                                    </div>
                                    <div class="summary-item">
                                        <span>Tax (10%)</span>
                                        <span id="tax">₱<?php echo number_format($tax, 2); ?></span>
                                    </div>
                                    <div class="summary-item">
                                        <span>Shipping</span>
                                        <span id="shipping">₱<?php echo number_format($shipping, 2); ?></span>
                                    </div>
                                    <div class="summary-total">
                                        <span>Total</span>
                                        <span id="total">₱<?php echo number_format($total, 2); ?></span>
                                    </div>
                                    <button onclick="checkout()" class="checkout-btn">Proceed to Checkout</button>
                                    <button onclick="clearCart()" class="clear-cart-btn">Clear Cart</button>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <footer>
        <div class="container">
            <div class="col-lg-12">
                <p>Copyright © 2024 DYNA Shop. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script src="vendor/jquery/jquery.min.js"></script>
    <script src="vendor/bootstrap/js/bootstrap.min.js"></script>
    <script src="assets/js/isotope.min.js"></script>
    <script src="assets/js/owl-carousel.js"></script>
    <script src="assets/js/counter.js"></script>
    <script src="assets/js/custom.js"></script>
    
    <script>
    // Simple function to show notification
    function showMessage(msg) {
        let div = document.createElement('div');
        div.className = 'notification';
        div.innerHTML = msg;
        document.body.appendChild(div);
        
        setTimeout(() => {
            div.style.animation = 'fadeOut 0.5s';
            setTimeout(() => div.remove(), 500);
        }, 3000);
    }
    
    // Update item quantity - FIXED VERSION
    function updateItem(id, price) {
        let qty = document.getElementById('qty-' + id).value;
        
        // Validate quantity
        if (qty < 1) {
            qty = 1;
            document.getElementById('qty-' + id).value = 1;
        }
        if (qty > 99) {
            qty = 99;
            document.getElementById('qty-' + id).value = 99;
        }
        
        // Send update request
        fetch('cart.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'action=update&id=' + id + '&qty=' + qty
        })
        .then(response => response.json())
        .then(data => {
            // Update item total using the price parameter
            let newTotal = price * qty;
            document.getElementById('total-' + id).innerHTML = '₱' + newTotal.toFixed(2);
            
            // Update summary with PHP formatted values (already with ₱ symbol)
            document.getElementById('subtotal').innerHTML = '₱' + data.subtotal;
            document.getElementById('tax').innerHTML = '₱' + data.tax;
            document.getElementById('shipping').innerHTML = '₱' + data.shipping;
            document.getElementById('total').innerHTML = '₱' + data.total;
            
            showMessage('Cart updated successfully!');
        })
        .catch(error => {
            console.error('Error:', error);
            showMessage('Error updating cart. Please try again.');
        });
    }
    
    // Remove item
    function removeItem(id) {
        if (confirm('Remove this item from your cart?')) {
            fetch('cart.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'action=remove&id=' + id
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('item-' + id).remove();
                    showMessage('Item removed from cart!');
                    
                    // Check if cart is empty
                    if (document.querySelectorAll('#cart-items tr').length === 0) {
                        location.reload();
                    } else {
                        // If cart not empty, refresh the page to update totals
                        location.reload();
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showMessage('Error removing item. Please try again.');
            });
        }
    }
    
    // Clear cart
    function clearCart() {
        if (confirm('Clear entire cart? This action cannot be undone.')) {
            fetch('cart.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'action=clear'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showMessage('Error clearing cart. Please try again.');
            });
        }
    }
    
    // Checkout
    function checkout() {
        if (confirm('Proceed to checkout?')) {
            alert('Thank you for shopping with DYNA Shop!\n\nYour order has been placed successfully.');
            clearCart();
        }
    }
    
    // Add enter key support for quantity inputs
    document.querySelectorAll('.quantity-input').forEach(input => {
        input.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                const id = this.id.split('-')[1];
                const price = parseFloat(this.closest('tr').querySelector('.cart-price').getAttribute('data-price'));
                updateItem(id, price);
            }
        });
    });
    </script>

</body>
</html>

<?php
$conn->close();
?>