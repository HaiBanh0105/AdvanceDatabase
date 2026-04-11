<?php
/** File xử lý Database cho Đăng ký & Mật khẩu */
require_once('DAO.php');

function user_check_email_exists($email) {
    $sql = "SELECT user_id FROM [User] WHERE email = ?";
    return db_query_one($sql, $email);
}

function user_register($email, $phone, $password, $full_name) {
    try {
        // Lấy đối tượng kết nối PDO trực tiếp để dùng Transaction và lastInsertId
        $conn = pdo_get_connection(DB_NAME); 
        $conn->beginTransaction();

        // 1. Thêm vào bảng User (role mặc định là Customer)
        $sql1 = "INSERT INTO [User] (email, phone, password, role) VALUES (?, ?, ?, 'Customer')";
        $stmt1 = $conn->prepare($sql1);
        $stmt1->execute([$email, $phone, $password]);
        
        // 2. Lấy ID vừa tạo
        $user_id = $conn->lastInsertId();

        // 3. Thêm vào bảng User_detail
        $sql2 = "INSERT INTO User_detail (user_id, full_name, status, balance) VALUES (?, ?, 'pending', 0.00)";
        $stmt2 = $conn->prepare($sql2);
        $stmt2->execute([$user_id, $full_name]);

        $conn->commit();
        return true;
    } catch (Exception $e) {
        $conn = pdo_get_connection(DB_NAME);
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        error_log("Lỗi Đăng Ký: " . $e->getMessage());
        return false;
    }
}

function user_get_password($email) {
    $sql = "SELECT password FROM [User] WHERE email = ?";
    return db_query_value($sql, $email);
}

function user_update_password($email, $new_password) {
    $sql = "UPDATE [User] SET password = ? WHERE email = ?";
    return db_execute($sql, $new_password, $email);
}
?>