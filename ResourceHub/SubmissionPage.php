<?php
session_start();
require 'db.php'; // Include the database connection script

if (!isset($_SESSION['user_id'])) {
    // Redirect to SignIn page if the user is not logged in
    header("Location: SignIn.php");
    exit();
}

$successMessage = '';

//language switch
if (isset($_GET['lang'])) {
    $_SESSION['lang'] = $_GET['lang'];
} elseif (!isset($_SESSION['lang'])) {
    $_SESSION['lang'] = 'en'; // default language (for now?)
}

//generic translations
$translations = [
    'en' => [
        'home_page' => 'Home Page',
        'business_listings' => 'Find Local Experts',
        'contribute' => 'Contribute',
        'sign_out' => 'Sign Out',
    ],
    'ar' => [
        'home_page' => 'الصفحة الرئيسية',
        'business_listings' => 'ابحث عن خبراء محليين',
        'contribute' => 'أضف القوائم',
        'sign_out' => 'تسجيل الخروج',
    ],
    'es' => [
        'home_page' => 'Página de Inicio',
        'business_listings' => 'Encuentra expertos locales',
        'contribute' => 'Contribuir listados',
        'sign_out' => 'Cerrar sesión',
    ],
];

$lang = $translations[$_SESSION['lang']];

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit'])) {
    // Fetch form data
    $businessName = $_POST['businessName'];
    $businessCategory = $_POST['businessCategory'];
    $address = $_POST['address'];
    $businessPhone = $_POST['businessPhone'];
    $website = $_POST['website'];
    $language = $_POST['language'];

    //Validate Form Data
    if(empty($businessName) || empty($businessCategory) || empty($address) || empty($businessPhone) || empty($website) || empty($language)){
        die('Error: All fields are required.');
    }

    // Fetch the currently signed-in user's ID
    $businessID = $_SESSION['user_id'];

    //Verify the user exists in the database to prevent foreign key errors
    $stmt = $conn->prepare("SELECT id FROM users WHERE id = ?");
    $stmt->execute([$businessID]);
    if($stmt->rowCount() == 0){
        die('Please Sign in to submit business form');
    }
    
    try {
        // Prepare and bind
        $stmt = $conn->prepare("INSERT INTO admin_approval (businessID, businessName, businessCategory, address, businessPhone, website, language) VALUES (?, ?, ?, ?, ?, ?, ?)");

        // Execute the statement with the data
        $stmt->execute([$businessID, $businessName, $businessCategory, $address, $businessPhone, $website, $language]);

        $successMessage = "Your business has been submitted for admin approval";
    } catch(PDOException $e) {
        echo "Error: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="assets/css/styles.css">
    <title>Submit Business</title>
    <style>
        .center-content {
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        form {
            width: 300px;
            margin-left: 90px;
        }
        label, input, select {
            display: block;
            margin-bottom: 10px;
        }
    </style>
</head>
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
                <li><a href="HomePage.php"><?= $lang['home_page'] ?></a></li>
                <li><a href="BusinessListingPage.php"><?= $lang['business_listings'] ?></a></li>
                <li><a href="SignOut.php"><?= $lang['sign_out'] ?></a></li>
            </ul>
        </div>
    </div>

    <div class="center-content">
        <h2>Submit Business</h2>
        <?php if ($successMessage): ?>
            <div class="alert alert-success" role="alert">
                <?php echo $successMessage; ?>
            </div>
        <?php endif; ?>
        <form method="post" action="SubmissionPage.php">
            <label for="businessName">Business Name:</label>
            <input type="text" id="businessName" name="businessName" required>
            
            <label for="businessCategory">Business Category:</label>
            <input type="text" id="businessCategory" name="businessCategory" required>
            
            <label for="address">Location Address:</label>
            <input type="text" id="address" name="address" required>
            
            <label for="businessPhone">Business Phone Number:</label>
            <input type="text" id="businessPhone" name="businessPhone" required>
            
            <label for="website">Business Website:</label>
            <input type="text" id="website" name="website" required>

            <label for="language">Language:</label>
            <select id="language" name="language" required>
                <option value="">Select Language</option>
                <option value="English">English</option>
                <option value="Spanish">Spanish</option>
                <option value="Arabic">Arabic</option>
            </select>
            
            <input type="submit" name="submit" value="Submit" class="btn btn-primary">
        </form>
    </div>
    <script src="assets/js/script.js"></script>
</body>
</html>