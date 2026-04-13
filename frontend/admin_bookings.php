<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header("Location: login.php");
    exit();
}

require_once '../dao/booking_dao.php';
require_once '../dao/room_dao.php';

$error_msg = '';
$bookings = [];
$room_types = [];
$all_rooms = [];
$active_bookings = [];

try {
    // Lấy dữ liệu từ Database
    $search = trim($_GET['search'] ?? '');
    $bookings = booking_get_all_admin($search);
    if (!is_array($bookings)) $bookings = [];

    $room_types = room_type_get_all('price ASC');
    if (!is_array($room_types)) $room_types = [];

    $all_rooms = room_get_all_with_types();
    if (!is_array($all_rooms)) $all_rooms = [];

    $active_bookings = booking_get_active();
    if (!is_array($active_bookings)) $active_bookings = [];
} catch (Exception $e) {
    // Bắt lỗi SQL nếu có để hiển thị ra màn hình thay vì trắng trang
    $error_msg = $e->getMessage();
}

$active_map = [];
foreach ($active_bookings as $ab) {
    $active_map[$ab['room_id']] = $ab;
}

$grouped_rooms = [];
foreach ($all_rooms as $r) {
    $grouped_rooms[$r['type_name']][] = $r;
}

$type_capacity_map = [];
foreach ($room_types as $rt) {
    $type_capacity_map[$rt['name']] = $rt['capacity'];
}

// Xác định tab mặc định
$default_tab = ($search !== '') ? 'listTab' : 'mapTab';
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý Đơn đặt phòng — Admin</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>

<body class="bg-slate-50 flex h-screen overflow-hidden">

    <?php include 'sidebar_admin.php'; ?>

    <main class="flex-1 overflow-y-auto p-4 md:p-8">

        <!-- Header -->
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
            <div>
                <h1 class="text-2xl font-bold text-slate-800">Quản lý Đơn đặt phòng</h1>
                <p class="text-slate-500 text-sm">Theo dõi và cập nhật trạng thái đơn của khách hàng.</p>
            </div>
        </div>

        <?php if ($error_msg): ?>
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded shadow-sm" role="alert">
            <p class="font-bold">Lỗi Cơ sở dữ liệu!</p>
            <p><?php echo htmlspecialchars($error_msg); ?></p>
        </div>
        <?php endif; ?>

        <!-- Tab bar -->
        <div class="flex gap-0 border-b border-slate-200 mb-6">
            <button id="btn-mapTab" class="tab-btn px-6 py-3 text-sm transition" onclick="switchTab('mapTab', this)">
                <i class="fa-solid fa-map-location-dot mr-2"></i>Sơ đồ phòng
            </button>
            <button id="btn-listTab" class="tab-btn px-6 py-3 text-sm transition" onclick="switchTab('listTab', this)">
                <i class="fa-solid fa-list-ul mr-2"></i>Quản lý Đơn đặt phòng
            </button>
        </div>

        <!-- TAB 1 -->
        <div id="mapTab" class="tab-content hidden">

            <div class="flex flex-wrap items-center gap-4 md:gap-6 mb-6 text-sm font-medium text-slate-600">
                <span class="flex items-center gap-2"><span
                        class="w-5 h-5 rounded-md bg-emerald-500 inline-block"></span> Phòng trống</span>
                <span class="flex items-center gap-2"><span class="w-5 h-5 rounded-md bg-red-500 inline-block"></span>
                    Đang có khách</span>
                <span class="flex items-center gap-2"><span class="w-5 h-5 rounded-md bg-amber-400 inline-block"></span>
                    Dọn dẹp</span>
                <span class="flex items-center gap-2"><span class="w-5 h-5 rounded-md bg-slate-300 inline-block"></span>
                    Bảo trì</span>
            </div>

            <?php foreach ($grouped_rooms as $type_name => $rooms): ?>
            <?php
                $total = count($rooms);
                $occupied = count(array_filter($rooms, fn($r) => isset($active_map[$r['room_id']]) || ($r['room_status'] ?? $r['status'] ?? '') === 'occupied'));
                $free = count(array_filter($rooms, fn($r) => !isset($active_map[$r['room_id']]) && ($r['room_status'] ?? $r['status'] ?? '') === 'available'));
                ?>

            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 mb-6 overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                    <div>
                        <h2 class="font-bold text-slate-800 text-base">
                            <?php echo htmlspecialchars($type_name ?? ''); ?>
                        </h2>
                        <p class="text-xs text-slate-400 mt-0.5">
                            Tổng: <strong><?= $total ?></strong> phòng ·
                            <span class="text-red-500 font-semibold"><?= $occupied ?> đang có khách</span> ·
                            <span class="text-emerald-600 font-semibold"><?= $free ?> trống</span>
                        </p>
                    </div>
                </div>

                <div class="p-6 flex flex-wrap gap-3">
                    <?php foreach ($rooms as $room): ?>
                    <?php
                            $rid = $room['room_id'];
                            $rnum = $room['room_number'];
                            $p_hr = $room['price_per_hour'] ?? 0;
                            $p_day = $room['price_per_day'] ?? 0;
                            $capacity = $room['capacity'] ?? $type_capacity_map[$type_name] ?? 2;
                            $occupied_info = $active_map[$rid] ?? null;
                            ?>

                    <?php if ($occupied_info): ?>
                    <?php
                                $data_js = htmlspecialchars(json_encode([
                                    'booking_id' => $occupied_info['booking_id'],
                                    'customer_name' => $occupied_info['customer_name'],
                                    'check_in' => $occupied_info['check_in'],
                                    'check_out' => $occupied_info['check_out'],
                                    'room_number' => $rnum,
                                    'type_name' => $type_name,
                                ]), ENT_QUOTES, 'UTF-8');
                                ?>
                    <button onclick="openViewBookingModal(<?php echo $data_js; ?>)" title="Đang có khách. Click để xem"
                        class="room-occupied w-16 h-16 bg-red-500 hover:bg-red-600 text-white font-bold text-sm rounded-xl flex flex-col items-center justify-center gap-0.5 shadow-md transition cursor-pointer">
                        <span><?= $rnum ?></span>
                        <i class="fa-solid fa-person-shelter text-xs opacity-80"></i>
                    </button>
                    <?php elseif (($room['room_status'] ?? $room['status'] ?? '') === 'occupied'): ?>
                    <div title="Đang có khách (Chưa ghi nhận trên hệ thống)"
                        class="room-occupied w-16 h-16 bg-red-500 text-white font-bold text-sm rounded-xl flex flex-col items-center justify-center gap-0.5 shadow-md cursor-not-allowed">
                        <span><?= $rnum ?></span>
                        <i class="fa-solid fa-person-shelter text-xs opacity-80"></i>
                    </div>
                    <?php elseif (($room['room_status'] ?? $room['status'] ?? '') === 'cleaning'): ?>
                    <form action="../actions/process_admin_booking.php" method="POST" class="inline"
                        onsubmit="return confirm('Xác nhận phòng đã sẵn sàng hoạt động?');">
                        <input type="hidden" name="action" value="mark_room_ready">
                        <input type="hidden" name="room_id" value="<?= $rid ?>">
                        <button type="submit" title="Đang dọn dẹp. Click để chuyển sang Sẵn sàng"
                            class="w-16 h-16 bg-amber-400 hover:bg-amber-500 text-white font-bold text-sm rounded-xl flex flex-col items-center justify-center gap-0.5 shadow-md transition cursor-pointer">
                            <span><?= $rnum ?></span>
                            <i class="fa-solid fa-broom text-xs"></i>
                        </button>
                    </form>
                    <?php elseif (($room['room_status'] ?? $room['status'] ?? '') === 'maintenance'): ?>
                    <div title="Đang bảo trì"
                        class="w-16 h-16 bg-slate-200 text-slate-400 font-bold text-sm rounded-xl flex flex-col items-center justify-center gap-0.5 cursor-not-allowed">
                        <span><?= $rnum ?></span>
                        <i class="fa-solid fa-wrench text-xs"></i>
                    </div>
                    <?php else: ?>
                    <button
                        onclick="openWalkinModal(<?= $rid ?>,'<?= $rnum ?>','<?= htmlspecialchars(addslashes($type_name)) ?>',<?= $p_hr ?>,<?= $p_day ?>, <?= $capacity ?>)"
                        title="Phòng trống. Click để nhận khách"
                        class="w-16 h-16 bg-emerald-500 hover:bg-emerald-600 text-white font-bold text-sm rounded-xl flex flex-col items-center justify-center gap-0.5 shadow-md transition cursor-pointer">
                        <span><?= $rnum ?></span>
                        <i class="fa-solid fa-plus text-xs opacity-80"></i>
                    </button>
                    <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- TAB 2 -->
        <div id="listTab" class="tab-content hidden">
            <div class="mb-4 flex flex-col md:flex-row gap-3 items-center">
                <div class="relative flex-1 w-full md:max-w-xs">
                    <i class="fa-solid fa-user absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
                    <input id="searchName" type="text" oninput="filterBookings()" placeholder="Tìm tên khách hàng..."
                        class="w-full pl-10 pr-4 py-2.5 bg-white border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-indigo-400 outline-none transition shadow-sm">
                </div>
                <div class="relative flex-1 w-full md:max-w-xs">
                    <i class="fa-solid fa-id-card absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
                    <input id="searchCCCD" type="text" value="<?= htmlspecialchars($search) ?>"
                        oninput="filterBookings()" placeholder="Tìm số CCCD..."
                        class="w-full pl-10 pr-4 py-2.5 bg-white border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-indigo-400 outline-none transition shadow-sm">
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                <table class="w-full text-left">
                    <thead class="bg-slate-50 border-b border-slate-100">
                        <tr class="text-slate-500 text-[10px] uppercase tracking-widest font-bold">
                            <th class="px-6 py-4">Mã Đơn</th>
                            <th class="px-6 py-4">Khách hàng</th>
                            <th class="px-6 py-4">Phòng</th>
                            <th class="px-6 py-4">Thời gian</th>
                            <th class="px-6 py-4 text-center">Trạng thái</th>
                            <th class="px-6 py-4 text-right">Hành động</th>
                        </tr>
                    </thead>
                    <tbody id="bookingTableBody" class="divide-y divide-slate-100 text-sm">
                        <?php if (empty($bookings)): ?>
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-slate-400">Không có đơn đặt phòng
                                nào.
                            </td>
                        </tr>
                        <?php endif; ?>
                        <?php foreach ($bookings as $b): ?>
                        <tr class="hover:bg-slate-50 transition">
                            <td class="px-6 py-4 font-black text-indigo-600">
                                #BK-<?= str_pad($b['booking_id'], 4, '0', STR_PAD_LEFT) ?></td>
                            <td class="px-6 py-4">
                                <p class="font-bold text-slate-800">
                                    <?= htmlspecialchars($b['full_name'] ?? $b['guest_name']) ?>
                                </p>
                                <p class="text-xs text-slate-400">
                                    SDT:
                                    <?= htmlspecialchars($b['user_phone'] ?? $b['guest_phone']) ?>
                                </p>
                                <p class="text-xs text-slate-400">
                                    CCCD: <?= htmlspecialchars($b['user_cccd'] ?? $b['guest_cccd']) ?>
                                </p>
                                <?php if (!empty($b['extra_guests_info'])): ?>
                                <div class="mt-2 pt-2 border-t border-slate-100">
                                    <p class="text-[10px] font-bold text-slate-400 uppercase">Khách đi cùng:</p>
                                    <p class="text-[11px] text-indigo-600 font-medium">
                                        <?= $b['extra_guests_info'] ?>
                                    </p>
                                </div>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4">
                                <p class="font-bold text-slate-800"><?= htmlspecialchars($b['room_number'] ?? '') ?>
                                </p>
                                <p class="text-xs text-slate-400"><?= htmlspecialchars($b['type_name'] ?? '') ?></p>
                            </td>
                            <td class="px-6 py-4 text-slate-600 text-xs">
                                <div><span class="font-bold">IN:</span>
                                    <?= date('d/m/Y H:i', strtotime($b['check_in'])) ?></div>
                                <?php if ($b['status'] === 'completed' || $b['status'] === 'cancelled'): ?>
                                <div><span class="font-bold">OUT:</span>
                                    <?= date('d/m/Y H:i', strtotime($b['check_out'])) ?></div>
                                <?php else: ?>
                                <div><span class="font-bold">OUT:</span> <span class="text-slate-400 italic">Chưa
                                        trả
                                        phòng</span></div>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <?php if ($b['status'] == 'pending'): ?>
                                <span
                                    class="px-2 py-1 rounded text-[10px] font-bold bg-amber-100 text-amber-600 uppercase">Chờ
                                    duyệt</span>
                                <?php elseif ($b['status'] == 'confirmed'): ?>
                                <span
                                    class="px-2 py-1 rounded text-[10px] font-bold bg-blue-100 text-blue-600 uppercase">Đã
                                    xác nhận</span>
                                <?php elseif ($b['status'] == 'checked-in'): ?>
                                <span
                                    class="px-2 py-1 rounded text-[10px] font-bold bg-indigo-100 text-indigo-600 uppercase">Đang
                                    ở</span>
                                <?php elseif ($b['status'] == 'completed'): ?>
                                <span
                                    class="px-2 py-1 rounded text-[10px] font-bold bg-emerald-100 text-emerald-600 uppercase">Hoàn
                                    thành</span>
                                <?php elseif ($b['status'] == 'cancelled'): ?>
                                <span
                                    class="px-2 py-1 rounded text-[10px] font-bold bg-red-100 text-red-600 uppercase">Đã
                                    hủy</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 text-center space-x-1 whitespace-nowrap">
                                <?php if ($b['status'] == 'pending'): ?>
                                <form action="../actions/process_admin_booking.php" method="POST" class="inline">
                                    <input type="hidden" name="action" value="update_status">
                                    <input type="hidden" name="booking_id" value="<?= $b['booking_id'] ?>">
                                    <button type="submit" name="status" value="confirmed"
                                        class="bg-blue-50 text-blue-600 px-3 py-1.5 rounded-lg text-[10px] uppercase font-bold hover:bg-blue-100 transition shadow-sm"
                                        title="Duyệt đơn này"><i class="fa-solid fa-check"></i> Duyệt</button>
                                    <button type="submit" name="status" value="cancelled"
                                        onclick="return confirm('Bạn có chắc chắn muốn hủy đơn này?');"
                                        class="bg-red-50 text-red-600 px-3 py-1.5 rounded-lg text-[10px] uppercase font-bold hover:bg-red-100 transition shadow-sm"
                                        title="Hủy đơn"><i class="fa-solid fa-xmark"></i> Hủy</button>
                                </form>
                                <?php elseif ($b['status'] == 'confirmed'): ?>
                                <form action="../actions/process_admin_booking.php" method="POST" class="inline">
                                    <input type="hidden" name="action" value="update_status">
                                    <input type="hidden" name="booking_id" value="<?= $b['booking_id'] ?>">
                                    <button type="submit" name="status" value="checked_in"
                                        class="bg-indigo-50 text-indigo-600 px-3 py-1.5 rounded-lg text-[10px] uppercase font-bold hover:bg-indigo-100 transition shadow-sm"
                                        title="Khách đã nhận phòng"><i class="fa-solid fa-key"></i> Nhận
                                        phòng</button>
                                    <button type="submit" name="status" value="cancelled"
                                        onclick="return confirm('Bạn có chắc chắn muốn hủy đơn này?');"
                                        class="bg-red-50 text-red-600 px-3 py-1.5 rounded-lg text-[10px] uppercase font-bold hover:bg-red-100 transition shadow-sm"
                                        title="Hủy đơn"><i class="fa-solid fa-xmark"></i> Hủy</button>
                                </form>
                                <?php elseif ($b['status'] == 'checked-in'): ?>
                                <?php
                                        $data_js = htmlspecialchars(json_encode([
                                            'booking_id' => $b['booking_id'],
                                            'customer_name' => $b['full_name'] ?? $b['guest_name'] ?? 'Khách vãng lai',
                                            'check_in' => $b['check_in'],
                                            'check_out' => $b['check_out'],
                                            'room_number' => $b['room_number'] ?? '',
                                            'type_name' => $b['type_name'] ?? '',
                                        ]), ENT_QUOTES, 'UTF-8');
                                        ?>
                                <button type="button" onclick="openViewBookingModal(<?= $data_js ?>)"
                                    class="bg-emerald-50 text-emerald-600 px-3 py-1.5 rounded-lg text-[10px] uppercase font-bold hover:bg-emerald-100 transition shadow-sm"
                                    title="Tiến hành thanh toán & Trả phòng"><i class="fa-solid fa-money-bill-wave"></i>
                                    Trả phòng</button>
                                <?php endif; ?>

                                <button type="button" onclick="openInvoiceModal(<?= $b['booking_id'] ?>)"
                                    class="bg-slate-100 text-slate-600 px-3 py-1.5 rounded-lg text-[10px] uppercase font-bold hover:bg-slate-200 transition shadow-sm"
                                    title="Xem chi tiết Hóa đơn"><i class="fa-solid fa-file-invoice"></i></button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </main>

    <!-- MODAL WALKIN (ĐẶT TRỰC TIẾP) -->
    <?php
    include 'Modals/walkin_modal.php';
    ?>

    <!-- MODAL XEM THÔNG TIN ĐƠN (CHECK-OUT) -->
    <?php
    include 'Modals/viewBooking_modal.php';
    ?>

    <!-- MODAL THANH TOÁN HOÀN TẤT -->
    <?php
    include 'Modals/checkout_modal.php';
    ?>

    <!-- MODAL XEM CHI TIẾT ĐƠN / HÓA ĐƠN -->
    <?php
    include 'Modals/invoice_modal.php';
    ?>

    <script src="../assets/js/toast.js"></script>
    <script src="../assets/js/admin_bookings.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', () => {
        const urlParams = new URLSearchParams(window.location.search);
        let activeTab = urlParams.get('search') ? 'listTab' : (sessionStorage.getItem(
            'adminBookingCurrentTab') || '<?php echo $default_tab; ?>');

        const btnMap = document.getElementById('btn-mapTab');
        const btnList = document.getElementById('btn-listTab');

        if (activeTab === 'listTab') {
            switchTab('listTab', btnList);
        } else {
            switchTab('mapTab', btnMap);
        }

        btnMap.addEventListener('click', () => sessionStorage.setItem('adminBookingCurrentTab', 'mapTab'));
        btnList.addEventListener('click', () => sessionStorage.setItem('adminBookingCurrentTab', 'listTab'));

        if (urlParams.get('msg') === 'booking_created') showToast('Giao phòng thành công!', 'success');
        if (urlParams.get('msg') === 'checkout_success') showToast('Trả phòng và thu tiền hoàn tất!',
            'success');
        if (urlParams.get('msg') === 'status_updated') showToast('Cập nhật trạng thái thành công!', 'success');
        if (urlParams.get('msg') === 'room_ready') showToast('Phòng đã dọn dẹp xong và sẵn sàng đón khách!',
            'success');
        if (urlParams.get('error') === 'room_occupied') showToast(
            'Lỗi: Phòng này đã bị đặt trong khoảng thời gian vừa chọn!', 'error');
        if (urlParams.get('error') === 'duplicate_cccd') showToast(
            'Lỗi: Số CCCD không được trùng lặp trong cùng một phòng!', 'error');
        if (urlParams.get('error') === 'cccd_name_mismatch') showToast(
            'Lỗi: CCCD đã tồn tại trong hệ thống nhưng sai tên Khách hàng!', 'error');
        if (urlParams.get('error') === 'duplicate_phone') showToast(
            'Lỗi: Số điện thoại này đã được sử dụng bởi khách hàng khác!', 'error');
        if (urlParams.get('error') === 'missing_cccd') showToast(
            'Lỗi: Bắt buộc phải nhập cả Tên và số CCCD cho tất cả khách hàng!', 'error');
        if (urlParams.get('error') === 'missing_guest') showToast(
            'Lỗi: Vui lòng nhập thông tin của ít nhất 1 khách hàng!', 'error');
        if (urlParams.get('error') === 'booking_failed') showToast(
            'Lỗi: Giao phòng thất bại do lỗi CSDL, vui lòng thử lại!', 'error');
    });
    </script>

</body>

</html>