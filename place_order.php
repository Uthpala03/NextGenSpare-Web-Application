<?php
session_start();

// Check if user has items in cart and has completed previous steps
if (empty($_SESSION['cart'])) {
    header('Location: cart.php');
    exit;
}

if (empty($_SESSION['checkout']['first_name'])) {
    header('Location: checkout.php?step=1');
    exit;
}

// Include database connection
require_once 'db.php';

try {
    // Get checkout data
    $ch = $_SESSION['checkout'];
    $cart = $_SESSION['cart'];
    
    // Process payment method
    $payment_method = ($_POST['payment_method'] ?? 'COD') === 'CARD' ? 'CARD' : 'COD';
    $card_last4 = null;
    
    if ($payment_method === 'CARD' && !empty($_POST['card_number'])) {
        $n = preg_replace('/\D/', '', $_POST['card_number']);
        if ($n !== '') {
            $card_last4 = substr($n, -4);
        }
    }
    
    // Calculate totals
    $subtotal = 0;
    foreach($cart as $c) {
        $subtotal += $c['price'] * $c['qty'];
    }
    $shipping = 0.00;
    $discount = 0.00;
    $total = $subtotal + $shipping - $discount;
    
    // Start transaction
    $conn->begin_transaction();
    
    // Insert order
    $sql = "INSERT INTO orders 
            (user_id, email, first_name, last_name, phone, address1, address2, city, postal_code,
             compatibility_note, policy_agreed, payment_method, card_last4, subtotal, shipping, discount, total, status, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
    
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    
    $status = ($payment_method === 'COD') ? 'cod_pending' : 'paid_mock';
    $user_id = $_SESSION['user_id'] ?? null;
    $policy_agreed = isset($ch['policy_agreed']) ? 1 : 0;
    
    $stmt->bind_param(
        "isssssssssissdddds",
        $user_id,
        $ch['email'],
        $ch['first_name'],
        $ch['last_name'],
        $ch['phone'],
        $ch['address1'],
        $ch['address2'],
        $ch['city'],
        $ch['postal_code'],
        $ch['compatibility_note'],
        $policy_agreed,
        $payment_method,
        $card_last4,
        $subtotal,
        $shipping,
        $discount,
        $total,
        $status
    );
    
    if (!$stmt->execute()) {
        throw new Exception("Execute failed: " . $stmt->error);
    }
    
    $order_id = $stmt->insert_id;
    $stmt->close();
    
    // Insert order items
    $item_sql = "INSERT INTO order_items (order_id, product_id, product_name, product_image, unit_price, qty) 
                 VALUES (?, ?, ?, ?, ?, ?)";
    $item_stmt = $conn->prepare($item_sql);
    
    if (!$item_stmt) {
        throw new Exception("Item prepare failed: " . $conn->error);
    }
    
    foreach ($cart as $c) {
        $item_stmt->bind_param(
            "isssdi",
            $order_id,
            $c['product_id'],
            $c['product_name'],
            $c['product_image'],
            $c['price'],
            $c['qty']
        );
        
        if (!$item_stmt->execute()) {
            throw new Exception("Item execute failed: " . $item_stmt->error);
        }
    }
    
    $item_stmt->close();
    
    // Commit transaction
    $conn->commit();
    $conn->close();
    
    // Clear cart and partial checkout data
    unset($_SESSION['cart']);
    // Keep checkout data for order success page, but you can clear it if needed
    // unset($_SESSION['checkout']);
    
    // Redirect to success page
    header('Location: order_success.php?id=' . $order_id);
    exit;
    
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    $conn->close();
    
    // Log error (in production, log to file instead of displaying)
    error_log("Order placement error: " . $e->getMessage());
    
    // Redirect back with error
    $_SESSION['error_message'] = "There was an error processing your order. Please try again.";
    header('Location: checkout.php?step=3&error=1');
    exit;
}
?>