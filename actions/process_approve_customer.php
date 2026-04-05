<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../frontend/login.php");
    exit();
}
require_once '../dao/DAO.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $customer_id = $_POST['user_id'] ?? '';
    if ($customer_id) {
        db_execute("UPDATE User_detail SET status = 'active' WHERE user_id = ?", $customer_id);
    }
    header("Location: ../frontend/admin_customers.php?msg=approved");
    exit();
}
?>