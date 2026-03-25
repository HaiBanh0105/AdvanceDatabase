<?php
session_start();
require_once '../config/pdo.php';
require_once '../dao/user_dao.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    $user = user_check_login($email, $password); // Hàm bạn đã viết trong DAO 

    if ($user) {
        // Lưu thông tin vào Session 
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['balance'] = $user['balance'];

        echo json_encode([
            'status' => 'success',
            'message' => 'Đăng nhập thành công! Đang chuyển hướng...',
            'role' => $user['role']
        ]);
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'Tài khoản hoặc mật khẩu không chính xác!'
        ]);
    }
    exit();
}