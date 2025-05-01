<?php
session_start();
require_once 'functions.php';

// Get any redirect messages
$messages = getRedirectMessages();
$errorMessage = $messages['error'];
$successMessage = $messages['success'];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Finance Portal</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>
    <div class="login-container">
        <h1 class="login-header">Finance Portal</h1>

        <?php if (isset($errorMessage)): ?>
            <div class="alert alert-error"
                style="background-color: #ffeeee; color: #dd0000; padding: 10px; border-radius: 4px; margin-bottom: 20px; text-align: center;">
                <?php echo $errorMessage; ?>
            </div>
        <?php endif; ?>

        <?php if (isset($successMessage)): ?>
            <div class="alert alert-success"
                style="background-color: #eeffee; color: #00aa00; padding: 10px; border-radius: 4px; margin-bottom: 20px; text-align: center;">
                <?php echo $successMessage; ?>
            </div>
        <?php endif; ?>

        <!-- Form allows user to enter login details and it proceeds to credCheck.php which checks if the login details are valid -->
        <form action="credCheck.php" method="post" class="login-form" id="loginForm">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" name="username" id="username" pattern="[A-Za-z0-9_]+"
                    title="Username can only contain letters, numbers, and underscores" required>
                <small class="form-hint">Enter your username</small>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" name="password" id="password" required>
                <small class="form-hint">Enter your password</small>
            </div>

            <button type="submit" class="login-button">Login</button>
        </form>

        <div class="login-create-user-container">
            <span>Don't currently have an account?</span>
            <button onclick="window.location.href='createUserPage.php'" class="login-button">Create Account</button>
        </div>
    </div>

    <!-- Load the external validation script -->
    <script src="form-validation.js"></script>
    <script>
        // Initialize form validation when the page loads
        document.addEventListener('DOMContentLoaded', function () {
            // Initialize general form validation
            initFormValidation('loginForm');
        });
    </script>
</body>

</html>