<?php
/**
 * ARIA HOTEL - LOGOUT
 * ============================================
 */

session_start();

// Xóa tất cả session
$_SESSION = array();

// Xóa cookie session
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// Xóa remember token cookie nếu có
if (isset($_COOKIE['remember_token'])) {
    setcookie('remember_token', '', time() - 3600, '/');
}

// Hủy session
session_destroy();

// Chuyển hướng về trang chủ
header('Location: index.php');
exit();