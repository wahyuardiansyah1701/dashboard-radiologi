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
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .login-container {
            background: white;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            width: 100%;
            max-width: 450px;
            animation: slideIn 0.5s ease;
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
            margin-bottom: 35px;
        }
        
        .logo {
            font-size: 60px;
            margin-bottom: 15px;
        }
        
        .login-header h1 {
            color: #667eea;
            font-size: 28px;
            margin-bottom: 10px;
            font-weight: 700;
        }
        
        .login-header p {
            color: #666;
            font-size: 14px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
            font-size: 14px;
        }
        
        .input-wrapper {
            position: relative;
        }
        
        .input-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #999;
            font-size: 18px;
        }
        
        .form-group input {
            width: 100%;
            padding: 12px 15px 12px 45px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 14px;
            transition: all 0.3s;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .btn-login {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 10px;
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4);
        }
        
        .btn-login:active {
            transform: translateY(0);
        }
        
        .error-message {
            background: #fee;
            color: #c33;
            padding: 12px 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
            font-size: 14px;
            border-left: 4px solid #c33;
            animation: shake 0.5s;
        }
        
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-10px); }
            75% { transform: translateX(10px); }
        }
        
        .demo-info {
            margin-top: 25px;
            padding: 20px;
            background: linear-gradient(135deg, #f0f4ff 0%, #f8f0ff 100%);
            border-radius: 10px;
            font-size: 13px;
            color: #555;
            border-left: 4px solid #667eea;
        }
        
        .demo-info h3 {
            color: #667eea;
            margin-bottom: 10px;
            font-size: 14px;
        }
        
        .demo-info .user-demo {
            margin: 8px 0;
            padding: 8px;
            background: white;
            border-radius: 6px;
            display: flex;
            justify-content: space-between;
        }
        
        .demo-info strong {
            color: #667eea;
            font-weight: 600;
        }
        
        .footer-text {
            text-align: center;
            margin-top: 20px;
            color: #999;
            font-size: 12px;
        }
        
        .remember-me {
            display: flex;
            align-items: center;
            margin: 15px 0;
        }
        
        .remember-me input[type="checkbox"] {
            margin-right: 8px;
            width: auto;
        }
        
        .remember-me label {
            font-size: 13px;
            color: #666;
            margin: 0;
            font-weight: 400;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <div class="logo">🏥</div>
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
            <h3>📝 Akun Demo:</h3>
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
            © 2026 Monitoring Radiologi create by: Wahyu Ardiansyah
        </div>
    </div>
</body>
</html>
