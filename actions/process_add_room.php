<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../frontend/login.php");
    exit();
}

require_once '../dao/room_dao.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? 'add';

    if ($action === 'add') {
        $room_number = trim($_POST['room_number'] ?? '');
        $type_id = (int)($_POST['type_id'] ?? 0);
        $status = trim($_POST['status'] ?? 'active');

        if (!empty($room_number) && $type_id > 0) {
            if (room_check_number_exists($room_number)) {
                header("Location: ../frontend/admin_rooms.php?error=duplicate_room&tab=rooms");
                exit();
            }
            room_insert($type_id, $room_number, $status);
        }
        header("Location: ../frontend/admin_rooms.php?msg=added&tab=rooms");
    } elseif ($action === 'edit') {
        $room_id = (int)($_POST['room_id'] ?? 0);
        $room_number = trim($_POST['room_number'] ?? '');
        $type_id = (int)($_POST['type_id'] ?? 0);
        $status = trim($_POST['status'] ?? 'active');

        if ($room_id > 0 && !empty($room_number)) {
            if (room_check_number_exists($room_number, $room_id)) {
                header("Location: ../frontend/admin_rooms.php?error=duplicate_room&tab=rooms");
                exit();
            }
            room_update($room_id, $type_id, $room_number, $status);
        }
        header("Location: ../frontend/admin_rooms.php?msg=updated&tab=rooms");
    } elseif ($action === 'delete') {
        $room_id = (int)($_POST['room_id'] ?? 0);
        if (room_check_has_bookings($room_id)) {
            header("Location: ../frontend/admin_rooms.php?error=room_has_bookings&tab=rooms");
            exit();
        }
        room_delete($room_id);
        header("Location: ../frontend/admin_rooms.php?msg=deleted&tab=rooms");
    }
}
