<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Xóa tất cả các biến trong session
$_SESSION = [];

// Ép trình duyệt hủy bỏ Cookie của Session hiện tại
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $params["path"], $params["domain"], $params["secure"], $params["httponly"]);
}

// Hủy hoàn toàn phiên làm việc trên máy chủ
session_destroy();

header("Location: ../frontend/login.php");
exit();