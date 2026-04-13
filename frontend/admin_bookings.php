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

            <div class="flex items-center gap-6 mb-6 text-sm font-medium text-slate-600">
                <span class="flex items-center gap-2"><span
                        class="w-5 h-5 rounded-md bg-emerald-500 inline-block"></span> Phòng trống</span>
                <span class="flex items-center gap-2"><span class="w-5 h-5 rounded-md bg-red-500 inline-block"></span>
                    Đang có khách</span>
                <span class="flex items-center gap-2"><span class="w-5 h-5 rounded-md bg-slate-300 inline-block"></span>
                    Bảo trì</span>
            </div>

            <?php foreach ($grouped_rooms as $type_name => $rooms): ?>
            <?php
                $total = count($rooms);
                $occupied = count(array_filter($rooms, fn($r) => isset($active_map[$r['room_id']])));
                $free = $total - $occupied;
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
                            $capacity = $room['capacity'] ?? 2;
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
                    <?php elseif ($room['room_status'] === 'maintenance'): ?>
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
                    <input id="searchCCCD" type="text" value="<?= htmlspecialchars($search) ?>" oninput="filterBookings()" placeholder="Tìm số CCCD..."
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
    <div id="walkinModal"
        class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 hidden items-center justify-center p-4">
        <div
            class="bg-white rounded-[2.5rem] w-full max-w-2xl shadow-2xl overflow-hidden max-h-[90vh] flex flex-col animate-in zoom-in duration-200">
            <div class="p-6 border-b border-slate-100 flex justify-between items-center bg-slate-50">
                <h3 class="font-bold text-lg text-slate-800 uppercase tracking-tight">Đăng ký Lưu trú — Phòng <span
                        id="wi_room_number" class="text-indigo-600"></span></h3>
                <button onclick="toggleModal('walkinModal')" class="text-slate-400 hover:text-slate-600"><i
                        class="fa-solid fa-xmark text-lg"></i></button>
            </div>
            <form action="../actions/process_admin_booking.php" method="POST" class="p-8 space-y-6 overflow-y-auto"
                onsubmit="return validateWalkinForm(event)">
                <input type="hidden" name="action" value="create_walkin">
                <input type="hidden" name="room_id" id="wi_room_id">

                <div class="p-4 bg-indigo-50/50 rounded-2xl border border-indigo-100 grid grid-cols-2 gap-4">
                    <div class="col-span-2">
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-3">Hình
                            thức thuê</label>
                        <div class="flex gap-4">
                            <label
                                class="flex-1 flex items-center gap-2 p-3 bg-white border border-slate-200 rounded-xl cursor-pointer hover:border-indigo-500 transition">
                                <input type="radio" name="rental_type" value="hourly" checked
                                    class="text-indigo-600 focus:ring-indigo-500" onchange="updateCalc()">
                                <span class="text-sm font-bold text-slate-700">Theo giờ <span id="wi_price_hr"
                                        class="text-xs font-normal text-slate-400 block"></span></span>
                            </label>
                            <label
                                class="flex-1 flex items-center gap-2 p-3 bg-white border border-slate-200 rounded-xl cursor-pointer hover:border-indigo-500 transition">
                                <input type="radio" name="rental_type" value="daily"
                                    class="text-indigo-600 focus:ring-indigo-500" onchange="updateCalc()">
                                <span class="text-sm font-bold text-slate-700">Theo ngày <span id="wi_price_day"
                                        class="text-xs font-normal text-slate-400 block"></span></span>
                            </label>
                        </div>
                    </div>
                    <div class="col-span-2">
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Số
                            điện thoại liên hệ (Đại diện)</label>
                        <input type="tel" name="guest_phone"
                            class="w-full px-5 py-3 bg-white border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none transition"
                            placeholder="Nhập SĐT để nhận hóa đơn...">
                    </div>
                    <div class="col-span-2">
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Thời
                            gian lưu trú (Giờ / Ngày)</label>
                        <input type="number" name="duration" id="wi_duration" min="1" value="1" required
                            class="w-full px-5 py-3 bg-white border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none transition"
                            oninput="updateCalc()">
                    </div>
                    <div
                        class="col-span-2 bg-white p-4 rounded-xl border border-dashed border-indigo-200 text-sm text-indigo-800">
                        <p><i class="fa-regular fa-clock mr-1"></i> Giờ nhận phòng: <b id="wi_calc_in">Lập tức</b>
                        </p>
                        <p class="mt-1"><i class="fa-solid fa-person-walking-luggage mr-1"></i> Giờ trả dự kiến: <b
                                id="wi_calc_out">...</b></p>
                        <p class="mt-1"><i class="fa-solid fa-money-bill-wave mr-1"></i> Tạm tính: <b id="wi_calc_price"
                                class="text-lg">0đ</b></p>
                    </div>
                </div>

                <div>
                    <div class="flex justify-between items-center mb-3">
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest">Khách
                            lưu
                            trú</label>
                        <button type="button" onclick="addGuestRow()"
                            class="text-xs font-bold text-indigo-600 hover:underline"><i
                                class="fa-solid fa-plus mr-1"></i>Thêm khách</button>
                    </div>
                    <div id="guestList" class="space-y-3"></div>
                </div>

                <button type="submit"
                    class="w-full px-4 py-4 bg-indigo-600 text-white rounded-2xl font-bold shadow-lg shadow-indigo-100 hover:bg-indigo-700 transition">
                    <i class="fa-solid fa-key mr-2"></i>Xác nhận Giao Phòng
                </button>
            </form>
        </div>
    </div>

    <!-- MODAL XEM THÔNG TIN ĐƠN (CHECK-OUT) -->
    <div id="viewBookingModal"
        class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 hidden items-center justify-center p-4">
        <div
            class="bg-white rounded-[2.5rem] w-full max-w-md shadow-2xl overflow-hidden animate-in zoom-in duration-200">
            <div class="p-6 border-b border-slate-100 flex justify-between items-center bg-rose-50">
                <h3 class="font-bold text-lg text-rose-800 uppercase tracking-tight"><i
                        class="fa-solid fa-bed mr-2"></i>Chi tiết đang lưu trú</h3>
                <button onclick="toggleModal('viewBookingModal')" class="text-slate-400 hover:text-slate-600"><i
                        class="fa-solid fa-xmark text-lg"></i></button>
            </div>
            <div class="p-8 space-y-4 text-sm relative">
                <div id="v_loader" class="absolute inset-0 bg-white/80 flex items-center justify-center hidden z-10">
                    <i class="fa-solid fa-circle-notch fa-spin text-3xl text-indigo-600"></i>
                </div>

                <div class="flex justify-between items-center pb-4 border-b border-slate-100">
                    <span class="text-[10px] font-black text-slate-400 uppercase">Mã Đơn</span>
                    <span class="font-black text-indigo-600" id="v_booking_id"></span>
                </div>
                <div class="flex justify-between items-center pb-4 border-b border-slate-100">
                    <span class="text-[10px] font-black text-slate-400 uppercase">Phòng</span>
                    <span class="font-bold text-slate-800" id="v_room"></span>
                </div>
                <div class="flex justify-between items-center pb-4 border-b border-slate-100">
                    <span class="text-[10px] font-black text-slate-400 uppercase">Ngày nhận (IN)</span>
                    <span class="font-medium text-slate-600" id="v_checkin"></span>
                </div>
                <div class="flex justify-between items-center pb-4 border-b border-slate-100">
                    <span class="text-[10px] font-black text-slate-400 uppercase">Ngày trả (OUT)</span>
                    <span class="font-medium text-slate-600" id="v_checkout"></span>
                </div>
                <div class="flex justify-between items-center pb-2">
                    <span class="text-[10px] font-black text-slate-400 uppercase">Tạm tính (Dự kiến)</span>
                    <span class="font-black text-lg text-emerald-600" id="v_price"></span>
                </div>

                <button onclick="proceedToCheckout()"
                    class="w-full mt-4 px-4 py-4 bg-emerald-500 text-white rounded-2xl font-bold shadow-lg shadow-emerald-200 hover:bg-emerald-600 transition">
                    <i class="fa-solid fa-money-bill-wave mr-2"></i>Thanh toán & Trả phòng
                </button>
            </div>
        </div>
    </div>

    <!-- MODAL THANH TOÁN HOÀN TẤT -->
    <div id="checkoutModal"
        class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-[60] hidden items-center justify-center p-4">
        <div
            class="bg-white rounded-[2.5rem] w-full max-w-lg shadow-2xl overflow-hidden animate-in zoom-in duration-200">
            <div class="p-6 border-b border-slate-100 flex justify-between items-center bg-emerald-50">
                <h3 class="font-bold text-lg text-emerald-800 uppercase tracking-tight">Hóa Đơn Thanh Toán</h3>
                <button onclick="toggleModal('checkoutModal')" class="text-slate-400 hover:text-slate-600"><i
                        class="fa-solid fa-xmark text-lg"></i></button>
            </div>
            <form action="../actions/process_admin_booking.php" method="POST" class="p-8 space-y-4 text-sm">
                <input type="hidden" name="action" value="checkout">
                <input type="hidden" name="booking_id" id="co_booking_id">
                <input type="hidden" name="total_paid" id="co_total_paid">

                <div class="bg-slate-50 p-4 rounded-xl border border-slate-200 mb-4">
                    <p class="text-xs text-slate-500 mb-1">Khách hàng đại diện</p>
                    <p class="font-bold text-slate-800 text-base" id="co_customer"></p>
                </div>

                <div class="flex justify-between items-center pb-3 border-b border-dashed border-slate-200">
                    <span class="text-slate-500">Giờ vào thực tế</span>
                    <span class="font-bold text-slate-800" id="co_in"></span>
                </div>
                <div class="flex justify-between items-center pb-3 border-b border-dashed border-slate-200">
                    <span class="text-slate-500">Giờ ra thực tế</span>
                    <span class="font-bold text-slate-800" id="co_out"></span>
                </div>
                <div class="flex justify-between items-center pt-2">
                    <span class="text-slate-500">Tiền phòng (Dự kiến)</span>
                    <span class="font-bold text-slate-800" id="co_base_price"></span>
                </div>
                <div id="co_overtime_box" class="flex justify-between items-center text-rose-600 hidden">
                    <span>Phụ thu lố giờ (<span id="co_overtime_hrs"></span> tiếng)</span>
                    <span class="font-bold" id="co_overtime_fee"></span>
                </div>

                <div class="flex justify-between items-center pt-4 border-t border-slate-200 mt-4">
                    <span class="text-xs font-black text-slate-400 uppercase">TỔNG THANH TOÁN</span>
                    <span class="text-3xl font-black text-indigo-600" id="co_final_total"></span>
                </div>

                <button type="submit"
                    class="w-full mt-6 px-4 py-4 bg-indigo-600 text-white rounded-2xl font-bold shadow-lg shadow-indigo-100 hover:bg-indigo-700 transition">
                    <i class="fa-solid fa-check-double mr-2"></i>Xác nhận Đã Thu Tiền
                </button>
            </form>
        </div>
    </div>

    <!-- MODAL XEM CHI TIẾT ĐƠN / HÓA ĐƠN -->
    <div id="invoiceModal"
        class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-[70] hidden items-center justify-center p-4">
        <div
            class="bg-white rounded-[2.5rem] w-full max-w-2xl shadow-2xl overflow-hidden animate-in zoom-in duration-200 flex flex-col max-h-[90vh]">
            <div class="p-6 border-b border-slate-100 flex justify-between items-center bg-indigo-50">
                <h3 class="font-bold text-lg text-indigo-800 uppercase tracking-tight"><i
                        class="fa-solid fa-file-invoice mr-2"></i>Chi tiết Đơn Đặt Phòng</h3>
                <button onclick="toggleModal('invoiceModal')" class="text-slate-400 hover:text-slate-600"><i
                        class="fa-solid fa-xmark text-lg"></i></button>
            </div>
            <div class="p-8 overflow-y-auto text-sm space-y-6 relative">
                <div id="inv_loader" class="absolute inset-0 bg-white/80 flex items-center justify-center hidden z-10">
                    <i class="fa-solid fa-circle-notch fa-spin text-3xl text-indigo-600"></i>
                </div>
                <div class="grid grid-cols-2 gap-6 bg-slate-50 p-6 rounded-2xl border border-slate-100">
                    <div>
                        <p class="text-[10px] font-black text-slate-400 uppercase mb-1">Mã Đơn</p>
                        <p class="font-black text-indigo-600 text-lg" id="inv_id"></p>
                    </div>
                    <div class="text-right">
                        <p class="text-[10px] font-black text-slate-400 uppercase mb-1">Trạng thái</p>
                        <p class="font-bold text-slate-800" id="inv_status"></p>
                    </div>
                </div>

                <!-- BỔ SUNG CHI TIẾT THANH TOÁN -->
                <div>
                    <h4 class="text-xs font-black text-slate-400 uppercase tracking-widest mb-3">Thông tin Lưu trú &
                        Thanh toán</h4>
                    <div class="bg-white border border-slate-200 rounded-xl p-5 space-y-3">
                        <div class="flex justify-between items-center border-b border-dashed border-slate-200 pb-2">
                            <span class="text-slate-500">Giờ nhận phòng (IN)</span>
                            <span class="font-bold text-slate-800" id="inv_in"></span>
                        </div>
                        <div class="flex justify-between items-center border-b border-dashed border-slate-200 pb-2">
                            <span class="text-slate-500">Giờ trả phòng (OUT)</span>
                            <span class="font-bold text-slate-800" id="inv_out"></span>
                        </div>
                        <div class="flex justify-between items-center border-b border-dashed border-slate-200 pb-2">
                            <span class="text-slate-500" id="inv_base_price_label">Tiền phòng dự kiến</span>
                            <span class="font-bold text-slate-800" id="inv_base_price"></span>
                        </div>
                        <div class="flex justify-between items-center border-b border-dashed border-slate-200 pb-2">
                            <span class="text-slate-500">Phụ thu phát sinh <span id="inv_overtime_note"
                                    class="text-[10px] text-slate-400 font-normal"></span></span>
                            <span class="font-bold text-rose-600" id="inv_overtime_fee"></span>
                        </div>
                        <div class="flex justify-between items-center border-b border-dashed border-slate-200 pb-2">
                            <span class="text-slate-500">Trạng thái thanh toán</span>
                            <span class="font-bold uppercase text-[10px] px-2 py-1 rounded"
                                id="inv_payment_status"></span>
                        </div>
                        <div class="flex justify-between items-center pt-2">
                            <span class="text-xs font-black text-slate-400 uppercase tracking-widest">TỔNG CỘNG</span>
                            <span class="text-2xl font-black text-indigo-600" id="inv_total"></span>
                        </div>
                    </div>
                </div>

                <div>
                    <h4 class="text-xs font-black text-slate-400 uppercase tracking-widest mb-3">Danh sách Khách lưu trú
                    </h4>
                    <div class="bg-white border border-slate-200 rounded-xl overflow-hidden">
                        <table class="w-full text-left">
                            <thead class="bg-slate-50 border-b border-slate-100">
                                <tr class="text-[10px] text-slate-500 uppercase tracking-wider font-bold">
                                    <th class="px-4 py-2">Họ Tên</th>
                                    <th class="px-4 py-2">CCCD</th>
                                    <th class="px-4 py-2 text-center">Vai trò</th>
                                </tr>
                            </thead>
                            <tbody id="inv_guests" class="divide-y divide-slate-100"></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="../assets/js/toast.js"></script>
    <script src="../assets/js/admin_bookings.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', () => {
        const urlParams = new URLSearchParams(window.location.search);
        let activeTab = urlParams.get('search') ? 'listTab' : (sessionStorage.getItem('adminBookingCurrentTab') || '<?php echo $default_tab; ?>');

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