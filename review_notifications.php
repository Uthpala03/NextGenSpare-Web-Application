<?php
// review_notifications.php - Include this in your admin pages for notifications
include 'db.php';

function getPendingReviewsCount() {
    global $conn;
    $result = $conn->query("SELECT COUNT(*) as count FROM reviews WHERE status = 'pending'");
    return (int)($result->fetch_assoc()['count'] ?? 0);
}

function getRecentReviews($limit = 5) {
    global $conn;
    $stmt = $conn->prepare("SELECT id, customer_name, review_text, rating, created_at FROM reviews WHERE status = 'pending' ORDER BY created_at DESC LIMIT ?");
    $stmt->bind_param("i", $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    $reviews = [];
    while ($row = $result->fetch_assoc()) {
        $reviews[] = $row;
    }
    $stmt->close();
    return $reviews;
}

// Function to send email notifications (optional)
function sendReviewNotification($reviewId) {
    // You can implement email notifications here
    // For now, just log it
    error_log("New review pending approval: ID $reviewId");
}
?>

<!-- Notification Dropdown (Add this to your admin header) -->
<style>
.notification-dropdown {
    position: relative;
    display: inline-block;
}

.notification-bell {
    background: none;
    border: none;
    font-size: 1.5rem;
    color: #667eea;
    cursor: pointer;
    position: relative;
}

.notification-badge {
    position: absolute;
    top: -8px;
    right: -8px;
    background: #dc3545;
    color: white;
    border-radius: 50%;
    width: 20px;
    height: 20px;
    font-size: 0.8rem;
    display: flex;
    align-items: center;
    justify-content: center;
}

.notification-content {
    display: none;
    position: absolute;
    right: 0;
    top: 100%;
    background: white;
    border-radius: 8px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.1);
    width: 350px;
    z-index: 1000;
    max-height: 400px;
    overflow-y: auto;
}

.notification-header {
    padding: 15px;
    border-bottom: 1px solid #eee;
    font-weight: 600;
    color: #333;
}

.notification-item {
    padding: 12px 15px;
    border-bottom: 1px solid #f5f5f5;
    cursor: pointer;
    transition: background 0.3s;
}

.notification-item:hover {
    background: #f8f9fa;
}

.notification-item:last-child {
    border-bottom: none;
}

.notification-text {
    font-size: 0.9rem;
    color: #555;
    margin-bottom: 5px;
}

.notification-time {
    font-size: 0.8rem;
    color: #888;
}

.notification-footer {
    padding: 10px 15px;
    text-align: center;
    border-top: 1px solid #eee;
}

.view-all-link {
    color: #667eea;
    text-decoration: none;
    font-weight: 600;
}

.view-all-link:hover {
    text-decoration: underline;
}
</style>

<div class="notification-dropdown">
    <button class="notification-bell" onclick="toggleNotifications()">
        <i class="fas fa-bell"></i>
        <?php 
        $pendingCount = getPendingReviewsCount();
        if ($pendingCount > 0): 
        ?>
        <span class="notification-badge"><?php echo $pendingCount; ?></span>
        <?php endif; ?>
    </button>
    
    <div class="notification-content" id="notificationContent">
        <div class="notification-header">
            <i class="fas fa-star"></i> Review Notifications
        </div>
        
        <?php 
        $recentReviews = getRecentReviews(5);
        if (!empty($recentReviews)): 
        ?>
            <?php foreach ($recentReviews as $review): ?>
                <div class="notification-item" onclick="goToReview(<?php echo $review['id']; ?>)">
                    <div class="notification-text">
                        <strong><?php echo htmlspecialchars($review['customer_name']); ?></strong> left a 
                        <?php echo $review['rating']; ?>-star review
                    </div>
                    <div class="notification-text">
                        "<?php echo htmlspecialchars(substr($review['review_text'], 0, 60)); ?>..."
                    </div>
                    <div class="notification-time">
                        <?php echo timeAgo($review['created_at']); ?>
                    </div>
                </div>
            <?php endforeach; ?>
            
            <div class="notification-footer">
                <a href="#" class="view-all-link" onclick="showReviewsSection()">View All Reviews</a>
            </div>
        <?php else: ?>
            <div class="notification-item">
                <div class="notification-text">No pending reviews</div>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function toggleNotifications() {
    const content = document.getElementById('notificationContent');
    content.style.display = content.style.display === 'block' ? 'none' : 'block';
}

function goToReview(reviewId) {
    showSection('reviews');
    toggleNotifications();
    
    // Highlight the specific review
    setTimeout(() => {
        const rows = document.querySelectorAll('#reviews-table tbody tr');
        rows.forEach(row => {
            const idCell = row.querySelector('td:first-child');
            if (idCell && idCell.textContent.trim() === reviewId.toString()) {
                row.style.backgroundColor = '#fff3cd';
                row.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        });
    }, 100);
}

function showReviewsSection() {
    showSection('reviews');
    toggleNotifications();
}

// Close notifications when clicking outside
document.addEventListener('click', function(event) {
    const dropdown = document.querySelector('.notification-dropdown');
    if (dropdown && !dropdown.contains(event.target)) {
        document.getElementById('notificationContent').style.display = 'none';
    }
});
</script>

<?php
// Helper function for time ago
function timeAgo($datetime) {
    $time = time() - strtotime($datetime);
    
    if ($time < 60) return 'Just now';
    if ($time < 3600) return floor($time/60) . 'm ago';
    if ($time < 86400) return floor($time/3600) . 'h ago';
    if ($time < 2592000) return floor($time/86400) . 'd ago';
    
    return date('M j, Y', strtotime($datetime));
}
?>