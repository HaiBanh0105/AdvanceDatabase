<?php
require_once('DAO.php');

/**
 * Kiểm tra đăng nhập và lấy thông tin phiên làm việc
 */
function user_check_login($email, $password) {
    // THAY ĐỔI QUAN TRỌNG: Bọc [User] trong dấu ngoặc vuông
    $sql = "SELECT u.user_id, u.email, u.role, ud.full_name, ud.balance 
            FROM [User] u 
            LEFT JOIN User_detail ud ON u.user_id = ud.user_id 
            WHERE u.email = ? AND u.password = ?";
            
    // Sử dụng Wrapper db_query_one (Lớp DAO của bạn sẽ tự động truyền DB_NAME)
    return db_query_one($sql, $email, $password);
}