<?php

// 3Dcart App privatekey
$privatekey = "XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX";
               
 
// Your database connection 
$con = mysqli_connect("host","user","password","databaseName");
global $con;
if (mysqli_connect_errno()) {
    echo "Failed to connect to MySQL: " . mysqli_connect_error();
    die;
} 
?>
