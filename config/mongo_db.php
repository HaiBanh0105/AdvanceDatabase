<?php
// Nạp thư viện tự động của Composer
require_once __DIR__ . '/../vendor/autoload.php';

function mongo_get_connection() {
    static $client = null;
    try {
        if ($client === null) {
            // Kết nối đến MongoDB server (localhost)
            $client = new MongoDB\Client("mongodb://127.0.0.1:27017");
        }
        // Trả về đối tượng Database 'hotel_management_db'
        return $client->hotel_management_db;
    } catch (Exception $e) {
        error_log("MongoDB Connection Error: " . $e->getMessage());
        throw new Exception("Lỗi kết nối MongoDB: " . $e->getMessage());
    }
}
?>