<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../frontend/login.php");
    exit();
}
require_once '../dao/DAO.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_id = $_SESSION['user_id'];
    $type_id = (int)$_POST['type_id'];
    $check_in = $_POST['check_in'];
    $check_out = $_POST['check_out'];

    // Xác thực logic thời gian cơ bản
    if (strtotime($check_in) >= strtotime($check_out)) {
        header("Location: ../frontend/customer_index.php?error=invalid_dates#rooms");
        exit();
    }

    // TÌM PHÒNG TRỐNG: Lấy 1 room_id thuộc type_id, có status 'active', và KHÔNG trùng lặp thời gian trong Booking_detail & Booking (Ngoại trừ đơn đã hủy/hoàn thành)
    $find_room_sql = "
        SELECT r.room_id, rt.price 
        FROM Room r 
        JOIN Room_types rt ON r.type_id = rt.type_id
        WHERE r.type_id = ? AND r.status = 'active'
        AND r.room_id NOT IN (
            SELECT bd.room_id 
            FROM Booking_detail bd 
            JOIN Booking b ON bd.booking_id = b.booking_id
            WHERE b.status NOT IN ('cancelled', 'completed')
            AND (b.check_in < ? AND b.check_out > ?)
        )
        LIMIT 1
    ";
    
    $available_room = db_query_one($find_room_sql, $type_id, $check_out, $check_in);

    if (!$available_room) {
        header("Location: ../frontend/customer_index.php?error=no_room_available#rooms");
        exit();
    }

    // Tính tổng tiền = Giá 1 đêm * Số đêm (Làm tròn lên)
    $nights = max(1, ceil((strtotime($check_out) - strtotime($check_in)) / 86400));
    $total_price = $available_room['price'] * $nights;

    // Ghi vào Database bằng transaction ngầm (thông qua DAO nếu có thể, hoặc tuần tự)
    db_execute("INSERT INTO Booking (user_id, check_in, check_out, total_price, payment_method, status) VALUES (?, ?, ?, ?, 'Pay at Hotel', 'pending')", $user_id, $check_in, $check_out, $total_price);
    $booking_id = db_query_value("SELECT booking_id FROM Booking WHERE user_id = ? ORDER BY booking_id DESC LIMIT 1", $user_id);
    db_execute("INSERT INTO Booking_detail (booking_id, room_id, price_at_booking, sub_total, status) VALUES (?, ?, ?, ?, 'pending')", $booking_id, $available_room['room_id'], $available_room['price'], $total_price);

    header("Location: ../frontend/customer_index.php?msg=booking_success");
    exit();
}
?>