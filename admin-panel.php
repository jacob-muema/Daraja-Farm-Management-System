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

// Chatbot functionality
if(isset($_POST['chatbot_message']) && !empty($_POST['chatbot_message'])) {
    $message = trim($_POST['chatbot_message']);
    $userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
    $username = $_SESSION['username'];
    
    // Simple responses based on keywords
    $response = "Thank you for your message. Our team will get back to you soon.";
    
    if(stripos($message, "price") !== false || stripos($message, "cost") !== false) {
        $response = "Our prices vary depending on the product. You can check individual product prices on our shop page.";
    } elseif(stripos($message, "delivery") !== false || stripos($message, "shipping") !== false) {
        $response = "We offer delivery services within Nairobi. Delivery fees depend on your location.";
    } elseif(stripos($message, "payment") !== false || stripos($message, "mpesa") !== false) {
        $response = "We accept M-Pesa payments and Cash on Delivery. You can choose your preferred payment method at checkout.";
    } elseif(stripos($message, "organic") !== false) {
        $response = "Yes, we offer organic fruits and vegetables. Look for the 'Organic' badge on our products.";
    } elseif(stripos($message, "hello") !== false || stripos($message, "hi") !== false) {
        $response = "Hello! How can I help you today?";
    }
    
    // Store message in database
    $stmt = $conn->prepare("INSERT INTO grocery_chatbot_messages (user_id, username, message, response) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isss", $userId, $username, $message, $response);
    $stmt->execute();
    
    // Return JSON response for AJAX
    if(isset($_POST['ajax']) && $_POST['ajax'] == 1) {
        echo json_encode(['status' => 'success', 'response' => $response]);
        exit;
    }
}

// Get previous chat messages for this user
$username = $_SESSION['username'];
$stmt = $conn->prepare("SELECT message, response, created_at FROM grocery_chatbot_messages WHERE username = ? ORDER BY created_at DESC LIMIT 10");
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();
$chatHistory = [];
while($row = $result->fetch_assoc()) {
    $chatHistory[] = $row;
}
$chatHistory = array_reverse($chatHistory);

// M-Pesa Configuration
define('CONSUMER_KEY', 'IdBtzK1kbopocwsNPsfx6zO8l6ysAfUaXaWV6je2c5aUAvgR');
define('CONSUMER_SECRET', '54g5Oqk8ArC5VOhseu216FIGlMFkGyjo9CoK4oluLv7CkHluhNZuadZxh0SAo9Ia');
define('SHORTCODE', '174379');
define('PASSKEY', 'bfb279f9aa9bdbcf158e97dd71a467cd2e0c893059b10f78e6b72ada1ed2c919');
define('CALLBACK_URL', 'https://yourdomain.com/callback.php');
define('MPESA_ENV', 'sandbox');
define('OAUTH_URL', 'https://sandbox.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials');
define('STK_PUSH_URL', 'https://sandbox.safaricom.co.ke/mpesa/stkpush/v1/processrequest');

// Get Access Token Function
function getAccessToken() {
    $credentials = base64_encode(CONSUMER_KEY . ":" . CONSUMER_SECRET);
    
    $curl = curl_init(OAUTH_URL);
    curl_setopt($curl, CURLOPT_HTTPHEADER, ["Authorization: Basic " . $credentials]);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    
    $response = json_decode(curl_exec($curl));
    curl_close($curl);

    return isset($response->access_token) ? $response->access_token : null;
}

// STK Push Function
function sendStkPush($phone, $amount, $accountRef = 'GroceryPayment') {
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
        'TransactionDesc' => 'Grocery Payment'
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

// Process Cash on Delivery Order
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['cod_submit'])) {
    $name = trim($_POST['customer_name']);
    $phone = trim($_POST['customer_phone']);
    $address = trim($_POST['delivery_address']);
    $notes = trim($_POST['order_notes']);
    $amount = $_POST['amount'];
    $userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
    
    // Validate inputs
    if (empty($name) || empty($phone) || empty($address)) {
        $responseMessage = "<p style='color: red;'>Please fill all required fields.</p>";
    } else {
        // Create order in database
        $orderDate = date('Y-m-d H:i:s');
        $orderStatus = 'Pending';
        $paymentMethod = 'Cash on Delivery';
        $paymentStatus = 'Pending';
        
        // Insert order into database
        $stmt = $conn->prepare("INSERT INTO grocery_orders (user_id, customer_name, phone, delivery_address, order_notes, amount, order_date, order_status, payment_method, payment_status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("issssdssss", $userId, $name, $phone, $address, $notes, $amount, $orderDate, $orderStatus, $paymentMethod, $paymentStatus);
        
        if ($stmt->execute()) {
            $orderId = $conn->insert_id;
            
            // Insert order items
            foreach ($_SESSION['cart'] as $productId => $item) {
                $productName = $item['name'];
                $price = $item['price'];
                $quantity = $item['quantity'];
                $subtotal = $price * $quantity;
                
                $stmt = $conn->prepare("INSERT INTO grocery_order_items (order_id, product_id, product_name, price, quantity, subtotal) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("iisdid", $orderId, $productId, $productName, $price, $quantity, $subtotal);
                $stmt->execute();
            }
            
            // Clear cart after successful order
            unset($_SESSION['cart']);
            
            $responseMessage = "<p style='color: green;'>Your order has been placed successfully! Order ID: #$orderId</p>";
        } else {
            $responseMessage = "<p style='color: red;'>Failed to place order. Please try again.</p>";
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

// Get username from session
$username = $_SESSION['username'];

// Get cart from session for use in functions
$sessionCart = isset($_SESSION['cart']) ? $_SESSION['cart'] : [];

// Get grocery products from database
$stmt = $conn->prepare("SELECT * FROM grocery_products ORDER BY category, name");
$stmt->execute();
$products = $stmt->get_result();
$groceryProducts = [];
while($row = $products->fetch_assoc()) {
    $groceryProducts[] = $row;
}

// Filter products by category if needed
$category = isset($_GET['category']) ? $_GET['category'] : '';
if($category) {
    $filteredProducts = array_filter($groceryProducts, function($product) use ($category) {
        if($category == 'organic') {
            return $product['is_organic'] == 1;
        }
        return strtolower($product['category']) === strtolower($category);
    });
} else {
    $filteredProducts = $groceryProducts;
}

// Current page (products, cart, or checkout)
$currentPage = isset($_GET['page']) ? $_GET['page'] : 'products';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Fresh Groceries - Online Grocery Store</title>
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
        
        .cart-icon {
            position: relative;
        }
        
        .cart-count {
            position: absolute;
            top: -8px;
            right: -8px;
            background-color: var(--primary-color);
            color: var(--white);
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: 600;
        }
        
        /* Hero Section */
        .hero-section {
            background: linear-gradient(rgba(0,0,0,0.6), rgba(0,0,0,0.6)), url('https://images.unsplash.com/photo-1542838132-92c53300491e?q=80&w=1200');
            background-size: cover;
            background-position: center;
            padding: 100px 0;
            margin-bottom: 60px;
            color: var(--white);
            text-align: center;
        }
        
        .hero-title {
            font-size: 48px;
            font-weight: 700;
            margin-bottom: 20px;
        }
        
        .hero-subtitle {
            font-size: 20px;
            margin-bottom: 30px;
            max-width: 700px;
            margin-left: auto;
            margin-right: auto;
        }
        
        .hero-btn {
            background-color: var(--primary-color);
            color: var(--white);
            padding: 12px 30px;
            border: none;
            font-size: 16px;
            font-weight: 600;
            border-radius: 4px;
            text-decoration: none;
            transition: all 0.3s ease;
            display: inline-block;
        }
        
        .hero-btn:hover {
            background-color: var(--dark-color);
            color: var(--white);
            text-decoration: none;
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        
        /* Category Filters */
        .category-filters {
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            margin-bottom: 40px;
        }
        
        .category-filter {
            background-color: var(--white);
            color: var(--text-color);
            padding: 10px 20px;
            margin: 5px;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            text-decoration: none;
            transition: all 0.3s ease;
            font-weight: 500;
        }
        
        .category-filter:hover, .category-filter.active {
            background-color: var(--primary-color);
            color: var(--white);
            border-color: var(--primary-color);
            text-decoration: none;
        }
        
        /* Product Cards */
        .product-card {
            background-color: var(--white);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            overflow: hidden;
            margin-bottom: 30px;
            transition: all 0.3s ease;
            box-shadow: var(--box-shadow);
            height: 100%;
            display: flex;
            flex-direction: column;
        }
        
        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.1);
        }
        
        .product-image {
            height: 200px;
            overflow: hidden;
            position: relative;
        }
        
        .product-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }
        
        .product-card:hover .product-image img {
            transform: scale(1.05);
        }
        
        .organic-badge {
            position: absolute;
            top: 10px;
            left: 10px;
            background-color: var(--primary-color);
            color: var(--white);
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
            z-index: 1;
        }
        
        .product-details {
            padding: 20px;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }
        
        .product-category {
            color: var(--light-text);
            font-size: 14px;
            margin-bottom: 5px;
        }
        
        .product-name {
            font-weight: 600;
            font-size: 18px;
            margin-bottom: 10px;
        }
        
        .product-price {
            font-weight: 700;
            font-size: 20px;
            color: var(--primary-color);
            margin-bottom: 15px;
        }
        
        .add-to-cart-btn {
            background-color: var(--primary-color);
            color: var(--white);
            border: none;
            padding: 10px;
            border-radius: 4px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: auto;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .add-to-cart-btn i {
            margin-right: 8px;
        }
        
        .add-to-cart-btn:hover {
            background-color: var(--dark-color);
        }
        
        /* Cart Page */
        .cart-item {
            background-color: var(--white);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            box-shadow: var(--box-shadow);
        }
        
        .cart-item-image {
            width: 100px;
            height: 100px;
            border-radius: 8px;
            overflow: hidden;
            margin-right: 20px;
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
            font-weight: 600;
            font-size: 18px;
            margin-bottom: 5px;
        }
        
        .cart-item-price {
            color: var(--primary-color);
            font-weight: 600;
            margin-bottom: 10px;
        }
        
        .cart-item-quantity {
            display: flex;
            align-items: center;
        }
        
        .quantity-btn {
            background-color: var(--light-bg);
            border: 1px solid var(--border-color);
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .quantity-btn:hover {
            background-color: var(--primary-color);
            color: var(--white);
            border-color: var(--primary-color);
        }
        
        .quantity-input {
            width: 50px;
            text-align: center;
            margin: 0 10px;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            padding: 5px;
        }
        
        .remove-btn {
            color: var(--danger-color);
            background: none;
            border: none;
            cursor: pointer;
            margin-left: 20px;
            font-size: 18px;
            transition: all 0.3s ease;
        }
        
        .remove-btn:hover {
            color: #c0392b;
        }
        
        .cart-summary {
            background-color: var(--white);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 20px;
            box-shadow: var(--box-shadow);
        }
        
        .cart-total {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid var(--border-color);
        }
        
        .cart-total-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        
        .cart-total-row.final {
            font-weight: 700;
            font-size: 20px;
            color: var(--primary-color);
            margin-top: 10px;
        }
        
        /* Payment Methods */
        .payment-tabs {
            display: flex;
            margin-bottom: 20px;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            overflow: hidden;
        }
        
        .payment-tab {
            flex: 1;
            text-align: center;
            padding: 12px;
            background-color: var(--light-bg);
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 500;
        }
        
        .payment-tab.active {
            background-color: var(--white);
            border-bottom: 3px solid var(--primary-color);
            color: var(--primary-color);
        }
        
        .payment-content {
            display: none;
        }
        
        .payment-content.active {
            display: block;
        }
        
        .payment-form input, .payment-form textarea {
            margin-bottom: 15px;
            padding: 10px;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            width: 100%;
        }
        
        .payment-form button {
            background-color: var(--primary-color);
            color: var(--white);
            border: none;
            padding: 12px;
            border-radius: 4px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            width: 100%;
        }
        
        .payment-form button:hover {
            background-color: var(--dark-color);
        }
        
        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 50px 0;
        }
        
        .empty-state i {
            font-size: 60px;
            color: var(--light-text);
            margin-bottom: 20px;
        }
        
        .empty-state h3 {
            font-weight: 600;
            margin-bottom: 10px;
        }
        
        .empty-state p {
            color: var(--light-text);
            margin-bottom: 20px;
            max-width: 500px;
            margin-left: auto;
            margin-right: auto;
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
            background-color: var(--primary-color);
        }
        
        /* Chatbot */
        .chatbot-container {
            position: fixed;
            bottom: 20px;
            right: 20px;
            width: 350px;
            background-color: var(--white);
            border-radius: 8px;
            box-shadow: 0 5px 25px rgba(0,0,0,0.2);
            z-index: 1000;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            max-height: 500px;
            transition: all 0.3s ease;
        }

        .chatbot-container.collapsed {
            height: 60px;
        }

        .chatbot-header {
            background-color: var(--primary-color);
            color: var(--white);
            padding: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            cursor: pointer;
        }

        .chatbot-header h4 {
            margin: 0;
            font-size: 16px;
            font-weight: 600;
        }

        .chatbot-toggle {
            background: none;
            border: none;
            color: var(--white);
            cursor: pointer;
            font-size: 18px;
        }

        .chatbot-body {
            flex: 1;
            overflow-y: auto;
            padding: 15px;
            display: flex;
            flex-direction: column;
            gap: 10px;
            max-height: 300px;
        }

        .chat-message {
            padding: 10px 15px;
            border-radius: 8px;
            max-width: 80%;
            word-wrap: break-word;
        }

        .user-message {
            background-color: var(--light-color);
            color: var(--text-color);
            align-self: flex-end;
        }

        .bot-message {
            background-color: var(--light-bg);
            color: var(--text-color);
            align-self: flex-start;
        }

        .chatbot-footer {
            padding: 10px;
            border-top: 1px solid var(--border-color);
        }

        .chatbot-input-container {
            display: flex;
            gap: 10px;
        }

        .chatbot-input {
            flex: 1;
            padding: 10px;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            outline: none;
        }

        .chatbot-send {
            background-color: var(--primary-color);
            color: var(--white);
            border: none;
            border-radius: 4px;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .chatbot-send:hover {
            background-color: var(--dark-color);
        }

        .chat-timestamp {
            font-size: 10px;
            color: var(--light-text);
            margin-top: 5px;
            display: block;
        }

        .chatbot-button {
            position: fixed;
            bottom: 20px;
            right: 20px;
            width: 60px;
            height: 60px;
            background-color: var(--primary-color);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--white);
            cursor: pointer;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
            z-index: 999;
            transition: all 0.3s ease;
        }

        .chatbot-button:hover {
            transform: scale(1.1);
        }

        .chatbot-button i {
            font-size: 24px;
        }
        
        /* Footer */
        .footer {
            background-color: var(--white);
            padding: 60px 0 30px;
            margin-top: 60px;
            border-top: 1px solid var(--border-color);
        }
        
        .footer-title {
            font-weight: 600;
            font-size: 18px;
            margin-bottom: 20px;
            color: var(--text-color);
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
            color: var(--light-text);
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        .footer-links a:hover {
            color: var(--primary-color);
            padding-left: 5px;
        }
        
        .footer-bottom {
            text-align: center;
            padding-top: 30px;
            margin-top: 30px;
            border-top: 1px solid var(--border-color);
            color: var(--light-text);
        }
        
        .social-links {
            display: flex;
            margin-top: 20px;
        }
        
        .social-link {
            width: 36px;
            height: 36px;
            border-radius: 4px;
            background-color: var(--light-bg);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 10px;
            transition: all 0.3s ease;
        }
        
        .social-link:hover {
            background-color: var(--primary-color);
        }
        
        .social-link:hover i {
            color: var(--white);
        }
        
        .social-link i {
            color: var(--light-text);
            font-size: 16px;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .hero-title {
                font-size: 36px;
            }
            
            .hero-subtitle {
                font-size: 16px;
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
            
            .chatbot-container {
                width: 300px;
                bottom: 10px;
                right: 10px;
            }
            
            .chatbot-button {
                bottom: 10px;
                right: 10px;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-light fixed-top">
        <div class="container">
            <a class="navbar-brand" href="#"><i class="fas fa-shopping-basket"></i> Fresh Groceries</a>
            <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav mr-auto">
                    <li class="nav-item">
                        <a class="nav-link <?php echo $currentPage == 'products' ? 'active' : ''; ?>" href="?page=products">Shop</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="?page=orders">My Orders</a>
                    </li>
                </ul>
                <ul class="navbar-nav ml-auto">
                    <li class="nav-item">
                        <a class="nav-link cart-icon" href="?page=cart">
                            <i class="fas fa-shopping-cart"></i>
                            <span class="cart-count"><?php echo getCartCount($sessionCart); ?></span>
                        </a>
                    </li>
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

    <div class="container">
        <?php if($responseMessage): ?>
            <div class="alert alert-info alert-dismissible fade show mt-4" role="alert">
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
                    <h1 class="hero-title">Fresh Groceries Delivered</h1>
                    <p class="hero-subtitle">Shop for fresh fruits, vegetables, and essential groceries with convenient payment options</p>
                    <a href="#products" class="hero-btn">Shop Now</a>
                </div>
            </div>
            
            <!-- Category Filters -->
            <div class="category-filters">
                <a href="?page=products" class="category-filter <?php echo $category == '' ? 'active' : ''; ?>">All Products</a>
                <a href="?page=products&category=fruits" class="category-filter <?php echo $category == 'fruits' ? 'active' : ''; ?>">Fruits</a>
                <a href="?page=products&category=vegetables" class="category-filter <?php echo $category == 'vegetables' ? 'active' : ''; ?>">Vegetables</a>
                <a href="?page=products&category=grains" class="category-filter <?php echo $category == 'grains' ? 'active' : ''; ?>">Grains</a>
                <a href="?page=products&category=essentials" class="category-filter <?php echo $category == 'essentials' ? 'active' : ''; ?>">Essentials</a>
                <a href="?page=products&category=organic" class="category-filter <?php echo $category == 'organic' ? 'active' : ''; ?>">Organic</a>
            </div>
            
            <!-- Products Section -->
            <h2 class="section-title" id="products">Our Fresh Products</h2>
            <div class="row">
                <?php if(empty($filteredProducts)): ?>
                    <div class="col-12 empty-state">
                        <i class="fas fa-shopping-basket"></i>
                        <h3>No products found</h3>
                        <p>We couldn't find any products matching your selection. Please try another category or check back later.</p>
                        <a href="?page=products" class="btn btn-primary">View All Products</a>
                    </div>
                <?php else: ?>
                    <?php foreach($filteredProducts as $product): ?>
                        <div class="col-md-3 col-sm-6 mb-4">
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
                                    <div class="product-price">KSh <?php echo number_format($product['price'], 2); ?></div>
                                    <form method="post">
                                        <input type="hidden" name="productId" value="<?php echo $product['id']; ?>">
                                        <input type="hidden" name="productName" value="<?php echo $product['name']; ?>">
                                        <input type="hidden" name="productPrice" value="<?php echo $product['price']; ?>">
                                        <input type="hidden" name="productImage" value="<?php echo $product['image']; ?>">
                                        <button type="submit" name="add_to_cart" class="add-to-cart-btn">
                                            <i class="fas fa-shopping-cart"></i> Add to Cart
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
        <?php elseif($currentPage == 'cart'): ?>
            <!-- Cart Page -->
            <div class="row mt-5">
                <div class="col-12">
                    <h2 class="section-title">Your Shopping Cart</h2>
                </div>
            </div>
            
            <?php if(empty($sessionCart)): ?>
                <div class="empty-state">
                    <i class="fas fa-shopping-cart"></i>
                    <h3>Your cart is empty</h3>
                    <p>Looks like you haven't added any items to your cart yet. Browse our products and add some items to your cart.</p>
                    <a href="?page=products" class="btn btn-primary">Continue Shopping</a>
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
                                        <div class="cart-item-price">KSh <?php echo number_format($product['price'], 2); ?></div>
                                        <div class="cart-item-quantity">
                                            <button type="button" class="quantity-btn" onclick="decrementQuantity(<?php echo $productId; ?>)">-</button>
                                            <input type="number" name="quantity[<?php echo $productId; ?>]" value="<?php echo $product['quantity']; ?>" min="1" class="quantity-input">
                                            <button type="button" class="quantity-btn" onclick="incrementQuantity(<?php echo $productId; ?>)">+</button>
                                        </div>
                                    </div>
                                    <a href="?page=cart&remove_from_cart=<?php echo $productId; ?>" class="remove-btn">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </div>
                            <?php endforeach; ?>
                            
                            <div class="text-right mb-4">
                                <button type="submit" name="update_cart" class="btn btn-secondary">
                                    <i class="fas fa-sync-alt mr-2"></i> Update Cart
                                </button>
                            </div>
                        </form>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="cart-summary">
                            <h3 class="mb-4">Order Summary</h3>
                            <div class="cart-total">
                                <div class="cart-total-row">
                                    <span>Subtotal:</span>
                                    <span>KSh <?php echo number_format(calculateCartTotal($sessionCart), 2); ?></span>
                                </div>
                                <div class="cart-total-row">
                                    <span>Delivery:</span>
                                    <span>KSh 0.00</span>
                                </div>
                                <div class="cart-total-row final">
                                    <span>Total:</span>
                                    <span>KSh <?php echo number_format(calculateCartTotal($sessionCart), 2); ?></span>
                                </div>
                            </div>
                            
                            <div class="payment-section">
                                <h4 class="mb-3">Payment Method</h4>
                                
                                <!-- Payment Method Tabs -->
                                <div class="payment-tabs">
                                    <div class="payment-tab active" data-target="mpesa-payment">M-Pesa</div>
                                    <div class="payment-tab" data-target="cod-payment">Cash on Delivery</div>
                                </div>
                                
                                <!-- M-Pesa Payment Form -->
                                <div class="payment-content active" id="mpesa-payment">
                                    <div class="d-flex align-items-center bg-light p-3 rounded mb-3">
                                        <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/1/15/M-PESA_LOGO-01.svg/320px-M-PESA_LOGO-01.svg.png" alt="M-Pesa Logo" height="30" class="mr-2">
                                        <span>M-Pesa Payment</span>
                                    </div>
                                    
                                    <form method="POST" class="payment-form">
                                        <div class="form-group">
                                            <label for="phone">Phone Number</label>
                                            <input type="text" class="form-control" name="phone" id="phone" placeholder="Enter Phone Number (07XXXXXXXX)" required>
                                            <small class="form-text text-muted">Format: 07XXXXXXXX or 2547XXXXXXXX</small>
                                        </div>
                                        <input type="hidden" name="amount" value="<?php echo calculateCartTotal($sessionCart); ?>">
                                        <button type="submit" name="payment_submit">
                                            <i class="fas fa-money-bill-wave mr-2"></i> Pay KSh <?php echo number_format(calculateCartTotal($sessionCart), 2); ?>
                                        </button>
                                    </form>
                                </div>
                                
                                <!-- Cash on Delivery Form -->
                                <div class="payment-content" id="cod-payment">
                                    <div class="d-flex align-items-center bg-light p-3 rounded mb-3">
                                        <i class="fas fa-money-bill-wave text-success mr-2"></i>
                                        <span>Cash on Delivery</span>
                                    </div>
                                    
                                    <form method="POST" class="payment-form">
                                        <div class="form-group">
                                            <label for="customer_name">Full Name</label>
                                            <input type="text" class="form-control" name="customer_name" id="customer_name" placeholder="Enter your full name" required>
                                        </div>
                                        <div class="form-group">
                                            <label for="customer_phone">Phone Number</label>
                                            <input type="text" class="form-control" name="customer_phone" id="customer_phone" placeholder="Enter your phone number" required>
                                        </div>
                                        <div class="form-group">
                                            <label for="delivery_address">Delivery Address</label>
                                            <textarea class="form-control" name="delivery_address" id="delivery_address" rows="3" placeholder="Enter your delivery address" required></textarea>
                                        </div>
                                        <div class="form-group">
                                            <label for="order_notes">Order Notes (Optional)</label>
                                            <textarea class="form-control" name="order_notes" id="order_notes" rows="2" placeholder="Any special instructions for delivery"></textarea>
                                        </div>
                                        <input type="hidden" name="amount" value="<?php echo calculateCartTotal($sessionCart); ?>">
                                        <button type="submit" name="cod_submit">
                                            <i class="fas fa-check mr-2"></i> Place Order - Pay on Delivery
                                        </button>
                                        <p class="mt-2 text-muted small text-center">You will pay KSh <?php echo number_format(calculateCartTotal($sessionCart), 2); ?> in cash when your order is delivered</p>
                                    </form>
                                </div>
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
                    <h4 class="footer-title">Fresh Groceries</h4>
                    <p>We provide fresh groceries directly from local farmers to your doorstep.</p>
                    <div class="social-links">
                        <a href="#" class="social-link"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" class="social-link"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="social-link"><i class="fab fa-instagram"></i></a>
                        <a href="#" class="social-link"><i class="fab fa-linkedin-in"></i></a>
                    </div>
                </div>
                <div class="col-md-2 col-6 mb-4">
                    <h4 class="footer-title">Quick Links</h4>
                    <ul class="footer-links">
                        <li><a href="?page=products">Shop</a></li>
                        <li><a href="?page=cart">Cart</a></li>
                        <li><a href="?page=orders">My Orders</a></li>
                        <li><a href="#">About Us</a></li>
                        <li><a href="#">Contact</a></li>
                    </ul>
                </div>
                <div class="col-md-2 col-6 mb-4">
                    <h4 class="footer-title">Categories</h4>
                    <ul class="footer-links">
                        <li><a href="?page=products&category=fruits">Fruits</a></li>
                        <li><a href="?page=products&category=vegetables">Vegetables</a></li>
                        <li><a href="?page=products&category=grains">Grains</a></li>
                        <li><a href="?page=products&category=essentials">Essentials</a></li>
                        <li><a href="?page=products&category=organic">Organic</a></li>
                    </ul>
                </div>
                <div class="col-md-4 mb-4">
                    <h4 class="footer-title">Contact Us</h4>
                    <ul class="footer-links">
                        <li><i class="fas fa-map-marker-alt mr-2"></i> 123 Grocery Street, Nairobi, Kenya</li>
                        <li><i class="fas fa-phone mr-2"></i> +254 712 345 678</li>
                        <li><i class="fas fa-envelope mr-2"></i> info@freshgroceries.co.ke</li>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> Fresh Groceries. All Rights Reserved.</p>
            </div>
        </div>
    </footer>

    <!-- Chatbot Button -->
    <div class="chatbot-button" id="chatbotButton">
        <i class="fas fa-comments"></i>
    </div>

    <!-- Chatbot Container -->
    <div class="chatbot-container collapsed" id="chatbotContainer">
        <div class="chatbot-header" id="chatbotHeader">
            <h4><i class="fas fa-robot"></i> Grocery Assistant</h4>
            <button class="chatbot-toggle" id="chatbotToggle">
                <i class="fas fa-chevron-up" id="toggleIcon"></i>
            </button>
        </div>
        <div class="chatbot-body" id="chatbotBody">
            <div class="chat-message bot-message">
                Hello <?php echo $username; ?>! How can I help you today?
                <span class="chat-timestamp"><?php echo date('h:i A'); ?></span>
            </div>
            
            <?php foreach($chatHistory as $chat): ?>
            <div class="chat-message user-message">
                <?php echo htmlspecialchars($chat['message']); ?>
                <span class="chat-timestamp"><?php echo date('h:i A', strtotime($chat['created_at'])); ?></span>
            </div>
            <div class="chat-message bot-message">
                <?php echo htmlspecialchars($chat['response']); ?>
                <span class="chat-timestamp"><?php echo date('h:i A', strtotime($chat['created_at'])); ?></span>
            </div>
            <?php endforeach; ?>
        </div>
        <div class="chatbot-footer">
            <form id="chatbotForm" method="post">
                <div class="chatbot-input-container">
                    <input type="text" class="chatbot-input" id="chatbotInput" name="chatbot_message" placeholder="Type your message..." required>
                    <button type="submit" class="chatbot-send">
                        <i class="fas fa-paper-plane"></i>
                    </button>
                    <input type="hidden" name="ajax" value="1">
                </div>
            </form>
        </div>
    </div>

    <!-- JavaScript -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    
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
        
        // Payment method tabs
        document.addEventListener('DOMContentLoaded', function() {
            const paymentTabs = document.querySelectorAll('.payment-tab');
            
            paymentTabs.forEach(tab => {
                tab.addEventListener('click', function() {
                    // Remove active class from all tabs
                    paymentTabs.forEach(t => t.classList.remove('active'));
                    
                    // Add active class to clicked tab
                    this.classList.add('active');
                    
                    // Hide all payment content
                    document.querySelectorAll('.payment-content').forEach(content => {
                        content.classList.remove('active');
                    });
                    
                    // Show selected payment content
                    const target = this.getAttribute('data-target');
                    document.getElementById(target).classList.add('active');
                });
            });
        });
        
        // Chatbot functionality
        document.addEventListener('DOMContentLoaded', function() {
            const chatbotButton = document.getElementById('chatbotButton');
            const chatbotContainer = document.getElementById('chatbotContainer');
            const chatbotHeader = document.getElementById('chatbotHeader');
            const chatbotToggle = document.getElementById('chatbotToggle');
            const toggleIcon = document.getElementById('toggleIcon');
            const chatbotBody = document.getElementById('chatbotBody');
            const chatbotForm = document.getElementById('chatbotForm');
            const chatbotInput = document.getElementById('chatbotInput');
            
            // Toggle chatbot visibility
            function toggleChatbot() {
                chatbotContainer.classList.toggle('collapsed');
                
                if (chatbotContainer.classList.contains('collapsed')) {
                    toggleIcon.className = 'fas fa-chevron-up';
                } else {
                    toggleIcon.className = 'fas fa-chevron-down';
                    chatbotBody.scrollTop = chatbotBody.scrollHeight;
                }
            }
            
            chatbotButton.addEventListener('click', function() {
                chatbotContainer.classList.remove('collapsed');
                toggleIcon.className = 'fas fa-chevron-down';
                chatbotBody.scrollTop = chatbotBody.scrollHeight;
                chatbotButton.style.display = 'none';
            });
            
            chatbotHeader.addEventListener('click', function() {
                toggleChatbot();
                
                if (chatbotContainer.classList.contains('collapsed')) {
                    chatbotButton.style.display = 'flex';
                } else {
                    chatbotButton.style.display = 'none';
                }
            });
            
            // Handle form submission
            chatbotForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const message = chatbotInput.value.trim();
                if (!message) return;
                
                // Add user message to chat
                const userMessageElement = document.createElement('div');
                userMessageElement.className = 'chat-message user-message';
                userMessageElement.innerHTML = `
                    ${message}
                    <span class="chat-timestamp">${new Date().toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}</span>
                `;
                chatbotBody.appendChild(userMessageElement);
                
                // Clear input
                chatbotInput.value = '';
                
                // Scroll to bottom
                chatbotBody.scrollTop = chatbotBody.scrollHeight;
                
                // Send message to server
                const formData = new FormData();
                formData.append('chatbot_message', message);
                formData.append('ajax', 1);
                
                fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    // Add bot response to chat
                    const botMessageElement = document.createElement('div');
                    botMessageElement.className = 'chat-message bot-message';
                    botMessageElement.innerHTML = `
                        ${data.response}
                        <span class="chat-timestamp">${new Date().toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}</span>
                    `;
                    chatbotBody.appendChild(botMessageElement);
                    
                    // Scroll to bottom
                    chatbotBody.scrollTop = chatbotBody.scrollHeight;
                })
                .catch(error => {
                    console.error('Error:', error);
                });
            });
            
            // Auto-scroll chat to bottom on load
            chatbotBody.scrollTop = chatbotBody.scrollHeight;
        });
    </script>
</body>
</html>