<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/auth/auth.php';

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
    <title>File Management System</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .landing {
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 2rem;
        }

        .landing-content {
            max-width: 800px;
        }

        .landing h1 {
            font-size: 3rem;
            margin-bottom: 1rem;
        }

        .landing p {
            font-size: 1.2rem;
            margin-bottom: 2rem;
            opacity: 0.9;
        }

        .landing-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
        }

        .landing-buttons a {
            padding: 1rem 2rem;
            font-size: 1.1rem;
            border-radius: 4px;
            text-decoration: none;
            transition: transform 0.3s;
        }

        .landing-buttons a:hover {
            transform: translateY(-2px);
        }

        .btn-white {
            background: white;
            color: #667eea;
            font-weight: bold;
        }

        .btn-outline {
            border: 2px solid white;
            color: white;
        }

        .features {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            margin-top: 3rem;
            background: rgba(255,255,255,0.1);
            padding: 2rem;
            border-radius: 8px;
            backdrop-filter: blur(10px);
        }

        .feature {
            padding: 1rem;
        }

        .feature h3 {
            margin-bottom: 0.5rem;
            font-size: 1.3rem;
        }

        .feature p {
            font-size: 0.95rem;
            margin-bottom: 0;
        }
    </style>
</head>
<body>
    <div class="landing">
        <div class="landing-content">
            <h1>File Management System</h1>
            <p>Organize, store, and manage your documents efficiently with powerful searching and categorization tools.</p>

            <div class="landing-buttons">
                <a href="login.php" class="btn-white">Login</a>
                <a href="register.php" class="btn-outline">Register</a>
            </div>

            <div class="features">
                <div class="feature">
                    <h3>👤 User Management</h3>
                    <p>Secure registration and login with session management</p>
                </div>
                <div class="feature">
                    <h3>📁 File Upload</h3>
                    <p>Upload files with titles, tags, and categories (up to 10 MB)</p>
                </div>
                <div class="feature">
                    <h3>🏷️ Organization</h3>
                    <p>Organize documents using categories and tags</p>
                </div>
                <div class="feature">
                    <h3>🔍 Smart Search</h3>
                    <p>Find documents by title, category, tags, or uploader</p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
