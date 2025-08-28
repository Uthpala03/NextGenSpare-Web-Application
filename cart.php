<?php
session_start();
if (!isset($_SESSION['cart'])) { $_SESSION['cart'] = []; }

// --- Handle POST actions (add / update qtys / remove line) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? '';

  // ✅ ADD TO CART
  if ($action === 'add') {
    $id    = $_POST['product_id'] ?? '';
    $name  = $_POST['product_name'] ?? 'Item';
    $img   = $_POST['product_image'] ?? '';
    $price = (float)($_POST['price'] ?? 0);
    $qty   = max(1, (int)($_POST['qty'] ?? 1));

    // Check if item already exists
    $found = false;
    foreach ($_SESSION['cart'] as &$item) {
      if ($item['product_id'] === $id) {
        $item['qty'] += $qty;
        $found = true;
        break;
      }
    }
    unset($item);

    // If new item, push to cart
    if (!$found) {
      $_SESSION['cart'][] = [
        'product_id'    => $id,
        'product_name'  => $name,
        'product_image' => $img,
        'price'         => $price,
        'qty'           => $qty
      ];
    }

    header('Location: cart.php'); exit;
  }

  // ✅ UPDATE CART
  if ($action === 'update' && isset($_POST['row'], $_POST['qty'])) {
    foreach ((array)$_POST['row'] as $i => $rowIndex) {
      $rowIndex = (int)$rowIndex;
      $newQty   = (int)($_POST['qty'][$i] ?? 1);
      $newQty   = max(1, min(99, $newQty));
      if (isset($_SESSION['cart'][$rowIndex])) {
        $_SESSION['cart'][$rowIndex]['qty'] = $newQty;
      }
    }
    header('Location: cart.php'); exit;
  }

  // ✅ REMOVE ITEM
  if ($action === 'remove' && isset($_POST['row'])) {
    $rowIndex = (int)$_POST['row'];
    if (isset($_SESSION['cart'][$rowIndex])) {
      unset($_SESSION['cart'][$rowIndex]);
      $_SESSION['cart'] = array_values($_SESSION['cart']); // reindex rows
    }
    header('Location: cart.php'); exit;
  }
}

// --- Totals ---
$cart = $_SESSION['cart'];
$subtotal = 0.0;
foreach ($cart as $item) { $subtotal += ((float)$item['price']) * ((int)$item['qty']); }
$shipping = 0.00;
$discount = 0.00;
$total    = $subtotal + $shipping - $discount;

function money($n){ return number_format((float)$n, 2); }
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Cart | NextGenSpare.lk</title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
<style>
:root{
  --brand:#8a0202;
  --brand-2:#ee5757;
  --ink:#0f172a;
  --muted:#6b7280;
  --line:#e5e7eb;
  --card:#ffffff;
  --ring:rgba(138,2,2,.18);
}
*{box-sizing:border-box}
html,body{height:100%}
body{
  margin:0;
  font-family:"Poppins",system-ui,-apple-system,"Segoe UI",Roboto,Arial,sans-serif;
  color:var(--ink);
  background:
    linear-gradient(rgba(255,255,255,.86), rgba(255,255,255,.94)),
    radial-gradient(1200px 420px at 50% -10%, rgba(138,2,2,.08), transparent 40%),
    url('bmw11.jpg') center / cover fixed no-repeat;
}
.topbar{
  background:#0b1220;
  color:#fff;
  padding:10px 0;
  box-shadow:0 6px 16px rgba(2,6,23,.25);
}
.topbar .inner{ max-width:1280px; margin:0 auto; padding:0 18px; display:flex; align-items:center; justify-content:space-between }
.brand{ display:flex; align-items:center; gap:10px; font-weight:700; }
.brand .logo{
  width:34px; height:34px; border-radius:10px; display:grid; place-items:center; color:#fff;
  background:linear-gradient(135deg,var(--brand-2),var(--brand));
  box-shadow:0 10px 22px rgba(138,2,2,.3);
}
.shell{ max-width:1280px; margin:26px auto; padding:0 18px }
.grid{ display:grid; grid-template-columns: 1.7fr 1fr; gap:20px }
@media (max-width: 980px){ .grid{ grid-template-columns: 1fr } }
.card{
  background:rgba(255,255,255,.95);
  border:1px solid var(--line);
  border-radius:16px;
  padding:18px;
  box-shadow:0 18px 40px rgba(2,6,23,.10);
  backdrop-filter:saturate(1.05) blur(4px);
  transition:transform .2s ease, box-shadow .2s ease;
}
.card:hover{ transform:translateY(-1px); box-shadow:0 22px 54px rgba(2,6,23,.14) }
.list-head, .line{ display:grid; grid-template-columns: 1fr 120px 120px 120px; gap:12px; align-items:center; }
.list-head{ color:#334155; font-weight:600; border-bottom:1px solid var(--line); padding-bottom:10px; }
.line{ padding:14px 0; border-bottom:1px solid #f0f2f6 }
.item{ display:flex; align-items:center; gap:12px; }
.item img{
  width:68px; height:68px; object-fit:cover; border-radius:10px; border:1px solid #edf0f6;
  background:#fff;
}
.name{ font-weight:600 }
.meta{ font-size:.9rem; color:#6b7280 }
.qty{ display:flex; align-items:center; gap:8px; }
.qty input{
  width:72px; padding:10px 10px; border:1px solid var(--line); border-radius:10px; text-align:center;
  font-family:inherit; outline:none; transition:border-color .2s, box-shadow .2s;
}
.qty input:focus{ border-color:var(--brand); box-shadow:0 0 0 6px var(--ring) }
.btn{ padding:10px 12px; border:none; border-radius:10px; font-weight:600; cursor:pointer }
.btn-ghost{ background:#fff; border:1px solid var(--line); color:#0b1220 }
.btn-ghost:hover{ border-color:#c7ccd7 }
.btn-danger{ background:#991b1b; color:#fff }
.btn-gradient{
  color:#fff; background:linear-gradient(135deg,var(--brand-2),var(--brand));
  box-shadow:0 12px 28px rgba(138,2,2,.28);
}
.actions{ display:flex; gap:10px; justify-content:flex-end; margin-top:12px }
.summary{ position:sticky; top:16px }
.summary h3{ margin:6px 0 12px }
.summary .row{ display:flex; justify-content:space-between; margin:.35rem 0; color:#334155 }
.summary .divider{ height:1px; background:var(--line); margin:10px 0 }
.summary .total{ font-weight:800; font-size:1.12rem }
.small{ color:#6b7280; font-size:.92rem }
.empty{ text-align:center; padding:40px 10px; color:#6b7280; }
.empty .btn{ margin-top:10px }
</style>
</head>
<body>

<!-- Top bar -->
<header class="topbar">
  <div class="inner">
    <div class="brand">
      <div class="logo">NG</div>
      <div>NextGenSpare.lk</div>
    </div>
    <button class="btn btn-ghost" onclick="location.href='index.html'">← Continue Shopping</button>
  </div>
</header>

<div class="shell">
  <div class="grid">

    <!-- LEFT: Cart Lines -->
    <section class="card">
      <h3 style="margin:4px 0 12px">Your Cart</h3>

      <?php if (empty($cart)): ?>
        <div class="empty">
          Your cart is empty. Let’s find the right parts for your vehicle.
          <div><button class="btn btn-gradient" onclick="location.href='index.html'">Browse Products</button></div>
        </div>
      <?php else: ?>

      <form method="POST">
        <div class="list-head">
          <div>Product</div>
          <div style="text-align:right">Price</div>
          <div style="text-align:center">Qty</div>
          <div style="text-align:right">Line Total</div>
        </div>

        <?php foreach ($cart as $i => $c): 
          $name = htmlspecialchars($c['product_name'] ?? 'Item');
          $img  = htmlspecialchars($c['product_image'] ?? '');
          $price= (float)($c['price'] ?? 0);
          $qty  = (int)($c['qty'] ?? 1);
          $line = $price * $qty;
        ?>
        <div class="line">
          <div class="item">
            <?php if ($img): ?><img src="<?php echo $img; ?>" alt="product"><?php endif; ?>
            <div>
              <div class="name"><?php echo $name; ?></div>
              <?php if (!empty($c['product_id'])): ?>
              <div class="meta">Code: <?php echo htmlspecialchars($c['product_id']); ?></div>
              <?php endif; ?>
            </div>
          </div>

          <div style="text-align:right">Rs. <?php echo money($price); ?></div>

          <div class="qty" style="justify-content:center">
            <input type="hidden" name="row[]" value="<?php echo (int)$i; ?>">
            <input type="number" min="1" max="99" name="qty[]" value="<?php echo (int)$qty; ?>">
          </div>

          <div style="text-align:right">
            Rs. <?php echo money($line); ?>
            <div>
              <form method="POST" style="display:inline">
                <input type="hidden" name="action" value="remove">
                <input type="hidden" name="row" value="<?php echo (int)$i; ?>">
                <button class="btn btn-danger" type="submit" style="margin-top:8px">Remove</button>
              </form>
            </div>
          </div>
        </div>
        <?php endforeach; ?>

        <div class="actions">
          <button class="btn btn-ghost" type="button" onclick="location.href='index.html'">← Continue Shopping</button>
          <input type="hidden" name="action" value="update">
          <button class="btn btn-gradient" type="submit">Update Cart</button>
        </div>
      </form>

      <?php endif; ?>
    </section>

    <!-- RIGHT: Summary -->
    <aside class="card summary">
      <h3>Order Summary</h3>

      <?php if (!empty($cart)): ?>
        <?php foreach($cart as $c): ?>
          <div class="row">
            <span class="small"><?php echo htmlspecialchars($c['product_name']); ?> × <?php echo (int)$c['qty']; ?></span>
            <span class="small">Rs. <?php echo money($c['price']*$c['qty']); ?></span>
          </div>
        <?php endforeach; ?>
        <div class="divider"></div>
      <?php else: ?>
        <div class="small">No items yet.</div>
        <div class="divider"></div>
      <?php endif; ?>

      <div class="row"><span class="small">Subtotal</span><span>Rs. <?php echo money($subtotal); ?></span></div>
      <div class="row"><span class="small">Shipping</span><span>Rs. <?php echo money($shipping); ?></span></div>
      <div class="row"><span class="small">Discount</span><span>Rs. <?php echo money($discount); ?></span></div>
      <div class="row total" style="margin-top:6px"><span>Total</span><span>Rs. <?php echo money($total); ?></span></div>

      <div class="actions" style="margin-top:12px">
        <button class="btn btn-ghost" onclick="location.href='index.html'">Continue Shopping</button>
        <button class="btn btn-gradient" onclick="location.href='checkout.php?step=1'">Checkout →</button>
      </div>
    </aside>

  </div>
</div>

</body>
</html>
