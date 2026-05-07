<?php
// Gọi đúng tên file cấu hình mongodb.php
require_once '../config/mongodb.php';

/**
 * Thêm mã khuyến mãi mới vào MongoDB
 */
function promotion_insert($code, $discount, $description, $quantity, $duration_day)
{
    $db = mongo_get_db();
    $collection = $db->promotions;

    return $collection->insertOne([
        'code' => $code,
        'discount_percent' => $discount,
        'description' => $description,
        'status' => 'active',
        'quantity' => $quantity,
        'created_at' => new MongoDB\BSON\UTCDateTime(),
        'duration_day' => $duration_day
    ]);
}

/**
 * Cập nhật mã khuyến mãi
 */
function promotion_update($id, $code, $discount, $description, $quantity, $duration_day)
{
    $db = mongo_get_db();
    $collection = $db->promotions;

    return $collection->updateOne(
        ['_id' => new MongoDB\BSON\ObjectId($id)],
        ['$set' => [
            'code' => $code,
            'discount_percent' => $discount,
            'description' => $description,
            'quantity' => $quantity,
            'duration_day' => $duration_day
        ]]
    );
}

/**
 * Xóa mã khuyến mãi
 */
function promotion_delete($id)
{
    $db = mongo_get_db();
    $collection = $db->promotions;

    return $collection->deleteOne(['_id' => new MongoDB\BSON\ObjectId($id)]);
}

/**
 * Lấy thông tin mã khuyến mãi hợp lệ (Áp dụng lúc đặt phòng)
 */
function promotion_get_valid_by_code($code)
{
    $db = mongo_get_db();
    $promo = $db->promotions->findOne([
        'code' => strtoupper($code),
        'status' => 'active',
        'quantity' => ['$gt' => 0]
    ]);

    if ($promo) {
        $created_ts = isset($promo['created_at']) ? $promo['created_at']->toDateTime()->getTimestamp() : time();
        $duration_seconds = (isset($promo['duration_day']) ? (int)$promo['duration_day'] : 0) * 86400;

        // So sánh: Nếu [Ngày tạo + Thời gian hiệu lực > Thời điểm hiện tại] -> Mã hợp lệ
        if ($created_ts + $duration_seconds > time()) {
            return $promo;
        }
    }
    return false;
}
/**
 * Trừ đi 1 lượt sử dụng của mã khuyến mãi
 */
function promotion_decrement_quantity($id_string)
{
    $db = mongo_get_db();
    return $db->promotions->updateOne(
        ['_id' => new MongoDB\BSON\ObjectId($id_string)],
        ['$inc' => ['quantity' => -1]]
    );
}