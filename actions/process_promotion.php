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
        $duration_day = (int)($_POST['duration_day'] ?? 0);

        if (!empty($code) && $discount > 0 && $duration_day > 0) {
            // Lưu duration_day thay vì expires_at
            promotion_insert($code, $discount, $description, $quantity, $duration_day);
        }
    } elseif ($action === 'edit') {
        $id = $_POST['id'] ?? '';
        $code = strtoupper(trim($_POST['code']));
        $discount = (int)$_POST['discount'];
        $description = trim($_POST['description']);
        $quantity = (int)($_POST['quantity'] ?? 0);
        $duration_day = (int)($_POST['duration_day'] ?? 0);

        if (!empty($id) && !empty($code) && $discount > 0 && $duration_day > 0) {
            // Lưu duration_day
            promotion_update($id, $code, $discount, $description, $quantity, $duration_day);
        }
    } elseif ($action === 'delete') {
        $id = $_POST['id'] ?? '';
        if (!empty($id)) {
            promotion_delete($id);
        }
    }

    header("Location: ../frontend/admin_promotions.php?msg=success");
    exit();
}
