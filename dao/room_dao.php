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
function room_details_upsert($type_id, $amenities, $base64 = null, $mime_type = null)
{
    $db = mongo_get_db();
    $update_data = [
        'type_id' => (int)$type_id,
        'amenities' => is_array($amenities) ? $amenities : []
    ];
    if ($base64 !== null) {
        $update_data['image_base64'] = $base64;
        $update_data['mime_type'] = $mime_type;
    }
    return $db->room_details->updateOne(
        ['type_id' => (int)$type_id], 
        ['$set' => $update_data], 
        ['upsert' => true]
    );
}

function room_details_delete($type_id)
{
    $db = mongo_get_db();
    return $db->room_details->deleteOne(['type_id' => (int)$type_id]);
}

function room_details_get_all()
{
    $db = mongo_get_db();
    $cursor = $db->room_details->find([]);
    $mongo_details = [];
    foreach ($cursor as $doc) {
        $mongo_details[$doc['type_id']] = [
            'amenities' => isset($doc['amenities']) ? iterator_to_array($doc['amenities']) : [],
            'base64' => $doc['image_base64'] ?? '',
            'mime' => $doc['mime_type'] ?? 'image/jpeg'
        ];
    }
    return $mongo_details;
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

// --- QUẢN LÝ TIỆN ÍCH (MONGODB) ---
function amenity_get_all()
{
    $db = mongo_get_db();
    $count = $db->amenities->countDocuments([]);
    // if ($count === 0) {
    //     $defaults = ['Wifi', 'Pool', 'Mini Bar', 'Ocean View', 'Balcony', 'Bathtub'];
    //     foreach ($defaults as $amn) {
    //         $db->amenities->insertOne(['name' => $amn]);
    //     }
    // }
    $cursor = $db->amenities->find([], ['sort' => ['name' => 1]]);
    $amenities = [];
    foreach ($cursor as $doc) {
        $amenities[] = $doc['name'];
    }
    return $amenities;
}

function amenity_add($name)
{
    $db = mongo_get_db();
    $name = trim($name);
    if (!empty($name)) {
        $existing = $db->amenities->findOne(['name' => new MongoDB\BSON\Regex('^' . preg_quote($name) . '$', 'i')]);
        if (!$existing) {
            $db->amenities->insertOne(['name' => $name]);
            return true;
        }
    }
    return false;
}

function amenity_delete($name)
{
    $db = mongo_get_db();
    $db->amenities->deleteOne(['name' => $name]);
    // Đồng thời gỡ bỏ tiện ích này khỏi tất cả các Hạng phòng đang chứa nó
    $db->room_details->updateMany([], ['$pull' => ['amenities' => $name]]);
    return true;
}