<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../frontend/login.php");
    exit();
}
require_once '../config/hotel_config.php';
require_once '../dao/booking_dao.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_status') {
        $booking_id = (int)$_POST['booking_id'];
        $status     = trim($_POST['status']);

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

        $ok = booking_update_status($booking_id, $status);
        if ($ok) {
            if ($status === 'checked_in') {
                db_execute("UPDATE Booking_detail SET actual_check_in = ? WHERE booking_id = ?", date('Y-m-d H:i:s'), $booking_id);
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
            $booking['final_total']     = (float)($booking['total_price'] ?? 0) + $overtime_fee;

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
                    $booking['final_total'] = (float)$booking['total_price'] + $overtime_fee;
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
                    $booking['final_total'] = (float)$booking['total_price'];
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