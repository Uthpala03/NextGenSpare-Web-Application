<?php
session_start();
if (empty($_SESSION['cart'])) { header('Location: cart.php'); exit; }

// internal steps: 1=Shipping, 2=Compatibility, 3=Payment
$step = max(1, min(3, (int)($_GET['step'] ?? 1)));
$currentUi = $step + 1; // UI shows 1 Cart, 2 Shipping, 3 Compatibility, 4 Payment

if (!function_exists('val')) {
  function val($k,$d=''){ return htmlspecialchars($_SESSION['checkout'][$k] ?? $d, ENT_QUOTES, 'UTF-8'); }
}
$email = $_SESSION['user_email'] ?? '';

if (!function_exists('stepCls')) {
  function stepCls($idx, $currentUi){
    if ($idx < $currentUi) return 'done active';
    if ($idx === $currentUi) return 'active';
    return '';
  }
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Checkout | NextGenSpare.lk</title>
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
  --dark:#0f172a;
}

/* ---------- Base ---------- */
*{box-sizing:border-box}
html,body{height:100%}
body{
  margin:0;
  font-family:"Poppins",system-ui,-apple-system,"Segoe UI",Roboto,Arial,sans-serif;
  color:var(--ink);
  background:
    linear-gradient(rgba(255,255,255,.84), rgba(255,255,255,.92)),
    radial-gradient(1200px 400px at 50% -10%, rgba(138,2,2,.08), transparent 40%),
    url('bmw11.jpg') center / cover fixed no-repeat; /* optional image */
}

/* ---------- Page Grid (AliExpress-style) ---------- */
.shell{ max-width:1320px; margin:28px auto; padding:0 18px; }
.layout{
  display:grid;
  grid-template-columns: 320px 1fr;   /* left panel + main */
  gap:22px;
}
@media (max-width: 1100px){ .layout{ grid-template-columns: 1fr; } }

/* ---------- Cards ---------- */
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

/* ---------- Assistant Panel (left) ---------- */
.asst-head{ display:flex; align-items:center; gap:10px; margin-bottom:10px }
.logo-badge{
  width:36px; height:36px; border-radius:10px;
  display:grid; place-items:center; color:#fff; font-weight:700;
  background:linear-gradient(135deg,var(--brand-2),var(--brand));
  box-shadow:0 10px 24px rgba(138,2,2,.25);
}
.asst-title{ font-weight:700; font-size:1.05rem }

.pill{
  display:inline-flex; align-items:center; gap:8px;
  padding:6px 10px; border:1px solid var(--line); border-radius:999px;
  background:#fff; font-size:.86rem; color:#0b1220;
}
.pill .dot{
  width:9px; height:9px; border-radius:999px; background:#16a34a; display:inline-block;
}

.asst-section{ margin-top:12px }
.asst-section h4{ margin:0 0 10px; font-size:.95rem; color:#111827 }

input[type="date"], .asst input[type="text"]{
  width:100%; padding:10px 12px; border:1px solid var(--line); border-radius:10px;
  background:#fff; outline:none; transition:border-color .2s, box-shadow .2s;
  font-family:inherit;
}
input[type="date"]:focus, .asst input[type="text"]:focus{
  border-color:var(--brand);
  box-shadow:0 0 0 6px var(--ring);
}

.btn{padding:12px 14px;border:none;border-radius:10px;cursor:pointer;font-weight:600}
.btn-dark{
  background:#111827; color:#fff; width:100%;
  box-shadow:0 8px 20px rgba(2,6,23,.25);
}
.btn-dark:hover{ filter:brightness(1.05) }
.btn-outline{
  width:100%; background:#fff; border:1px solid var(--line); color:#0b1220;
}
.btn-outline:hover{ border-color:#c7ccd7 }
.btn-primary{
  width:100%;
  color:#fff;
  background:linear-gradient(135deg,var(--brand-2),var(--brand));
  box-shadow:0 12px 28px rgba(138,2,2,.28);
}
.asst .row{display:flex; gap:10px}

/* ---------- Main content ---------- */
.progress{
  position:relative;
  display:grid;
  grid-template-columns:repeat(4,1fr);
  gap:12px;
  margin-bottom:18px;
  align-items:center;
}
.progress::before{
  content:""; position:absolute; inset:18px 6px auto 6px; height:2px; background:var(--line);
}
.step{ text-align:center; font-weight:600; color:var(--muted) }
.step .dot{
  width:34px; height:34px; border-radius:999px; display:grid; place-items:center;
  background:#fff; border:2px solid var(--line); margin:0 auto 6px;
}
.step.active{ color:#0b1220 }
.step.active .dot{
  color:#fff; border-color:transparent;
  background:linear-gradient(135deg,var(--brand-2),var(--brand));
  box-shadow:0 10px 26px rgba(138,2,2,.28);
}
.step.done .dot{ background:#16a34a; border-color:#16a34a; color:#fff }
.step span{ font-size:.9rem }

.main-grid{ display:grid; grid-template-columns: 1.6fr 1fr; gap:20px }
@media (max-width: 880px){ .main-grid{ grid-template-columns: 1fr } }

h3{ margin:6px 0 14px }

input,select,textarea{
  width:100%; padding:12px 12px; border:1px solid var(--line); border-radius:12px;
  background:#fff; outline:none; transition:border-color .2s, box-shadow .2s; font-family:inherit;
}
input:focus,select:focus,textarea:focus{ border-color:var(--brand); box-shadow:0 0 0 6px var(--ring) }
label.pay{
  display:flex; align-items:center; gap:10px; padding:12px 14px; border:1px solid var(--line);
  border-radius:14px; background:#fff; margin-bottom:10px; cursor:pointer; transition:.2s ease;
}
label.pay:hover{ border-color:var(--brand) }
label.pay input[type=radio]{ appearance:none; width:18px; height:18px; border:2px solid #cbd5e1; border-radius:50%; margin:0 }
label.pay input[type=radio]:checked{ border-color:var(--brand) }
label.pay input[type=radio]:checked::after{ content:""; width:10px; height:10px; background:var(--brand); border-radius:50%; display:block; margin:2px }
label.pay:has(input[type=radio]:checked){ border-color:var(--brand); box-shadow:0 10px 24px rgba(138,2,2,.10) }

.actions{ display:flex; gap:10px; justify-content:space-between; margin-top:8px }

/* Summary card */
.summary{ position:sticky; top:16px }
.summary .row{ display:flex; justify-content:space-between; margin:.35rem 0; color:#334155 }
.summary .divider{ height:1px; background:var(--line); margin:10px 0 }
.summary .total{ font-weight:800; font-size:1.1rem; color:#0b1220 }
.small{ color:#64748b; font-size:.9rem }
</style>
</head>
<body>
<div class="shell">
  <div class="layout">

    <!-- ========== LEFT: Assistant panel ========== -->
    <aside class="asst card">
      <div class="asst-head">
        <div class="logo-badge">NG</div>
        <div class="asst-title">Checkout Assistant</div>
      </div>
      <div class="pill" title="Progress">
        <span class="dot"></span>
        Step progress: <strong style="margin-left:4px;"><?php echo $step; ?>/3</strong>
      </div>

      <div class="asst-section">
        <h4>Delivery window (optional)</h4>
        <div class="row">
          <input type="date" id="fromDate">
          <input type="date" id="toDate">
        </div>
        <button class="btn btn-dark" onclick="scrollToForm()">Collect Shipping Details</button>
        <button class="btn btn-outline" style="margin-top:8px" onclick="location.href='cart.php'">Back to Cart</button>
      </div>

      <div class="asst-section">
        <h4>Quick Actions</h4>
        <?php if ($step === 1): ?>
          <!-- submit the shipping form on the right -->
          <button class="btn btn-primary" form="form-shipping">Save & Next</button>
        <?php elseif ($step === 2): ?>
          <button class="btn btn-primary" form="form-compat">Next</button>
        <?php else: ?>
          <button class="btn btn-primary" form="form-payment">Place Order</button>
        <?php endif; ?>
      </div>
    </aside>

    <!-- ========== RIGHT: Main content ========== -->
    <main class="content">
      <!-- Top progress -->
      <div class="progress">
        <div class="step <?= stepCls(1,$currentUi) ?>"><div class="dot">1</div><span>Cart</span></div>
        <div class="step <?= stepCls(2,$currentUi) ?>"><div class="dot">2</div><span>Shipping</span></div>
        <div class="step <?= stepCls(3,$currentUi) ?>"><div class="dot">3</div><span>Compatibility</span></div>
        <div class="step <?= stepCls(4,$currentUi) ?>"><div class="dot">4</div><span>Payment</span></div>
      </div>

      <div class="main-grid" id="stepContent">
        <!-- LEFT: step forms -->
        <section class="card">
          <?php if ($step === 1): ?>
            <h3>Billing details and shipping</h3>
            <form id="form-shipping" method="POST" action="save_checkout.php?go=2">
              <div style="display:flex; gap:12px">
                <input name="first_name" placeholder="First name" required value="<?= val('first_name'); ?>">
                <input name="last_name" placeholder="Last name" required value="<?= val('last_name'); ?>">
              </div>
              <div style="display:flex; gap:12px">
                <input type="email" name="email" placeholder="Email" required value="<?= val('email', $email); ?>">
                <input name="phone" placeholder="Phone" required value="<?= val('phone'); ?>">
              </div>
              <input name="address1" placeholder="Street address" required value="<?= val('address1'); ?>">
              <input name="address2" placeholder="Apartment, suite, etc. (optional)" value="<?= val('address2'); ?>">
              <div style="display:flex; gap:12px">
                <input name="city" placeholder="Town/City" required value="<?= val('city'); ?>">
                <input name="postal_code" placeholder="Postal code" required value="<?= val('postal_code'); ?>">
              </div>

              <div class="actions">
                <button class="btn btn-outline" type="button" onclick="location.href='cart.php'">← Back</button>
                <button class="btn btn-primary" type="submit">Save & Next →</button>
              </div>
            </form>

          <?php elseif ($step === 2): ?>
            <h3>Compatibility</h3>
            <form id="form-compat" method="POST" action="save_checkout.php?go=3">
              <label class="small" style="font-weight:600;margin-bottom:6px;display:block">Compatibility / Notes</label>
              <textarea name="compatibility_note" rows="4" placeholder="Ex: BMW 1 Series F20, 2015, 1.6L"><?= val('compatibility_note'); ?></textarea>
              <label style="display:flex;align-items:center;gap:8px;margin:10px 0 14px">
                <input type="checkbox" name="policy_agreed" value="1" <?= val('policy_agreed')?'checked':''; ?>> I agree to the return & privacy policy
              </label>
              <div class="actions">
                <button class="btn btn-outline" type="button" onclick="location.href='checkout.php?step=1'">← Back</button>
                <button class="btn btn-primary" type="submit">Next →</button>
              </div>
            </form>

          <?php else: ?>
            <h3>Payment Method</h3>
            <form id="form-payment" method="POST" action="place_order.php">
              <label class="pay">
                <input type="radio" name="payment_method" value="COD" <?= val('payment_method','COD')==='COD'?'checked':''; ?>>
                Cash on Delivery (COD)
              </label>
              <label class="pay">
                <input type="radio" name="payment_method" value="CARD" <?= val('payment_method')==='CARD'?'checked':''; ?>>
                Credit/Debit Card (mock)
              </label>

              <div id="cardFields" style="display:<?= val('payment_method')==='CARD'?'block':'none'; ?>;margin-top:8px">
                <input name="card_name" placeholder="Name on Card">
                <div style="display:flex; gap:12px">
                  <input name="card_number" placeholder="Card Number">
                  <input name="card_exp" placeholder="MM/YY">
                  <input name="card_cvc" placeholder="CVC">
                </div>
              </div>

              <div class="actions">
                <button class="btn btn-outline" type="button" onclick="location.href='checkout.php?step=2'">← Back</button>
                <button class="btn btn-line" type="submit" style="background:#002f5f;color:#fff">Place Order</button>
              </div>
            </form>
            <script>
              // Show/hide card fields
              document.querySelectorAll('input[name=payment_method]').forEach(r=>{
                r.addEventListener('change', ()=> {
                  document.getElementById('cardFields').style.display = (r.value==='CARD')?'block':'none';
                });
              });
            </script>
          <?php endif; ?>
        </section>

        <!-- RIGHT: Order Summary -->
        <aside class="card summary">
          <h3>Order Summary</h3>
          <?php
            $cart = $_SESSION['cart'];
            $subtotal=0; foreach($cart as $c){ $subtotal += $c['price']*$c['qty']; }
            $shipping=0.00; $discount=0.00; $total=$subtotal+$shipping-$discount;
          ?>
          <?php foreach($cart as $c): ?>
            <div class="row" style="justify-content:space-between">
              <small><?php echo htmlspecialchars($c['product_name']); ?> × <?php echo (int)$c['qty']; ?></small>
              <small>Rs. <?php echo number_format($c['price']*$c['qty'],2); ?></small>
            </div>
          <?php endforeach; ?>
          <div class="divider"></div>
          <div class="row" style="justify-content:space-between"><span class="small">Subtotal</span><span>Rs. <?php echo number_format($subtotal,2); ?></span></div>
          <div class="row" style="justify-content:space-between"><span class="small">Shipping</span><span>Rs. <?php echo number_format($shipping,2); ?></span></div>
          <div class="row" style="justify-content:space-between"><span class="small">Discount</span><span>Rs. <?php echo number_format($discount,2); ?></span></div>
          <div class="row total" style="justify-content:space-between;margin-top:6px"><span>Total</span><span>Rs. <?php echo number_format($total,2); ?></span></div>
          <div style="margin-top:12px;text-align:right">
            <button class="btn btn-outline" onclick="location.href='cart.php'">Edit Cart</button>
          </div>
        </aside>
      </div>
    </main>
  </div>
</div>

<script>
  function scrollToForm(){
    const el = document.getElementById('stepContent');
    if(el){ el.scrollIntoView({behavior:'smooth', block:'start'}); }
  }
</script>
</body>
</html>
