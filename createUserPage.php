<?php
session_start();

// If $_GET['redirect'] is set it means the user has already had an attempt at registration and they get a pop up alert
if (isset($_GET['redirect']))
{
    if ($_GET['redirect'] == 'username_exists')
    {
        $errorMessage = 'Username already exists. Please choose a different username.';
    }
    else if ($_GET['redirect'] == 'email_exists')
    {
        $errorMessage = 'Email already exists. Please use a different email address.';
    }
    else if ($_GET['redirect'] == 'missing_fields')
    {
        $errorMessage = 'Please fill in all required fields.';
    }
    else if ($_GET['redirect'] == 'password_mismatch')
    {
        $errorMessage = 'Passwords do not match. Please try again.';
    }
    else if ($_GET['redirect'] == 'success')
    {
        $successMessage = 'Account created successfully! You can now login.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account - Finance Portal</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>
    <div class="login-container">
        <h1 class="login-header">Create Account</h1>

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

        <!-- Form collects user details and sends to createUser.php for processing -->
        <form action="createUser.php" method="post" class="login-form">
            <div class="form-group">
                <label for="first_name">First Name</label>
                <input type="text" name="first_name" id="first_name" required>
            </div>

            <div class="form-group">
                <label for="last_name">Last Name</label>
                <input type="text" name="last_name" id="last_name" required>
            </div>

            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" name="email" id="email" required>
            </div>

            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" name="username" id="username" required>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" name="password" id="password" required>
            </div>

            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>
                <input type="password" name="confirm_password" id="confirm_password" required>
            </div>

            <button type="submit" class="login-button">Create Account</button>
        </form>

        <div class="login-create-user-container">
            <span>Already have an account?</span>
            <button onclick="window.location.href='index.php'" class="login-button">Login</button>
        </div>
    </div>
</body>

</html>