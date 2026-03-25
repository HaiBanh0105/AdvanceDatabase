<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>
<aside class="w-64 bg-slate-900 h-screen sticky top-0 text-white p-6 shadow-xl flex flex-col">
    <div class="flex items-center gap-3 mb-10">
        <i class="fa-solid fa-user-shield text-indigo-400 text-2xl"></i>
        <span class="font-bold text-lg tracking-wider uppercase">Grand Admin</span>
    </div>
    

    <nav class="space-y-4">
            <a href="admin_dashboard.php" class="flex items-center gap-3 p-3 rounded-xl transition <?php echo ($current_page == 'admin_dashboard.php') ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/20' : 'text-slate-400 hover:bg-slate-800 hover:text-white'; ?>">
                <i class="fa-solid fa-chart-line w-5"></i> Tổng quan
            </a>
            <!-- <a href="room-types.php" class="flex items-center gap-3 p-3 rounded-xl transition <?php echo ($current_page == 'room-types.php') ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/20' : 'text-slate-400 hover:bg-slate-800 hover:text-white'; ?>">
                <i class="fa-solid fa-tags w-5"></i> Quản lý Giá phòng
            </a> -->
            <a href="admin_rooms.php" class="flex items-center gap-3 p-3 rounded-xl transition <?php echo ($current_page == 'admin_rooms.php') ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/20' : 'text-slate-400 hover:bg-slate-800 hover:text-white'; ?>">
                <i class="fa-solid fa-bed w-5"></i> Quản lý Phòng
            </a>
            <a href="admin_bookings.php" class="flex items-center gap-3 p-3 rounded-xl transition <?php echo ($current_page == 'admin_bookings.php') ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/20' : 'text-slate-400 hover:bg-slate-800 hover:text-white'; ?>">
                <i class="fa-solid fa-calendar-check w-5"></i> Đơn đặt phòng
            </a>
        </nav>

    <div class="pt-6 border-t border-slate-800">
        <a href="login.php" class="flex items-center gap-3 p-3 text-slate-500 hover:text-red-400 transition">
            <i class="fa-solid fa-arrow-right-from-bracket w-5"></i> Đăng xuất
        </a>
    </div>
</aside>