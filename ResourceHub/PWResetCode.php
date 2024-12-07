<?php
session_start();
require 'db.php'; // Your database connection
require 'vendor/autoload.php';

use Dotenv\Dotenv;
use \Mailjet\Resources;

// Initialize Dotenv
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Fetch API keys from environment variables
$apiKey = $_ENV['API_GENERAL_KEY'];
$apiSecret = $_ENV['API_SECRET_KEY'];

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get email from form input
    $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);

    if ($email) {
        // Check if the email exists in the users table
        $stmt = $pdo->prepare("SELECT email FROM users WHERE email = :email");
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();

        if ($user) {
            // Generate a random 6-digit code
            $resetCode = random_int(100000, 999999);

            // Store the reset code in the session (or database if needed)
            $_SESSION['reset_code'] = $resetCode;
            $_SESSION['reset_email'] = $email;

            // Prepare email data
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
                        'Subject' => "Password Reset Code",
                        'TextPart' => "Your password reset code is: $resetCode",
                        'HTMLPart' => "<h3>Your password reset code is: <strong>$resetCode</strong></h3>"
                    ]
                ]
            ];

            // Send the email
            $response = $mj->post(Resources::$Email, ['body' => $emailData]);

            if ($response->success()) {
                $message = "Reset code sent to your email.";
            } else {
                $message = "Failed to send email. Error: " . $response->getData()['ErrorMessage'];
            }
        } else {
            $message = "Email not found in our records.";
        }
    } else {
        $message = "Invalid email address.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Code</title>
    <style>
        /* body */
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            background-color: #3D648A;
        }

        /* Container for form */
        .login-container {
            background-color: #00274C;
            border-radius: 10px;
            padding: 40px;
            width: 400px;
            box-shadow: 0px 4px 12px rgba(0, 0, 0, 0.2);
            text-align: center;
            color: white;
        }

        .login-container h1 {
            font-size: 24px;
            margin-bottom: 30px;
        }

        .login-container label {
            display: block;
            font-size: 16px;
            margin-bottom: 8px;
            text-align: left;
        }

        .login-container input {
            width: 100%;
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 5px;
            border: 1px solid #ccc;
            font-size: 16px;
        }

        /* Button styling */
        .login-container button {
            width: 100%;
            padding: 10px;
            background-color: #FFCB05;
            border: none;
            color: #00274C;
            font-size: 16px;
            border-radius: 5px;
            cursor: pointer;
        }

        .login-container button:hover {
            background-color: #FFB600;
        }

        /* Error message */
        .error {
            color: red;
            font-size: 14px;
            margin-bottom: 10px;
        }

        /* Footer */
        .footer {
            margin-top: 20px;
            font-size: 12px;
            color: #FFCB05;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h1>Reset Password Code</h1>
        <?php if (!empty($message)): ?>
            <p class="<?php echo $message === 'Reset code sent to your email.' ? '' : 'error'; ?>">
                <?php echo htmlspecialchars($message); ?>
            </p>
        <?php endif; ?>
        <form method="POST">
            <label for="email">Email:</label>
            <input type="email" name="email" id="email" required>
            <button type="submit">Send Code</button>
        </form>
        <div class="footer">
            <a href="SignIn.php" style="color: #FFCB05;">Back to Sign In</a>.
        </div>
    </div>
</body>
</html>
