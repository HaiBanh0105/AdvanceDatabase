<?php
require_once '../dao/DAO.php';
try {
    // Giả sử hủy đơn có booking_id = 1
    db_execute("CALL sp_cancel_booking(?)", 1);
    echo "Hủy thành công!";
} catch (Exception $e) {
    echo "Lỗi: " . $e->getMessage();
}
?>
