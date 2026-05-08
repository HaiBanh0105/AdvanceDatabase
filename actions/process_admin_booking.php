<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../frontend/login.php");
    exit();
}
require_once '../config/hotel_config.php';
require_once '../dao/booking_dao.php';
require_once '../config/mail_config.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require_once '../PHPMailer/Exception.php';
require_once '../PHPMailer/PHPMailer.php';
require_once '../PHPMailer/SMTP.php';

function send_booking_email($booking_id, $type)
{
    $booking = booking_get_details_for_checkout($booking_id);
    if (!$booking) return;

    // Kiểm tra xem khách hàng này có tài khoản (người dùng online) không
    $customer_id = db_query_value("SELECT customer_id FROM Booking WHERE booking_id = ?", $booking_id);
    $account_email = db_query_value("SELECT email FROM Account WHERE customer_id = ?", $customer_id);

    if (!$account_email) return; // Nếu không có Account => Khách Walk-in => Không gửi mail

    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = MAIL_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = MAIL_USERNAME;
        $mail->Password   = MAIL_PASSWORD;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = MAIL_PORT;
        $mail->CharSet    = 'UTF-8';

        $mail->setFrom(MAIL_USERNAME, MAIL_FROM_NAME);
        $mail->addAddress($account_email);
        $mail->isHTML(true);

        $customer_name = $booking['guest_name'] ?? 'Khách hàng';
        $room_number = $booking['room_number'] ?? 'Đang cập nhật';
        $check_in = date('d/m/Y H:i', strtotime($booking['check_in']));
        $check_out = date('d/m/Y H:i', strtotime($booking['check_out']));

        if ($type === 'approved') {
            $mail->Subject = 'Xác nhận đặt phòng thành công - Grand Horizon';
            $total_price = number_format($booking['total_price'], 0, ',', '.');

            $meta = booking_get_meta($booking_id);
            $deposit = ($meta && !empty($meta['deposit_amount'])) ? number_format($meta['deposit_amount'], 0, ',', '.') : '0';

            $mail->Body = "
                <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; border: 1px solid #e5e7eb; border-radius: 12px; overflow: hidden;'>
                    <div style='background-color: #4F46E5; color: white; padding: 20px; text-align: center;'>
                        <h2 style='margin: 0;'>Xác nhận Đặt phòng</h2>
                    </div>
                    <div style='padding: 20px; color: #374151; line-height: 1.6;'>
                        <p>Xin chào <b>$customer_name</b>,</p>
                        <p>Đơn đặt phòng của bạn đã được hệ thống duyệt thành công!</p>
                        <h3 style='color: #4F46E5; border-bottom: 1px solid #e5e7eb; padding-bottom: 10px;'>Chi tiết đơn:</h3>
                        <ul style='list-style-type: none; padding: 0;'>
                            <li style='margin-bottom: 8px;'><b>🏨 Phòng:</b> $room_number</li>
                            <li style='margin-bottom: 8px;'><b>📥 Nhận phòng:</b> $check_in</li>
                            <li style='margin-bottom: 8px;'><b>📤 Trả phòng (dự kiến):</b> $check_out</li>
                            <li style='margin-bottom: 8px;'><b>💰 Tổng tiền:</b> <span style='color: #E11D48; font-weight: bold;'>$total_price đ</span></li>
                            <li style='margin-bottom: 8px;'><b>💳 Tiền cọc đã thanh toán:</b> $deposit đ</li>
                        </ul>
                        <p>Cảm ơn bạn đã lựa chọn Grand Horizon. Chúc bạn một kỳ nghỉ tuyệt vời!</p>
                    </div>
                </div>
            ";
        } elseif ($type === 'checkout') {
            $mail->Subject = 'Hóa đơn thanh toán & Cảm ơn - Grand Horizon';
            $total_price = number_format($booking['total_price'], 0, ',', '.');

            $actual_out = isset($booking['actual_check_out']) && $booking['actual_check_out'] ? date('d/m/Y H:i', strtotime($booking['actual_check_out'])) : date('d/m/Y H:i');

            $mail->Body = "
                <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; border: 1px solid #e5e7eb; border-radius: 12px; overflow: hidden;'>
                    <div style='background-color: #10B981; color: white; padding: 20px; text-align: center;'>
                        <h2 style='margin: 0;'>Cảm ơn quý khách!</h2>
                    </div>
                    <div style='padding: 20px; color: #374151; line-height: 1.6;'>
                        <p>Xin chào <b>$customer_name</b>,</p>
                        <p>Quá trình trả phòng của bạn đã hoàn tất. Cảm ơn bạn đã tin tưởng và sử dụng dịch vụ tại Grand Horizon.</p>
                        <h3 style='color: #10B981; border-bottom: 1px solid #e5e7eb; padding-bottom: 10px;'>Chi tiết hóa đơn:</h3>
                        <ul style='list-style-type: none; padding: 0;'>
                            <li style='margin-bottom: 8px;'><b>🏨 Phòng:</b> $room_number</li>
                            <li style='margin-bottom: 8px;'><b>📥 Nhận phòng:</b> $check_in</li>
                            <li style='margin-bottom: 8px;'><b>📤 Trả phòng:</b> $actual_out</li>
                            <li style='margin-bottom: 8px;'><b>💰 Tổng thanh toán:</b> <span style='color: #E11D48; font-weight: bold;'>$total_price đ</span></li>
                        </ul>
                        <p>Hẹn gặp lại bạn trong thời gian sớm nhất!</p>
                    </div>
                </div>
            ";
        }

        $mail->send();
    } catch (Exception $e) {
        error_log('Mail Error: ' . $mail->ErrorInfo);
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_status') {
        $booking_id = (int)$_POST['booking_id'];
        $status     = trim($_POST['status']);

        $current_b = db_query_one("SELECT booking_status, customer_id FROM Booking WHERE booking_id = ?", $booking_id);

        if ($status === 'checked_in') {
            $sql_check = "SELECT b.check_in_planned, r.status as room_status 
                          FROM Booking b 
                          JOIN Booking_detail bd ON b.booking_id = bd.booking_id 
                          JOIN Room r ON bd.room_id = r.room_id 
                          WHERE b.booking_id = ?";
            $check_info = db_query_one($sql_check, $booking_id);

            if ($check_info) {
                if ($check_info['room_status'] !== 'available') {
                    header("Location: ../frontend/admin_bookings.php?error=room_not_ready");
                    exit();
                }

                $earliest_checkin = strtotime('-2 hours', strtotime($check_info['check_in_planned']));
                if (time() < $earliest_checkin) {
                    header("Location: ../frontend/admin_bookings.php?error=too_early");
                    exit();
                }
            }
        }

        if ($status === 'cancelled') {
            $refund_amount = 0;
            $new_total_price = 0;
            if ($current_b && in_array(strtolower($current_b['booking_status']), ['confirmed', 'checked_in', 'checked-in'])) {
                $meta = booking_get_meta($booking_id);
                if ($meta && !empty($meta['is_deducted'])) {
                    $refund_amount = $meta['deposit_amount']; // Admin chủ động hủy -> Hoàn lại 100% cọc
                }
            }
            $ok = booking_cancel_transaction($booking_id, $refund_amount, $new_total_price);
        } else {
            $deduct_amount = 0;
            if (in_array($status, ['confirmed', 'checked_in', 'checked-in']) && $current_b) {
                $meta = booking_get_meta($booking_id);
                if ($meta && empty($meta['is_deducted'])) {
                    $deduct_amount = $meta['deposit_amount'];
                    booking_mark_deducted($booking_id);
                }
            }
            $ok = booking_update_status_transaction($booking_id, $status, $deduct_amount);
        }

        if ($ok) {
            if (in_array($status, ['confirmed', 'checked_in', 'checked-in']) && strtolower($current_b['booking_status']) === 'pending') {
                send_booking_email($booking_id, 'approved');
            }
            header("Location: ../frontend/admin_bookings.php?msg=status_updated");
        } else {
            header("Location: ../frontend/admin_bookings.php?error=invalid_status");
        }
        exit();
    } elseif ($action === 'create_walkin') {
        $room_id     = (int)$_POST['room_id'];
        $rental_type = $_POST['rental_type'];
        $duration    = (int)$_POST['duration'];
        $guest_phone = trim($_POST['guest_phone'] ?? '');
        $check_in    = date('Y-m-d H:i:s');

        $guests    = $_POST['guests'] ?? [];
        $rep_index = (int)$_POST['rep_index'];

        $sql = "SELECT price_per_hour, price_per_day FROM Room_types rt JOIN Room r ON rt.type_id = r.type_id WHERE r.room_id = ?";
        $room_info = db_query_one($sql, $room_id);

        if (!$room_info) {
            header("Location: ../frontend/admin_bookings.php?error=room_not_found");
            exit();
        }

        if ($rental_type === 'hourly') {
            $check_out  = date('Y-m-d H:i:s', strtotime("+$duration hours", strtotime($check_in)));
            $base_price = $duration * $room_info['price_per_hour'];
            $unit_price = $room_info['price_per_hour'];
        } else {
            $checkout_date = date('Y-m-d', strtotime("+$duration days", strtotime($check_in)));
            $check_out     = date('Y-m-d H:i:s', strtotime($checkout_date . ' ' . sprintf('%02d:00:00', HOTEL_STANDARD_CHECKOUT_HOUR)));

            $base_price = 0;
            $current_date = strtotime(date('Y-m-d', strtotime($check_in)));

            $pricing_config = get_pricing_config();
            $holidays = $pricing_config['holidays'];

            for ($i = 0; $i < $duration; $i++) {
                $day_of_week = date('N', $current_date);
                $date_md = date('d-m', $current_date);
                if (in_array($date_md, $holidays)) {
                    $base_price += $room_info['price_per_day'] * $pricing_config['holiday_multiplier'];
                } elseif ($day_of_week == 6 || $day_of_week == 7) {
                    $base_price += $room_info['price_per_day'] * $pricing_config['weekend_multiplier'];
                } else {
                    $base_price += $room_info['price_per_day'];
                }
                $current_date = strtotime('+1 day', $current_date);
            }
            $unit_price    = $room_info['price_per_day'];
        }

        $rep_cccd = trim($guests[$rep_index]['cccd'] ?? '');
        $existing_rep = booking_get_guest_by_cccd($rep_cccd);
        $confirm_phone = (int)($_POST['confirm_phone'] ?? 0);

        if ($guest_phone !== '') {
            // 1. Kiểm tra xem số điện thoại này đã bị khách hàng KHÁC sử dụng chưa
            $exclude_id = $existing_rep ? $existing_rep['customer_id'] : 0;

            if ($exclude_id > 0) {
                $is_duplicate = db_query_value("SELECT COUNT(*) FROM Customer WHERE phone = ? AND customer_id != ?", $guest_phone, $exclude_id) > 0;
            } else {
                $is_duplicate = db_query_value("SELECT COUNT(*) FROM Customer WHERE phone = ?", $guest_phone) > 0;
            }

            if ($is_duplicate) {
                header("Location: ../frontend/admin_bookings.php?error=duplicate_phone");
                exit();
            }

            // 2. Kiểm tra nếu khách CŨ đổi sang số MỚI (chưa có xác nhận từ Modal)
            if ($existing_rep) {
                $old_phone = trim($existing_rep['phone'] ?? '');
                if ($old_phone !== '' && $old_phone !== $guest_phone && $confirm_phone === 0) {
                    header("Location: ../frontend/admin_bookings.php?error=phone_confirm_required");
                    exit();
                }
            }
        }

        // KIỂM TRA CHẶT CHẼ DỮ LIỆU KHÁCH TỪ SERVER
        $cccd_list = [];
        foreach ($guests as $i => $g) {
            $name = trim($g['name'] ?? '');
            $cccd = trim($g['cccd'] ?? '');

            // Bỏ qua nếu dòng này hoàn toàn rỗng (JS gửi dư)
            if ($name === '' && $cccd === '') continue;

            // Nếu có nhập Tên nhưng thiếu CCCD (hoặc ngược lại) -> Báo lỗi
            if ($name === '' || $cccd === '') {
                header("Location: ../frontend/admin_bookings.php?error=missing_cccd");
                exit();
            }

            if (in_array($cccd, $cccd_list)) {
                header("Location: ../frontend/admin_bookings.php?error=duplicate_cccd");
                exit();
            }
            $cccd_list[] = $cccd;

            $existing_guest = booking_get_guest_by_cccd($cccd);
            $existing_name = $existing_guest ? $existing_guest['full_name'] : false;
            if ($existing_name && mb_strtolower($existing_name, 'UTF-8') !== mb_strtolower($name, 'UTF-8')) {
                header("Location: ../frontend/admin_bookings.php?error=cccd_name_mismatch");
                exit();
            }
        }

        if (empty($cccd_list)) {
            header("Location: ../frontend/admin_bookings.php?error=missing_guest");
            exit();
        }

        $ok = booking_create_admin_walkin($_SESSION['user_id'], $room_id, $check_in, $check_out, $base_price, $unit_price, $guests, $rep_index, $guest_phone);

        if ($ok === 'ROOM_OCCUPIED') {
            header("Location: ../frontend/admin_bookings.php?error=room_occupied");
            exit();
        } elseif ($ok) {
            header("Location: ../frontend/admin_bookings.php?msg=booking_created");
        } else {
            header("Location: ../frontend/admin_bookings.php?error=booking_failed");
        }
        exit();
    } elseif ($action === 'checkout') {
        $booking_id     = (int)$_POST['booking_id'];
        $actual_checkout = date('Y-m-d H:i:s');

        $final_total = booking_process_checkout($booking_id, $actual_checkout);

        if ($final_total === false) {
            header("Location: ../frontend/admin_bookings.php?error=checkout_failed");
            exit();
        }

        send_booking_email($booking_id, 'checkout');
        header("Location: ../frontend/admin_bookings.php?msg=checkout_success");
        exit();
    } elseif ($action === 'mark_room_ready') {
        $room_id = (int)$_POST['room_id'];
        db_execute("UPDATE Room SET status = 'available' WHERE room_id = ?", $room_id);
        header("Location: ../frontend/admin_bookings.php?msg=room_ready");
        exit();
    }
} elseif ($_SERVER['REQUEST_METHOD'] == 'GET') {
    $action = $_GET['action'] ?? '';

    if ($action === 'calculate_bill') {
        // Bắt đầu đệm output để chặn mọi cảnh báo lỗi PHP (nếu có) in ra màn hình
        ob_start();

        try {
            $booking_id = (int)$_GET['booking_id'];
            $booking    = booking_get_details_for_checkout($booking_id);

            if (!$booking) {
                ob_end_clean();
                header('Content-Type: application/json');
                echo json_encode(['error' => 'Not found']);
                exit();
            }

            $now      = time();
            $exp_out  = strtotime($booking['check_out'] ?? date('Y-m-d H:i:s'));
            $overtime_hours = 0;
            $overtime_fee   = 0;

            if ($now > $exp_out) {
                $diff           = $now - $exp_out;
                $overtime_hours = ceil($diff / 3600);

                $overtime_fee = $overtime_hours * (float)($booking['price_per_hour'] ?? 0);
                if ($overtime_fee > (float)($booking['price_per_day'] ?? 0)) {
                    $overtime_fee = (float)($booking['price_per_day'] ?? 0);
                }
            }

            $booking['actual_checkout'] = date('Y-m-d H:i:s', $now);
            $booking['overtime_hours']  = $overtime_hours;
            $booking['overtime_fee']    = $overtime_fee;

            $meta = booking_get_meta($booking_id);
            $deposit = ($meta && !empty($meta['is_deducted'])) ? (float)$meta['deposit_amount'] : 0;
            $booking['deposit_amount']  = $deposit;

            $booking['base_price']      = (float)($booking['total_price'] ?? 0);
            $booking['final_total']     = $booking['base_price'] + $overtime_fee - $deposit;

            // Xóa mọi output rác, chỉ giữ lại đúng nội dung JSON
            ob_end_clean();
            header('Content-Type: application/json');
            echo json_encode($booking);
            exit();
        } catch (\Throwable $th) {
            $err = ob_get_clean(); // Lấy các thông báo lỗi nếu PHP lỡ in ra
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Server Exception', 'message' => $th->getMessage(), 'php_error' => $err]);
            exit();
        }
    } elseif ($action === 'check_cccd') {
        ob_start();
        $cccd = trim($_GET['cccd'] ?? '');
        $guest = booking_get_guest_by_cccd($cccd);
        ob_end_clean();
        header('Content-Type: application/json');
        if ($guest) {
            echo json_encode([
                'status' => 'found',
                'name' => $guest['full_name'],
                'phone' => $guest['phone'],
                'email' => $guest['email'],
                'address' => $guest['address'],
                'nation' => $guest['nation']
            ]);
        } else {
            echo json_encode(['status' => 'not_found']);
        }
        exit();
    } elseif ($action === 'check_phone') {
        ob_start();
        $phone = trim($_GET['phone'] ?? '');
        $cccd = trim($_GET['cccd'] ?? '');

        $guest_by_phone = db_query_one("SELECT customer_id, full_name, cccd FROM Customer WHERE phone = ?", $phone);

        ob_end_clean();
        header('Content-Type: application/json');
        if ($guest_by_phone) {
            if ($cccd !== '' && $guest_by_phone['cccd'] === $cccd) {
                echo json_encode(['status' => 'ok']);
            } else {
                echo json_encode([
                    'status' => 'duplicate',
                    'message' => 'Số điện thoại này đã được đăng ký bởi khách hàng khác.'
                ]);
            }
        } else {
            echo json_encode(['status' => 'ok']);
        }
        exit();
    } elseif ($action === 'get_room_timeline') {
        ob_start();
        try {
            $room_id = (int)$_GET['room_id'];
            $sql = "SELECT b.check_in_planned as check_in, b.check_out_planned as check_out, b.booking_status as status, c.full_name 
                    FROM Booking_detail bd 
                    JOIN Booking b ON bd.booking_id = b.booking_id 
                    LEFT JOIN Customer c ON b.customer_id = c.customer_id
                    WHERE bd.room_id = ? AND b.booking_status NOT IN ('cancelled', 'completed') 
                    ORDER BY b.check_in_planned ASC";
            $timeline = db_query($sql, $room_id);
            ob_end_clean();
            header('Content-Type: application/json');
            echo json_encode($timeline ?: []);
            exit();
        } catch (\Throwable $th) {
            ob_end_clean();
            echo json_encode(['error' => 'Server Exception']);
            exit();
        }
    } elseif ($action === 'get_invoice') {
        ob_start();
        try {
            $booking_id = (int)$_GET['booking_id'];
            $booking    = booking_get_details_for_checkout($booking_id);

            if ($booking) {
                $meta = booking_get_meta($booking_id);
                $deposit = ($meta && !empty($meta['is_deducted'])) ? (float)$meta['deposit_amount'] : 0;
                $booking['deposit_amount'] = $deposit;

                if (!in_array($booking['status'], ['completed', 'cancelled'])) {
                    $now      = time();
                    $exp_out  = strtotime($booking['check_out']);
                    $overtime_hours = 0;
                    $overtime_fee   = 0;

                    if ($now > $exp_out) {
                        $diff           = $now - $exp_out;
                        $overtime_hours = ceil($diff / 3600);
                        $overtime_fee = $overtime_hours * (float)$booking['price_per_hour'];
                        if ($overtime_fee > (float)$booking['price_per_day']) {
                            $overtime_fee = (float)$booking['price_per_day'];
                        }
                    }
                    $booking['base_price'] = (float)$booking['total_price'];
                    $booking['overtime_hours'] = $overtime_hours;
                    $booking['overtime_fee'] = $overtime_fee;
                    $booking['amount_to_pay'] = (float)$booking['total_price'] + $overtime_fee - $deposit;
                    $booking['is_estimated'] = true;
                } else {
                    $overtime_fee = 0;
                    $overtime_hours = 0;
                    if ($booking['status'] === 'completed' && !empty($booking['actual_check_out'])) {
                        $exp_out = strtotime($booking['check_out']);
                        $act_out = strtotime($booking['actual_check_out']);
                        if ($act_out > $exp_out) {
                            $overtime_hours = ceil(($act_out - $exp_out) / 3600);
                            $overtime_fee = $overtime_hours * (float)$booking['price_per_hour'];
                            if ($overtime_fee > (float)$booking['price_per_day']) {
                                $overtime_fee = (float)$booking['price_per_day'];
                            }
                        }
                    }
                    $booking['base_price'] = (float)$booking['total_price'] - $overtime_fee;
                    $booking['overtime_hours'] = $overtime_hours;
                    $booking['overtime_fee'] = $overtime_fee;
                    $booking['amount_to_pay'] = (float)$booking['total_price'] - $deposit;
                    $booking['is_estimated'] = false;
                }
            }

            ob_end_clean();
            header('Content-Type: application/json');
            echo json_encode($booking ?: ['error' => 'Not found']);
            exit();
        } catch (\Throwable $th) {
            ob_end_clean();
            echo json_encode(['error' => 'Server Exception']);
            exit();
        }
    }
}
