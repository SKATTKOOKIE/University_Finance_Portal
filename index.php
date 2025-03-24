<?php
    session_start();

    // If $_GET['redirect'] is set it means the user has already had an attempt at loging in and they get a pop up alert
    if(isset($_GET['redirect']))
    {
        echo "<script>alert('Incorrect Username or Password');</script>";
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
        <header>
            <h1 id="loginHeader">Welcome to the Finance Portal!</h1>
        </header>
        <main>
            <!-- Form allows user to enter login details and it proceeds to credCheck.php which checks if the login details are valid -->
            <form action="credCheck.php" method="post">
                <label for="username">Username:</label><br>
                <input type="text" name="username" id="username" required><br>
                <label for="password">Password:</label><br>
                <input type="password" name="password" id="password" required><br>
                <button type="submit">Login</button>
            </form>
    </body>
</html>