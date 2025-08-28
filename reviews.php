<?php

include 'db.php';


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['customer_name']);
    $location = trim($_POST['location']);
    $review = trim($_POST['review_text']);
    $rating = intval($_POST['rating']);
    
    
    if (empty($name) || empty($location) || empty($review) || $rating < 1 || $rating > 5) {
        $error = "Please fill all fields and provide a valid rating (1-5 stars).";
    } else {
        
        $stmt = $conn->prepare("INSERT INTO reviews (customer_name, location, review_text, rating) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("sssi", $name, $location, $review, $rating);
        
        if ($stmt->execute()) {
            $success = "Thank you for your review! It will be visible after approval.";
        } else {
            $error = "Error submitting review. Please try again.";
        }
        $stmt->close();
    }
}


$reviews_query = "SELECT customer_name, location, review_text, rating, created_at FROM reviews WHERE status = 'approved' ORDER BY created_at DESC";
$reviews_result = $conn->query($reviews_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Reviews - NextGenSpare.lk</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .header {
            background: linear-gradient(45deg, #ff6b6b, #ee5a24);
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .header h1 {
            font-size: 2.5rem;
            margin-bottom: 10px;
        }
        
        .header p {
            font-size: 1.1rem;
            opacity: 0.9;
        }
        
        .content {
            padding: 40px;
        }
        
        .review-form {
            background: #f8f9fa;
            padding: 30px;
            border-radius: 10px;
            margin-bottom: 40px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #333;
        }
        
        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s;
        }
        
        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #ff6b6b;
        }
        
        .rating-group {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .stars {
            display: flex;
            gap: 5px;
        }
        
        .star {
            font-size: 24px;
            color: #ddd;
            cursor: pointer;
            transition: color 0.2s;
        }
        
        .star.active,
        .star:hover {
            color: #ffd700;
        }
        
        .submit-btn {
            background: linear-gradient(45deg, #ff6b6b, #ee5a24);
            color: white;
            padding: 15px 30px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.3s;
        }
        
        .submit-btn:hover {
            transform: translateY(-2px);
        }
        
        .reviews-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 20px;
        }
        
        .review-card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            border-left: 4px solid #ff6b6b;
            transition: transform 0.3s;
        }
        
        .review-card:hover {
            transform: translateY(-5px);
        }
        
        .review-text {
            font-style: italic;
            margin-bottom: 15px;
            color: #555;
            line-height: 1.6;
        }
        
        .review-author {
            font-weight: 600;
            color: #333;
            margin-bottom: 10px;
        }
        
        .review-stars {
            color: #ffd700;
            margin-bottom: 10px;
        }
        
        .review-date {
            font-size: 0.9rem;
            color: #888;
        }
        
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 8px;
            font-weight: 600;
        }
        
        .alert.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .back-btn {
            display: inline-block;
            margin-bottom: 20px;
            padding: 10px 20px;
            background: #6c757d;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            transition: background 0.3s;
        }
        
        .back-btn:hover {
            background: #5a6268;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Customer Reviews</h1>
            <p>Share your experience with NextGenSpare.lk</p>
        </div>
        
        <div class="content">
            <a href="home.html" class="back-btn">← Back to Home</a>
            
            <?php if (isset($success)): ?>
                <div class="alert success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <?php if (isset($error)): ?>
                <div class="alert error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <!-- Review Submission Form -->
            <div class="review-form">
                <h2 style="margin-bottom: 20px; color: #333;">Write a Review</h2>
                <form method="POST">
                    <div class="form-group">
                        <label for="customer_name">Your Name</label>
                        <input type="text" id="customer_name" name="customer_name" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="location">Your Location</label>
                        <input type="text" id="location" name="location" placeholder="e.g., Colombo, Kandy" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="review_text">Your Review</label>
                        <textarea id="review_text" name="review_text" rows="4" placeholder="Share your experience with our service..." required></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label>Rating</label>
                        <div class="rating-group">
                            <div class="stars" id="rating-stars">
                                <span class="star" data-rating="1">★</span>
                                <span class="star" data-rating="2">★</span>
                                <span class="star" data-rating="3">★</span>
                                <span class="star" data-rating="4">★</span>
                                <span class="star" data-rating="5">★</span>
                            </div>
                            <span id="rating-text">Click to rate</span>
                            <input type="hidden" id="rating" name="rating" value="" required>
                        </div>
                    </div>
                    
                    <button type="submit" class="submit-btn">Submit Review</button>
                </form>
            </div>
            
            <!-- Display Reviews -->
            <h2 style="margin-bottom: 30px; color: #333;">What Our Customers Say</h2>
            <div class="reviews-grid">
                <?php if ($reviews_result->num_rows > 0): ?>
                    <?php while($review = $reviews_result->fetch_assoc()): ?>
                        <div class="review-card">
                            <div class="review-text">"<?php echo htmlspecialchars($review['review_text']); ?>"</div>
                            <div class="review-author">— <?php echo htmlspecialchars($review['customer_name']); ?>, <?php echo htmlspecialchars($review['location']); ?></div>
                            <div class="review-stars">
                                <?php for($i = 1; $i <= 5; $i++): ?>
                                    <?php echo $i <= $review['rating'] ? '⭐' : '☆'; ?>
                                <?php endfor; ?>
                            </div>
                            <div class="review-date"><?php echo date('M d, Y', strtotime($review['created_at'])); ?></div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p>No reviews yet. Be the first to share your experience!</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script>
        
        document.addEventListener('DOMContentLoaded', function() {
            const stars = document.querySelectorAll('.star');
            const ratingInput = document.getElementById('rating');
            const ratingText = document.getElementById('rating-text');
            
            stars.forEach(star => {
                star.addEventListener('click', function() {
                    const rating = this.getAttribute('data-rating');
                    ratingInput.value = rating;
                    
                    
                    stars.forEach((s, index) => {
                        if (index < rating) {
                            s.classList.add('active');
                        } else {
                            s.classList.remove('active');
                        }
                    });
                    
                    
                    const ratingTexts = ['', 'Poor', 'Fair', 'Good', 'Very Good', 'Excellent'];
                    ratingText.textContent = ratingTexts[rating];
                });
                
                star.addEventListener('mouseover', function() {
                    const rating = this.getAttribute('data-rating');
                    stars.forEach((s, index) => {
                        if (index < rating) {
                            s.style.color = '#ffd700';
                        } else {
                            s.style.color = '#ddd';
                        }
                    });
                });
            });
            
           
            document.getElementById('rating-stars').addEventListener('mouseleave', function() {
                const currentRating = ratingInput.value;
                stars.forEach((s, index) => {
                    if (index < currentRating) {
                        s.style.color = '#ffd700';
                    } else {
                        s.style.color = '#ddd';
                    }
                });
            });
        });
    </script>
</body>
</html>