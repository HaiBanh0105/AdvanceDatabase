<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../frontend/login.php");
    exit();
}
require_once '../dao/DAO.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $customer_id = $_POST['user_id'] ?? '';
    $amount = (float)($_POST['amount'] ?? 0);
    
    if ($customer_id && $amount > 0) {
        db_execute("UPDATE User_detail SET balance = balance + ? WHERE user_id = ?", $amount, $customer_id);
    }
    header("Location: ../frontend/admin_customers.php?msg=deposited");
    exit();
}
?>