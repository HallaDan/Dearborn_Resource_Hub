<?php
session_start();
require 'db.php';

//redirect to SignIn.php if the user is not logged in
//we dont need to keep this obviously, but just to test login/logout
if (!isset($_SESSION['user_id'])) {
    header("Location: SignIn.php");
    exit();
}

// get user details
$sql = "SELECT * FROM users WHERE id = :id";
$stmt = $conn->prepare($sql);
$stmt->execute([':id' => $_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$is_admin = ($user['role'] === 'admin');

//language switch
if (isset($_GET['lang'])) {
    $_SESSION['lang'] = $_GET['lang'];
} elseif (!isset($_SESSION['lang'])) {
    $_SESSION['lang'] = 'en'; //default language (for now?)
}

//generic translations
$translations = [
    'en' => [
        'welcome' => 'Welcome!',
        'description' => 'Bridging Language Barriers in Your Community',
        'business_listings' => 'Find Local Experts',
        'contribute' => 'Contribute',
        'sign_out' => 'Sign Out',
        'block_text' => "
            Welcome!<br>
            Bridging Language Barriers in Your Community<br><br>
            We are here to connect you with multilingual professionals who can help with essential services—whether it's legal aid, plumbing, or mechanics. Our platform ensures that language is no longer a barrier, offering listings in English, Arabic, and Spanish.<br><br>
            <strong>How It Works:</strong><br>
            <ul>
                <li>Find Local Experts: Search for professionals based on their language and specialization.</li>
                <li>Contribute Listings: Share your own recommendations and help others in the community (admin-approved).</li>
                <li>Join Community Events: Stay updated with events that foster connections within Dearborn.</li>
            </ul>
            Whether you’re new to Dearborn or looking to connect with professionals who speak your language, we’re here to help!
        ",
    ],
    'ar' => [
        'welcome' => 'مرحبًا!',
        'description' => 'سد الفجوة اللغوية في مجتمعك',
        'business_listings' => 'ابحث عن خبراء محليين',
        'contribute' => 'أضف القوائم',
        'sign_out' => 'تسجيل الخروج',
        'block_text' => "
            مرحبًا!<br>
            سد الفجوة اللغوية في مجتمعك<br><br>
            نحن هنا لربطك بالمهنيين متعددي اللغات الذين يمكنهم المساعدة في الخدمات الأساسية - سواء كان ذلك مساعدات قانونية، أو السباكة، أو الميكانيكا. تضمن منصتنا أن اللغة لم تعد عائقًا، حيث نقدم قوائم باللغات الإنجليزية والعربية والإسبانية.<br><br>
            <strong>كيف يعمل:</strong><br>
            <ul>
                <li>ابحث عن خبراء محليين: ابحث عن المهنيين بناءً على لغتهم وتخصصهم.</li>
                <li>أضف القوائم: شارك توصياتك الخاصة وساعد الآخرين في المجتمع (تخضع لموافقة الإدارة).</li>
                <li>انضم إلى الأحداث المجتمعية: ابقَ على اطلاع بالأحداث التي تعزز الروابط داخل ديربورن.</li>
            </ul>
            سواء كنت جديدًا في ديربورن أو تبحث عن مهنيين يتحدثون لغتك، نحن هنا للمساعدة!
        ",
    ],
    'es' => [
        'welcome' => '¡Bienvenido!',
        'description' => 'Superando las barreras lingüísticas en tu comunidad',
        'business_listings' => 'Encuentra expertos locales',
        'contribute' => 'Contribuir listados',
        'sign_out' => 'Cerrar sesión',
        'block_text' => "
            ¡Bienvenido!<br>
            Superando las barreras lingüísticas en tu comunidad<br><br>
            Estamos aquí para conectarte con profesionales multilingües que puedan ayudar con servicios esenciales, ya sea asistencia legal, plomería o mecánica. Nuestra plataforma garantiza que el idioma ya no sea una barrera, ofreciendo listados en inglés, árabe y español.<br><br>
            <strong>¿Cómo funciona?</strong><br>
            <ul>
                <li>Encuentra expertos locales: Busca profesionales según su idioma y especialización.</li>
                <li>Aporta listados: Comparte tus propias recomendaciones y ayuda a otros en la comunidad (aprobado por el administrador).</li>
                <li>Únete a eventos comunitarios: Mantente informado sobre eventos que fomenten conexiones dentro de Dearborn.</li>
            </ul>
            Ya sea que seas nuevo en Dearborn o busques conectarte con profesionales que hablen tu idioma, ¡estamos aquí para ayudarte!
        ",
    ],
];
$lang = $translations[$_SESSION['lang']];
?>

<!DOCTYPE html>
<html lang="<?= $_SESSION['lang'] ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HomePage - Resource Hub</title>
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body>
<body>
    <header>
        <div class="nav-container">
            <h1>Multilingual Resource Hub</h1>
        </div>
    </header>

    <div class="hamburger-container">
        <div class="dropdown">
            <button class="dropdown-toggle">☰</button>
            <ul class="hamburger-menu">
                <li><a href="BusinessListingPage.php"><?= $lang['business_listings'] ?></a></li>
                <?php if (!$is_admin): ?>
                    <li><a href="SubmissionPage.php"><?= $lang['contribute'] ?></a></li>
                <?php endif; ?>
                <?php if ($is_admin): ?>
                    <li><a href="AdminPanel.php">Admin Panel</a></li>
                <?php endif; ?>
                <li><a href="SignOut.php"><?= $lang['sign_out'] ?></a></li>
            </ul>
        </div>
    </div>

    <main>
        <section class="language-selector-container">
            <div class="language-selector">
                <form method="GET" action="">
                    <label>Select Your Language:</label><br>
                    <input type="radio" name="lang" value="en" <?= $_SESSION['lang'] === 'en' ? 'checked' : '' ?>> English<br>
                    <input type="radio" name="lang" value="ar" <?= $_SESSION['lang'] === 'ar' ? 'checked' : '' ?>> Arabic<br>
                    <input type="radio" name="lang" value="es" <?= $_SESSION['lang'] === 'es' ? 'checked' : '' ?>> Spanish<br>
                    <button type="submit">Apply</button>
                </form>
            </div>
            <div class="images-container">
                <img src="assets/images/dearbornlogo.png" alt="Dearborn Logo">
                <img src="assets/images/homepage.jpg" alt="Community helping image">
            </div>
        </section>
        <section class="welcome-box">
            <h2><?= $lang['welcome'] ?></h2>
            <p><?= $lang['block_text'] ?></p>
        </section>
    </main>


    <script src="assets/js/script.js"></script>
</body>
</html>