<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../frontend/login.php");
    exit();
}

require_once '../dao/promotion_dao.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $code = strtoupper(trim($_POST['code']));
        $discount = (int)$_POST['discount'];
        $description = trim($_POST['description']);
        $quantity = (int)($_POST['quantity'] ?? 0);
        $expires_at = $_POST['expires_at'] ?? '';
        
        // Kiểm tra hợp lệ: Ngày hết hạn không được bé hơn hiện tại
        if (!empty($expires_at) && strtotime($expires_at) < time()) {
            header("Location: ../frontend/admin_promotions.php?error=invalid_date");
            exit();
        }

        // Chuyển đổi thời gian sang chuẩn của MongoDB
        $expires_mongo = !empty($expires_at) ? new MongoDB\BSON\UTCDateTime(strtotime($expires_at) * 1000) : new MongoDB\BSON\UTCDateTime();

        if (!empty($code) && $discount > 0) {
            promotion_insert($code, $discount, $description, $quantity, $expires_mongo);
        }
    } 
    elseif ($action === 'edit') {
        $id = $_POST['id'] ?? '';
        $code = strtoupper(trim($_POST['code']));
        $discount = (int)$_POST['discount'];
        $description = trim($_POST['description']);
        $quantity = (int)($_POST['quantity'] ?? 0);
        $expires_at = $_POST['expires_at'] ?? '';

        // Kiểm tra hợp lệ: Ngày hết hạn không được bé hơn hiện tại
        if (!empty($expires_at) && strtotime($expires_at) < time()) {
            header("Location: ../frontend/admin_promotions.php?error=invalid_date");
            exit();
        }
        
        $expires_mongo = !empty($expires_at) ? new MongoDB\BSON\UTCDateTime(strtotime($expires_at) * 1000) : new MongoDB\BSON\UTCDateTime();

        if (!empty($id) && !empty($code) && $discount > 0) {
            promotion_update($id, $code, $discount, $description, $quantity, $expires_mongo);
        }
    }
    elseif ($action === 'delete') {
        $id = $_POST['id'] ?? '';
        if (!empty($id)) {
            promotion_delete($id);
        }
    }

    header("Location: ../frontend/admin_promotions.php?msg=success");
    exit();
}
