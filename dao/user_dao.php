<?php
require_once('DAO.php');
function user_check_login($email, $password) {
    // Truy vấn kết hợp bảng User và User_detail để lấy đủ thông tin cần thiết 
    $sql = "SELECT u.user_id, u.email, u.role, ud.full_name, ud.balance 
            FROM User u 
            LEFT JOIN User_detail ud ON u.user_id = ud.user_id 
            WHERE u.email = ? AND u.password = ?";
            
    // Sử dụng Wrapper db_query_one bạn đã tạo
    return db_query_one($sql, $email, $password);
}