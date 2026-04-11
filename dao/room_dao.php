<?php
require_once('DAO.php');
require_once('../config/mongodb.php');

// --- QUẢN LÝ PHÒNG ---
function room_insert($type_id, $room_number, $status)
{
    return db_execute("INSERT INTO Room (type_id, room_number, status) VALUES (?, ?, ?)", $type_id, $room_number, $status);
}

function room_update($room_id, $type_id, $room_number, $status)
{
    return db_execute("UPDATE Room SET type_id = ?, room_number = ?, status = ? WHERE room_id = ?", $type_id, $room_number, $status, $room_id);
}

function room_delete($room_id)
{
    return db_execute("DELETE FROM Room WHERE room_id = ?", $room_id);
}

function room_check_has_bookings($room_id)
{
    return db_query_value("SELECT COUNT(*) FROM Booking_detail WHERE room_id = ?", $room_id) > 0;
}

function room_get_all_details()
{
    return db_query("SELECT r.*, rt.name as type_name FROM Room r LEFT JOIN Room_types rt ON r.type_id = rt.type_id ORDER BY r.room_id DESC");
}

function room_get_all_with_types()
{
    return db_query("SELECT r.room_id, r.room_number, r.status as room_status, rt.type_id, rt.name as type_name, rt.price_per_hour, rt.price_per_day
                     FROM Room r JOIN Room_types rt ON r.type_id = rt.type_id
                     ORDER BY rt.price_per_day ASC, r.room_number ASC");
}

// --- QUẢN LÝ HẠNG PHÒNG ---
function room_type_insert($name, $price_per_hour, $price_per_day, $capacity, $description)
{
    db_execute("INSERT INTO Room_types (name, price_per_hour, price_per_day, capacity, description) VALUES (?, ?, ?, ?, ?)", $name, $price_per_hour, $price_per_day, $capacity, $description);
    // Lấy ID tự động cho SQL Server (Thay cho LIMIT 1)
    return db_query_value("SELECT TOP 1 type_id FROM Room_types ORDER BY type_id DESC");
}

function room_type_update($type_id, $name, $price_per_hour, $price_per_day, $capacity, $description)
{
    return db_execute("UPDATE Room_types SET name = ?, price_per_hour = ?, price_per_day = ?, capacity = ?, description = ? WHERE type_id = ?", $name, $price_per_hour, $price_per_day, $capacity, $description, $type_id);
}

function room_type_delete($type_id)
{
    return db_execute("DELETE FROM Room_types WHERE type_id = ?", $type_id);
}

function room_type_check_has_bookings($type_id)
{
    return db_query_value("SELECT COUNT(*) FROM Booking_detail bd JOIN Room r ON bd.room_id = r.room_id WHERE r.type_id = ?", $type_id) > 0;
}

function room_type_get_all($order_by = 'type_id DESC')
{
    $order_clause = ($order_by === 'price ASC') ? 'price_per_day ASC' : 'type_id DESC';
    return db_query("SELECT * FROM Room_types ORDER BY " . $order_clause);
}

// --- QUẢN LÝ ẢNH (MONGODB) ---
function room_image_insert($type_id, $base64, $mime_type)
{
    $db = mongo_get_db();
    return $db->room_images->insertOne(['type_id' => (int)$type_id, 'image_base64' => $base64, 'mime_type' => $mime_type]);
}

function room_image_update($type_id, $base64, $mime_type)
{
    $db = mongo_get_db();
    return $db->room_images->updateOne(['type_id' => (int)$type_id], ['$set' => ['image_base64' => $base64, 'mime_type' => $mime_type]], ['upsert' => true]);
}

function room_image_delete($type_id)
{
    $db = mongo_get_db();
    return $db->room_images->deleteOne(['type_id' => (int)$type_id]);
}

function room_image_get_all()
{
    $db = mongo_get_db();
    $images_cursor = $db->room_images->find([]);
    $mongo_images = [];
    foreach ($images_cursor as $img) {
        $mongo_images[$img['type_id']] = [
            'base64' => $img['image_base64'],
            'mime' => $img['mime_type'] ?? 'image/jpeg'
        ];
    }
    return $mongo_images;
}

function room_check_number_exists($room_number, $exclude_id = 0)
{
    if ($exclude_id > 0) {
        return db_query_value("SELECT COUNT(*) FROM Room WHERE room_number = ? AND room_id != ?", $room_number, $exclude_id) > 0;
    }
    return db_query_value("SELECT COUNT(*) FROM Room WHERE room_number = ?", $room_number) > 0;
}

function room_type_check_name_exists($name, $exclude_id = 0)
{
    if ($exclude_id > 0) {
        return db_query_value("SELECT COUNT(*) FROM Room_types WHERE name = ? AND type_id != ?", $name, $exclude_id) > 0;
    }
    return db_query_value("SELECT COUNT(*) FROM Room_types WHERE name = ?", $name) > 0;
}
