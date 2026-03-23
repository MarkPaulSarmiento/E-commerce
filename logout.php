<?php
session_start();

// Database connection
$conn = new mysqli('localhost', 'root', '', 'dyna_shop');

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Update session in database if session token exists
if (isset($_SESSION['session_token']) && isset($_SESSION['user_id'])) {
    $stmt = $conn->prepare("UPDATE user_sessions SET logout_time = NOW(), is_active = 0 WHERE session_token = ? AND user_id = ? AND is_active = 1");
    $stmt->bind_param("si", $_SESSION['session_token'], $_SESSION['user_id']);
    
    if ($stmt->execute()) {
        // Successfully updated logout time
        if ($stmt->affected_rows > 0) {
            error_log("Logout time recorded for user_id: " . $_SESSION['user_id'] . ", session: " . $_SESSION['session_token']);
        } else {
            error_log("No active session found to update for user_id: " . $_SESSION['user_id']);
        }
    } else {
        error_log("Failed to update logout time: " . $stmt->error);
    }
    $stmt->close();
}

// Destroy all session data
$_SESSION = array();

// Delete session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time()-3600, '/');
}

// Destroy session
session_destroy();

// Close database connection
$conn->close();

// Redirect to login page
header("Location: index.php");
exit();
?>