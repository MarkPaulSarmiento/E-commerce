<?php
session_start();

// Database connection
$conn = new mysqli('localhost', 'root', '', 'dyna_shop');

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || !isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Handle order cancellation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_order'])) {
    $order_id = intval($_POST['order_id']);
    
    // Verify order belongs to user and is still pending
    $check_stmt = $conn->prepare("SELECT order_id FROM orders WHERE order_id = ? AND user_id = ? AND status = 'pending'");
    $check_stmt->bind_param("ii", $order_id, $user_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows > 0) {
        $update_stmt = $conn->prepare("UPDATE orders SET status = 'cancelled' WHERE order_id = ?");
        $update_stmt->bind_param("i", $order_id);
        
        if ($update_stmt->execute()) {
            $success_message = "Order #$order_id has been cancelled successfully.";
        } else {
            $error_message = "Failed to cancel order. Please try again.";
        }
        $update_stmt->close();
    } else {
        $error_message = "Order cannot be cancelled. It may have already been shipped or doesn't exist.";
    }
    $check_stmt->close();
}

// Handle order completion confirmation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['complete_order'])) {
    $order_id = intval($_POST['order_id']);
    
    // Verify order belongs to user and is shipped
    $check_stmt = $conn->prepare("SELECT order_id FROM orders WHERE order_id = ? AND user_id = ? AND status = 'shipped'");
    $check_stmt->bind_param("ii", $order_id, $user_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows > 0) {
        $update_stmt = $conn->prepare("UPDATE orders SET status = 'completed' WHERE order_id = ?");
        $update_stmt->bind_param("i", $order_id);
        
        if ($update_stmt->execute()) {
            $success_message = "Order #$order_id has been marked as received. Thank you!";
        } else {
            $error_message = "Failed to update order status. Please try again.";
        }
        $update_stmt->close();
    } else {
        $error_message = "Order cannot be marked as received. It may not be shipped yet.";
    }
    $check_stmt->close();
}

// Get user's orders
$query = "
    SELECT o.* 
    FROM orders o 
    WHERE o.user_id = ? 
    ORDER BY o.order_date DESC
";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$orders_result = $stmt->get_result();
$stmt->close();

// Get order counts for dashboard
$counts = [];
$counts['pending'] = $conn->query("SELECT COUNT(*) as total FROM orders WHERE user_id = $user_id AND status = 'pending'")->fetch_assoc()['total'];
$counts['shipped'] = $conn->query("SELECT COUNT(*) as total FROM orders WHERE user_id = $user_id AND status = 'shipped'")->fetch_assoc()['total'];
$counts['completed'] = $conn->query("SELECT COUNT(*) as total FROM orders WHERE user_id = $user_id AND status = 'completed'")->fetch_assoc()['total'];
$counts['cancelled'] = $conn->query("SELECT COUNT(*) as total FROM orders WHERE user_id = $user_id AND status = 'cancelled'")->fetch_assoc()['total'];
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@100;200;300;400;500;600;700;800;900&display=swap" rel="stylesheet">

    <title>My Orders - DYNA Shop</title>

    <!-- Bootstrap core CSS -->
    <link href="vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">

    <!-- Additional CSS Files -->
    <link rel="stylesheet" href="assets/css/fontawesome.css">
    <link rel="stylesheet" href="assets/css/templatemo-lugx-gaming.css">
    <link rel="stylesheet" href="assets/css/owl.css">
    <link rel="stylesheet" href="assets/css/animate.css">
    <link rel="stylesheet" href="https://unpkg.com/swiper@7/swiper-bundle.min.css"/>
    <link rel="stylesheet" href="assets/css/admin.css">
    
    <link rel="stylesheet" href="assets/css/my_orders.css">
</head>

<body>
    <!-- ***** Preloader Start ***** -->
    <div id="js-preloader" class="js-preloader">
        <div class="preloader-inner">
            <span class="dot"></span>
            <div class="dots">
                <span></span>
                <span></span>
                <span></span>
            </div>
        </div>
    </div>
    <!-- ***** Preloader End ***** -->

    <!-- ***** Header Area Start ***** -->
    <header class="header-area header-sticky">
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <nav class="main-nav">
                        <a href="home.php" class="logo">
                            <img src="assets/images/logo.png" alt="" style="width: 158px;">
                        </a>
                        <ul class="nav">
                            <li><a href="home.php">Home</a></li>
                            <li><a href="shop_page1.php">Our Shop</a></li>
                            <li><a href="my_orders.php" class="active">My Orders</a></li>
                            <li><a href="user-profile.php">My Profile</a></li>
                            <li><a href="contact.php">Contact Us</a></li>
                            <li><a href="cart.php">Cart</a></li>
                            <li><a href="logout.php">Logout</a></li>
                        </ul>
                        <a class='menu-trigger'>
                            <span>Menu</span>
                        </a>
                    </nav>
                </div>
            </div>
        </div>
    </header>
    <!-- ***** Header Area End ***** -->

    <div class="page-heading-1 header-text">
        <div class="container">
            <div class="row">
                <div class="col-lg-12">
                    <h3>My Orders</h3>
                    <span class="breadcrumb"><a href="home.php">Home</a> > My Orders</span>
                </div>
            </div>
        </div>
    </div>

    <div class="orders-wrapper">
        <div class="container">
            <!-- Alert Messages -->
            <?php if (isset($success_message)): ?>
                <div class="alert-message alert-success">
                    <i class="fa fa-check-circle"></i> <?php echo $success_message; ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($error_message)): ?>
                <div class="alert-message alert-error">
                    <i class="fa fa-exclamation-circle"></i> <?php echo $error_message; ?>
                </div>
            <?php endif; ?>
            
            <!-- Order Statistics -->
            <div class="order-stats">
                <div class="stat-box">
                    <i class="fa fa-clock"></i>
                    <h3><?php echo $counts['pending']; ?></h3>
                    <p>Pending Orders</p>
                </div>
                <div class="stat-box">
                    <i class="fa fa-truck"></i>
                    <h3><?php echo $counts['shipped']; ?></h3>
                    <p>Shipped Orders</p>
                </div>
                <div class="stat-box">
                    <i class="fa fa-check-circle"></i>
                    <h3><?php echo $counts['completed']; ?></h3>
                    <p>Completed Orders</p>
                </div>
                <div class="stat-box">
                    <i class="fa fa-times-circle"></i>
                    <h3><?php echo $counts['cancelled']; ?></h3>
                    <p>Cancelled Orders</p>
                </div>
            </div>
            
            <!-- Orders List -->
            <?php if ($orders_result && $orders_result->num_rows > 0): ?>
                <?php while($order = $orders_result->fetch_assoc()): ?>
                    <div class="order-card">
                        <div class="order-header">
                            <div>
                                <span class="order-id">Order #<?php echo $order['order_id']; ?></span>
                            </div>
                            <div class="order-date">
                                <i class="fa fa-calendar"></i> <?php echo date('F d, Y h:i A', strtotime($order['order_date'])); ?>
                            </div>
                        </div>
                        
                        <div class="order-details">
                            <div class="detail-item">
                                <span class="detail-label">Total Amount</span>
                                <span class="detail-value">₱<?php echo number_format($order['total_amount'], 2); ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Payment Method</span>
                                <span class="detail-value"><?php echo ucfirst(str_replace('_', ' ', $order['payment_method'])); ?></span>
                                <?php if ($order['reference_number']): ?>
                                    <small class="detail-label">Ref: <?php echo htmlspecialchars($order['reference_number']); ?></small>
                                <?php endif; ?>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Order Breakdown</span>
                                <span class="detail-value">
                                    Subtotal: ₱<?php echo number_format($order['subtotal'], 2); ?><br>
                                    Tax: ₱<?php echo number_format($order['tax'], 2); ?><br>
                                    Shipping: ₱<?php echo number_format($order['shipping'], 2); ?>
                                </span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Status</span>
                                <span class="status-badge status-<?php echo $order['status']; ?>">
                                    <?php echo ucfirst($order['status']); ?>
                                </span>
                                <?php if ($order['tracking_number'] && $order['status'] == 'shipped'): ?>
                                    <small class="detail-label">Tracking: <?php echo htmlspecialchars($order['tracking_number']); ?></small>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="order-actions">
                            <?php if ($order['status'] == 'pending'): ?>
                                <button type="button" class="btn-cancel" onclick="openCancelModal(<?php echo $order['order_id']; ?>, <?php echo $order['total_amount']; ?>)">
                                    <i class="fa fa-times"></i> Cancel Order
                                </button>
                            <?php elseif ($order['status'] == 'shipped'): ?>
                                <button type="button" class="btn-complete" onclick="openCompleteModal(<?php echo $order['order_id']; ?>, <?php echo $order['total_amount']; ?>)">
                                    <i class="fa fa-check-circle"></i> Confirm Received
                                </button>
                            <?php endif; ?>
                            
                            <a href="order-details.php?id=<?php echo $order['order_id']; ?>" class="btn-view">
                                <i class="fa fa-eye"></i> View Details
                            </a>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="empty-orders">
                    <i class="fa fa-shopping-bag"></i>
                    <h3>No Orders Yet</h3>
                    <p>You haven't placed any orders yet. Start shopping now!</p>
                    <a href="shop_page1.php" class="btn-shop">Start Shopping</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Cancel Order Modal -->
    <div id="cancelModal" class="action-modal">
        <div class="action-modal-content">
            <div class="action-modal-header">
                <i class="fa fa-exclamation-triangle cancel-icon"></i>
                <h3>Cancel Order</h3>
                <p>Are you sure you want to cancel this order?</p>
            </div>
            <div class="action-modal-body">
                <div class="order-info">
                    <p><strong>Order #<span id="cancelOrderId"></span></strong></p>
                    <p>Total: <strong>₱<span id="cancelTotalAmount"></span></strong></p>
                </div>
                <div class="warning-text">
                    <i class="fa fa-info-circle"></i> This action cannot be undone. Once cancelled, your order will be permanently cancelled.
                </div>
            </div>
            <form method="POST" id="cancelForm">
                <input type="hidden" name="order_id" id="cancelOrderIdInput">
                <div class="action-modal-buttons">
                    <button type="button" class="modal-cancel-btn" onclick="closeCancelModal()">
                        <i class="fa fa-times"></i> Go Back
                    </button>
                    <button type="submit" name="cancel_order" class="modal-confirm-cancel">
                        <i class="fa fa-check"></i> Yes, Cancel Order
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Confirm Received Modal -->
    <div id="completeModal" class="action-modal">
        <div class="action-modal-content">
            <div class="action-modal-header">
                <i class="fa fa-check-circle complete-icon"></i>
                <h3>Confirm Received</h3>
                <p>Confirm that you have received this order?</p>
            </div>
            <div class="action-modal-body">
                <div class="order-info">
                    <p><strong>Order #<span id="completeOrderId"></span></strong></p>
                    <p>Total: <strong>₱<span id="completeTotalAmount"></span></strong></p>
                </div>
                <div class="success-text">
                    <i class="fa fa-info-circle"></i> By confirming, you acknowledge that you have received your order in good condition.
                </div>
            </div>
            <form method="POST" id="completeForm">
                <input type="hidden" name="order_id" id="completeOrderIdInput">
                <div class="action-modal-buttons">
                    <button type="button" class="modal-cancel-btn" onclick="closeCompleteModal()">
                        <i class="fa fa-times"></i> Not Yet
                    </button>
                    <button type="submit" name="complete_order" class="modal-confirm-complete">
                        <i class="fa fa-check"></i> Yes, Confirm Received
                    </button>
                </div>
            </form>
        </div>
    </div>

    <footer>
        <div class="container">
            <div class="col-lg-12">
                <p>Copyright © 2024 DYNA Shop. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <!-- Scripts -->
    <script src="vendor/jquery/jquery.min.js"></script>
    <script src="vendor/bootstrap/js/bootstrap.min.js"></script>
    <script src="assets/js/isotope.min.js"></script>
    <script src="assets/js/owl-carousel.js"></script>
    <script src="assets/js/counter.js"></script>
    <script src="assets/js/custom.js"></script>
    
    <script>
        // Cancel Modal Functions
        function openCancelModal(orderId, totalAmount) {
            document.getElementById('cancelOrderId').textContent = orderId;
            document.getElementById('cancelOrderIdInput').value = orderId;
            document.getElementById('cancelTotalAmount').textContent = parseFloat(totalAmount).toFixed(2);
            document.getElementById('cancelModal').style.display = 'block';
        }
        
        function closeCancelModal() {
            document.getElementById('cancelModal').style.display = 'none';
        }
        
        // Complete Modal Functions
        function openCompleteModal(orderId, totalAmount) {
            document.getElementById('completeOrderId').textContent = orderId;
            document.getElementById('completeOrderIdInput').value = orderId;
            document.getElementById('completeTotalAmount').textContent = parseFloat(totalAmount).toFixed(2);
            document.getElementById('completeModal').style.display = 'block';
        }
        
        function closeCompleteModal() {
            document.getElementById('completeModal').style.display = 'none';
        }
        
        // Close modals when clicking outside
        window.onclick = function(event) {
            var cancelModal = document.getElementById('cancelModal');
            var completeModal = document.getElementById('completeModal');
            if (event.target == cancelModal) {
                closeCancelModal();
            }
            if (event.target == completeModal) {
                closeCompleteModal();
            }
        }
    </script>
</body>

</html>

<?php
$conn->close();
?>