<?php
// Gọi đúng tên file cấu hình mongodb.php
require_once '../config/mongodb.php';

/**
 * Thêm mã khuyến mãi mới vào MongoDB
 */
function promotion_insert($code, $discount, $description, $quantity, $expires_mongo) {
    $db = mongo_get_db();
    $collection = $db->promotions;
    
    return $collection->insertOne([
        'code' => $code,
        'discount_percent' => $discount,
        'description' => $description,
        'status' => 'active',
        'quantity' => $quantity,
        'created_at' => new MongoDB\BSON\UTCDateTime(),
        'expires_at' => $expires_mongo
    ]);
}

/**
 * Cập nhật mã khuyến mãi
 */
function promotion_update($id, $code, $discount, $description, $quantity, $expires_mongo) {
    $db = mongo_get_db();
    $collection = $db->promotions;
    
    return $collection->updateOne(
        ['_id' => new MongoDB\BSON\ObjectId($id)],
        ['$set' => [
            'code' => $code,
            'discount_percent' => $discount,
            'description' => $description,
            'quantity' => $quantity,
            'expires_at' => $expires_mongo
        ]]
    );
}

/**
 * Xóa mã khuyến mãi
 */
function promotion_delete($id) {
    $db = mongo_get_db();
    $collection = $db->promotions;
    
    return $collection->deleteOne(['_id' => new MongoDB\BSON\ObjectId($id)]);
}
?>