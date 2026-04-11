<?php
// Include các file cấu hình bằng đường dẫn tuyệt đối để tránh lỗi
require_once __DIR__ . '/config/pdo.php';
require_once __DIR__ . '/config/mongodb.php';

$sqlServerStatus = "Đang kiểm tra...";
$mongoStatus = "Đang kiểm tra...";

// 1. Kiểm tra kết nối SQL Server
try {
    // Sử dụng tên DB giống như cấu hình trong file DAO.php của bạn
    $conn = pdo_get_connection('hotel_management_db');
    if ($conn) {
        $sqlServerStatus = "<span style='color: #10B981;'><b>Thành công!</b> Đã kết nối được tới SQL Server.</span>";
    }
} catch (\Throwable $th) {
    $sqlServerStatus = "<span style='color: #EF4444;'><b>Thất bại:</b> " . htmlspecialchars($th->getMessage()) . "</span>";
}

// 2. Kiểm tra kết nối MongoDB
try {
    $mongoClient = mongo_get_client();
    // Thực hiện lệnh ping admin để ép MongoDB Driver thực hiện kết nối máy chủ thực tế
    $mongoClient->selectDatabase('admin')->command(['ping' => 1]);
    $mongoStatus = "<span style='color: #10B981;'><b>Thành công!</b> Đã kết nối được tới MongoDB.</span>";
} catch (\Throwable $th) {
    $mongoStatus = "<span style='color: #EF4444;'><b>Thất bại:</b> " . htmlspecialchars($th->getMessage()) . "</span>";
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kiểm tra kết nối Database</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f3f4f6; margin: 0; padding: 40px; }
        .container { max-width: 600px; margin: 0 auto; background: #ffffff; padding: 30px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        h2 { text-align: center; color: #1f2937; margin-top: 0; margin-bottom: 30px; border-bottom: 2px solid #e5e7eb; padding-bottom: 15px; }
        .status-card { margin-bottom: 20px; padding: 20px; border-radius: 6px; border: 1px solid #e5e7eb; border-left: 5px solid #d1d5db; background: #f9fafb; }
        .sql-card { border-left-color: #3b82f6; }
        .mongo-card { border-left-color: #10b981; }
        .status-card h3 { margin-top: 0; color: #374151; font-size: 18px; }
        .status-card p { margin-bottom: 0; font-size: 15px; line-height: 1.5; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Trạng thái kết nối Máy chủ</h2>
        
        <div class="status-card sql-card">
            <h3><i class="fas fa-database"></i> SQL Server</h3>
            <p><?php echo $sqlServerStatus; ?></p>
        </div>

        <div class="status-card mongo-card">
            <h3><i class="fas fa-leaf"></i> MongoDB</h3>
            <p><?php echo $mongoStatus; ?></p>
        </div>
    </div>
</body>
</html>