<?php
    session_start();

    // If $_GET['redirect'] is set it means the user has already had an attempt at loging in and they get a pop up alert
    if(isset($_GET['redirect']))
    {
        if($_GET['redirect'] == 'failed') 
        {
            $errorMessage = 'Incorrect username or password. Please try again.';
        }
        else if($_GET['redirect'] == 'missing_fields') 
        {
            $errorMessage = 'Please fill in all required fields.';
        } 
        else if($_GET['redirect'] == 'logout') 
        {
            $successMessage = 'You have been successfully logged out.';
        }
    }
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
            
            <?php if(isset($errorMessage)): ?>
                <div class="alert alert-error" style="background-color: #ffeeee; color: #dd0000; padding: 10px; border-radius: 4px; margin-bottom: 20px; text-align: center;">
                    <?php echo $errorMessage; ?>
                </div>
            <?php endif; ?>
            
            <?php if(isset($successMessage)): ?>
                <div class="alert alert-success" style="background-color: #eeffee; color: #00aa00; padding: 10px; border-radius: 4px; margin-bottom: 20px; text-align: center;">
                    <?php echo $successMessage; ?>
                </div>
            <?php endif; ?>
            
            <!-- Form allows user to enter login details and it proceeds to credCheck.php which checks if the login details are valid -->
            <form action="credCheck.php" method="post" class="login-form">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" name="username" id="username" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" name="password" id="password" required>
                </div>
                
                <button type="submit" class="login-button">Login</button>
            </form>

            <div class="login-create-user-container">
                <span>Don't currently have an account?</span>
                <button onclick="window.location.href='createUserPage.php'" class="login-button">Create Account</button>
            </div>

        </div>
    </body>
</html>