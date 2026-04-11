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
        $name = trim($_POST['name'] ?? '');
        $price_per_hour = (float)($_POST['price_per_hour'] ?? 0);
        $price_per_day = (float)($_POST['price_per_day'] ?? 0);
        $capacity = (int)($_POST['capacity'] ?? 0);
        $description = trim($_POST['description'] ?? '');

        if (!empty($name) && $price_per_day >= 0 && $capacity > 0) {
            if (room_type_check_name_exists($name)) {
                header("Location: ../frontend/admin_rooms.php?error=duplicate_type&tab=types");
                exit();
            }

            $type_id = room_type_insert($name, $price_per_hour, $price_per_day, $capacity, $description);

            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $base64 = base64_encode(file_get_contents($_FILES['image']['tmp_name']));
                room_image_insert($type_id, $base64, $_FILES['image']['type']);
            }
        }
        header("Location: ../frontend/admin_rooms.php?msg=added&tab=types");
    } elseif ($action === 'edit') {
        $type_id = (int)($_POST['type_id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $price_per_hour = (float)($_POST['price_per_hour'] ?? 0);
        $price_per_day = (float)($_POST['price_per_day'] ?? 0);
        $capacity = (int)($_POST['capacity'] ?? 0);
        $description = trim($_POST['description'] ?? '');

        if ($type_id > 0 && !empty($name)) {
            if (room_type_check_name_exists($name, $type_id)) {
                header("Location: ../frontend/admin_rooms.php?error=duplicate_type&tab=types");
                exit();
            }

            room_type_update($type_id, $name, $price_per_hour, $price_per_day, $capacity, $description);

            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $base64 = base64_encode(file_get_contents($_FILES['image']['tmp_name']));
                room_image_update($type_id, $base64, $_FILES['image']['type']);
            }
        }
        header("Location: ../frontend/admin_rooms.php?msg=updated&tab=types");
    } elseif ($action === 'delete') {
        $type_id = (int)($_POST['type_id'] ?? 0);

        if (room_type_check_has_bookings($type_id)) {
            header("Location: ../frontend/admin_rooms.php?error=has_bookings&tab=types");
            exit();
        }

        room_type_delete($type_id);
        room_image_delete($type_id);

        header("Location: ../frontend/admin_rooms.php?msg=deleted&tab=types");
    }
}
