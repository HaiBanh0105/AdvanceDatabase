<?php
/** File xử lý Database cho Đăng nhập */
require_once('DAO.php');

/**
 * Kiểm tra đăng nhập và lấy thông tin phiên làm việc
 */

function user_check_login($email, $password) {
    $sql = "SELECT u.user_id, u.email, u.role, u.password, ud.full_name, ud.balance 
            FROM `User` u 
            LEFT JOIN User_detail ud ON u.user_id = ud.user_id 
            WHERE u.email = ?";
            
    $user = db_query_one($sql, $email);
    
    // Hỗ trợ cả mật khẩu đã mã hóa (Tài khoản mới tạo) và mật khẩu text thường (Tài khoản cũ)
    if ($user && (password_verify($password, $user['password']) || $password === $user['password'])) {
        unset($user['password']); // Xóa password khỏi mảng trước khi trả về để bảo mật Session
        return $user;
    }
    return false;
}
?>