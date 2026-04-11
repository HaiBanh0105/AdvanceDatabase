<?php
require_once('DAO.php');

/**
 * Kiểm tra đăng nhập và lấy thông tin phiên làm việc
 */
function user_check_login($email, $password) {
    // THAY ĐỔI QUAN TRỌNG: Bọc [User] trong dấu ngoặc vuông
    $sql = "SELECT u.user_id, u.email, u.password, u.role, ud.full_name, ud.balance 
            FROM [User] u 
            LEFT JOIN User_detail ud ON u.user_id = ud.user_id 
            WHERE u.email = ?";
            
    // Sử dụng Wrapper db_query_one để lấy user theo email
    $user = db_query_one($sql, $email);
    
    if ($user) {
        $match = false;

        if (strpos($user['password'], '$2y$') === 0) {
            $match = password_verify($password, $user['password']);
        } else {
            $match = ($password === $user['password']);

            if ($match) {
                $new_hash = password_hash($password, PASSWORD_DEFAULT);
                db_execute("UPDATE [User] SET password = ? WHERE user_id = ?", $new_hash, $user['user_id']);
            }
        }

        if ($match) {
            unset($user['password']); 
            return $user;
        }
    }
    return false;
}

function user_approve_customer($user_id) {
    return db_execute("UPDATE User_detail SET status = 'active' WHERE user_id = ?", $user_id);
}

function user_deposit_balance($user_id, $amount) {
    return db_execute("UPDATE User_detail SET balance = balance + ? WHERE user_id = ?", $amount, $user_id);
}

function user_update_profile($user_id, $phone, $full_name, $dob, $id_number, $nation, $address) {
    try {
        $conn = pdo_get_connection(DB_NAME);
        $conn->beginTransaction();

        // Sử dụng ngoặc vuông [] cho SQL Server để tránh trùng từ khóa hệ thống
        $sql_user = "UPDATE [User] SET phone = ? WHERE user_id = ?";
        $conn->prepare($sql_user)->execute([$phone, $user_id]);

        $sql_detail = "UPDATE User_detail SET full_name = ?, dob = ?, ID_number = ?, nation = ?, address = ? WHERE user_id = ?";
        $conn->prepare($sql_detail)->execute([$full_name, $dob, $id_number, $nation, $address, $user_id]);

        $conn->commit();
        return true;
    } catch (Exception $e) {
        $conn = pdo_get_connection(DB_NAME);
        if ($conn->inTransaction()) { $conn->rollBack(); }
        return false;
    }
}