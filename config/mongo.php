<?php
// Nạp thư viện MongoDB đã cài qua Composer
require_once __DIR__ . '/../vendor/autoload.php';

/**
 * Khởi tạo và trả về kết nối đến MongoDB
 */
function mongo_get_client() {
    static $client = null;
    if ($client === null) {
        $client = new MongoDB\Client("mongodb://127.0.0.1:27017");
    }
    return $client;
}

/**
 * Lấy Database
 */
function mongo_get_db() {
    $client = mongo_get_client();
    return $client->grand_horizon_mongo;
}
?>