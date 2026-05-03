<?php
session_start();

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'admin') {
        header('Location: admin/dashboard.php');
    } else {
        header('Location: passenger/dashboard.php');
    }
    exit;
}

$error = $_GET['error'] ?? null;
$registered = $_GET['registered'] ?? null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transit System - Login</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0;
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 25px 80px rgba(0,0,0,0.3);
            max-width: 900px;
            width: 100%;
        }
        .left-panel {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 60px 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        .logo {
            font-size: 3rem;
            margin-bottom: 20px;
        }
        .left-panel h1 {
            font-size: 2rem;
            margin-bottom: 15px;
        }
        .left-panel p {
            opacity: 0.9;
            line-height: 1.6;
        }
        .right-panel {
            padding: 60px 40px;
        }
        .tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 30px;
        }
        .tab {
            flex: 1;
            padding: 12px;
            border: none;
            background: #f1f5f9;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s;
        }
        .tab.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .form-section {
            display: none;
        }
        .form-section.active {
            display: block;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #374151;
            font-weight: 500;
            font-size: 0.9rem;
        }
        .form-group input {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }
        .form-group input:focus {
            outline: none;
            border-color: #667eea;
        }
        .btn-submit {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4);
        }
        .alert {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 0.9rem;
        }
        .alert-error {
            background: #fee2e2;
            color: #dc2626;
            border: 1px solid #fecaca;
        }
        .alert-success {
            background: #d1fae5;
            color: #059669;
            border: 1px solid #a7f3d0;
        }
        @media (max-width: 768px) {
            .container {
                grid-template-columns: 1fr;
            }
            .left-panel {
                padding: 40px 30px;
            }
            .right-panel {
                padding: 40px 30px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="left-panel">
            <div class="logo">🚌</div>
            <h1>Welcome Back!</h1>
            <p>Sign in to access your transit account, purchase passes, track vehicles in real-time, and manage your travel.</p>
        </div>
        <div class="right-panel">
            <div class="tabs">
                <button class="tab active" onclick="showTab('login')">Login</button>
                <button class="tab" onclick="showTab('register')">Register</button>
            </div>
            
            <?php if ($error === 'invalid'): ?>
                <div class="alert alert-error">Invalid email or password. Please try again.</div>
            <?php elseif ($error === 'missing_fields'): ?>
                <div class="alert alert-error">Please fill in all required fields.</div>
            <?php elseif ($error === 'email_exists'): ?>
                <div class="alert alert-error">Email already registered. Please use a different email.</div>
            <?php elseif ($registered === 'success'): ?>
                <div class="alert alert-success">Registration successful! Please log in.</div>
            <?php endif; ?>
            
            <div id="login" class="form-section active">
                <form action="auth/login.php" method="POST">
                    <div class="form-group">
                        <label for="login-email">Email Address</label>
                        <input type="email" id="login-email" name="email" required 
                               placeholder="Enter your email">
                    </div>
                    <div class="form-group">
                        <label for="login-password">Password</label>
                        <input type="password" id="login-password" name="password" required 
                               placeholder="Enter your password">
                    </div>
                    <button type="submit" class="btn-submit">Sign In</button>
                </form>
                <p style="text-align: center; margin-top: 20px; color: #6b7280; font-size: 0.9rem;">
                    <!-- Admin account must be created through admin registration -->
                </p>
            </div>
            
            <div id="register" class="form-section">
                <form action="auth/register.php" method="POST">
                    <div class="form-group">
                        <label for="reg-name">Full Name</label>
                        <input type="text" id="reg-name" name="name" required 
                               placeholder="Enter your full name">
                    </div>
                    <div class="form-group">
                        <label for="reg-email">Email Address</label>
                        <input type="email" id="reg-email" name="email" required 
                               placeholder="Enter your email">
                    </div>
                    <div class="form-group">
                        <label for="reg-phone">Phone Number</label>
                        <input type="tel" id="reg-phone" name="phone" 
                               placeholder="Enter your phone number">
                    </div>
                    <div class="form-group">
                        <label for="reg-password">Password</label>
                        <input type="password" id="reg-password" name="password" required 
                               placeholder="Create a password">
                    </div>
                    <button type="submit" class="btn-submit">Create Account</button>
                </form>
            </div>
        </div>
    </div>
    
    <script>
        function showTab(tab) {
            document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
            document.querySelectorAll('.form-section').forEach(s => s.classList.remove('active'));
            
            event.target.classList.add('active');
            document.getElementById(tab).classList.add('active');
        }
    </script>
</body>
</html>
