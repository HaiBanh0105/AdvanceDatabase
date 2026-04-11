<?php
require_once('DAO.php');

const VALID_BOOKING_STATUSES = ['pending', 'confirmed', 'checked_in', 'completed', 'cancelled'];

function booking_find_available_room($type_id, $check_in, $check_out)
{
    $sql = "
        SELECT TOP 1 r.room_id, rt.price_per_day, rt.price_per_hour
        FROM Room r WITH (UPDLOCK, ROWLOCK)
        JOIN Room_types rt ON r.type_id = rt.type_id
        WHERE r.type_id = ? AND r.status = 'active'
        AND r.room_id NOT IN (
            SELECT bd.room_id
            FROM Booking_detail bd
            JOIN Booking b ON bd.booking_id = b.booking_id
            WHERE b.status NOT IN ('cancelled', 'completed')
            AND (b.check_in < ? AND b.check_out > ?)
        )
    ";
    return db_query_one($sql, $type_id, $check_out, $check_in);
}

function booking_create_customer($user_id, $check_in, $check_out, $total_price, $room_id, $price, $rental_type = 'daily')
{
    $conn = pdo_get_connection(DB_NAME);
    $is_nested = $conn->inTransaction(); // Kiểm tra xem đã có transaction bên ngoài chưa
    try {
        if (!$is_nested) {
            $conn->beginTransaction();
        }

        $sql1 = "INSERT INTO Booking (rental_type, user_id, check_in, check_out, total_price, payment_method, status)
                 VALUES (?, ?, ?, ?, ?, 'Pay at Hotel', 'pending')";
        $conn->prepare($sql1)->execute([$rental_type, $user_id, $check_in, $check_out, $total_price]);
        $booking_id = $conn->lastInsertId();

        $sql2 = "INSERT INTO Booking_detail (booking_id, room_id, price_at_booking, sub_total, status)
                 VALUES (?, ?, ?, ?, 'pending')";
        $conn->prepare($sql2)->execute([$booking_id, $room_id, $price, $total_price]);
        $detail_id = $conn->lastInsertId();

        // TỰ ĐỘNG ĐẨY THÔNG TIN KHÁCH HÀNG ONLINE VÀO DANH SÁCH KHÁCH LƯU TRÚ (Booking_guests)
        $sql_user = "SELECT ud.full_name, ud.ID_number, u.phone FROM User_detail ud JOIN [User] u ON ud.user_id = u.user_id WHERE ud.user_id = ?";
        $stmt_user = $conn->prepare($sql_user);
        $stmt_user->execute([$user_id]);
        $user_data = $stmt_user->fetch(PDO::FETCH_ASSOC);

        // Khách được duyệt chắc chắn có ID_number
        if ($user_data && !empty($user_data['ID_number'])) {
            $sql3 = "INSERT INTO Booking_guests (booking_id, detail_id, full_name, cccd, phone, is_representative) VALUES (?, ?, ?, ?, ?, 1)";
            $conn->prepare($sql3)->execute([$booking_id, $detail_id, $user_data['full_name'], $user_data['ID_number'], $user_data['phone']]);
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
        if ($is_nested) throw $e; // Ném ngược lỗi ra ngoài để file process_booking xử lý rollback
        return false;
    }
}

function booking_create_admin_walkin($room_id, $rental_type, $check_in, $check_out, $base_price, $unit_price, $guests, $rep_index, $guest_phone)
{
    $conn = pdo_get_connection(DB_NAME);
    try {
        $conn->beginTransaction();

        $rep_name = $guests[$rep_index]['name'] ?? 'Khách vãng lai';
        $rep_cccd = $guests[$rep_index]['cccd'] ?? '';

        $sql1 = "INSERT INTO Booking (rental_type, check_in, check_out, total_price, payment_method, status)
                 VALUES (?, ?, ?, ?, 'Cash', 'checked_in')";
        $conn->prepare($sql1)->execute([$rental_type, $check_in, $check_out, $base_price]);
        $booking_id = $conn->lastInsertId();

        $sql2 = "INSERT INTO Booking_detail (booking_id, room_id, price_at_booking, sub_total, status)
                 VALUES (?, ?, ?, ?, 'checked_in')";
        $conn->prepare($sql2)->execute([$booking_id, $room_id, $unit_price, $base_price]);
        $detail_id = $conn->lastInsertId();

        $sql3 = "INSERT INTO Booking_guests (booking_id, detail_id, full_name, cccd, phone, is_representative)
                 VALUES (?, ?, ?, ?, ?, ?)";
        $stmt3 = $conn->prepare($sql3);
        foreach ($guests as $i => $g) {
            // FIX: Bỏ qua khách có tên rỗng thay vì insert dữ liệu rác
            $name = trim($g['name'] ?? '');
            if ($name === '') continue;
            $is_rep = ($i == $rep_index) ? 1 : 0;
            $g_phone = $is_rep ? $guest_phone : null;
            $stmt3->execute([$booking_id, $detail_id, $name, trim($g['cccd'] ?? ''), $g_phone, $is_rep]);
        }

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
    db_execute("UPDATE Booking SET status = ? WHERE booking_id = ?", $status, $booking_id);
    db_execute("UPDATE Booking_detail SET status = ? WHERE booking_id = ?", $status, $booking_id);
    return true;
}

function booking_get_all_admin($search = '')
{
    if ($search !== '') {
        $term = "%{$search}%";
        $sql = "SELECT b.*, u.email, ud.full_name, u.phone as user_phone, ud.ID_number as user_cccd, bg.full_name as guest_name, bg.cccd as guest_cccd, bg.phone as guest_phone,
                       r.room_number, rt.name as type_name
                FROM Booking b
                LEFT JOIN [User] u ON b.user_id = u.user_id
                LEFT JOIN User_detail ud ON u.user_id = ud.user_id
                LEFT JOIN Booking_guests bg ON b.booking_id = bg.booking_id AND bg.is_representative = 1
                JOIN Booking_detail bd ON b.booking_id = bd.booking_id
                JOIN Room r ON bd.room_id = r.room_id
                JOIN Room_types rt ON r.type_id = rt.type_id
                WHERE CAST(b.booking_id AS NVARCHAR) LIKE ?
                   OR ud.full_name LIKE ?
                   OR bg.full_name LIKE ?
                   OR r.room_number LIKE ?
                ORDER BY b.booking_id DESC";
        return db_query($sql, $term, $term, $term, $term);
    } else {
        $sql = "SELECT b.*, u.email, ud.full_name, u.phone as user_phone, ud.ID_number as user_cccd, bg.full_name as guest_name, bg.cccd as guest_cccd, bg.phone as guest_phone,
                       r.room_number, rt.name as type_name
                FROM Booking b
                LEFT JOIN [User] u ON b.user_id = u.user_id
                LEFT JOIN User_detail ud ON u.user_id = ud.user_id
                LEFT JOIN Booking_guests bg ON b.booking_id = bg.booking_id AND bg.is_representative = 1
                JOIN Booking_detail bd ON b.booking_id = bd.booking_id
                JOIN Room r ON bd.room_id = r.room_id
                JOIN Room_types rt ON r.type_id = rt.type_id
                ORDER BY b.booking_id DESC";
        return db_query($sql);
    }
}

function booking_get_active()
{
    $sql = "SELECT bd.room_id, b.booking_id, b.check_in, b.check_out, 
                   COALESCE(ud.full_name, bg.full_name) as customer_name,
                   b.rental_type, b.status
            FROM Booking b
            JOIN Booking_detail bd ON b.booking_id = bd.booking_id
            LEFT JOIN User_detail ud ON b.user_id = ud.user_id
            LEFT JOIN Booking_guests bg ON b.booking_id = bg.booking_id AND bg.is_representative = 1
            WHERE b.status = 'checked_in'";
    return db_query($sql);
}

function booking_get_guest_by_cccd($cccd)
{
    $sql = "SELECT full_name FROM User_detail WHERE ID_number = ?";
    $user = db_query_one($sql, $cccd);
    if ($user) return $user['full_name'];

    $sql2 = "SELECT TOP 1 full_name FROM Booking_guests WHERE cccd = ? ORDER BY guest_id DESC";
    $guest = db_query_one($sql2, $cccd);
    if ($guest) return $guest['full_name'];

    return false;
}

function booking_get_details_for_checkout($booking_id)
{
    $sql = "SELECT b.*, bd.price_at_booking, r.room_number, rt.price_per_hour, rt.price_per_day
            FROM Booking b
            JOIN Booking_detail bd ON b.booking_id = bd.booking_id
            JOIN Room r ON bd.room_id = r.room_id
            JOIN Room_types rt ON r.type_id = rt.type_id
            WHERE b.booking_id = ?";
    $booking = db_query_one($sql, $booking_id);
    if (!$booking) return false;

    $sql_guests = "SELECT full_name, cccd, phone, is_representative FROM Booking_guests WHERE booking_id = ?";
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
    $exp_out = strtotime($booking['check_out']);
    $overtime_fee = 0;

    if ($now > $exp_out) {
        $overtime_hours = ceil(($now - $exp_out) / 3600);

        if ($booking['rental_type'] === 'daily' && $overtime_hours >= HOTEL_MAX_OVERTIME_HOURS) {
            $overtime_fee = $booking['price_per_day'];
        } else {
            $overtime_fee = $overtime_hours * $booking['price_per_hour'];
        }
    }

    $final_total = $booking['total_price'] + $overtime_fee;

    db_execute(
        "UPDATE Booking SET check_out = ?, total_price = ?, status = 'completed' WHERE booking_id = ?",
        $actual_checkout,
        $final_total,
        $booking_id
    );
    db_execute(
        "UPDATE Booking_detail SET sub_total = ?, status = 'completed' WHERE booking_id = ?",
        $final_total,
        $booking_id
    );

    return $final_total;
}

function booking_is_room_available($room_id, $check_in, $check_out)
{
    $sql = "SELECT COUNT(*) as cnt
            FROM Booking_detail bd
            JOIN Booking b ON bd.booking_id = b.booking_id
            WHERE bd.room_id = ?
              AND b.status NOT IN ('cancelled', 'completed')
              AND (b.check_in < ? AND b.check_out > ?)";
    $result = db_query_one($sql, $room_id, $check_out, $check_in);
    return ($result && $result['cnt'] == 0);
}

function booking_check_phone_exists($phone)
{
    $sql1 = "SELECT COUNT(*) FROM [User] WHERE phone = ?";
    if (db_query_value($sql1, $phone) > 0) return true;

    $sql2 = "SELECT COUNT(*) FROM Booking_guests WHERE phone = ?";
    if (db_query_value($sql2, $phone) > 0) return true;
    return false;
}
