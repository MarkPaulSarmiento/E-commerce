<?php
session_start();

// Database connection
$conn = new mysqli('localhost', 'root', '', 'dyna_shop');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check admin session (same as before)
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['user_type'] !== 'admin') {
    header("Location: index.php");
    exit();
}
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

// Get product ID
$product_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($product_id <= 0) {
    header("Location: admin_products.php");
    exit();
}

// Fetch product details
$stmt = $conn->prepare("SELECT * FROM products WHERE product_id = ?");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$product) {
    header("Location: admin_products.php");
    exit();
}

// Handle AJAX update request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_update'])) {
    // Verify CSRF token (simple – you can reuse the same session token)
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        echo json_encode(['success' => false, 'message' => 'Invalid security token.']);
        exit;
    }

    $name = trim($_POST['name']);
    $category = trim($_POST['category']);
    $original_price = floatval($_POST['original_price']);
    $discounted_price = isset($_POST['discounted_price']) && $_POST['discounted_price'] !== '' ? floatval($_POST['discounted_price']) : $original_price;
    $image_url = trim($_POST['image_url']);
    $filter_tags = trim($_POST['filter_tags']);

    if (empty($name) || empty($category) || $original_price <= 0) {
        echo json_encode(['success' => false, 'message' => 'Please fill in all required fields.']);
        exit;
    }

    $update = $conn->prepare("UPDATE products SET name = ?, category = ?, original_price = ?, discounted_price = ?, image_url = ?, filter_tags = ? WHERE product_id = ?");
    $update->bind_param("ssddssi", $name, $category, $original_price, $discounted_price, $image_url, $filter_tags, $product_id);

    if ($update->execute()) {
        if ($update->affected_rows > 0) {
            echo json_encode(['success' => true, 'message' => 'Product updated successfully!']);
        } else {
            echo json_encode(['success' => true, 'message' => 'No changes were made.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $update->error]);
    }
    $update->close();
    exit;
}

// CSRF token for the form
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
    <title>Edit Product - DYNA Shop</title>
    <link href="vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/fontawesome.css">
    <link rel="stylesheet" href="assets/css/templatemo-lugx-gaming.css">
    <link rel="stylesheet" href="assets/css/owl.css">
    <link rel="stylesheet" href="assets/css/animate.css">
    <link rel="stylesheet" href="https://unpkg.com/swiper@7/swiper-bundle.min.css"/>
    <link rel="stylesheet" href="assets/css/admin.css">

    <style>
        .form-container {
            background: #fff;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 40px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
        }
        .form-container h3 {
            margin-bottom: 20px;
            font-size: 24px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            font-weight: 600;
            display: block;
            margin-bottom: 5px;
        }
        .form-group input {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
        }
        .btn-submit {
            background: #e75e8d;
            color: #fff;
            border: none;
            padding: 10px 25px;
            border-radius: 25px;
            cursor: pointer;
            transition: all 0.3s;
        }
        .btn-submit:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        .btn-cancel {
            background: #6c757d;
            color: #fff;
            border: none;
            padding: 10px 25px;
            border-radius: 25px;
            text-decoration: none;
            display: inline-block;
            margin-left: 10px;
        }
        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            color: #e75e8d;
            text-decoration: none;
            font-weight: 500;
        }
        /* Toast notification */
        .toast-notification {
            position: fixed;
            top: 20px;
            right: 20px;
            background: #28a745;
            color: white;
            padding: 12px 20px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            z-index: 9999;
            animation: slideIn 0.3s ease;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .toast-notification.error {
            background: #dc3545;
        }
        .toast-notification i {
            font-size: 18px;
        }
        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        .fade-out {
            animation: fadeOut 0.5s forwards;
        }
        @keyframes fadeOut {
            to { opacity: 0; visibility: hidden; }
        }
    </style>
</head>

<body>
    <div id="js-preloader" class="js-preloader"><div class="preloader-inner"><span class="dot"></span><div class="dots"><span></span><span></span><span></span></div></div></div>

    <header class="header-area header-sticky">
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <nav class="main-nav">
                        <a href="admin_dashboard.php" class="logo"><img src="assets/images/logo.png" alt="" style="width: 158px;"></a>
                        <ul class="nav">
                            <li><a href="admin_dashboard.php">Dashboard</a></li>
                            <li><a href="admin_orders.php">Orders</a></li>
                            <li><a href="admin_users.php">Users</a></li>
                            <li><a href="admin_products.php" class="active">Products</a></li>
                            <li><a href="admin_logout.php">Logout</a></li>
                        </ul>
                        <a class='menu-trigger'><span>Menu</span></a>
                    </nav>
                </div>
            </div>
        </div>
    </header>

    <div class="page-heading-1 header-text">
        <div class="container"><div class="row"><div class="col-lg-12">
            <h3>Edit Product</h3>
            <span class="breadcrumb"><a href="admin_products.php">Products</a> > Edit Product</span>
        </div></div></div>
    </div>

    <div class="admin-wrapper">
        <div class="container">
            <a href="admin_products.php" class="back-link"><i class="fa fa-arrow-left"></i> Back to Products</a>

            <div class="form-container">
                <h3><i class="fa fa-edit"></i> Edit Product Details</h3>
                <form id="editProductForm">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Product Name *</label>
                                <input type="text" name="name" id="name" value="<?php echo htmlspecialchars($product['name']); ?>" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Category *</label>
                                <input type="text" name="category" id="category" value="<?php echo htmlspecialchars($product['category']); ?>" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Original Price (₱) *</label>
                                <input type="number" step="0.01" name="original_price" id="original_price" value="<?php echo $product['original_price']; ?>" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Discounted Price (₱)</label>
                                <input type="number" step="0.01" name="discounted_price" id="discounted_price" value="<?php echo $product['discounted_price']; ?>">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Filter Tags</label>
                                <input type="text" name="filter_tags" id="filter_tags" value="<?php echo htmlspecialchars($product['filter_tags']); ?>" placeholder="e.g., casual, str, adv">
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="form-group">
                                <label>Image URL *</label>
                                <input type="text" name="image_url" id="image_url" value="<?php echo htmlspecialchars($product['image_url']); ?>" required>
                            </div>
                        </div>
                    </div>
                    <div class="mt-3">
                        <button type="submit" class="btn-submit" id="updateBtn">
                            <i class="fa fa-save"></i> Update Product
                        </button>
                        <a href="admin_products.php" class="btn-cancel"><i class="fa fa-times"></i> Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <footer><div class="container"><div class="col-lg-12"><p>Copyright © 2024 DYNA Shop. All rights reserved.</p></div></div></footer>

    <script src="vendor/jquery/jquery.min.js"></script>
    <script src="vendor/bootstrap/js/bootstrap.min.js"></script>
    <script src="assets/js/isotope.min.js"></script>
    <script src="assets/js/owl-carousel.js"></script>
    <script src="assets/js/counter.js"></script>
    <script src="assets/js/custom.js"></script>

    <script>
        const form = document.getElementById('editProductForm');
        const updateBtn = document.getElementById('updateBtn');

        function showToast(message, isError = false) {
            const toast = document.createElement('div');
            toast.className = 'toast-notification' + (isError ? ' error' : '');
            toast.innerHTML = `<i class="fa ${isError ? 'fa-exclamation-circle' : 'fa-check-circle'}"></i> ${message}`;
            document.body.appendChild(toast);
            setTimeout(() => {
                toast.classList.add('fade-out');
                setTimeout(() => toast.remove(), 500);
            }, 3000);
        }

        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            if (updateBtn.disabled) return;

            updateBtn.disabled = true;
            updateBtn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Updating...';

            // Get form data
            const formData = new FormData(form);
            formData.append('ajax_update', '1');

            try {
                const response = await fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                });
                const data = await response.json();

                if (data.success) {
                    showToast(data.message, false);
                    // Update the input values in case they were changed on server (though we sent them)
                    // Optional: you could also refetch the product data if needed
                } else {
                    showToast(data.message, true);
                }
            } catch (err) {
                showToast('Network error. Please try again.', true);
            } finally {
                updateBtn.disabled = false;
                updateBtn.innerHTML = '<i class="fa fa-save"></i> Update Product';
            }
        });
    </script>
</body>
</html>
<?php $conn->close(); ?>