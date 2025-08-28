// Enhanced Chatbot Functionality
let sessionId = generateSessionId();
let isTyping = false;

function generateSessionId() {
    return 'chat_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
}

function toggleChatbot() {
    const chatbox = document.getElementById("chatbox");
    const isVisible = chatbox.style.display === "flex";
    
    if (isVisible) {
        chatbox.style.display = "none";
    } else {
        chatbox.style.display = "flex";
        // Focus on input when opening
        setTimeout(() => {
            document.getElementById("userInput").focus();
        }, 100);
    }
}

function sendMessage() {
    const input = document.getElementById("userInput");
    const message = input.value.trim();
    
    if (message === "" || isTyping) return;

    const messagesContainer = document.getElementById("chat-messages");

    // Create and append user message
    const userMsg = document.createElement("div");
    userMsg.className = "user-message";
    userMsg.textContent = message;
    messagesContainer.appendChild(userMsg);

    // Scroll to bottom
    scrollToBottom();

    // Clear input
    input.value = "";

    // Show typing indicator
    showTypingIndicator();

    // Send message to backend
    fetch('chatbot.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            message: message,
            session_id: sessionId
        })
    })
    .then(response => response.json())
    .then(data => {
        hideTypingIndicator();
        
        if (data.error) {
            showBotMessage("Sorry, I'm having trouble processing your message. Please try again.");
        } else {
            showBotMessage(data.response);
            
            // Add quick action buttons for relevant responses
            addQuickActions(message, data.response);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        hideTypingIndicator();
        showBotMessage("Sorry, I'm experiencing technical difficulties. Please try again later or contact us directly.");
    });
}

function showBotMessage(message) {
    const messagesContainer = document.getElementById("chat-messages");
    const botMsg = document.createElement("div");
    botMsg.className = "bot-message";
    
    // Handle line breaks in bot messages
    botMsg.innerHTML = message.replace(/\n/g, '<br>');
    
    messagesContainer.appendChild(botMsg);
    scrollToBottom();
}

function showTypingIndicator() {
    if (isTyping) return;
    
    isTyping = true;
    const messagesContainer = document.getElementById("chat-messages");
    
    const typingDiv = document.createElement("div");
    typingDiv.className = "bot-message typing-indicator";
    typingDiv.id = "typing-indicator";
    typingDiv.innerHTML = '<span>NextGenSpare is typing</span><div class="typing-dots"><div></div><div></div><div></div></div>';
    
    messagesContainer.appendChild(typingDiv);
    scrollToBottom();
}

function hideTypingIndicator() {
    isTyping = false;
    const typingIndicator = document.getElementById("typing-indicator");
    if (typingIndicator) {
        typingIndicator.remove();
    }
}

function scrollToBottom() {
    const messagesContainer = document.getElementById("chat-messages");
    messagesContainer.scrollTop = messagesContainer.scrollHeight;
}

function addQuickActions(userMessage, botResponse) {
    const messagesContainer = document.getElementById("chat-messages");
    const message = userMessage.toLowerCase();
    
    let buttons = [];
    
    // Add relevant quick action buttons based on conversation
    if (message.includes('bmw') || botResponse.includes('BMW')) {
        buttons.push('BMW Parts', 'BMW Models');
    }
    if (message.includes('mercedes') || botResponse.includes('Mercedes')) {
        buttons.push('Mercedes Parts', 'Mercedes Models');
    }
    if (message.includes('price') || message.includes('cost')) {
        buttons.push('Get Quote', 'Compare Prices');
    }
    if (message.includes('delivery') || message.includes('shipping')) {
        buttons.push('Track Order', 'Delivery Areas');
    }
    if (message.includes('help') || message.includes('contact')) {
        buttons.push('Call Us', 'WhatsApp', 'Email Support');
    }
    
    // General helpful buttons if no specific context
    if (buttons.length === 0) {
        buttons = ['View Parts', 'Check Compatibility', 'Get Quote', 'Track Order'];
    }
    
    if (buttons.length > 0) {
        const quickActionsDiv = document.createElement("div");
        quickActionsDiv.className = "quick-actions";
        
        buttons.slice(0, 3).forEach(buttonText => { // Limit to 3 buttons
            const button = document.createElement("button");
            button.className = "quick-action-btn";
            button.textContent = buttonText;
            button.onclick = () => handleQuickAction(buttonText);
            quickActionsDiv.appendChild(button);
        });
        
        messagesContainer.appendChild(quickActionsDiv);
        scrollToBottom();
    }
}

function handleQuickAction(action) {
    const responses = {
        'BMW Parts': 'I need BMW parts',
        'BMW Models': 'What BMW models do you support?',
        'Mercedes Parts': 'I need Mercedes parts',
        'Mercedes Models': 'What Mercedes models do you support?',
        'Get Quote': 'I need a price quote',
        'Compare Prices': 'Can you help me compare prices?',
        'Track Order': 'I want to track my order',
        'Delivery Areas': 'What areas do you deliver to?',
        'Call Us': 'What is your phone number?',
        'WhatsApp': 'How can I contact you on WhatsApp?',
        'Email Support': 'What is your email address?',
        'View Parts': 'Show me available parts',
        'Check Compatibility': 'How do I check part compatibility?'
    };
    
    const message = responses[action] || action;
    
    // Simulate user clicking the button by sending the message
    document.getElementById("userInput").value = message;
    sendMessage();
}

// Handle Enter key press in input
document.addEventListener('DOMContentLoaded', function() {
    const userInput = document.getElementById("userInput");
    if (userInput) {
        userInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                sendMessage();
            }
        });
    }
    
    // Add welcome message with delay
    setTimeout(() => {
        const messagesContainer = document.getElementById("chat-messages");
        const welcomeMsg = document.createElement("div");
        welcomeMsg.className = "bot-message";
        welcomeMsg.innerHTML = `Welcome to NextGenSpare.lk! 🚗<br><br>I can help you with:<br>• Finding genuine auto parts<br>• Checking compatibility<br>• Pricing & availability<br>• Order tracking<br><br>What can I help you with today?`;
        messagesContainer.appendChild(welcomeMsg);
        
        // Add initial quick actions
        const quickActionsDiv = document.createElement("div");
        quickActionsDiv.className = "quick-actions";
        
        const initialButtons = ['BMW Parts', 'Mercedes Parts', 'Audi Parts', 'Get Quote'];
        initialButtons.forEach(buttonText => {
            const button = document.createElement("button");
            button.className = "quick-action-btn";
            button.textContent = buttonText;
            button.onclick = () => handleQuickAction(buttonText);
            quickActionsDiv.appendChild(button);
        });
        
        messagesContainer.appendChild(quickActionsDiv);
        scrollToBottom();
    }, 1000);
});


const observer = new MutationObserver(function(mutations) {
    mutations.forEach(function(mutation) {
        if (mutation.type === 'childList') {
            scrollToBottom();
        }
    });
});

document.addEventListener('DOMContentLoaded', function() {
    const messagesContainer = document.getElementById("chat-messages");
    if (messagesContainer) {
        observer.observe(messagesContainer, {
            childList: true,
            subtree: true
        });
    }
});