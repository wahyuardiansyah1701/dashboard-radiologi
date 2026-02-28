<?php
session_start();
require_once 'config.php';

// Jika sudah login, redirect ke dashboard
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    header('Location: dashboard.php');
    exit;
}

$error = '';

// Proses login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    
    // Validasi input
    if (empty($username) || empty($password)) {
        $error = "Username dan password harus diisi!";
    } else {
        // Cek kredensial (dalam produksi, gunakan database dengan password hash)
        // Contoh multiple user:
        $users = [
            'admin' => 'admin123',
            'radiologi' => 'radio123',
            'Wahyu' => 'wahyu'

        ];
        
        if (isset($users[$username]) && $users[$username] === $password) {
            $_SESSION['logged_in'] = true;
            $_SESSION['username'] = $username;
            $_SESSION['login_time'] = time();
            
            header('Location: dashboard.php');
            exit;
        } else {
            $error = "Username atau password salah!";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistem Radiologi</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #7fd8be 0%, #ff9f5a 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 15px;
            position: relative;
        }
        
        body::before {
            content: '';
            position: absolute;
            width: 350px;
            height: 350px;
            background: radial-gradient(circle, rgba(127, 216, 190, 0.3) 0%, transparent 70%);
            border-radius: 50%;
            top: -100px;
            left: -100px;
            animation: float 6s ease-in-out infinite;
        }
        
        body::after {
            content: '';
            position: absolute;
            width: 300px;
            height: 300px;
            background: radial-gradient(circle, rgba(255, 159, 90, 0.3) 0%, transparent 70%);
            border-radius: 50%;
            bottom: -80px;
            right: -80px;
            animation: float 8s ease-in-out infinite;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-15px); }
        }
        
        .login-container {
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(10px);
            padding: 30px 35px;
            border-radius: 20px;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.12);
            width: 100%;
            max-width: 400px;
            animation: slideIn 0.5s ease-out;
            position: relative;
            z-index: 1;
            border: 1px solid rgba(255, 255, 255, 0.9);
        }
        
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 25px;
        }
        
        .logo {
            margin-bottom: 12px;
            display: inline-block;
        }
        
        .logo img {
            width: 80px;
            height: 80px;
            object-fit: contain;
            filter: drop-shadow(0 3px 8px rgba(127, 216, 190, 0.3));
        }
        
        .login-header h1 {
            background: linear-gradient(135deg, #3fa87d 0%, #ff8540 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-size: 22px;
            margin-bottom: 6px;
            font-weight: 700;
        }
        
        .login-header p {
            color: #666;
            font-size: 13px;
            font-weight: 400;
        }
        
        .form-group {
            margin-bottom: 18px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 7px;
            color: #333;
            font-weight: 500;
            font-size: 13px;
        }
        
        .input-wrapper {
            position: relative;
        }
        
        .input-icon {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 16px;
            z-index: 1;
        }
        
        .form-group input {
            width: 100%;
            padding: 11px 15px 11px 42px;
            border: 2px solid #e5f5ef;
            border-radius: 10px;
            font-size: 14px;
            transition: all 0.3s ease;
            background: #fafafa;
            font-family: 'Poppins', sans-serif;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #7fd8be;
            background: white;
            box-shadow: 0 3px 12px rgba(127, 216, 190, 0.15);
        }
        
        .btn-login {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #7fd8be 0%, #ff9f5a 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 8px;
            box-shadow: 0 6px 20px rgba(127, 216, 190, 0.3);
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(127, 216, 190, 0.35);
            background: linear-gradient(135deg, #6ac9ad 0%, #ff8c47 100%);
        }
        
        .btn-login:active {
            transform: translateY(0);
        }
        
        .error-message {
            background: linear-gradient(135deg, #ffe5e5 0%, #fff5f5 100%);
            color: #d32f2f;
            padding: 11px 15px;
            border-radius: 10px;
            margin-bottom: 18px;
            text-align: center;
            font-size: 13px;
            border-left: 3px solid #ff6b6b;
            animation: shake 0.5s;
        }
        
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-8px); }
            75% { transform: translateX(8px); }
        }
        
        .demo-info {
            margin-top: 20px;
            padding: 18px;
            background: linear-gradient(135deg, #f0fdf8 0%, #fff4ed 100%);
            border-radius: 12px;
            font-size: 12px;
            color: #555;
            border-left: 3px solid #7fd8be;
            box-shadow: 0 3px 12px rgba(127, 216, 190, 0.08);
        }
        
        .demo-info h3 {
            background: linear-gradient(135deg, #3fa87d 0%, #ff8540 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 12px;
            font-size: 13px;
            font-weight: 600;
        }
        
        .demo-info .user-demo {
            margin: 8px 0;
            padding: 10px 12px;
            background: white;
            border-radius: 8px;
            display: flex;
            justify-content: space-between;
            transition: all 0.3s ease;
            border: 1px solid #e5f5ef;
            font-size: 12px;
        }
        
        .demo-info .user-demo:hover {
            transform: translateX(3px);
            box-shadow: 0 3px 10px rgba(127, 216, 190, 0.12);
            border-color: #7fd8be;
        }
        
        .demo-info strong {
            background: linear-gradient(135deg, #3fa87d 0%, #ff8540 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-weight: 600;
        }
        
        .footer-text {
            text-align: center;
            margin-top: 18px;
            color: #999;
            font-size: 11px;
            font-weight: 300;
        }
        
        .remember-me {
            display: flex;
            align-items: center;
            margin: 15px 0;
        }
        
        .remember-me input[type="checkbox"] {
            margin-right: 8px;
            width: 16px;
            height: 16px;
            cursor: pointer;
            accent-color: #7fd8be;
        }
        
        .remember-me label {
            font-size: 13px;
            color: #666;
            margin: 0;
            font-weight: 400;
            cursor: pointer;
        }

        /* Responsive */
        @media (max-width: 480px) {
            .login-container {
                padding: 25px 28px;
            }
            
            .login-header h1 {
                font-size: 20px;
            }
            
            .logo {
                font-size: 40px;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <div class="logo">
                <img src="Logo RSKHS.jpg" alt="Logo RSKHS" style="width: 80px; height: 80px; object-fit: contain;">
            </div>
            <h1>Information Technology</h1>
            <p>Monitoring Pemeriksaan Radiologi</p>
        </div>
        
        <?php if (!empty($error)): ?>
            <div class="error-message">
                ⚠️ <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label>Username</label>
                <div class="input-wrapper">
                    <span class="input-icon">👤</span>
                    <input type="text" name="username" placeholder="Masukkan username" required autofocus>
                </div>
            </div>
            
            <div class="form-group">
                <label>Password</label>
                <div class="input-wrapper">
                    <span class="input-icon">🔒</span>
                    <input type="password" name="password" placeholder="Masukkan password" required>
                </div>
            </div>
            
            <div class="remember-me">
                <input type="checkbox" id="remember" name="remember">
                <label for="remember">Ingat saya</label>
            </div>
            
            <button type="submit" name="login" class="btn-login">
                🚀 Login Sekarang
            </button>
        </form>
        
        <div class="demo-info">
            <h3>🔐 Akun Demo:</h3>
            <div class="user-demo">
                <span>Username: <strong>admin</strong></span>
                <span>Password: <strong>admin123</strong></span>
            </div>
            <div class="user-demo">
                <span>Username: <strong>radiologi</strong></span>
                <span>Password: <strong>radio123</strong></span>
            </div>
            <div class="user-demo">
                <span>Username: <strong>petugas</strong></span>
                <span>Password: <strong>petugas123</strong></span>
            </div>
        </div>
        
        <div class="footer-text">
        <p>© 2026 <strong>Wahyu Ardiansyah</strong>. All Rights Reserved.</p>
        </div>
    </div>
</body>
</html>
