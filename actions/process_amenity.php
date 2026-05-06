<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../frontend/login.php");
    exit();
}
require_once '../dao/room_dao.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $name = trim($_POST['name'] ?? '');
        if (amenity_add($name)) {
            header("Location: ../frontend/admin_amenities.php?msg=added");
        } else {
            header("Location: ../frontend/admin_amenities.php?error=exists");
        }
        exit();
    } elseif ($action === 'delete') {
        $name = trim($_POST['name'] ?? '');
        amenity_delete($name);
        header("Location: ../frontend/admin_amenities.php?msg=deleted");
        exit();
    }
}
