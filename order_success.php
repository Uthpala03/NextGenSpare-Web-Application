<?php
session_start();

$order_id = (int)($_GET['id'] ?? 0);
if ($order_id <= 0) {
    header('Location: index.php');
    exit;
}

// Optional: Fetch order details from database to display
require_once 'db.php';

$stmt = $conn->prepare("SELECT * FROM orders WHERE id = ?");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$order) {
    header('Location: index.php');
    exit;
}

// Get order items
$items_stmt = $conn->prepare("SELECT * FROM order_items WHERE order_id = ?");
$items_stmt->bind_param("i", $order_id);
$items_stmt->execute();
$items = $items_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$items_stmt->close();
$conn->close();
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Order Confirmation | NextGenSpare.lk</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
<style>
:root{
  --brand:#8a0202;
  --brand-2:#ee5757;
  --ink:#0f172a;
  --muted:#6b7280;
  --line:#e5e7eb;
  --card:#ffffff;
  --success:#16a34a;
}

*{box-sizing:border-box}
html,body{height:100%}
body{
  margin:0;
  font-family:"Poppins",system-ui,-apple-system,"Segoe UI",Roboto,Arial,sans-serif;
  color:var(--ink);
  background:
    linear-gradient(rgba(255,255,255,.84), rgba(255,255,255,.92)),
    radial-gradient(1200px 400px at 50% -10%, rgba(138,2,2,.08), transparent 40%);
}

.shell{ max-width:800px; margin:60px auto; padding:0 20px; }

.success-card{
  background:rgba(255,255,255,.95);
  border:1px solid var(--line);
  border-radius:20px;
  padding:40px;
  box-shadow:0 20px 50px rgba(2,6,23,.12);
  text-align:center;
}

.success-icon{
  width:80px; height:80px; 
  background:var(--success);
  border-radius:50%;
  display:grid; place-items:center;
  margin:0 auto 20px;
  color:#fff; font-size:40px;
}

h1{ color:var(--success); margin-bottom:10px }
.order-number{ font-size:1.2em; color:var(--muted); margin-bottom:30px }

.details{ text-align:left; margin:30px 0; padding:20px; background:#f8fafc; border-radius:12px }
.row{ display:flex; justify-content:space-between; margin:8px 0; padding:8px 0; border-bottom:1px solid #e2e8f0 }
.row:last-child{ border-bottom:none }

.btn{
  display:inline-block; padding:12px 24px; border:none; border-radius:12px;
  text-decoration:none; font-weight:600; cursor:pointer; margin:0 8px;
}
.btn-primary{
  background:linear-gradient(135deg,var(--brand-2),var(--brand));
  color:#fff;
}
.btn-outline{ background:#fff; border:1px solid var(--line); color:var(--ink) }
</style>
</head>
<body>
<div class="shell">
  <div class="success-card">
    <div class="success-icon">✓</div>
    <h1>Order Placed Successfully!</h1>
    <p class="order-number">Order #<?php echo $order_id; ?></p>
    
    <p>Thank you for your order! We've received your order and will process it shortly.</p>
    
    <?php if ($order['payment_method'] === 'COD'): ?>
      <p style="color:var(--brand); font-weight:600;">
        Payment Method: Cash on Delivery<br>
        Please have Rs. <?php echo number_format($order['total'], 2); ?> ready when your order arrives.
      </p>
    <?php else: ?>
      <p style="color:var(--success); font-weight:600;">
        Payment Method: Card Payment (Mock)<br>
        Payment processed successfully.
      </p>
    <?php endif; ?>
    
    <div class="details">
      <h3 style="margin-top:0">Order Details</h3>
      <div class="row">
        <span>Customer:</span>
        <span><?php echo htmlspecialchars($order['first_name'] . ' ' . $order['last_name']); ?></span>
      </div>
      <div class="row">
        <span>Email:</span>
        <span><?php echo htmlspecialchars($order['email']); ?></span>
      </div>
      <div class="row">
        <span>Phone:</span>
        <span><?php echo htmlspecialchars($order['phone']); ?></span>
      </div>
      <div class="row">
        <span>Address:</span>
        <span>
          <?php echo htmlspecialchars($order['address1']); ?>
          <?php if ($order['address2']): ?>, <?php echo htmlspecialchars($order['address2']); ?><?php endif; ?><br>
          <?php echo htmlspecialchars($order['city'] . ' ' . $order['postal_code']); ?>
        </span>
      </div>
      
      <h4>Items Ordered:</h4>
      <?php foreach($items as $item): ?>
        <div class="row">
          <span><?php echo htmlspecialchars($item['product_name']); ?> × <?php echo $item['qty']; ?></span>
          <span>Rs. <?php echo number_format($item['unit_price'] * $item['qty'], 2); ?></span>
        </div>
      <?php endforeach; ?>
      
      <div class="row" style="font-weight:700; font-size:1.1em; border-top:2px solid #cbd5e1; margin-top:10px; padding-top:10px">
        <span>Total:</span>
        <span>Rs. <?php echo number_format($order['total'], 2); ?></span>
      </div>
    </div>
    
    <p style="color:var(--muted); font-size:0.9em">
      You will receive an email confirmation shortly. If you have any questions, please contact our support team.
    </p>
    
    <div style="margin-top:30px">
      <a href="index.php" class="btn btn-primary">Continue Shopping</a>
      <a href="my_orders.php" class="btn btn-outline">View Orders</a>
    </div>
  </div>
</div>
</body>
</html>