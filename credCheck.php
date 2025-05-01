<?php
session_start();
require_once 'functions.php';

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST")
{
    // Validate required fields
    if (empty($_POST['username']) || empty($_POST['password']))
    {
        header("Location: index.php?redirect=missing_fields");
        exit;
    }

    $username = trim($_POST['username']);
    $password = $_POST['password'];

    try
    {
        $db = connectdb();

        // Get user from database
        $stmt = $db->prepare("SELECT * FROM users WHERE user_name = ?");
        $stmt->execute([ $username ]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // Check if user exists and verify password
        if ($user)
        {
            // Get the salt from the database
            $salt = $user['salt'];

            // Check if this is a legacy account (before salt was implemented)
            if (empty($salt))
            {
                // For backward compatibility with old accounts
                if ($password === $user['password'])
                {
                    // Legacy login successful - upgrade to secure password
                    $salt = bin2hex(random_bytes(16));
                    $hashedPassword = password_hash($password . $salt, PASSWORD_BCRYPT);

                    // Update user with new secure password
                    $updateStmt = $db->prepare("UPDATE users SET password = ?, salt = ? WHERE user_id = ?");
                    $updateStmt->execute([ $hashedPassword, $salt, $user['user_id'] ]);

                    // Set session and redirect to homepage
                    $_SESSION['logged_in'] = TRUE;
                    $_SESSION['userid'] = $user['user_id'];
                    $_SESSION['firstname'] = $user['first_name'];
                    $_SESSION['username'] = $user['user_name'];
                    $_SESSION['role'] = $user['role'];
                    header("Location: homePage.php");
                    exit;
                }
            }
            else
            {
                // Modern account with salt
                if (password_verify($password . $salt, $user['password']))
                {
                    // Login successful
                    $_SESSION['logged_in'] = TRUE;
                    $_SESSION['userid'] = $user['user_id'];
                    $_SESSION['firstname'] = $user['first_name'];
                    $_SESSION['username'] = $user['user_name'];
                    $_SESSION['role'] = $user['role'];
                    header("Location: homePage.php");
                    exit;
                }
            }
        }

        // If we get here, authentication failed
        header("Location: index.php?redirect=failed");
        exit;

    }
    catch ( PDOException $e )
    {
        // Log the error and show a generic error message
        error_log("Login error: " . $e->getMessage());
        die("An error occurred during login. Please try again later.");
    }
}
else
{
    // If someone tries to access this file directly without submitting the form
    header("Location: index.php");
    exit;
}
?>