<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../frontend/login.php");
    exit();
}
// Gọi đến file bank_dao.php thay vì gọi thẳng DAO.php lõi
require_once '../dao/bank_dao.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_id = $_SESSION['user_id'];
    
    $provider = trim($_POST['provider'] ?? '');
    $cardholder_name = trim(strtoupper($_POST['cardholder_name'] ?? ''));
    $card_id = trim($_POST['card_id'] ?? ''); // 16 số
    $expiry_month = trim($_POST['expiry_date'] ?? ''); // Định dạng YYYY-MM
    $cvv = trim($_POST['cvv'] ?? '');

    // Chuyển YYYY-MM thành YYYY-MM-DD để lưu vào CSDL (Ngày cuối cùng của tháng)
    $expiry_date = date("Y-m-t", strtotime($expiry_month . "-01"));

    try {
        // Kiểm tra xem thẻ này đã bị ai khác dùng chưa
        $existing = bank_check_card_exists($card_id);
        
        if ($existing && $existing['user_id'] != $user_id) {
            header("Location: ../frontend/customer_profile.php?error=bank_duplicate");
            exit();
        }

        // Xóa thẻ cũ của user (nếu có) trước khi liên kết thẻ mới
        bank_delete_by_user($user_id);

        // Lưu thẻ mới thông qua lớp DAO
        bank_insert_account($card_id, $user_id, $provider, $cardholder_name, $cvv, $expiry_date);

    header("Location: ../frontend/customer_profile.php?update=bank_success");
    } catch (Exception $e) {
        header("Location: ../frontend/customer_profile.php?error=bank_duplicate");
    }
    exit();
}
?>