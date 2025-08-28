<?php
session_start();

$servername = "localhost";
$username = "root";  
$password = "";      
$dbname = "nextgenspareslk";


$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) die("Connection failed: ".$conn->connect_error);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Validate password
    if ($password !== $confirm_password) {
        echo "<script>alert('Passwords do not match'); window.history.back();</script>";
        exit;
    }

    
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        echo "<script>alert('Email already registered'); window.history.back();</script>";
        exit;
    }
    $stmt->close();

    
    $password_hash = password_hash($password, PASSWORD_DEFAULT);

    
    $stmt = $conn->prepare("INSERT INTO users (email, password_hash) VALUES (?, ?)");
    $stmt->bind_param("ss", $email, $password_hash);
    if ($stmt->execute()) {
        
        header("Location: signin.html");
        exit;
    } else {
        echo "<script>alert('Error creating account'); window.history.back();</script>";
    }
    $stmt->close();
}

$conn->close();
?>
