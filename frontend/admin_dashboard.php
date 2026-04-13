<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header("Location: login.php");
    exit();
}
require_once '../dao/room_dao.php';
require_once '../dao/booking_dao.php';

// 1. Báo cáo tổng quan
$total_revenue = db_query_value("SELECT SUM(total_price) FROM Booking WHERE booking_status = 'completed'") ?: 0;
$total_bookings = db_query_value("SELECT COUNT(*) FROM Booking") ?: 0;
$today_bookings = db_query_value("SELECT COUNT(*) FROM Booking WHERE CAST(booking_date AS DATE) = CAST(GETDATE() AS DATE)") ?: 0;

$total_rooms = db_query_value("SELECT COUNT(*) FROM Room") ?: 0;
$occupied_rooms = db_query_value("SELECT COUNT(*) FROM Room WHERE status = 'occupied'") ?: 0;
$occupancy_rate = $total_rooms > 0 ? round(($occupied_rooms / $total_rooms) * 100) : 0;

// Lấy doanh thu tháng này
$this_month_revenue = db_query_value("SELECT SUM(total_price) FROM Booking WHERE booking_status = 'completed' AND MONTH(booking_date) = MONTH(GETDATE()) AND YEAR(booking_date) = YEAR(GETDATE())") ?: 0;

// 2. Danh sách Hạng phòng
$room_types = room_type_get_all('price ASC');

// 3. Danh sách Phòng
$rooms = db_query("SELECT room_number, status FROM Room ORDER BY room_number ASC");

?>
<!DOCTYPE html>
<!-- Giao diện Tổng quan Admin -->
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Grand Horizon</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body class="bg-slate-50 flex">

    <?php include 'sidebar_admin.php'; ?>

    <main class="flex-1 p-8 overflow-y-auto">
        <header class="flex justify-between items-center mb-10">
            <div>
                <h1 class="text-2xl font-bold text-slate-800">Báo cáo doanh thu</h1>
                <p class="text-slate-500 text-sm">Chào mừng trở lại, Quản trị viên.</p>
            </div>
            <div class="flex gap-4">
                <button
                    class="bg-white border border-slate-200 px-4 py-2 rounded-lg text-sm font-semibold shadow-sm hover:bg-slate-50">
                    <i class="fa-solid fa-download mr-2"></i> Xuất PDF
                </button>
                <img src="https://ui-avatars.com/api/?name=Admin"
                    class="w-10 h-10 rounded-full border-2 border-indigo-500">
            </div>
        </header>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10">
            <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
                <p class="text-slate-500 text-xs font-bold uppercase tracking-wider mb-1">Tổng doanh thu</p>
                <h3 class="text-3xl font-black text-slate-900"><?= number_format($total_revenue, 0, ',', '.') ?>đ</h3>
                <span class="text-emerald-500 text-xs font-bold">Tháng này:
                    <?= number_format($this_month_revenue, 0, ',', '.') ?>đ</span>
            </div>
            <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
                <p class="text-slate-500 text-xs font-bold uppercase tracking-wider mb-1">Đơn đặt phòng</p>
                <h3 class="text-3xl font-black text-slate-900"><?= $total_bookings ?></h3>
                <span class="text-indigo-500 text-xs font-bold"><?= $today_bookings ?> đơn mới hôm nay</span>
            </div>
            <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
                <p class="text-slate-500 text-xs font-bold uppercase tracking-wider mb-1">Công suất phòng</p>
                <h3 class="text-3xl font-black text-slate-900"><?= $occupancy_rate ?>%</h3>
                <div class="w-full bg-slate-100 h-2 rounded-full mt-3">
                    <div class="bg-indigo-600 h-2 rounded-full" style="width: <?= $occupancy_rate ?>%"></div>
                </div>
            </div>
        </div>

        <div id="room-types" class="bg-white rounded-2xl border border-slate-200 shadow-sm mb-10 overflow-hidden">
            <div class="p-6 border-b border-slate-100 flex justify-between items-center bg-slate-50/50">
                <h2 class="font-bold text-slate-800">Bảng giá & Loại phòng</h2>
                <button class="text-indigo-600 text-sm font-bold hover:underline">Chỉnh sửa tất cả</button>
            </div>
            <table class="w-full text-left">
                <thead
                    class="bg-slate-50 text-slate-400 text-[10px] uppercase font-bold tracking-widest border-b border-slate-100">
                    <tr>
                        <th class="px-6 py-4">Loại phòng</th>
                        <th class="px-6 py-4">Sức chứa</th>
                        <th class="px-6 py-4">Giá hiện tại (MONEY)</th>
                        <th class="px-6 py-4">Trạng thái</th>
                        <th class="px-6 py-4 text-right">Hành động</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    <?php if (empty($room_types)): ?>
                        <tr>
                            <td colspan="5" class="px-6 py-4 text-center text-slate-400">Chưa có hạng phòng nào</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($room_types as $rt): ?>
                            <tr class="hover:bg-slate-50/50 transition">
                                <td class="px-6 py-4 font-bold text-slate-700"><?= htmlspecialchars($rt['name']) ?></td>
                                <td class="px-6 py-4 text-slate-500"><?= $rt['capacity'] ?> người</td>
                                <td class="px-6 py-4 font-bold text-indigo-600 uppercase">
                                    <?= number_format($rt['price_per_day'], 0, ',', '.') ?>đ</td>
                                <td class="px-6 py-4">
                                    <span
                                        class="bg-emerald-100 text-emerald-600 px-3 py-1 rounded-full text-[10px] font-bold">ĐANG
                                        BÁN</span>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <a href="admin_rooms.php?tab=types"
                                        class="text-slate-400 hover:text-indigo-600 transition p-2"><i
                                            class="fa-solid fa-pen"></i></a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div id="rooms" class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="p-6 border-b border-slate-100">
                <h2 class="font-bold text-slate-800">Trạng thái phòng chi tiết (Room Status)</h2>
            </div>
            <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4 p-6">
                <?php foreach ($rooms as $r): ?>
                    <?php
                    $status_class = '';
                    $status_text = '';
                    if ($r['status'] == 'available') {
                        $status_class = 'bg-emerald-100 text-emerald-600';
                        $status_text = 'Sẵn sàng';
                    } elseif ($r['status'] == 'occupied') {
                        $status_class = 'bg-red-100 text-red-600';
                        $status_text = 'Đang ở';
                    } elseif ($r['status'] == 'cleaning') {
                        $status_class = 'bg-amber-100 text-amber-600';
                        $status_text = 'Dọn dẹp';
                    } else {
                        $status_class = 'bg-slate-100 text-slate-600';
                        $status_text = 'Bảo trì';
                    }
                    ?>
                    <div
                        class="border border-slate-200 rounded-xl p-4 text-center hover:border-indigo-500 transition cursor-pointer group <?= $r['status'] != 'available' ? 'bg-slate-50' : '' ?>">
                        <p class="text-xs font-bold text-slate-400 mb-1">PHÒNG</p>
                        <h4
                            class="text-xl font-black <?= $r['status'] == 'available' ? 'text-slate-800 group-hover:text-indigo-600' : 'text-slate-400' ?>">
                            <?= htmlspecialchars($r['room_number']) ?></h4>
                        <div
                            class="mt-2 inline-block <?= $status_class ?> px-2 py-0.5 rounded text-[9px] font-bold uppercase">
                            <?= $status_text ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </main>

    <script>
        // JS xử lý các tương tác nhanh
        console.log("Admin Dashboard Loaded");
    </script>
</body>

</html>