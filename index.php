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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <style>
        body{margin:0;font-family:Arial, Helvetica, sans-serif;background:#f4f6fb;}
        .alertbar{background:#e6b9f0;text-align:center;padding:8px;font-size:14px;position:fixed;width:100%;top:0;z-index:1000;}
        .navbar{margin-top:35px;display:flex;justify-content:space-between;align-items:center;padding:15px 60px;background:white;box-shadow:0 2px 10px rgba(0,0,0,0.1);}
        .logo{font-size:22px;font-weight:bold;color:#4e1e72;}
        .navbuttons{display:flex;gap:15px;align-items:center;}
        .btn-login{text-decoration:none;padding:10px 22px;border:2px solid #912a98;border-radius:25px;color:#8b2a98;font-weight:600;transition:0.3s;}
        .btn-login:hover{background:#7c2a98;color:white;}
        .btn-register{text-decoration:none;padding:10px 22px;background:linear-gradient(135deg,#4f1e72,#792a98);border-radius:25px;color:white;font-weight:600;transition:0.3s;}
        .btn-register:hover{transform:translateY(-2px);box-shadow:0 5px 15px rgba(0,0,0,0.2);}
        .hero{padding:80px 60px;background:linear-gradient(135deg,#521e72,#7e2a98);color:white;}
        .hero h1{font-size:36px;margin-bottom:15px;}
        .hero p{max-width:600px;line-height:1.6;}
        .services{padding:60px;text-align:center;}
        .services h2{font-size:28px;margin-bottom:30px;}
        .expand-search{display:flex;align-items:center;background:white;border-radius:50px;padding:10px;width:50px;overflow:hidden;transition:0.4s;margin:40px auto;box-shadow:0 5px 20px rgba(0,0,0,0.1);cursor:pointer;}
        .expand-search i{color:#2a5298;font-size:18px;}
        .expand-search input{border:none;outline:none;margin-left:10px;width:0;transition:0.4s;background:transparent;font-size:15px;}
        .expand-search:hover{width:300px;padding:10px 20px;}
        .expand-search:hover input{width:100%;}
        .services-container{display:flex;justify-content:center;gap:30px;flex-wrap:wrap;}
        .service-card{background:white;padding:30px;width:250px;border-radius:10px;box-shadow:0 8px 20px rgba(0,0,0,0.1);text-align:center;transition:0.3s;cursor:pointer;}
        .service-card:hover{transform:translateY(-10px);}
        .service-icon{font-size:40px;color:#2a5298;margin-bottom:15px;}
        .howitworks{padding:60px;background:#f4f6fb;text-align:center;}
        .steps{display:flex;justify-content:center;gap:30px;flex-wrap:wrap;}
        .step{background:white;padding:30px;width:220px;border-radius:10px;box-shadow:0 5px 15px rgba(0,0,0,0.1);}
        .step i{font-size:35px;color:#2a5298;margin-bottom:10px;}
        .security{padding:70px;background:#f4f6fb;text-align:center;}
        .security-container{display:flex;justify-content:center;gap:30px;flex-wrap:wrap;margin-top:30px;}
        .security-card{background:white;padding:30px;width:220px;border-radius:10px;box-shadow:0 5px 15px rgba(0,0,0,0.1);transition:0.3s;}
        .security-card:hover{transform:translateY(-10px);}
        .security-card i{font-size:40px;color:#4e1e72;margin-bottom:15px;}
        .footer{background:linear-gradient(135deg,#4e1e72,#7e2a98);color:white;padding:40px 60px;}
        .footer-container{display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:30px;}
        .footer-about{max-width:300px;}
        .footer-contact a{color:white;font-weight:bold;text-decoration:none;}
        .footer-stores img{height:40px;margin-top:10px;transition:0.3s;}
        .footer-stores img:hover{transform:scale(1.05);}
        .social-icons{margin-top:15px;}
        .social-icons i{margin-right:12px;font-size:20px;cursor:pointer;transition:0.3s;}
        .social-icons i:hover{color:#e6b9f0;}
        .footer-bottom{text-align:center;margin-top:30px;border-top:1px solid rgba(255,255,255,0.2);padding-top:15px;font-size:14px;}
        .cta-new{display:flex;justify-content:space-between;align-items:center;padding:70px 60px;background:linear-gradient(135deg,#4e1e72,#7e2a98);color:white;flex-wrap:wrap;gap:40px;}
        .cta-content{max-width:500px;}
        .cta-content h2{font-size:32px;margin-bottom:15px;}
        .cta-content p{line-height:1.6;margin-bottom:25px;}
        .cta-buttons{display:flex;gap:15px;flex-wrap:wrap;}
        .btn-primary{background:white;color:#4e1e72;padding:12px 25px;border-radius:25px;text-decoration:none;font-weight:bold;transition:0.3s;}
        .btn-primary:hover{transform:scale(1.05);box-shadow:0 5px 15px rgba(0,0,0,0.2);}
        .btn-secondary{border:2px solid white;color:white;padding:12px 25px;border-radius:25px;text-decoration:none;font-weight:bold;transition:0.3s;}
        .btn-secondary:hover{background:white;color:#4e1e72;}
        .cta-image{font-size:100px;opacity:0.2;}
    </style>
</head>
<body>

<div class="alertbar">
Kujdes: Mos ndani kredencialet e llogarise tuaj me persona te paautorizuar.
</div>

<header class="navbar">
<div class="logo">Document Management System</div>

<div class="navbuttons">
<a href="login.php" class="btn-login">Login</a>
<a href="register.php" class="btn-register">Register</a>
</div>
</header>

<section class="hero">
<h1>Menaxhoni dokumentet tuaja ne menyre te sigurt</h1>
<p>
Platforma jone ju lejon te ngarkoni, organizoni dhe kerkoni dokumente ne menyre
te shpejte dhe te sigurt duke perdorur nje sistem modern.
</p>
</section>

<section class="services">
<h2>Sherbimet tona</h2>

<div class="expand-search">
<i class="fa-solid fa-magnifying-glass"></i>
<input type="text" placeholder="Kërko shërbimin...">
</div>

<div class="services-container">

<div class="service-card">
<i class="fa-solid fa-upload service-icon"></i>
<h3>Ngarkim Dokumentesh</h3>
<p>Ngarkoni dokumentet tuaja ne menyre te sigurt.</p>
</div>

<div class="service-card">
<i class="fa-solid fa-magnifying-glass service-icon"></i>
<h3>Kerkim Dokumentesh</h3>
<p>Gjeni dokumentet shpejt duke perdorur filtrat.</p>
</div>

<div class="service-card">
<i class="fa-solid fa-lock service-icon"></i>
<h3>Kontroll Aksesi</h3>
<p>Vetem perdoruesit e autorizuar mund te aksesojne dokumentet.</p>
</div>

</div>
</section>

<section class="howitworks">
<h2>Si funksionon sistemi</h2>

<div class="steps">

<div class="step">
<i class="fa-solid fa-user-plus"></i>
<h3>Krijo llogari</h3>
<p>Regjistrohu per te perdorur sistemin.</p>
</div>

<div class="step">
<i class="fa-solid fa-upload"></i>
<h3>Ngarko dokument</h3>
<p>Ngarko dokumentet ne sistem.</p>
</div>

<div class="step">
<i class="fa-solid fa-folder-open"></i>
<h3>Menaxho dokumentet</h3>
<p>Kerko dhe organizo dokumentet.</p>
</div>

</div>
</section>

<section class="security">
<h2>Siguria e dokumenteve</h2>

<div class="security-container">

<div class="security-card">
<i class="fa-solid fa-shield-halved"></i>
<h3>Enkriptim</h3>
<p>Dokumentet ruhen te enkriptuara per siguri maksimale.</p>
</div>

<div class="security-card">
<i class="fa-solid fa-user-lock"></i>
<h3>Kontroll Aksesi</h3>
<p>Vetëm perdoruesit e autorizuar mund te aksesojne dokumentet.</p>
</div>

<div class="security-card">
<i class="fa-solid fa-database"></i>
<h3>Backup Automatik</h3>
<p>Sistemi krijon backup per te mbrojtur te dhenat.</p>
</div>

</div>
</section>
<section class="cta-new">

<div class="cta-content">

<h2>Menaxho dokumentet si profesionist</h2>

<p>
Filloni tani dhe organizoni çdo dokument në një platformë të vetme,
 të sigurt dhe të shpejtë.
</p>

<div class="cta-buttons">
<a href="register.php" class="btn-primary">Fillo Falas</a>
<a href="login.php" class="btn-secondary">Kam llogari</a>
</div>

</div>

<div class="cta-image">
<i class="fa-solid fa-folder-open"></i>
</div>

</section>

<footer class="footer">

<div class="footer-container">

<div class="footer-about">
<h3>Document Management System</h3>
<p>
Ky sistem ju ndihmon te ruani dhe organizoni dokumentet tuaja
në menyre te strukturuar dhe te sigurt.
</p>
</div>

<div class="footer-contact">
<p>Kontaktoni per çdo paqartesi:</p>
<a href="tel:+355690000000">+355 69 000 000</a>
</div>

<div class="footer-stores">
<a href="#">
<img src="https://developer.apple.com/assets/elements/badges/download-on-the-app-store.svg" alt="App Store">
</a>
<a href="#">
<img src="https://upload.wikimedia.org/wikipedia/commons/7/78/Google_Play_Store_badge_EN.svg" alt="Google Play">
</a>
</div>

<div class="social-icons">
<i class="fa-brands fa-facebook"></i>
<i class="fa-brands fa-instagram"></i>
<i class="fa-brands fa-linkedin"></i>
</div>

</div>

<div class="footer-bottom">
<p>© 2026 Document Management System | All Rights Reserved</p>
</div>

</footer>

</body>
</html>
