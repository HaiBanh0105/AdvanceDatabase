<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../frontend/login.php");
    exit();
}
require_once '../dao/booking_dao.php';
require_once '../dao/promotion_dao.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    $action = $_GET['action'] ?? '';
    if ($action === 'check_cccd') {
        $cccd = trim($_GET['cccd'] ?? '');
        $guest = booking_get_guest_by_cccd($cccd);
        if ($guest) {
            echo json_encode([
                'status' => 'found',
                'name' => $guest['full_name']
            ]);
        } else {
            echo json_encode(['status' => 'not_found']);
        }
        exit();
    } elseif ($action === 'get_details') {
        $booking_id = (int)$_GET['booking_id'];
        $user_id = $_SESSION['user_id'];

        $customer_id = db_query_value("SELECT customer_id FROM Account WHERE account_id = ?", $user_id);
        $check_owner = db_query_value("SELECT COUNT(*) FROM Booking WHERE booking_id = ? AND customer_id = ?", $booking_id, $customer_id);

        if ($check_owner > 0) {
            $booking = booking_get_details_for_checkout($booking_id);
            if ($booking) {
                echo json_encode($booking);
                exit();
            }
        }

        echo json_encode(['error' => 'Not found or Unauthorized']);
        exit();
    } elseif ($action === 'check_promo') {
        $code = trim($_GET['code'] ?? '');
        $promo = promotion_get_valid_by_code($code);

        if ($promo) {
            echo json_encode(['status' => 'success', 'discount' => $promo['discount_percent']]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Mã không hợp lệ, đã hết hạn hoặc hết lượt sử dụng!']);
        }
        exit();
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'cancel_booking') {
        $booking_id = (int)$_POST['booking_id'];
        $user_id  = $_SESSION['user_id'];
        $customer_id = db_query_value("SELECT customer_id FROM Account WHERE account_id = ?", $user_id);

        $booking = db_query_one("SELECT booking_status FROM Booking WHERE booking_id = ? AND customer_id = ?", $booking_id, $customer_id);
        if ($booking && in_array(strtolower($booking['booking_status']), ['pending', 'confirmed'])) {
            if (booking_update_status($booking_id, 'cancelled')) {
                echo json_encode(['status' => 'success', 'message' => 'Hủy đặt phòng thành công!']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Lỗi hệ thống khi cập nhật trạng thái!']);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Đơn đặt phòng này không thể hủy!']);
        }
        exit();
    }

    $user_id  = $_SESSION['user_id'];

    // Kiểm tra tài khoản đã được phê duyệt (active) chưa trước khi cho phép đặt phòng
    $user_status = db_query_value("SELECT status FROM Account WHERE account_id = ?", $user_id);
    $customer_id = db_query_value("SELECT customer_id FROM Account WHERE account_id = ?", $user_id);

    if ($user_status !== 'active') {
        echo json_encode(['status' => 'warning', 'message' => 'Tài khoản chưa được phê duyệt. Vui lòng cập nhật hồ sơ và chờ Admin duyệt!']);
        exit();
    }

    $type_id  = (int)$_POST['type_id'];

    // Thiết lập giờ mặc định của khách sạn (Check-in: 14h, Check-out: 12h)
    $check_in  = date('Y-m-d 14:00:00', strtotime($_POST['check_in']));
    $check_out = date('Y-m-d 12:00:00', strtotime($_POST['check_out']));

    $extra_guests = $_POST['guests'] ?? [];
    $promo_code = trim($_POST['promo_code'] ?? '');

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

        // Xử lý áp dụng mã giảm giá (Nếu có)
        $applied_promo_id = null;
        if ($promo_code !== '') {
            $promo = promotion_get_valid_by_code($promo_code);
            if ($promo) {
                $discount_amount = $total_price * ($promo['discount_percent'] / 100);
                $total_price = max(0, $total_price - $discount_amount);
                $applied_promo_id = (string)$promo['_id'];
            } else {
                throw new Exception("Mã khuyến mãi không hợp lệ hoặc đã hết lượt tại thời điểm thanh toán!");
            }
        }

        $booking_id = booking_create_customer(
            $customer_id,
            $check_in,
            $check_out,
            $total_price,
            $available_room['room_id'],
            $available_room['price_per_day'],
            'daily',
            $extra_guests
        );

        if (!$booking_id) {
            throw new Exception("Không thể lưu đơn đặt phòng vào cơ sở dữ liệu.");
        }

        $conn->commit();

        // Trừ số lượng mã khuyến mãi bên MongoDB sau khi giao dịch SQL thành công
        if ($applied_promo_id) {
            promotion_decrement_quantity($applied_promo_id);
        }
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
