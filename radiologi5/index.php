<?php
// File index.php sebagai halaman utama
// Redirect ke login jika belum login, atau ke dashboard jika sudah login

session_start();

if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    // Jika sudah login, redirect ke dashboard
    header('Location: dashboard.php');
} else {
    // Jika belum login, redirect ke halaman login
    header('Location: login.php');
}
exit;
?>
