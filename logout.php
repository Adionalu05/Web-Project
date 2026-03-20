<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/auth/auth.php';

if (!$auth->isAuthenticated()) {
    header('Location: login.php');
    exit;
}

$result = $auth->logout();

// Redirect to login page
header('Location: login.php?message=Logged out successfully');
exit;
?>
