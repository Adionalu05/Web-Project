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
    <title>Document Management System</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body style="background: linear-gradient(135deg,#1e3c72,#2a5298); display:flex; flex-direction:column; min-height:100vh;">
    
    <div class="landing-navbar">
        Kujdes: Ruani informacionin tuaj personal dhe të dokumenteve me kujdes! Mos ndani fjalëkalimet, email-et apo dokumentet tuaja me persona të paautorizuar. Sistemi ynë siguron që vetëm përdoruesit e autorizuar të kenë akses. Për çdo problem ose dyshim mbi sigurinë, kontaktoni administratorin e platformës.
    </div>

    <div class="landing-main">
        <div class="landing-title">
            Document Management System
        </div>

        <div class="landing-container">
            <p>Nëse keni një llogari shtypni:</p>
            <a href="login.php"><button>Login</button></a>

            <p>Nëse ende nuk keni hapur një llogari shtypni:</p>
            <a href="register.php"><button>Register</button></a>
        </div>
    </div>

    <section class="services-section">
        <h2>Sherbimet tona</h2>
        
        <div class="services-container">
            
            <div class="service-card">
                <h3>Upload Dokumentesh</h3>
                <p>Ngarkoni dokumentet tuaja në mënyrë të sigurt dhe të strukturuar.</p>
            </div>

            <div class="service-card">
                <h3>Kerkim i avancuar</h3>
                <p>Gjeni shpejt dokumentet sipas etiketeve, datave ose emrit.</p>
            </div>

            <div class="service-card">
                <h3>Kontroll Aksesi</h3>
                <p>Siguroni që dokumentet të aksesohen vetëm nga përdoruesit e autorizuar.</p>
            </div>

        </div>
    </section>

    <footer class="landing-footer">
        <div class="footer-flex">
            <div class="footer-desc">
                <p>Document Management System është një platformë që ndihmon përdoruesit të ruajnë dhe organizojnë dokumentet në mënyrë të sigurt dhe të strukturuar.</p>
            </div>

            <div class="footer-stores">
                <img src="https://developer.apple.com/assets/elements/badges/download-on-the-app-store.svg" alt="App Store">
                <img src="https://upload.wikimedia.org/wikipedia/commons/7/78/Google_Play_Store_badge_EN.svg" alt="Google Play">
            </div>

            <div class="footer-contact">
                <p>Për çdo paqartësi, kontaktoni: <a href="tel:+35512345678">+355 12 345 678</a></p>
            </div>

        </div>
    </footer>
</body>
</html>
