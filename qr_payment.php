<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: index.php");
    exit();
}

// Check if there's pending checkout data
if (!isset($_SESSION['pending_checkout'])) {
    header("Location: cart.php");
    exit();
}

$checkout_data = $_SESSION['pending_checkout'];
$selected_ids = $checkout_data['selected_ids'];
$payment_method = $checkout_data['payment_method'];

// Verify that the checkout data is not expired
if (time() - $checkout_data['timestamp'] > 600) {
    unset($_SESSION['pending_checkout']);
    header("Location: cart.php?error=expired");
    exit();
}

// Database connection
$conn = new mysqli('localhost', 'root', '', 'dyna_shop');

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 1;
$purchased_items = [];
$subtotal = 0;

// Get selected items from cart
foreach ($_SESSION['cart'] as $key => $item) {
    if (in_array($item['id'], $selected_ids)) {
        $purchased_items[] = $item;
        $subtotal += ($item['price'] * $item['quantity']);
    }
}

$tax = $subtotal * 0.10;
$shipping = $subtotal > 0 ? 5.00 : 0;
$total = $subtotal + $tax + $shipping;
$reference_number = 'DYNA-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));

// Handle form submission
if (isset($_POST['confirm_payment'])) {
    // Save order
    $stmt = $conn->prepare("INSERT INTO orders (user_id, subtotal, tax, shipping, total_amount, payment_method, reference_number) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("iddddss", $user_id, $subtotal, $tax, $shipping, $total, $payment_method, $reference_number);
    $stmt->execute();
    $order_id = $conn->insert_id;
    $stmt->close();

    // Save items
    $stmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
    foreach ($purchased_items as $item) {
        $stmt->bind_param("iiid", $order_id, $item['id'], $item['quantity'], $item['price']);
        $stmt->execute();
        
        // Remove from cart
        foreach ($_SESSION['cart'] as $key => $cart_item) {
            if ($cart_item['id'] == $item['id']) {
                unset($_SESSION['cart'][$key]);
            }
        }
    }
    $stmt->close();
    $_SESSION['cart'] = array_values($_SESSION['cart']);
    unset($_SESSION['pending_checkout']);
    
    // Store receipt
    $_SESSION['receipt_data'] = [
        'order_id' => $order_id,
        'items' => $purchased_items,
        'subtotal' => $subtotal,
        'tax' => $tax,
        'shipping' => $shipping,
        'total' => $total,
        'payment_method' => $payment_method,
        'reference_number' => $reference_number,
        'date' => date('F j, Y, g:i a')
    ];
    
    // Redirect to cart
    header("Location: cart.php?payment_success=1");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>QR Code Payment - Dyna Shop</title>
    <link href="vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/fontawesome.css">
    <link rel="stylesheet" href="assets/css/templatemo-lugx-gaming.css">
    
    <style>
        .qr-payment-page { padding: 100px 0; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; }
        .qr-container { background: #fff; border-radius: 30px; padding: 40px; box-shadow: 0 20px 60px rgba(0,0,0,0.3); max-width: 500px; margin: 0 auto; }
        .qr-code-wrapper { background: #fff; padding: 20px; border-radius: 20px; text-align: center; margin: 20px 0; border: 2px solid #f0f0f0; }
        .qr-code { margin: 0 auto; display: block; max-width: 100%; height: auto; width: 200px; }
        .payment-details { background: #f8f9fa; border-radius: 15px; padding: 20px; margin: 20px 0; }
        .detail-row { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #e5e5e5; }
        .detail-row:last-child { border-bottom: none; }
        .total-amount { font-size: 24px; font-weight: bold; color: #ee626b; }
        .reference-box { background: #fff; border: 2px dashed #ee626b; border-radius: 10px; padding: 15px; text-align: center; margin: 20px 0; }
        .reference-box code { font-size: 18px; font-weight: bold; color: #ee626b; }
        .btn-pay { background: #ee626b; color: #fff; border: none; padding: 15px 30px; border-radius: 50px; font-weight: 600; width: 100%; margin-top: 20px; transition: all 0.3s; cursor: pointer; font-size: 16px; }
        .btn-pay:hover { background: #d94c1a; transform: translateY(-2px); }
        .btn-cancel { background: #6c757d; color: #fff; border: none; padding: 12px 25px; border-radius: 50px; margin-top: 10px; width: 100%; text-decoration: none; display: inline-block; text-align: center; }
        .btn-cancel:hover { background: #5a6268; color: #fff; text-decoration: none; }
        .merchant-info { text-align: center; margin-bottom: 20px; }
        .merchant-info img { width: 150px; margin-bottom: 10px; }
    </style>
</head>

<body>
    <div class="qr-payment-page">
        <div class="container">
            <div class="qr-container">
                <div class="merchant-info">
                    <img src="assets/images/Logo.png" alt="DYNA Shop">
                    <h4 class="mt-2">QR Code Payment</h4>
                    <p class="text-muted">Scan to pay using GCash, PayMaya, or any banking app</p>
                </div>
                
                <div class="qr-code-wrapper">
                    <img src="assets/images/qr.png" alt="Payment QR Code" class="qr-code">
                    <p class="text-muted mt-2">Scan this QR code to complete your payment</p>
                </div>
                
                <div class="reference-box">
                    <i class="fa fa-qrcode"></i> Reference Number:<br>
                    <code><?php echo $reference_number; ?></code>
                </div>
                
                <div class="payment-details">
                    <h6 class="mb-3">Order Summary</h6>
                    <?php foreach ($purchased_items as $item): ?>
                    <div class="detail-row">
                        <span><?php echo htmlspecialchars($item['name']); ?> (x<?php echo $item['quantity']; ?>)</span>
                        <span>₱<?php echo number_format($item['price'] * $item['quantity'], 2); ?></span>
                    </div>
                    <?php endforeach; ?>
                    <div class="detail-row">
                        <span>Subtotal</span>
                        <span>₱<?php echo number_format($subtotal, 2); ?></span>
                    </div>
                    <div class="detail-row">
                        <span>Tax (10%)</span>
                        <span>₱<?php echo number_format($tax, 2); ?></span>
                    </div>
                    <div class="detail-row">
                        <span>Shipping Fee</span>
                        <span>₱<?php echo number_format($shipping, 2); ?></span>
                    </div>
                    <div class="detail-row">
                        <strong>Total Amount</strong>
                        <strong class="total-amount">₱<?php echo number_format($total, 2); ?></strong>
                    </div>
                </div>
                
                <!-- Simple form - no JavaScript -->
                <form method="POST">
                    <button type="submit" name="confirm_payment" class="btn-pay">
                        <i class="fa fa-check-circle"></i> Confirm Payment
                    </button>
                </form>
                
                <a href="cart.php" class="btn-cancel">
                    <i class="fa fa-arrow-left"></i> Cancel
                </a>
            </div>
        </div>
    </div>
</body>
</html>