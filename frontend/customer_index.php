<?php
session_start();
require_once '../dao/DAO.php';
require_once '../config/mongodb.php';

// Lấy dữ liệu Hạng phòng
$room_types = db_query("SELECT * FROM Room_types ORDER BY price ASC");

// Lấy ảnh MongoDB
$mongo_db = mongo_get_db();
$images_cursor = $mongo_db->room_images->find([]);
$mongo_images = [];
foreach ($images_cursor as $img) {
    $mongo_images[$img['type_id']] = [
        'base64' => $img['image_base64'],
        'mime' => $img['mime_type'] ?? 'image/jpeg'
    ];
}

// Lấy dữ liệu Đánh giá từ MongoDB
$reviews = $mongo_db->reviews->find([], ['sort' => ['created_at' => -1]])->toArray();
$has_reviewed = false;
$check_review = null;
if (isset($_SESSION['user_id'])) {
    $check_review = $mongo_db->reviews->findOne(['user_id' => (int)$_SESSION['user_id']]);
    if ($check_review) $has_reviewed = true;
}
?>
<!DOCTYPE html>
<html lang="vi" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Grand Horizon - Trang chủ Khách hàng</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-slate-50 text-slate-800">

    <?php include 'navbar_customer.php'; ?>

    <!-- Hero Section -->
    <div class="relative h-[60vh] bg-slate-900 flex items-center justify-center overflow-hidden">
        <img src="https://images.unsplash.com/photo-1542314831-c6a4d14d8373?auto=format&fit=crop&q=80" class="absolute inset-0 w-full h-full object-cover opacity-40">
        <div class="relative z-10 text-center px-4">
            <h1 class="text-5xl md:text-7xl font-black text-white mb-6 tracking-tight drop-shadow-lg">Nơi nghỉ dưỡng hoàn hảo</h1>
            <p class="text-xl text-slate-200 font-medium max-w-2xl mx-auto drop-shadow">Trải nghiệm dịch vụ đẳng cấp 5 sao với không gian sang trọng và tiện nghi hiện đại.</p>
        </div>
    </div>

    <!-- Danh sách Hạng phòng (Room Types) -->
    <div class="max-w-7xl mx-auto px-6 py-24" id="rooms">
        <div class="text-center mb-16">
            <h2 class="text-4xl font-black text-slate-800 tracking-tight">Lựa chọn của bạn</h2>
            <p class="text-slate-500 mt-4 font-medium">Các hạng phòng được thiết kế chuyên biệt để mang lại sự thoải mái tối đa.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-10">
            <?php foreach ($room_types as $rt): ?>
            <div class="bg-white rounded-[2rem] overflow-hidden shadow-lg border border-slate-100 group hover:-translate-y-2 transition-all duration-500 flex flex-col">
                <div class="relative h-64 overflow-hidden bg-slate-100">
                    <?php if (isset($mongo_images[$rt['type_id']])): ?>
                        <img src="data:<?php echo $mongo_images[$rt['type_id']]['mime']; ?>;base64,<?php echo $mongo_images[$rt['type_id']]['base64']; ?>" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-700">
                    <?php else: ?>
                        <div class="flex items-center justify-center h-full text-slate-300"><i class="fa-solid fa-image text-5xl"></i></div>
                    <?php endif; ?>
                    <div class="absolute top-4 right-4 bg-white/90 backdrop-blur-sm px-4 py-2 rounded-2xl shadow-lg">
                        <p class="text-indigo-600 font-black text-lg tracking-tight"><?php echo number_format($rt['price'], 0, ',', '.'); ?>đ<span class="text-[10px] text-slate-500 font-bold"> / Đêm</span></p>
                    </div>
                </div>
                <div class="p-8 flex-1 flex flex-col">
                    <h3 class="text-2xl font-black text-slate-800 mb-2 uppercase tracking-tight"><?php echo htmlspecialchars($rt['name']); ?></h3>
                    <p class="text-slate-500 text-sm line-clamp-3 mb-6 flex-1"><?php echo htmlspecialchars($rt['description']); ?></p>
                    <div class="flex items-center gap-4 text-xs font-bold text-slate-400 uppercase tracking-widest mb-8">
                        <span><i class="fa-solid fa-user-group text-indigo-400 mr-1.5"></i>Tối đa <?php echo $rt['capacity']; ?> người</span>
                    </div>
                    <!-- Form ẩn để gọi logic đặt phòng -->
                    <form action="../actions/process_booking.php" method="POST" class="mt-auto bg-slate-50 p-4 rounded-2xl border border-slate-100">
                        <input type="hidden" name="type_id" value="<?php echo $rt['type_id']; ?>">
                        <div class="grid grid-cols-2 gap-3 mb-4">
                            <div>
                                <label class="block text-[10px] font-black text-slate-400 uppercase mb-1">Check-in</label>
                                <input type="datetime-local" name="check_in" required class="w-full px-3 py-2 bg-white border border-slate-200 rounded-xl text-xs font-bold focus:ring-2 focus:ring-indigo-500 outline-none">
                            </div>
                            <div>
                                <label class="block text-[10px] font-black text-slate-400 uppercase mb-1">Check-out</label>
                                <input type="datetime-local" name="check_out" required class="w-full px-3 py-2 bg-white border border-slate-200 rounded-xl text-xs font-bold focus:ring-2 focus:ring-indigo-500 outline-none">
                            </div>
                        </div>
                        <button type="submit" class="w-full bg-indigo-600 text-white py-3 rounded-xl font-bold shadow-lg shadow-indigo-200 hover:bg-indigo-700 transition active:scale-95">Đặt phòng ngay</button>
                    </form>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Cảnh báo lỗi từ server -->
    <?php if (isset($_GET['error']) && $_GET['error'] == 'no_room_available'): ?>
        <div class="fixed top-24 right-6 z-50 p-4 bg-red-100 text-red-600 border border-red-200 rounded-2xl text-sm font-bold flex items-center gap-3 shadow-lg">
            <i class="fa-solid fa-circle-exclamation text-lg"></i> Xin lỗi, không còn phòng trống trong khoảng thời gian này!
        </div>
    <?php elseif (isset($_GET['msg']) && $_GET['msg'] == 'booking_success'): ?>
        <div class="fixed top-24 right-6 z-50 p-4 bg-emerald-100 text-emerald-600 border border-emerald-200 rounded-2xl text-sm font-bold flex items-center gap-3 shadow-lg">
            <i class="fa-solid fa-circle-check text-lg"></i> Đặt phòng thành công! Hãy đợi nhân viên xác nhận.
        </div>
    <?php endif; ?>

    <?php 
    include 'review_section.php'; 
    ?>

    <!-- Khôi phục Footer -->
    <footer class="bg-slate-900 text-slate-400 py-12 text-center text-sm border-t border-slate-800">
        <div class="max-w-7xl mx-auto px-6 flex flex-col md:flex-row justify-between items-center gap-4">
            <div class="font-black text-xl text-white tracking-tighter uppercase">Grand<span class="text-indigo-500">Horizon</span></div>
            <p>&copy; <?php echo date('Y'); ?> Grand Horizon Hotel. Tất cả các quyền được bảo lưu.</p>
            <div class="flex gap-4 text-lg">
                <a href="#" class="hover:text-white transition"><i class="fa-brands fa-facebook"></i></a>
                <a href="#" class="hover:text-white transition"><i class="fa-brands fa-instagram"></i></a>
                <a href="#" class="hover:text-white transition"><i class="fa-brands fa-twitter"></i></a>
            </div>
        </div>
    </footer>

</body>
</html>