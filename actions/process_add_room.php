<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../frontend/login.php");
    exit();
}

require_once '../dao/DAO.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? 'add';

    if ($action === 'add') {
        $room_number = trim($_POST['room_number'] ?? '');
        $type_id = (int)($_POST['type_id'] ?? 0);
        $status = trim($_POST['status'] ?? 'active');

        if (!empty($room_number) && $type_id > 0) {
            db_execute("INSERT INTO Room (type_id, room_number, status) VALUES (?, ?, ?)", $type_id, $room_number, $status);
        }
        header("Location: ../frontend/admin_rooms.php?msg=added&tab=rooms");
    } 
    elseif ($action === 'edit') {
        $room_id = (int)($_POST['room_id'] ?? 0);
        $room_number = trim($_POST['room_number'] ?? '');
        $type_id = (int)($_POST['type_id'] ?? 0);
        $status = trim($_POST['status'] ?? 'active');

        if ($room_id > 0 && !empty($room_number)) {
            db_execute("UPDATE Room SET type_id = ?, room_number = ?, status = ? WHERE room_id = ?", $type_id, $room_number, $status, $room_id);
        }
        header("Location: ../frontend/admin_rooms.php?msg=updated&tab=rooms");
    } 
    elseif ($action === 'delete') {
        $room_id = (int)($_POST['room_id'] ?? 0);
        // Kiểm tra xem phòng này đã có đơn đặt chưa
        $booking_count = db_query_value("SELECT COUNT(*) FROM Booking_detail WHERE room_id = ?", $room_id);
        if ($booking_count > 0) {
            header("Location: ../frontend/admin_rooms.php?error=room_has_bookings&tab=rooms");
            exit();
        }
        db_execute("DELETE FROM Room WHERE room_id = ?", $room_id);
        header("Location: ../frontend/admin_rooms.php?msg=deleted&tab=rooms");
    }
}
?>