<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../frontend/login.php");
    exit();
}
require_once '../dao/DAO.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_id = $_SESSION['user_id'];
    db_execute("DELETE FROM Bank_account WHERE user_id = ?", $user_id);
    
    header("Location: ../frontend/customer_profile.php?update=bank_deleted");
    exit();
}
?>