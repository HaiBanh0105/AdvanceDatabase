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
    
    $action = $_POST['action'] ?? 'add';
    $rating = (int)($_POST['rating'] ?? 5);
    $comment = trim($_POST['comment'] ?? '');

    try {
        $mongo_db = mongo_get_db();
        $reviews_collection = $mongo_db->reviews;

        // Xử lý Xóa đánh giá
        if ($action === 'delete') {
            $reviews_collection->deleteOne(['user_id' => $user_id]);
            header("Location: ../frontend/customer_index.php?review=deleted#reviews");
            exit();
        }

        // Kiểm tra dữ liệu hợp lệ cho Thêm/Sửa
        if ($rating < 1 || $rating > 5 || empty($comment)) {
            header("Location: ../frontend/customer_index.php?review=error#reviews");
            exit();
        }

        if ($action === 'add') {
            $existing = $reviews_collection->findOne(['user_id' => $user_id]);
            if ($existing) {
                header("Location: ../frontend/customer_index.php?review=exists#reviews");
                exit();
            }
            
            $reviews_collection->insertOne([
                'user_id' => $user_id,
                'user_name' => $user_name,
                'rating' => $rating,
                'comment' => $comment,
                'created_at' => new MongoDB\BSON\UTCDateTime()
            ]);
            header("Location: ../frontend/customer_index.php?review=added#reviews");
            
        } elseif ($action === 'edit') {
            $reviews_collection->updateOne(
                ['user_id' => $user_id],
                ['$set' => [
                    'rating' => $rating,
                    'comment' => $comment,
                    'updated_at' => new MongoDB\BSON\UTCDateTime()
                ]]
            );
            header("Location: ../frontend/customer_index.php?review=updated#reviews");
        }
    } catch (Exception $e) {
        header("Location: ../frontend/customer_index.php?review=error#reviews");
    }
    exit();
}
?>