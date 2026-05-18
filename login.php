<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/auth/auth.php';

$message = '';
$messageType = '';

// Show success after password reset
if (isset($_GET['reset']) && $_GET['reset'] === '1') {
    $message = 'Password reset successfully. You can now log in with your new password.';
    $messageType = 'success';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    $result = $auth->login($username, $password);
    
    if ($result['success']) {
        $redirect = $_GET['redirect'] ?? 'dashboard.php';
        header('Location: ' . $redirect);
        exit;
    } else {
        $message = $result['error'];
        $messageType = 'error';
    }
}

// Redirect if already logged in
if ($auth->isAuthenticated()) {
    header('Location: dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - File Management System</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-box">
            <h1>Login</h1>
            
            <?php if ($message): ?>
                <div class="alert alert-<?php echo $messageType; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>
                
                <button type="submit" class="btn btn-primary">Login</button>
            </form>
            
            <p class="auth-link">
                Don't have an account? <a href="register.php">Register here</a>
            </p>
            <p class="auth-link">
                <a href="forgot_password.php">Forgot your password?</a>
            </p>
        </div>
    </div>
    <script src="js/theme.js"></script>
</body>
</html>
