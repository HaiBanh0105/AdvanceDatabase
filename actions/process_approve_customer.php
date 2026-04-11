<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../frontend/login.php");
    exit();
}
require_once '../dao/user_dao.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $customer_id = $_POST['user_id'] ?? '';
    if ($customer_id) {
        user_approve_customer($customer_id);
    }
    header("Location: ../frontend/admin_customers.php?msg=approved");
    exit();
}
?>