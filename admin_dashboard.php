<?php
session_start();
include 'db.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: signin.html");
    exit();
}

// Get dashboard statistics
$stats = [];

// Total users
$result = $conn->query("SELECT COUNT(*) as total FROM users");
$stats['total_users'] = (int) ($result->fetch_assoc()['total'] ?? 0);

// Total orders
$result = $conn->query("SELECT COUNT(*) as total FROM orders");
$stats['total_orders'] = (int) ($result->fetch_assoc()['total'] ?? 0);

// Total revenue
$result = $conn->query("SELECT COALESCE(SUM(total),0) as revenue FROM orders WHERE status != 'cancelled'");
$stats['total_revenue'] = (float) ($result->fetch_assoc()['revenue'] ?? 0);

// Pending orders
$result = $conn->query("SELECT COUNT(*) as total FROM orders WHERE status = 'pending'");
$stats['pending_orders'] = (int) ($result->fetch_assoc()['total'] ?? 0);

// ✅ Reviews statistics
$result = $conn->query("SELECT COUNT(*) as total FROM reviews");
$stats['total_reviews'] = (int) ($result->fetch_assoc()['total'] ?? 0);

$result = $conn->query("SELECT COUNT(*) as total FROM reviews WHERE status = 'pending'");
$stats['pending_reviews'] = (int) ($result->fetch_assoc()['total'] ?? 0);

// Recent orders (✅ include user email)
$recent_orders = $conn->query("
    SELECT o.*, u.full_name, u.email
    FROM orders o
    LEFT JOIN users u ON o.user_id = u.id
    ORDER BY o.created_at DESC
    LIMIT 10
");

// Get all tables data
$all_users = $conn->query("SELECT * FROM users ORDER BY created_at DESC");
$all_orders = $conn->query("
    SELECT o.*, u.full_name
    FROM orders o
    LEFT JOIN users u ON o.user_id = u.id
    ORDER BY o.created_at DESC
");

// ✅ Get all reviews
$all_reviews = $conn->query("SELECT * FROM reviews ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - NextGenSpare.lk</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Poppins', sans-serif; background: #f5f7fa; color: #333; }
        .sidebar { position: fixed; left: 0; top: 0; width: 280px; height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 2rem 1rem;
            z-index: 1000; transform: translateX(0); transition: transform 0.3s ease; }
        .sidebar.collapsed { transform: translateX(-280px); }
        .sidebar-header { text-align: center; margin-bottom: 2rem; border-bottom: 1px solid rgba(255,255,255,0.2); padding-bottom: 1rem; }
        .sidebar-header img { width: 120px; margin-bottom: 0.5rem; }
        .sidebar-nav { list-style: none; }
        .sidebar-nav li { margin-bottom: 0.5rem; }
        .sidebar-nav a { color: white; text-decoration: none; display: flex; align-items: center; padding: 12px 16px; border-radius: 8px; transition: all 0.3s ease; }
        .sidebar-nav a:hover, .sidebar-nav a.active { background: rgba(255,255,255,0.1); transform: translateX(5px); }
        .sidebar-nav i { margin-right: 12px; width: 20px; }
        .main-content { margin-left: 280px; padding: 2rem; transition: margin-left 0.3s ease; }
        .main-content.expanded { margin-left: 0; }
        .top-bar { background: white; padding: 1rem 2rem; border-radius: 12px; box-shadow: 0 2px 20px rgba(0,0,0,0.1);
            margin-bottom: 2rem; display: flex; justify-content: space-between; align-items: center; }
        .menu-toggle { display: none; background: none; border: none; font-size: 1.5rem; color: #667eea; cursor: pointer; }
        .admin-info { display: flex; align-items: center; gap: 1rem; }
        .logout-btn { background: linear-gradient(135deg, #ee5757, #8a0202); color: white; border: none; padding: 8px 16px; border-radius: 6px; cursor: pointer; text-decoration: none; transition: all 0.3s ease; }
        .logout-btn:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(238, 87, 87, 0.3); }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem; margin-bottom: 2rem; }
        .stat-card { background: white; padding: 2rem; border-radius: 12px; box-shadow: 0 2px 20px rgba(0,0,0,0.1); position: relative; overflow: hidden; }
        .stat-card::before { content: ''; position: absolute; top: 0; left: 0; width: 100%; height: 4px; background: linear-gradient(135deg, #667eea, #764ba2); }
        .stat-card h3 { color: #666; font-size: 0.9rem; margin-bottom: 0.5rem; text-transform: uppercase; letter-spacing: 0.5px; }
        .stat-card .number { font-size: 2.5rem; font-weight: 700; color: #333; margin-bottom: 0.5rem; }
        .stat-card .icon { position: absolute; right: 2rem; top: 2rem; font-size: 2.5rem; color: rgba(102, 126, 234, 0.2); }
        .content-section { background: white; border-radius: 12px; box-shadow: 0 2px 20px rgba(0,0,0,0.1); margin-bottom: 2rem; overflow: hidden; }
        .section-header { background: linear-gradient(135deg, #667eea, #764ba2); color: white; padding: 1.5rem 2rem; font-size: 1.25rem; font-weight: 600; }
        .table-container { overflow-x: auto; max-height: 500px; overflow-y: auto; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px 16px; text-align: left; border-bottom: 1px solid #eee; }
        th { background: #f8f9fa; font-weight: 600; color: #555; position: sticky; top: 0; z-index: 10; }
        tr:hover { background: #f8f9fa; }
        .status-badge { padding: 4px 8px; border-radius: 20px; font-size: 0.8rem; font-weight: 500; }
        .status-pending { background: #fff3cd; color: #856404; }
        .status-completed { background: #d4edda; color: #155724; }
        .status-cancelled { background: #f8d7da; color: #721c24; }
        .status-processing { background: #cce7ff; color: #004085; }
        .status-approved { background: #d4edda; color: #155724; }
        .status-rejected { background: #f8d7da; color: #721c24; }
        .search-box { padding: 1rem 2rem; border-bottom: 1px solid #eee; }
        .search-box input { width: 100%; padding: 8px 12px; border: 1px solid #ddd; border-radius: 6px; font-size: 1rem; }
        .order-details { cursor: pointer; color: #667eea; }
        .order-details:hover { text-decoration: underline; }
        
        /* ✅ Review action buttons */
        .action-buttons { display: flex; gap: 8px; }
        .btn-approve { background: #28a745; color: white; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer; font-size: 0.8rem; }
        .btn-reject { background: #dc3545; color: white; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer; font-size: 0.8rem; }
        .btn-delete { background: #6c757d; color: white; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer; font-size: 0.8rem; }
        .btn-approve:hover { background: #218838; }
        .btn-reject:hover { background: #c82333; }
        .btn-delete:hover { background: #5a6268; }
        
        .review-text { max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .review-text:hover { white-space: normal; overflow: visible; }
        
        .stars { color: #ffd700; }
        
        @media (max-width: 768px) {
            .sidebar { transform: translateX(-280px); }
            .main-content { margin-left: 0; }
            .menu-toggle { display: block; }
            .stats-grid { grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); }
            .action-buttons { flex-direction: column; }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <img src="newLogo.png" alt="Logo">
            <h3>Admin Panel</h3>
        </div>
        <ul class="sidebar-nav">
            <li><a href="#dashboard" class="nav-link active" onclick="showSection('dashboard')">
                <i class="fas fa-tachometer-alt"></i> Dashboard
            </a></li>
            <li><a href="#users" class="nav-link" onclick="showSection('users')">
                <i class="fas fa-users"></i> Users
            </a></li>
            <li><a href="#orders" class="nav-link" onclick="showSection('orders')">
                <i class="fas fa-shopping-cart"></i> Orders
            </a></li>
            <li><a href="#order-items" class="nav-link" onclick="showSection('order-items')">
                <i class="fas fa-list"></i> Order Items
            </a></li>
            <!-- ✅ Reviews navigation -->
            <li><a href="#reviews" class="nav-link" onclick="showSection('reviews')">
                <i class="fas fa-star"></i> Reviews
                <?php if ($stats['pending_reviews'] > 0): ?>
                    <span class="status-badge status-pending" style="margin-left: 8px;"><?php echo $stats['pending_reviews']; ?></span>
                <?php endif; ?>
            </a></li>
            <li><a href="#reports" class="nav-link" onclick="showSection('reports')">
                <i class="fas fa-file-alt"></i> Reports
            </a></li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content" id="mainContent">
        <!-- Top Bar -->
        <div class="top-bar">
            <button class="menu-toggle" onclick="toggleSidebar()">
                <i class="fas fa-bars"></i>
            </button>
            <h2 id="pageTitle">Dashboard</h2>
            <div class="admin-info">
                <span>Welcome, Admin</span>
                <a href="logout.php" class="logout-btn">Logout</a>
            </div>
        </div>

        <!-- Dashboard Section -->
        <div id="dashboard-section" class="section">
            <!-- Stats Grid -->
            <div class="stats-grid">
                <div class="stat-card">
                    <h3>Total Users</h3>
                    <div class="number"><?php echo number_format($stats['total_users']); ?></div>
                    <i class="fas fa-users icon"></i>
                </div>
                <div class="stat-card">
                    <h3>Total Orders</h3>
                    <div class="number"><?php echo number_format($stats['total_orders']); ?></div>
                    <i class="fas fa-shopping-cart icon"></i>
                </div>
                <div class="stat-card">
                    <h3>Total Revenue</h3>
                    <div class="number">LKR <?php echo number_format($stats['total_revenue'], 2); ?></div>
                    <i class="fas fa-dollar-sign icon"></i>
                </div>
                <div class="stat-card">
                    <h3>Pending Orders</h3>
                    <div class="number"><?php echo number_format($stats['pending_orders']); ?></div>
                    <i class="fas fa-clock icon"></i>
                </div>
                <!-- ✅ Reviews stats -->
                <div class="stat-card">
                    <h3>Total Reviews</h3>
                    <div class="number"><?php echo number_format($stats['total_reviews']); ?></div>
                    <i class="fas fa-star icon"></i>
                </div>
                <div class="stat-card">
                    <h3>Pending Reviews</h3>
                    <div class="number"><?php echo number_format($stats['pending_reviews']); ?></div>
                    <i class="fas fa-star-half-alt icon"></i>
                </div>
            </div>

            <!-- Recent Orders -->
            <div class="content-section">
                <div class="section-header">
                    <i class="fas fa-history"></i> Recent Orders
                </div>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Customer</th>
                                <th>Email</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($order = $recent_orders->fetch_assoc()): ?>
                            <tr>
                                <td>#<?php echo (int)$order['id']; ?></td>
                                <td><?php echo htmlspecialchars($order['full_name'] ?: 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($order['email'] ?? ''); ?></td>
                                <td>LKR <?php echo number_format((float)$order['total'], 2); ?></td>
                                <td><span class="status-badge status-<?php echo htmlspecialchars($order['status']); ?>">
                                    <?php echo ucfirst($order['status']); ?>
                                </span></td>
                                <td><?php echo date('Y-m-d H:i', strtotime($order['created_at'])); ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Users Section -->
        <div id="users-section" class="section" style="display: none;">
            <div class="content-section">
                <div class="section-header">
                    <i class="fas fa-users"></i> All Users
                </div>
                <div class="search-box">
                    <input type="text" placeholder="Search users..." onkeyup="searchTable(this, 'users-table')">
                </div>
                <div class="table-container">
                    <table id="users-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Email</th>
                                <th>Full Name</th>
                                <th>Mobile</th>
                                <th>Role</th>
                                <th>Created</th>
                                <th>Last Login</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($user = $all_users->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo (int)$user['id']; ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                                <td><?php echo htmlspecialchars($user['mobile_number']); ?></td>
                                <td><span class="status-badge status-<?php echo htmlspecialchars($user['role']); ?>">
                                    <?php echo ucfirst($user['role']); ?>
                                </span></td>
                                <td><?php echo date('Y-m-d H:i', strtotime($user['created_at'])); ?></td>
                                <td><?php echo !empty($user['last_login_at']) ? date('Y-m-d H:i', strtotime($user['last_login_at'])) : 'Never'; ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Orders Section -->
        <div id="orders-section" class="section" style="display: none;">
            <div class="content-section">
                <div class="section-header">
                    <i class="fas fa-shopping-cart"></i> All Orders
                </div>
                <div class="search-box">
                    <input type="text" placeholder="Search orders..." onkeyup="searchTable(this, 'orders-table')">
                </div>
                <div class="table-container">
                    <table id="orders-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Customer</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>City</th>
                                <th>Subtotal</th>
                                <th>Shipping</th>
                                <th>Discount</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Payment</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $all_orders->data_seek(0);
                            while ($order = $all_orders->fetch_assoc()): ?>
                            <tr>
                                <td>#<?php echo (int)$order['id']; ?></td>
                                <td><?php echo htmlspecialchars(trim(($order['first_name'] ?? '').' '.($order['last_name'] ?? ''))); ?></td>
                                <td><?php echo htmlspecialchars($order['email']); ?></td>
                                <td><?php echo htmlspecialchars($order['phone']); ?></td>
                                <td><?php echo htmlspecialchars($order['city']); ?></td>
                                <td>LKR <?php echo number_format((float)$order['subtotal'], 2); ?></td>
                                <td>LKR <?php echo number_format((float)$order['shipping'], 2); ?></td>
                                <td>LKR <?php echo number_format((float)$order['discount'], 2); ?></td>
                                <td>LKR <?php echo number_format((float)$order['total'], 2); ?></td>
                                <td><span class="status-badge status-<?php echo htmlspecialchars($order['status']); ?>">
                                    <?php echo ucfirst($order['status']); ?>
                                </span></td>
                                <td><?php echo ucfirst($order['payment_method']); ?></td>
                                <td><?php echo date('Y-m-d H:i', strtotime($order['created_at'])); ?></td>
                                <td><span class="order-details" onclick="viewOrderItems(<?php echo (int)$order['id']; ?>)">View Items</span></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Order Items Section -->
        <div id="order-items-section" class="section" style="display: none;">
            <div class="content-section">
                <div class="section-header">
                    <i class="fas fa-list"></i> Order Items
                </div>
                <div class="search-box">
                    <input type="text" placeholder="Search order items..." onkeyup="searchTable(this, 'order-items-table')">
                </div>
                <div class="table-container">
                    <table id="order-items-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Order ID</th>
                                <th>Product Name</th>
                                <th>Product Image</th>
                                <th>Unit Price</th>
                                <th>Quantity</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $order_items = $conn->query("SELECT * FROM order_items ORDER BY order_id DESC");
                            while ($item = $order_items->fetch_assoc()):
                            ?>
                            <tr>
                                <td><?php echo (int)$item['id']; ?></td>
                                <td>#<?php echo (int)$item['order_id']; ?></td>
                                <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                                <td>
                                    <?php if (!empty($item['product_image'])): ?>
                                        <img src="<?php echo htmlspecialchars($item['product_image']); ?>" alt="Product" style="width: 50px; height: 50px; object-fit: cover; border-radius: 4px;">
                                    <?php else: ?>
                                        No Image
                                    <?php endif; ?>
                                </td>
                                <td>LKR <?php echo number_format((float)$item['unit_price'], 2); ?></td>
                                <td><?php echo (int)$item['qty']; ?></td>
                                <td>LKR <?php echo number_format((float)$item['unit_price'] * (int)$item['qty'], 2); ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ✅ Reviews Section -->
        <div id="reviews-section" class="section" style="display: none;">
            <div class="content-section">
                <div class="section-header">
                    <i class="fas fa-star"></i> Reviews Management
                </div>
                <div class="search-box">
                    <input type="text" placeholder="Search reviews..." onkeyup="searchTable(this, 'reviews-table')">
                    <div style="margin-top: 10px;">
                        <button onclick="filterReviews('all')" class="btn-approve">All</button>
                        <button onclick="filterReviews('pending')" class="btn-reject">Pending</button>
                        <button onclick="filterReviews('approved')" class="btn-approve">Approved</button>
                        <button onclick="filterReviews('rejected')" class="btn-delete">Rejected</button>
                    </div>
                </div>
                <div class="table-container">
                    <table id="reviews-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Customer</th>
                                <th>Location</th>
                                <th>Review</th>
                                <th>Rating</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($review = $all_reviews->fetch_assoc()): ?>
                            <tr data-status="<?php echo $review['status']; ?>">
                                <td><?php echo (int)$review['id']; ?></td>
                                <td><?php echo htmlspecialchars($review['customer_name']); ?></td>
                                <td><?php echo htmlspecialchars($review['location']); ?></td>
                                <td>
                                    <div class="review-text" title="<?php echo htmlspecialchars($review['review_text']); ?>">
                                        "<?php echo htmlspecialchars(substr($review['review_text'], 0, 50)); ?><?php echo strlen($review['review_text']) > 50 ? '...' : ''; ?>"
                                    </div>
                                </td>
                                <td>
                                    <div class="stars">
                                        <?php for($i = 1; $i <= 5; $i++): ?>
                                            <?php echo $i <= $review['rating'] ? '⭐' : '☆'; ?>
                                        <?php endfor; ?>
                                        (<?php echo $review['rating']; ?>)
                                    </div>
                                </td>
                                <td>
                                    <span class="status-badge status-<?php echo htmlspecialchars($review['status']); ?>">
                                        <?php echo ucfirst($review['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo date('Y-m-d H:i', strtotime($review['created_at'])); ?></td>
                                <td>
                                    <div class="action-buttons">
                                        <?php if ($review['status'] !== 'approved'): ?>
                                            <button class="btn-approve" onclick="handleReview(<?php echo $review['id']; ?>, 'approve')">
                                                <i class="fas fa-check"></i> Approve
                                            </button>
                                        <?php endif; ?>
                                        <?php if ($review['status'] !== 'rejected'): ?>
                                            <button class="btn-reject" onclick="handleReview(<?php echo $review['id']; ?>, 'reject')">
                                                <i class="fas fa-times"></i> Reject
                                            </button>
                                        <?php endif; ?>
                                        <button class="btn-delete" onclick="handleReview(<?php echo $review['id']; ?>, 'delete')">
                                            <i class="fas fa-trash"></i> Delete
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Reports Section -->
        <div id="reports-section" class="section" style="display: none;">
            <div class="content-section">
                <div class="section-header">
                    <i class="fas fa-file-alt"></i> Generate Reports
                </div>
                <div class="search-box">
                    <form method="POST" action="reports.php">
                        <label>From: <input type="date" name="start_date" required></label>
                        <label>To: <input type="date" name="end_date" required></label>
                        <button type="submit" name="generate_csv" class="logout-btn">Export CSV</button>
                        <button type="submit" name="generate_pdf" class="logout-btn">Export PDF</button>
                    </form>
                </div>
            </div>
        </div>
    </div><!-- /.main-content -->

    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.getElementById('mainContent');
            sidebar.classList.toggle('collapsed');
            mainContent.classList.toggle('expanded');
        }

        function showSection(sectionName) {
            const sections = document.querySelectorAll('.section');
            sections.forEach(section => section.style.display = 'none');

            const target = document.getElementById(sectionName + '-section');
            if (target) target.style.display = 'block';

            const navLinks = document.querySelectorAll('.nav-link');
            navLinks.forEach(link => link.classList.remove('active'));
            if (event && event.target) event.target.classList.add('active');

            const titles = {
                'dashboard': 'Dashboard',
                'users': 'Users Management',
                'orders': 'Orders Management',
                'order-items': 'Order Items',
                'reviews': 'Reviews Management',
                'reports': 'Reports'
            };
            document.getElementById('pageTitle').textContent = titles[sectionName] || 'Dashboard';
        }

        function searchTable(input, tableId) {
            const filter = input.value.toLowerCase();
            const table = document.getElementById(tableId);
            if (!table) return;
            const rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');

            for (let i = 0; i < rows.length; i++) {
                const cells = rows[i].getElementsByTagName('td');
                let found = false;
                for (let j = 0; j < cells.length; j++) {
                    if (cells[j].textContent.toLowerCase().indexOf(filter) > -1) { found = true; break; }
                }
                rows[i].style.display = found ? '' : 'none';
            }
        }

        function viewOrderItems(orderId) {
            showSection('order-items');
            const table = document.getElementById('order-items-table');
            if (!table) return;
            const rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');

            for (let i = 0; i < rows.length; i++) {
                const orderIdCell = rows[i].getElementsByTagName('td')[1];
                const orderIdText = orderIdCell.textContent.replace('#', '').trim();
                if (orderIdText === String(orderId)) {
                    rows[i].style.display = '';
                    rows[i].style.backgroundColor = '#e3f2fd';
                } else {
                    rows[i].style.display = 'none';
                    rows[i].style.backgroundColor = '';
                }
            }
        }

        // ✅ Reviews management functions
        function handleReview(reviewId, action) {
            if (action === 'delete' && !confirm('Are you sure you want to delete this review?')) {
                return;
            }
            
            const formData = new FormData();
            formData.append('action', action);
            formData.append('review_id', reviewId);
            
            fetch('admin_reviews.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    location.reload(); // Refresh to show updated data
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while processing the request.');
            });
        }

        function filterReviews(status) {
            const table = document.getElementById('reviews-table');
            if (!table) return;
            const rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');

            for (let i = 0; i < rows.length; i++) {
                const rowStatus = rows[i].getAttribute('data-status');
                if (status === 'all' || rowStatus === status) {
                    rows[i].style.display = '';
                } else {
                    rows[i].style.display = 'none';
                }
            }
        }

        // Auto-refresh dashboard stats every 30 seconds
        setInterval(function() {
            const dash = document.getElementById('dashboard-section');
            if (dash && dash.style.display !== 'none') {
                location.reload();
            }
        }, 30000);

        // Mobile responsiveness
        window.addEventListener('resize', function() {
            if (window.innerWidth <= 768) {
                document.getElementById('sidebar').classList.add('collapsed');
                document.getElementById('mainContent').classList.add('expanded');
            } else {
                document.getElementById('sidebar').classList.remove('collapsed');
                document.getElementById('mainContent').classList.remove('expanded');
            }
        });

       
        document.addEventListener('DOMContentLoaded', function() {
            const pendingCount = <?php echo $stats['pending_reviews']; ?>;
            if (pendingCount > 0) {
                console.log(`You have ${pendingCount} pending review(s) to moderate.`);
            }
        });
    </script>
</body>
</html>