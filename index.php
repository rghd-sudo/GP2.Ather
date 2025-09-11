<?php

$host = "localhost";
$username = "root";
$password = "";
$dbname = "athar_graduate";

$conn = new mysqli($host, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
echo "Connected successfully";
?>