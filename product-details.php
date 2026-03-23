<?php
// Start session
session_start();

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: index.php");
    exit();
}

// Database configuration
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'dyna_shop';

// Create connection
$conn = new mysqli($host, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Initialize cart if not exists
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Handle add to cart
if (isset($_POST['add_to_cart'])) {
    $product_id = $_POST['product_id'];
    $product_name = $_POST['product_name'];
    $product_price = $_POST['product_price'];
    $product_image = $_POST['product_image'];
    $quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 1;
    
    // Check if product already in cart
    $found = false;
    foreach ($_SESSION['cart'] as &$item) {
        if ($item['id'] == $product_id) {
            $item['quantity'] += $quantity;
            $found = true;
            break;
        }
    }
    
    if (!$found) {
        $_SESSION['cart'][] = [
            'id' => $product_id,
            'name' => $product_name,
            'price' => $product_price,
            'image' => $product_image,
            'quantity' => $quantity
        ];
    }
    
    // Set success message
    $_SESSION['cart_message'] = "Item added to cart successfully!";
    
    // Redirect to cart page instead of staying on product page
    header("Location: cart.php");
    exit();
}

// Get product ID from URL
$product_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Fetch product details
$product = null;
$related_products = [];

if ($product_id > 0) {
    // Get main product
    $product_sql = "SELECT * FROM products WHERE product_id = $product_id";
    $product_result = $conn->query($product_sql);
    
    if ($product_result && $product_result->num_rows > 0) {
        $product = $product_result->fetch_assoc();
        
        // Fetch related products (same category, excluding current product)
        $related_sql = "SELECT * FROM products WHERE category = '{$product['category']}' AND product_id != $product_id LIMIT 4";
        $related_result = $conn->query($related_sql);
        
        if ($related_result && $related_result->num_rows > 0) {
            while($row = $related_result->fetch_assoc()) {
                $related_products[] = $row;
            }
        }
    }
}

// If no product found, redirect to home page
if (!$product) {
    header("Location: home.php");
    exit();
}

// Get cart message if exists
$cart_message = isset($_SESSION['cart_message']) ? $_SESSION['cart_message'] : '';
unset($_SESSION['cart_message']);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@100;200;300;400;500;600;700;800;900&display=swap" rel="stylesheet">

    <title>Dyna Shop - <?php echo htmlspecialchars($product['name']); ?></title>

    <!-- Bootstrap core CSS -->
    <link href="vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">

    <!-- Additional CSS Files -->
    <link rel="stylesheet" href="assets/css/fontawesome.css">
    <link rel="stylesheet" href="assets/css/templatemo-lugx-gaming.css">
    <link rel="stylesheet" href="assets/css/owl.css">
    <link rel="stylesheet" href="assets/css/animate.css">
    <link rel="stylesheet" href="https://unpkg.com/swiper@7/swiper-bundle.min.css"/>
    
    <style>
    /* Add to Cart Button Styles */
    .quantity-input {
        width: 80px;
        text-align: center;
        border: 1px solid #e5e5e5;
        border-radius: 10px;
        padding: 8px;
        font-size: 14px;
        display: inline-block;
        margin-right: 10px;
    }
    
    .add-to-cart-btn {
        background: #ee626b;
        color: #fff;
        border: none;
        padding: 10px 25px;
        border-radius: 25px;
        font-weight: 600;
        transition: all 0.3s;
        cursor: pointer;
    }
    
    .add-to-cart-btn:hover {
        background: #d4555e;
        transform: translateY(-2px);
    }
    
    @media (max-width: 992px) {
        .main-nav .nav li {
            margin-left: 15px;
        }
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
                        <!-- ***** Logo Start ***** -->
                        <a href="home.php" class="logo">
                            <img src="assets/images/Logo.png" alt="" style="width: 158px;">
                        </a>
                        <!-- ***** Logo End ***** -->
                        <!-- ***** Menu Start ***** -->
                        <ul class="nav">
                            <li><a href="home.php">Home</a></li>
                            <li><a href="shop_page1.php">Our Shop</a></li>
                            <li><a href="contact.php">Contact Us</a></li>
                            <li><a href="cart.php">Cart</a></li>
                            <li><a href="logout.php">Logout</a></li>
                        </ul>   
                        <a class='menu-trigger'>
                            <span>Menu</span>
                        </a>
                        <!-- ***** Menu End ***** -->
                    </nav>
                </div>
            </div>
        </div>
    </header>
    <!-- ***** Header Area End ***** -->

    <div class="page-heading header-text">
        <div class="container">
            <div class="row">
                <div class="col-lg-12">
                    <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                    <span class="breadcrumb"><a href="home.php">Home</a> > <a href="shop_page1.php">Shop</a> > <?php echo htmlspecialchars($product['category']); ?></span>
                </div>
            </div>
        </div>
    </div>

    <div class="single-product section">
        <div class="container">
            <div class="row">
                <div class="col-lg-6">
                    <div class="left-image">
                        <img src="<?php echo $product['image_url']; ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                    </div>
                </div>
                <div class="col-lg-6 align-self-center">
                    <h4><?php echo htmlspecialchars($product['name']); ?></h4>
                    <?php if($product['discounted_price'] < $product['original_price']): ?>
                        <span class="price"><em>$<?php echo $product['original_price']; ?></em> $<?php echo $product['discounted_price']; ?></span>
                    <?php else: ?>
                        <span class="price">$<?php echo $product['original_price']; ?></span>
                    <?php endif; ?>
                    <p>Experience premium quality with our <?php echo htmlspecialchars($product['name']); ?>. Perfect for any occasion, this product combines style, comfort, and durability. Made with high-quality materials to ensure long-lasting wear. Shop now and elevate your wardrobe with this essential piece from DYNA Shop.</p>
                    
                    <!-- Add to Cart Form - Now redirects to cart.php -->
                    <form method="POST" action="">
                        <input type="hidden" name="add_to_cart" value="1">
                        <input type="hidden" name="product_id" value="<?php echo $product['product_id']; ?>">
                        <input type="hidden" name="product_name" value="<?php echo htmlspecialchars($product['name']); ?>">
                        <input type="hidden" name="product_price" value="<?php echo $product['discounted_price'] < $product['original_price'] ? $product['discounted_price'] : $product['original_price']; ?>">
                        <input type="hidden" name="product_image" value="<?php echo $product['image_url']; ?>">
                        <input type="number" name="quantity" class="quantity-input" value="1" min="1" max="99">
                        <button type="submit" class="add-to-cart-btn">
                            <i class="fa fa-shopping-bag"></i> ADD TO CART
                        </button>
                    </form>
                    
                    <ul style="margin-top: 30px;">
                        <li><span>Product ID:</span> <?php echo $product['product_id']; ?></li>
                        <li><span>Category:</span> <a href="shop_page1.php?category=<?php echo urlencode($product['category']); ?>"><?php echo $product['category']; ?></a></li>
                        <li><span>Tags:</span> <a href="#"><?php echo $product['filter_tags']; ?></a></li>
                    </ul>
                </div>
                <div class="col-lg-12">
                    <div class="sep"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="more-info">
        <div class="container">
            <div class="row">
                <div class="col-lg-12">
                    <div class="tabs-content">
                        <div class="row">
                            <div class="nav-wrapper">
                                <ul class="nav nav-tabs" role="tablist">
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link active" id="description-tab" data-bs-toggle="tab" data-bs-target="#description" type="button" role="tab" aria-controls="description" aria-selected="true">Description</button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link" id="reviews-tab" data-bs-toggle="tab" data-bs-target="#reviews" type="button" role="tab" aria-controls="reviews" aria-selected="false">Reviews (3)</button>
                                    </li>
                                </ul>
                            </div>              
                            <div class="tab-content" id="myTabContent">
                                <div class="tab-pane fade show active" id="description" role="tabpanel" aria-labelledby="description-tab">
                                    <p>Elevate your style with our premium <?php echo htmlspecialchars($product['name']); ?>. Crafted with attention to detail, this piece offers exceptional comfort and durability. Perfect for everyday wear or special occasions, it's designed to make you look and feel your best.</p>
                                    <br>
                                    <p>Key features include high-quality materials, expert craftsmanship, and versatile styling options. Whether you're dressing up for work or keeping it casual, this <?php echo htmlspecialchars($product['name']); ?> is the perfect choice. Available in various sizes to ensure the perfect fit.</p>
                                </div>
                                <div class="tab-pane fade" id="reviews" role="tabpanel" aria-labelledby="reviews-tab">
                                    <p><strong>★★★★★</strong> Amazing quality! The <?php echo htmlspecialchars($product['name']); ?> exceeded my expectations. Highly recommended! - Sarah M.</p>
                                    <br>
                                    <p><strong>★★★★☆</strong> Great product, fits perfectly and looks stylish. Fast shipping too! - John D.</p>
                                    <br>
                                    <p><strong>★★★★★</strong> Best purchase I've made this year. Will definitely buy again! - Emma L.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="section categories related-games">
        <div class="container">
            <div class="row">
                <div class="col-lg-6">
                    <div class="section-heading">
                        <h6><?php echo $product['category']; ?></h6>
                        <h2>Related Products</h2>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="main-button">
                        <a href="shop_page1.php">View All</a>
                    </div>
                </div>
                
                <?php if(count($related_products) > 0): ?>
                    <?php foreach($related_products as $related): ?>
                        <div class="col-lg col-sm-6 col-xs-12">
                            <div class="item">
                                <h4><?php echo $related['category']; ?></h4>
                                <div class="thumb">
                                    <a href="product-details.php?id=<?php echo $related['product_id']; ?>">
                                        <img src="<?php echo $related['image_url']; ?>" alt="<?php echo htmlspecialchars($related['name']); ?>">
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="col-12">
                        <p>No related products found.</p>
                    </div>
                <?php endif; ?>
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

    <!-- Scripts -->
    <script src="vendor/jquery/jquery.min.js"></script>
    <script src="vendor/bootstrap/js/bootstrap.min.js"></script>
    <script src="assets/js/isotope.min.js"></script>
    <script src="assets/js/owl-carousel.js"></script>
    <script src="assets/js/counter.js"></script>
    <script src="assets/js/custom.js"></script>

</body>

</html>

<?php
// Close database connection
$conn->close();
?>