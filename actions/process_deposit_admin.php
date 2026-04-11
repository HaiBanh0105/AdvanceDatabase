<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../frontend/login.php");
    exit();
}
require_once '../dao/user_dao.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $customer_id = $_POST['user_id'] ?? '';
    $amount = (float)($_POST['amount'] ?? 0);
    
    if ($customer_id && $amount > 0) {
        user_deposit_balance($customer_id, $amount);
    }
    header("Location: ../frontend/admin_customers.php?msg=deposited");
    exit();
}
?>