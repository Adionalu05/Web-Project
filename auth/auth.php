<?php
session_start();
require_once __DIR__ . '/../config/database.php';

class Auth {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    /**
     * Register a new user
     */
    public function register($username, $email, $password, $confirm_password) {
        // Validation
        if (empty($username) || empty($email) || empty($password)) {
            return ['success' => false, 'error' => 'All fields are required'];
        }

        if ($password !== $confirm_password) {
            return ['success' => false, 'error' => 'Passwords do not match'];
        }

        if (strlen($password) < 6) {
            return ['success' => false, 'error' => 'Password must be at least 6 characters'];
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'error' => 'Invalid email address'];
        }

        if (strlen($username) < 3) {
            return ['success' => false, 'error' => 'Username must be at least 3 characters'];
        }

        try {
            // Check if user already exists
            $stmt = $this->db->prepare("SELECT id FROM users WHERE username = :username OR email = :email");
            $stmt->execute(['username' => $username, 'email' => $email]);
            if ($stmt->fetch()) {
                return ['success' => false, 'error' => 'Username or email already exists'];
            }

            // Hash password
            $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

            // Create user
            $stmt = $this->db->prepare("
                INSERT INTO users (username, email, password) 
                VALUES (:username, :email, :password)
            ");
            $stmt->execute([
                'username' => $username,
                'email' => $email,
                'password' => $hashedPassword
            ]);

            return ['success' => true, 'message' => 'Registration successful. Please login.'];
        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Database error: ' . $e->getMessage()];
        }
    }

    /**
     * Login user
     */
    public function login($username, $password) {
        if (empty($username) || empty($password)) {
            return ['success' => false, 'error' => 'Username and password are required'];
        }

        try {
            $stmt = $this->db->prepare("SELECT id, password FROM users WHERE username = :username");
            $stmt->execute(['username' => $username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user || !password_verify($password, $user['password'])) {
                return ['success' => false, 'error' => 'Invalid username or password'];
            }

            // Create session
            $sessionToken = bin2hex(random_bytes(32));
            $expiresAt = date('Y-m-d H:i:s', strtotime('+' . SESSION_TIMEOUT . ' seconds'));

            $stmt = $this->db->prepare("
                INSERT INTO sessions (user_id, session_token, expires_at)
                VALUES (:user_id, :session_token, :expires_at)
            ");
            $stmt->execute([
                'user_id' => $user['id'],
                'session_token' => $sessionToken,
                'expires_at' => $expiresAt
            ]);

            $_SESSION['user_id'] = $user['id'];
            $_SESSION['session_token'] = $sessionToken;

            return ['success' => true, 'message' => 'Login successful'];
        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Database error: ' . $e->getMessage()];
        }
    }

    /**
     * Logout user
     */
    public function logout() {
        if (isset($_SESSION['session_token'])) {
            try {
                $stmt = $this->db->prepare("DELETE FROM sessions WHERE session_token = :token");
                $stmt->execute(['token' => $_SESSION['session_token']]);
            } catch (Exception $e) {
                // Log error
            }
        }
        session_destroy();
        return ['success' => true, 'message' => 'Logged out successfully'];
    }

    /**
     * Check if user is authenticated
     */
    public function isAuthenticated() {
        if (!isset($_SESSION['user_id']) || !isset($_SESSION['session_token'])) {
            return false;
        }

        try {
            $stmt = $this->db->prepare("
                SELECT id FROM sessions 
                WHERE user_id = :user_id 
                AND session_token = :token 
                AND expires_at > datetime('now')
            ");
            $stmt->execute([
                'user_id' => $_SESSION['user_id'],
                'token' => $_SESSION['session_token']
            ]);

            if ($stmt->fetch()) {
                return true;
            }
        } catch (Exception $e) {
            // Log error
        }

        return false;
    }

    /**
     * Get current user ID
     */
    public function getCurrentUserId() {
        return $_SESSION['user_id'] ?? null;
    }

    /**
     * Get current user details
     */
    public function getCurrentUser() {
        if (!$this->isAuthenticated()) {
            return null;
        }

        try {
            $stmt = $this->db->prepare("SELECT id, username, email FROM users WHERE id = :id");
            $stmt->execute(['id' => $this->getCurrentUserId()]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return null;
        }
    }
}

$auth = new Auth($db);

// Redirect to login if not authenticated (exception for specific pages)
$public_pages = ['login.php', 'register.php', 'index.php'];
$current_page = basename($_SERVER['PHP_SELF']);

if (!in_array($current_page, $public_pages) && !$auth->isAuthenticated()) {
    header('Location: login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}
?>
