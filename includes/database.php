<?php
    $servername = "localhost";
    $username = "root";
    $password = ""; 
    $dbname = "webmxh_nhom06";
    
   
    $conn = mysqli_connect($servername, $username, $password,$dbname);
    mysqli_set_charset($conn , 'UTF8MB4');
    
    if (!$conn) {
      die("Connection failed: " . mysqli_connect_error());
    }
    
    
?>