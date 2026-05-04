<?php
require_once '../config/mongo_db.php';

/**
 * 1. AGGREGATION PIPELINE PHỨC TẠP
 * Pipeline này xử lý nghiệp vụ: 
 * - Lọc khách sạn theo danh sách tiện ích (amenities) mong muốn.
 * - Tính toán điểm số (matched_count: số lượng tiện ích trùng khớp).
 * - Sắp xếp khách sạn nào có nhiều tiện ích khớp nhất lên đầu.
 * - Lọc mảng hình ảnh để chỉ trả về ảnh bìa (is_cover = true) nhằm tối ưu băng thông API.
 */
function mongo_search_hotels_by_amenities($search_amenities) {
    $db = mongo_get_connection();
    $collection = $db->HotelCatalog;
    
    $pipeline = [
        [
            // Bước 1 ($match): Chỉ lấy những khách sạn có ít nhất 1 tiện ích nằm trong mảng tìm kiếm
            '$match' => [
                'amenities' => ['$in' => $search_amenities]
            ]
        ],
        [
            // Bước 2 ($addFields): Đếm số lượng tiện ích trùng khớp sử dụng Set Intersection
            '$addFields' => [
                'matched_count' => [
                    '$size' => [
                        '$setIntersection' => ['$amenities', $search_amenities]
                    ]
                ]
            ]
        ],
        [
            // Bước 3 ($sort): Ưu tiên hiển thị KS khớp nhiều tiện ích nhất
            '$sort' => ['matched_count' => -1]
        ],
        [
            // Bước 4 ($project): Chỉ trả về các trường cần thiết và filter array images
            '$project' => [
                'hotel_id' => 1,
                'name' => 1,
                'description' => 1,
                'amenities' => 1,
                'matched_count' => 1,
                'contact' => 1,
                'cover_image' => [
                    '$filter' => [
                        'input' => '$images',
                        'as' => 'img',
                        'cond' => ['$eq' => ['$$img.is_cover', true]]
                    ]
                ]
            ]
        ]
    ];

    // Chạy aggregation và trả về mảng kết quả
    return $collection->aggregate($pipeline)->toArray();
}
?>