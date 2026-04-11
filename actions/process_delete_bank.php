<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../frontend/login.php");
    exit();
}
require_once '../dao/bank_dao.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_id = $_SESSION['user_id'];
    bank_delete_by_user($user_id);
    
    header("Location: ../frontend/customer_profile.php?update=bank_deleted");
    exit();
}
?>