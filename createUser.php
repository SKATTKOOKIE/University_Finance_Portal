<?php
session_start();
require_once 'functions.php';

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST")
{
    // Validate required fields
    if (
        empty($_POST['first_name']) || empty($_POST['last_name']) || empty($_POST['email']) ||
        empty($_POST['username']) || empty($_POST['password']) || empty($_POST['confirm_password'])
    )
    {
        header("Location: createUserPage.php?redirect=missing_fields");
        exit;
    }

    // Check if passwords match
    if ($_POST['password'] !== $_POST['confirm_password'])
    {
        header("Location: createUserPage.php?redirect=password_mismatch");
        exit;
    }

    // Collect form data
    $firstName = trim($_POST['first_name']);
    $lastName = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    // Connect to database
    try
    {
        $db = connectdb();

        // Check if username already exists
        $stmt = $db->prepare("SELECT user_id FROM users WHERE user_name = ?");
        $stmt->execute([ $username ]);
        if ($stmt->fetch())
        {
            header("Location: createUserPage.php?redirect=username_exists");
            exit;
        }

        // Check if email already exists
        $stmt = $db->prepare("SELECT user_id FROM users WHERE email = ?");
        $stmt->execute([ $email ]);
        if ($stmt->fetch())
        {
            header("Location: createUserPage.php?redirect=email_exists");
            exit;
        }

        // Generate a secure random salt
        $salt = bin2hex(random_bytes(16)); // 32 character salt

        // Hash the password with the salt using a secure algorithm
        $hashedPassword = password_hash($password . $salt, PASSWORD_BCRYPT);

        // Store the user in the database
        $stmt = $db->prepare("INSERT INTO users (first_name, last_name, role, email, user_name, password, salt) 
                              VALUES (?, ?, ?, ?, ?, ?, ?)");
        $userRole = 'U'; // Default role for new users (assuming 'U' for normal users, 'A' for admin)
        $stmt->execute([ $firstName, $lastName, $userRole, $email, $username, $hashedPassword, $salt ]);

        // Redirect to login page with success message
        header("Location: index.php?redirect=registration_success");
    }
    catch ( PDOException $e )
    {
        // Log the error and show a generic error message
        error_log("Registration error: " . $e->getMessage());
        die("An error occurred during registration. Please try again later.");
    }
}
else
{
    // If someone tries to access this file directly without submitting the form
    header("Location: createUserPage.php");
    exit;
}
?>