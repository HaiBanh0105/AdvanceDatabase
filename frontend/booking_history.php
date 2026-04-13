<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
require_once '../dao/room_dao.php';

$user_id = $_SESSION['user_id'];

// Lấy dữ liệu ảnh từ MongoDB
$mongo_images = room_image_get_all();

// Lấy tất cả đơn đặt phòng của User hiện tại kèm thông tin phòng đại diện (lấy phòng đầu tiên trong đơn)
$sql = "SELECT b.booking_id, b.booking_date, b.check_in_planned as check_in, b.check_out_planned as check_out, b.total_price, 'Pay at Hotel' as payment_method, b.booking_status as status,
            (SELECT TOP 1 rt.name FROM Booking_detail bd JOIN Room r ON bd.room_id = r.room_id JOIN Room_types rt ON r.type_id = rt.type_id WHERE bd.booking_id = b.booking_id) as room_name,
            (SELECT TOP 1 rt.type_id FROM Booking_detail bd JOIN Room r ON bd.room_id = r.room_id JOIN Room_types rt ON r.type_id = rt.type_id WHERE bd.booking_id = b.booking_id) as type_id
        FROM Booking b 
        JOIN Account a ON b.customer_id = a.customer_id
        WHERE a.account_id = ? 
        ORDER BY b.booking_date DESC";
$bookings = db_query($sql, $user_id);
?>
<!DOCTYPE html>
<!-- Giao diện Lịch sử đặt phòng -->
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lịch sử đặt phòng - Grand Horizon</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body class="bg-slate-50">

    <?php include 'navbar_customer.php'; ?>

    <main class="max-w-7xl mx-auto px-4 py-24">
        <div class="mb-10">
            <h1 class="text-3xl font-black text-slate-800 tracking-tight">Lịch sử đặt phòng</h1>
            <p class="text-slate-500 mt-2 text-sm">Xem và quản lý tất cả các đơn đặt phòng của bạn tại Grand Horizon.
            </p>
        </div>

        <div class="space-y-6">

            <?php if (empty($bookings)): ?>
            <div class="bg-white rounded-[2rem] border border-slate-100 shadow-sm p-12 text-center">
                <div class="inline-flex items-center justify-center w-20 h-20 bg-slate-50 rounded-full mb-4">
                    <i class="fa-solid fa-suitcase-rolling text-3xl text-slate-300"></i>
                </div>
                <h2 class="text-xl font-bold text-slate-700 mb-2">Chưa có chuyến đi nào</h2>
                <p class="text-slate-500 mb-6">Bạn chưa thực hiện bất kỳ đơn đặt phòng nào tại Grand Horizon.</p>
                <a href="customer_index.php#rooms"
                    class="inline-block bg-indigo-600 text-white px-8 py-3 rounded-2xl font-bold hover:bg-indigo-700 shadow-lg transition">Khám
                    phá phòng ngay</a>
            </div>
            <?php else: ?>
            <?php foreach ($bookings as $b):
                ?>
            <div
                class="bg-white rounded-[2rem] border border-slate-100 shadow-sm overflow-hidden hover:shadow-md transition <?php echo (strtolower($b['status']) === 'cancelled') ? 'opacity-75 grayscale-[0.5]' : ''; ?>">
                <div class="p-6 md:p-8 flex flex-col md:flex-row gap-6 items-center">
                    <div class="w-full md:w-48 h-32 rounded-2xl bg-slate-100 overflow-hidden shrink-0">
                        <?php
                                $img = 'https://images.unsplash.com/photo-1611892440504-42a792e24d32?auto=format&fit=crop&w=400';
                                if (!empty($b['type_id']) && isset($mongo_images[$b['type_id']])) {
                                    $img = "data:" . $mongo_images[$b['type_id']]['mime'] . ";base64," . $mongo_images[$b['type_id']]['base64'];
                                }
                                ?>
                        <img src="<?php echo $img; ?>" class="w-full h-full object-cover">
                    </div>

                    <div class="flex-1 space-y-2 text-center md:text-left">
                        <div class="flex flex-wrap justify-center md:justify-start items-center gap-2 mb-1">
                            <span
                                class="text-[10px] font-black uppercase tracking-widest <?php echo (strtolower($b['status']) === 'cancelled') ? 'text-slate-400' : 'text-indigo-600'; ?>">Mã
                                đơn: #BK-<?php echo str_pad($b['booking_id'], 4, '0', STR_PAD_LEFT); ?></span>

                            <?php if (strtolower($b['status']) === 'cancelled'): ?>
                            <span
                                class="px-2 py-0.5 rounded-full text-[9px] font-bold bg-red-100 text-red-600 uppercase">Đã
                                hủy</span>
                            <?php elseif (strtolower($b['status']) === 'pending'): ?>
                            <span
                                class="px-2 py-0.5 rounded-full text-[9px] font-bold bg-amber-100 text-amber-600 uppercase">Chờ
                                xử lý</span>
                            <?php elseif (strtolower($b['status']) === 'confirmed'): ?>
                            <span
                                class="px-2 py-0.5 rounded-full text-[9px] font-bold bg-blue-100 text-blue-600 uppercase">Đã
                                xác nhận</span>
                            <?php elseif (strtolower($b['status']) === 'checked-in'): ?>
                            <span
                                class="px-2 py-0.5 rounded-full text-[9px] font-bold bg-indigo-100 text-indigo-600 uppercase">Đang
                                ở</span>
                            <?php elseif (strtolower($b['status']) === 'completed'): ?>
                            <span
                                class="px-2 py-0.5 rounded-full text-[9px] font-bold bg-emerald-100 text-emerald-600 uppercase">Đã
                                hoàn thành</span>
                            <?php endif; ?>
                        </div>
                        <h3
                            class="text-xl font-bold <?php echo (strtolower($b['status']) === 'cancelled') ? 'text-slate-400' : 'text-slate-800'; ?> uppercase tracking-wide">
                            <?php echo htmlspecialchars($b['room_name'] ?? 'Chưa phân phòng'); ?></h3>
                        <div
                            class="flex flex-wrap justify-center md:justify-start gap-x-6 gap-y-1 text-sm text-slate-500 font-medium">
                            <p><i class="fa-solid fa-calendar-day mr-2 text-indigo-400"></i>Nhận: <span
                                    class="text-slate-700"><?php echo date('d/m/Y', strtotime($b['check_in'])); ?></span>
                            </p>
                            <p><i class="fa-solid fa-calendar-check mr-2 text-indigo-400"></i>Trả: <span
                                    class="text-slate-700"><?php echo date('d/m/Y', strtotime($b['check_out'])); ?></span>
                            </p>
                        </div>
                    </div>

                    <div
                        class="w-full md:w-auto text-center md:text-right border-t md:border-t-0 md:border-l border-slate-100 pt-6 md:pt-0 md:pl-10 space-y-3">
                        <div>
                            <p
                                class="text-[10px] font-bold <?php echo (strtolower($b['status']) === 'cancelled') ? 'text-slate-300' : 'text-slate-400'; ?> uppercase leading-none mb-1">
                                Tổng thanh toán</p>
                            <p
                                class="text-2xl font-black <?php echo (strtolower($b['status']) === 'cancelled') ? 'text-slate-300' : 'text-slate-900'; ?> uppercase">
                                <?php echo number_format($b['total_price'], 0, ',', '.'); ?>đ</p>
                            <p class="text-[10px] font-bold text-emerald-500 uppercase mt-1">
                                <?php echo htmlspecialchars($b['payment_method']); ?></p>
                        </div>
                        <div class="flex flex-col gap-2">
                            <button type="button" onclick="openViewHistoryModal(<?php echo $b['booking_id']; ?>)"
                                class="bg-indigo-600 text-white px-6 py-2.5 rounded-xl text-xs font-bold hover:bg-indigo-700 transition shadow-lg shadow-indigo-100">Xem
                                chi tiết</button>
                            <?php if (in_array(strtolower($b['status']), ['pending', 'confirmed'])): ?>
                            <button onclick="confirmCancel('<?php echo $b['booking_id']; ?>')"
                                class="text-slate-400 hover:text-red-600 text-[10px] font-bold uppercase transition">Hủy
                                đặt phòng</button>
                            <?php else: ?>
                            <a href="customer_index.php#searchBar"
                                class="mt-1 text-center bg-slate-100 text-slate-500 px-6 py-2 rounded-xl text-[10px] font-bold hover:bg-indigo-600 hover:text-white transition">Đặt
                                lại phòng này</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>

        </div>
    </main>

    <!-- MODAL XEM CHI TIẾT ĐƠN -->
    <?php include 'Modals/viewBookingHistory_modal.php'; ?>

    <script src="../assets/js/toast.js"></script>
    <script>
    function confirmCancel(id) {
        if (confirm("Bạn có chắc chắn muốn hủy đơn đặt phòng #" + id +
                " không? Số tiền sẽ được hoàn về ví dựa trên chính sách của chúng tôi.")) {
            // Logic xử lý hủy phòng tại đây
            showToast("Yêu cầu hủy đơn #" + id + " đã được gửi đi.", "info");

            const formData = new FormData();
            formData.append('action', 'cancel_booking');
            formData.append('booking_id', id);

            fetch('../actions/process_booking.php', {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    showToast(data.message, data.status);
                    if (data.status === 'success') {
                        setTimeout(() => window.location.reload(), 1500);
                    }
                })
                .catch(err => showToast('Lỗi kết nối máy chủ!', 'error'));
        }
    }
    </script>

</body>

</html>