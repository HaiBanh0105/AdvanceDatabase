<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header("Location: login.php");
    exit();
}
require_once '../dao/DAO.php';

// Lấy tất cả Đơn đặt phòng kèm thông tin Khách hàng và Hạng phòng
$sql = "
    SELECT b.*, u.email, ud.full_name, u.phone as user_phone, r.room_number, rt.name as type_name
    FROM Booking b
    LEFT JOIN `User` u ON b.user_id = u.user_id
    LEFT JOIN User_detail ud ON u.user_id = ud.user_id
    JOIN Booking_detail bd ON b.booking_id = bd.booking_id
    JOIN Room r ON bd.room_id = r.room_id
    JOIN Room_types rt ON r.type_id = rt.type_id
    ORDER BY b.booking_id DESC
";
$bookings = db_query($sql);

$room_types = db_query("SELECT * FROM Room_types");
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý Đơn đặt phòng - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-slate-50 flex h-screen overflow-hidden">
    <?php include 'sidebar_admin.php'; ?>

    <main class="flex-1 overflow-y-auto p-4 md:p-8">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-4">
            <div>
                <h1 class="text-2xl font-bold text-slate-800">Quản lý Đơn đặt phòng</h1>
                <p class="text-slate-500 text-sm">Theo dõi và cập nhật trạng thái đơn của khách hàng.</p>
            </div>
            <button onclick="document.getElementById('manualBookingModal').classList.remove('hidden')" class="bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-2.5 rounded-xl font-bold shadow-lg shadow-indigo-200 transition flex items-center gap-2">
                <i class="fa-solid fa-plus"></i> Tạo đơn thủ công
            </button>
        </div>

    <!-- Toast Messages -->
    <div id="toast-container" class="fixed top-24 right-6 z-[100] flex flex-col gap-3 pointer-events-none">
        <?php if (isset($_GET['error']) && $_GET['error'] == 'no_room'): ?>
            <div class="toast-alert p-4 bg-red-100 text-red-600 border border-red-200 rounded-2xl text-sm font-bold flex items-center gap-3 shadow-lg transition-all duration-500">
                <i class="fa-solid fa-circle-exclamation text-lg"></i> Lỗi: Không còn phòng trống cho thời gian này!
            </div>
        <?php elseif (isset($_GET['msg']) && $_GET['msg'] == 'booking_created'): ?>
            <div class="toast-alert p-4 bg-emerald-100 text-emerald-600 border border-emerald-200 rounded-2xl text-sm font-bold flex items-center gap-3 shadow-lg transition-all duration-500">
                <i class="fa-solid fa-circle-check text-lg"></i> Tạo đơn thủ công thành công!
            </div>
        <?php endif; ?>
    </div>

        <!-- Bảng hiển thị Đơn -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
            <table class="w-full text-left">
                <thead class="bg-slate-50 border-b border-slate-100">
                    <tr class="text-slate-500 text-[10px] uppercase tracking-widest font-bold">
                        <th class="px-6 py-4">Mã Đơn</th>
                        <th class="px-6 py-4">Khách hàng</th>
                        <th class="px-6 py-4">Phòng</th>
                        <th class="px-6 py-4">Thời gian lưu trú</th>
                        <th class="px-6 py-4">Tổng tiền</th>
                        <th class="px-6 py-4 text-center">Trạng thái (Action)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-sm">
                    <?php foreach ($bookings as $b): ?>
                    <tr class="hover:bg-slate-50 transition">
                        <td class="px-6 py-4 font-black text-indigo-600">#BK-<?php echo str_pad($b['booking_id'], 4, '0', STR_PAD_LEFT); ?></td>
                        <td class="px-6 py-4">
                            <?php if ($b['user_id']): ?>
                                <p class="font-bold text-slate-800"><?php echo htmlspecialchars($b['full_name'] ?? 'Khách chưa cập nhật tên'); ?></p>
                                <p class="text-xs text-slate-400"><?php echo htmlspecialchars($b['user_phone'] ?? $b['email']); ?></p>
                            <?php else: ?>
                                <p class="font-bold text-slate-800"><?php echo htmlspecialchars($b['guest_name']); ?> <span class="text-indigo-500 text-[10px] uppercase">(Vãng lai)</span></p>
                                <p class="text-xs text-slate-400"><?php echo htmlspecialchars($b['guest_phone']); ?> - CCCD: <?php echo htmlspecialchars($b['guest_cccd']); ?></p>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4">
                            <p class="font-bold text-slate-800"><?php echo htmlspecialchars($b['room_number']); ?></p>
                            <p class="text-xs text-slate-400 line-clamp-1"><?php echo htmlspecialchars($b['type_name']); ?></p>
                        </td>
                        <td class="px-6 py-4 text-slate-600">
                            <div class="text-xs">
                                <div><span class="font-bold">IN:</span> <?php echo date('d/m/Y H:i', strtotime($b['check_in'])); ?></div>
                                <div><span class="font-bold">OUT:</span> <?php echo date('d/m/Y H:i', strtotime($b['check_out'])); ?></div>
                            </div>
                        </td>
                        <td class="px-6 py-4 font-bold text-slate-800"><?php echo number_format($b['total_price'], 0, ',', '.'); ?>đ</td>
                        <td class="px-6 py-4 text-center">
                        <?php if ($b['status'] == 'pending'): ?>
                            <form action="../actions/process_admin_booking.php" method="POST" class="inline-flex gap-2">
                                <input type="hidden" name="action" value="update_status">
                                <input type="hidden" name="booking_id" value="<?php echo $b['booking_id']; ?>">
                                <button type="submit" name="status" value="confirmed" onclick="return confirm('Xác nhận đơn này?')" class="bg-blue-100 text-blue-700 px-3 py-1.5 rounded-lg text-[10px] uppercase tracking-wider font-bold hover:bg-blue-200 transition shadow-sm">Xác nhận</button>
                                <button type="submit" name="status" value="cancelled" onclick="return confirm('Hủy đơn này?')" class="bg-red-100 text-red-700 px-3 py-1.5 rounded-lg text-[10px] uppercase tracking-wider font-bold hover:bg-red-200 transition shadow-sm">Hủy</button>
                            </form>
                        <?php elseif ($b['status'] == 'confirmed'): ?>
                            <form action="../actions/process_admin_booking.php" method="POST" class="inline-flex">
                                <input type="hidden" name="action" value="update_status">
                                <input type="hidden" name="booking_id" value="<?php echo $b['booking_id']; ?>">
                                <button type="submit" name="status" value="checked_in" onclick="return confirm('Khách đã đến nhận phòng (Check-in)?')" class="bg-purple-100 text-purple-700 px-3 py-1.5 rounded-lg text-[10px] uppercase tracking-wider font-bold hover:bg-purple-200 transition shadow-sm"><i class="fa-solid fa-key mr-1"></i> Check-in</button>
                            </form>
                        <?php elseif ($b['status'] == 'checked_in'): ?>
                            <form action="../actions/process_admin_booking.php" method="POST" class="inline-flex">
                                <input type="hidden" name="action" value="update_status">
                                <input type="hidden" name="booking_id" value="<?php echo $b['booking_id']; ?>">
                                <button type="submit" name="status" value="completed" onclick="return confirm('Hoàn thành đơn đặt phòng này?')" class="bg-emerald-100 text-emerald-700 px-3 py-1.5 rounded-lg text-[10px] uppercase tracking-wider font-bold hover:bg-emerald-200 transition shadow-sm"><i class="fa-solid fa-check-double mr-1"></i> Hoàn tất</button>
                            </form>
                        <?php elseif ($b['status'] == 'completed'): ?>
                            <span class="px-3 py-1.5 rounded-lg text-[10px] uppercase tracking-wider font-bold bg-slate-100 text-emerald-600"><i class="fa-solid fa-check mr-1"></i> Hoàn thành</span>
                        <?php elseif ($b['status'] == 'cancelled'): ?>
                            <span class="px-3 py-1.5 rounded-lg text-[10px] uppercase tracking-wider font-bold bg-slate-100 text-red-600"><i class="fa-solid fa-xmark mr-1"></i> Đã hủy</span>
                        <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>

    <!-- Modal Tạo Đơn thủ công -->
    <div id="manualBookingModal" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-[2.5rem] w-full max-w-lg shadow-2xl overflow-hidden animate-in zoom-in duration-300">
            <div class="p-6 border-b border-slate-100 flex justify-between items-center bg-slate-50/50">
                <h3 class="font-bold text-lg text-slate-800 uppercase tracking-tight">Tạo Đơn Thủ Công</h3>
                <button onclick="document.getElementById('manualBookingModal').classList.add('hidden')" class="text-slate-400 hover:text-slate-600"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <form action="../actions/process_admin_booking.php" method="POST" class="p-8 space-y-4">
                <input type="hidden" name="action" value="create_manual">
                <div class="grid grid-cols-2 gap-4">
                    <div class="col-span-2">
                        <label class="block text-[10px] font-black text-slate-400 uppercase mb-2">Họ tên Khách hàng (Khách vãng lai)</label>
                        <input type="text" name="guest_name" required class="w-full px-5 py-3 bg-slate-50 border border-slate-200 rounded-2xl focus:ring-2 focus:ring-indigo-500 outline-none transition-all">
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-slate-400 uppercase mb-2">Số điện thoại</label>
                        <input type="text" name="guest_phone" required class="w-full px-5 py-3 bg-slate-50 border border-slate-200 rounded-2xl focus:ring-2 focus:ring-indigo-500 outline-none transition-all">
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-slate-400 uppercase mb-2">Số CCCD</label>
                        <input type="text" name="guest_cccd" required class="w-full px-5 py-3 bg-slate-50 border border-slate-200 rounded-2xl focus:ring-2 focus:ring-indigo-500 outline-none transition-all">
                    </div>
                </div>
                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase mb-2">Hạng phòng muốn đặt</label>
                    <select name="type_id" class="w-full px-5 py-3 bg-slate-50 border border-slate-200 rounded-2xl focus:ring-2 focus:ring-indigo-500 outline-none">
                        <?php foreach ($room_types as $rt): ?>
                            <option value="<?php echo $rt['type_id']; ?>"><?php echo htmlspecialchars($rt['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[10px] font-black text-slate-400 uppercase mb-2">Giờ Check-in</label>
                        <input type="datetime-local" name="check_in" required class="w-full px-5 py-3 bg-slate-50 border border-slate-200 rounded-2xl focus:ring-2 focus:ring-indigo-500 outline-none">
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-slate-400 uppercase mb-2">Giờ Check-out</label>
                        <input type="datetime-local" name="check_out" required class="w-full px-5 py-3 bg-slate-50 border border-slate-200 rounded-2xl focus:ring-2 focus:ring-indigo-500 outline-none">
                    </div>
                </div>
                <button type="submit" class="w-full mt-4 px-4 py-4 bg-indigo-600 text-white rounded-2xl font-bold shadow-lg shadow-indigo-100 hover:bg-indigo-700 transition">Tạo Đơn Đặt Phòng</button>
            </form>
        </div>
    </div>

    <script>
        // Tự động ẩn thông báo Toast sau 4 giây
        setTimeout(() => { document.querySelectorAll('.toast-alert').forEach(el => { el.style.opacity = '0'; setTimeout(() => el.remove(), 500); }); }, 4000);
    </script>
</body>
</html>