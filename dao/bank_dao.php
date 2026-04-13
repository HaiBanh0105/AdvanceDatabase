<?php
require_once('DAO.php');

/**
 * Kiểm tra xem thẻ ngân hàng đã được liên kết với user nào chưa
 */
function bank_check_card_exists($card_id) {
    $sql = "SELECT account_id FROM Bank_account WHERE card_id = ?";
    return db_query_one($sql, $card_id);
}

/**
 * Xóa các liên kết thẻ cũ của user
 */
function bank_delete_by_user($user_id) {
    $sql = "DELETE FROM Bank_account WHERE account_id = ?";
    return db_execute($sql, $user_id);
}

/**
 * Thêm liên kết thẻ ngân hàng mới
 */
function bank_insert_account($card_id, $user_id, $provider, $cardholder_name, $cvv, $expiry_date) {
    $sql = "INSERT INTO Bank_account (card_id, account_id, provider, cardholder_name, CVV, expiry_date) VALUES (?, ?, ?, ?, ?, ?)";
    return db_execute($sql, $card_id, $user_id, $provider, $cardholder_name, $cvv, $expiry_date);
}
?>