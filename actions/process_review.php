<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../frontend/login.php");
    exit();
}

require_once '../dao/review_dao.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_id = (int)$_SESSION['user_id'];
    $user_name = trim($_SESSION['full_name'] ?? 'Khách');
    
    $action = $_POST['action'] ?? 'add';
    $rating = (int)($_POST['rating'] ?? 5);
    $comment = trim($_POST['comment'] ?? '');

    try {
        // Xử lý Xóa đánh giá
        if ($action === 'delete') {
            review_delete_by_user($user_id);
            header("Location: ../frontend/customer_index.php?review=deleted#reviews");
            exit();
        }

        // Kiểm tra dữ liệu hợp lệ cho Thêm/Sửa
        if ($rating < 1 || $rating > 5 || empty($comment)) {
            header("Location: ../frontend/customer_index.php?review=error#reviews");
            exit();
        }

        if ($action === 'add') {
            $existing = review_check_exists($user_id);
            if ($existing) {
                header("Location: ../frontend/customer_index.php?review=exists#reviews");
                exit();
            }
            
            review_insert($user_id, $user_name, $rating, $comment);
            header("Location: ../frontend/customer_index.php?review=added#reviews");
            
        } elseif ($action === 'edit') {
            review_update($user_id, $rating, $comment);
            header("Location: ../frontend/customer_index.php?review=updated#reviews");
        }
    } catch (Exception $e) {
        header("Location: ../frontend/customer_index.php?review=error#reviews");
    }
    exit();
}
?>