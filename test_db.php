<?php
$conn = new mysqli("localhost", "root", "", "nextgenspareslk");
if ($conn->connect_error) {
    die("DB connection failed: " . $conn->connect_error);
} else {
    echo "Database connected successfully!";
}
?>
