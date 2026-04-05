<?php
/** File Adapter kết nối DAO tự động */
// Điều chỉnh đường dẫn tương đối đến file pdo.php
require_once('../config/pdo.php');

// Định nghĩa tên DB chính xác DỰA TRÊN CẤU TRÚC THỰC TẾ CỦA BẠN
const DB_NAME = 'hotel_management_db';

// ------------------------------------
// Phần 1: WRAPPER PDO (DAO Adapter)
// ------------------------------------

/**
 * Wrapper cho pdo_execute, tự động truyền tên DB 
 */
function db_execute($sql)
{
    $args = array_slice(func_get_args(), 1);
    return pdo_execute(DB_NAME, $sql, $args);
}

/**
 * Wrapper cho pdo_query (truy vấn nhiều bản ghi), tự động truyền tên DB 
 */
function db_query($sql)
{
    $args = array_slice(func_get_args(), 1);
    return pdo_query(DB_NAME, $sql, $args);
}

/**
 * Wrapper cho truy vấn một bản ghi, tự động truyền tên DB 
 */
function db_query_one($sql)
{
    $args = array_slice(func_get_args(), 1);
    return pdo_query_one(DB_NAME, $sql, $args);
}

/**
 * Wrapper cho truy vấn một giá trị, tự động truyền tên DB 
 */
function db_query_value($sql)
{
    $args = array_slice(func_get_args(), 1);
    return pdo_query_value(DB_NAME, $sql, $args);
}
?>
