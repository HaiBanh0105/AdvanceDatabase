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

        $ok = booking_update_status($booking_id, $status);
        if ($ok) {
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
            $base_price    = $duration * $room_info['price_per_day'];
            $unit_price    = $room_info['price_per_day'];
        }

        if (!booking_is_room_available($room_id, $check_in, $check_out)) {
            header("Location: ../frontend/admin_bookings.php?error=room_occupied");
            exit();
        }

        if ($guest_phone !== '') {
            if (booking_check_phone_exists($guest_phone)) {
                header("Location: ../frontend/admin_bookings.php?error=duplicate_phone");
                exit();
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

            $existing_name = booking_get_guest_by_cccd($cccd);
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
        if ($ok) {
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

                if (($booking['rental_type'] ?? '') === 'daily' && $overtime_hours >= HOTEL_MAX_OVERTIME_HOURS) {
                    $overtime_fee = (float)($booking['price_per_day'] ?? 0);
                } else {
                    $overtime_fee = $overtime_hours * (float)($booking['price_per_hour'] ?? 0);
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
        $name = booking_get_guest_by_cccd($cccd);
        ob_end_clean();
        header('Content-Type: application/json');
        if ($name) {
            echo json_encode(['status' => 'found', 'name' => $name]);
        } else {
            echo json_encode(['status' => 'not_found']);
        }
        exit();
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
                        if (($booking['rental_type'] ?? '') === 'daily' && $overtime_hours >= HOTEL_MAX_OVERTIME_HOURS) {
                            $overtime_fee = (float)$booking['price_per_day'];
                        } else {
                            $overtime_fee = $overtime_hours * (float)$booking['price_per_hour'];
                        }
                    }
                    $booking['overtime_fee'] = $overtime_fee;
                    $booking['final_total'] = (float)$booking['total_price'] + $overtime_fee;
                    $booking['is_estimated'] = true;
                } else {
                    $booking['overtime_fee'] = 0;
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
