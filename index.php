<?php

$conn = new mysqli("localhost", "root", "", "dbag");

// ضبط الترميز
$conn->set_charset("utf8mb4");
if ($conn->connect_error) {
    echo "Connected not successfully!!!";
}

?>