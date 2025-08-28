<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: signin.html");
    exit;
}

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "nextgenspareslk";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) die("Connection failed: ".$conn->connect_error);

// Handle form update (Full Name + Mobile Number)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_name = trim($_POST['full_name'] ?? '');
    $new_mobile = trim($_POST['mobile_number'] ?? '');

    $stmt = $conn->prepare("UPDATE users SET full_name = ?, mobile_number = ? WHERE id = ?");
    $stmt->bind_param("ssi", $new_name, $new_mobile, $_SESSION['user_id']);
    $stmt->execute();
    $stmt->close();

    // Refresh page to show updated data
    header("Location: myaccount.php");
    exit;
}

// Fetch user data
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT email, full_name, mobile_number, created_at, role FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($email, $full_name, $mobile_number, $created_at, $role);
$stmt->fetch();
$stmt->close();
$conn->close();
?>


<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>My Account | NextGenSpare.lk</title>
<link rel="stylesheet" href="style.css">
<link rel="stylesheet" href="chatbot.css">
<link href="https://fonts.googleapis.com/css2?family=Roboto&family=Poppins&display=swap" rel="stylesheet">
<style>
.account-container { max-width: 800px; margin: 50px auto; font-family: 'Poppins', sans-serif; padding: 20px; background: #fff; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.1); }
.account-container h2 { color: #8a0202; margin-bottom: 20px; }
.account-field { margin-bottom: 15px; }
.account-field label { font-weight: bold; display: block; margin-bottom: 5px; }
.account-field span { display: block; padding: 10px; background: #f1f1f1; border-radius: 6px; }
.logout-btn { display: inline-block; padding: 10px 20px; background: linear-gradient(to right, #ee5757, #8a0202); color: #fff; border-radius: 8px; text-decoration: none; font-weight: bold; margin-top: 20px; }
.logout-btn:hover { background: linear-gradient(135deg, #615f5f, #000); }
input.update-name { width: 100%; padding: 10px; font-size: 1rem; border-radius: 6px; border: 1px solid #ccc; margin-bottom: 10px; }
button.save-btn { padding: 10px 20px; background: #8a0202; color: #fff; border: none; border-radius: 6px; cursor: pointer; font-weight: bold; }
button.save-btn:hover { background: #ee5757; }
</style>
</head>
<body>
      <!-- Navbar -->
  <header class="navbar">
    <img src="newLogo.png" alt="Logo" class="logo">
    <div class="search-container">
      <input type="text" placeholder="Search" class="search-bar">
      <button class="search-button">Search</button>
    </div>
    <div class="navbar-buttons">
      <button onclick="location.href='myaccount.php'">
        <img src="user.png" alt="User Icon" style="width:20px; height:20px; vertical-align:middle; margin-right:5px;">
        My Account </button>
      <button onclick="location.href='myaccount.html'">
        <img src="garage.png" alt="garage Icon"
          style="width:20px; height:20px; vertical-align:middle; margin-right:5px;">
        My Garage </button>

    </div>
    <div class="menu-toggle" onclick="document.querySelector('.desktop-menu').classList.toggle('show')">
      <div></div>
      <div></div>
      <div></div>
    </div>
  </header>

  <div class="desktop-menu">
    <a href="home.html">Home</a>
    <a href="Vehicle.html">Vehicles</a>
    <a href="category.html">Categories</a>
    <a href="articlesPage.html">Articles</a>
    <a href="reviwes.html">Reviews</a>
    <a href="signin.html">Sign In</a>
    <a href="garage.html">My Garage</a>
  </div>


<div class="account-container">
    <h2>My Account</h2>

    <form method="POST" action="myaccount.php">
        <div class="account-field">
            <label>Full Name:</label>
            <input type="text" name="full_name" class="update-name" value="<?php echo htmlspecialchars($full_name ?? ''); ?>" placeholder="Enter your full name">
        </div>

        <div class="account-field">
            <label>Mobile Number:</label>
            <input type="text" name="mobile_number" class="update-name" value="<?php echo htmlspecialchars($mobile_number ?? ''); ?>" placeholder="Enter your mobile number">
        </div>

        <button type="submit" class="save-btn">Save Details</button>
    </form>
<br><br>
    <div class="account-field">
        <label>Email:</label>
        <span><?php echo htmlspecialchars($email); ?></span>
    </div>
    <div class="account-field">
        <label>Role:</label>
        <span><?php echo htmlspecialchars($role); ?></span>
    </div>
    <div class="account-field">
        <label>Account Created:</label>
        <span><?php echo htmlspecialchars($created_at); ?></span>
    </div>

    <a href="logout.php" class="logout-btn">Logout</a>
</div>

  <section class="section">
    <h1>Welcome to <span class="highlight-red">NextGenSpare.lk</span><span class="highlight-gray">- The Future of Luxury
        Auto Parts</span> </h1>
    <p>
      NextGenSpare.lk is your trusted destination for premium, 100% genuine spare parts tailored exclusively
      for luxury vehicles including BMW, Mercedes-Benz, Land Rover, Audi, and Porsche. Designed to deliver
      both performance and peace of mind, our platform offers a seamless browsing experience with vehicle-specific
      search and expertly categorized parts—ranging from engine systems and air conditioning to oils, filters,
      and interior comfort. With transparent pricing, expert support, and secure payment options, we make it
      easier than ever to maintain your luxury vehicle with OEM-certified quality and unmatched reliability.</p>
  </section>

  <!-- Chatbot Icon -->
  <div class="chatbot-icon" onclick="toggleChatbot()">
    <img src="robot.png" alt="Chatbot" />
  </div>

  <!-- Chatbot Popup Box -->
  <div class="chatbox-container" id="chatbox">
    <div class="chatbox-header">
      <span>Chat with Us</span>
      <button onclick="toggleChatbot()">✖</button>
    </div>
    <div class="chatbox-messages" id="chat-messages">
      <div class="bot-message">Hi! How can we help you today?</div>
    </div>
    <div class="chatbox-input">
      <input type="text" id="userInput" placeholder="Type your message..." />
      <button onclick="sendMessage()">Send</button>
    </div>
  </div>

  <script>
    function toggleChatbot() {
      const chatbox = document.getElementById("chatbox");
      chatbox.style.display = chatbox.style.display === "flex" ? "none" : "flex";
    }

    function sendMessage() {
      const input = document.getElementById("userInput");
      const message = input.value.trim();
      if (message === "") return;

      const messagesContainer = document.getElementById("chat-messages");

      // Create and append user message
      const userMsg = document.createElement("div");
      userMsg.className = "user-message";
      userMsg.textContent = message;
      messagesContainer.appendChild(userMsg);

      // Scroll to bottom
      messagesContainer.scrollTop = messagesContainer.scrollHeight;

      // Clear input
      input.value = "";

      // Simulate bot reply
      setTimeout(() => {
        const botMsg = document.createElement("div");
        botMsg.className = "bot-message";
        botMsg.textContent = "Thanks for your message. We'll get back shortly!";
        messagesContainer.appendChild(botMsg);
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
      }, 1000);
    }

  </script>

  <!-- Footer -->
  <footer>
    <div class="footer-section logo-section">
      <img src="newLogo.png" class="footer-logo" alt="Logo" />
      <p class="footer-tagline">The Future of Luxury Auto Parts.</p>
    </div>

    <div class="footer-section">
      <h4>About</h4>
      <a href="about.html">About us</a>
      <a href="contact.html">Contact us</a>
      <a href="Invenstor-Relations.html">Investor Relations</a>
      <a href="suppliers.html">Suppliers Relations</a>
      <a href="Discovery.html">Discovery Points</a>
    </div>

    <div class="footer-section">
      <h4>Policy</h4>
      <a href="return.html">Return Policy</a>
      <a href="Privacy.html">Privacy Policy</a>
      <a href="Terms.html">Terms of Use</a>
    </div>

    <div class="footer-section">
      <h4>Useful Links</h4>
      <a href="home.html">Home</a>
      <a href="#articles">Articles</a>
      <a href="#vehicles">Vehicles</a>
      <a href="#Category">Categories</a>
      <a href="#offers">Best Offers</a>
    </div>
  </footer>
  <div class="footer-bottom-bar">
    © 2025 NextGenSpare.lk (v7.3.7 build 250715.1409)
  </div>
</body>

</html>
</body>
</html>
