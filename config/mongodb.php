<?php
// Import tự động các thư viện tải từ Composer
require_once __DIR__ . '/../vendor/autoload.php';

/**
 * Lấy đối tượng Client kết nối đến MongoDB Server
 */
function mongo_get_client() {
    static $client = null;
    if ($client === null) {
        try {
            // Kết nối đến MongoDB Server đang chạy ngầm trên máy (Localhost)
            $client = new MongoDB\Client("mongodb://localhost:27017");
        } catch (Exception $e) {
            error_log("MongoDB Connection Error: " . $e->getMessage());
            throw new Exception("Không thể kết nối đến MongoDB Server.");
        }
    }
    return $client;
}

/**
 * Lấy Database MongoDB cụ thể
 * @param string $db_name Tên Database (Ví dụ: grand_horizon_mongo)
 */
function mongo_get_db($db_name = 'grand_horizon_mongo') {
    $client = mongo_get_client();
    return $client->$db_name;
}
?>