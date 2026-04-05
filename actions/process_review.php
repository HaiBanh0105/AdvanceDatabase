<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../frontend/login.php");
    exit();
}

require_once '../config/mongodb.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_id = (int)$_SESSION['user_id'];
    $user_name = trim($_SESSION['full_name'] ?? 'Khách');
    
    $rating = (int)($_POST['rating'] ?? 5);
    $comment = trim($_POST['comment'] ?? '');

    if ($rating < 1 || $rating > 5 || empty($comment)) {
        header("Location: ../frontend/customer_index.php?review=error#reviews");
        exit();
    }

    try {
        $mongo_db = mongo_get_db();
        $reviews_collection = $mongo_db->reviews;

        // Kiểm tra lại lần nữa để chắc chắn mỗi người chỉ đánh giá 1 lần
        $existing = $reviews_collection->findOne(['user_id' => $user_id]);
        if ($existing) {
            header("Location: ../frontend/customer_index.php?review=exists#reviews");
            exit();
        }

        // Lưu vào MongoDB
        $reviews_collection->insertOne([
            'user_id' => $user_id,
            'user_name' => $user_name,
            'rating' => $rating,
            'comment' => $comment,
            'created_at' => new MongoDB\BSON\UTCDateTime()
        ]);

        header("Location: ../frontend/customer_index.php?review=success#reviews");
    } catch (Exception $e) {
        header("Location: ../frontend/customer_index.php?review=error#reviews");
    }
    exit();
}
?>