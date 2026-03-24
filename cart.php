<?php
// Start session
session_start();

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: index.php");
    exit();
}

// Ensure we have a user_id
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 1;

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

$receipt_data = null; 

// Check if payment was successful and receipt data exists
if (isset($_GET['payment_success']) && isset($_SESSION['receipt_data'])) {
    $receipt_data = $_SESSION['receipt_data'];
}

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    // Update quantity (AJAX)
    if ($action == 'update') {
        $id = $_POST['id'];
        $qty = (int)$_POST['qty'];

        foreach ($_SESSION['cart'] as &$item) {
            if ($item['id'] == $id) {
                $item['quantity'] = max(1, min(99, $qty));
                break;
            }
        }
        echo json_encode(['success' => true]);
        exit();
    }

    // Remove item (AJAX)
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

    // Clear cart (AJAX)
    if ($action == 'clear') {
        $_SESSION['cart'] = [];
        echo json_encode(['success' => true]);
        exit();
    }

    // Handle QR Code Checkout (Redirect to QR page)
    if ($action == 'qr_checkout' && isset($_POST['selected_ids'])) {
        $selected_ids = json_decode($_POST['selected_ids'], true);
        $payment_method = 'QR Code';
        
        // Store checkout data in session for QR page
        $_SESSION['pending_checkout'] = [
            'selected_ids' => $selected_ids,
            'payment_method' => $payment_method,
            'timestamp' => time()
        ];
        
        // Redirect to QR payment page
        header("Location: qr_payment.php");
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
    <link href="vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/fontawesome.css">
    <link rel="stylesheet" href="assets/css/templatemo-lugx-gaming.css">
    <link rel="stylesheet" href="assets/css/owl.css">
    <link rel="stylesheet" href="assets/css/animate.css">
    <link rel="stylesheet" href="https://unpkg.com/swiper@7/swiper-bundle.min.css" />

    <style>
        .cart-page { padding: 80px 0; }
        .cart-table { background: #fff; border-radius: 23px; overflow: hidden; box-shadow: 0 0 20px rgba(0,0,0,0.05); }
        .cart-table th { background: #ee626b; color: #fff; padding: 15px 20px; }
        .cart-table td { padding: 20px; vertical-align: middle; border-bottom: 1px solid #eee; }
        .checkbox-cell { width: 50px; text-align: center; }
        .custom-checkbox { width: 20px; height: 20px; cursor: pointer; accent-color: #ee626b; }
        .cart-product-img { width: 80px; height: 80px; border-radius: 15px; overflow: hidden; display: inline-block; margin-right: 15px; }
        .cart-product-img img { width: 100%; height: 100%; object-fit: cover; }
        .cart-product-title { font-weight: 600; color: #1e1e1e; }
        .cart-price { font-weight: 700; color: #ee626b; font-size: 18px; }
        .quantity-input { width: 70px; text-align: center; border: 1px solid #e5e5e5; border-radius: 10px; padding: 8px; }
        .update-btn { background: #ee626b; border: none; color: white; padding: 8px 15px; border-radius: 10px; margin-left: 5px; cursor: pointer; transition: all 0.3s; }
        .update-btn:hover { background: #d94c1a; transform: translateY(-2px); }
        .cart-remove { color: #ff4444; cursor: pointer; background: none; border: none; font-size: 18px; transition: all 0.3s; }
        .cart-remove:hover { color: #cc0000; transform: scale(1.1); }
        .cart-summary { background: #f7f7f7; border-radius: 23px; padding: 30px; margin-top: 30px; }
        .summary-item { display: flex; justify-content: space-between; padding: 12px 0; border-bottom: 1px solid #e5e5e5; }
        .summary-total { display: flex; justify-content: space-between; padding: 15px 0; font-size: 20px; font-weight: 700; color: #ee626b; }
        .checkout-btn, .clear-cart-btn { color: #fff; border: none; padding: 15px; border-radius: 25px; width: 100%; margin-top: 20px; cursor: pointer; transition: all 0.3s; }
        .checkout-btn { background: #ee626b; }
        .checkout-btn:hover { background: #d94c1a; transform: translateY(-2px); }
        .clear-cart-btn { background: #666; margin-top: 10px; }
        .empty-cart { text-align: center; padding: 60px; background: #fff; border-radius: 23px; }
        .empty-cart i { font-size: 80px; color: #ddd; margin-bottom: 20px; }
        .continue-shopping { background: #ee626b; color: #fff; padding: 12px 30px; border-radius: 25px; display: inline-block; margin-top: 20px; text-decoration: none; transition: all 0.3s; }
        .notification { position: fixed; top: 20px; right: 20px; background: #ee626b; color: #fff; padding: 12px 24px; border-radius: 25px; z-index: 9999; animation: slideIn 0.3s; }

        .receipt-wrapper { background: #fff; padding: 40px; border-radius: 23px; box-shadow: 0 0 20px rgba(0,0,0,0.05); max-width: 600px; margin: 0 auto; }
        .receipt-header { text-align: center; border-bottom: 2px dashed #ddd; padding-bottom: 20px; margin-bottom: 20px; }
        .receipt-header h2 { color: #ee626b; font-weight: 700; }
        .receipt-item { display: flex; justify-content: space-between; margin-bottom: 12px; font-size: 16px; padding: 8px 0; border-bottom: 1px solid #f0f0f0; }
        .grand-total { font-size: 22px !important; font-weight: 700; color: #ee626b; margin-top: 15px; border-top: 2px solid #ddd; padding-top: 15px; }
        .success-icon { font-size: 60px; color: #28a745; margin-bottom: 20px; }

        @keyframes slideIn { from { transform: translateX(100%); opacity: 0; } to { transform: translateX(0); opacity: 1; } }
        @media print {
            body * { visibility: hidden; }
            .receipt-wrapper, .receipt-wrapper * { visibility: visible; }
            .receipt-wrapper { position: absolute; left: 0; top: 0; width: 100%; margin: 0; padding: 15px; box-shadow: none; }
            .continue-shopping, .clear-cart-btn, .print-btn { display: none !important; }
        }
    </style>
</head>

<body>
    <header class="header-area header-sticky">
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <nav class="main-nav">
                        <a href="home.php" class="logo"><img src="assets/images/Logo.png" alt="" style="width: 158px;"></a>
                        <ul class="nav">
                            <li><a href="home.php">Home</a></li>
                            <li><a href="shop_page1.php">Our Shop</a></li>
                            <li><a href="contact.php">Contact Us</a></li>
                            <li><a href="cart.php" class="active">Cart</a></li>
                            <li><a href="index.php">Logout</a></li>
                        </ul>
                    </nav>
                </div>
            </div>
        </div>
    </header>

    <div class="page-heading header-text">
        <div class="container">
            <div class="row">
                <div class="col-lg-12">
                    <h3><?php echo $receipt_data ? 'Order Confirmation' : 'Shopping Cart'; ?></h3>
                </div>
            </div>
        </div>
    </div>

    <div class="cart-page">
        <div class="container">
            <div class="row">
                <div class="col-lg-12">
                    <?php if ($receipt_data): ?>
                        <!-- Receipt Display Section -->
                        <div class="receipt-wrapper">
                            <div class="text-center">
                                <div class="success-icon">
                                    <i class="fa fa-check-circle"></i>
                                </div>
                                <h2 style="color: #ee626b;">Payment Successful!</h2>
                                <p>Thank you for your purchase</p>
                            </div>
                            
                            <div class="receipt-header">
                                <h2>DYNA Shop</h2>
                                <p>Official Receipt</p>
                                <span style="color:#888;">Order ID: #<?php echo str_pad($receipt_data['order_id'], 6, "0", STR_PAD_LEFT); ?></span><br>
                                <?php if (isset($receipt_data['reference_number'])): ?>
                                <span style="color:#888;">Reference: <?php echo $receipt_data['reference_number']; ?></span><br>
                                <?php endif; ?>
                                <span style="color:#888;">Payment: <?php echo $receipt_data['payment_method']; ?></span><br>
                                <span style="color:#888;">Date: <?php echo $receipt_data['date']; ?></span>
                            </div>
                            
                            <div class="receipt-body">
                                <h6>Order Details:</h6>
                                <?php foreach ($receipt_data['items'] as $item): ?>
                                    <div class="receipt-item">
                                        <span style="flex:2;"><?php echo htmlspecialchars($item['name']); ?></span>
                                        <span style="flex:1; text-align:center;">x<?php echo $item['quantity']; ?></span>
                                        <span style="flex:1; text-align:right;">₱<?php echo number_format($item['price'] * $item['quantity'], 2); ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            
                            <div class="receipt-totals">
                                <div class="receipt-item"><span>Subtotal</span><span>₱<?php echo number_format($receipt_data['subtotal'], 2); ?></span></div>
                                <div class="receipt-item"><span>Tax (10%)</span><span>₱<?php echo number_format($receipt_data['tax'], 2); ?></span></div>
                                <div class="receipt-item"><span>Shipping</span><span>₱<?php echo number_format($receipt_data['shipping'], 2); ?></span></div>
                                <div class="receipt-item grand-total"><span>Total Paid</span><span>₱<?php echo number_format($receipt_data['total'], 2); ?></span></div>
                            </div>
                            
                            <div class="text-center mt-4">
                                <button onclick="window.print()" class="btn btn-dark" style="border-radius: 20px; padding: 12px 25px;"><i class="fa fa-print"></i> Print Receipt</button>
                                <a href="shop_page1.php" class="btn btn-danger" style="border-radius: 20px; padding: 12px 25px; margin-left: 10px;"><i class="fa fa-shopping-bag"></i> Continue Shopping</a>
                            </div>
                        </div>
                        <?php 
                        // Clear receipt data after displaying
                        unset($_SESSION['receipt_data']);
                        ?>

                    <?php else: ?>
                        <?php if (empty($_SESSION['cart'])): ?>
                            <div class="empty-cart">
                                <i class="fa fa-shopping-cart"></i>
                                <h3>Your cart is empty</h3>
                                <a href="shop_page1.php" class="continue-shopping">Continue Shopping</a>
                            </div>
                        <?php else: ?>
                            <div class="cart-table">
                                <table class="table">
                                    <thead>
                                        32<th class="checkbox-cell"><input type="checkbox" id="selectAll" class="custom-checkbox" checked></th>
                                            <th>Product</th><th>Price</th><th>Quantity</th><th>Total</th><th></th>
                                        </tr>
                                    </thead>
                                    <tbody id="cart-items">
                                        <?php $subtotal = 0; foreach ($_SESSION['cart'] as $item): $item_total = $item['price'] * $item['quantity']; $subtotal += $item_total; ?>
                                            <tr id="item-<?php echo $item['id']; ?>">
                                                <td class="checkbox-cell"><input type="checkbox" class="item-select custom-checkbox" value="<?php echo $item['id']; ?>" checked> </td>
                                                <td>
                                                    <div class="cart-product-img"><img src="<?php echo $item['image']; ?>"></div>
                                                    <span class="cart-product-title"><?php echo htmlspecialchars($item['name']); ?></span>
                                                </td>
                                                <td class="cart-price" data-price="<?php echo $item['price']; ?>">₱<?php echo number_format($item['price'], 2); ?></td>
                                                <td>
                                                    <input type="number" class="quantity-input" id="qty-<?php echo $item['id']; ?>" value="<?php echo $item['quantity']; ?>" min="1" max="99">
                                                    <button class="update-btn" onclick="updateItem(<?php echo $item['id']; ?>, <?php echo $item['price']; ?>)">Update</button>
                                                </td>
                                                <td class="cart-price" id="total-<?php echo $item['id']; ?>">₱<?php echo number_format($item_total, 2); ?></td>
                                                <td><button class="cart-remove" onclick="removeItem(<?php echo $item['id']; ?>)"><i class="fa fa-trash"></i></button></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>

                            <div class="cart-summary">
                                <div class="row">
                                    <div class="col-lg-6 offset-lg-6">
                                        <h4>Cart Summary</h4>
                                        <div class="summary-item"><span>Subtotal</span><span id="subtotal">₱<?php echo number_format($subtotal, 2); ?></span></div>
                                        <div class="summary-item"><span>Tax (10%)</span><span id="tax">₱<?php echo number_format($subtotal * 0.1, 2); ?></span></div>
                                        <div class="summary-item"><span>Shipping</span><span id="shipping">₱5.00</span></div>
                                        <div class="summary-total"><span>Total</span><span id="total">₱<?php echo number_format($subtotal * 1.1 + 5, 2); ?></span></div>
                                        
                                        <button onclick="proceedToPayment()" class="checkout-btn" id="proceedBtn">
                                            <span class="btn-text">Proceed to Payment (QR Code)</span>
                                            <span class="loading-spinner"></span>
                                        </button>
                                        <button onclick="clearCart()" class="clear-cart-btn">Clear Entire Cart</button>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="vendor/jquery/jquery.min.js"></script>
    <script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    
    <script>
        let selectedItemsList = [];
        
        function showMessage(msg) {
            let div = $('<div class="notification"><i class="fa fa-check-circle"></i> '+msg+'</div>');
            $('body').append(div);
            setTimeout(() => div.fadeOut(500, function(){ $(this).remove(); }), 3000);
        }

        function recalculateSelectedTotals() {
            let subtotal = 0;
            $('.item-select:checked').each(function() {
                let row = $(this).closest('tr');
                let price = parseFloat(row.find('.cart-price').data('price'));
                let qty = parseInt(row.find('.quantity-input').val());
                subtotal += (price * qty);
            });
            let tax = subtotal * 0.10;
            let shipping = subtotal > 0 ? 5 : 0;
            $('#subtotal').text('₱' + subtotal.toFixed(2));
            $('#tax').text('₱' + tax.toFixed(2));
            $('#shipping').text('₱' + shipping.toFixed(2));
            $('#total').text('₱' + (subtotal + tax + shipping).toFixed(2));
        }

        $('#selectAll').on('change', function() {
            $('.item-select').prop('checked', this.checked);
            recalculateSelectedTotals();
        });
        
        $('.item-select').on('change', recalculateSelectedTotals);

        function updateItem(id, price) {
            let qty = $('#qty-' + id).val();
            $.post('cart.php', {action: 'update', id: id, qty: qty}, function() {
                $('#total-' + id).text('₱' + (price * qty).toFixed(2));
                recalculateSelectedTotals();
                showMessage('Quantity updated!');
            }, 'json');
        }

        function removeItem(id) {
            if(confirm('Remove item?')) {
                $.post('cart.php', {action: 'remove', id: id}, function() {
                    $('#item-' + id).remove();
                    recalculateSelectedTotals();
                    if($('.item-select').length === 0) location.reload();
                }, 'json');
            }
        }

        function clearCart() {
            if(confirm('Clear entire cart?')) {
                $.post('cart.php', {action: 'clear'}, function() { location.reload(); }, 'json');
            }
        }
        
        function proceedToPayment() {
            selectedItemsList = [];
            $('.item-select:checked').each(function() { 
                selectedItemsList.push(parseInt($(this).val())); 
            });
            
            if (selectedItemsList.length === 0) {
                alert('Please select at least one item to checkout.');
                return;
            }
            
            // Directly redirect to QR payment page
            let form = $('<form method="POST" action="cart.php"></form>');
            form.append('<input type="hidden" name="action" value="qr_checkout">');
            form.append('<input type="hidden" name="selected_ids" value=\'' + JSON.stringify(selectedItemsList) + '\'>');
            $('body').append(form);
            form.submit();
        }
        
        // Initial calculation
        recalculateSelectedTotals();
    </script>
</body>
</html>
<?php $conn->close(); ?>