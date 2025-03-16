<?php
// Function to establish db connection
// Enter relevant login details in the empty quotes
function connectdb(){
    $db = new PDO('mysql:host=localhost; dbname=syncforge;','','');
    return $db;
}
?>