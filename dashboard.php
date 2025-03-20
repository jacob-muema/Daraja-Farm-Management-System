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

// Fetch some stats for dashboard
$plantCount = 0;
$query = "SELECT COUNT(*) as count FROM plant";
$result = mysqli_query($conn, $query);
if($result) {
    $row = mysqli_fetch_assoc($result);
    $plantCount = $row['count'];
}

$medicineCount = 0;
$query = "SELECT COUNT(*) as count FROM medicines";
$result = mysqli_query($conn, $query);
if($result) {
    $row = mysqli_fetch_assoc($result);
    $medicineCount = $row['count'];
}

$methodCount = 0;
$query = "SELECT COUNT(*) as count FROM method";
$result = mysqli_query($conn, $query);
if($result) {
    $row = mysqli_fetch_assoc($result);
    $methodCount = $row['count'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>ADMT Farm Management - Dashboard</title>
    <link rel="shortcut icon" type="image/x-icon" href="logo.jpeg"/>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="stylesheet" type="text/css" href="font-awesome-4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0-beta/css/bootstrap.min.css">
    <link href="https://fonts.googleapis.com/css?family=IBM+Plex+Sans&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --primary-color: #1b5e20;
            --secondary-color: #4caf50;
            --accent-color: #8bc34a;
            --green-color: #28a745;
            --light-green: #e8f5e9;
            --dark-green: #1b5e20;
            --orange-color: #ff9800;
            --red-color: #dc3545;
            --light-color: #f8f9fa;
            --dark-color: #212529;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f5f7f9;
            padding-top: 60px;
            color: #333;
            line-height: 1.6;
        }
        
        /* Navbar Styles */
        .navbar {
            background: linear-gradient(to right, var(--dark-green), var(--secondary-color)) !important;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
            padding: 12px 0;
        }
        
        .navbar-brand {
            font-weight: 700;
            font-size: 22px;
            display: flex;
            align-items: center;
        }
        
        .navbar-brand i {
            margin-right: 10px;
            font-size: 24px;
            color: var(--accent-color);
        }
        
        .nav-link {
            font-weight: 500;
            padding: 8px 15px !important;
            transition: all 0.3s;
            border-radius: 8px;
            position: relative;
        }
        
        .nav-link:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }
        
        .nav-item.active .nav-link {
            background-color: rgba(255, 255, 255, 0.2);
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
            background-color: var(--orange-color);
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
        
        /* Main Container */
        .main-container {
            padding: 30px 0;
        }
        
        /* Welcome Section */
        .welcome-section {
            background: linear-gradient(rgba(0, 0, 0, 0.7), rgba(0, 0, 0, 0.7)), url('https://images.unsplash.com/photo-1523348837708-15d4a09cfac2?q=80&w=1200');
            background-size: cover;
            background-position: center;
            color: white;
            padding: 60px 30px;
            margin-bottom: 40px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            position: relative;
            overflow: hidden;
        }
        
        .welcome-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, rgba(27, 94, 32, 0.7) 0%, rgba(76, 175, 80, 0.4) 100%);
            z-index: 1;
        }
        
        .welcome-content {
            position: relative;
            z-index: 2;
        }
        
        .welcome-title {
            font-size: 36px;
            font-weight: 700;
            margin-bottom: 15px;
            text-shadow: 0 2px 4px rgba(0,0,0,0.3);
        }
        
        .welcome-subtitle {
            font-size: 18px;
            margin-bottom: 0;
            opacity: 0.9;
            max-width: 600px;
            text-shadow: 0 1px 3px rgba(0,0,0,0.3);
        }
        
        /* Stats Cards */
        .stats-card {
            background-color: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            text-align: center;
            margin-bottom: 30px;
            transition: all 0.3s;
            border-top: 5px solid var(--primary-color);
            height: 100%;
        }
        
        .stats-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.1);
        }
        
        .stats-image {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            margin: 0 auto 20px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: all 0.3s;
            border: 3px solid var(--light-green);
        }
        
        .stats-card:hover .stats-image {
            transform: scale(1.05);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        
        .stats-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: all 0.3s;
        }
        
        .stats-card:hover .stats-image img {
            transform: scale(1.1);
        }
        
        .stats-number {
            font-size: 36px;
            font-weight: 700;
            margin-bottom: 10px;
            color: var(--primary-color);
            line-height: 1;
        }
        
        .stats-title {
            font-size: 16px;
            color: #6c757d;
            font-weight: 500;
        }
        
        /* Section Title */
        .section-title {
            margin-bottom: 30px;
            position: relative;
            padding-bottom: 15px;
            font-weight: 700;
            color: var(--dark-color);
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
        
        /* Quick Actions */
        .quick-action {
            display: block;
            background-color: white;
            border-radius: 15px;
            padding: 25px 20px;
            text-align: center;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            transition: all 0.3s;
            text-decoration: none;
            color: var(--dark-color);
            margin-bottom: 30px;
            border: none;
            height: 100%;
        }
        
        .quick-action:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.1);
            text-decoration: none;
            color: var(--primary-color);
        }
        
        .quick-action-image {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            margin: 0 auto 20px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: all 0.3s;
            border: 3px solid white;
        }
        
        .quick-action:hover .quick-action-image {
            transform: scale(1.05);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
            border-color: var(--light-green);
        }
        
        .quick-action-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: all 0.3s;
        }
        
        .quick-action:hover .quick-action-image img {
            transform: scale(1.1);
        }
        
        .quick-action-title {
            font-weight: 600;
            font-size: 18px;
            margin-bottom: 10px;
        }
        
        .quick-action-desc {
            font-size: 14px;
            color: #6c757d;
        }
        
        /* Dashboard Card */
        .dashboard-card {
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            transition: all 0.3s;
            background-color: white;
            height: 100%;
            padding: 30px;
            border: none;
            margin-bottom: 30px;
        }
        
        .dashboard-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.1);
        }
        
        /* Farm Management */
        .farm-management-link {
            display: block;
            text-align: center;
            padding: 20px;
            border-radius: 10px;
            transition: all 0.3s;
            text-decoration: none;
            color: var(--dark-color);
        }
        
        .farm-management-link:hover {
            background-color: var(--light-green);
            text-decoration: none;
            color: var(--primary-color);
        }
        
        .fa-stack {
            margin-bottom: 15px;
        }
        
        .fa-stack-2x {
            color: var(--primary-color) !important;
        }
        
        /* Recent Activity */
        .activity-item {
            display: flex;
            align-items: center;
            padding: 15px 0;
            border-bottom: 1px solid #eee;
        }
        
        .activity-item:last-child {
            border-bottom: none;
        }
        
        .activity-image {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            margin-right: 15px;
            flex-shrink: 0;
            overflow: hidden;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
            border: 2px solid white;
        }
        
        .activity-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .activity-content {
            flex-grow: 1;
        }
        
        .activity-title {
            font-weight: 600;
            font-size: 15px;
            margin-bottom: 3px;
            color: var(--dark-color);
        }
        
        .activity-time {
            font-size: 13px;
            color: #6c757d;
        }
        
        /* Tables */
        .table {
            background-color: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            overflow: hidden;
            margin-bottom: 30px;
        }
        
        .table thead th {
            background-color: var(--light-green);
            border-top: none;
            padding: 15px;
            font-weight: 600;
            color: var(--dark-green);
        }
        
        .table tbody td {
            padding: 15px;
            vertical-align: middle;
            border-color: #f0f0f0;
        }
        
        .table tbody tr:hover {
            background-color: rgba(76, 175, 80, 0.05);
        }
        
        /* Payment Form */
        .payment-form {
            background-color: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }
        
        .payment-form .form-control {
            height: 50px;
            border-radius: 8px;
            border: 1px solid #e0e0e0;
            padding: 10px 15px;
            font-size: 15px;
            transition: all 0.3s;
        }
        
        .payment-form .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(27, 94, 32, 0.25);
        }
        
        .payment-form .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            height: 50px;
            font-weight: 600;
            border-radius: 8px;
            margin-top: 10px;
            transition: all 0.3s;
        }
        
        .payment-form .btn-primary:hover {
            background-color: var(--dark-green);
            border-color: var(--dark-green);
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(27, 94, 32, 0.3);
        }
        
        /* Footer */
        .footer {
            background-color: white;
            padding: 60px 0 30px;
            margin-top: 60px;
            border-top: 1px solid #eee;
        }
        
        .footer-title {
            font-weight: 700;
            font-size: 18px;
            margin-bottom: 20px;
            color: var(--dark-color);
            position: relative;
            padding-bottom: 10px;
        }
        
        .footer-title:after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 40px;
            height: 2px;
            background-color: var(--primary-color);
        }
        
        .footer-links {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .footer-links li {
            margin-bottom: 12px;
        }
        
        .footer-links a {
            color: #6c757d;
            text-decoration: none;
            transition: all 0.3s;
            display: block;
        }
        
        .footer-links a:hover {
            color: var(--primary-color);
            padding-left: 5px;
        }
        
        .footer-links i {
            margin-right: 8px;
            color: var(--primary-color);
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
            background-color: var(--primary-color);
            transform: translateY(-3px);
        }
        
        .social-link:hover i {
            color: white;
        }
        
        .social-link i {
            color: #6c757d;
            font-size: 18px;
        }
        
        /* Responsive adjustments */
        @media (max-width: 992px) {
            .welcome-title {
                font-size: 30px;
            }
            
            .welcome-subtitle {
                font-size: 16px;
            }
            
            .stats-number {
                font-size: 30px;
            }
            
            .stats-image, .quick-action-image {
                width: 70px;
                height: 70px;
            }
        }
        
        @media (max-width: 768px) {
            .welcome-section {
                padding: 40px 20px;
            }
            
            .welcome-title {
                font-size: 26px;
            }
            
            .section-title {
                font-size: 22px;
            }
            
            .dashboard-card, .payment-form {
                padding: 20px;
            }
            
            .footer {
                padding: 40px 0 20px;
            }
            
            .quick-action-image {
                width: 70px;
                height: 70px;
            }
            
            .activity-image {
                width: 40px;
                height: 40px;
            }
            
            .stats-image {
                width: 60px;
                height: 60px;
            }
        }
        
        @media (max-width: 576px) {
            .stats-card, .quick-action {
                padding: 20px 15px;
            }
            
            .stats-number {
                font-size: 26px;
            }
            
            .stats-image {
                width: 50px;
                height: 50px;
                margin-bottom: 15px;
            }
            
            .quick-action-image {
                width: 60px;
                height: 60px;
                margin-bottom: 15px;
            }
            
            .quick-action-title {
                font-size: 16px;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-dark fixed-top">
        <div class="container">
            <a class="navbar-brand" href="#"><i class="fa fa-leaf" aria-hidden="true"></i> ADMT Farm Management</a>
            <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarSupportedContent">
                <ul class="navbar-nav mr-auto">
                    <li class="nav-item active">
                        <a class="nav-link" href="dashboard.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="fruit-vegetable-sales.php">Fruits & Vegetables</a>
                    </li>
                    <li class="nav-item">
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

    <div class="container main-container">
        <!-- Welcome Section -->
        <div class="welcome-section">
            <div class="welcome-content">
                <h1 class="welcome-title">Welcome back, <?php echo $username; ?>!</h1>
                <p class="welcome-subtitle">Manage your farm activities and shop for fresh produce all in one place.</p>
            </div>
        </div>
        
        <!-- Stats Section -->
        <div class="row">
            <div class="col-md-3 col-sm-6">
                <div class="stats-card">
                    <div class="stats-image">
                        <img src="https://images.unsplash.com/photo-1591857177580-dc82b9ac4e1e?q=80&w=150&h=150&auto=format&fit=crop" alt="Plants">
                    </div>
                    <div class="stats-number"><?php echo $plantCount; ?></div>
                    <div class="stats-title">Plants</div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="stats-card">
                    <div class="stats-image">
                        <img src="https://images.unsplash.com/photo-1587854692152-cbe660dbde88?q=80&w=150&h=150&auto=format&fit=crop" alt="Medicines">
                    </div>
                    <div class="stats-number"><?php echo $medicineCount; ?></div>
                    <div class="stats-title">Medicines</div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="stats-card">
                    <div class="stats-image">
                        <img src="https://images.unsplash.com/photo-1589391886645-d51941baf7fb?q=80&w=150&h=150&auto=format&fit=crop" alt="Methods">
                    </div>
                    <div class="stats-number"><?php echo $methodCount; ?></div>
                    <div class="stats-title">Methods</div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="stats-card">
                    <div class="stats-image">
                        <img src="https://images.unsplash.com/photo-1601598851547-4302969d0614?q=80&w=150&h=150&auto=format&fit=crop" alt="Cart Items">
                    </div>
                    <div class="stats-number"><?php echo getCartCount($sessionCart); ?></div>
                    <div class="stats-title">Cart Items</div>
                </div>
            </div>
        </div>
        
        <!-- Quick Actions Section -->
        <div class="row mt-5">
            <div class="col-12">
                <h2 class="section-title">Quick Actions</h2>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-3 col-sm-6">
                <a href="fruit-vegetable-sales.php" class="quick-action">
                    <div class="quick-action-image">
                        <img src="https://images.unsplash.com/photo-1542838132-92c53300491e?q=80&w=200&h=200&auto=format&fit=crop" alt="Fresh produce">
                    </div>
                    <h4 class="quick-action-title">Shop Products</h4>
                    <p class="quick-action-desc">Browse our fresh fruits and vegetables</p>
                </a>
            </div>
            <div class="col-md-3 col-sm-6">
                <a href="fruit-vegetable-sales.php?page=cart" class="quick-action">
                    <div class="quick-action-image">
                        <img src="https://images.unsplash.com/photo-1601598851547-4302969d0614?q=80&w=200&h=200&auto=format&fit=crop" alt="Shopping cart">
                    </div>
                    <h4 class="quick-action-title">View Cart</h4>
                    <p class="quick-action-desc">Check your shopping cart</p>
                </a>
            </div>
            <div class="col-md-3 col-sm-6">
                <a href="orders.php" class="quick-action">
                    <div class="quick-action-image">
                        <img src="https://images.unsplash.com/photo-1607349913338-fca6f7fc42d0?q=80&w=200&h=200&auto=format&fit=crop" alt="Order tracking">
                    </div>
                    <h4 class="quick-action-title">My Orders</h4>
                    <p class="quick-action-desc">Track and manage your orders</p>
                </a>
            </div>
            <!-- <div class="col-md-3 col-sm-6">
                <a href="#" class="quick-action">
                    <div class="quick-action-image">
                        <img src="https://images.unsplash.com/photo-1633332755192-727a05c4013d?q=80&w=200&h=200&auto=format&fit=crop" alt="User profile">
                    </div>
                    <h4 class="quick-action-title">My Profile</h4>
                    <p class="quick-action-desc">Update your account information</p>
                </a>
            </div>
        </div> -->
        
        <!-- Farm Management Section -->
        <!-- <div class="row mt-5">
            <div class="col-md-6">
                <h2 class="section-title">Farm Management</h2>
                <div class="dashboard-card">
                    <div class="row">
                        <div class="col-md-6 col-sm-6 mb-4">
                            <a href="#list-plant" onclick="document.querySelector('#list-plant-list').click()" class="farm-management-link">
                                <span class="fa-stack fa-2x"> 
                                    <i class="fa fa-square fa-stack-2x"></i> 
                                    <i class="fa fa-pagelines fa-stack-1x fa-inverse"></i> 
                                </span>
                                <h4 class="mt-2">View Plants</h4>
                            </a>
                        </div>
                        <div class="col-md-6 col-sm-6 mb-4">
                            <a href="#list-med" onclick="document.querySelector('#list-med-list').click()" class="farm-management-link">
                                <span class="fa-stack fa-2x"> 
                                    <i class="fa fa-square fa-stack-2x"></i> 
                                    <i class="fa fa-medkit fa-stack-1x fa-inverse"></i> 
                                </span>
                                <h4 class="mt-2">View Medicines</h4>
                            </a>
                        </div>
                        <div class="col-md-6 col-sm-6 mb-4">
                            <a href="#list-method" onclick="document.querySelector('#list-method-list').click()" class="farm-management-link">
                                <span class="fa-stack fa-2x"> 
                                    <i class="fa fa-square fa-stack-2x"></i> 
                                    <i class="fa fa-list-ul fa-stack-1x fa-inverse"></i> 
                                </span>
                                <h4 class="mt-2">View Methods</h4>
                            </a>
                        </div>
                        <div class="col-md-6 col-sm-6 mb-4">
                            <a href="#list-payment" onclick="document.querySelector('#list-payment-list').click()" class="farm-management-link">
                                <span class="fa-stack fa-2x"> 
                                    <i class="fa fa-square fa-stack-2x"></i> 
                                    <i class="fa fa-money fa-stack-1x fa-inverse"></i> 
                                </span>
                                <h4 class="mt-2">Make Payment</h4>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
             -->
            <div class="col-md-6">
                <h2 class="section-title">Recent Activity</h2>
                <div class="dashboard-card">
                    <div class="activity-item">
                        <div class="activity-image">
                            <img src="https://images.unsplash.com/photo-1591857177580-dc82b9ac4e1e?q=80&w=100&h=100&auto=format&fit=crop" alt="New plants">
                        </div>
                        <div class="activity-content">
                            <div class="activity-title">New plants added to the system</div>
                            <div class="activity-time">Today, 10:30 AM</div>
                        </div>
                    </div>
                    <div class="activity-item">
                        <div class="activity-image">
                            <img src="https://images.unsplash.com/photo-1542838132-92c53300491e?q=80&w=100&h=100&auto=format&fit=crop" alt="Cart items">
                        </div>
                        <div class="activity-content">
                            <div class="activity-title">You added items to your cart</div>
                            <div class="activity-time">Yesterday, 3:45 PM</div>
                        </div>
                    </div>
                    <div class="activity-item">
                        <div class="activity-image">
                            <img src="https://images.unsplash.com/photo-1563409236340-c174b51cbb81?q=80&w=100&h=100&auto=format&fit=crop" alt="Watering reminder">
                        </div>
                        <div class="activity-content">
                            <div class="activity-title">Reminder: Check plant watering schedule</div>
                            <div class="activity-time">Yesterday, 9:00 AM</div>
                        </div>
                    </div>
                    <div class="activity-item">
                        <div class="activity-image">
                            <img src="https://images.unsplash.com/photo-1523348837708-15d4a09cfac2?q=80&w=100&h=100&auto=format&fit=crop" alt="Farming method">
                        </div>
                        <div class="activity-content">
                            <div class="activity-title">New farming method added</div>
                            <div class="activity-time">March 14, 2023</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Sidebar Tabs Content (Hidden initially) -->
        <div class="tab-content mt-5" id="nav-tabContent" style="display: none;">
            <!-- Plants Tab -->
            <div class="tab-pane fade" id="list-plant" role="tabpanel" aria-labelledby="list-plant-list">
                <h4 class="mb-3">Plants List</h4>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th scope="col">Plant Id</th>
                                <th scope="col">Plant Name</th>
                                <th scope="col">Plant Type</th>
                                <th scope="col">Plant Description</th>
                                <th scope="col">Soil Type</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $query = "SELECT * FROM plant;";
                            $result = mysqli_query($conn, $query);
                            while ($row = mysqli_fetch_array($result)){
                            ?>
                            <tr>
                                <td><?php echo $row['plant_id'];?></td>
                                <td><?php echo $row['plant_name'];?></td>
                                <td><?php echo $row['plant_type'];?></td>
                                <td><?php echo $row['plant_desc'];?></td>
                                <td><?php echo $row['soil_type'];?></td>
                            </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Methods Tab -->
            <div class="tab-pane fade" id="list-method" role="tabpanel" aria-labelledby="list-method-list">
                <h4 class="mb-3">Methods List</h4>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th scope="col">Method ID</th>
                                <th scope="col">Method Name</th>
                                <th scope="col">Method Type</th>
                                <th scope="col">Method Description</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $query = "SELECT * FROM method;";
                            $result = mysqli_query($conn, $query);
                            if(!$result){
                                echo mysqli_error($conn);
                            }
                            while ($row = mysqli_fetch_array($result)){
                            ?>
                            <tr>
                                <td><?php echo $row['method_id'];?></td>
                                <td><?php echo $row['method_name'];?></td>
                                <td><?php echo $row['method_type'];?></td>
                                <td><?php echo $row['method_desc'];?></td>
                            </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Medicines Tab -->
            <div class="tab-pane fade" id="list-med" role="tabpanel" aria-labelledby="list-med-list">
                <h4 class="mb-3">Medicines List</h4>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th scope="col">Plant ID</th>
                                <th scope="col">Medicine ID</th>
                                <th scope="col">Medicine Name</th>
                                <th scope="col">Medicine Type</th>
                                <th scope="col">Medicine Cost</th>
                                <th scope="col">Medicine Description</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $query = "SELECT * FROM medicines;";
                            $result = mysqli_query($conn, $query);
                            if(!$result){
                                echo mysqli_error($conn);
                            }
                            while ($row = mysqli_fetch_array($result)){
                            ?>
                            <tr>
                                <td><?php echo $row['plant_id'];?></td>
                                <td><?php echo $row['med_id'];?></td>
                                <td><?php echo $row['med_name'];?></td>
                                <td><?php echo $row['med_type'];?></td>
                                <td><?php echo $row['med_cost'];?></td>
                                <td><?php echo $row['med_desc'];?></td>
                            </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- M-Pesa Payment Tab -->
            <div class="tab-pane fade" id="list-payment" role="tabpanel" aria-labelledby="list-payment-list">
                <div class="row">
                    <div class="col-md-6 mx-auto">
                        <h4 class="text-center mb-4">Lipa na M-Pesa</h4>
                        <div class="payment-form">
                            <form method="POST" id="paymentForm">
                                <div class="form-group">
                                    <label for="phone">Phone Number</label>
                                    <input type="text" class="form-control" name="phone" id="phone" placeholder="Enter Phone Number (07XXXXXXXX or 2547XXXXXXXX)" required>
                                </div>
                                <div class="form-group">
                                    <label for="amount">Amount (KES)</label>
                                    <input type="number" class="form-control" name="amount" id="amount" placeholder="Enter Amount" required>
                                </div>
                                <button type="submit" name="payment_submit" class="btn btn-primary btn-block">Pay Now</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
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
        
        // Function to click on tab links programmatically
        function clickDiv(id) {
            document.querySelector(id).click();
        }
        
        // Show tab content when clicking on dashboard links
        document.querySelectorAll('a[href^="#list-"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                
                // Show the tab content container
                document.getElementById('nav-tabContent').style.display = 'block';
                
                // Get the target tab ID
                const targetId = this.getAttribute('href').substring(1);
                
                // Hide all tabs
                document.querySelectorAll('.tab-pane').forEach(tab => {
                    tab.classList.remove('show', 'active');
                });
                
                // Show the target tab
                const targetTab = document.getElementById(targetId);
                targetTab.classList.add('show', 'active');
                
                // Scroll to the tab content
                targetTab.scrollIntoView({
                    behavior: 'smooth'
                });
            });
        });
    </script>
</body>
</html>