<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../frontend/login.php");
    exit();
}
require_once '../dao/booking_dao.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_id  = $_SESSION['user_id'];

    // Kiểm tra tài khoản đã được phê duyệt (active) chưa trước khi cho phép đặt phòng
    $user_status = db_query_value("SELECT status FROM User_detail WHERE user_id = ?", $user_id);
    if ($user_status !== 'active') {
        echo json_encode(['status' => 'warning', 'message' => 'Tài khoản chưa được phê duyệt. Vui lòng cập nhật hồ sơ và chờ Admin duyệt!']);
        exit();
    }

    $type_id  = (int)$_POST['type_id'];

    // Chuyển đổi định dạng thời gian từ HTML5 (có chữ T) sang chuẩn YYYY-MM-DD HH:MM:SS của SQL Server
    $check_in  = date('Y-m-d H:i:s', strtotime($_POST['check_in']));
    $check_out = date('Y-m-d H:i:s', strtotime($_POST['check_out']));

    // Validate thời gian cơ bản
    if (strtotime($check_in) >= strtotime($check_out)) {
        echo json_encode(['status' => 'error', 'message' => 'Thời gian trả phòng phải lớn hơn thời gian nhận phòng!']);
        exit();
    }

    $conn = pdo_get_connection(DB_NAME);
    try {
        $conn->beginTransaction();

        $available_room = booking_find_available_room($type_id, $check_in, $check_out);

        if (!$available_room) {
            $conn->rollBack();
            echo json_encode(['status' => 'error', 'message' => 'Xin lỗi, không còn phòng trống trong khoảng thời gian này!']);
            exit();
        }

        $nights      = max(1, ceil((strtotime($check_out) - strtotime($check_in)) / 86400));
        $total_price = $available_room['price_per_day'] * $nights;

        $booking_id = booking_create_customer(
            $user_id,
            $check_in,
            $check_out,
            $total_price,
            $available_room['room_id'],
            $available_room['price_per_day'],
            'daily'
        );

        if (!$booking_id) {
            throw new Exception("Không thể lưu đơn đặt phòng vào cơ sở dữ liệu.");
        }

        $conn->commit();
    } catch (Exception $e) {
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        error_log('process_booking error: ' . $e->getMessage());
        echo json_encode(['status' => 'error', 'message' => 'Chi tiết lỗi: ' . $e->getMessage()]);
        exit();
    }

    echo json_encode(['status' => 'success', 'message' => 'Đặt phòng thành công! Hãy đợi nhân viên xác nhận.']);
    exit();
}
