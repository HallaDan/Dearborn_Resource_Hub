<?php
require 'db.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Validation
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Invalid email format.";
    } elseif (strlen($password) < 10 || !preg_match("/[A-Z]/", $password) || !preg_match("/[a-z]/", $password) || !preg_match("/[0-9]/", $password)) {
        $message = "Password must be at least 10 characters long and include uppercase, lowercase letters, and a number.";
    } elseif ($password !== $confirm_password) {
        $message = "Passwords do not match.";
    } else {
        $checkUser = $conn->prepare("SELECT * FROM users WHERE email = :email");
        $checkUser->execute([':email' => $email]);
        
        if ($checkUser->rowCount() > 0) {
            $message = "Email is already taken.";
        } else {
            // Password hash
            $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

            $sql = "INSERT INTO users (email, password, role) VALUES (:email, :password, :role)";
            $stmt = $conn->prepare($sql);
            
            if ($stmt->execute([':email' => $email, ':password' => $hashedPassword, ':role' => 'user'])) {
                $message = "Successfully registered!";
                header("Location: SignIn.php");
                exit();
            } else {
                $message = "Registration failed.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up</title>
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
        <h1>Sign Up for Dearborn Resource Hub</h1>
        <?php if (!empty($message)): ?>
            <p class="error"><?php echo htmlspecialchars($message); ?></p>
        <?php endif; ?>
        <form method="POST">
            <label for="email">Email:</label>
            <input type="email" name="email" id="email" required>

            <label for="password">Password:</label>
            <input type="password" name="password" id="password" required>

            <label for="confirm_password">Confirm Password:</label>
            <input type="password" name="confirm_password" id="confirm_password" required>

            <button type="submit">Register</button>
        </form>
        <div class="footer">
            Already registered? <a href="SignIn.php" style="color: #FFCB05;">Sign In Here</a>.
        </div>
    </div>
</body>
</html>
