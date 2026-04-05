<?php
session_start();
require_once __DIR__ . '/config/pdo.php';

try {
    // Thử kết nối đến Database để kiểm tra
    $conn = pdo_get_connection('hotel_management_db');
    
    // Nếu kết nối thành công, thực hiện logic chuyển hướng như bình thường
    if (!isset($_SESSION['user_id'])) {
        header("Location: frontend/login.php");
        exit();
    }

    if ($_SESSION['role'] === 'Admin') {
        header("Location: frontend/admin_dashboard.php");
    } else {
        header("Location: frontend/customer_index.php");
    }
    exit();

} catch (Exception $e) {
    // Bắt lỗi kết nối và lưu thông báo lỗi
    $error_msg = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lỗi Hệ Thống - Grand Horizon</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;600;700&display=swap');
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
    </style>
</head>
<body class="bg-slate-50 h-screen flex items-center justify-center p-4">
    <div class="max-w-md w-full bg-white p-8 rounded-[2.5rem] shadow-xl shadow-slate-200/60 border border-slate-100 text-center">
        <div class="inline-flex items-center justify-center bg-red-100 p-5 rounded-full mb-6">
            <i class="fa-solid fa-database text-red-500 text-4xl"></i>
        </div>
        <h1 class="text-2xl font-black text-slate-800 mb-2">Lỗi Kết Nối Database!</h1>
        <p class="text-slate-500 mb-6 text-sm">Hệ thống không thể kết nối đến cơ sở dữ liệu MySQL. Vui lòng kiểm tra lại XAMPP hoặc thông tin cấu hình.</p>
        
        <div class="bg-slate-50 p-4 rounded-2xl border border-slate-200 text-left mb-8 overflow-hidden">
            <p class="text-xs font-mono text-red-600 break-words"><?php echo htmlspecialchars($error_msg); ?></p>
        </div>

        <button onclick="window.location.reload()" class="w-full bg-slate-900 text-white py-4 rounded-2xl font-bold flex items-center justify-center gap-2 hover:bg-slate-800 transition-all active:scale-95 shadow-lg">
            <i class="fa-solid fa-rotate-right"></i> Thử lại
        </button>
    </div>
</body>
</html>
