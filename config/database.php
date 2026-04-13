<?php
// Database Configuration
define('DB_PATH', __DIR__ . '/../data/documents.db');
define('DATA_DIR', __DIR__ . '/../data');

// Ensure data directory exists
if (!file_exists(DATA_DIR)) {
    mkdir(DATA_DIR, 0755, true);
}

// Connect to SQLite database
try {
    $db = new PDO('sqlite:' . DB_PATH);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Exception $e) {
    die('Database connection error: ' . $e->getMessage());
}

// Initialize database tables if they don't exist
function initializeDatabase($db) {
    // Users table
    $db->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT UNIQUE NOT NULL,
            email TEXT NOT NULL,
            email_hash TEXT UNIQUE NOT NULL,
            password TEXT NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ");

    // Ensure email_hash exists for older databases
    try {
        // SQLite does not allow UNIQUE in ALTER TABLE ADD COLUMN, so add the column first then create a unique index.
        $db->exec("ALTER TABLE users ADD COLUMN email_hash TEXT NOT NULL DEFAULT ''");
    } catch (Exception $e) {
        // ignore if column already exists
    }

    // Create a unique index for email_hash if it doesn't already exist
    try {
        $db->exec("CREATE UNIQUE INDEX IF NOT EXISTS idx_users_email_hash ON users(email_hash)");
    } catch (Exception $e) {
        // ignore if index already exists or cannot be created
    }

    // Backfill email_hash for existing rows (and encrypt email values)
    try {
        $stmt = $db->query("SELECT id, email, email_hash FROM users WHERE email_hash = '' OR email_hash IS NULL");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as $row) {
            $storedEmail = $row['email'];
            $plainEmail = $storedEmail;

            // If the email is already encrypted in the database, decrypt it first
            $decrypted = decryptValue($storedEmail);
            if ($decrypted !== false && $decrypted !== null && $decrypted !== '') {
                $plainEmail = $decrypted;
            }

            $emailHash = hash('sha256', strtolower(trim($plainEmail)));
            $encryptedEmail = encryptValue($plainEmail);

            $update = $db->prepare("UPDATE users SET email = :email, email_hash = :email_hash WHERE id = :id");
            $update->execute(['email' => $encryptedEmail, 'email_hash' => $emailHash, 'id' => $row['id']]);
        }
    } catch (Exception $e) {
        // ignore errors during migration step
    }


    // Categories table
    $db->exec("
        CREATE TABLE IF NOT EXISTS categories (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT UNIQUE NOT NULL,
            description TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ");

    // Documents table
    $db->exec("
        CREATE TABLE IF NOT EXISTS documents (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            title TEXT NOT NULL,
            category_id INTEGER,
            file_path TEXT NOT NULL,
            file_name TEXT NOT NULL,
            file_size INTEGER NOT NULL,
            file_format TEXT NOT NULL,
            description TEXT,
            uploaded_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
        )
    ");

    // Tags table
    $db->exec("
        CREATE TABLE IF NOT EXISTS tags (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT UNIQUE NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ");

    // Document-Tags junction table
    $db->exec("
        CREATE TABLE IF NOT EXISTS document_tags (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            document_id INTEGER NOT NULL,
            tag_id INTEGER NOT NULL,
            FOREIGN KEY (document_id) REFERENCES documents(id) ON DELETE CASCADE,
            FOREIGN KEY (tag_id) REFERENCES tags(id) ON DELETE CASCADE,
            UNIQUE(document_id, tag_id)
        )
    ");

    // Sessions table
    $db->exec("
        CREATE TABLE IF NOT EXISTS sessions (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            session_token TEXT UNIQUE NOT NULL,
            expires_at DATETIME NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )
    ");

    // Folders table
    $db->exec("
        CREATE TABLE IF NOT EXISTS folders (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            user_id INTEGER NOT NULL,
            parent_id INTEGER DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (parent_id) REFERENCES folders(id) ON DELETE SET NULL
        )
    ");

    // Shares table
    $db->exec("
        CREATE TABLE IF NOT EXISTS shares (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            document_id INTEGER NOT NULL,
            owner_id INTEGER NOT NULL,
            shared_with_user_id INTEGER NOT NULL,
            permission TEXT DEFAULT 'read',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            UNIQUE(document_id, shared_with_user_id),
            FOREIGN KEY (document_id) REFERENCES documents(id) ON DELETE CASCADE,
            FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (shared_with_user_id) REFERENCES users(id) ON DELETE CASCADE
        )
    ");

    // Password resets table
    $db->exec("
        CREATE TABLE IF NOT EXISTS password_resets (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            email_hash TEXT NOT NULL,
            token TEXT UNIQUE NOT NULL,
            expires_at DATETIME NOT NULL,
            used INTEGER DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )
    ");

    // Add folder_id column to documents if it doesn't exist
    try {
        $db->exec("ALTER TABLE documents ADD COLUMN folder_id INTEGER DEFAULT NULL");
    } catch (Exception $e) {
        // Column already exists
    }

    // Insert default categories
    $db->exec("
        INSERT OR IGNORE INTO categories (id, name, description) VALUES
        (1, 'Tickets', 'Support tickets and issues'),
        (2, 'Contracts', 'Contract documents'),
        (3, 'Reports', 'Report documents'),
        (4, 'Other', 'Miscellaneous documents')
    ");
}

// Initialize database on first load
initializeDatabase($db);

// Security configuration
define('MAX_FILE_SIZE', 10 * 1024 * 1024); // 10 MB
define('ALLOWED_FILE_TYPES', ['pdf', 'docx', 'doc', 'jpg', 'jpeg', 'png', 'txt', 'xlsx', 'xls']);
define('SESSION_TIMEOUT', 24 * 60 * 60); // 24 hours

// Claude API key for AI search (set via environment variable or replace empty string)
define('CLAUDE_API_KEY', getenv('CLAUDE_API_KEY') ?: '');

// Encryption settings (used for sensitive data at rest)
// In production, set ENCRYPTION_KEY via environment variable and keep it secret.
define('ENCRYPTION_KEY', getenv('ENCRYPTION_KEY') ?: 'change_me_to_a_random_secret');

define('ENCRYPTION_METHOD', 'AES-256-CBC');

define('ENCRYPTION_AVAILABLE', function_exists('openssl_encrypt') && function_exists('openssl_cipher_iv_length'));

define('ENCRYPTION_IV_LENGTH', ENCRYPTION_AVAILABLE ? openssl_cipher_iv_length(ENCRYPTION_METHOD) : 16);

function encryptValue($value) {
    if ($value === null || $value === '') {
        return $value;
    }

    // If OpenSSL is not available, fall back to storing cleartext
    if (!ENCRYPTION_AVAILABLE) {
        return $value;
    }

    $iv = openssl_random_pseudo_bytes(ENCRYPTION_IV_LENGTH);
    $encrypted = openssl_encrypt($value, ENCRYPTION_METHOD, ENCRYPTION_KEY, 0, $iv);
    return base64_encode($iv . $encrypted);
}

function decryptValue($value) {
    if ($value === null || $value === '') {
        return $value;
    }

    if (!ENCRYPTION_AVAILABLE) {
        return $value;
    }

    $decoded = base64_decode($value);
    $iv = substr($decoded, 0, ENCRYPTION_IV_LENGTH);
    $ciphertext = substr($decoded, ENCRYPTION_IV_LENGTH);

    $decrypted = openssl_decrypt($ciphertext, ENCRYPTION_METHOD, ENCRYPTION_KEY, 0, $iv);
    return $decrypted !== false ? $decrypted : $value;
}
?>
