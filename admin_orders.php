<?php
session_start();

// Database connection
$conn = new mysqli('localhost', 'root', '', 'dyna_shop');

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if user is logged in and is admin
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['user_type'] !== 'admin') {
    header("Location: index.php");
    exit();
}

// Verify admin session is still valid using admin_sessions table
$check_stmt = $conn->prepare("SELECT is_active FROM admin_sessions WHERE session_token = ? AND admin_id = ? AND is_active = 1");
$check_stmt->bind_param("si", $_SESSION['session_token'], $_SESSION['user_id']);
$check_stmt->execute();
$check_result = $check_stmt->get_result();

if ($check_result->num_rows === 0) {
    session_destroy();
    header("Location: index.php");
    exit();
}
$check_stmt->close();

// Function to send email notification
function sendOrderStatusEmail($to, $username, $order_id, $status, $total_amount, $tracking_number = '') {
    $subject = "Order #$order_id Status Update - DYNA Shop";
    
    // Email content based on status
    if ($status == 'shipped') {
        $status_text = "SHIPPED";
        $status_color = "#3498db";
        $message = "
            <p>Great news! Your order has been shipped!</p>
            <p><strong>Tracking Number:</strong> $tracking_number</p>
            <p>You can track your package using the tracking number above.</p>
            <p>Estimated delivery time: 3-5 business days.</p>
            <p>Once you receive your order, please mark it as received in your account.</p>
        ";
    } elseif ($status == 'cancelled') {
        $status_text = "CANCELLED";
        $status_color = "#e74c3c";
        $message = "
            <p>We regret to inform you that your order has been cancelled.</p>
            <p>If you have already made a payment, it will be refunded within 3-5 business days.</p>
            <p>If you have any questions, please contact our customer support.</p>
        ";
    } else {
        $status_text = strtoupper($status);
        $status_color = "#e75e8d";
        $message = "<p>Your order status has been updated to: " . ucfirst($status) . "</p>";
    }
    
    // HTML Email Template
    $html = '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>' . $subject . '</title>
        <style>
            body {
                font-family: "Poppins", Arial, sans-serif;
                background-color: #f7f7f7;
                margin: 0;
                padding: 0;
            }
            .email-container {
                max-width: 600px;
                margin: 0 auto;
                background-color: #ffffff;
                border-radius: 15px;
                overflow: hidden;
                box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            }
            .email-header {
                background: linear-gradient(105deg, #0e1b2e 0%, #1a2a3f 100%);
                padding: 30px;
                text-align: center;
            }
            .email-header img {
                max-width: 150px;
                margin-bottom: 15px;
            }
            .email-header h2 {
                color: #fff;
                margin: 0;
                font-size: 24px;
            }
            .email-body {
                padding: 30px;
            }
            .status-badge {
                display: inline-block;
                padding: 8px 20px;
                border-radius: 25px;
                font-size: 14px;
                font-weight: 600;
                text-transform: uppercase;
                background-color: ' . $status_color . ';
                color: #fff;
                margin: 15px 0;
            }
            .order-details {
                background-color: #f8f9fa;
                border-radius: 10px;
                padding: 15px;
                margin: 20px 0;
            }
            .order-details table {
                width: 100%;
                border-collapse: collapse;
            }
            .order-details tr {
                border-bottom: 1px solid #e0e0e0;
            }
            .order-details td {
                padding: 8px 0;
            }
            .order-details td:last-child {
                text-align: right;
                font-weight: 600;
            }
            .tracking-number {
                background-color: #fff3e0;
                padding: 10px;
                border-radius: 8px;
                margin: 10px 0;
                font-family: monospace;
                font-size: 16px;
                text-align: center;
            }
            .btn {
                display: inline-block;
                padding: 12px 25px;
                background-color: #e75e8d;
                color: #fff;
                text-decoration: none;
                border-radius: 25px;
                margin-top: 20px;
                font-weight: 500;
            }
            .btn:hover {
                background-color: #c4456b;
            }
            .email-footer {
                background-color: #f7f7f7;
                padding: 20px;
                text-align: center;
                font-size: 12px;
                color: #7a7a7a;
            }
        </style>
    </head>
    <body style="margin: 0; padding: 20px; background-color: #f7f7f7;">
        <div class="email-container">
            <div class="email-header">
                <img src="assets/images/logo.png" alt="DYNA Shop">
                <h2>Order Status Update</h2>
            </div>
            <div class="email-body">
                <h3>Hello ' . htmlspecialchars($username) . ',</h3>
                ' . $message . '
                
                <div class="status-badge">' . $status_text . '</div>
                
                <div class="order-details">
                    <h4 style="margin-bottom: 15px;">Order Summary</h4>
                     <table>
                         <tr>
                             <td>Order Number:</td>
                             <td><strong>#' . $order_id . '</strong></td>
                         </tr>
                         <tr>
                             <td>Total Amount:</td>
                             <td><strong>₱' . number_format($total_amount, 2) . '</strong></td>
                         </tr>
                         <tr>
                             <td>Status:</td>
                             <td><strong>' . ucfirst($status) . '</strong></td>
                         </tr>
                     </table>
                    ' . ($tracking_number ? '<div class="tracking-number"><strong>Tracking Number:</strong> ' . $tracking_number . '</div>' : '') . '
                </div>
                
                <p>To view your full order details, please click the button below:</p>
                <a href="order-details.php?id=' . $order_id . '" class="btn">View Order Details</a>
                
                <p style="margin-top: 25px;">Thank you for shopping with DYNA Shop!</p>
                <p>Best regards,<br><strong>DYNA Shop Team</strong></p>
            </div>
            <div class="email-footer">
                <p>&copy; 2024 DYNA Shop. All rights reserved.</p>
                <p>This email was sent to ' . $to . ' regarding your order status update.</p>
            </div>
        </div>
    </body>
    </html>
    ';
    
    // Plain text fallback
    $text = "Hello $username,\n\n";
    $text .= "Your order #$order_id status has been updated to: " . strtoupper($status) . "\n\n";
    $text .= "Order Total: ₱" . number_format($total_amount, 2) . "\n";
    if ($tracking_number) {
        $text .= "Tracking Number: $tracking_number\n";
    }
    $text .= "\nThank you for shopping with DYNA Shop!\n";
    
    // Headers for HTML email
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "From: DYNA Shop <noreply@dyna-shop.com>\r\n";
    $headers .= "Reply-To: support@dyna-shop.com\r\n";
    
    // Send ONLY ONE email
    return mail($to, $subject, $html, $headers);
}

// Handle order status update with CSRF protection and duplicate prevention
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && isset($_POST['order_id'])) {
    
    // Generate CSRF token if not exists
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error_message = "Invalid security token. Please refresh the page and try again.";
    } else {
        $order_id = intval($_POST['order_id']);
        $action = $_POST['action'];
        $status = '';
        $tracking_number = isset($_POST['tracking_number']) ? trim($_POST['tracking_number']) : '';
        
        if ($action === 'ship') {
            $status = 'shipped';
        } elseif ($action === 'decline') {
            $status = 'cancelled';
        }
        
        if ($status) {
            // First, check the current order status to prevent duplicate updates
            $check_current_stmt = $conn->prepare("SELECT status, tracking_number FROM orders WHERE order_id = ?");
            $check_current_stmt->bind_param("i", $order_id);
            $check_current_stmt->execute();
            $current_order = $check_current_stmt->get_result()->fetch_assoc();
            $check_current_stmt->close();
            
            if ($current_order) {
                // Check if the order is already in the target status
                if ($current_order['status'] == $status) {
                    $error_message = "Order #$order_id is already " . ($status == 'shipped' ? 'shipped' : 'cancelled') . ". No action taken.";
                } 
                // For shipped orders, check if tracking number already exists
                elseif ($status == 'shipped' && !empty($current_order['tracking_number'])) {
                    $error_message = "Order #$order_id already has a tracking number. No action taken.";
                }
                // Check if order is still pending (can only ship or decline pending orders)
                elseif ($current_order['status'] != 'pending') {
                    $error_message = "Order #$order_id cannot be " . ($status == 'shipped' ? 'shipped' : 'cancelled') . " because it is already " . $current_order['status'] . ".";
                } else {
                    // Get order and user details for email
                    $order_stmt = $conn->prepare("
                        SELECT o.*, u.username, u.email 
                        FROM orders o 
                        JOIN users u ON o.user_id = u.user_id 
                        WHERE o.order_id = ?
                    ");
                    $order_stmt->bind_param("i", $order_id);
                    $order_stmt->execute();
                    $order_details = $order_stmt->get_result()->fetch_assoc();
                    $order_stmt->close();
                    
                    if ($order_details) {
                        // Start transaction to ensure data integrity
                        $conn->begin_transaction();
                        
                        try {
                            // Update order status and tracking number
                            if ($status == 'shipped' && $tracking_number) {
                                $update_stmt = $conn->prepare("UPDATE orders SET status = ?, tracking_number = ? WHERE order_id = ? AND status = 'pending'");
                                $update_stmt->bind_param("ssi", $status, $tracking_number, $order_id);
                            } else {
                                $update_stmt = $conn->prepare("UPDATE orders SET status = ? WHERE order_id = ? AND status = 'pending'");
                                $update_stmt->bind_param("si", $status, $order_id);
                            }
                            
                            if ($update_stmt->execute() && $update_stmt->affected_rows > 0) {
                                // Send ONLY ONE email notification
                                $email_sent = sendOrderStatusEmail(
                                    $order_details['email'],
                                    $order_details['username'],
                                    $order_id,
                                    $status,
                                    $order_details['total_amount'],
                                    $tracking_number
                                );
                                
                                if ($email_sent) {
                                    $conn->commit();
                                    $success_message = "Order #$order_id has been " . 
                                        ($action === 'ship' ? 'shipped' : 'declined') . 
                                        " successfully! An email notification has been sent to the customer.";
                                } else {
                                    // Rollback if email fails
                                    $conn->rollback();
                                    $success_message = "Order #$order_id status updated successfully, but email notification could not be sent.";
                                }
                            } elseif ($update_stmt->affected_rows == 0) {
                                $conn->rollback();
                                $error_message = "Order #$order_id could not be updated. It may have already been processed by another admin.";
                            } else {
                                throw new Exception("Failed to update order status.");
                            }
                            $update_stmt->close();
                        } catch (Exception $e) {
                            $conn->rollback();
                            $error_message = $e->getMessage();
                        }
                    } else {
                        $error_message = "Order not found.";
                    }
                }
            } else {
                $error_message = "Order not found.";
            }
        }
    }
    
    // Regenerate CSRF token after form submission to prevent resubmission
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Filter by status
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Build query
$query = "
    SELECT o.*, u.username, u.email 
    FROM orders o 
    JOIN users u ON o.user_id = u.user_id 
    WHERE 1=1
";

if ($status_filter !== 'all') {
    $query .= " AND o.status = '" . $conn->real_escape_string($status_filter) . "'";
}

if ($search) {
    $query .= " AND (o.order_id LIKE '%$search%' OR u.username LIKE '%$search%' OR u.email LIKE '%$search%')";
}

$query .= " ORDER BY o.order_date DESC";

$orders_result = $conn->query($query);

// Get order counts for badges
$counts = [];
$counts['all'] = $conn->query("SELECT COUNT(*) as total FROM orders")->fetch_assoc()['total'];
$counts['pending'] = $conn->query("SELECT COUNT(*) as total FROM orders WHERE status = 'pending'")->fetch_assoc()['total'];
$counts['shipped'] = $conn->query("SELECT COUNT(*) as total FROM orders WHERE status = 'shipped'")->fetch_assoc()['total'];
$counts['completed'] = $conn->query("SELECT COUNT(*) as total FROM orders WHERE status = 'completed'")->fetch_assoc()['total'];
$counts['cancelled'] = $conn->query("SELECT COUNT(*) as total FROM orders WHERE status = 'cancelled'")->fetch_assoc()['total'];

// Generate CSRF token for forms
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@100;200;300;400;500;600;700;800;900&display=swap" rel="stylesheet">

    <title>Admin Orders - Dyna Shop</title>

    <!-- Bootstrap core CSS -->
    <link href="vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">

    <!-- Additional CSS Files -->
    <link rel="stylesheet" href="assets/css/fontawesome.css">
    <link rel="stylesheet" href="assets/css/templatemo-lugx-gaming.css">
    <link rel="stylesheet" href="assets/css/owl.css">
    <link rel="stylesheet" href="assets/css/animate.css">
    <link rel="stylesheet" href="https://unpkg.com/swiper@7/swiper-bundle.min.css"/>
    
    <!-- Admin Dashboard CSS -->
    <link rel="stylesheet" href="assets/css/admin.css">
    
    <!-- Admin Orders CSS -->
    <link rel="stylesheet" href="assets/css/admin-orders.css">
    
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
                        <a href="admin_dashboard.php" class="logo">
                            <img src="assets/images/logo.png" alt="" style="width: 158px;">
                        </a>
                        <ul class="nav">
                            <li><a href="admin_dashboard.php">Dashboard</a></li>
                            <li><a href="admin_orders.php" class="active">Orders</a></li>
                            <li><a href="admin_users.php">Users</a></li>
                            <li><a href="admin_products.php">Products</a></li>
                            <li><a href="admin_logout.php">Logout</a></li>
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
                    <h3>Manage Orders</h3>
                    <span class="breadcrumb"><a href="admin_dashboard.php">Dashboard</a> > Orders</span>
                </div>
            </div>
        </div>
    </div>

    <div class="admin-wrapper">
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
            
            <!-- Filter Buttons -->
            <div class="filter-buttons">
                <a href="?status=all" class="filter-btn <?php echo $status_filter == 'all' ? 'active' : ''; ?>">
                    All Orders <span class="badge"><?php echo $counts['all']; ?></span>
                </a>
                <a href="?status=pending" class="filter-btn <?php echo $status_filter == 'pending' ? 'active' : ''; ?>">
                    Pending <span class="badge"><?php echo $counts['pending']; ?></span>
                </a>
                <a href="?status=shipped" class="filter-btn <?php echo $status_filter == 'shipped' ? 'active' : ''; ?>">
                    Shipped <span class="badge"><?php echo $counts['shipped']; ?></span>
                </a>
                <a href="?status=completed" class="filter-btn <?php echo $status_filter == 'completed' ? 'active' : ''; ?>">
                    Completed <span class="badge"><?php echo $counts['completed']; ?></span>
                </a>
                <a href="?status=cancelled" class="filter-btn <?php echo $status_filter == 'cancelled' ? 'active' : ''; ?>">
                    Cancelled <span class="badge"><?php echo $counts['cancelled']; ?></span>
                </a>
            </div>
            
            <!-- Search Box -->
            <div class="search-box">
                <form method="GET" action="">
                    <input type="hidden" name="status" value="<?php echo $status_filter; ?>">
                    <input type="text" name="search" placeholder="Search by Order ID, Customer, or Email..." value="<?php echo htmlspecialchars($search); ?>">
                    <button type="submit"><i class="fa fa-search"></i> Search</button>
                    <?php if ($search): ?>
                        <a href="?status=<?php echo $status_filter; ?>" class="btn-link" style="margin-left: 10px;">Clear</a>
                    <?php endif; ?>
                </form>
            </div>
            
            <!-- Orders Table -->
            <div class="admin-section">
                <div class="section-header">
                    <h2><i class="fa fa-shopping-cart"></i> Orders List</h2>
                </div>
                <div class="table-responsive">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Customer</th>
                                <th>Payment Method</th>
                                <th>Total Amount</th>
                                <th>Shipping Address</th>
                                <th>Status</th>
                                <th>Tracking #</th>
                                <th>Order Date</th>
                                <th>Actions</th>
                             </tr>
                        </thead>
                        <tbody>
                            <?php if ($orders_result && $orders_result->num_rows > 0): ?>
                                <?php while($order = $orders_result->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <a href="admin_order_detail.php?id=<?php echo $order['order_id']; ?>" class="order-details-link">
                                            #<?php echo $order['order_id']; ?>
                                        </a>
                                    </td>
                                    <td>
                                        <?php echo htmlspecialchars($order['username']); ?>
                                        <div class="order-summary"><?php echo htmlspecialchars($order['email']); ?></div>
                                    </td>
                                    <td class="payment-method">
                                        <?php 
                                        $method = ucfirst(str_replace('_', ' ', $order['payment_method']));
                                        echo $method;
                                        if ($order['reference_number']) {
                                            echo '<div class="order-summary">Ref: ' . htmlspecialchars($order['reference_number']) . '</div>';
                                        }
                                        ?>
                                    </td>
                                    <td>₱<?php echo number_format($order['total_amount'], 2); ?></td>
                                    <td>
                                        <div class="order-summary"><?php echo nl2br(htmlspecialchars($order['shipping_address'])); ?></div>
                                    </td>
                                    <td>
                                        <span class="status-badge status-<?php echo $order['status']; ?>">
                                            <?php echo ucfirst($order['status']); ?>
                                        </span>
                                        <?php if ($order['status'] == 'shipped'): ?>
                                            <div class="note-text">Awaiting customer confirmation</div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($order['tracking_number']): ?>
                                            <span class="tracking-number-display">
                                                <i class="fa fa-barcode"></i> <?php echo htmlspecialchars($order['tracking_number']); ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="order-summary">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo date('M d, Y H:i', strtotime($order['order_date'])); ?></td>
                                    <td>
                                        <div class="action-buttons">
                                            <?php if ($order['status'] == 'pending'): ?>
                                                <button type="button" class="btn-sm btn-ship" onclick="openShipModal(<?php echo $order['order_id']; ?>)">
                                                    <i class="fa fa-truck"></i> Ship
                                                </button>
                                                <button type="button" class="btn-sm btn-decline" onclick="openDeclineModal(<?php echo $order['order_id']; ?>, <?php echo $order['total_amount']; ?>, '<?php echo addslashes($order['username']); ?>')">
                                                    <i class="fa fa-times"></i> Decline
                                                </button>
                                            <?php elseif ($order['status'] == 'completed'): ?>
                                                <span class="order-summary">Order completed by customer</span>
                                            <?php elseif ($order['status'] == 'cancelled'): ?>
                                                <span class="order-summary">Order cancelled</span>
                                            <?php endif; ?>
                                            
                                            <a href="order-details.php?id=<?php echo $order['order_id']; ?>" class="btn-sm btn-primary">
                                                <i class="fa fa-eye"></i> View
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" style="text-align: center; padding: 40px;">
                                        <i class="fa fa-inbox" style="font-size: 48px; color: #ccc;"></i>
                                        <p style="margin-top: 10px;">No orders found.</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Ship Modal -->
    <div id="shipModal" class="modal">
        <div class="modal-content">
            <h3><i class="fa fa-truck"></i> Mark Order as Shipped</h3>
            <p>Please enter the tracking number for this order:</p>
            <form method="POST" id="shipForm" onsubmit="disableSubmitButton(this)">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                <input type="hidden" name="order_id" id="shipOrderId">
                <input type="hidden" name="action" value="ship">
                <input type="text" name="tracking_number" id="trackingNumber" placeholder="Enter tracking number (e.g., TRK-123456789)" required>
                <div class="modal-buttons">
                    <button type="button" class="close-modal" onclick="closeShipModal()">Cancel</button>
                    <button type="submit" class="confirm-ship" id="shipSubmitBtn">Confirm Shipment</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Decline Modal -->
    <div id="declineModal" class="decline-modal">
        <div class="decline-modal-content">
            <div class="decline-modal-header">
                <i class="fa fa-exclamation-triangle"></i>
                <h3>Decline Order</h3>
                <p>Are you sure you want to decline this order?</p>
            </div>
            <div class="decline-modal-body">
                <div class="decline-order-details">
                    <p><strong>Order #<span id="declineOrderId"></span></strong></p>
                    <p>Customer: <strong id="declineCustomerName"></strong></p>
                    <p>Total: <strong>₱<span id="declineTotalAmount"></span></strong></p>
                </div>
                <p style="margin-top: 15px; color: #e74c3c; font-size: 13px;">
                    <i class="fa fa-info-circle"></i> This action cannot be undone. The customer will receive a cancellation email.
                </p>
            </div>
            <form method="POST" id="declineForm" onsubmit="disableSubmitButton(this)">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                <input type="hidden" name="order_id" id="declineOrderIdInput">
                <input type="hidden" name="action" value="decline">
                <div class="decline-modal-buttons">
                    <button type="button" class="decline-cancel-btn" onclick="closeDeclineModal()">
                        <i class="fa fa-times"></i> Cancel
                    </button>
                    <button type="submit" class="decline-confirm-btn" id="declineSubmitBtn">
                        <i class="fa fa-check"></i> Yes, Decline Order
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
        function openShipModal(orderId) {
            document.getElementById('shipOrderId').value = orderId;
            document.getElementById('shipModal').style.display = 'block';
        }
        
        function closeShipModal() {
            document.getElementById('shipModal').style.display = 'none';
            document.getElementById('trackingNumber').value = '';
            // Re-enable submit button when modal closes
            const shipBtn = document.getElementById('shipSubmitBtn');
            if (shipBtn) shipBtn.disabled = false;
        }
        
        function openDeclineModal(orderId, totalAmount, customerName) {
            document.getElementById('declineOrderId').textContent = orderId;
            document.getElementById('declineOrderIdInput').value = orderId;
            document.getElementById('declineTotalAmount').textContent = parseFloat(totalAmount).toFixed(2);
            document.getElementById('declineCustomerName').textContent = customerName;
            document.getElementById('declineModal').style.display = 'block';
        }
        
        function closeDeclineModal() {
            document.getElementById('declineModal').style.display = 'none';
            // Re-enable submit button when modal closes
            const declineBtn = document.getElementById('declineSubmitBtn');
            if (declineBtn) declineBtn.disabled = false;
        }
        
        // Disable submit button after click to prevent multiple submissions
        function disableSubmitButton(form) {
            const buttons = form.querySelectorAll('button[type="submit"]');
            buttons.forEach(button => {
                button.disabled = true;
                button.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Processing...';
            });
            return true;
        }
        
        // Close modals when clicking outside
        window.onclick = function(event) {
            var shipModal = document.getElementById('shipModal');
            var declineModal = document.getElementById('declineModal');
            if (event.target == shipModal) {
                closeShipModal();
            }
            if (event.target == declineModal) {
                closeDeclineModal();
            }
        }
        
        // Prevent double form submission
        document.addEventListener('submit', function(e) {
            const forms = document.querySelectorAll('form');
            forms.forEach(form => {
                if (form === e.target) {
                    const submitButtons = form.querySelectorAll('button[type="submit"]');
                    if (submitButtons.length && submitButtons[0].hasAttribute('data-submitted')) {
                        e.preventDefault();
                        return false;
                    }
                    submitButtons.forEach(btn => btn.setAttribute('data-submitted', 'true'));
                }
            });
        });
    </script>
</body>

</html>

<?php
$conn->close();
?>