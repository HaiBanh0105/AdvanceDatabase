<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../frontend/login.php");
    exit();
}
require_once '../dao/booking_dao.php';
require_once '../dao/promotion_dao.php';
require_once '../config/mail_config.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require_once '../PHPMailer/Exception.php';
require_once '../PHPMailer/PHPMailer.php';
require_once '../PHPMailer/SMTP.php';

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
            $current_status = strtolower($booking['booking_status']);

            $refund_amount = 0;
            $new_total_price = 0; // Mặc định hủy đơn chưa duyệt thì không bị phạt (Tổng bill = 0)

            $meta = booking_get_meta($booking_id);
            if ($meta) {
                $penalty_amount = $meta['deposit_amount'] / 2; // Phạt 50% tiền cọc
                if ($current_status === 'confirmed' || $current_status === 'checked-in') {
                    if (!empty($meta['is_deducted'])) {
                        $refund_amount = $meta['deposit_amount'] - $penalty_amount;
                        $new_total_price = $penalty_amount; // Đặt tổng bill thành tiền phạt để làm Doanh thu
                    }
                }
            }

            if (booking_cancel_transaction($booking_id, $refund_amount, $new_total_price)) {
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

        //Uncomment để Debug
        //sleep(5);

        if (!$available_room) {
            $conn->rollBack();
            echo json_encode(['status' => 'error', 'message' => 'Xin lỗi, không còn phòng trống trong khoảng thời gian này!']);
            exit();
        }

        $total_price = 0;
        $current_date = strtotime(date('Y-m-d', strtotime($check_in)));
        $end_date = strtotime(date('Y-m-d', strtotime($check_out)));

        // Lấy cấu hình giá động
        $pricing_config = get_pricing_config();
        $holidays = $pricing_config['holidays'];

        while ($current_date < $end_date) {
            $day_of_week = date('N', $current_date); // 1 = Thứ 2, ..., 7 = Chủ nhật
            $date_md = date('d-m', $current_date);

            if (in_array($date_md, $holidays)) {
                $total_price += $available_room['price_per_day'] * $pricing_config['holiday_multiplier'];
            } elseif ($day_of_week == 6 || $day_of_week == 7) {
                $total_price += $available_room['price_per_day'] * $pricing_config['weekend_multiplier'];
            } else {
                $total_price += $available_room['price_per_day'];
            }
            $current_date = strtotime('+1 day', $current_date);
        }
        if ($total_price == 0) $total_price = $available_room['price_per_day'];

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

        // TÍNH TOÁN VÀ KIỂM TRA SỐ DƯ ĐỂ ĐẶT CỌC
        $deposit_percent = $pricing_config['deposit_percent'] ?? 30;
        $deposit_amount = $total_price * ($deposit_percent / 100);

        $account_balance = db_query_value("SELECT balance FROM Account WHERE account_id = ?", $user_id);
        if ($account_balance < $deposit_amount) {
            throw new Exception("Số dư ví không đủ để thanh toán tiền cọc " . number_format($deposit_amount, 0, ',', '.') . "đ (" . $deposit_percent . "%). Vui lòng nạp thêm tiền vào ví!");
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

        // LƯU LẠI LỊCH SỬ TIỀN CỌC ĐỂ SAU NÀY ADMIN DUYỆT SẼ TRỪ
        booking_save_meta($booking_id, $deposit_amount);

        $conn->commit();

        // Trừ số lượng mã khuyến mãi bên MongoDB sau khi giao dịch SQL thành công
        if ($applied_promo_id) {
            promotion_decrement_quantity($applied_promo_id);
        }

        // Gửi email thông báo đã nhận yêu cầu đặt phòng
        try {
            $customer_email = db_query_value("SELECT email FROM Account WHERE account_id = ?", $user_id);
            $customer_name = $_SESSION['full_name'] ?? 'Khách hàng';
            $room_name = db_query_value("SELECT name FROM Room_types WHERE type_id = ?", $type_id);
            $total_price_formatted = number_format($total_price, 0, ',', '.');
            $deposit_formatted = number_format($deposit_amount, 0, ',', '.');

            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host       = MAIL_HOST;
            $mail->SMTPAuth   = true;
            $mail->Username   = MAIL_USERNAME;
            $mail->Password   = MAIL_PASSWORD;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port       = MAIL_PORT;
            $mail->CharSet    = 'UTF-8';

            $mail->setFrom(MAIL_USERNAME, MAIL_FROM_NAME);
            $mail->addAddress($customer_email);
            $mail->isHTML(true);

            $mail->Subject = 'Yêu cầu đặt phòng đang được xử lý - Grand Horizon';
            $mail->Body = "
                <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; border: 1px solid #e5e7eb; border-radius: 12px; overflow: hidden;'>
                    <div style='background-color: #4F46E5; color: white; padding: 20px; text-align: center;'>
                        <h2 style='margin: 0;'>Yêu cầu đặt phòng đã được ghi nhận</h2>
                    </div>
                    <div style='padding: 20px; color: #374151; line-height: 1.6;'>
                        <p>Xin chào <b>$customer_name</b>,</p>
                        <p>Cảm ơn bạn đã lựa chọn Grand Horizon. Chúng tôi đã nhận được yêu cầu đặt phòng của bạn và đang tiến hành xử lý.</p>
                        <h3 style='color: #4F46E5; border-bottom: 1px solid #e5e7eb; padding-bottom: 10px;'>Chi tiết yêu cầu:</h3>
                        <ul style='list-style-type: none; padding: 0;'>
                            <li style='margin-bottom: 8px;'><b>🏨 Hạng phòng:</b> $room_name</li>
                            <li style='margin-bottom: 8px;'><b>📥 Nhận phòng:</b> " . date('d/m/Y H:i', strtotime($check_in)) . "</li>
                            <li style='margin-bottom: 8px;'><b>📤 Trả phòng:</b> " . date('d/m/Y H:i', strtotime($check_out)) . "</li>
                            <li style='margin-bottom: 8px;'><b>💰 Tổng tiền:</b> <span style='color: #E11D48; font-weight: bold;'>$total_price_formatted đ</span></li>
                            <li style='margin-bottom: 8px;'><b>💳 Số tiền cần cọc:</b> $deposit_formatted đ</li>
                        </ul>
                        <p>Quản trị viên sẽ xem xét yêu cầu và gửi email xác nhận trong thời gian sớm nhất.</p>
                        <p>Trân trọng,<br>Đội ngũ Grand Horizon</p>
                    </div>
                </div>
            ";
            $mail->send();
        } catch (Exception $e) {
            error_log('Booking Request Mail Error: ' . $e->getMessage());
        }
    } catch (Exception $e) {
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        error_log('process_booking error: ' . $e->getMessage());
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        exit();
    }

    echo json_encode(['status' => 'success', 'message' => 'Đặt phòng thành công! Hãy đợi nhân viên xác nhận.']);
    exit();
}
