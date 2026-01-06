<?php
    $servername = "localhost";
    $username = "root";
    $password = ""; 
    $dbname = "WEBMXH_Nhom06";
    
   
    $conn = mysqli_connect($servername, $username, $password,$dbname);
    mysqli_set_charset($conn , 'UTF8');
    
    if (!$conn) {
      die("Connection failed: " . mysqli_connect_error());
    }
    
    
?>