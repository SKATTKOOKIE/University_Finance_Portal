<?php
session_start();
// Checks the user has logged in
if ($_SESSION['logged_in'] != TRUE){
    header('Location: login.php');
}
require_once("inc/header.php");
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
    <main>
        <h1>Welcome to the Finance Portal!</h1>
    </main>
</body>
</html>