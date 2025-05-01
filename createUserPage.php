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
    <title>Create Account - Finance Portal</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .form-hint {
            font-size: 0.8rem;
            color: #666;
            margin-top: 3px;
            display: block;
        }

        .requirement-met {
            color: #00aa00;
        }

        .requirement-unmet {
            color: #dd0000;
        }

        input:invalid {
            border-color: #dd0000;
        }

        #password-requirements {
            margin-bottom: 15px;
            padding: 10px;
            background-color: #f8f8f8;
            border-radius: 4px;
            display: none;
        }
    </style>
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
        <form action="createUser.php" method="post" class="login-form" id="createUserForm">
            <div class="form-group">
                <label for="first_name">First Name</label>
                <input type="text" name="first_name" id="first_name" pattern="[A-Za-z]+"
                    title="Only letters are allowed" required>
                <small class="form-hint">Only letters allowed (no spaces)</small>
            </div>

            <div class="form-group">
                <label for="last_name">Last Name</label>
                <input type="text" name="last_name" id="last_name" pattern="[A-Za-z]+" title="Only letters are allowed"
                    required>
                <small class="form-hint">Only letters allowed (no spaces)</small>
            </div>

            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" name="email" id="email" required>
                <small class="form-hint">Must be a valid email format (e.g., user@example.com)</small>
            </div>

            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" name="username" id="username" pattern="[A-Za-z0-9_]+" minlength="3" maxlength="20"
                    title="Username can only contain letters, numbers, and underscores" required>
                <small class="form-hint">3-20 characters: letters, numbers, and underscores only</small>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" name="password" id="password" minlength="8" maxlength="20" required>
                <small class="form-hint">8-20 characters, must include a number, a symbol, and a capital letter</small>
            </div>

            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>
                <input type="password" name="confirm_password" id="confirm_password" minlength="8" maxlength="20"
                    required>
                <small class="form-hint">Re-enter your password</small>
            </div>

            <div id="password-requirements">
                <p style="margin-top: 0; font-weight: bold;">Password must contain:</p>
                <ul style="margin-bottom: 0; padding-left: 20px;">
                    <li id="length-check">8-20 characters</li>
                    <li id="uppercase-check">At least one uppercase letter</li>
                    <li id="number-check">At least one number</li>
                    <li id="symbol-check">At least one symbol</li>
                </ul>
            </div>

            <button type="submit" class="login-button" id="submit-button">Create Account</button>
        </form>

        <div class="login-create-user-container">
            <span>Already have an account?</span>
            <button onclick="window.location.href='index.php'" class="login-button">Login</button>
        </div>
    </div>

    <!-- Load the external validation script -->
    <script src="form-validation.js"></script>
    <script>
        // Initialize form validation when the page loads
        document.addEventListener('DOMContentLoaded', function () {
            // Initialize password validation
            initPasswordValidation('createUserForm', {
                // Optional configuration - defaults will work fine
                showRequirements: true
            });

            // Initialize general form validation
            initFormValidation('createUserForm');
        });
    </script>
</body>

</html>