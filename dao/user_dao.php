<?php
require_once('DAO.php');

/**
 * Kiểm tra đăng nhập và lấy thông tin phiên làm việc
 */
function user_check_login($email, $password) {
    // Kiểm tra Employee (Nhân viên / Admin) trước
    $sqlEmp = "SELECT employee_id as user_id, username as email, password, full_name, role, 'Employee' as type 
               FROM Employee WHERE username = ? AND status = 'active'";
    $emp = db_query_one($sqlEmp, $email);
    
    if ($emp && (password_verify($password, $emp['password']) || $password === $emp['password'])) {
        unset($emp['password']);
        return $emp;
    }

    // Nếu không phải nhân viên, kiểm tra Account (Khách hàng online)
    $sqlAcc = "SELECT a.account_id as user_id, a.customer_id, a.email, a.password, c.full_name, 'Customer' as role, 'Customer' as type 
               FROM Account a 
               JOIN Customer c ON a.customer_id = c.customer_id 
               WHERE a.email = ?";
    $acc = db_query_one($sqlAcc, $email);
    
    if ($acc && (password_verify($password, $acc['password']) || $password === $acc['password'])) {
        unset($acc['password']);
        return $acc;
    }

    return false;
}

function user_approve_customer($user_id) {
    return db_execute("UPDATE Account SET status = 'active' WHERE account_id = ?", $user_id);
}

function user_deposit_balance($user_id, $amount) {
    // Lưu ý: Bảng Customer/Account mới không còn cột balance. Giữ hàm này trống hoặc tạo bảng Wallet riêng.
    return true;
}

function user_update_profile($user_id, $phone, $full_name, $dob, $id_number, $nation, $address) {
    $customer_id = db_query_value("SELECT customer_id FROM Account WHERE account_id = ?", $user_id);
    
    $sql = "UPDATE Customer SET full_name = ?, phone = ?, cccd = ?, nation = ?, address = ? WHERE customer_id = ?";
    return db_execute($sql, $full_name, $phone, $id_number, $nation, $address, $customer_id);
}