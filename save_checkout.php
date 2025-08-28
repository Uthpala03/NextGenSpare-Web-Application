<?php
session_start();


if (empty($_SESSION['cart'])) {
    header('Location: cart.php');
    exit;
}


$go = max(1, min(3, (int)($_GET['go'] ?? 1)));


if (!isset($_SESSION['checkout'])) {
    $_SESSION['checkout'] = [];
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    if ($go === 2) {
        
        $_SESSION['checkout']['first_name'] = trim($_POST['first_name'] ?? '');
        $_SESSION['checkout']['last_name'] = trim($_POST['last_name'] ?? '');
        $_SESSION['checkout']['email'] = trim($_POST['email'] ?? '');
        $_SESSION['checkout']['phone'] = trim($_POST['phone'] ?? '');
        $_SESSION['checkout']['address1'] = trim($_POST['address1'] ?? '');
        $_SESSION['checkout']['address2'] = trim($_POST['address2'] ?? '');
        $_SESSION['checkout']['city'] = trim($_POST['city'] ?? '');
        $_SESSION['checkout']['postal_code'] = trim($_POST['postal_code'] ?? '');
        
        
        if (empty($_SESSION['checkout']['first_name']) || 
            empty($_SESSION['checkout']['last_name']) || 
            empty($_SESSION['checkout']['email']) || 
            empty($_SESSION['checkout']['phone']) || 
            empty($_SESSION['checkout']['address1']) || 
            empty($_SESSION['checkout']['city']) || 
            empty($_SESSION['checkout']['postal_code'])) {
            
            $_SESSION['error_message'] = "Please fill in all required fields.";
            header('Location: checkout.php?step=1&error=1');
            exit;
        }
        
        
        if (!filter_var($_SESSION['checkout']['email'], FILTER_VALIDATE_EMAIL)) {
            $_SESSION['error_message'] = "Please enter a valid email address.";
            header('Location: checkout.php?step=1&error=1');
            exit;
        }
        
    } elseif ($go === 3) {
        
        $_SESSION['checkout']['compatibility_note'] = trim($_POST['compatibility_note'] ?? '');
        $_SESSION['checkout']['policy_agreed'] = isset($_POST['policy_agreed']) ? 1 : 0;
        
        
        if (!$_SESSION['checkout']['policy_agreed']) {
            $_SESSION['error_message'] = "Please agree to the return & privacy policy to continue.";
            header('Location: checkout.php?step=2&error=1');
            exit;
        }
    }
}


header('Location: checkout.php?step=' . $go);
exit;
?>