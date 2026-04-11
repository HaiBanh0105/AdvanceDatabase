<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../frontend/login.php");
    exit();
}

require_once '../dao/user_dao.php';

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

    user_update_profile($user_id, $phone, $full_name, $dob, $id_number, $nation, $address);

    $_SESSION['full_name'] = $full_name;

    header("Location: ../frontend/customer_profile.php?update=success");
    exit();
}
?>