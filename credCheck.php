<?php
    session_start();
    require_once("functions.php");

    // Validate form submission
    if (!isset($_POST['username']) || !isset($_POST['password']))
    {
        header('Location: index.php?redirect=missing_fields');
        exit;
    }

    $db = connectdb();

    // Use prepared statements with the correct column name (user_name instead of username)
    $stmt = $db->prepare("SELECT user_id, user_name, password, first_name FROM users WHERE user_name = ?");
    $stmt->execute([$_POST['username']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Verify password (plain text for now, should be hashed in the future)
    if ($user && $_POST['password'] === $user['password']) 
    {
        // Regenerate session ID to prevent session fixation
        session_regenerate_id(true);
        
        $_SESSION['logged_in'] = TRUE;
        $_SESSION['firstname'] = $user['first_name'];
        $_SESSION['userid'] = $user['user_id'];
        
        // Check if the function exists before calling it
        if (function_exists('updatelastlogin')) 
        {
            updatelastlogin($user['user_id']);
        }
        
        header('Location: homepage.php');
        exit;
    } 
    else 
    {
        $_SESSION['logged_in'] = FALSE;
        header('Location: login.php?redirect=failed');
        exit;
    }
?>