<?php

/**
 * Mở kết nối đến CSDL sử dụng PDO
 *
 * @param string $db_name Tên database cần kết nối (ví dụ: 'user_management')
 * @return PDO
 * @throws Exception
 */
function pdo_get_connection($db_name)
{
    static $connections = [];

    if (!isset($connections[$db_name])) {
        $host = 'localhost';
        $username = 'root';
        $password = ''; // Cập nhật nếu có

        $dburl = "mysql:host={$host};dbname={$db_name};charset=utf8";

        try {
            $conn = new PDO($dburl, $username, $password);
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $conn->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
            $conn->setAttribute(PDO::MYSQL_ATTR_INIT_COMMAND, "SET NAMES 'utf8'");
            $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

            $connections[$db_name] = $conn; // Lưu lại kết nối
        } catch (PDOException $e) {
            error_log("Database Connection Error to {$db_name}: " . $e->getMessage());
            throw new Exception("Không thể kết nối đến cơ sở dữ liệu ({$db_name}). Vui lòng kiểm tra cấu hình.");
        }
    }

    return $connections[$db_name]; // Trả về kết nối đã lưu
}


/**
 * Thực thi câu lệnh sql thao tác dữ liệu (INSERT, UPDATE, DELETE)
 * @param string $sql câu lệnh sql
 * @param array $args mảng giá trị cung cấp cho các tham số của $sql (optional)
 * @param string $db_name Tên database cần thực thi
 * @throws PDOException lỗi thực thi câu lệnh
 */
function pdo_execute($db_name, $sql)
{
    $sql_args = func_num_args() > 2 ? (is_array(func_get_arg(2)) ? func_get_arg(2) : array_slice(func_get_args(), 2)) : [];
    $conn = null;
    try {
        $conn = pdo_get_connection($db_name);
        $stmt = $conn->prepare($sql);
        $stmt->execute($sql_args);
        return $stmt->rowCount();
    } catch (PDOException $e) {
        error_log("SQL Execute Error on {$db_name}: " . $e->getMessage() . " SQL: " . $sql);
        throw $e;
    } finally {
        if ($conn) {
            unset($conn);
        }
    }
}

/**
 * Thực thi câu lệnh sql truy vấn dữ liệu (SELECT)
 * @param string $db_name Tên database cần truy vấn
 * @param string $sql câu lệnh sql
 * @param array $args mảng giá trị cung cấp cho các tham số của $sql (optional)
 * @return array mảng các bản ghi
 * @throws PDOException lỗi thực thi câu lệnh
 */
function pdo_query($db_name, $sql)
{
    $sql_args = func_num_args() > 2 ? (is_array(func_get_arg(2)) ? func_get_arg(2) : array_slice(func_get_args(), 2)) : [];
    $conn = null;
    try {
        $conn = pdo_get_connection($db_name);
        $stmt = $conn->prepare($sql);
        $stmt->execute($sql_args);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $rows;
    } catch (PDOException $e) {
        error_log("SQL Query Error on {$db_name}: " . $e->getMessage() . " SQL: " . $sql);
        throw $e;
    } finally {
        if ($conn) {
            unset($conn);
        }
    }
}

/**
 * Thực thi câu lệnh sql truy vấn một bản ghi
 * @param string $db_name Tên database cần truy vấn
 * @param string $sql câu lệnh sql
 * @param array $args mảng giá trị cung cấp cho các tham số của $sql (optional)
 * @return array|false mảng chứa bản ghi hoặc false nếu không tìm thấy
 * @throws PDOException lỗi thực thi câu lệnh
 */
function pdo_query_one($db_name, $sql)
{
    $sql_args = func_num_args() > 2 ? (is_array(func_get_arg(2)) ? func_get_arg(2) : array_slice(func_get_args(), 2)) : [];
    $conn = null;
    try {
        $conn = pdo_get_connection($db_name);
        $stmt = $conn->prepare($sql);
        $stmt->execute($sql_args);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row;
    } catch (PDOException $e) {
        error_log("SQL Query One Error on {$db_name}: " . $e->getMessage() . " SQL: " . $sql);
        throw $e;
    } finally {
        if ($conn) {
            unset($conn);
        }
    }
}

/**
 * Thực thi câu lệnh sql truy vấn một giá trị
 * @param string $db_name Tên database cần truy vấn
 * @param string $sql câu lệnh sql
 * @param array $args mảng giá trị cung cấp cho các tham số của $sql (optional)
 * @return mixed|false giá trị hoặc false nếu không tìm thấy
 * @throws PDOException lỗi thực thi câu lệnh
 */
function pdo_query_value($db_name, $sql)
{
    $sql_args = func_num_args() > 2 ? (is_array(func_get_arg(2)) ? func_get_arg(2) : array_slice(func_get_args(), 2)) : [];
    try {
        $conn = pdo_get_connection($db_name); // phải là kết nối dùng chung
        $stmt = $conn->prepare($sql);
        $stmt->execute($sql_args);
        $row = $stmt->fetch(PDO::FETCH_NUM);
        return $row ? $row[0] : false;
    } catch (PDOException $e) {
        error_log("SQL Query Value Error on {$db_name}: " . $e->getMessage() . " SQL: " . $sql);
        throw $e;
    }
}
