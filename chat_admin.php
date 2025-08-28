<?php
session_start();

// Simple admin authentication (enhance this for production)
if (!isset($_SESSION['admin_logged_in'])) {
    if (isset($_POST['admin_password']) && $_POST['admin_password'] === 'nextgen2025') {
        $_SESSION['admin_logged_in'] = true;
    } else {
        // Show login form
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <title>Chat Admin - Login</title>
            <style>
                body { font-family: Arial, sans-serif; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; background: #f3f4f6; }
                .login-form { background: white; padding: 40px; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
                input[type="password"] { width: 100%; padding: 10px; margin: 10px 0; border: 1px solid #ddd; border-radius: 5px; }
                button { width: 100%; padding: 10px; background: #e53e3e; color: white; border: none; border-radius: 5px; cursor: pointer; }
                button:hover { background: #c53030; }
            </style>
        </head>
        <body>
            <div class="login-form">
                <h2>Chat Admin Login</h2>
                <form method="POST">
                    <input type="password" name="admin_password" placeholder="Admin Password" required>
                    <button type="submit">Login</button>
                </form>
            </div>
        </body>
        </html>
        <?php
        exit;
    }
}

require_once 'db.php';

// Handle actions
if (isset($_GET['action'])) {
    switch ($_GET['action']) {
        case 'delete_session':
            $sessionId = $_GET['session_id'] ?? '';
            if ($sessionId) {
                $stmt = $conn->prepare("DELETE FROM chat_messages WHERE session_id = ?");
                $stmt->bind_param("s", $sessionId);
                $stmt->execute();
                $stmt->close();
            }
            header('Location: chat_admin.php');
            exit;
            
        case 'export_csv':
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="chat_messages_' . date('Y-m-d') . '.csv"');
            
            $output = fopen('php://output', 'w');
            fputcsv($output, ['Session ID', 'Sender', 'Message', 'Timestamp']);
            
            $result = $conn->query("SELECT session_id, sender, message, created_at FROM chat_messages ORDER BY created_at DESC");
            while ($row = $result->fetch_assoc()) {
                fputcsv($output, [$row['session_id'], $row['sender'], $row['message'], $row['created_at']]);
            }
            
            fclose($output);
            exit;
    }
}

// Get statistics
$stats = [];
$stats['total_sessions'] = $conn->query("SELECT COUNT(DISTINCT session_id) as count FROM chat_messages")->fetch_assoc()['count'];
$stats['total_messages'] = $conn->query("SELECT COUNT(*) as count FROM chat_messages")->fetch_assoc()['count'];
$stats['today_sessions'] = $conn->query("SELECT COUNT(DISTINCT session_id) as count FROM chat_messages WHERE DATE(created_at) = CURDATE()")->fetch_assoc()['count'];
$stats['today_messages'] = $conn->query("SELECT COUNT(*) as count FROM chat_messages WHERE DATE(created_at) = CURDATE()")->fetch_assoc()['count'];

// Get recent sessions
$sessions = $conn->query("
    SELECT 
        session_id, 
        COUNT(*) as message_count,
        MIN(created_at) as started_at,
        MAX(created_at) as last_message
    FROM chat_messages 
    GROUP BY session_id 
    ORDER BY last_message DESC 
    LIMIT 20
")->fetch_all(MYSQLI_ASSOC);

// Get selected session messages
$selectedSession = $_GET['session'] ?? '';
$messages = [];
if ($selectedSession) {
    $stmt = $conn->prepare("SELECT * FROM chat_messages WHERE session_id = ? ORDER BY created_at ASC");
    $stmt->bind_param("s", $selectedSession);
    $stmt->execute();
    $messages = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>NextGenSpare.lk - Chat Admin</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin: 0; background: #f8fafc; }
        
        .header { background: #e53e3e; color: white; padding: 1rem 2rem; display: flex; justify-content: space-between; align-items: center; }
        .header h1 { margin: 0; font-size: 1.5rem; }
        
        .container { display: grid; grid-template-columns: 1fr 2fr; gap: 2rem; padding: 2rem; max-width: 1400px; margin: 0 auto; }
        
        .card { background: white; border-radius: 10px; padding: 1.5rem; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        
        .stats { display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem; margin-bottom: 2rem; }
        .stat-card { background: white; padding: 1rem; border-radius: 8px; text-align: center; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .stat-number { font-size: 2rem; font-weight: bold; color: #e53e3e; }
        
        .sessions-list { max-height: 500px; overflow-y: auto; }
        .session-item { padding: 0.75rem; border: 1px solid #e5e7eb; border-radius: 6px; margin-bottom: 0.5rem; cursor: pointer; transition: all 0.2s; }
        .session-item:hover { background: #f9fafb; border-color: #e53e3e; }
        .session-item.active { background: #fef2f2; border-color: #e53e3e; }
        
        .messages-container { max-height: 600px; overflow-y: auto; padding: 1rem; background: #f9fafb; border-radius: 8px; }
        .message { padding: 0.75rem 1rem; margin: 0.5rem 0; border-radius: 12px; max-width: 80%; }
        .message.user { background: #e53e3e; color: white; margin-left: auto; text-align: right; }
        .message.bot { background: white; border: 1px solid #e5e7eb; }
        .message-time { font-size: 0.75rem; opacity: 0.7; margin-top: 0.25rem; }
        
        .btn { padding: 0.5rem 1rem; border: none; border-radius: 6px; cursor: pointer; text-decoration: none; display: inline-block; margin: 0.25rem; }
        .btn-primary { background: #e53e3e; color: white; }
        .btn-secondary { background: #6b7280; color: white; }
        .btn-danger { background: #dc2626; color: white; }
        .btn:hover { opacity: 0.9; }
        
        .actions { display: flex; gap: 1rem; margin-bottom: 1rem; }
        
        .no-selection { text-align: center; color: #6b7280; padding: 2rem; }
        
        @media (max-width: 768px) {
            .container { grid-template-columns: 1fr; }
            .stats { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>💬 Chat Administration</h1>
        <div>
            <a href="?action=export_csv" class="btn btn-secondary">📊 Export CSV</a>
            <a href="?logout=1" class="btn btn-danger" onclick="return confirm('Logout?')">🚪 Logout</a>
        </div>
    </div>
    
    <div class="stats">
        <div class="stat-card">
            <div class="stat-number"><?= $stats['total_sessions'] ?></div>
            <div>Total Sessions</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?= $stats['total_messages'] ?></div>
            <div>Total Messages</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?= $stats['today_sessions'] ?></div>
            <div>Today Sessions</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?= $stats['today_messages'] ?></div>
            <div>Today Messages</div>
        </div>
    </div>
    
    <div class="container">
        <div class="card">
            <h3>Recent Chat Sessions</h3>
            <div class="sessions-list">
                <?php foreach ($sessions as $session): ?>
                    <div class="session-item <?= $session['session_id'] === $selectedSession ? 'active' : '' ?>" 
                         onclick="location.href='?session=<?= $session['session_id'] ?>'">
                        <strong>Session: <?= substr($session['session_id'], -8) ?></strong><br>
                        <small>
                            Messages: <?= $session['message_count'] ?> | 
                            Started: <?= date('M j, H:i', strtotime($session['started_at'])) ?><br>
                            Last: <?= date('M j, H:i', strtotime($session['last_message'])) ?>
                        </small>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <div class="card">
            <div class="actions">
                <h3>Chat Messages</h3>
                <?php if ($selectedSession): ?>
                    <a href="?action=delete_session&session_id=<?= $selectedSession ?>" 
                       class="btn btn-danger" 
                       onclick="return confirm('Delete this session?')">🗑️ Delete Session</a>
                <?php endif; ?>
            </div>
            
            <?php if ($selectedSession && $messages): ?>
                <div class="messages-container">
                    <?php foreach ($messages as $message): ?>
                        <div class="message <?= $message['sender'] ?>">
                            <?= nl2br(htmlspecialchars($message['message'])) ?>
                            <div class="message-time">
                                <?= date('M j, Y H:i:s', strtotime($message['created_at'])) ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php elseif ($selectedSession): ?>
                <div class="no-selection">No messages in this session</div>
            <?php else: ?>
                <div class="no-selection">👈 Select a session to view messages</div>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        // Auto refresh every 30 seconds
        setInterval(() => {
            if (!document.hidden) {
                location.reload();
            }
        }, 30000);
    </script>
</body>
</html>

<?php
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: chat_admin.php');
    exit;
}

$conn->close();
?>