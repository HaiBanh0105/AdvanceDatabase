<?php
require_once('DAO.php');
require_once('../config/mongodb.php');

const VALID_BOOKING_STATUSES = ['pending', 'confirmed', 'checked_in', 'completed', 'cancelled'];

function get_pricing_config()
{
    $config_file = __DIR__ . '/../config/pricing.json';
    if (!file_exists($config_file)) {
        $default = [
            'weekend_multiplier' => 1.2,
            'holiday_multiplier' => 1.5,
            'holidays' => ['01-01', '30-04', '01-05', '02-09', '31-12'],
            'deposit_percent' => 30
        ];
        file_put_contents($config_file, json_encode($default, JSON_PRETTY_PRINT));
        return $default;
    }
    return json_decode(file_get_contents($config_file), true);
}

function save_pricing_config($data)
{
    $config_file = __DIR__ . '/../config/pricing.json';
    file_put_contents($config_file, json_encode($data, JSON_PRETTY_PRINT));
}

function booking_find_available_room($type_id, $check_in, $check_out)
{
    $sql = "
        SELECT TOP 1 r.room_id, rt.price_per_day, rt.price_per_hour
        FROM Room r WITH (UPDLOCK, ROWLOCK, READPAST)
        JOIN Room_types rt ON r.type_id = rt.type_id
        WHERE r.type_id = ? AND r.status = 'available'
        AND r.room_id NOT IN (
            SELECT bd.room_id
            FROM Booking_detail bd
            JOIN Booking b ON bd.booking_id = b.booking_id
            WHERE b.booking_status NOT IN ('cancelled', 'completed')
            AND (b.check_in_planned < ? AND b.check_out_planned > ?)
        )
    ";
    return db_query_one($sql, $type_id, $check_out, $check_in);
}

function booking_create_customer($customer_id, $check_in, $check_out, $total_price, $room_id, $price, $rental_type = 'daily', $extra_guests = [])
{
    try {
        $json_guests = empty($extra_guests) ? null : json_encode($extra_guests, JSON_UNESCAPED_UNICODE);
        $sql = "EXEC sp_CreateCustomerBooking @customer_id=?, @check_in=?, @check_out=?, @total_price=?, @room_id=?, @price_at_booking=?, @extra_guests_json=?";
        $result = db_query_one($sql, $customer_id, $check_in, $check_out, $total_price, $room_id, $price, $json_guests);
        return $result ? $result['new_booking_id'] : false;
    } catch (Exception $e) {
        error_log('booking_create_customer error: ' . $e->getMessage());
        throw $e;
    }
}

function booking_create_admin_walkin($employee_id, $room_id, $check_in, $check_out, $base_price, $unit_price, $guests, $rep_index, $guest_phone)
{
    try {
        $rep_cccd = trim($guests[$rep_index]['cccd'] ?? '');
        if ($rep_cccd === '') {
            throw new Exception("Thiếu thông tin CCCD của người đại diện!");
        }

        $json_guests = json_encode($guests, JSON_UNESCAPED_UNICODE);
        $sql = "EXEC sp_CreateWalkinBooking @room_id=?, @check_in=?, @check_out=?, @base_price=?, @unit_price=?, @guest_phone=?, @guests_json=?, @rep_index=?";
        db_execute($sql, $room_id, $check_in, $check_out, $base_price, $unit_price, $guest_phone, $json_guests, $rep_index);

        return true;
    } catch (Exception $e) {
        // Bắt lỗi 50001 (ROOM_OCCUPIED) do SQL Server THROW ra
        if (strpos($e->getMessage(), 'ROOM_OCCUPIED') !== false) {
            return 'ROOM_OCCUPIED';
        }
        error_log('booking_create_admin_walkin error: ' . $e->getMessage());
        return false;
    }
}

function booking_update_status($booking_id, $status)
{
    if (!in_array($status, VALID_BOOKING_STATUSES, true)) {
        error_log("booking_update_status: trạng thái không hợp lệ '$status' cho booking #$booking_id");
        return false;
    }
    // Chuyển checked_in thành checked-in để tương thích Schema
    $db_status = str_replace('_', '-', $status);
    db_execute("UPDATE Booking SET booking_status = ? WHERE booking_id = ?", $db_status, $booking_id);

    return true;
}

function booking_update_status_transaction($booking_id, $status, $deduct_amount = 0)
{
    $db_status = str_replace('_', '-', $status);
    try {
        db_execute("EXEC sp_UpdateBookingStatus @booking_id = ?, @new_status = ?, @deduct_amount = ?", $booking_id, $db_status, $deduct_amount);
        return true;
    } catch (Exception $e) {
        return false;
    }
}

function booking_cancel_transaction($booking_id, $refund_amount, $new_total_price)
{
    try {
        db_execute("EXEC sp_CancelBooking @booking_id = ?, @refund_amount = ?, @new_total_price = ?", $booking_id, $refund_amount, $new_total_price);
        return true;
    } catch (Exception $e) {
        return false;
    }
}

function booking_get_all_admin($search = '')
{
    if ($search !== '') {
        $term = "%{$search}%";
        $sql = "SELECT b.booking_id, bd.detail_id, b.check_in_planned as check_in, b.check_out_planned as check_out, b.booking_status as status, b.total_price, c.full_name as guest_name, ISNULL(c.phone, '') as guest_phone, ISNULL(c.cccd, '') as guest_cccd, r.room_number, rt.name as type_name
                FROM Booking b
                JOIN Customer c ON b.customer_id = c.customer_id
                JOIN Booking_detail bd ON b.booking_id = bd.booking_id
                JOIN Room r ON bd.room_id = r.room_id
                JOIN Room_types rt ON r.type_id = rt.type_id
                WHERE CAST(b.booking_id AS NVARCHAR) LIKE ? OR c.full_name LIKE ? OR c.cccd LIKE ? OR r.room_number LIKE ?
                OR EXISTS (
                    SELECT 1 FROM Booking_guests bg2 
                    JOIN Customer c3 ON bg2.customer_id = c3.customer_id 
                    WHERE bg2.detail_id = bd.detail_id AND (c3.full_name LIKE ? OR c3.cccd LIKE ?)
                )
                ORDER BY b.booking_id DESC";
        $bookings = db_query($sql, $term, $term, $term, $term, $term, $term);
    } else {
        $sql = "SELECT b.booking_id, bd.detail_id, b.check_in_planned as check_in, b.check_out_planned as check_out, b.booking_status as status, b.total_price, c.full_name as guest_name, ISNULL(c.phone, '') as guest_phone, ISNULL(c.cccd, '') as guest_cccd, r.room_number, rt.name as type_name
                FROM Booking b
                JOIN Customer c ON b.customer_id = c.customer_id
                JOIN Booking_detail bd ON b.booking_id = bd.booking_id
                JOIN Room r ON bd.room_id = r.room_id
                JOIN Room_types rt ON r.type_id = rt.type_id
                ORDER BY b.booking_id DESC";
        $bookings = db_query($sql);
    }

    if ($bookings) {
        $detail_ids = array_column($bookings, 'detail_id');
        if (!empty($detail_ids)) {
            $placeholders = implode(',', array_fill(0, count($detail_ids), '?'));
            $sql_guests = "SELECT bg.detail_id, c.full_name, c.cccd FROM Booking_guests bg JOIN Customer c ON bg.customer_id = c.customer_id WHERE bg.is_representative = 0 AND bg.detail_id IN ($placeholders)";
            $extra_guests_raw = db_query($sql_guests, ...$detail_ids);

            $guests_map = [];
            if ($extra_guests_raw) {
                foreach ($extra_guests_raw as $eg) {
                    $guests_map[$eg['detail_id']][] = $eg['full_name'] . ' (' . $eg['cccd'] . ')';
                }
            }

            foreach ($bookings as &$b) {
                $b['extra_guests_info'] = isset($guests_map[$b['detail_id']]) ? implode('<br>', $guests_map[$b['detail_id']]) : '';
            }
        }
    }

    return $bookings;
}

function booking_get_active()
{
    $sql = "SELECT bd.room_id, b.booking_id, b.check_in_planned as check_in, b.check_out_planned as check_out, 
                   c.full_name as customer_name, b.booking_status as status
            FROM Booking b
            JOIN Booking_detail bd ON b.booking_id = bd.booking_id
            JOIN Customer c ON b.customer_id = c.customer_id
            WHERE b.booking_status = 'checked-in'";
    return db_query($sql);
}

function booking_get_guest_by_cccd($cccd)
{
    $sql2 = "SELECT TOP 1 * FROM Customer WHERE cccd = ? ORDER BY customer_id DESC";
    $guest = db_query_one($sql2, $cccd);

    return $guest ?: false;
}

function booking_get_details_for_checkout($booking_id)
{
    $sql = "SELECT b.booking_id, b.check_in_planned as check_in, b.check_out_planned as check_out, b.booking_status as status, b.payment_status, b.total_price, bd.price_at_booking, bd.actual_check_out, r.room_number, rt.price_per_hour, rt.price_per_day
            FROM Booking b
            JOIN Booking_detail bd ON b.booking_id = bd.booking_id
            JOIN Room r ON bd.room_id = r.room_id
            JOIN Room_types rt ON r.type_id = rt.type_id
            WHERE b.booking_id = ?";
    $booking = db_query_one($sql, $booking_id);
    if (!$booking) return false;

    $sql_guests = "SELECT c.full_name, ISNULL(c.cccd, '') as cccd, ISNULL(c.phone, '') as phone, bg.is_representative 
                   FROM Booking_guests bg JOIN Booking_detail d ON bg.detail_id = d.detail_id JOIN Customer c ON bg.customer_id = c.customer_id WHERE d.booking_id = ?";
    $booking['guests'] = db_query($sql_guests, $booking_id);

    // Tìm và gán thông tin người đại diện vào cấp cao nhất của mảng để JS dễ truy cập
    $booking['guest_name'] = 'Khách vãng lai'; // Giá trị mặc định
    $booking['guest_cccd'] = ''; // Giá trị mặc định
    if (!empty($booking['guests'])) {
        foreach ($booking['guests'] as $guest) {
            if ($guest['is_representative']) {
                $booking['guest_name'] = $guest['full_name'];
                $booking['guest_cccd'] = $guest['cccd'];
                break;
            }
        }
    }
    return $booking;
}

function booking_process_checkout($booking_id, $actual_checkout)
{
    // Mọi logic tính tiền quá hạn và cập nhật hóa đơn đã được chuyển xuống Database gánh vác
    $sql = "EXEC sp_ProcessCheckout @booking_id = ?, @actual_checkout = ?";
    $result = db_query_one($sql, $booking_id, $actual_checkout);

    return $result ? $result['final_total'] : false;
}

function booking_is_room_available($room_id, $check_in, $check_out)
{
    $sql = "SELECT COUNT(*) as cnt
            FROM Booking_detail bd
            JOIN Booking b ON bd.booking_id = b.booking_id
            WHERE bd.room_id = ?
              AND b.booking_status NOT IN ('cancelled', 'completed')
              AND (b.check_in_planned < ? AND b.check_out_planned > ?)";
    $result = db_query_one($sql, $room_id, $check_out, $check_in);
    return ($result && $result['cnt'] == 0);
}

function booking_check_phone_exists($phone)
{
    $sql2 = "SELECT COUNT(*) FROM Customer WHERE phone = ?";
    if (db_query_value($sql2, $phone) > 0) return true;
    return false;
}

// --- MONGODB: QUẢN LÝ TIỀN ĐẶT CỌC CỦA ĐƠN HÀNG ---
function booking_save_meta($booking_id, $deposit_amount)
{
    $db = mongo_get_db();
    $db->booking_meta->insertOne([
        'booking_id' => (int)$booking_id,
        'deposit_amount' => (float)$deposit_amount,
        'is_deducted' => false
    ]);
}

function booking_get_meta($booking_id)
{
    $db = mongo_get_db();
    return $db->booking_meta->findOne(['booking_id' => (int)$booking_id]);
}

function booking_mark_deducted($booking_id)
{
    $db = mongo_get_db();
    $db->booking_meta->updateOne(['booking_id' => (int)$booking_id], ['$set' => ['is_deducted' => true]]);
}
