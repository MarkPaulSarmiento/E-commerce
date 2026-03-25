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
// Retrieve shipping address and decode any URL-encoded characters
$shipping_address = urldecode($checkout_data['shipping_address'] ?? '');  // get the address from cart

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

// Get user email for sending receipt
$user_email = '';
$user_name = '';
$email_stmt = $conn->prepare("SELECT username, email FROM users WHERE user_id = ?");
$email_stmt->bind_param("i", $user_id);
$email_stmt->execute();
$email_result = $email_stmt->get_result();
if ($email_row = $email_result->fetch_assoc()) {
    $user_name = $email_row['username'];
    $user_email = $email_row['email'];
}
$email_stmt->close();

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

// Function to send email (includes shipping address)
function sendOrderConfirmation($to_email, $to_name, $order_data, $items, $reference_number, $order_id) {
    $subject = "Order Confirmation - DYNA Shop #" . str_pad($order_id, 8, '0', STR_PAD_LEFT);
    
    // Build email body
    $message = "
    <html>
    <head>
        <style>
            body { font-family: 'Poppins', Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: #ee626b; color: white; padding: 20px; text-align: center; border-radius: 10px 10px 0 0; }
            .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; }
            .order-details { background: white; padding: 20px; border-radius: 10px; margin: 20px 0; }
            .order-row { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #eee; }
            .total-row { font-size: 18px; font-weight: bold; color: #ee626b; border-top: 2px solid #ee626b; margin-top: 10px; padding-top: 15px; }
            .footer { text-align: center; padding: 20px; font-size: 12px; color: #666; }
            .btn { background: #ee626b; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; }
            .reference { background: #fff3cd; border-left: 4px solid #ffc107; padding: 10px; margin: 15px 0; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h2>🎉 Order Confirmed!</h2>
                <p>Thank you for shopping with DYNA Shop</p>
            </div>
            <div class='content'>
                <h3>Hello " . htmlspecialchars($to_name) . ",</h3>
                <p>Your order has been successfully placed and is now being processed. Here are your order details:</p>
                
                <div class='reference'>
                    <strong>📦 Order #:</strong> " . str_pad($order_id, 8, '0', STR_PAD_LEFT) . "<br>
                    <strong>🔖 Reference Number:</strong> " . $reference_number . "<br>
                    <strong>📅 Order Date:</strong> " . date('F j, Y, g:i a') . "<br>
                    <strong>💳 Payment Method:</strong> " . ucfirst($order_data['payment_method']) . "<br>
                    <strong>📍 Shipping Address:</strong> " . nl2br(htmlspecialchars($order_data['shipping_address'])) . "
                </div>
                
                <div class='order-details'>
                    <h4>🛍️ Items Ordered:</h4>";
    
    foreach ($items as $item) {
        $message .= "
                    <div class='order-row'>
                        <span>" . htmlspecialchars($item['name']) . " (x" . $item['quantity'] . ")</span>
                        <span>₱" . number_format($item['price'] * $item['quantity'], 2) . "</span>
                    </div>";
    }
    
    $message .= "
                    <div class='order-row'>
                        <strong>Subtotal:</strong>
                        <span>₱" . number_format($order_data['subtotal'], 2) . "</span>
                    </div>
                    <div class='order-row'>
                        <strong>Tax (10%):</strong>
                        <span>₱" . number_format($order_data['tax'], 2) . "</span>
                    </div>
                    <div class='order-row'>
                        <strong>Shipping Fee:</strong>
                        <span>₱" . number_format($order_data['shipping'], 2) . "</span>
                    </div>
                    <div class='total-row'>
                        <strong>TOTAL:</strong>
                        <strong>₱" . number_format($order_data['total'], 2) . "</strong>
                    </div>
                </div>
                
                <p>Your order will be processed within 24-48 hours. You will receive another email once your items are shipped.</p>
                
                <p style='text-align: center; margin-top: 30px;'>
                    <a href='http://localhost/E-commerce/home.php' class='btn'>Continue Shopping</a>
                </p>
            </div>
            <div class='footer'>
                <p>© 2024 DYNA Shop. All rights reserved.<br>
                Need help? Contact us at dynamastershop@gmail.com</p>
            </div>
        </div>
    </body>
    </html>";
    
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: DYNA Shop <dynamastershop@gmail.com>" . "\r\n";
    $headers .= "Reply-To: dynamastershop@gmail.com" . "\r\n";
    
    return mail($to_email, $subject, $message, $headers);
}

// Handle form submission
if (isset($_POST['confirm_payment'])) {
    // Check if orders table exists and has correct structure (including shipping_address)
    $table_check = $conn->query("SHOW TABLES LIKE 'orders'");
    if ($table_check->num_rows == 0) {
        $create_orders = "CREATE TABLE IF NOT EXISTS orders (
            order_id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            subtotal DECIMAL(10,2) NOT NULL,
            tax DECIMAL(10,2) NOT NULL,
            shipping DECIMAL(10,2) NOT NULL,
            total_amount DECIMAL(10,2) NOT NULL,
            payment_method VARCHAR(50) NOT NULL,
            shipping_address TEXT,
            reference_number VARCHAR(100) NOT NULL UNIQUE,
            order_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            status VARCHAR(50) DEFAULT 'pending',
            FOREIGN KEY (user_id) REFERENCES users(user_id)
        )";
        $conn->query($create_orders);
    } else {
        // Add shipping_address column if it doesn't exist (for existing tables)
        $col_check = $conn->query("SHOW COLUMNS FROM orders LIKE 'shipping_address'");
        if ($col_check->num_rows == 0) {
            $conn->query("ALTER TABLE orders ADD COLUMN shipping_address TEXT AFTER payment_method");
        }
    }
    
    // Check if order_items table exists
    $table_check_items = $conn->query("SHOW TABLES LIKE 'order_items'");
    if ($table_check_items->num_rows == 0) {
        $create_items = "CREATE TABLE IF NOT EXISTS order_items (
            item_id INT AUTO_INCREMENT PRIMARY KEY,
            order_id INT NOT NULL,
            product_id INT NOT NULL,
            quantity INT NOT NULL,
            price DECIMAL(10,2) NOT NULL,
            FOREIGN KEY (order_id) REFERENCES orders(order_id) ON DELETE CASCADE
        )";
        $conn->query($create_items);
    }
    
    // Insert order including shipping_address
    $stmt = $conn->prepare("INSERT INTO orders (user_id, subtotal, tax, shipping, total_amount, payment_method, shipping_address, reference_number, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending')");
    $stmt->bind_param("iddddsss", $user_id, $subtotal, $tax, $shipping, $total, $payment_method, $shipping_address, $reference_number);
    
    if ($stmt->execute()) {
        $order_id = $conn->insert_id;
        
        // Insert order items
        $stmt_items = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
        
        foreach ($purchased_items as $item) {
            $stmt_items->bind_param("iiid", $order_id, $item['id'], $item['quantity'], $item['price']);
            $stmt_items->execute();
            
            // Remove from cart
            foreach ($_SESSION['cart'] as $key => $cart_item) {
                if ($cart_item['id'] == $item['id']) {
                    unset($_SESSION['cart'][$key]);
                }
            }
        }
        $stmt_items->close();
        
        // Reindex cart array
        $_SESSION['cart'] = array_values($_SESSION['cart']);
        unset($_SESSION['pending_checkout']);
        
        // Prepare order data for email (including address)
        $order_data = [
            'order_id' => $order_id,
            'subtotal' => $subtotal,
            'tax' => $tax,
            'shipping' => $shipping,
            'total' => $total,
            'payment_method' => $payment_method,
            'shipping_address' => $shipping_address
        ];
        
        // Send email confirmation
        $email_sent = false;
        if (!empty($user_email)) {
            $email_sent = sendOrderConfirmation($user_email, $user_name, $order_data, $purchased_items, $reference_number, $order_id);
        }
        
        // Store receipt in session
        $_SESSION['receipt_data'] = [
            'order_id' => $order_id,
            'items' => $purchased_items,
            'subtotal' => $subtotal,
            'tax' => $tax,
            'shipping' => $shipping,
            'total' => $total,
            'payment_method' => $payment_method,
            'reference_number' => $reference_number,
            'date' => date('F j, Y, g:i a'),
            'email_sent' => $email_sent,
            'customer_email' => $user_email,
            'shipping_address' => $shipping_address
        ];
        
        $stmt->close();
        
        // Redirect to cart with success
        header("Location: cart.php?payment_success=1");
        exit();
    } else {
        // Insert failed
        $error_message = $stmt->error;
        $stmt->close();
        die("Order creation failed: " . $error_message);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>QR Code Payment - DYNA Shop</title>
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
        .email-notice { background: #e8f5e9; border-left: 4px solid #4caf50; padding: 10px; margin-top: 15px; font-size: 12px; border-radius: 5px; }
        .alert-message { padding: 15px 20px; border-radius: 10px; margin-bottom: 20px; }
        .alert-error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
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
                
                <?php if (isset($error_message)): ?>
                <div class="alert-message alert-error">
                    <i class="fa fa-exclamation-circle"></i> <?php echo htmlspecialchars($error_message); ?>
                </div>
                <?php endif; ?>
                
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
                
                <!-- Display Shipping Address (read-only, for confirmation) -->
                <div class="form-group">
                    <label for="shipping_address">Shipping Address</label>
                    <div class="form-control" style="background: #f8f9fa;"><?php echo nl2br(htmlspecialchars($shipping_address)); ?></div>
                    <small class="text-muted">This is the address you provided during checkout.</small>
                </div>
                
                <?php if (!empty($user_email)): ?>
                <div class="email-notice">
                    <i class="fa fa-envelope"></i> A confirmation email will be sent to: <strong><?php echo htmlspecialchars($user_email); ?></strong>
                </div>
                <?php endif; ?>
                
                <form method="POST">
                    <button type="submit" name="confirm_payment" class="btn-pay">
                        <i class="fa fa-check-circle"></i> Confirm Payment & Get Receipt
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

<?php
$conn->close();
?>