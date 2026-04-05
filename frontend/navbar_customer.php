<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Lấy tên file hiện tại để xử lý logic "Active"
$current_page = basename($_SERVER['PHP_SELF']);
$full_name = $_SESSION['full_name'] ?? 'Khách';
?>
<nav class="fixed w-full z-50 bg-white/90 backdrop-blur-md border-b border-gray-100">
    <div class="max-w-7xl mx-auto px-6 h-20 flex justify-between items-center">
        <div class="flex items-center gap-2">
            <div class="bg-indigo-600 p-2 rounded-lg">
                <i class="fa-solid fa-crown text-white"></i>
            </div>
            <span class="font-bold text-xl tracking-tight uppercase tracking-widest">Grand<span class="text-indigo-600">Horizon</span></span>
        </div>
        
        <div class="hidden md:flex items-center space-x-10 text-sm font-semibold">
            <a href="customer_index.php" class="<?php echo ($current_page == 'customer_index.php') ? 'text-indigo-600 border-b-2 border-indigo-600' : 'text-gray-600 hover:text-indigo-600'; ?> pb-1 transition">Trang chủ</a>
            <a href="booking_history.php" class="<?php echo ($current_page == 'booking_history.php') ? 'text-indigo-600 border-b-2 border-indigo-600' : 'text-gray-600 hover:text-indigo-600'; ?> pb-1 transition">Lịch sử đặt phòng</a>
            <a href="customer_profile.php" class="<?php echo ($current_page == 'customer_profile.php') ? 'text-indigo-600 border-b-2 border-indigo-600' : 'text-gray-600 hover:text-indigo-600'; ?> pb-1 transition">Tài khoản</a>
        </div>

        <div class="flex items-center gap-6">
            <div class="text-right border-r pr-6 hidden sm:block">
                <p class="text-[10px] text-gray-400 uppercase font-bold">Chào mừng trở lại,</p>
                <p class="text-sm font-bold text-indigo-600"><?php echo htmlspecialchars($full_name); ?></p>
            </div>
            <a href="../actions/process_logout.php" title="Đăng xuất" class="text-gray-500 hover:text-red-600 transition"><i class="fa-solid fa-right-from-bracket"></i></a>
        </div>
    </div>
</nav>