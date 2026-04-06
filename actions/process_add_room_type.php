<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../frontend/login.php");
    exit();
}

require_once '../dao/DAO.php';
require_once '../config/mongodb.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? 'add';
    $mongo_db = mongo_get_db();
    $images_collection = $mongo_db->room_images;

    if ($action === 'add') {
        $name = trim($_POST['name'] ?? '');
        $price = (float)($_POST['price'] ?? 0);
        $capacity = (int)($_POST['capacity'] ?? 0);
        $description = trim($_POST['description'] ?? '');

        if (!empty($name) && $price >= 0 && $capacity > 0) {
            $sql = "INSERT INTO Room_types (name, price, capacity, description) VALUES (?, ?, ?, ?)";
            db_execute($sql, $name, $price, $capacity, $description);
            
            // Lấy ID vừa được tạo
            $type_id = db_query_value("SELECT type_id FROM Room_types ORDER BY type_id DESC LIMIT 1");

            // Xử lý lưu Hình ảnh vào MongoDB (Base64)
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $base64 = base64_encode(file_get_contents($_FILES['image']['tmp_name']));
                $images_collection->insertOne([
                    'type_id' => (int)$type_id,
                    'image_base64' => $base64,
                    'mime_type' => $_FILES['image']['type']
                ]);
            }
        }
        header("Location: ../frontend/admin_rooms.php?msg=added&tab=types");
    } 
    elseif ($action === 'edit') {
        $type_id = (int)($_POST['type_id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $price = (float)($_POST['price'] ?? 0);
        $capacity = (int)($_POST['capacity'] ?? 0);
        $description = trim($_POST['description'] ?? '');

        if ($type_id > 0 && !empty($name)) {
            $sql = "UPDATE Room_types SET name = ?, price = ?, capacity = ?, description = ? WHERE type_id = ?";
            db_execute($sql, $name, $price, $capacity, $description, $type_id);

            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $base64 = base64_encode(file_get_contents($_FILES['image']['tmp_name']));
                $images_collection->updateOne(['type_id' => $type_id], ['$set' => ['image_base64' => $base64, 'mime_type' => $_FILES['image']['type']]], ['upsert' => true]);
            }
        }
        header("Location: ../frontend/admin_rooms.php?msg=updated&tab=types");
    } 
    elseif ($action === 'delete') {
        $type_id = (int)($_POST['type_id'] ?? 0);
        
        // KIỂM TRA LOGIC: Loại phòng đã có đơn đặt chưa?
        $booking_count = db_query_value("SELECT COUNT(*) FROM Booking_detail bd JOIN Room r ON bd.room_id = r.room_id WHERE r.type_id = ?", $type_id);
        if ($booking_count > 0) {
            header("Location: ../frontend/admin_rooms.php?error=has_bookings&tab=types");
            exit();
        }
        
        db_execute("DELETE FROM Room_types WHERE type_id = ?", $type_id);
        $images_collection->deleteOne(['type_id' => $type_id]); // Xóa luôn ảnh
        
        header("Location: ../frontend/admin_rooms.php?msg=deleted&tab=types");
    }
}
?>