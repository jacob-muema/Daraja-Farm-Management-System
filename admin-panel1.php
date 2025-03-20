<?php
session_start(); // Start session once at the beginning

// Set default admin values to bypass login checks
$_SESSION['username'] = 'admin';
$_SESSION['is_admin'] = 1;
$username = 'admin';

// Database connection
$conn = new mysqli("localhost", "root", "", "fms");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
// Initialize variables
$responseMessage = '';
$editProduct = null;

// Handle Product Operations (CRUD)
// 1. Add New Product
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_product'])) {
    $name = trim($_POST['name']);
    $category = trim($_POST['category']);
    $price = floatval($_POST['price']);
    $image = trim($_POST['image']);
    $is_organic = isset($_POST['is_organic']) ? 1 : 0;
    $stock_quantity = intval($_POST['stock_quantity']);
    $description = trim($_POST['description']);
    
    if (empty($name) || empty($category) || $price <= 0 || empty($image)) {
        $responseMessage = "<p style='color: red;'>Please fill all required fields.</p>";
    } else {
        $stmt = $conn->prepare("INSERT INTO grocery_products (name, category, price, image, is_organic, stock_quantity, description) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssdsiss", $name, $category, $price, $image, $is_organic, $stock_quantity, $description);
        
        if ($stmt->execute()) {
            $responseMessage = "<p style='color: green;'>Product added successfully!</p>";
        } else {
            $responseMessage = "<p style='color: red;'>Failed to add product: " . $stmt->error . "</p>";
        }
    }
}

// 2. Update Product
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_product'])) {
    $id = intval($_POST['product_id']);
    $name = trim($_POST['name']);
    $category = trim($_POST['category']);
    $price = floatval($_POST['price']);
    $image = trim($_POST['image']);
    $is_organic = isset($_POST['is_organic']) ? 1 : 0;
    $stock_quantity = intval($_POST['stock_quantity']);
    $description = trim($_POST['description']);
    
    if (empty($name) || empty($category) || $price <= 0) {
        $responseMessage = "<p style='color: red;'>Please fill all required fields.</p>";
    } else {
        $stmt = $conn->prepare("UPDATE grocery_products SET name=?, category=?, price=?, image=?, is_organic=?, stock_quantity=?, description=? WHERE id=?");
        $stmt->bind_param("ssdsisii", $name, $category, $price, $image, $is_organic, $stock_quantity, $description, $id);
        
        if ($stmt->execute()) {
            $responseMessage = "<p style='color: green;'>Product updated successfully!</p>";
        } else {
            $responseMessage = "<p style='color: red;'>Failed to update product: " . $stmt->error . "</p>";
        }
    }
}

// 3. Delete Product
if (isset($_GET['delete_product'])) {
    $id = intval($_GET['delete_product']);
    
    // Check if product exists
    $stmt = $conn->prepare("SELECT id FROM grocery_products WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $stmt = $conn->prepare("DELETE FROM grocery_products WHERE id = ?");
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            $responseMessage = "<p style='color: green;'>Product deleted successfully!</p>";
        } else {
            $responseMessage = "<p style='color: red;'>Failed to delete product: " . $stmt->error . "</p>";
        }
    } else {
        $responseMessage = "<p style='color: red;'>Product not found!</p>";
    }
}

// 4. Edit Product (Get product data for editing)
if (isset($_GET['edit_product'])) {
    $id = intval($_GET['edit_product']);
    
    $stmt = $conn->prepare("SELECT * FROM grocery_products WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $editProduct = $result->fetch_assoc();
    }
}

// Handle Order Operations
// 1. Delete Order
if (isset($_GET['delete_order'])) {
    $id = intval($_GET['delete_order']);
    
    // Check if order exists
    $stmt = $conn->prepare("SELECT id FROM grocery_orders WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        // Delete order items first (cascade should handle this, but just to be safe)
        $stmt = $conn->prepare("DELETE FROM grocery_order_items WHERE order_id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        
        // Delete the order
        $stmt = $conn->prepare("DELETE FROM grocery_orders WHERE id = ?");
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            $responseMessage = "<p style='color: green;'>Order deleted successfully!</p>";
        } else {
            $responseMessage = "<p style='color: red;'>Failed to delete order: " . $stmt->error . "</p>";
        }
    } else {
        $responseMessage = "<p style='color: red;'>Order not found!</p>";
    }
}

// 2. Update Order Status
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_order_status'])) {
    $id = intval($_POST['order_id']);
    $status = trim($_POST['order_status']);
    $payment_status = trim($_POST['payment_status']);
    
    $stmt = $conn->prepare("UPDATE grocery_orders SET order_status=?, payment_status=? WHERE id=?");
    $stmt->bind_param("ssi", $status, $payment_status, $id);
    
    if ($stmt->execute()) {
        $responseMessage = "<p style='color: green;'>Order status updated successfully!</p>";
    } else {
        $responseMessage = "<p style='color: red;'>Failed to update order status: " . $stmt->error . "</p>";
    }
}

// Get all products
$stmt = $conn->prepare("SELECT * FROM grocery_products ORDER BY category, name");
$stmt->execute();
$products = $stmt->get_result();

// Get all orders
$stmt = $conn->prepare("SELECT * FROM grocery_orders ORDER BY order_date DESC");
$stmt->execute();
$orders = $stmt->get_result();

// Get username from session
$username = $_SESSION['username'];

// Current page (products or orders)
$currentPage = isset($_GET['page']) ? $_GET['page'] : 'products';

// Dashboard statistics
// Total products
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM grocery_products");
$stmt->execute();
$result = $stmt->get_result();
$productCount = $result->fetch_assoc()['count'];

// Total orders
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM grocery_orders");
$stmt->execute();
$result = $stmt->get_result();
$orderCount = $result->fetch_assoc()['count'];

// Total sales
$stmt = $conn->prepare("SELECT SUM(amount) as total FROM grocery_orders WHERE payment_status = 'Paid'");
$stmt->execute();
$result = $stmt->get_result();
$totalSales = $result->fetch_assoc()['total'] ?? 0;

// Total customers
$stmt = $conn->prepare("SELECT COUNT(DISTINCT user_id) as count FROM grocery_orders");
$stmt->execute();
$result = $stmt->get_result();
$customerCount = $result->fetch_assoc()['count'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Grocery Admin Panel</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary-color: #27ae60;
            --secondary-color: #2ecc71;
            --dark-color: #219653;
            --light-color: #e9f7ef;
            --danger-color: #e74c3c;
            --warning-color: #f39c12;
            --info-color: #3498db;
            --success-color: #2ecc71;
            --text-color: #333333;
            --light-text: #7f8c8d;
            --white: #ffffff;
            --light-bg: #f9f9f9;
            --border-color: #e0e0e0;
            --box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--light-bg);
            color: var(--text-color);
            line-height: 1.6;
            padding-top: 76px;
        }
        
        /* Header & Navigation */
        .navbar {
            background-color: var(--white);
            box-shadow: var(--box-shadow);
            padding: 15px 0;
        }
        
        .navbar-brand {
            font-weight: 700;
            color: var(--primary-color) !important;
            font-size: 24px;
        }
        
        .navbar-brand i {
            color: var(--primary-color);
            margin-right: 5px;
        }
        
        .nav-link {
            color: var(--text-color) !important;
            font-weight: 500;
            margin: 0 10px;
            transition: all 0.3s ease;
        }
        
        .nav-link:hover, .nav-link.active {
            color: var(--primary-color) !important;
        }
        
        /* Dashboard Cards */
        .dashboard-card {
            background-color: var(--white);
            border-radius: 8px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: var(--box-shadow);
            transition: all 0.3s ease;
            height: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
        }
        
        .dashboard-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.1);
        }
        
        .dashboard-icon {
            width: 70px;
            height: 70px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
            font-size: 30px;
            color: var(--white);
        }
        
        .dashboard-icon.products {
            background-color: var(--primary-color);
        }
        
        .dashboard-icon.orders {
            background-color: var(--info-color);
        }
        
        .dashboard-icon.sales {
            background-color: var(--success-color);
        }
        
        .dashboard-icon.customers {
            background-color: var(--warning-color);
        }
        
        .dashboard-number {
            font-size: 36px;
            font-weight: 700;
            margin-bottom: 10px;
        }
        
        .dashboard-title {
            font-size: 16px;
            color: var(--light-text);
            font-weight: 500;
        }
        
        /* Admin Tabs */
        .admin-tabs {
            display: flex;
            margin-bottom: 30px;
            background-color: var(--white);
            border-radius: 8px;
            overflow: hidden;
            box-shadow: var(--box-shadow);
        }
        
        .admin-tab {
            flex: 1;
            text-align: center;
            padding: 15px;
            background-color: var(--white);
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 600;
            color: var(--text-color);
            text-decoration: none;
            border-bottom: 3px solid transparent;
        }
        
        .admin-tab.active {
            color: var(--primary-color);
            border-bottom: 3px solid var(--primary-color);
        }
        
        .admin-tab:hover:not(.active) {
            background-color: var(--light-bg);
        }
        
        /* Product Form */
        .product-form {
            background-color: var(--white);
            border-radius: 8px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: var(--box-shadow);
        }
        
        .product-form h3 {
            margin-bottom: 20px;
            color: var(--primary-color);
            font-weight: 600;
        }
        
        .form-group label {
            font-weight: 500;
            margin-bottom: 8px;
        }
        
        .form-control {
            border: 1px solid var(--border-color);
            border-radius: 4px;
            padding: 10px;
            margin-bottom: 15px;
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(39, 174, 96, 0.25);
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            padding: 10px 20px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover {
            background-color: var(--dark-color);
            border-color: var(--dark-color);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .btn-secondary {
            background-color: #6c757d;
            border-color: #6c757d;
            padding: 10px 20px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-secondary:hover {
            background-color: #5a6268;
            border-color: #545b62;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        /* Product Table */
        .table-container {
            background-color: var(--white);
            border-radius: 8px;
            overflow: hidden;
            box-shadow: var(--box-shadow);
            margin-bottom: 30px;
        }
        
        .table {
            margin-bottom: 0;
        }
        
        .table thead th {
            background-color: var(--light-bg);
            border-top: none;
            font-weight: 600;
            color: var(--text-color);
            padding: 15px;
        }
        
        .table tbody td {
            padding: 15px;
            vertical-align: middle;
        }
        
        .product-image {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 4px;
        }
        
        .action-buttons {
            display: flex;
            gap: 10px;
        }
        
        .btn-action {
            width: 36px;
            height: 36px;
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--white);
            transition: all 0.3s ease;
        }
        
        .btn-action:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .btn-edit {
            background-color: var(--warning-color);
        }
        
        .btn-edit:hover {
            background-color: #e67e22;
        }
        
        .btn-delete {
            background-color: var(--danger-color);
        }
        
        .btn-delete:hover {
            background-color: #c0392b;
        }
        
        .btn-view {
            background-color: var(--info-color);
        }
        
        .btn-view:hover {
            background-color: #2980b9;
        }
        
        /* Status Badges */
        .status-badge {
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }
        
        .status-processing {
            background-color: #cce5ff;
            color: #004085;
        }
        
        .status-shipped {
            background-color: #d1ecf1;
            color: #0c5460;
        }
        
        .status-delivered {
            background-color: #d4edda;
            color: #155724;
        }
        
        .status-cancelled {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        /* Order Modal */
        .modal-content {
            border-radius: 8px;
            border: none;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        
        .modal-header {
            background-color: var(--light-color);
            border-bottom: none;
            padding: 20px;
        }
        
        .modal-title {
            color: var(--dark-color);
            font-weight: 600;
        }
        
        .modal-body {
            padding: 20px;
        }
        
        .modal-footer {
            border-top: none;
            padding: 20px;
        }
        
        .customer-info {
            background-color: var(--light-bg);
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .customer-info h5 {
            color: var(--primary-color);
            margin-bottom: 15px;
            font-weight: 600;
        }
        
        .customer-info p {
            margin-bottom: 10px;
        }
        
        .customer-info strong {
            display: inline-block;
            width: 120px;
        }
        
        .order-item {
            display: flex;
            align-items: center;
            padding: 15px;
            border-bottom: 1px solid var(--border-color);
        }
        
        .order-item:last-child {
            border-bottom: none;
        }
        
        .order-item-image {
            width: 60px;
            height: 60px;
            border-radius: 4px;
            overflow: hidden;
            margin-right: 15px;
        }
        
        .order-item-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .order-item-details {
            flex: 1;
        }
        
        .order-item-name {
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .order-item-price {
            color: var(--primary-color);
            font-weight: 500;
        }
        
        .order-item-quantity {
            margin-left: 15px;
            background-color: var(--light-bg);
            padding: 5px 10px;
            border-radius: 4px;
            font-weight: 600;
            font-size: 14px;
        }
        
        /* Section Title */
        .section-title {
            margin-bottom: 30px;
            position: relative;
            padding-bottom: 15px;
            font-weight: 600;
        }
        
        .section-title:after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 80px;
            height: 3px;
            background-color: var(--primary-color);
        }
        
        /* Organic Badge */
        .organic-badge {
            background-color: var(--success-color);
            color: var(--white);
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
            margin-left: 10px;
        }
        
        /* Footer */
        .footer {
            background-color: var(--white);
            padding: 20px 0;
            margin-top: 60px;
            border-top: 1px solid var(--border-color);
            text-align: center;
            color: var(--light-text);
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .admin-tabs {
                flex-direction: column;
            }
            
            .action-buttons {
                flex-direction: column;
                gap: 5px;
            }
            
            .btn-action {
                width: 100%;
            }
            
            .dashboard-card {
                margin-bottom: 15px;
            }
            
            .dashboard-number {
                font-size: 28px;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-light fixed-top">
        <div class="container">
            <a class="navbar-brand" href="#"><i class="fas fa-shopping-basket"></i> Grocery Admin</a>
            <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav mr-auto">
                    <!-- <li class="nav-item">
                        <a class="nav-link" href="grocery-admin.php">Dashboard</a>
                    </li> -->
                    <li class="nav-item">
                        <a class="nav-link <?php echo $currentPage == 'products' ? 'active' : ''; ?>" href="?page=products">Products</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $currentPage == 'orders' ? 'active' : ''; ?>" href="?page=orders">Orders</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="grocery-shop.php" target="_blank">View Shop</a>
                    </li>
                </ul>
                <ul class="navbar-nav ml-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="#"><i class="fas fa-user"></i> <?php echo $username; ?></a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="logout1.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <?php if($responseMessage): ?>
            <div class="alert alert-info alert-dismissible fade show" role="alert">
                <?php echo $responseMessage; ?>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        <?php endif; ?>
        
        <!-- Dashboard Stats -->
        <div class="row mb-4">
            <div class="col-md-3 col-sm-6">
                <div class="dashboard-card">
                    <div class="dashboard-icon products">
                        <i class="fas fa-shopping-basket"></i>
                    </div>
                    <div class="dashboard-number"><?php echo $productCount; ?></div>
                    <div class="dashboard-title">Total Products</div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="dashboard-card">
                    <div class="dashboard-icon orders">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <div class="dashboard-number"><?php echo $orderCount; ?></div>
                    <div class="dashboard-title">Total Orders</div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="dashboard-card">
                    <div class="dashboard-icon sales">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                    <div class="dashboard-number">KSh <?php echo number_format($totalSales, 0); ?></div>
                    <div class="dashboard-title">Total Sales</div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="dashboard-card">
                    <div class="dashboard-icon customers">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="dashboard-number"><?php echo $customerCount; ?></div>
                    <div class="dashboard-title">Customers</div>
                </div>
            </div>
        </div>
        
        <!-- Admin Tabs -->
        <div class="admin-tabs">
            <a href="?page=products" class="admin-tab <?php echo $currentPage == 'products' ? 'active' : ''; ?>">
                <i class="fas fa-shopping-basket mr-2"></i> Manage Products
            </a>
            <a href="?page=orders" class="admin-tab <?php echo $currentPage == 'orders' ? 'active' : ''; ?>">
                <i class="fas fa-shopping-cart mr-2"></i> Manage Orders
            </a>
        </div>
        
        <?php if($currentPage == 'products'): ?>
            <!-- Product Management Section -->
            <div class="row">
                <div class="col-md-4">
                    <div class="product-form">
                        <h3><?php echo $editProduct ? 'Edit Product' : 'Add New Product'; ?></h3>
                        <form method="post" action="">
                            <?php if($editProduct): ?>
                                <input type="hidden" name="product_id" value="<?php echo $editProduct['id']; ?>">
                            <?php endif; ?>
                            
                            <div class="form-group">
                                <label for="name">Product Name</label>
                                <input type="text" class="form-control" id="name" name="name" value="<?php echo $editProduct ? $editProduct['name'] : ''; ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="category">Category</label>
                                <select class="form-control" id="category" name="category" required>
                                    <option value="">Select Category</option>
                                    <option value="Fruits" <?php echo ($editProduct && $editProduct['category'] == 'Fruits') ? 'selected' : ''; ?>>Fruits</option>
                                    <option value="Vegetables" <?php echo ($editProduct && $editProduct['category'] == 'Vegetables') ? 'selected' : ''; ?>>Vegetables</option>
                                    <option value="Grains" <?php echo ($editProduct && $editProduct['category'] == 'Grains') ? 'selected' : ''; ?>>Grains</option>
                                    <option value="Essentials" <?php echo ($editProduct && $editProduct['category'] == 'Essentials') ? 'selected' : ''; ?>>Essentials</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="price">Price (KSh)</label>
                                <input type="number" class="form-control" id="price" name="price" min="0" step="0.01" value="<?php echo $editProduct ? $editProduct['price'] : ''; ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="image">Image URL</label>
                                <input type="text" class="form-control" id="image" name="image" value="<?php echo $editProduct ? $editProduct['image'] : ''; ?>" required>
                                <small class="form-text text-muted">Enter a URL for the product image</small>
                            </div>
                            
                            <div class="form-group">
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input" id="is_organic" name="is_organic" <?php echo ($editProduct && $editProduct['is_organic'] == 1) ? 'checked' : ''; ?>>
                                    <label class="custom-control-label" for="is_organic">Organic Product</label>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="stock_quantity">Stock Quantity</label>
                                <input type="number" class="form-control" id="stock_quantity" name="stock_quantity" min="0" value="<?php echo $editProduct ? $editProduct['stock_quantity'] : '0'; ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="description">Description</label>
                                <textarea class="form-control" id="description" name="description" rows="3"><?php echo $editProduct ? $editProduct['description'] : ''; ?></textarea>
                            </div>
                            
                            <?php if($editProduct): ?>
                                <button type="submit" name="update_product" class="btn btn-primary btn-block">
                                    <i class="fas fa-save mr-2"></i> Update Product
                                </button>
                                <a href="grocery-admin.php?page=products" class="btn btn-secondary btn-block mt-2">
                                    <i class="fas fa-times mr-2"></i> Cancel
                                </a>
                            <?php else: ?>
                                <button type="submit" name="add_product" class="btn btn-primary btn-block">
                                    <i class="fas fa-plus mr-2"></i> Add Product
                                </button>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>
                
                <div class="col-md-8">
                    <div class="table-container">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Image</th>
                                        <th>Name</th>
                                        <th>Category</th>
                                        <th>Price</th>
                                        <th>Stock</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if($products->num_rows > 0): ?>
                                        <?php while($product = $products->fetch_assoc()): ?>
                                            <tr>
                                                <td>
                                                    <img src="<?php echo $product['image']; ?>" alt="<?php echo $product['name']; ?>" class="product-image">
                                                </td>
                                                <td>
                                                    <?php echo $product['name']; ?>
                                                    <?php if($product['is_organic']): ?>
                                                        <span class="organic-badge">Organic</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo $product['category']; ?></td>
                                                <td>KSh <?php echo number_format($product['price'], 2); ?></td>
                                                <td><?php echo $product['stock_quantity']; ?></td>
                                                <td>
                                                    <div class="action-buttons">
                                                        <a href="?page=products&edit_product=<?php echo $product['id']; ?>" class="btn-action btn-edit" title="Edit">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <a href="?page=products&delete_product=<?php echo $product['id']; ?>" class="btn-action btn-delete" title="Delete" onclick="return confirm('Are you sure you want to delete this product?');">
                                                            <i class="fas fa-trash"></i>
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="6" class="text-center">No products found. Add some products to get started.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        <?php elseif($currentPage == 'orders'): ?>
            <!-- Order Management Section -->
            <div class="row">
                <div class="col-12">
                    <h2 class="section-title">Manage Orders</h2>
                    <div class="table-container">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Order ID</th>
                                        <th>Customer</th>
                                        <th>Amount</th>
                                        <th>Date</th>
                                        <th>Status</th>
                                        <th>Payment</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if($orders->num_rows > 0): ?>
                                        <?php while($order = $orders->fetch_assoc()): ?>
                                            <tr>
                                                <td>#<?php echo $order['id']; ?></td>
                                                <td><?php echo $order['customer_name']; ?></td>
                                                <td>KSh <?php echo number_format($order['amount'], 2); ?></td>
                                                <td><?php echo date('M d, Y', strtotime($order['order_date'])); ?></td>
                                                <td>
                                                    <?php
                                                    $statusClass = '';
                                                    switch($order['order_status']) {
                                                        case 'Pending':
                                                            $statusClass = 'status-pending';
                                                            break;
                                                        case 'Processing':
                                                            $statusClass = 'status-processing';
                                                            break;
                                                        case 'Shipped':
                                                            $statusClass = 'status-shipped';
                                                            break;
                                                        case 'Delivered':
                                                            $statusClass = 'status-delivered';
                                                            break;
                                                        case 'Cancelled':
                                                            $statusClass = 'status-cancelled';
                                                            break;
                                                    }
                                                    ?>
                                                    <span class="status-badge <?php echo $statusClass; ?>"><?php echo $order['order_status']; ?></span>
                                                </td>
                                                <td>
                                                    <?php
                                                    $paymentClass = '';
                                                    switch($order['payment_status']) {
                                                        case 'Pending':
                                                            $paymentClass = 'status-pending';
                                                            break;
                                                        case 'Paid':
                                                            $paymentClass = 'status-delivered';
                                                            break;
                                                        case 'Failed':
                                                            $paymentClass = 'status-cancelled';
                                                            break;
                                                    }
                                                    ?>
                                                    <span class="status-badge <?php echo $paymentClass; ?>"><?php echo $order['payment_status']; ?></span>
                                                </td>
                                                <td>
                                                    <div class="action-buttons">
                                                        <button type="button" class="btn-action btn-view" data-toggle="modal" data-target="#orderModal<?php echo $order['id']; ?>" title="View Details">
                                                            <i class="fas fa-eye"></i>
                                                        </button>
                                                        <a href="?page=orders&delete_order=<?php echo $order['id']; ?>" class="btn-action btn-delete" title="Delete Order" onclick="return confirm('Are you sure you want to delete this order? This action cannot be undone.');">
                                                            <i class="fas fa-trash"></i>
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                            
                                            <!-- Order Details Modal -->
                                            <div class="modal fade" id="orderModal<?php echo $order['id']; ?>" tabindex="-1" role="dialog" aria-labelledby="orderModalLabel<?php echo $order['id']; ?>" aria-hidden="true">
                                                <div class="modal-dialog modal-lg" role="document">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title" id="orderModalLabel<?php echo $order['id']; ?>">Order #<?php echo $order['id']; ?> Details</h5>
                                                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                                <span aria-hidden="true">&times;</span>
                                                            </button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <div class="customer-info">
                                                                <h5>Customer Information</h5>
                                                                <p><strong>Name:</strong> <?php echo $order['customer_name']; ?></p>
                                                                <p><strong>Phone:</strong> <?php echo $order['phone']; ?></p>
                                                                <p><strong>Address:</strong> <?php echo $order['delivery_address']; ?></p>
                                                                <?php if(!empty($order['order_notes'])): ?>
                                                                    <p><strong>Notes:</strong> <?php echo $order['order_notes']; ?></p>
                                                                <?php endif; ?>
                                                            </div>
                                                            
                                                            <h5>Order Items</h5>
                                                            <div class="order-items">
                                                                <?php
                                                                // Get order items
                                                                $stmt = $conn->prepare("SELECT * FROM grocery_order_items WHERE order_id = ?");
                                                                $stmt->bind_param("i", $order['id']);
                                                                $stmt->execute();
                                                                $orderItems = $stmt->get_result();
                                                                
                                                                if($orderItems->num_rows > 0):
                                                                    while($item = $orderItems->fetch_assoc()):
                                                                        // Get product image
                                                                        $productId = $item['product_id'];
                                                                        $productImage = '';
                                                                        
                                                                        $stmt = $conn->prepare("SELECT image FROM grocery_products WHERE id = ?");
                                                                        $stmt->bind_param("i", $productId);
                                                                        $stmt->execute();
                                                                        $productResult = $stmt->get_result();
                                                                        
                                                                        if($productResult->num_rows > 0) {
                                                                            $productImage = $productResult->fetch_assoc()['image'];
                                                                        }
                                                                ?>
                                                                    <div class="order-item">
                                                                        <div class="order-item-image">
                                                                            <img src="<?php echo !empty($productImage) ? $productImage : 'https://via.placeholder.com/60'; ?>" alt="<?php echo $item['product_name']; ?>">
                                                                        </div>
                                                                        <div class="order-item-details">
                                                                            <div class="order-item-name"><?php echo $item['product_name']; ?></div>
                                                                            <div class="order-item-price">KSh <?php echo number_format($item['price'], 2); ?> x <?php echo $item['quantity']; ?> = KSh <?php echo number_format($item['subtotal'], 2); ?></div>
                                                                        </div>
                                                                        <div class="order-item-quantity">
                                                                            Qty: <?php echo $item['quantity']; ?>
                                                                        </div>
                                                                    </div>
                                                                <?php
                                                                    endwhile;
                                                                else:
                                                                ?>
                                                                    <p class="text-center">No items found for this order.</p>
                                                                <?php endif; ?>
                                                            </div>
                                                            
                                                            <div class="row mt-4">
                                                                <div class="col-md-6">
                                                                    <h5>Order Summary</h5>
                                                                    <table class="table table-sm">
                                                                        <tr>
                                                                            <td>Order Date:</td>
                                                                            <td><?php echo date('M d, Y h:i A', strtotime($order['order_date'])); ?></td>
                                                                        </tr>
                                                                        <tr>
                                                                            <td>Payment Method:</td>
                                                                            <td><?php echo $order['payment_method']; ?></td>
                                                                        </tr>
                                                                        <tr>
                                                                            <td>Total Amount:</td>
                                                                            <td><strong>KSh <?php echo number_format($order['amount'], 2); ?></strong></td>
                                                                        </tr>
                                                                    </table>
                                                                </div>
                                                                <div class="col-md-6">
                                                                    <h5>Update Order Status</h5>
                                                                    <form method="post" action="">
                                                                        <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                                                        <div class="form-group">
                                                                            <label for="order_status<?php echo $order['id']; ?>">Order Status</label>
                                                                            <select class="form-control" id="order_status<?php echo $order['id']; ?>" name="order_status">
                                                                                <option value="Pending" <?php echo ($order['order_status'] == 'Pending') ? 'selected' : ''; ?>>Pending</option>
                                                                                <option value="Processing" <?php echo ($order['order_status'] == 'Processing') ? 'selected' : ''; ?>>Processing</option>
                                                                                <option value="Shipped" <?php echo ($order['order_status'] == 'Shipped') ? 'selected' : ''; ?>>Shipped</option>
                                                                                <option value="Delivered" <?php echo ($order['order_status'] == 'Delivered') ? 'selected' : ''; ?>>Delivered</option>
                                                                                <option value="Cancelled" <?php echo ($order['order_status'] == 'Cancelled') ? 'selected' : ''; ?>>Cancelled</option>
                                                                            </select>
                                                                        </div>
                                                                        <div class="form-group">
                                                                            <label for="payment_status<?php echo $order['id']; ?>">Payment Status</label>
                                                                            <select class="form-control" id="payment_status<?php echo $order['id']; ?>" name="payment_status">
                                                                                <option value="Pending" <?php echo ($order['payment_status'] == 'Pending') ? 'selected' : ''; ?>>Pending</option>
                                                                                <option value="Paid" <?php echo ($order['payment_status'] == 'Paid') ? 'selected' : ''; ?>>Paid</option>
                                                                                <option value="Failed" <?php echo ($order['payment_status'] == 'Failed') ? 'selected' : ''; ?>>Failed</option>
                                                                            </select>
                                                                        </div>
                                                                        <button type="submit" name="update_order_status" class="btn btn-primary btn-block">
                                                                            <i class="fas fa-sync-alt mr-2"></i> Update Status
                                                                        </button>
                                                                    </form>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="7" class="text-center">No orders found.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> Fresh Groceries Admin Panel. All Rights Reserved.</p>
        </div>
    </footer>

    <!-- JavaScript -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    
    <script>
        // Prevent form resubmission on page refresh
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }
        
        // Preview image when URL is entered
        document.addEventListener('DOMContentLoaded', function() {
            const imageInput = document.getElementById('image');
            if (imageInput) {
                imageInput.addEventListener('input', function() {
                    const imageUrl = this.value;
                    if (imageUrl) {
                        // You could add image preview functionality here if needed
                        console.log('Image URL changed:', imageUrl);
                    }
                });
            }
        });
    </script>
</body>
</html>