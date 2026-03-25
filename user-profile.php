<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Database connection
$conn = new mysqli('localhost', 'root', '', 'dyna_shop');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch user details
$stmt = $conn->prepare("SELECT username, email, address, created_at FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    header("Location: logout.php");
    exit();
}
$user = $result->fetch_assoc();
$stmt->close();

// Generate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

// Handle AJAX update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    header('Content-Type: application/json');
    // Verify CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        echo json_encode(['success' => false, 'message' => 'Invalid security token.']);
        exit;
    }

    $new_username = trim($_POST['username']);
    $new_address = trim($_POST['address']);

    // Basic validation
    if (empty($new_username)) {
        echo json_encode(['success' => false, 'message' => 'Username cannot be empty.']);
        exit;
    }

    // Check if username already taken by another user
    $check_stmt = $conn->prepare("SELECT user_id FROM users WHERE username = ? AND user_id != ?");
    $check_stmt->bind_param("si", $new_username, $user_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    if ($check_result->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Username already taken.']);
        $check_stmt->close();
        exit;
    }
    $check_stmt->close();

    // Update user
    $update_stmt = $conn->prepare("UPDATE users SET username = ?, address = ? WHERE user_id = ?");
    $update_stmt->bind_param("ssi", $new_username, $new_address, $user_id);
    if ($update_stmt->execute()) {
        // Update session username if changed
        $_SESSION['username'] = $new_username;
        echo json_encode(['success' => true, 'message' => 'Profile updated successfully!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error.']);
    }
    $update_stmt->close();
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@100;200;300;400;500;600;700;800;900&display=swap" rel="stylesheet">

    <title>My Profile - DYNA Shop</title>

    <!-- Bootstrap core CSS -->
    <link href="vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">

    <!-- Additional CSS Files -->
    <link rel="stylesheet" href="assets/css/fontawesome.css">
    <link rel="stylesheet" href="assets/css/templatemo-lugx-gaming.css">
    <link rel="stylesheet" href="assets/css/owl.css">
    <link rel="stylesheet" href="assets/css/animate.css">
    <link rel="stylesheet" href="https://unpkg.com/swiper@7/swiper-bundle.min.css" />
    <link rel="stylesheet" href="assets/css/admin.css">

    <style>
        .profile-wrapper {
            background: #f7f7f7;
            padding: 60px 0;
        }
        .profile-card {
            background: #fff;
            border-radius: 23px;
            padding: 40px;
            box-shadow: 0 0 20px rgba(0,0,0,0.05);
            margin-bottom: 40px;
        }
        .profile-avatar {
            width: 100px;
            height: 100px;
            background: #ee626b;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 48px;
            color: #fff;
            margin: 0 auto 20px;
        }
        .profile-info {
            text-align: center;
            margin-bottom: 30px;
        }
        .profile-info h3 {
            font-size: 24px;
            margin-bottom: 5px;
        }
        .profile-info p {
            color: #7a7a7a;
            margin-bottom: 5px;
        }
        .info-row {
            display: flex;
            border-bottom: 1px solid #f0f0f0;
            padding: 15px 0;
        }
        .info-label {
            width: 150px;
            font-weight: 600;
            color: #1e1e1e;
        }
        .info-value {
            flex: 1;
            color: #4a4a4a;
        }
        .edit-form {
            background: #fff;
            border-radius: 23px;
            padding: 30px;
            box-shadow: 0 0 20px rgba(0,0,0,0.05);
        }
        .edit-form h4 {
            margin-bottom: 20px;
            font-size: 20px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            font-weight: 600;
            display: block;
            margin-bottom: 8px;
        }
        .form-group input, .form-group textarea {
            width: 100%;
            padding: 10px 15px;
            border: 1px solid #e5e5e5;
            border-radius: 15px;
            font-family: inherit;
        }
        .btn-save {
            background: #ee626b;
            color: #fff;
            border: none;
            padding: 12px 25px;
            border-radius: 25px;
            cursor: pointer;
            transition: all 0.3s;
        }
        .btn-save:hover {
            background: #d94c1a;
            transform: translateY(-2px);
        }
        .btn-save:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
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
        .text-muted {
            color: #7a7a7a;
        }
    </style>
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
                            <img src="assets/images/Logo.png" alt="" style="width: 158px;">
                        </a>
                        <ul class="nav">
                            <li><a href="home.php">Home</a></li>
                            <li><a href="shop_page1.php">Our Shop</a></li>
                            <li><a href="my_orders.php">My Orders</a></li>
                            <li><a href="user-profile.php" class="active">My Profile</a></li>
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
                    <h3>My Profile</h3>
                    <span class="breadcrumb"><a href="home.php">Home</a> > Profile</span>
                </div>
            </div>
        </div>
    </div>

    <div class="profile-wrapper">
        <div class="container">
            <div class="row">
                <div class="col-lg-8 offset-lg-2">
                    <!-- Profile Information Card -->
                    <div class="profile-card">
                        <div class="profile-avatar">
                            <i class="fa fa-user"></i>
                        </div>
                        <div class="profile-info">
                            <h3><?php echo htmlspecialchars($user['username']); ?></h3>
                            <p>Member since <?php echo date('F d, Y', strtotime($user['created_at'])); ?></p>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Email Address</div>
                            <div class="info-value"><?php echo htmlspecialchars($user['email']); ?></div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Shipping Address</div>
                            <div class="info-value">
                                <?php echo !empty($user['address']) ? nl2br(htmlspecialchars($user['address'])) : '<span class="text-muted">Not provided</span>'; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Edit Form -->
                    <div class="edit-form">
                        <h4><i class="fa fa-edit"></i> Edit Profile</h4>
                        <form id="profileForm">
                            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                            <div class="form-group">
                                <label for="username">Username</label>
                                <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="address">Shipping Address</label>
                                <textarea id="address" name="address" rows="3"><?php echo htmlspecialchars($user['address']); ?></textarea>
                            </div>
                            <div class="form-group">
                                <label>Email Address</label>
                                <input type="email" value="<?php echo htmlspecialchars($user['email']); ?>" disabled style="background:#f8f9fa;">
                                <small class="text-muted">Email cannot be changed.</small>
                            </div>
                            <button type="submit" class="btn-save" id="saveBtn">
                                <i class="fa fa-save"></i> Save Changes
                            </button>
                        </form>
                    </div>
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
    <script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/isotope.min.js"></script>
    <script src="assets/js/owl-carousel.js"></script>
    <script src="assets/js/counter.js"></script>
    <script src="assets/js/custom.js"></script>

    <script>
        $(document).ready(function() {
            $('#profileForm').on('submit', function(e) {
                e.preventDefault();
                const button = $('#saveBtn');
                if (button.prop('disabled')) return;

                button.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Saving...');

                const formData = new FormData(this);
                formData.append('update_profile', '1');

                $.ajax({
                    url: window.location.href,
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            // Update displayed info
                            $('.profile-info h3').text($('#username').val());
                            $('.info-value:last').html($('#address').val().replace(/\n/g, '<br>') || '<span class="text-muted">Not provided</span>');
                            showMessage(response.message, false);
                        } else {
                            showMessage(response.message, true);
                        }
                    },
                    error: function() {
                        showMessage('Network error. Please try again.', true);
                    },
                    complete: function() {
                        button.prop('disabled', false).html('<i class="fa fa-save"></i> Save Changes');
                    }
                });
            });

            function showMessage(msg, isError) {
                let bgColor = isError ? '#e74c3c' : '#ee626b';
                let icon = isError ? 'fa-exclamation-circle' : 'fa-check-circle';
                let div = $('<div class="notification" style="background:'+bgColor+';"><i class="fa '+icon+'"></i> '+msg+'</div>');
                $('body').append(div);
                setTimeout(() => div.fadeOut(500, function(){ $(this).remove(); }), 3000);
            }
        });
    </script>
</body>
</html>
<?php $conn->close(); ?>