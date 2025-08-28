<?php
// admin_reviews.php - Handle review actions
session_start();
include 'db.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $review_id = (int)($_POST['review_id'] ?? 0);
    
    if (!$review_id) {
        echo json_encode(['success' => false, 'message' => 'Invalid review ID']);
        exit();
    }
    
    switch ($action) {
        case 'approve':
            $stmt = $conn->prepare("UPDATE reviews SET status = 'approved' WHERE id = ?");
            $stmt->bind_param("i", $review_id);
            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Review approved successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Error approving review']);
            }
            $stmt->close();
            break;
            
        case 'reject':
            $stmt = $conn->prepare("UPDATE reviews SET status = 'rejected' WHERE id = ?");
            $stmt->bind_param("i", $review_id);
            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Review rejected successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Error rejecting review']);
            }
            $stmt->close();
            break;
            
        case 'delete':
            $stmt = $conn->prepare("DELETE FROM reviews WHERE id = ?");
            $stmt->bind_param("i", $review_id);
            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Review deleted successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Error deleting review']);
            }
            $stmt->close();
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}
?>