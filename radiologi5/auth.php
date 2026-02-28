<?php
// File untuk mengecek apakah user sudah login atau belum
// Include file ini di setiap halaman yang memerlukan login

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Cek apakah user sudah login
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    // Jika belum login, redirect ke halaman login
    header('Location: login.php');
    exit;
}

// Cek session timeout (optional, 1 jam)
$timeout_duration = 3600; // 1 jam dalam detik

if (isset($_SESSION['login_time'])) {
    $elapsed_time = time() - $_SESSION['login_time'];
    
    if ($elapsed_time > $timeout_duration) {
        // Session timeout
        session_unset();
        session_destroy();
        header('Location: login.php?timeout=1');
        exit;
    }
}

// Update login time
$_SESSION['login_time'] = time();

// Fungsi untuk mendapatkan username yang sedang login
function getLoggedInUser() {
    return $_SESSION['username'] ?? 'Guest';
}

// Fungsi untuk cek role (jika ingin implementasi role-based access)
function checkRole($required_role) {
    $user_role = $_SESSION['role'] ?? 'user';
    return $user_role === $required_role;
}
?>
