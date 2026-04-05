<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../config/mongodb.php';

// Khởi tạo MongoDB
try {
    $mongo_db = mongo_get_db();
    $reviews_collection = $mongo_db->reviews;
    // Lấy danh sách đánh giá, sắp xếp mới nhất
    $reviews = $reviews_collection->find([], ['sort' => ['created_at' => -1]])->toArray();
    
    // Kiểm tra user đã đánh giá chưa
    $has_reviewed = false;
    if (isset($_SESSION['user_id'])) {
        $check_review = $reviews_collection->findOne(['user_id' => (int)$_SESSION['user_id']]);
        if ($check_review) {
            $has_reviewed = true;
        }
    }
} catch (Exception $e) {
    $reviews = [];
    $has_reviewed = true; // Tránh hiện form nếu MongoDB chưa bật
}
?>
<!DOCTYPE html>
<!-- Giao diện Trang chủ Khách hàng -->
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Grand Horizon Hotel - Hệ thống đặt phòng trực tuyến</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;600;700&display=swap');
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
    </style>
</head>
<body class="bg-slate-50 scroll-smooth">

<?php include 'navbar_customer.php'; ?>

<!-- Thông báo nổi -->
<div id="toast-container" class="fixed top-24 right-6 z-[100] flex flex-col gap-3 pointer-events-none">
    <?php if (isset($_GET['review']) && $_GET['review'] == 'success'): ?>
        <div class="toast-alert p-4 bg-emerald-100 text-emerald-600 border border-emerald-200 rounded-2xl text-sm font-bold flex items-center gap-3 shadow-lg transition-all duration-500 translate-x-0">
            <i class="fa-solid fa-circle-check text-lg"></i> Cảm ơn bạn đã gửi đánh giá!
        </div>
    <?php elseif (isset($_GET['review']) && $_GET['review'] == 'exists'): ?>
        <div class="toast-alert p-4 bg-amber-100 text-amber-600 border border-amber-200 rounded-2xl text-sm font-bold flex items-center gap-3 shadow-lg transition-all duration-500 translate-x-0">
            <i class="fa-solid fa-circle-exclamation text-lg"></i> Bạn đã đánh giá rồi, mỗi tài khoản chỉ được đánh giá 1 lần!
        </div>
    <?php endif; ?>
</div>

<section class="pt-32 pb-20 px-6">
    <div class="max-w-7xl mx-auto grid lg:grid-cols-2 gap-12 items-center">
        <div>
            <div class="inline-block px-4 py-2 bg-indigo-50 rounded-full mb-6">
                <span class="text-indigo-600 font-bold tracking-widest text-[10px] uppercase">Khám phá Grand Horizon</span>
            </div>
            <h1 class="text-5xl lg:text-6xl font-extrabold text-slate-800 leading-tight mb-6">
                Trải nghiệm kỳ nghỉ <br><span class="text-transparent bg-clip-text bg-gradient-to-r from-indigo-600 to-purple-600">đẳng cấp 5 sao</span>
            </h1>
            <p class="text-slate-500 text-lg mb-8 leading-relaxed max-w-xl">
                Nằm giữa lòng thành phố, khách sạn chúng tôi mang đến không gian nghỉ dưỡng sang trọng với đầy đủ tiện nghi hiện đại nhất.
            </p>
            <div class="flex gap-4">
                <a href="#rooms" class="bg-indigo-600 text-white px-8 py-4 rounded-2xl font-bold hover:bg-indigo-700 transition shadow-lg shadow-indigo-200">Xem loại phòng</a>
                <button class="flex items-center gap-3 font-bold text-slate-600 px-6 py-4 hover:text-indigo-600 transition group">
                    <span class="w-12 h-12 flex items-center justify-center bg-white shadow-md rounded-full group-hover:scale-110 transition text-indigo-600"><i class="fa-solid fa-play text-xs ml-1"></i></span> Xem video
                </button>
            </div>
        </div>
        <div class="relative">
            <img src="https://images.unsplash.com/photo-1542314831-068cd1dbfeeb?auto=format&fit=crop&w=800" class="rounded-[2rem] shadow-2xl">
            <div class="absolute -bottom-10 -left-10 bg-white p-6 rounded-2xl shadow-xl border border-gray-100 hidden md:block">
                <div class="flex items-center gap-4">
                    <div class="flex -space-x-3">
                        <img src="https://i.pravatar.cc/40?img=1" class="w-10 h-10 rounded-full border-2 border-white">
                        <img src="https://i.pravatar.cc/40?img=2" class="w-10 h-10 rounded-full border-2 border-white">
                        <img src="https://i.pravatar.cc/40?img=3" class="w-10 h-10 rounded-full border-2 border-white">
                    </div>
                    <div>
                        <p class="text-xs font-bold text-gray-800">1,200+ khách hàng</p>
                        <p class="text-[10px] text-yellow-500 font-bold">★★★★★ 4.9/5</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<section id="rooms" class="py-24 bg-white">
    <div class="max-w-7xl mx-auto px-6">
        <div class="text-center mb-16">
            <h2 class="text-3xl font-bold text-gray-900 mb-4">Các loại phòng của chúng tôi</h2> [cite: 9]
            <div class="h-1 w-20 bg-indigo-600 mx-auto rounded-full"></div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            <div class="group border border-gray-100 rounded-3xl overflow-hidden hover:shadow-2xl transition duration-500">
                <div class="h-64 overflow-hidden relative">
                    <img src="https://images.unsplash.com/photo-1611892440504-42a792e24d32?auto=format&fit=crop&w=600" class="w-full h-full object-cover group-hover:scale-110 transition duration-700"> [cite: 10]
                    <div class="absolute top-4 left-4 bg-white/90 backdrop-blur px-3 py-1 rounded-full text-[10px] font-bold text-indigo-600 uppercase">
                        Sức chứa: 2 người [cite: 10]
                    </div>
                </div>
                <div class="p-8">
                    <h3 class="text-xl font-bold text-gray-900 mb-2 uppercase tracking-wide">Phòng Deluxe hướng biển</h3> [cite: 10]
                    <p class="text-gray-500 text-sm mb-6 line-clamp-2 italic">Mô tả: Nội thất gỗ sang trọng, ban công rộng hướng trực diện ra biển Đông.</p> [cite: 10]
                    <div class="flex items-center justify-between pt-6 border-t border-dashed border-gray-200">
                        <div>
                            <span class="text-2xl font-extrabold text-indigo-600">2.500.000đ</span> [cite: 10]
                            <span class="text-xs text-gray-400 font-bold">/đêm</span>
                        </div>
                        <button onclick="openBookingModal('Deluxe')" class="bg-gray-900 text-white px-5 py-2.5 rounded-xl text-xs font-bold hover:bg-indigo-600 transition shadow-lg">
                            Đặt phòng
                        </button> [cite: 21]
                    </div>
                </div>
            </div>
            </div>
    </div>
</section>

<section id="reviews" class="py-24 bg-indigo-50/50 border-t border-slate-100">
    <div class="max-w-7xl mx-auto px-6">
        <div class="text-center mb-16">
            <h2 class="text-3xl font-black text-slate-800 mb-4">Khách hàng nói gì về chúng tôi?</h2>
            <div class="h-1 w-20 bg-indigo-600 mx-auto rounded-full mb-6"></div>
            <p class="text-slate-500 max-w-2xl mx-auto">Hàng ngàn khách hàng đã trải nghiệm và hài lòng với dịch vụ tại Grand Horizon. Hãy chia sẻ cảm nhận của bạn nhé!</p>
        </div>

        <!-- Form Đánh giá -->
        <?php if (isset($_SESSION['user_id']) && !$has_reviewed): ?>
        <div class="max-w-2xl mx-auto bg-white p-8 rounded-[2rem] shadow-xl shadow-slate-200/50 border border-slate-100 mb-16 relative overflow-hidden">
            <div class="absolute top-0 left-0 w-full h-2 bg-gradient-to-r from-indigo-500 to-purple-500"></div>
            <h3 class="text-xl font-black text-slate-800 mb-6 flex items-center gap-3"><i class="fa-solid fa-pen-to-square text-indigo-500"></i> Để lại đánh giá của bạn</h3>
            <form action="../actions/process_review.php" method="POST">
                <div class="mb-5">
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Chấm điểm dịch vụ</label>
                    <select name="rating" class="w-full px-5 py-3.5 bg-slate-50 border border-slate-200 rounded-2xl focus:outline-none focus:ring-2 focus:ring-indigo-500 font-bold text-yellow-500 appearance-none">
                        <option value="5">★★★★★ Tuyệt vời</option>
                        <option value="4">★★★★☆ Rất tốt</option>
                        <option value="3">★★★☆☆ Tạm được</option>
                        <option value="2">★★☆☆☆ Không hài lòng</option>
                        <option value="1">★☆☆☆☆ Rất tệ</option>
                    </select>
                </div>
                <div class="mb-6">
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Cảm nhận của bạn</label>
                    <textarea name="comment" rows="3" required placeholder="Chia sẻ trải nghiệm của bạn tại Grand Horizon..." class="w-full px-5 py-4 bg-slate-50 border border-slate-200 rounded-2xl focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm font-medium text-slate-700 resize-none"></textarea>
                </div>
                <button type="submit" class="bg-indigo-600 text-white px-8 py-4 rounded-2xl font-bold hover:bg-indigo-700 transition w-full shadow-lg shadow-indigo-100 flex items-center justify-center gap-2"><i class="fa-solid fa-paper-plane"></i> Gửi đánh giá ngay</button>
            </form>
        </div>
        <?php elseif (!isset($_SESSION['user_id'])): ?>
        <div class="text-center mb-16 p-8 bg-white rounded-[2rem] border border-slate-100 shadow-sm max-w-2xl mx-auto">
            <div class="w-16 h-16 bg-indigo-50 text-indigo-500 rounded-full flex items-center justify-center mx-auto mb-4 text-2xl"><i class="fa-solid fa-lock"></i></div>
            <h3 class="text-lg font-bold text-slate-800 mb-2">Bạn chưa đăng nhập</h3>
            <p class="text-slate-500 text-sm mb-6">Vui lòng đăng nhập để có thể chia sẻ trải nghiệm và để lại đánh giá của bạn.</p>
            <a href="login.php" class="inline-block px-8 py-3 bg-indigo-600 text-white rounded-xl font-bold hover:bg-indigo-700 shadow-lg shadow-indigo-100 transition">Đăng nhập ngay</a>
        </div>
        <?php endif; ?>

        <!-- Danh sách Đánh giá -->
        <?php if (empty($reviews)): ?>
            <div class="text-center text-slate-400 italic text-sm">Chưa có đánh giá nào. Hãy là người đầu tiên!</div>
        <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($reviews as $rv): 
                    $date = '';
                    if (isset($rv['created_at']) && $rv['created_at'] instanceof MongoDB\BSON\UTCDateTime) {
                        $date = $rv['created_at']->toDateTime()->setTimezone(new DateTimeZone('Asia/Ho_Chi_Minh'))->format('d/m/Y H:i');
                    }
                ?>
                <div class="bg-white p-8 rounded-[2rem] shadow-sm border border-slate-100 hover:shadow-lg transition duration-300 flex flex-col">
                    <div class="flex items-center gap-4 mb-6">
                        <div class="w-12 h-12 bg-gradient-to-br from-indigo-500 to-purple-500 text-white rounded-full flex items-center justify-center font-black text-xl shadow-md">
                            <?php echo mb_substr($rv['user_name'], 0, 1, 'UTF-8'); ?>
                        </div>
                        <div>
                            <h4 class="font-bold text-slate-800"><?php echo htmlspecialchars($rv['user_name']); ?></h4>
                            <div class="text-yellow-400 text-[10px] tracking-widest mt-1">
                                <?php echo str_repeat('★', $rv['rating']) . str_repeat('☆', 5 - $rv['rating']); ?>
                            </div>
                        </div>
                    </div>
                    <p class="text-slate-600 text-sm italic leading-relaxed flex-1">"<?php echo htmlspecialchars($rv['comment']); ?>"</p>
                    <div class="mt-6 pt-4 border-t border-slate-50 flex items-center justify-between text-[10px] font-bold text-slate-400 uppercase tracking-wider">
                        <span>Đã xác thực</span>
                        <span><?php echo $date; ?></span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<footer class="bg-slate-900 text-slate-300 py-16 border-t border-slate-800">
    <div class="max-w-7xl mx-auto px-6 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-12">
        <div>
            <div class="flex items-center gap-2 mb-6">
                <div class="bg-indigo-600 p-2 rounded-lg">
                    <i class="fa-solid fa-crown text-white"></i>
                </div>
                <span class="font-bold text-xl tracking-tight text-white uppercase tracking-widest">Grand<span class="text-indigo-500">Horizon</span></span>
            </div>
            <p class="text-sm text-slate-400 leading-relaxed mb-6">Trải nghiệm không gian nghỉ dưỡng đẳng cấp 5 sao với dịch vụ hoàn hảo và tầm nhìn tuyệt đẹp, mang lại kỳ nghỉ khó quên.</p>
            <div class="flex gap-3">
                <a href="#" class="w-10 h-10 rounded-full bg-slate-800 flex items-center justify-center hover:bg-indigo-600 hover:text-white transition"><i class="fa-brands fa-facebook-f"></i></a>
                <a href="#" class="w-10 h-10 rounded-full bg-slate-800 flex items-center justify-center hover:bg-indigo-600 hover:text-white transition"><i class="fa-brands fa-instagram"></i></a>
                <a href="#" class="w-10 h-10 rounded-full bg-slate-800 flex items-center justify-center hover:bg-indigo-600 hover:text-white transition"><i class="fa-brands fa-twitter"></i></a>
            </div>
        </div>

        <div>
            <h4 class="text-white font-bold mb-6 uppercase tracking-wider text-sm">Liên kết nhanh</h4>
            <ul class="space-y-3 text-sm">
                <li><a href="#" class="text-slate-400 hover:text-indigo-400 transition">Về chúng tôi</a></li>
                <li><a href="#rooms" class="text-slate-400 hover:text-indigo-400 transition">Danh sách phòng</a></li>
                <li><a href="#" class="text-slate-400 hover:text-indigo-400 transition">Dịch vụ Spa & Massage</a></li>
                <li><a href="#" class="text-slate-400 hover:text-indigo-400 transition">Nhà hàng 5 sao</a></li>
            </ul>
        </div>

        <div>
            <h4 class="text-white font-bold mb-6 uppercase tracking-wider text-sm">Hỗ trợ khách hàng</h4>
            <ul class="space-y-3 text-sm">
                <li><a href="#" class="text-slate-400 hover:text-indigo-400 transition">Chính sách đặt/hủy phòng</a></li>
                <li><a href="#" class="text-slate-400 hover:text-indigo-400 transition">Điều khoản bảo mật</a></li>
                <li><a href="#" class="text-slate-400 hover:text-indigo-400 transition">Câu hỏi thường gặp (FAQ)</a></li>
                <li><a href="#" class="text-slate-400 hover:text-indigo-400 transition">Hướng dẫn thanh toán</a></li>
            </ul>
        </div>

        <div>
            <h4 class="text-white font-bold mb-6 uppercase tracking-wider text-sm">Thông tin liên hệ</h4>
            <ul class="space-y-4 text-sm text-slate-400">
                <li class="flex items-start gap-3">
                    <i class="fa-solid fa-location-dot mt-1 text-indigo-500"></i>
                    <span>Số 1, Đại lộ Tự Do, Quận 1<br>Thành phố Hồ Chí Minh, VN</span>
                </li>
                <li class="flex items-center gap-3">
                    <i class="fa-solid fa-phone text-indigo-500"></i>
                    <span>+84 1900 8888</span>
                </li>
                <li class="flex items-center gap-3">
                    <i class="fa-solid fa-envelope text-indigo-500"></i>
                    <span>contact@grandhorizon.com</span>
                </li>
            </ul>
        </div>
    </div>
    <div class="max-w-7xl mx-auto px-6 mt-12 pt-8 border-t border-slate-800 text-center text-xs font-medium text-slate-500">
        © <?php echo date('Y'); ?> Grand Horizon Hotel. Tất cả quyền được bảo lưu.
    </div>
</footer>

<script>
    function openBookingModal(roomType) {
        alert("Chức năng đặt phòng cho loại: " + roomType + " đang được xử lý!");
    }

    // Tự động ẩn thông báo Toast sau 3 giây
    document.addEventListener("DOMContentLoaded", function() {
        setTimeout(() => {
            const alerts = document.querySelectorAll('.toast-alert');
            alerts.forEach(alert => {
                alert.classList.add('opacity-0', 'translate-x-full');
                setTimeout(() => alert.remove(), 500);
            });
        }, 3000);
    });
</script>

</body>
</html>