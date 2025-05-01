<?php
session_start();
require_once 'functions.php';

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] != TRUE)
{
    header('Location: index.php');
    exit;
}

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST")
{
    // Validate required fields
    if (empty($_POST['current_password']) || empty($_POST['new_password']) || empty($_POST['confirm_password']))
    {
        header("Location: profile.php?redirect=missing_fields");
        exit;
    }

    // Validate password match
    if ($_POST['new_password'] !== $_POST['confirm_password'])
    {
        header("Location: profile.php?redirect=password_mismatch");
        exit;
    }

    // Validate new password complexity
    $newPassword = $_POST['new_password'];
    if (
        strlen($newPassword) < 8 || strlen($newPassword) > 20 ||
        !preg_match('/[A-Z]/', $newPassword) ||
        !preg_match('/[0-9]/', $newPassword) ||
        !preg_match('/[^A-Za-z0-9]/', $newPassword)
    )
    {
        header("Location: profile.php?redirect=invalid_password");
        exit;
    }

    // Get current user details
    $userId = $_SESSION['userid'];
    $currentPassword = $_POST['current_password'];

    try
    {
        $db = connectdb();

        // Get current user data from database
        $stmt = $db->prepare("SELECT * FROM users WHERE user_id = ?");
        $stmt->execute([ $userId ]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user)
        {
            header("Location: profile.php?redirect=account_not_found");
            exit;
        }

        // Verify current password
        $isValid = false;

        // Check if this is a legacy account (before salt was implemented)
        if (empty($user['salt']))
        {
            // For backward compatibility with old accounts
            $isValid = ($currentPassword === $user['password']);
        }
        else
        {
            // Modern account with salt
            $isValid = password_verify($currentPassword . $user['salt'], $user['password']);
        }

        if (!$isValid)
        {
            header("Location: profile.php?redirect=incorrect_password");
            exit;
        }

        // Generate a new salt
        $salt = bin2hex(random_bytes(16)); // 32 character salt

        // Hash the new password with the salt
        $hashedPassword = password_hash($newPassword . $salt, PASSWORD_BCRYPT);

        // Update the password in the database
        $stmt = $db->prepare("UPDATE users SET password = ?, salt = ? WHERE user_id = ?");
        $stmt->execute([ $hashedPassword, $salt, $userId ]);

        // Redirect to profile page with success message
        header("Location: profile.php?redirect=password_updated");
        exit;

    }
    catch ( PDOException $e )
    {
        // Log the error and redirect with a generic message
        error_log("Password update error: " . $e->getMessage());
        header("Location: profile.php?redirect=update_error");
        exit;
    }
}
else
{
    // If the form wasn't submitted, redirect to profile page
    header("Location: profile.php");
    exit;
}
?>