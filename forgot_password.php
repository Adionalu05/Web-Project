<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/auth/auth.php';
require_once __DIR__ . '/auth/email.php';

// Redirect logged-in users away
if ($auth->isAuthenticated()) {
    header('Location: dashboard.php');
    exit;
}

$message = '';
$messageType = '';
$sent = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = 'Please enter a valid email address.';
        $messageType = 'error';
    } else {
        $emailHash = hash('sha256', strtolower($email));

        try {
            // Look up user by email hash (emails are stored encrypted)
            $stmt = $db->prepare("SELECT id FROM users WHERE email_hash = :email_hash");
            $stmt->execute(['email_hash' => $emailHash]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                // Invalidate any existing unused tokens for this user
                $stmt = $db->prepare("UPDATE password_resets SET used = 1 WHERE user_id = :uid AND used = 0");
                $stmt->execute(['uid' => $user['id']]);

                // Generate new token
                $token = bin2hex(random_bytes(32));
                $expiresAt = date('Y-m-d H:i:s', time() + 3600); // 1 hour

                $stmt = $db->prepare("
                    INSERT INTO password_resets (user_id, email_hash, token, expires_at)
                    VALUES (:user_id, :email_hash, :token, :expires_at)
                ");
                $stmt->execute([
                    'user_id'    => $user['id'],
                    'email_hash' => $emailHash,
                    'token'      => $token,
                    'expires_at' => $expiresAt,
                ]);

                $resetLink = 'http://' . $_SERVER['HTTP_HOST'] . '/reset_password.php?token=' . urlencode($token);
                $subject = 'Password Reset – File Management System';
                $body = "<p>Hello,</p>
                         <p>You requested a password reset. Click the link below to set a new password. This link expires in 1 hour.</p>
                         <p><a href=\"{$resetLink}\">{$resetLink}</a></p>
                         <p>If you did not request this, you can ignore this email.</p>";

                sendEmail($email, $subject, $body);
            }

            // Always show the same message to avoid user enumeration
            $sent = true;
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
    <title>Forgot Password – File Management System</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-box">
            <h1>Forgot Password</h1>

            <?php if ($sent): ?>
                <div class="alert alert-success">
                    If an account with that email exists, a password reset link has been sent. Check your inbox.
                </div>
                <p class="auth-link"><a href="login.php">Back to Login</a></p>

            <?php else: ?>
                <?php if ($message): ?>
                    <div class="alert alert-<?php echo $messageType; ?>">
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>

                <p style="margin-bottom:1rem;color:#666;font-size:.95rem;">
                    Enter your email address and we'll send you a link to reset your password.
                </p>

                <form method="POST" action="">
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" required
                               value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                    </div>
                    <button type="submit" class="btn btn-primary" style="width:100%;">Send Reset Link</button>
                </form>

                <p class="auth-link">
                    Remembered it? <a href="login.php">Back to Login</a>
                </p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
