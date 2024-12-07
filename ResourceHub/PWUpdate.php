<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Password</title>
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

        /* Footer link */
        .footer a {
            color: #FFCB05;
            text-decoration: none;
        }

        .footer a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h1>Update Password</h1>
        <?php if (!empty($message)): ?>
            <p class="error"><?php echo htmlspecialchars($message); ?></p>
        <?php endif; ?>
        <form method="POST">
            <label for="code">Code:</label>
            <input type="number" name="code" id="code" required>

            <label for="new_password">New Password:</label>
            <input type="password" name="new_password" id="new_password" required>

            <label for="confirm_password">Confirm New Password:</label>
            <input type="password" name="confirm_password" id="confirm_password" required>

            <button type="submit">Update</button>
        </form>
        <div class="footer">
            <a href="HomePage.php">Back to Home</a>
        </div>
    </div>
</body>
</html>
