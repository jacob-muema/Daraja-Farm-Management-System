<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ADMT Farm Management</title>
    <link rel="shortcut icon" type="image/x-icon" href="logo.jpeg"/>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link href="//maxcdn.bootstrapcdn.com/bootstrap/4.1.1/css/bootstrap.min.css" rel="stylesheet" id="bootstrap-css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    
    <style>
        :root {
            --primary: #2c8c3c;
            --primary-light: #4caf50;
            --primary-dark: #1b5e20;
            --accent: #ffc107;
            --light: #f8f9fa;
            --dark: #212529;
            --success: #28a745;
            --danger: #dc3545;
            --gray-100: #f8f9fa;
            --gray-200: #e9ecef;
            --gray-300: #dee2e6;
            --gray-800: #343a40;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Nunito', sans-serif;
            background-color: #f0f2f5;
            color: var(--dark);
            min-height: 100vh;
        }
        
        /* Header */
        .site-header {
            background-color: var(--primary);
            padding: 15px 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            position: fixed;
            width: 100%;
            top: 0;
            z-index: 1000;
        }
        
        .site-header .container {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .site-logo {
            display: flex;
            align-items: center;
            color: white;
            text-decoration: none;
        }
        
        .site-logo i {
            color: var(--accent);
            font-size: 24px;
            margin-right: 10px;
        }
        
        .site-logo h1 {
            font-size: 20px;
            margin: 0;
            font-weight: 700;
        }
        
        .main-nav ul {
            display: flex;
            list-style: none;
            margin: 0;
            padding: 0;
        }
        
        .main-nav li {
            margin-left: 30px;
        }
        
        .main-nav a {
            color: rgba(255,255,255,0.9);
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            transition: color 0.3s;
        }
        
        .main-nav a:hover {
            color: var(--accent);
        }
        
        /* Main Content */
        .main-content {
            padding: 100px 0 50px;
        }
        
        .auth-container {
            max-width: 1000px;
            margin: 0 auto;
            background: white;
            border-radius: 10px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
            overflow: hidden;
        }
        
        /* Welcome Section */
        .welcome-section {
            background-color: var(--primary);
            color: white;
            text-align: center;
            padding: 40px;
            border-radius: 10px 10px 0 0;
        }
        
        .welcome-section h2 {
            font-size: 28px;
            margin-bottom: 15px;
            font-weight: 700;
        }
        
        .welcome-section p {
            font-size: 16px;
            opacity: 0.9;
            max-width: 600px;
            margin: 0 auto 20px;
        }
        
        .logo-container {
            width: 100px;
            height: 100px;
            margin: 0 auto;
            background: rgba(255,255,255,0.1);
            border-radius: 50%;
            padding: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .logo-container img {
            max-width: 100%;
            border-radius: 50%;
        }
        
        /* Tabs */
        .auth-tabs {
            display: flex;
            background-color: #f5f5f5;
            border-bottom: 1px solid #eee;
        }
        
        .auth-tab {
            flex: 1;
            text-align: center;
            padding: 15px;
            font-weight: 600;
            color: var(--gray-800);
            cursor: pointer;
            transition: all 0.3s;
            border-bottom: 3px solid transparent;
        }
        
        .auth-tab.active {
            color: var(--primary);
            border-bottom-color: var(--primary);
            background-color: white;
        }
        
        /* Forms */
        .auth-forms {
            padding: 30px;
        }
        
        .auth-form {
            display: none;
        }
        
        .auth-form.active {
            display: block;
        }
        
        .form-title {
            text-align: center;
            color: var(--primary);
            margin-bottom: 30px;
            font-weight: 700;
            font-size: 24px;
        }
        
        .form-row {
            display: flex;
            margin: 0 -15px;
            flex-wrap: wrap;
        }
        
        .form-col {
            flex: 1;
            padding: 0 15px;
            min-width: 250px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--gray-800);
            font-size: 14px;
        }
        
        .form-control {
            width: 100%;
            height: 45px;
            padding: 10px 15px;
            border: 1px solid var(--gray-300);
            border-radius: 5px;
            font-size: 14px;
            transition: all 0.3s;
        }
        
        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(44, 140, 60, 0.1);
            outline: none;
        }
        
        /* Radio Buttons */
        .radio-group {
            display: flex;
            margin-top: 10px;
        }
        
        .radio-option {
            display: flex;
            align-items: center;
            margin-right: 20px;
            cursor: pointer;
        }
        
        .radio-option input {
            margin-right: 8px;
        }
        
        /* Submit Button */
        .submit-btn {
            width: 100%;
            background-color: var(--primary);
            color: white;
            border: none;
            padding: 12px;
            border-radius: 5px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 10px;
        }
        
        .submit-btn:hover {
            background-color: var(--primary-dark);
        }
        
        /* Links */
        .auth-link {
            display: block;
            text-align: center;
            margin-top: 20px;
            color: var(--primary);
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
        }
        
        .auth-link:hover {
            text-decoration: underline;
        }
        
        /* Password Match Message */
        #message {
            font-size: 12px;
            margin-top: 5px;
            display: inline-block;
            font-weight: 600;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .site-header .container {
                flex-direction: column;
                text-align: center;
            }
            
            .main-nav {
                margin-top: 15px;
            }
            
            .main-nav ul {
                justify-content: center;
            }
            
            .main-nav li {
                margin: 0 10px;
            }
            
            .form-row {
                flex-direction: column;
            }
            
            .form-col {
                padding: 0;
            }
            
            .auth-forms {
                padding: 20px;
            }
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Tab switching
            const tabs = document.querySelectorAll('.auth-tab');
            const forms = document.querySelectorAll('.auth-form');
            
            tabs.forEach(tab => {
                tab.addEventListener('click', () => {
                    // Remove active class from all tabs and forms
                    tabs.forEach(t => t.classList.remove('active'));
                    forms.forEach(f => f.classList.remove('active'));
                    
                    // Add active class to clicked tab and corresponding form
                    tab.classList.add('active');
                    document.getElementById(tab.dataset.target).classList.add('active');
                });
            });
        });
        
        var check = function() {
            if (document.getElementById('password').value ==
                document.getElementById('cpassword').value) {
                document.getElementById('message').style.color = '#28a745';
                document.getElementById('message').innerHTML = 'Matched';
            } else {
                document.getElementById('message').style.color = '#dc3545';
                document.getElementById('message').innerHTML = 'Not Matching';
            }
        }

        function alphaOnly(event) {
            var key = event.keyCode;
            return ((key >= 65 && key <= 90) || key == 8 || key == 32);
        }

        function checklen() {
            var pass1 = document.getElementById("password");  
            if(pass1.value.length < 6) {  
                alert("Password must be at least 6 characters long. Try again!");  
                return false;  
            }  
        }
    </script>
</head>

<body>
    <!-- Header -->
    <header class="site-header">
        <div class="container">
            <a href="index.php" class="site-logo">
                <i class="fa fa-leaf"></i>
                <h1>ADMT FARM MANAGEMENT</h1>
            </a>
            
            <nav class="main-nav">
                <ul>
                    <li><a href="index.php">Home</a></li>
                    <li><a href="services.html">About Us</a></li>
                    <li><a href="contact.html">Contact</a></li>
                </ul>
            </nav>
        </div>
    </header>
    
    <!-- Main Content -->
    <main class="main-content">
        <div class="container">
            <div class="auth-container">
                <!-- Welcome Section -->
                <div class="welcome-section">
                    <h2>Welcome to ADMT Farm Management</h2>
                    <p>Join our advanced farm management system to optimize your agricultural operations and increase productivity.</p>
                    <div class="logo-container">
                        <img src="logo.jpeg" alt="ADMT Farm Logo">
                    </div>
                </div>
                
                <!-- Auth Tabs -->
                <div class="auth-tabs">
                    <div class="auth-tab active" data-target="user-form">Customer</div>
                    <div class="auth-tab" data-target="admin-form">Farmer</div>
                </div>
                
                <!-- Auth Forms -->
                <div class="auth-forms">
                    <!-- User Registration Form -->
                    <div id="user-form" class="auth-form active">
                        <h3 class="form-title">Register as User</h3>
                        <!-- This form submits to func2.php which will redirect to index1.php after successful registration -->
                        <form method="post" action="func2.php">
                            <div class="form-row">
                                <div class="form-col">
                                    <div class="form-group">
                                        <label for="fname">First Name</label>
                                        <input type="text" id="fname" class="form-control" placeholder="Enter your first name" name="fname" onkeydown="return alphaOnly(event);" required/>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="email">Email Address</label>
                                        <input type="email" id="email" class="form-control" placeholder="Enter your email" name="email" required/>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="password">Password</label>
                                        <input type="password" id="password" class="form-control" placeholder="Create a password" name="password" onkeyup='check();' required/>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>Gender</label>
                                        <div class="radio-group">
                                            <label class="radio-option">
                                                <input type="radio" name="gender" value="Male" checked>
                                                Male
                                            </label>
                                            <label class="radio-option">
                                                <input type="radio" name="gender" value="Female">
                                                Female
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="form-col">
                                    <div class="form-group">
                                        <label for="lname">Last Name</label>
                                        <input type="text" id="lname" class="form-control" placeholder="Enter your last name" name="lname" onkeydown="return alphaOnly(event);" required/>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="contact">Phone Number</label>
                                        <input type="tel" id="contact" minlength="10" maxlength="10" name="contact" class="form-control" placeholder="Enter your phone number" required/>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="cpassword">Confirm Password</label>
                                        <input type="password" id="cpassword" class="form-control" placeholder="Confirm your password" name="cpassword" onkeyup='check();' required/>
                                        <span id='message'></span>
                                    </div>
                                    
                                    <button type="submit" class="submit-btn" name="usersub1" onclick="return checklen();">Register Account</button>
                                </div>
                            </div>
                            
                            <a href="index1.php" class="auth-link">Already have an account? Login here</a>
                        </form>
                    </div>
                    
                    <!-- Admin Login Form -->
                    <div id="admin-form" class="auth-form">
                        <h3 class="form-title">Login as Admin</h3>
                        <form method="post" action="func3.php">
                            <div class="form-group">
                                <label for="username1">Username</label>
                                <input type="text" id="username1" class="form-control" placeholder="Enter your username" name="username1" onkeydown="return alphaOnly(event);" required/>
                            </div>
                            
                            <div class="form-group">
                                <label for="password2">Password</label>
                                <input type="password" id="password2" class="form-control" placeholder="Enter your password" name="password2" required/>
                            </div>
                            
                            <button type="submit" class="submit-btn" name="adsub">Login</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="https://code.jquery.com/jquery-3.3.1.slim.min.js" integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js" integrity="sha384-UO2eT0CpHqdSJQ6hJty5KVphtPhzWj9WO1clHTMGa3JDZwrnQq4sF86dIHNDz0W1" crossorigin="anonymous"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js" integrity="sha384-JjSmVgyd0p3pXB1rRibZUAYoIIy6OrQ6VrjIEaFf/nJGzIxFDsf4x0xIM+B07jRM" crossorigin="anonymous"></script>
</body>
</html>