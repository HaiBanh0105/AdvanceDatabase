<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../frontend/login.php");
    exit();
}

require_once '../dao/DAO.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_id = $_SESSION['user_id'];
    
    $full_name = trim($_POST['full_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $dob = trim($_POST['dob'] ?? '');
    $id_number = trim($_POST['id_number'] ?? '');
    $nation = trim($_POST['nation'] ?? '');
    $address = trim($_POST['address'] ?? '');
    if (!preg_match('/^\d{12}$/', $id_number)) {
        header("Location: ../frontend/customer_profile.php?error=invalid_id");
        exit();
    }
    $sql_user = "UPDATE `User` SET phone = ? WHERE user_id = ?";
    db_execute($sql_user, $phone, $user_id);

    $sql_detail = "UPDATE User_detail SET full_name = ?, dob = ?, ID_number = ?, nation = ?, address = ? WHERE user_id = ?";
    db_execute($sql_detail, $full_name, $dob, $id_number, $nation, $address, $user_id);

    $_SESSION['full_name'] = $full_name;

    header("Location: ../frontend/customer_profile.php?update=success");
    exit();
}
?>