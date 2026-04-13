<?php
/** File xử lý Database cho Đăng ký & Mật khẩu */
require_once('DAO.php');

function user_check_email_exists($email) {
    $sql = "SELECT account_id FROM Account WHERE email = ?";
    return db_query_one($sql, $email);
}

function user_register($email, $phone, $password, $full_name) {
    try {
        $conn = pdo_get_connection(DB_NAME); 
        $conn->beginTransaction();

        $sql1 = "INSERT INTO Customer (full_name, phone, email) VALUES (?, ?, ?)";
        $stmt1 = $conn->prepare($sql1);
        $stmt1->execute([$full_name, $phone, $email]);
        
        $customer_id = $conn->lastInsertId();

        $sql2 = "INSERT INTO Account (customer_id, email, password, status) VALUES (?, ?, ?, 'Inactive')";
        $stmt2 = $conn->prepare($sql2);
        $stmt2->execute([$customer_id, $email, $password]);

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
    $sql = "SELECT password FROM Account WHERE email = ?";
    return db_query_value($sql, $email);
}

function user_update_password($email, $new_password) {
    $sql = "UPDATE Account SET password = ? WHERE email = ?";
    return db_execute($sql, $new_password, $email);
}
?>