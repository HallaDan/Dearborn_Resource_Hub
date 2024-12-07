<?php
session_start();
require 'db.php';

require 'vendor/autoload.php';
use Dotenv\Dotenv;
use \Mailjet\Resources;

// Initialize Dotenv
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Fetch API keys from environment variables
$apiKey = $_ENV['API_GENERAL_KEY'];
$apiSecret = $_ENV['API_SECRET_KEY'];

if (!isset($_SESSION['user_id'])) {
    header("Location: SignIn.php");
    exit();
}

$sql = "SELECT * FROM users WHERE id = :id";
$stmt = $conn->prepare($sql);
$stmt->execute([':id' => $_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$nav = [
    'home_page' => 'Home Page',
    'business_listings' => 'Find Local Experts',
    'sign_out' => 'Sign Out',
];

//language switch
if (isset($_GET['lang'])) {
    $_SESSION['lang'] = $_GET['lang'];
} elseif (!isset($_SESSION['lang'])) {
    $_SESSION['lang'] = 'en'; //default language (for now?)
}

// Pagination settings
$rows_per_page = 10;
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($current_page - 1) * $rows_per_page;

// Fetch the total number of rows
try {
    $stmt = $conn->prepare("SELECT COUNT(*) FROM admin_approval");
    $stmt->execute();
    $total_rows = $stmt->fetchColumn();
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}

// Calculate total number of pages
$total_pages = ceil($total_rows / $rows_per_page);

// Fetch the rows for the current page
try {
    $stmt = $conn->prepare("SELECT * FROM admin_approval LIMIT :limit OFFSET :offset");
    $stmt->bindParam(':limit', $rows_per_page, PDO::PARAM_INT);
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $businesses = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}

$table_mapping = [
    'en' => 'business_en',
    'ar' => 'business_ar',
    'es' => 'business_es',
];

$attribute_mapping = [
    'en' => [
        'businessID' => 'enBusinessID',
        'businessName' => 'enBusinessName',
        'businessCategory' => 'enBusinessCategory',
        'address' => 'enAddress',
        'businessPhone' => 'enBusinessPhone',
        'website' => 'enWebsite',
    ],
    'ar' => [
        'businessID' => 'arBusinessID',
        'businessName' => 'arBusinessName',
        'businessCategory' => 'arBusinessCategory',
        'address' => 'arAddress',
        'businessPhone' => 'arBusinessPhone',
        'website' => 'arWebsite',
    ],
    'es' => [
        'businessID' => 'esBusinessID',
        'businessName' => 'esBusinessName',
        'businessCategory' => 'esBusinessCategory',
        'address' => 'esAddress',
        'businessPhone' => 'esBusinessPhone',
        'website' => 'esWebsite',
    ],
];

$attributes = $attribute_mapping[$_SESSION['lang']];


$selected_table = $table_mapping[$_SESSION['lang']];
// Initialize selected table
if (!isset($_SESSION['lang'])) {
    $_SESSION['lang'] = 'en'; // default language (for now?)
}

// Handle form submission to move selected businesses or deny
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['selected_business']) && isset($_POST['lang']) && isset($_POST['move_selected'])) {
        // "Move Selected" button logic
        $selected_business_ids = $_POST['selected_business'];
        $selected_lang = $_POST['lang'];
        $selected_table = $table_mapping[$selected_lang];
        $attributes = $attribute_mapping[$selected_lang];

        // Move selected businesses to the appropriate table
        foreach ($selected_business_ids as $business_id) {
            try {
                // Fetch the business details to insert into the new table
                $stmt = $conn->prepare("SELECT * FROM admin_approval WHERE id = :id");
                $stmt->execute([':id' => $business_id]);
                $business = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($business) {
                    // Map the attributes to the selected table
                    $mapped_business = [];
                    foreach ($attributes as $admin_attr => $lang_attr) {
                        $mapped_business[$lang_attr] = $business[$admin_attr];
                    }

                    // Insert into the selected table
                    $fields = implode(", ", array_keys($mapped_business));
                    $placeholders = ":" . implode(", :", array_keys($mapped_business));

                    $stmt_insert = $conn->prepare("INSERT INTO {$selected_table} ($fields) VALUES ($placeholders)");
                    $stmt_insert->execute($mapped_business);

                    // Delete the business from the admin_approval table
                    $stmt_delete = $conn->prepare("DELETE FROM admin_approval WHERE id = :id");
                    $stmt_delete->execute([':id' => $business_id]);
                }
            } catch (PDOException $e) {
                echo "Error: " . $e->getMessage();
            }
        }

        // Redirect to the same page to reflect changes
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();

    } elseif (isset($_POST['selected_business']) && isset($_POST['deny'])) {
        // "Deny" button logic
        $selected_business_ids = $_POST['selected_business'];

        foreach ($selected_business_ids as $business_id) {
            try {
                // retreive the business details for the email
                $stmt = $conn->prepare("SELECT * FROM admin_approval WHERE id = :id");
                $stmt->execute([':id' => $business_id]);
                $business = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($business) {
                    // get user email from users table
                    $stmt_user = $conn->prepare("SELECT email FROM users WHERE id = :id");
                    $stmt_user->execute([':id' => $business['businessID']]);
                    $user = $stmt_user->fetch(PDO::FETCH_ASSOC);

                    if ($user) {

                        $mj = new \Mailjet\Client($apiKey, $apiSecret, true, ['version' => 'v3.1']);
                        $emailData = [
                            'Messages' => [
                                [
                                    'From' => [
                                        'Email' => "dbresourcehub@gmail.com",
                                        'Name' => "Dearborn Resource Hub"
                                    ],
                                    'To' => [
                                        [
                                            'Email' => $user['email']
                                        ]
                                    ],
                                    'Subject' => "Application Denied",
                                    'TextPart' => "Unfortunately, your application has been denied.",
                                    'HTMLPart' => "<h3>Unfortunately, your application has been denied.</h3>"
                                ]
                            ]
                        ];

                        $response = $mj->post(Resources::$Email, ['body' => $emailData]);

                        if (!$response->success()) {
                            echo "Failed to send email. Error: " . $response->getData()['ErrorMessage'];
                        }
                    }

                    // delete the business entry from the admin_approval table
                    $stmt_delete = $conn->prepare("DELETE FROM admin_approval WHERE id = :id");
                    $stmt_delete->execute([':id' => $business_id]);
                }
            } catch (PDOException $e) {
                echo "Error: " . $e->getMessage();
            }
        }

        // Redirect to the same page to reflect changes
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }
}

?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>ListingPage - Resource Hub</title>
        <link rel="stylesheet" href="assets/css/styles.css">
        <style>
        .table-container {
            padding: 10px;
        }
        table {
            margin: auto;
            border-collapse: collapse;
        }
        th, td {
            padding: 10px;
            border: 1px solid #ddd;
            text-align: left;
        }
        th {
            background-color: #f4f4f4;
        }
        .table-responsive {
            overflow-x: auto;
        }
        @media (max-width: 600px) {
            .table-container {
                overflow-x: scroll;
            }
        }
        .language-selector {
            margin: auto;
            border-collapse: collapse;
        }
        .pagination {
            display: flex;
            justify-content: center;
            margin-top: 20px;
        }
        .pagination a {
            margin: 0 5px;
            padding: 8px 16px;
            text-decoration: none;
            border: 1px solid #ddd;
            color: #333;
        }
        .pagination a.active {
            background-color: #333;
            color: white;
            border: 1px solid #333;
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
                    <li><a href="HomePage.php"><?= $nav['home_page'] ?></a></li>
                    <li><a href="BusinessListingPage.php"><?= $nav['business_listings'] ?></a></li>
                    <li><a href="SignOut.php"><?= $nav['sign_out'] ?></a></li>
                </ul>
            </div>
        </div>

        <div class="container">
        <h2>Admin Approval</h2>
        <div class="language-selector">
            <form method="POST" action="">
                <label>Select Table:</label><br>
                <input type="radio" name="lang" value="en" <?= $_SESSION['lang'] === 'en' ? 'checked' : '' ?>> English<br>
                <input type="radio" name="lang" value="ar" <?= $_SESSION['lang'] === 'ar' ? 'checked' : '' ?>> Arabic<br>
                <input type="radio" name="lang" value="es" <?= $_SESSION['lang'] === 'es' ? 'checked' : '' ?>> Spanish<br>
                <button type="submit" name="move_selected">Move Selected</button>
                <button type="submit" name="deny">Deny</button>
        </div>
    </div>

    <div class="container">
        <div class="table-container">
            <?php if (count($businesses) > 0): ?>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Select</th>
                                <th>Business ID</th>
                                <th>Business Name</th>
                                <th>Category</th>
                                <th>Address</th>
                                <th>Phone</th>
                                <th>Website</th>
                                <th>Language</th>
                                <th>Created At</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($businesses as $business): ?>
                                <tr>
                                    <td>
                                        <input type="checkbox" name="selected_business[]" value="<?php echo htmlspecialchars($business['id']); ?>">
                                    </td>
                                    <td><?php echo htmlspecialchars($business['businessID']); ?></td>
                                    <td><?php echo htmlspecialchars($business['businessName']); ?></td>
                                    <td><?php echo htmlspecialchars($business['businessCategory']); ?></td>
                                    <td><?php echo htmlspecialchars($business['address']); ?></td>
                                    <td><?php echo htmlspecialchars($business['businessPhone']); ?></td>
                                    <td><a href="<?php echo htmlspecialchars($business['website']); ?>" target="_blank"><?php echo htmlspecialchars($business['website']); ?></a></td>
                                    <td><?php echo htmlspecialchars($business['language']); ?></td>
                                    <td><?php echo htmlspecialchars($business['create_at']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p>No businesses found.</p>
            <?php endif; ?>
        </div>
    </div>
    </form>

        <div class="pagination">
            <?php for ($page = 1; $page <= $total_pages; $page++): ?>
                <a href="?page=<?= $page ?>" class="<?= $page == $current_page ? 'active' : '' ?>"><?= $page ?></a>
            <?php endfor; ?>
        </div>

        <script src="assets/js/script.js"></script>
    </body>
</html>