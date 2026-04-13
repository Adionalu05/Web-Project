<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/auth/auth.php';

if ($auth->isAuthenticated()) {
    header('Location: dashboard.php');
    exit;
}

$token = trim($_GET['token'] ?? '');
$message = '';
$messageType = '';
$validToken = false;
$tokenRow = null;

// Validate token on every load
if ($token) {
    try {
        $stmt = $db->prepare("
            SELECT * FROM password_resets
            WHERE token = :token
              AND used = 0
              AND expires_at > datetime('now')
        ");
        $stmt->execute(['token' => $token]);
        $tokenRow = $stmt->fetch(PDO::FETCH_ASSOC);
        $validToken = (bool)$tokenRow;
    } catch (Exception $e) {
        $message = 'An error occurred. Please request a new reset link.';
        $messageType = 'error';
    }
}

if (!$token || (!$validToken && !$message)) {
    $message = 'This reset link is invalid or has expired. Please request a new one.';
    $messageType = 'error';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $validToken) {
    $password        = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if (strlen($password) < 6) {
        $message = 'Password must be at least 6 characters.';
        $messageType = 'error';
    } elseif ($password !== $confirmPassword) {
        $message = 'Passwords do not match.';
        $messageType = 'error';
    } else {
        try {
            $hashed = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $db->prepare("UPDATE users SET password = :password, updated_at = CURRENT_TIMESTAMP WHERE id = :id");
            $stmt->execute(['password' => $hashed, 'id' => $tokenRow['user_id']]);

            // Mark token as used
            $stmt = $db->prepare("UPDATE password_resets SET used = 1 WHERE id = :id");
            $stmt->execute(['id' => $tokenRow['id']]);

            header('Location: login.php?reset=1');
            exit;
        } catch (Exception $e) {
            $message = 'An error occurred. Please try again.';
            $messageType = 'error';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password – File Management System</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-box">
            <h1>Reset Password</h1>

            <?php if ($message): ?>
                <div class="alert alert-<?php echo $messageType; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <?php if ($validToken): ?>
                <form method="POST" action="">
                    <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">

                    <div class="form-group">
                        <label for="password">New Password</label>
                        <input type="password" id="password" name="password" required minlength="6">
                        <small>Minimum 6 characters</small>
                    </div>

                    <div class="form-group">
                        <label for="confirm_password">Confirm New Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" required>
                    </div>

                    <button type="submit" class="btn btn-primary" style="width:100%;">Set New Password</button>
                </form>
            <?php else: ?>
                <p class="auth-link"><a href="forgot_password.php">Request a new reset link</a></p>
            <?php endif; ?>

            <p class="auth-link"><a href="login.php">Back to Login</a></p>
        </div>
    </div>
</body>
</html>
