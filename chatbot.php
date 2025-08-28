<?php
session_start();
header('Content-Type: application/json');

// Include database connection
require_once 'db.php';

// Get the user message
$input = json_decode(file_get_contents('php://input'), true);
$userMessage = trim($input['message'] ?? '');
$sessionId = $input['session_id'] ?? session_id();

if (empty($userMessage)) {
    echo json_encode(['error' => 'No message provided']);
    exit;
}

// Store user message in database
storeMessage($conn, $sessionId, 'user', $userMessage);

// Process the message and get bot response
$botResponse = processMessage($userMessage, $conn);

// Store bot response in database
storeMessage($conn, $sessionId, 'bot', $botResponse);

// Return response
echo json_encode([
    'response' => $botResponse,
    'session_id' => $sessionId
]);

function storeMessage($conn, $sessionId, $sender, $message) {
    $stmt = $conn->prepare("INSERT INTO chat_messages (session_id, sender, message, created_at) VALUES (?, ?, ?, NOW())");
    $stmt->bind_param("sss", $sessionId, $sender, $message);
    $stmt->execute();
    $stmt->close();
}

function processMessage($message, $conn) {
    $message = strtolower($message);
    
    // Greeting responses
    if (preg_match('/\b(hi|hello|hey|good morning|good afternoon|good evening)\b/', $message)) {
        return "Hello! Welcome to NextGenSpare.lk! 🚗 I'm here to help you find genuine parts for your luxury vehicle. Which brand are you looking for? (BMW, Mercedes, Audi, Land Rover, or Porsche)";
    }
    
    // Brand-specific responses
    if (preg_match('/\bbmw\b/', $message)) {
        return "Great choice! BMW parts available 🔧 We have genuine parts for all BMW models. What specific part do you need? (Engine, Brakes, Filters, Bearings, etc.) Or tell me your BMW model (e.g., X5, 3 Series, 5 Series)";
    }
    
    if (preg_match('/\bmercedes\b|\bbenz\b/', $message)) {
        return "Mercedes-Benz parts in stock! ⭐ We specialize in genuine MB components. What part are you looking for? Or which Mercedes model do you have? (C-Class, E-Class, S-Class, GLC, etc.)";
    }
    
    if (preg_match('/\baudi\b/', $message)) {
        return "Audi parts available! 🎯 We stock OEM parts for all Audi models. Which part do you need? Or tell me your Audi model (A3, A4, A6, Q5, Q7, etc.)";
    }
    
    if (preg_match('/\bland rover\b|\brange rover\b/', $message)) {
        return "Land Rover parts in stock! 🏔️ Premium parts for Discovery, Defender, Range Rover models. What specific component are you looking for?";
    }
    
    if (preg_match('/\bporsche\b/', $message)) {
        return "Porsche parts available! 🏁 High-performance genuine parts for 911, Cayenne, Macan, Panamera. Which part do you need?";
    }
    
    // Part categories
    if (preg_match('/\bengine\b|\bmotor\b/', $message)) {
        return "Engine parts available! 🔧 We have: Oil filters, Air filters, Spark plugs, Engine oil, Timing belts, Water pumps, Gaskets. What specific engine component do you need?";
    }
    
    if (preg_match('/\bbrake\b|\bbraking\b/', $message)) {
        return "Brake system parts in stock! 🛑 Available: Brake pads, Brake discs, Brake fluid, Brake calipers, ABS sensors. Which brake component do you need?";
    }
    
    if (preg_match('/\bfilter\b/', $message)) {
        return "Filter selection available! 🌀 We stock: Oil filters, Air filters, Fuel filters, Cabin filters, Hydraulic filters. Which type of filter do you need?";
    }
    
    if (preg_match('/\bbearing\b/', $message)) {
        return "Bearing parts available! ⚙️ We have: Wheel bearings, Engine bearings, Transmission bearings. Hub assemblies also in stock. What type of bearing do you need?";
    }
    
    if (preg_match('/\boil\b|\bfluid\b/', $message)) {
        return "Oils & fluids in stock! 🛢️ Available: Engine oil, Transmission fluid, Brake fluid, Power steering fluid, Coolant. What type of fluid do you need?";
    }
    
    // Price inquiries
    if (preg_match('/\bprice\b|\bcost\b|\bhow much\b/', $message)) {
        return "For accurate pricing, please specify: 1) Vehicle brand & model, 2) Year, 3) Specific part needed. You can also browse our website or call +94 77 123 4567 for instant quotes! 💰";
    }
    
    // Compatibility questions
    if (preg_match('/\bcompatible\b|\bfit\b|\bwork with\b/', $message)) {
        return "Part compatibility is crucial! 🔍 Please share: 1) Your vehicle make & model, 2) Year of manufacture, 3) Engine size. Our experts will confirm compatibility before shipping!";
    }
    
    // Delivery questions
    if (preg_match('/\bdelivery\b|\bshipping\b|\bhow long\b/', $message)) {
        return "Delivery info 🚚: Colombo & suburbs: 1-2 days | Other areas: 2-3 days | Express delivery available. Free shipping on orders above Rs. 15,000!";
    }
    
    // Payment questions
    if (preg_match('/\bpayment\b|\bpay\b|\bcash\b|\bcard\b/', $message)) {
        return "Payment options 💳: Cash on Delivery (COD), Credit/Debit Cards, Bank Transfer. Secure checkout with 100% buyer protection!";
    }
    
    // Warranty questions
    if (preg_match('/\bwarranty\b|\bguarantee\b/', $message)) {
        return "Warranty coverage 🛡️: All genuine parts come with manufacturer warranty. OEM parts: 12-24 months. Premium aftermarket: 6-12 months. Installation warranty available!";
    }
    
    // Order status
    if (preg_match('/\border\b|\btrack\b|\bstatus\b/', $message)) {
        return "Track your order! 📦 Please provide your order number or email address. You can also check order status in 'My Account' section.";
    }
    
    // Contact information
    if (preg_match('/\bcontact\b|\bphone\b|\bcall\b/', $message)) {
        return "Contact us 📞: Phone: +94 77 123 4567 | WhatsApp: +94 77 123 4567 | Email: info@nextgenspare.lk | Live chat: Available 9AM-8PM";
    }
    
    // Returns/exchanges
    if (preg_match('/\breturn\b|\bexchange\b|\brefund\b/', $message)) {
        return "Return policy 🔄: 7-day return policy on unused parts. Original packaging required. Free returns for wrong/defective items. Refund processed within 3-5 days.";
    }
    
    // Installation questions
    if (preg_match('/\binstall\b|\bfitting\b|\bmechanic\b/', $message)) {
        return "Installation support 🔧: We can recommend trusted mechanics in your area. Installation guides available. Some complex parts include free consultation!";
    }
    
    // Availability questions
    if (preg_match('/\bavailable\b|\bin stock\b|\bhave\b/', $message)) {
        return "Stock availability varies by part 📋. Most common parts are in stock. For specific availability, please share the part name/number and your vehicle details.";
    }
    
    // Thanks and goodbye
    if (preg_match('/\bthank\b|\bthanks\b|\bbye\b|\bgoodbye\b/', $message)) {
        return "You're welcome! 😊 Feel free to ask anytime. For immediate assistance, call +94 77 123 4567. Happy motoring with NextGenSpare.lk! 🚗💨";
    }
    
    // Help or confused responses
    if (preg_match('/\bhelp\b|\bconfused\b|\bdon\'t know\b/', $message)) {
        return "I'm here to help! 🤝 You can ask me about: \n• Parts for BMW, Mercedes, Audi, Land Rover, Porsche \n• Pricing & availability \n• Delivery & payment options \n• Order tracking \n• Returns & warranty \nWhat would you like to know?";
    }
    
    // Default response with suggestions
    return "I understand you're looking for auto parts! 🔍 To help you better, could you tell me: \n\n1️⃣ Which vehicle brand? (BMW, Mercedes, Audi, etc.) \n2️⃣ What part do you need? \n3️⃣ Your vehicle model & year? \n\nOr try asking about: prices, delivery, warranty, or contact info!";
}

$conn->close();
?>