<?php
session_start();
// Checks that a username has been entered
if (!isset($_POST['username'])){
    header('Location: index.php?redirect="true"');
}

$db = connectdb();
#Checks if the username exists, if it does it stores the relevant data from that row
foreach ($db->query("select * from users") as $row){
    if ($row['username'] == $_POST['username']){
        $userid = $row['user_id'];
        $username = $row['user_name'];
        $password = $row['password'];
        $firstname = $row['first_name'];
    }
}

// Check if the username and password that were submitted in login.php are correct
if($_POST['username'] == $username AND $_POST['password'] == $password){
    // If they are correct the session variable logged_in is set to true and the user is redirected to pageHome.php
    $_SESSION['logged_in'] = TRUE;
    $_SESSION['firstname'] = $firstname;
    $_SESSION['userid'] = $userid;
    updatelastlogin($userid);
    header('Location: homepage.php');
}
else{
    // If the credentials are incorrect the user is redirected back to login.php
    // setting redirect to true tells the login page that the user has already had a failed login attempt
    $_SESSION['logged_in'] = FALSE;
    header('Location: login.php?redirect="true"');
}
?>