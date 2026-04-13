<?php
require_once('DAO.php');

const VALID_BOOKING_STATUSES = ['pending', 'confirmed', 'checked_in', 'completed', 'cancelled'];

function booking_find_available_room($type_id, $check_in, $check_out)
{
    $sql = "
        SELECT TOP 1 r.room_id, rt.price_per_day, rt.price_per_hour
        FROM Room r WITH (UPDLOCK, ROWLOCK)
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
    $conn = pdo_get_connection(DB_NAME);
    $is_nested = $conn->inTransaction();
    try {
        if (!$is_nested) {
            $conn->beginTransaction();
        }

        $sql1 = "INSERT INTO Booking (customer_id, check_in_planned, check_out_planned, total_price, payment_status, booking_status)
                 VALUES (?, ?, ?, ?, 'unpaid', 'pending')";
        $conn->prepare($sql1)->execute([$customer_id, $check_in, $check_out, $total_price]);
        $booking_id = $conn->lastInsertId();

        $sql2 = "INSERT INTO Booking_detail (booking_id, room_id, price_at_booking) VALUES (?, ?, ?)";
        $conn->prepare($sql2)->execute([$booking_id, $room_id, $price]);
        $detail_id = $conn->lastInsertId();

        $sql3 = "INSERT INTO Booking_guests (detail_id, customer_id, is_representative) VALUES (?, ?, 1)";
        $conn->prepare($sql3)->execute([$detail_id, $customer_id]);

        // Thêm thông tin những khách đi cùng (nếu có)
        if (!empty($extra_guests)) {
            $stmt_guest = $conn->prepare("INSERT INTO Booking_guests (detail_id, customer_id, is_representative) VALUES (?, ?, 0)");
            foreach ($extra_guests as $g) {
                $g_name = trim($g['name'] ?? '');
                $g_cccd = trim($g['cccd'] ?? '');
                if ($g_name === '' || $g_cccd === '') continue;

                $g_id = db_query_value("SELECT customer_id FROM Customer WHERE cccd = ?", $g_cccd);
                if (!$g_id) {
                    db_execute("INSERT INTO Customer (full_name, cccd) VALUES (?, ?)", $g_name, $g_cccd);
                    $g_id = db_query_value("SELECT customer_id FROM Customer WHERE cccd = ?", $g_cccd);
                }
                $stmt_guest->execute([$detail_id, $g_id]);
            }
        }

        if (!$is_nested) {
            $conn->commit();
        }
        return $booking_id;
    } catch (Exception $e) {
        if (!$is_nested && $conn->inTransaction()) {
            $conn->rollBack();
        }
        error_log('booking_create_customer error: ' . $e->getMessage());
        if ($is_nested) throw $e;
        return false;
    }
}

function booking_create_admin_walkin($employee_id, $room_id, $check_in, $check_out, $base_price, $unit_price, $guests, $rep_index, $guest_phone)
{
    $conn = pdo_get_connection(DB_NAME);
    try {
        $conn->beginTransaction();

        $rep_name = $guests[$rep_index]['name'] ?? 'Khách vãng lai';
        $rep_cccd = trim($guests[$rep_index]['cccd'] ?? '');

        if ($rep_cccd === '') {
            throw new Exception("Thiếu thông tin CCCD của người đại diện!");
        }

        // Find or create customer
        $customer_id = db_query_value("SELECT customer_id FROM Customer WHERE cccd = ?", $rep_cccd);
        if (!$customer_id) {
            db_execute("INSERT INTO Customer (full_name, cccd, phone) VALUES (?, ?, ?)", $rep_name, $rep_cccd, $guest_phone);
            $customer_id = db_query_value("SELECT customer_id FROM Customer WHERE cccd = ?", $rep_cccd);
        }

        $sql1 = "INSERT INTO Booking (customer_id, check_in_planned, check_out_planned, total_price, payment_status, booking_status)
                 VALUES (?, ?, ?, ?, 'unpaid', 'checked-in')";
        $conn->prepare($sql1)->execute([$customer_id, $check_in, $check_out, $base_price]);
        $booking_id = $conn->lastInsertId();

        $sql2 = "INSERT INTO Booking_detail (booking_id, room_id, price_at_booking, actual_check_in)
                 VALUES (?, ?, ?, ?)";
        $conn->prepare($sql2)->execute([$booking_id, $room_id, $unit_price, $check_in]);
        $detail_id = $conn->lastInsertId();

        $sql3 = "INSERT INTO Booking_guests (detail_id, customer_id, is_representative) VALUES (?, ?, ?)";
        $stmt3 = $conn->prepare($sql3);
        foreach ($guests as $i => $g) {
            $name = trim($g['name'] ?? '');
            $cccd = trim($g['cccd'] ?? '');
            if ($name === '' || $cccd === '') continue;

            $g_id = db_query_value("SELECT customer_id FROM Customer WHERE cccd = ?", $cccd);
            if (!$g_id) {
                db_execute("INSERT INTO Customer (full_name, cccd) VALUES (?, ?)", $name, $cccd);
                $g_id = db_query_value("SELECT customer_id FROM Customer WHERE cccd = ?", $cccd);
            }
            $stmt3->execute([$detail_id, $g_id, ($i == $rep_index) ? 1 : 0]);
        }

        // Tự động chuyển phòng sang trạng thái có khách
        db_execute("UPDATE Room SET status = 'occupied' WHERE room_id = ?", $room_id);

        $conn->commit();
        return true;
    } catch (Exception $e) {
        if ($conn->inTransaction()) {
            $conn->rollBack();
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

    // Cập nhật trạng thái phòng khi Lễ tân duyệt đơn Check-in hoặc Hủy
    $room_id = db_query_value("SELECT room_id FROM Booking_detail WHERE booking_id = ?", $booking_id);
    if ($room_id) {
        if ($db_status === 'checked-in') {
            db_execute("UPDATE Room SET status = 'occupied' WHERE room_id = ?", $room_id);
        } elseif ($db_status === 'cancelled') {
            db_execute("UPDATE Room SET status = 'available' WHERE room_id = ?", $room_id);
        }
    }

    return true;
}

function booking_get_all_admin($search = '')
{
    if ($search !== '') {
        $term = "%{$search}%";
        $sql = "SELECT b.booking_id, bd.detail_id, b.check_in_planned as check_in, b.check_out_planned as check_out, b.booking_status as status, b.total_price, c.full_name as guest_name, c.phone as guest_phone, c.cccd as guest_cccd, r.room_number, rt.name as type_name
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
        $sql = "SELECT b.booking_id, bd.detail_id, b.check_in_planned as check_in, b.check_out_planned as check_out, b.booking_status as status, b.total_price, c.full_name as guest_name, c.phone as guest_phone, c.cccd as guest_cccd, r.room_number, rt.name as type_name
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
    $sql = "SELECT b.booking_id, b.check_in_planned as check_in, b.check_out_planned as check_out, b.booking_status as status, b.total_price, bd.price_at_booking, r.room_number, rt.price_per_hour, rt.price_per_day
            FROM Booking b
            JOIN Booking_detail bd ON b.booking_id = bd.booking_id
            JOIN Room r ON bd.room_id = r.room_id
            JOIN Room_types rt ON r.type_id = rt.type_id
            WHERE b.booking_id = ?";
    $booking = db_query_one($sql, $booking_id);
    if (!$booking) return false;

    $sql_guests = "SELECT c.full_name, c.cccd, c.phone, bg.is_representative 
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
    $booking = booking_get_details_for_checkout($booking_id);
    if (!$booking) return false;

    $now    = strtotime($actual_checkout);
    $exp_out = strtotime($booking['check_out']); // Đã alias từ check_out_planned
    $overtime_fee = 0;

    if ($now > $exp_out) {
        $overtime_hours = ceil(($now - $exp_out) / 3600);

        $overtime_fee = $overtime_hours * $booking['price_per_hour'];
    }

    $final_total = $booking['total_price'] + $overtime_fee;

    db_execute(
        "UPDATE Booking SET booking_status = 'completed', total_price = ?, payment_status = 'paid' WHERE booking_id = ?",
        $final_total,
        $booking_id
    );
    db_execute(
        "UPDATE Booking_detail SET actual_check_out = ? WHERE booking_id = ?",
        $actual_checkout,
        $booking_id
    );

    // Cập nhật trạng thái phòng sang 'cleaning' sau khi khách trả phòng
    $room_id = db_query_value("SELECT room_id FROM Booking_detail WHERE booking_id = ?", $booking_id);
    if ($room_id) {
        db_execute("UPDATE Room SET status = 'cleaning' WHERE room_id = ?", $room_id);
    }

    return $final_total;
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