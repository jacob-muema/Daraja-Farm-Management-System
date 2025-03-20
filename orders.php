<?php
session_start();

// Check if user is logged in
if(!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

// Database connection
$conn = new mysqli("localhost", "root", "", "fms");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get username from session
$username = $_SESSION['username'];

// Check if user is admin
$isAdmin = false;
$query = "SELECT role FROM users WHERE username = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();
if($row = $result->fetch_assoc()) {
    $isAdmin = ($row['role'] === 'admin');
}
$stmt->close();

// Get cart from session
$sessionCart = isset($_SESSION['cart']) ? $_SESSION['cart'] : [];

// Calculate cart total
function calculateCartTotal($cart) {
    $total = 0;
    if(!empty($cart)) {
        foreach($cart as $item) {
            $total += $item['price'] * $item['quantity'];
        }
    }
    return $total;
}

// Get cart count
function getCartCount($sessionCart) {
    return !empty($sessionCart) ? count($sessionCart) : 0;
}

// Process order status update (admin only)
$statusUpdateMessage = '';
if($isAdmin && isset($_POST['update_order_status'])) {
    $orderId = $_POST['order_id'];
    $newStatus = $_POST['new_status'];
    
    $updateQuery = "UPDATE orders SET status = ? WHERE order_id = ?";
    $stmt = $conn->prepare($updateQuery);
    $stmt->bind_param("ss", $newStatus, $orderId);
    
    if($stmt->execute()) {
        $statusUpdateMessage = "<div class='alert alert-success'>Order status updated successfully!</div>";
    } else {
        $statusUpdateMessage = "<div class='alert alert-danger'>Failed to update order status: " . $conn->error . "</div>";
    }
    $stmt->close();
}

// Fetch orders from database
$orders = [];
if($isAdmin) {
    // Admin sees all orders
    $query = "SELECT o.*, u.username, u.phone, u.address 
              FROM orders o 
              JOIN users u ON o.user_id = u.id 
              ORDER BY o.order_date DESC";
    $result = $conn->query($query);
} else {
    // Regular users see only their orders
    $query = "SELECT o.* 
              FROM orders o 
              JOIN users u ON o.user_id = u.id 
              WHERE u.username = ? 
              ORDER BY o.order_date DESC";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
}

if($result) {
    while($row = $result->fetch_assoc()) {
        // Get order items
        $itemsQuery = "SELECT * FROM order_items WHERE order_id = ?";
        $stmt = $conn->prepare($itemsQuery);
        $stmt->bind_param("s", $row['order_id']);
        $stmt->execute();
        $itemsResult = $stmt->get_result();
        
        $items = [];
        while($item = $itemsResult->fetch_assoc()) {
            $items[] = [
                'name' => $item['product_name'],
                'quantity' => $item['quantity'],
                'price' => $item['price']
            ];
        }
        
        $orders[] = [
            'id' => $row['order_id'],
            'date' => $row['order_date'],
            'items' => $items,
            'total' => $row['total_amount'],
            'status' => $row['status'],
            'payment_method' => $row['payment_method'],
            'transaction_id' => $row['transaction_id'],
            'customer' => isset($row['username']) ? $row['username'] : $username,
            'phone' => isset($row['phone']) ? $row['phone'] : '',
            'address' => isset($row['address']) ? $row['address'] : '123 Farm Road, Nairobi, Kenya'
        ];
    }
}

// Get order details if order_id is provided
$selectedOrder = null;
if(isset($_GET['order_id'])) {
    $orderId = $_GET['order_id'];
    foreach($orders as $order) {
        if($order['id'] === $orderId) {
            $selectedOrder = $order;
            break;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>ADMT Farm Management - My Orders</title>
    <link rel="shortcut icon" type="image/x-icon" href="logo.jpeg"/>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="stylesheet" type="text/css" href="font-awesome-4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0-beta/css/bootstrap.min.css">
    <link href="https://fonts.googleapis.com/css?family=IBM+Plex+Sans&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --primary-color: #3931af;
            --secondary-color: #00c6ff;
            --accent-color: #342ac1;
            --green-color: #28a745;
            --light-green: #e8f5e9;
            --dark-green: #1b5e20;
            --orange-color: #ff9800;
            --red-color: #dc3545;
        }
        
        body {
            font-family: 'Poppins', 'IBM Plex Sans', sans-serif;
            background-color: #f8f9fa;
            padding-top: 60px;
            color: #333;
        }
        
        .bg-primary {
            background: linear-gradient(to right, var(--primary-color), var(--secondary-color)) !important;
        }
        
        .text-primary {
            color: var(--accent-color) !important;
        }
        
        /* Navbar Styles */
        .navbar {
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
            padding: 12px 0;
        }
        
        .navbar-brand {
            font-weight: 700;
            font-size: 22px;
        }
        
        .nav-link {
            font-weight: 500;
            padding: 8px 15px !important;
            transition: all 0.3s;
            border-radius: 8px;
        }
        
        .nav-link:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }
        
        /* Cart Badge */
        .cart-badge {
            position: relative;
            display: inline-block;
        }
        
        .cart-count {
            position: absolute;
            top: -10px;
            right: -10px;
            background-color: var(--green-color);
            color: white;
            border-radius: 50%;
            width: 22px;
            height: 22px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: 700;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }
        
        /* Section Title */
        .section-title {
            margin-bottom: 30px;
            position: relative;
            padding-bottom: 15px;
            font-weight: 700;
        }
        
        .section-title:after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 60px;
            height: 3px;
            background: linear-gradient(to right, var(--primary-color), var(--secondary-color));
            border-radius: 3px;
        }
        
        /* Order Card */
        .order-card {
            background-color: white;
            border-radius: 15px;
            box-shadow: 0 10px 20px rgba(0,0,0,0.05);
            margin-bottom: 30px;
            overflow: hidden;
            transition: all 0.3s;
            border: none;
        }
        
        .order-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.1);
        }
        
        .order-header {
            padding: 20px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .order-id {
            font-weight: 700;
            font-size: 18px;
            color: var(--accent-color);
        }
        
        .order-date {
            color: #6c757d;
            font-size: 14px;
        }
        
        .order-status {
            padding: 5px 15px;
            border-radius: 30px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .order-status.delivered {
            background-color: var(--light-green);
            color: var(--dark-green);
        }
        
        .order-status.processing {
            background-color: rgba(255, 152, 0, 0.1);
            color: var(--orange-color);
        }
        
        .order-status.cancelled {
            background-color: rgba(220, 53, 69, 0.1);
            color: var(--red-color);
        }
        
        .order-status.pending {
            background-color: rgba(0, 123, 255, 0.1);
            color: #007bff;
        }
        
        .order-body {
            padding: 20px;
        }
        
        .order-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            padding-bottom: 10px;
            border-bottom: 1px solid #f5f5f5;
        }
        
        .order-item:last-child {
            margin-bottom: 0;
            padding-bottom: 0;
            border-bottom: none;
        }
        
        .order-item-name {
            font-weight: 600;
        }
        
        .order-item-quantity {
            color: #6c757d;
        }
        
        .order-item-price {
            font-weight: 600;
            color: var(--accent-color);
        }
        
        .order-footer {
            padding: 15px 20px;
            background-color: #f9f9f9;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .order-total {
            font-weight: 700;
            font-size: 18px;
        }
        
        .order-actions {
            display: flex;
            gap: 10px;
        }
        
        .btn-sm {
            padding: 5px 15px;
            font-size: 12px;
            border-radius: 30px;
        }
        
        /* Order Details */
        .order-details {
            background-color: white;
            border-radius: 15px;
            box-shadow: 0 10px 20px rgba(0,0,0,0.05);
            overflow: hidden;
        }
        
        .order-details-header {
            padding: 25px;
            border-bottom: 1px solid #eee;
            background-color: #f9f9f9;
        }
        
        .order-details-body {
            padding: 25px;
        }
        
        .order-details-item {
            display: flex;
            justify-content: space-between;
            padding: 15px 0;
            border-bottom: 1px solid #f5f5f5;
        }
        
        .order-details-item:last-child {
            border-bottom: none;
        }
        
        .order-details-footer {
            padding: 25px;
            border-top: 1px solid #eee;
            background-color: #f9f9f9;
        }
        
        .payment-info {
            background-color: #f9f9f9;
            padding: 20px;
            border-radius: 10px;
            margin-top: 20px;
        }
        
        .payment-method {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .payment-method img {
            height: 30px;
            margin-right: 10px;
        }
        
        /* Admin Panel Styles */
        .admin-panel {
            background-color: white;
            border-radius: 15px;
            box-shadow: 0 10px 20px rgba(0,0,0,0.05);
            padding: 25px;
            margin-bottom: 30px;
        }
        
        .admin-panel-title {
            font-weight: 700;
            margin-bottom: 20px;
            color: var(--accent-color);
        }
        
        .admin-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }
        
        .admin-table th {
            background-color: #f1f1f1;
            padding: 15px;
            font-weight: 600;
            text-align: left;
        }
        
        .admin-table td {
            padding: 15px;
            border-top: 1px solid #f1f1f1;
        }
        
        .admin-table tr:hover {
            background-color: #f9f9f9;
        }
        
        .status-select {
            padding: 8px 15px;
            border-radius: 30px;
            border: 1px solid #ddd;
            background-color: white;
            font-size: 14px;
            font-weight: 500;
            color: #333;
            width: 100%;
        }
        
        /* Footer */
        .footer {
            background-color: #f8f9fa;
            padding: 60px 0 30px;
            margin-top: 60px;
            border-top: 1px solid #eee;
        }
        
        .footer-title {
            font-weight: 700;
            font-size: 18px;
            margin-bottom: 20px;
            color: #333;
        }
        
        .footer-links {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .footer-links li {
            margin-bottom: 10px;
        }
        
        .footer-links a {
            color: #6c757d;
            text-decoration: none;
            transition: all 0.3s;
        }
        
        .footer-links a:hover {
            color: var(--accent-color);
            padding-left: 5px;
        }
        
        .footer-bottom {
            text-align: center;
            padding-top: 30px;
            margin-top: 30px;
            border-top: 1px solid #eee;
            color: #6c757d;
        }
        
        .social-links {
            display: flex;
            margin-top: 20px;
        }
        
        .social-link {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: #f1f1f1;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 10px;
            transition: all 0.3s;
        }
        
        .social-link:hover {
            background-color: var(--accent-color);
            transform: translateY(-3px);
        }
        
        .social-link:hover i {
            color: white;
        }
        
        .social-link i {
            color: #6c757d;
            font-size: 18px;
        }
        
        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 60px 0;
        }
        
        .empty-state h3 {
            font-weight: 700;
            margin-bottom: 15px;
            color: #333;
        }
        
        .empty-state p {
            color: #6c757d;
            margin-bottom: 30px;
            max-width: 500px;
            margin-left: auto;
            margin-right: auto;
        }
        
        /* Customer Info */
        .customer-info {
            background-color: #f9f9f9;
            padding: 20px;
            border-radius: 10px;
            margin-top: 20px;
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .order-header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .order-status {
                margin-top: 10px;
            }
            
            .order-footer {
                flex-direction: column;
                gap: 15px;
            }
            
            .order-actions {
                width: 100%;
                justify-content: space-between;
            }
            
            .admin-table {
                display: block;
                overflow-x: auto;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary fixed-top">
        <div class="container">
            <a class="navbar-brand" href="#"><i class="fa fa-leaf" aria-hidden="true"></i> ADMT Farm Management </a>
            <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarSupportedContent">
                <ul class="navbar-nav mr-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="fruit-vegetable-sales.php">Fruits & Vegetables</a>
                    </li>
                    <li class="nav-item active">
                        <a class="nav-link" href="orders.php">My Orders</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="logout1.php"><i class="fa fa-sign-out" aria-hidden="true"></i> Logout</a>
                    </li>
                </ul>
                <ul class="navbar-nav ml-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="fruit-vegetable-sales.php?page=cart">
                            <div class="cart-badge">
                                <i class="fa fa-shopping-cart" aria-hidden="true"></i>
                                <span class="cart-count"><?php echo getCartCount($sessionCart); ?></span>
                            </div>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#"><i class="fa fa-user-circle" aria-hidden="true"></i> <?php echo $username; ?></a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container" style="margin-top:30px;">
        <?php if($statusUpdateMessage): ?>
            <div class="row">
                <div class="col-12">
                    <?php echo $statusUpdateMessage; ?>
                </div>
            </div>
        <?php endif; ?>
        
        <?php if($isAdmin && !$selectedOrder): ?>
            <!-- Admin Orders Management Panel -->
            <div class="row">
                <div class="col-12">
                    <h2 class="section-title">Orders Management</h2>
                    <div class="admin-panel">
                        <h4 class="admin-panel-title">All Customer Orders</h4>
                        
                        <?php if(empty($orders)): ?>
                            <div class="alert alert-info">No orders found in the system.</div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="admin-table">
                                    <thead>
                                        <tr>
                                            <th>Order ID</th>
                                            <th>Customer</th>
                                            <th>Date</th>
                                            <th>Total</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($orders as $order): ?>
                                            <tr>
                                                <td><?php echo $order['id']; ?></td>
                                                <td><?php echo $order['customer']; ?></td>
                                                <td><?php echo date('F j, Y', strtotime($order['date'])); ?></td>
                                                <td>KSh <?php echo $order['total']; ?></td>
                                                <td>
                                                    <form method="post" class="d-flex align-items-center">
                                                        <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                                        <select name="new_status" class="status-select mr-2">
                                                            <option value="Pending" <?php echo $order['status'] === 'Pending' ? 'selected' : ''; ?>>Pending</option>
                                                            <option value="Processing" <?php echo $order['status'] === 'Processing' ? 'selected' : ''; ?>>Processing</option>
                                                            <option value="Delivered" <?php echo $order['status'] === 'Delivered' ? 'selected' : ''; ?>>Delivered</option>
                                                            <option value="Cancelled" <?php echo $order['status'] === 'Cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                                        </select>
                                                        <button type="submit" name="update_order_status" class="btn btn-primary btn-sm">Update</button>
                                                    </form>
                                                </td>
                                                <td>
                                                    <a href="?order_id=<?php echo $order['id']; ?>" class="btn btn-info btn-sm">View Details</a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
        <?php if($selectedOrder): ?>
            <!-- Order Details View -->
            <div class="row mb-4">
                <div class="col-12">
                    <a href="orders.php" class="btn btn-outline-primary">
                        <i class="fa fa-arrow-left"></i> Back to Orders
                    </a>
                </div>
            </div>
            
            <div class="row">
                <div class="col-12">
                    <h2 class="section-title">Order Details</h2>
                </div>
            </div>
            
            <div class="row">
                <div class="col-12">
                    <div class="order-details">
                        <div class="order-details-header">
                            <div class="row">
                                <div class="col-md-3">
                                    <h5>Order ID</h5>
                                    <p class="mb-0 font-weight-bold"><?php echo $selectedOrder['id']; ?></p>
                                </div>
                                <div class="col-md-3">
                                    <h5>Order Date</h5>
                                    <p class="mb-0"><?php echo date('F j, Y', strtotime($selectedOrder['date'])); ?></p>
                                </div>
                                <div class="col-md-3">
                                    <h5>Status</h5>
                                    <span class="order-status <?php echo strtolower($selectedOrder['status']); ?>">
                                        <?php echo $selectedOrder['status']; ?>
                                    </span>
                                </div>
                                <?php if($isAdmin): ?>
                                <div class="col-md-3">
                                    <h5>Update Status</h5>
                                    <form method="post" class="d-flex align-items-center">
                                        <input type="hidden" name="order_id" value="<?php echo $selectedOrder['id']; ?>">
                                        <select name="new_status" class="status-select mr-2">
                                            <option value="Pending" <?php echo $selectedOrder['status'] === 'Pending' ? 'selected' : ''; ?>>Pending</option>
                                            <option value="Processing" <?php echo $selectedOrder['status'] === 'Processing' ? 'selected' : ''; ?>>Processing</option>
                                            <option value="Delivered" <?php echo $selectedOrder['status'] === 'Delivered' ? 'selected' : ''; ?>>Delivered</option>
                                            <option value="Cancelled" <?php echo $selectedOrder['status'] === 'Cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                        </select>
                                        <button type="submit" name="update_order_status" class="btn btn-primary btn-sm">Update</button>
                                    </form>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="order-details-body">
                            <h5 class="mb-3">Items</h5>
                            <?php foreach($selectedOrder['items'] as $item): ?>
                                <div class="order-details-item">
                                    <div>
                                        <div class="font-weight-bold"><?php echo $item['name']; ?></div>
                                        <div class="text-muted">Quantity: <?php echo $item['quantity']; ?></div>
                                    </div>
                                    <div class="font-weight-bold">KSh <?php echo $item['price'] * $item['quantity']; ?></div>
                                </div>
                            <?php endforeach; ?>
                            
                            <div class="payment-info">
                                <h5 class="mb-3">Payment Information</h5>
                                <div class="payment-method">
                                    <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/1/15/M-PESA_LOGO-01.svg/320px-M-PESA_LOGO-01.svg.png" alt="M-Pesa Logo">
                                    <span>Paid with M-Pesa</span>
                                </div>
                                <div>Transaction ID: <?php echo $selectedOrder['transaction_id']; ?></div>
                            </div>
                            
                            <?php if($isAdmin): ?>
                            <div class="customer-info">
                                <h5 class="mb-3">Customer Information</h5>
                                <div class="row">
                                    <div class="col-md-4">
                                        <strong>Name:</strong> <?php echo $selectedOrder['customer']; ?>
                                    </div>
                                    <div class="col-md-4">
                                        <strong>Phone:</strong> <?php echo $selectedOrder['phone']; ?>
                                    </div>
                                    <div class="col-md-4">
                                        <strong>Order ID:</strong> <?php echo $selectedOrder['id']; ?>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="order-details-footer">
                            <div class="row">
                                <div class="col-md-6">
                                    <h5>Delivery Address</h5>
                                    <p class="mb-0"><?php echo $selectedOrder['address']; ?></p>
                                </div>
                                <div class="col-md-6 text-md-right">
                                    <h5>Order Total</h5>
                                    <p class="mb-0 font-weight-bold">KSh <?php echo $selectedOrder['total']; ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
        <?php elseif(!$isAdmin): ?>
            <!-- Orders List View for Regular Users -->
            <div class="row">
                <div class="col-12">
                    <h2 class="section-title">My Orders</h2>
                </div>
            </div>
            
            <?php if(empty($orders)): ?>
                <div class="empty-state">
                    <h3>No orders found</h3>
                    <p>You haven't placed any orders yet. Browse our products and place your first order.</p>
                    <a href="fruit-vegetable-sales.php" class="btn btn-primary">Shop Now</a>
                </div>
            <?php else: ?>
                <div class="row">
                    <?php foreach($orders as $order): ?>
                        <div class="col-md-6">
                            <div class="order-card">
                                <div class="order-header">
                                    <div>
                                        <div class="order-id"><?php echo $order['id']; ?></div>
                                        <div class="order-date"><?php echo date('F j, Y', strtotime($order['date'])); ?></div>
                                    </div>
                                    <span class="order-status <?php echo strtolower($order['status']); ?>">
                                        <?php echo $order['status']; ?>
                                    </span>
                                </div>
                                <div class="order-body">
                                    <?php 
                                    $itemCount = count($order['items']);
                                    $displayItems = array_slice($order['items'], 0, 2);
                                    
                                    foreach($displayItems as $item): 
                                    ?>
                                        <div class="order-item">
                                            <div class="order-item-name"><?php echo $item['name']; ?></div>
                                            <div class="order-item-quantity">x<?php echo $item['quantity']; ?></div>
                                            <div class="order-item-price">KSh <?php echo $item['price'] * $item['quantity']; ?></div>
                                        </div>
                                    <?php endforeach; ?>
                                    
                                    <?php if($itemCount > 2): ?>
                                        <div class="text-center mt-2">
                                            <small class="text-muted">+ <?php echo $itemCount - 2; ?> more items</small>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="order-footer">
                                    <div class="order-total">Total: KSh <?php echo $order['total']; ?></div>
                                    <div class="order-actions">
                                        <a href="?order_id=<?php echo $order['id']; ?>" class="btn btn-primary btn-sm">View Details</a>
                                        <?php if($order['status'] === 'Pending' || $order['status'] === 'Processing'): ?>
                                            <button class="btn btn-outline-danger btn-sm">Cancel Order</button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="row">
                <div class="col-md-4 mb-4">
                    <h4 class="footer-title">ADMT Farm Management</h4>
                    <p>We provide farm-fresh fruits and vegetables directly from local farmers to your doorstep.</p>
                    <div class="social-links">
                        <a href="#" class="social-link"><i class="fa fa-facebook"></i></a>
                        <a href="#" class="social-link"><i class="fa fa-twitter"></i></a>
                        <a href="#" class="social-link"><i class="fa fa-instagram"></i></a>
                        <a href="#" class="social-link"><i class="fa fa-linkedin"></i></a>
                    </div>
                </div>
                <div class="col-md-2 col-6 mb-4">
                    <h4 class="footer-title">Quick Links</h4>
                    <ul class="footer-links">
                        <li><a href="dashboard.php">Dashboard</a></li>
                        <li><a href="fruit-vegetable-sales.php">Shop</a></li>
                        <li><a href="orders.php">My Orders</a></li>
                        <li><a href="#">About Us</a></li>
                        <li><a href="#">Contact</a></li>
                    </ul>
                </div>
                <div class="col-md-2 col-6 mb-4">
                    <h4 class="footer-title">Categories</h4>
                    <ul class="footer-links">
                        <li><a href="fruit-vegetable-sales.php?page=products&category=fruits">Fruits</a></li>
                        <li><a href="fruit-vegetable-sales.php?page=products&category=vegetables">Vegetables</a></li>
                        <li><a href="fruit-vegetable-sales.php?page=products&category=organic">Organic</a></li>
                    </ul>
                </div>
                <div class="col-md-4 mb-4">
                    <h4 class="footer-title">Contact Us</h4>
                    <ul class="footer-links">
                        <li><i class="fa fa-map-marker mr-2"></i> 123 Farm Road, Nairobi, Kenya</li>
                        <li><i class="fa fa-phone mr-2"></i> +254 712 345 678</li>
                        <li><i class="fa fa-envelope mr-2"></i> info@admtfarm.co.ke</li>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> ADMT Farm Management. All Rights Reserved.</p>
            </div>
        </div>
    </footer>

    <!-- JavaScript -->
    <script src="https://code.jquery.com/jquery-3.2.1.slim.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.11.0/umd/popper.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0-beta/js/bootstrap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/limonte-sweetalert2/6.10.1/sweetalert2.all.min.js"></script>

    <script>
        // Prevent form resubmission on page refresh
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }
    </script>
</body>
</html>
