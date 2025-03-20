
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

// M-Pesa Configuration - Using your existing configuration
define('CONSUMER_KEY', 'IdBtzK1kbopocwsNPsfx6zO8l6ysAfUaXaWV6je2c5aUAvgR');
define('CONSUMER_SECRET', '54g5Oqk8ArC5VOhseu216FIGlMFkGyjo9CoK4oluLv7CkHluhNZuadZxh0SAo9Ia');
define('SHORTCODE', '174379');
define('PASSKEY', 'bfb279f9aa9bdbcf158e97dd71a467cd2e0c893059b10f78e6b72ada1ed2c919');
define('CALLBACK_URL', 'https://yourdomain.com/callback.php');
define('MPESA_ENV', 'sandbox');
define('OAUTH_URL', 'https://sandbox.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials');
define('STK_PUSH_URL', 'https://sandbox.safaricom.co.ke/mpesa/stkpush/v1/processrequest');

// Get Access Token Function - Using your existing function
function getAccessToken() {
    $credentials = base64_encode(CONSUMER_KEY . ":" . CONSUMER_SECRET);
    
    $curl = curl_init(OAUTH_URL);
    curl_setopt($curl, CURLOPT_HTTPHEADER, ["Authorization: Basic " . $credentials]);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    
    $response = json_decode(curl_exec($curl));
    curl_close($curl);

    return isset($response->access_token) ? $response->access_token : null;
}

// STK Push Function - Using your existing function
function sendStkPush($phone, $amount, $accountRef = 'FarmPayment') {
    $timestamp = date('YmdHis');
    $password = base64_encode(SHORTCODE . PASSKEY . $timestamp);
    $accessToken = getAccessToken();

    if (!$accessToken) {
        return ['error' => 'Failed to retrieve access token'];
    }

    // STK Push Payload
    $stkPayload = [
        'BusinessShortCode' => SHORTCODE,
        'Password' => $password,
        'Timestamp' => $timestamp,
        'TransactionType' => 'CustomerPayBillOnline',
        'Amount' => $amount,
        'PartyA' => $phone,
        'PartyB' => SHORTCODE,
        'PhoneNumber' => $phone,
        'CallBackURL' => CALLBACK_URL,
        'AccountReference' => $accountRef,
        'TransactionDesc' => 'Farm Management Payment'
    ];

    // Send STK Push Request
    $curl = curl_init(STK_PUSH_URL);
    curl_setopt($curl, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $accessToken,
        'Content-Type: application/json'
    ]);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($stkPayload));

    $response = curl_exec($curl);
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);

    $decodedResponse = json_decode($response, true);

    return [
        'http_code' => $httpCode,
        'response' => $decodedResponse,
        'payload' => $stkPayload
    ];
}

// Process M-Pesa Payment
$responseMessage = '';
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['payment_submit'])) {
    $phoneNumber = trim($_POST['phone']);
    $amount = $_POST['amount'];

    // Convert 07XXXXXXXX to 2547XXXXXXXX format
    if (preg_match('/^07[0-9]{8}$/', $phoneNumber)) {
        $phoneNumber = '254' . substr($phoneNumber, 1);
    }

    // Validate phone number format
    if (!preg_match('/^2547[0-9]{8}$/', $phoneNumber)) {
        $responseMessage = "<p style='color: red;'>Invalid phone number. Use format: 07XXXXXXXX or 2547XXXXXXXX</p>";
    } elseif ($amount <= 0) {
        $responseMessage = "<p style='color: red;'>Amount must be greater than zero.</p>";
    } else {
        // Call STK Push
        $response = sendStkPush($phoneNumber, $amount);
        
        if ($response['http_code'] == 200 && $response['response']['ResponseCode'] == '0') {
            $responseMessage = "<p style='color: green;'>STK Push sent successfully! Check your phone.</p>";
        } else {
            $responseMessage = "<p style='color: red;'>Failed to send STK Push. Try again.</p>";
        }
    }
}

// Add to cart functionality
if(isset($_POST['add_to_cart'])) {
    $productId = $_POST['productId'];
    $productName = $_POST['productName'];
    $productPrice = $_POST['productPrice'];
    $productImage = $_POST['productImage'];
    $productQuantity = 1;
    
    // Check if product already in cart
    if(isset($_SESSION['cart'][$productId])) {
        $_SESSION['cart'][$productId]['quantity']++;
    } else {
        $_SESSION['cart'][$productId] = [
            'name' => $productName,
            'price' => $productPrice,
            'image' => $productImage,
            'quantity' => $productQuantity
        ];
    }
    
    $responseMessage = "<p style='color: green;'>Product added to cart!</p>";
}

// Remove from cart
if(isset($_GET['remove_from_cart'])) {
    $productId = $_GET['remove_from_cart'];
    if(isset($_SESSION['cart'][$productId])) {
        unset($_SESSION['cart'][$productId]);
        $responseMessage = "<p style='color: green;'>Product removed from cart!</p>";
    }
}

// Update cart quantity
if(isset($_POST['update_cart'])) {
    foreach($_POST['quantity'] as $productId => $quantity) {
        if($quantity > 0) {
            $_SESSION['cart'][$productId]['quantity'] = $quantity;
        } else {
            unset($_SESSION['cart'][$productId]);
        }
    }
    $responseMessage = "<p style='color: green;'>Cart updated!</p>";
}

// Calculate cart total - Fixed to accept cart as parameter
function calculateCartTotal($cart) {
    $total = 0;
    if(!empty($cart)) {
        foreach($cart as $item) {
            $total += $item['price'] * $item['quantity'];
        }
    }
    return $total;
}

// Get cart total for current session - Fixed to not directly access $_SESSION
function getSessionCartTotal($sessionCart) {
    return calculateCartTotal($sessionCart);
}

// Get cart count - Fixed to not directly access $_SESSION
function getCartCount($sessionCart) {
    return !empty($sessionCart) ? count($sessionCart) : 0;
}

// Get username from session
$username = $_SESSION['username'];

// Get cart from session for use in functions
$sessionCart = isset($_SESSION['cart']) ? $_SESSION['cart'] : [];

// Fruit and vegetable products
$products = [
    [
        'id' => 1,
        'name' => 'Fresh Apples',
        'category' => 'Fruits',
        'price' => 120,
        'image' => 'https://images.unsplash.com/photo-1560806887-1e4cd0b6cbd6?q=80&w=400',
        'is_organic' => true
    ],
    [
        'id' => 2,
        'name' => 'Bananas',
        'category' => 'Fruits',
        'price' => 80,
        'image' => 'https://images.unsplash.com/photo-1603833665858-e61d17a86224?q=80&w=400',
        'is_organic' => true
    ],
    [
        'id' => 3,
        'name' => 'Carrots',
        'category' => 'Vegetables',
        'price' => 60,
        'image' => 'https://images.unsplash.com/photo-1598170845058-32b9d6a5da37?q=80&w=400',
        'is_organic' => false
    ],
    [
        'id' => 4,
        'name' => 'Tomatoes',
        'category' => 'Vegetables',
        'price' => 90,
        'image' => 'https://images.unsplash.com/photo-1592924357228-91a4daadcfea?q=80&w=400',
        'is_organic' => true
    ],
    [
        'id' => 5,
        'name' => 'Spinach',
        'category' => 'Vegetables',
        'price' => 50,
        'image' => 'https://images.unsplash.com/photo-1576045057995-568f588f82fb?q=80&w=400',
        'is_organic' => true
    ],
    [
        'id' => 6,
        'name' => 'Oranges',
        'category' => 'Fruits',
        'price' => 100,
        'image' => 'https://images.unsplash.com/photo-1611080626919-7cf5a9dbab12?q=80&w=400',
        'is_organic' => false
    ],
    [
        'id' => 7,
        'name' => 'Avocados',
        'category' => 'Fruits',
        'price' => 150,
        'image' => 'https://images.unsplash.com/photo-1523049673857-eb18f1d7b578?q=80&w=400',
        'is_organic' => true
    ],
    [
        'id' => 8,
        'name' => 'Bell Peppers',
        'category' => 'Vegetables',
        'price' => 85,
        'image' => 'https://images.unsplash.com/photo-1563565375-f3fdfdbefa83?q=80&w=400',
        'is_organic' => false
    ]
];

// Filter products by category if needed
$category = isset($_GET['category']) ? $_GET['category'] : '';
if($category) {
    $filteredProducts = array_filter($products, function($product) use ($category) {
        return strtolower($product['category']) === strtolower($category);
    });
} else {
    $filteredProducts = $products;
}

// Current page (products, cart, or checkout)
$currentPage = isset($_GET['page']) ? $_GET['page'] : 'products';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>ADMT Farm Management - Fruits & Vegetables</title>
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
        
        .list-group-item.active {
            z-index: 2;
            color: #fff;
            background-color: var(--accent-color);
            border-color: #007bff;
        }
        
        .text-primary {
            color: var(--accent-color) !important;
        }
        
        .btn-outline-light:hover {
            color: var(--secondary-color);
            background-color: #f8f9fa;
            border-color: #f8f9fa;
        }
        
        .dashboard-card {
            border-radius: 15px;
            box-shadow: 0 10px 20px rgba(0,0,0,0.05);
            transition: transform 0.3s, box-shadow 0.3s;
            background-color: white;
            height: 100%;
            padding: 25px;
            border: none;
        }
        
        .dashboard-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.1);
        }
        
        .fa-stack {
            margin-bottom: 15px;
        }
        
        .payment-form {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 10px 20px rgba(0,0,0,0.05);
            margin-bottom: 20px;
        }
        
        .payment-form input, .payment-form button {
            display: block;
            margin: 15px auto;
            padding: 12px;
            width: 100%;
            border-radius: 8px;
        }
        
        .payment-form button {
            background-color: var(--accent-color);
            color: white;
            border: none;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .payment-form button:hover {
            background-color: var(--primary-color);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .table {
            background-color: white;
            border-radius: 15px;
            box-shadow: 0 10px 20px rgba(0,0,0,0.05);
            overflow: hidden;
        }
        
        .table thead th {
            background-color: #f1f1f1;
            border-top: none;
            padding: 15px;
            font-weight: 600;
        }
        
        .table tbody td {
            padding: 15px;
            vertical-align: middle;
        }
        
        #response {
            margin-top: 15px;
            font-weight: bold;
        }
        
        /* Product Card Styles */
        .product-card {
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 10px 20px rgba(0,0,0,0.05);
            transition: transform 0.3s, box-shadow 0.3s;
            background-color: white;
            margin-bottom: 30px;
            position: relative;
            border: none;
        }
        
        .product-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.1);
        }
        
        .product-image {
            height: 220px;
            overflow: hidden;
            position: relative;
        }
        
        .product-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s;
        }
        
        .product-card:hover .product-image img {
            transform: scale(1.1);
        }
        
        .organic-badge {
            position: absolute;
            top: 15px;
            left: 15px;
            background-color: var(--green-color);
            color: white;
            padding: 5px 15px;
            border-radius: 30px;
            font-size: 12px;
            font-weight: 600;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            z-index: 10;
        }
        
        .product-details {
            padding: 20px;
        }
        
        .product-category {
            color: #6c757d;
            font-size: 14px;
            margin-bottom: 8px;
            font-weight: 500;
        }
        
        .product-name {
            font-weight: 700;
            font-size: 18px;
            margin-bottom: 12px;
            color: #333;
        }
        
        .product-price {
            font-weight: 700;
            font-size: 20px;
            color: var(--accent-color);
            margin-bottom: 20px;
        }
        
        .add-to-cart-btn {
            background-color: var(--accent-color);
            color: white;
            border: none;
            border-radius: 8px;
            padding: 12px 20px;
            width: 100%;
            cursor: pointer;
            transition: all 0.3s;
            font-weight: 600;
            font-size: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .add-to-cart-btn i {
            margin-right: 8px;
        }
        
        .add-to-cart-btn:hover {
            background-color: var(--primary-color);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        /* Cart Styles */
        .cart-item {
            display: flex;
            margin-bottom: 25px;
            padding-bottom: 25px;
            border-bottom: 1px solid #eee;
            align-items: center;
        }
        
        .cart-item-image {
            width: 120px;
            height: 120px;
            margin-right: 20px;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        
        .cart-item-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .cart-item-details {
            flex-grow: 1;
        }
        
        .cart-item-name {
            font-weight: 700;
            font-size: 18px;
            margin-bottom: 8px;
            color: #333;
        }
        
        .cart-item-price {
            color: var(--accent-color);
            font-weight: 700;
            font-size: 18px;
            margin-bottom: 12px;
        }
        
        .cart-item-quantity {
            display: flex;
            align-items: center;
        }
        
        .quantity-btn {
            background-color: #f1f1f1;
            border: none;
            width: 35px;
            height: 35px;
            border-radius: 50%;
            cursor: pointer;
            font-size: 16px;
            font-weight: 700;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .quantity-btn:hover {
            background-color: #e0e0e0;
        }
        
        .quantity-input {
            width: 50px;
            text-align: center;
            margin: 0 10px;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 5px;
            font-weight: 600;
        }
        
        .remove-btn {
            color: var(--red-color);
            background: none;
            border: none;
            cursor: pointer;
            margin-left: 20px;
            font-size: 18px;
            transition: all 0.3s;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
        }
        
        .remove-btn:hover {
            background-color: rgba(220, 53, 69, 0.1);
        }
        
        .cart-summary {
            background-color: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 10px 20px rgba(0,0,0,0.05);
        }
        
        .cart-total {
            font-size: 20px;
            font-weight: 700;
            margin-bottom: 25px;
            padding-bottom: 20px;
            border-bottom: 1px solid #eee;
        }
        
        /* Hero Section */
        .hero-section {
            background: linear-gradient(rgba(0, 0, 0, 0.6), rgba(0, 0, 0, 0.6)), url('https://images.unsplash.com/photo-1542838132-92c53300491e?q=80&w=1200');
            background-size: cover;
            background-position: center;
            color: white;
            padding: 120px 0;
            margin-bottom: 60px;
            border-radius: 15px;
            text-align: center;
            box-shadow: 0 15px 30px rgba(0,0,0,0.1);
        }
        
        .hero-title {
            font-size: 52px;
            font-weight: 800;
            margin-bottom: 25px;
            text-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }
        
        .hero-subtitle {
            font-size: 22px;
            margin-bottom: 40px;
            max-width: 700px;
            margin-left: auto;
            margin-right: auto;
            line-height: 1.6;
            text-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }
        
        .hero-btn {
            background-color: var(--green-color);
            color: white;
            border: none;
            padding: 15px 40px;
            font-size: 18px;
            font-weight: 600;
            border-radius: 50px;
            text-decoration: none;
            transition: all 0.3s;
            display: inline-block;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        
        .hero-btn:hover {
            background-color: var(--dark-green);
            color: white;
            text-decoration: none;
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.3);
        }
        
        /* Category Pills */
        .category-pills {
            display: flex;
            justify-content: center;
            margin-bottom: 40px;
            flex-wrap: wrap;
        }
        
        .category-pill {
            background-color: white;
            color: var(--accent-color);
            border: 2px solid var(--accent-color);
            border-radius: 50px;
            padding: 10px 25px;
            margin: 8px;
            text-decoration: none;
            transition: all 0.3s;
            font-weight: 600;
            font-size: 15px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.05);
        }
        
        .category-pill:hover, .category-pill.active {
            background-color: var(--accent-color);
            color: white;
            text-decoration: none;
            transform: translateY(-3px);
            box-shadow: 0 6px 12px rgba(0,0,0,0.1);
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .hero-title {
                font-size: 36px;
            }
            
            .hero-subtitle {
                font-size: 18px;
            }
            
            .product-image {
                height: 180px;
            }
            
            .cart-item {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .cart-item-image {
                width: 100%;
                margin-right: 0;
                margin-bottom: 15px;
            }
            
            .remove-btn {
                margin-left: auto;
                margin-top: 15px;
            }
        }
        
        /* M-Pesa Logo */
        .mpesa-logo {
            height: 40px;
            margin-right: 15px;
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
        
        /* Section Titles */
        .section-title {
            text-align: center;
            margin-bottom: 40px;
            position: relative;
            padding-bottom: 15px;
        }
        
        .section-title:after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
            height: 3px;
            background: linear-gradient(to right, var(--primary-color), var(--secondary-color));
            border-radius: 3px;
        }
        
        /* Navbar Improvements */
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
        
        /* Button Styles */
        .btn {
            border-radius: 8px;
            font-weight: 600;
            padding: 10px 20px;
            transition: all 0.3s;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .btn-primary {
            background: linear-gradient(to right, var(--primary-color), var(--secondary-color));
            border: none;
        }
        
        .btn-success {
            background-color: var(--green-color);
            border: none;
        }
        
        .btn-success:hover {
            background-color: var(--dark-green);
        }
        
        /* Alert Styles */
        .alert {
            border-radius: 10px;
            padding: 15px 20px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.05);
            border: none;
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
        
        /* Stats Cards */
        .stats-card {
            background-color: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 10px 20px rgba(0,0,0,0.05);
            text-align: center;
            margin-bottom: 30px;
            transition: all 0.3s;
            border-top: 5px solid var(--accent-color);
        }
        
        .stats-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.1);
        }
        
        .stats-icon {
            width: 60px;
            height: 60px;
            background-color: rgba(52, 42, 193, 0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
        }
        
        .stats-icon i {
            font-size: 24px;
            color: var(--accent-color);
        }
        
        .stats-number {
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 10px;
            color: var(--accent-color);
        }
        
        .stats-title {
            font-size: 16px;
            color: #6c757d;
            font-weight: 500;
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
                    <li class="nav-item active">
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
                        <a class="nav-link" href="?page=cart">
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
        <?php if($responseMessage): ?>
            <div class="alert alert-info alert-dismissible fade show" role="alert">
                <?php echo $responseMessage; ?>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        <?php endif; ?>
        
        <?php if($currentPage == 'products'): ?>
            <!-- Hero Section -->
            <div class="hero-section">
                <div class="container">
                    <h1 class="hero-title">Fresh Fruits & Vegetables</h1>
                    <p class="hero-subtitle">Farm-fresh produce delivered to your doorstep with convenient M-Pesa payments</p>
                    <a href="#products" class="hero-btn">Shop Now</a>
                </div>
            </div>
            
            <!-- Category Pills -->
            <div class="category-pills">
                <a href="?page=products" class="category-pill <?php echo $category == '' ? 'active' : ''; ?>">All Products</a>
                <a href="?page=products&category=fruits" class="category-pill <?php echo $category == 'fruits' ? 'active' : ''; ?>">Fruits</a>
                <a href="?page=products&category=vegetables" class="category-pill <?php echo $category == 'vegetables' ? 'active' : ''; ?>">Vegetables</a>
                <a href="?page=products&category=organic" class="category-pill <?php echo $category == 'organic' ? 'active' : ''; ?>">Organic</a>
            </div>
            
            <!-- Products Section -->
            <h2 class="section-title">Our Fresh Products</h2>
            <div id="products" class="row">
                <?php 
                // Filter for organic if needed
                if($category == 'organic') {
                    $filteredProducts = array_filter($products, function($product) {
                        return $product['is_organic'] === true;
                    });
                }
                
                foreach($filteredProducts as $product): 
                ?>
                <div class="col-md-3 col-sm-6">
                    <div class="product-card">
                        <div class="product-image">
                            <img src="<?php echo $product['image']; ?>" alt="<?php echo $product['name']; ?>">
                            <?php if($product['is_organic']): ?>
                                <div class="organic-badge">Organic</div>
                            <?php endif; ?>
                        </div>
                        <div class="product-details">
                            <div class="product-category"><?php echo $product['category']; ?></div>
                            <div class="product-name"><?php echo $product['name']; ?></div>
                            <div class="product-price">KSh <?php echo $product['price']; ?></div>
                            <form method="post">
                                <input type="hidden" name="productId" value="<?php echo $product['id']; ?>">
                                <input type="hidden" name="productName" value="<?php echo $product['name']; ?>">
                                <input type="hidden" name="productPrice" value="<?php echo $product['price']; ?>">
                                <input type="hidden" name="productImage" value="<?php echo $product['image']; ?>">
                                <button type="submit" name="add_to_cart" class="add-to-cart-btn">
                                    <i class="fa fa-shopping-cart"></i> Add to Cart
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
                
                <?php if(empty($filteredProducts)): ?>
                    <div class="col-12 empty-state">
                        <h3>No products found in this category</h3>
                        <p>We couldn't find any products matching your selection. Please try another category or check back later.</p>
                        <a href="?page=products" class="btn btn-primary">View All Products</a>
                    </div>
                <?php endif; ?>
            </div>
            
        <?php elseif($currentPage == 'cart'): ?>
            <!-- Cart Page -->
            <div class="row">
                <div class="col-12">
                    <h2 class="section-title">Your Shopping Cart</h2>
                </div>
            </div>
            
            <?php if(empty($sessionCart)): ?>
                <div class="row">
                    <div class="col-12 empty-state">
                        <h3>Your cart is empty</h3>
                        <p>Looks like you haven't added any items to your cart yet. Browse our products and add some items to your cart.</p>
                        <a href="?page=products" class="btn btn-primary">Continue Shopping</a>
                    </div>
                </div>
            <?php else: ?>
                <div class="row">
                    <div class="col-md-8">
                        <form method="post">
                            <?php foreach($sessionCart as $productId => $product): ?>
                                <div class="cart-item">
                                    <div class="cart-item-image">
                                        <img src="<?php echo $product['image']; ?>" alt="<?php echo $product['name']; ?>">
                                    </div>
                                    <div class="cart-item-details">
                                        <div class="cart-item-name"><?php echo $product['name']; ?></div>
                                        <div class="cart-item-price">KSh <?php echo $product['price']; ?></div>
                                        <div class="cart-item-quantity">
                                            <button type="button" class="quantity-btn" onclick="decrementQuantity(<?php echo $productId; ?>)">-</button>
                                            <input type="number" name="quantity[<?php echo $productId; ?>]" value="<?php echo $product['quantity']; ?>" min="1" class="quantity-input">
                                            <button type="button" class="quantity-btn" onclick="incrementQuantity(<?php echo $productId; ?>)">+</button>
                                        </div>
                                    </div>
                                    <a href="?page=cart&remove_from_cart=<?php echo $productId; ?>" class="remove-btn">
                                        <i class="fa fa-trash"></i>
                                    </a>
                                </div>
                            <?php endforeach; ?>
                            
                            <div class="text-right mb-4">
                                <button type="submit" name="update_cart" class="btn btn-secondary">Update Cart</button>
                            </div>
                        </form>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="cart-summary">
                            <h3 class="mb-4">Order Summary</h3>
                            <div class="cart-total">
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Subtotal:</span>
                                    <span>KSh <?php echo calculateCartTotal($sessionCart); ?></span>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Shipping:</span>
                                    <span>KSh 0</span>
                                </div>
                                <div class="d-flex justify-content-between font-weight-bold">
                                    <span>Total:</span>
                                    <span>KSh <?php echo calculateCartTotal($sessionCart); ?></span>
                                </div>
                            </div>
                            
                            <div class="payment-section">
                                <h4 class="mb-3">Payment Method</h4>
                                <div class="d-flex align-items-center bg-light p-3 rounded mb-3">
                                    <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/1/15/M-PESA_LOGO-01.svg/320px-M-PESA_LOGO-01.svg.png" alt="M-Pesa Logo" class="mpesa-logo">
                                    <span>M-Pesa Payment</span>
                                </div>
                                
                                <form method="POST" id="paymentForm">
                                    <div class="form-group">
                                        <label for="phone">Phone Number</label>
                                        <input type="text" class="form-control" name="phone" id="phone" placeholder="Enter Phone Number (07XXXXXXXX or 2547XXXXXXXX)" required>
                                        <small class="form-text text-muted">Format: 07XXXXXXXX or 254XXXXXXXXX</small>
                                    </div>
                                    <input type="hidden" name="amount" value="<?php echo calculateCartTotal($sessionCart); ?>">
                                    <button type="submit" name="payment_submit" class="btn btn-success btn-block">
                                        <i class="fa fa-money"></i> Pay KSh <?php echo calculateCartTotal($sessionCart); ?>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
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
                        <li><a href="?page=products&category=fruits">Fruits</a></li>
                        <li><a href="?page=products&category=vegetables">Vegetables</a></li>
                        <li><a href="?page=products&category=organic">Organic</a></li>
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
        
        // Quantity increment/decrement functions
        function incrementQuantity(productId) {
            const input = document.querySelector(`input[name="quantity[${productId}]"]`);
            input.value = parseInt(input.value) + 1;
        }
        
        function decrementQuantity(productId) {
            const input = document.querySelector(`input[name="quantity[${productId}]"]`);
            if (parseInt(input.value) > 1) {
                input.value = parseInt(input.value) - 1;
            }
        }
        
        // Smooth scroll for "Shop Now" button
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                
                document.querySelector(this.getAttribute('href')).scrollIntoView({
                    behavior: 'smooth'
                });
            });
        });
    </script>
</body>
</html>

Please make sure to add the following environment variables to your project:

<AddEnvironmentVariables names={["MPESA_SHORTCODE", "MPESA_PASSKEY", "MPESA_CALLBACK_URL", "MPESA_CONSUMER_KEY", "MPESA_CONSUMER_SECRET"]} />
```