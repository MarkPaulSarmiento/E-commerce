<?php
session_start();

// Database connection
$conn = new mysqli('localhost', 'root', '', 'dyna_shop');

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if user is already logged in with valid session
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true && isset($_SESSION['session_token'])) {
    // Check if it's admin or regular user
    if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin') {
        // Verify admin session using admin_sessions table
        $check_stmt = $conn->prepare("SELECT is_active FROM admin_sessions WHERE session_token = ? AND admin_id = ? AND is_active = 1");
        $check_stmt->bind_param("si", $_SESSION['session_token'], $_SESSION['user_id']);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            // Admin session is valid, redirect to admin dashboard
            $check_stmt->close();
            $conn->close();
            header("Location: admin_dashboard.php");
            exit();
        } else {
            // Session is no longer active, destroy it
            session_destroy();
        }
        $check_stmt->close();
    } else {
        // Verify regular user session
        $check_stmt = $conn->prepare("SELECT is_active, logout_time FROM user_sessions WHERE session_token = ? AND user_id = ?");
        $check_stmt->bind_param("si", $_SESSION['session_token'], $_SESSION['user_id']);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            $session_data = $check_result->fetch_assoc();
            // Check if session is active (is_active = 1) and logout_time is NULL
            if ($session_data['is_active'] == 1 && is_null($session_data['logout_time'])) {
                // Session is still valid, redirect to home page
                $check_stmt->close();
                $conn->close();
                header("Location: home.php");
                exit();
            } else {
                // Session is no longer active, destroy it
                session_destroy();
            }
        } else {
            // Session token not found, destroy session
            session_destroy();
        }
        $check_stmt->close();
    }
}

$error = '';
$success = '';

// Handle Login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    
    if (empty($username) || empty($password)) {
        $error = "Please enter both username and password";
    } else {
        // First, check if it's an admin user
        // CHANGED: Using 'password' instead of 'password_hash' for plain text storage
        $admin_stmt = $conn->prepare("SELECT admin_id, username, email, password, full_name, role, is_active FROM admin_users WHERE username = ? AND is_active = 1");
        $admin_stmt->bind_param("s", $username);
        $admin_stmt->execute();
        $admin_result = $admin_stmt->get_result();
        
        if ($admin_result->num_rows === 1) {
            // Admin user found
            $admin = $admin_result->fetch_assoc();
            
            // CHANGED: Direct password comparison (plain text) for testing
            if ($password === $admin['password']) {
                // First, deactivate any existing active sessions for this admin
                $deactivate_stmt = $conn->prepare("UPDATE admin_sessions SET is_active = 0, logout_time = NOW() WHERE admin_id = ? AND is_active = 1");
                $deactivate_stmt->bind_param("i", $admin['admin_id']);
                $deactivate_stmt->execute();
                $deactivate_stmt->close();
                
                // Generate new session token
                $session_token = bin2hex(random_bytes(32));
                
                // Store new admin session in admin_sessions table
                $insert_session = $conn->prepare("INSERT INTO admin_sessions (admin_id, session_token, is_active, login_time) VALUES (?, ?, 1, NOW())");
                $insert_session->bind_param("is", $admin['admin_id'], $session_token);
                
                if ($insert_session->execute()) {
                    // Set session variables for admin
                    $_SESSION['user_id'] = $admin['admin_id'];
                    $_SESSION['username'] = $admin['username'];
                    $_SESSION['full_name'] = $admin['full_name'];
                    $_SESSION['user_type'] = 'admin';
                    $_SESSION['admin_role'] = $admin['role'];
                    $_SESSION['session_token'] = $session_token;
                    $_SESSION['logged_in'] = true;
                    
                    // Update last login time in admin_users
                    $update_login = $conn->prepare("UPDATE admin_users SET last_login = NOW() WHERE admin_id = ?");
                    $update_login->bind_param("i", $admin['admin_id']);
                    $update_login->execute();
                    $update_login->close();
                    
                    // Redirect to admin dashboard
                    header("Location: admin_dashboard.php");
                    exit();
                } else {
                    $error = "Login failed. Please try again.";
                }
                $insert_session->close();
            } else {
                $error = "Invalid username or password";
            }
        } else {
            // Check regular user (keeping password_hash for regular users)
            $stmt = $conn->prepare("SELECT user_id, username, password_hash FROM users WHERE username = ?");
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 1) {
                $user = $result->fetch_assoc();
                
                // Verify password using password_verify for regular users
                if (password_verify($password, $user['password_hash'])) {
                    // First, deactivate any existing active sessions for this user
                    $deactivate_stmt = $conn->prepare("UPDATE user_sessions SET is_active = 0, logout_time = NOW() WHERE user_id = ? AND is_active = 1");
                    $deactivate_stmt->bind_param("i", $user['user_id']);
                    $deactivate_stmt->execute();
                    $deactivate_stmt->close();
                    
                    // Generate new session token
                    $session_token = bin2hex(random_bytes(32));
                    
                    // Store new session in user_sessions table
                    $insert_session = $conn->prepare("INSERT INTO user_sessions (user_id, session_token, is_active, login_time) VALUES (?, ?, 1, NOW())");
                    $insert_session->bind_param("is", $user['user_id'], $session_token);
                    
                    if ($insert_session->execute()) {
                        // Set session variables for regular user
                        $_SESSION['user_id'] = $user['user_id'];
                        $_SESSION['username'] = $user['username'];
                        $_SESSION['user_type'] = 'user';
                        $_SESSION['session_token'] = $session_token;
                        $_SESSION['logged_in'] = true;
                        
                        // Redirect to home page
                        header("Location: home.php");
                        exit();
                    } else {
                        $error = "Login failed. Please try again.";
                    }
                    $insert_session->close();
                } else {
                    $error = "Invalid username or password";
                }
            } else {
                $error = "Invalid username or password";
            }
            $stmt->close();
        }
        $admin_stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@100;200;300;400;500;600;700;800;900&display=swap" rel="stylesheet">

    <title>Dyna Shop - Login</title>

    <!-- Bootstrap core CSS -->
    <link href="vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">

    <!-- Additional CSS Files -->
    <link rel="stylesheet" href="assets/css/fontawesome.css">
    <link rel="stylesheet" href="assets/css/templatemo-lugx-gaming.css">
    <link rel="stylesheet" href="assets/css/owl.css">
    <link rel="stylesheet" href="assets/css/animate.css">
    <link rel="stylesheet" href="https://unpkg.com/swiper@7/swiper-bundle.min.css" />
    
    <!-- Custom Login CSS -->
    <link rel="stylesheet" href="assets/css/login.css">
    <link rel="stylesheet" href="assets/css/admin.css">

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
                        <a href="index.php" class="logo">
                            <img src="assets/images/Logo.png" alt="" style="width: 158px;">
                        </a>
                        <!-- ***** Logo End ***** -->
                        <!-- ***** Menu Start ***** -->
                        <ul class="nav">
                            <li><a href="index.php" class="active">Login</a></li>
                            <li><a href="register.php">Register</a></li>
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

    <div class="page-heading-1 header-text">
        <div class="container">
            <div class="row">
                <div class="col-lg-12">
                    <h3>Login to Your Account</h3>
                    <span class="breadcrumb"><a href="index.php">Home</a> > Login</span>
                </div>
            </div>
        </div>
    </div>

    <div class="login-page">
        <div class="container">
            <div class="row">
                <div class="col-lg-6 offset-lg-3">
                    <div class="login-container">
                        <div class="login-header">
                            <h2>Welcome Back!</h2>
                            <p>Please login to access your account</p>
                        </div>
                        
                        <?php if ($error): ?>
                            <div class="alert-message alert-error">
                                <i class="fa fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($success): ?>
                            <div class="alert-message alert-success">
                                <i class="fa fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
                            </div>
                        <?php endif; ?>
                        
                        <form id="login-form" method="POST" action="">
                            <div class="form-group">
                                <label for="username">Username</label>
                                <input type="text" id="username" name="username" placeholder="Enter your username" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="password">Password</label>
                                <div class="password-wrapper">
                                    <input type="password" id="password" name="password" placeholder="Enter your password" required>
                                    <button type="button" class="toggle-password" onclick="togglePassword()">
                                        <i class="fa fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <div class="form-options">
                                <label class="checkbox-label">
                                    <input type="checkbox" name="remember" id="remember">
                                    <span>Remember me</span>
                                </label>
                                <a href="#" class="forgot-password">Forgot Password?</a>
                            </div>
                            
                            <button type="submit" name="login" class="login-btn">Login Now</button>
                        </form>
                        
                        <div class="register-link">
                            <p>Don't have an account? <a href="register.php">Create an account</a></p>
                        </div>
                        
                        <!-- Updated Admin Login Info -->
                        <div style="margin-top: 20px; padding: 10px; background: #f8f9fa; border-radius: 5px; text-align: center; font-size: 12px; color: #6c757d;">
                            <i class="fa fa-shield-alt"></i> Admin Login: admin / Mspp1414!
                        </div>
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

    <!-- Scripts -->
    <script src="vendor/jquery/jquery.min.js"></script>
    <script src="vendor/bootstrap/js/bootstrap.min.js"></script>
    <script src="assets/js/isotope.min.js"></script>
    <script src="assets/js/owl-carousel.js"></script>
    <script src="assets/js/counter.js"></script>
    <script src="assets/js/custom.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Custom Login JS -->
    <script src="assets/js/login.js"></script>
    
    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleBtn = document.querySelector('.toggle-password i');
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleBtn.classList.remove('fa-eye');
                toggleBtn.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                toggleBtn.classList.remove('fa-eye-slash');
                toggleBtn.classList.add('fa-eye');
            }
        }
    </script>

</body>

</html>

<?php
$conn->close();
?>