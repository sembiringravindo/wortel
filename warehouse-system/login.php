<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';

if ($auth->isLoggedIn()) {
    header('Location: dashboard/index.php');
    exit();
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if ($auth->login($username, $password)) {
        header('Location: dashboard/index.php');
        exit();
    } else {
        $error = 'Username atau password salah!';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistem Gudang Wortel</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
        }
        
        :root {
            --primary: #10B981;
            --primary-dark: #059669;
            --primary-light: #D1FAE5;
            --secondary: #8B5CF6;
            --dark: #1F2937;
            --light: #F9FAFB;
            --gray: #6B7280;
            --error: #EF4444;
            --warning: #F59E0B;
            --info: #3B82F6;
        }
        
        body {
            background: linear-gradient(135deg, #1a1f2e 0%, #2d3748 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
            position: relative;
            overflow-x: hidden;
        }
        
        /* Background Animation */
        .bg-animation {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            overflow: hidden;
        }
        
        .bg-animation div {
            position: absolute;
            border-radius: 50%;
            background: rgba(16, 185, 129, 0.05);
            animation: float 15s infinite linear;
        }
        
        .bg-animation div:nth-child(1) {
            width: 80px;
            height: 80px;
            top: 20%;
            left: 10%;
            animation-delay: 0s;
        }
        
        .bg-animation div:nth-child(2) {
            width: 150px;
            height: 150px;
            top: 60%;
            left: 80%;
            animation-delay: 3s;
        }
        
        .bg-animation div:nth-child(3) {
            width: 100px;
            height: 100px;
            top: 80%;
            left: 20%;
            animation-delay: 6s;
        }
        
        .bg-animation div:nth-child(4) {
            width: 120px;
            height: 120px;
            top: 10%;
            left: 70%;
            animation-delay: 9s;
        }
        
        @keyframes float {
            0%, 100% {
                transform: translateY(0) translateX(0) rotate(0deg);
            }
            25% {
                transform: translateY(-20px) translateX(10px) rotate(90deg);
            }
            50% {
                transform: translateY(0) translateX(20px) rotate(180deg);
            }
            75% {
                transform: translateY(20px) translateX(10px) rotate(270deg);
            }
        }
        
        /* Login Container */
        .login-container {
            width: 100%;
            max-width: 450px;
            animation: fadeIn 0.8s ease-out;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .login-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 24px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3), 0 0 0 1px rgba(255, 255, 255, 0.1);
            position: relative;
            overflow: hidden;
        }
        
        /* Header Section */
        .login-header {
            text-align: center;
            margin-bottom: 40px;
            position: relative;
        }
        
        .logo-container {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .logo-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 28px;
            box-shadow: 0 8px 20px rgba(16, 185, 129, 0.3);
        }
        
        .logo-text h1 {
            font-size: 28px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-weight: 700;
            margin-bottom: 5px;
        }
        
        .logo-text p {
            color: var(--gray);
            font-size: 14px;
            font-weight: 500;
        }
        
        /* Form Styling */
        .form-group {
            margin-bottom: 24px;
            position: relative;
        }
        
        .form-label {
            display: block;
            margin-bottom: 8px;
            color: var(--dark);
            font-weight: 600;
            font-size: 14px;
            transition: color 0.3s;
        }
        
        .input-container {
            position: relative;
        }
        
        .input-icon {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--gray);
            font-size: 18px;
            transition: color 0.3s;
            z-index: 1;
        }
        
        .form-control {
            width: 100%;
            padding: 16px 16px 16px 50px;
            border: 2px solid #E5E7EB;
            border-radius: 12px;
            font-size: 16px;
            transition: all 0.3s;
            background-color: white;
            color: var(--dark);
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
        }
        
        .form-control:focus + .input-icon {
            color: var(--primary);
        }
        
        /* Password Toggle */
        .password-toggle {
            position: absolute;
            right: 16px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--gray);
            cursor: pointer;
            font-size: 18px;
            transition: color 0.3s;
        }
        
        .password-toggle:hover {
            color: var(--primary);
        }
        
        /* Error Message */
        .error-message {
            background: linear-gradient(135deg, rgba(239, 68, 68, 0.1), rgba(239, 68, 68, 0.05));
            color: var(--error);
            padding: 14px;
            border-radius: 12px;
            margin-bottom: 24px;
            text-align: center;
            border-left: 4px solid var(--error);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            animation: shake 0.5s ease;
        }
        
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }
        
        /* Button Container */
        .button-container {
            display: flex;
            flex-direction: column;
            gap: 15px;
            margin-top: 10px;
        }
        
        /* Login Button */
        .btn-login {
            width: 100%;
            padding: 18px;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            box-shadow: 0 8px 20px rgba(16, 185, 129, 0.3);
            position: relative;
            overflow: hidden;
        }
        
        .btn-login:before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.7s;
        }
        
        .btn-login:hover:before {
            left: 100%;
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 25px rgba(16, 185, 129, 0.4);
        }
        
        .btn-login:active {
            transform: translateY(0);
        }
        
        /* Back Button */
        .btn-back {
            width: 100%;
            padding: 16px;
            background: transparent;
            color: var(--gray);
            border: 2px solid #E5E7EB;
            border-radius: 12px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            position: relative;
            overflow: hidden;
        }
        
        .btn-back:before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(59, 130, 246, 0.05), transparent);
            transition: left 0.7s;
        }
        
        .btn-back:hover:before {
            left: 100%;
        }
        
        .btn-back:hover {
            background: rgba(59, 130, 246, 0.05);
            border-color: var(--info);
            color: var(--info);
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(59, 130, 246, 0.1);
        }
        
        .btn-back:active {
            transform: translateY(0);
        }
        
        /* Footer */
        .login-footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #E5E7EB;
            color: var(--gray);
            font-size: 14px;
        }
        
        .copyright {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        
        .carrot-icon {
            color: var(--warning);
            animation: bounce 2s infinite;
        }
        
        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-5px); }
        }
        
        /* Responsive */
        @media (max-width: 480px) {
            .login-card {
                padding: 30px 25px;
            }
            
            .logo-container {
                flex-direction: column;
                gap: 10px;
            }
            
            .logo-text h1 {
                font-size: 24px;
            }
            
            .button-container {
                gap: 12px;
            }
        }
        
        @media (min-width: 768px) {
            .button-container {
                flex-direction: row;
            }
            
            .btn-login, .btn-back {
                flex: 1;
            }
        }
        
        /* Loading Animation */
        .loading-spinner {
            display: none;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 1s ease-in-out infinite;
        }
        
        .loading-spinner-dark {
            display: none;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(107, 114, 128, 0.3);
            border-radius: 50%;
            border-top-color: var(--gray);
            animation: spin 1s ease-in-out infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        /* Decorative Elements */
        .decorative-element {
            position: absolute;
            width: 200px;
            height: 200px;
            background: linear-gradient(135deg, rgba(139, 92, 246, 0.05), rgba(16, 185, 129, 0.05));
            border-radius: 50%;
            top: -100px;
            right: -100px;
            z-index: -1;
        }
        
        /* Additional decorative element for bottom left */
        .decorative-element-bottom {
            position: absolute;
            width: 150px;
            height: 150px;
            background: linear-gradient(135deg, rgba(245, 158, 11, 0.05), rgba(59, 130, 246, 0.05));
            border-radius: 50%;
            bottom: -75px;
            left: -75px;
            z-index: -1;
        }
    </style>
</head>
<body>
    <!-- Background Animation -->
    <div class="bg-animation">
        <div></div>
        <div></div>
        <div></div>
        <div></div>
    </div>
    
    <div class="login-container">
        <div class="login-card">
            <!-- Decorative Elements -->
            <div class="decorative-element"></div>
            <div class="decorative-element-bottom"></div>
            
            <!-- Header -->
            <div class="login-header">
                <div class="logo-container">
                    <div class="logo-icon">
                        <i class="fas fa-warehouse"></i>
                    </div>
                    <div class="logo-text">
                        <h1>Desa Barus Julu</h1>
                        <p>Sistem Manajemen Gudang Wortel</p>
                    </div>
                </div>
            </div>
            
            <!-- Error Message -->
            <?php if ($error): ?>
                <div class="error-message">
                    <i class="fas fa-exclamation-circle"></i>
                    <span><?php echo htmlspecialchars($error); ?></span>
                </div>
            <?php endif; ?>
            
            <!-- Login Form -->
            <form method="POST" action="" id="loginForm">
                <div class="form-group">
                    <label class="form-label" for="username">Username</label>
                    <div class="input-container">
                        <div class="input-icon">
                            <i class="fas fa-user"></i>
                        </div>
                        <input type="text" id="username" name="username" class="form-control" placeholder="Masukkan username" required autofocus>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="password">Password</label>
                    <div class="input-container">
                        <div class="input-icon">
                            <i class="fas fa-lock"></i>
                        </div>
                        <input type="password" id="password" name="password" class="form-control" placeholder="Masukkan password" required>
                        <button type="button" class="password-toggle" id="togglePassword">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
                
                <!-- Button Container -->
                <div class="button-container">
                    <button type="submit" class="btn-login" id="loginButton">
                        <span>Masuk ke Sistem</span>
                        <i class="fas fa-sign-in-alt"></i>
                        <div class="loading-spinner" id="loadingSpinner"></div>
                    </button>
                    
                    <button type="button" class="btn-back" id="backButton" onclick="goToHome()">
                        <i class="fas fa-home"></i>
                        <span>Kembali ke Beranda</span>
                        <div class="loading-spinner-dark" id="loadingSpinnerBack"></div>
                    </button>
                </div>
            </form>
            
            <!-- Footer -->
            <div class="login-footer">
                <div class="copyright">
                    <i class="fas fa-carrot carrot-icon"></i>
                    <span>© 2024 Desa Barus Julu - Sistem Gudang Wortel</span>
                </div>
                <p style="margin-top: 8px; font-size: 13px; color: #9CA3AF;">Anda memerlukan akun untuk mengakses sistem</p>
            </div>
        </div>
    </div>
    
    <script src="assets/js/main.js"></script>
    <script>
        // Toggle password visibility
        document.getElementById('togglePassword').addEventListener('click', function() {
            const passwordInput = document.getElementById('password');
            const icon = this.querySelector('i');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });
        
        // Form submission with loading state
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const loginButton = document.getElementById('loginButton');
            const loadingSpinner = document.getElementById('loadingSpinner');
            const buttonText = loginButton.querySelector('span');
            const buttonIcon = loginButton.querySelector('.fa-sign-in-alt');
            
            // Show loading state
            buttonText.textContent = 'Memproses...';
            buttonIcon.style.display = 'none';
            loadingSpinner.style.display = 'block';
            loginButton.disabled = true;
            
            // Simulate processing delay
            setTimeout(() => {
                // Reset button state
                buttonText.textContent = 'Masuk ke Sistem';
                buttonIcon.style.display = 'inline-block';
                loadingSpinner.style.display = 'none';
                loginButton.disabled = false;
            }, 1500);
        });
        
        // Back button function
        function goToHome() {
            const backButton = document.getElementById('backButton');
            const loadingSpinnerBack = document.getElementById('loadingSpinnerBack');
            const buttonText = backButton.querySelector('span');
            const buttonIcon = backButton.querySelector('.fa-home');
            
            // Show loading state
            buttonText.textContent = 'Mengarahkan...';
            buttonIcon.style.display = 'none';
            loadingSpinnerBack.style.display = 'block';
            backButton.disabled = true;
            
            // Simulate delay before redirecting
            setTimeout(() => {
                // Change text to show redirecting
                buttonText.textContent = 'Mengarahkan ke Beranda...';
                
                // Redirect to home page after another brief delay
                setTimeout(() => {
                    // Redirect to index.php
                    window.location.href = 'index.php';
                }, 800);
            }, 700);
        }
        
        // Alternative: Go back to previous page if from same site
        function goBack() {
            if (document.referrer && document.referrer.includes(window.location.hostname)) {
                window.history.back();
            } else {
                window.location.href = '/';
            }
        }
        
        // Add focus effects to form inputs
        const inputs = document.querySelectorAll('.form-control');
        inputs.forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.parentElement.querySelector('.form-label').style.color = 'var(--primary)';
            });
            
            input.addEventListener('blur', function() {
                if (!this.value) {
                    this.parentElement.parentElement.querySelector('.form-label').style.color = 'var(--dark)';
                }
            });
        });
        
        // Add animation to logo icon on page load
        document.addEventListener('DOMContentLoaded', function() {
            const logoIcon = document.querySelector('.logo-icon');
            setTimeout(() => {
                logoIcon.style.transform = 'scale(1.1)';
                setTimeout(() => {
                    logoIcon.style.transform = 'scale(1)';
                }, 300);
            }, 500);
            
            // Add hover effect to back button icon
            const backIcon = document.querySelector('.btn-back .fa-home');
            document.getElementById('backButton').addEventListener('mouseenter', function() {
                backIcon.style.transform = 'translateX(-3px)';
            });
            
            document.getElementById('backButton').addEventListener('mouseleave', function() {
                backIcon.style.transform = 'translateX(0)';
            });
        });
        
        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            // Ctrl+Enter to submit form
            if (e.ctrlKey && e.key === 'Enter') {
                document.getElementById('loginForm').submit();
            }
            
            // Escape key to go back
            if (e.key === 'Escape') {
                goToHome();
            }
        });
    </script>
</body>
</html>