<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../frontend/login.php");
    exit();
}
require_once '../dao/DAO.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_status') {
        $booking_id = (int)$_POST['booking_id'];
        $status = trim($_POST['status']);
        
        // Đơn không bị xóa, chỉ thay đổi trạng thái
        db_execute("UPDATE Booking SET status = ? WHERE booking_id = ?", $status, $booking_id);
        db_execute("UPDATE Booking_detail SET status = ? WHERE booking_id = ?", $status, $booking_id);
        
        header("Location: ../frontend/admin_bookings.php?msg=status_updated");
    } 
    elseif ($action === 'create_manual') {
        $guest_name = trim($_POST['guest_name']);
        $guest_phone = trim($_POST['guest_phone']);
        $guest_cccd = trim($_POST['guest_cccd']);
        $type_id = (int)$_POST['type_id'];
        $check_in = $_POST['check_in'];
        $check_out = $_POST['check_out'];
        // Luôn mặc định đã check-in khi tạo thủ công
        $status = 'checked_in'; 

        // Tìm phòng trống (Sử dụng lại logic tương tự file process_booking)
        $find_room_sql = "SELECT r.room_id, rt.price FROM Room r JOIN Room_types rt ON r.type_id = rt.type_id WHERE r.type_id = ? AND r.status = 'active' AND r.room_id NOT IN (SELECT bd.room_id FROM Booking_detail bd JOIN Booking b ON bd.booking_id = b.booking_id WHERE b.status NOT IN ('cancelled', 'completed') AND (b.check_in < ? AND b.check_out > ?)) LIMIT 1";
        $available_room = db_query_one($find_room_sql, $type_id, $check_out, $check_in);
        if (!$available_room) {
            header("Location: ../frontend/admin_bookings.php?error=no_room");
            exit();
        }

        $nights = max(1, ceil((strtotime($check_out) - strtotime($check_in)) / 86400));
        $total_price = $available_room['price'] * $nights;

        db_execute("INSERT INTO Booking (guest_name, guest_phone, guest_cccd, check_in, check_out, total_price, payment_method, status) VALUES (?, ?, ?, ?, ?, ?, 'Cash', ?)", $guest_name, $guest_phone, $guest_cccd, $check_in, $check_out, $total_price, $status);
        $booking_id = db_query_value("SELECT booking_id FROM Booking ORDER BY booking_id DESC LIMIT 1");
        db_execute("INSERT INTO Booking_detail (booking_id, room_id, price_at_booking, sub_total, status) VALUES (?, ?, ?, ?, ?)", $booking_id, $available_room['room_id'], $available_room['price'], $total_price, $status);
        header("Location: ../frontend/admin_bookings.php?msg=booking_created");
    }
}
?>
