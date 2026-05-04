<?php
require_once '../config/mongo_db.php';

try {
    $db = mongo_get_connection();
    $collection = $db->HotelCatalog;

    // Xóa dữ liệu cũ (nếu có) để làm mới hoàn toàn
    $collection->drop();

    // Nạp dữ liệu mẫu vào MongoDB
    $result = $collection->insertMany([
        [
            "hotel_id" => 1,
            "name" => "Ocean View Resort",
            "description" => "Khu nghỉ dưỡng 5 sao ven biển tuyệt đẹp với góc nhìn ra đại dương bao la.",
            "contact" => [
                "phone" => "+84 123 456 789",
                "email" => "contact@oceanview.com"
            ],
            "amenities" => ["Free WiFi", "Infinity Pool", "Spa & Massage", "Gym"],
            "images" => [
                ["url" => "/img/ocean_1.jpg", "caption" => "Hồ bơi vô cực", "is_cover" => true],
                ["url" => "/img/ocean_2.jpg", "caption" => "Sảnh chờ", "is_cover" => false]
            ],
            "last_updated" => new MongoDB\BSON\UTCDateTime()
        ],
        [
            "hotel_id" => 2,
            "name" => "Mountain Retreat Hotel",
            "description" => "Nghỉ dưỡng trên vùng cao nguyên mát mẻ, hòa mình vào thiên nhiên.",
            "contact" => [
                "phone" => "+84 987 654 321",
                "email" => "booking@mountainretreat.com"
            ],
            "amenities" => ["Free WiFi", "Heated Pool", "BBQ Area"],
            "images" => [
                ["url" => "/img/mountain_1.jpg", "caption" => "Toàn cảnh resort", "is_cover" => true]
            ],
            "last_updated" => new MongoDB\BSON\UTCDateTime()
        ]
    ]);

    echo "<h3>Thành công! Đã nạp " . $result->getInsertedCount() . " khách sạn vào MongoDB.</h3>";
    echo "<p>Bây giờ bạn có thể quay lại test thử API nhé.</p>";
} catch (Exception $e) {
    echo "Lỗi: " . $e->getMessage();
}
?>