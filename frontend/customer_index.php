<?php
session_start();
require_once '../dao/room_dao.php';
require_once '../config/mongodb.php';
require_once '../dao/booking_dao.php';

// Xử lý Bộ lọc Tìm kiếm
$search_in = $_GET['check_in'] ?? '';
$search_out = $_GET['check_out'] ?? '';
$search_guests = (int)($_GET['guests'] ?? 0);
$search_amenities = $_GET['amenities'] ?? []; // Lấy mảng tiện ích khách đã chọn
$is_searched = ($search_in || $search_out || $search_guests > 0 || !empty($search_amenities));
$can_book = ($search_in && $search_out); // Chỉ cho phép đặt khi đã chọn đủ ngày

// TRUY VẤN MONGODB ĐỂ LỌC TIỆN ÍCH TRƯỚC
$mongo_db = mongo_get_db();
$valid_type_ids = [];
$filter_by_mongo = false;

if (!empty($search_amenities)) {
    $filter_by_mongo = true;
    // MongoDB Query: Tìm các hạng phòng có chứa TẤT CẢ các tiện ích được chọn
    $mongo_filter = ['amenities' => ['$all' => $search_amenities]];
    $cursor = $mongo_db->room_details->find($mongo_filter);
    foreach ($cursor as $doc) {
        if (isset($doc['type_id'])) {
            $valid_type_ids[] = (int)$doc['type_id'];
        }
    }
}

if ($is_searched) {
    $sql = "SELECT rt.* FROM Room_types rt WHERE 1=1 ";
    $params = [];

    if ($search_guests > 0) {
        $sql .= " AND rt.capacity >= ? ";
        $params[] = $search_guests;
    }

    // Khớp ID từ MongoDB vào câu lệnh SQL
    if ($filter_by_mongo) {
        if (empty($valid_type_ids)) {
            $sql .= " AND 1=0 "; // Ép trả về rỗng nếu MongoDB không tìm thấy kết quả nào
        } else {
            $placeholders = implode(',', array_fill(0, count($valid_type_ids), '?'));
            $sql .= " AND rt.type_id IN ($placeholders) ";
            $params = array_merge($params, $valid_type_ids);
        }
    }

    // Nếu khách có chọn Ngày, kiểm tra phòng rỗng trong khoảng thời gian đó
    if ($can_book) {
        $in_time = $search_in . ' 14:00:00';
        $out_time = $search_out . ' 12:00:00';
        $sql .= " AND EXISTS (
                SELECT 1 FROM Room r
                WHERE r.type_id = rt.type_id AND r.status = 'available'
                AND r.room_id NOT IN (
                    SELECT bd.room_id FROM Booking_detail bd
                    JOIN Booking b ON bd.booking_id = b.booking_id
                    WHERE b.booking_status NOT IN ('cancelled', 'completed')
                    AND (b.check_in_planned < ? AND b.check_out_planned > ?)
                )
            )";
        $params[] = $out_time;
        $params[] = $in_time;
    }

    $sql .= " ORDER BY rt.price_per_day ASC";

    if (!empty($params)) {
        $room_types = db_query($sql, ...$params);
    } else {
        $room_types = db_query($sql);
    }
} else {
    // Mặc định lấy tất cả hạng phòng
    $room_types = room_type_get_all('price ASC');
}

// Lấy thông tin User hiện tại (Để fill sẵn vào Form Đặt phòng)
$user_id = $_SESSION['user_id'] ?? null;
$user_profile = null;
if ($user_id) {
    $user_profile = db_query_one("SELECT c.*, a.status FROM Customer c JOIN Account a ON c.customer_id = a.customer_id WHERE a.account_id = ?", $user_id);
}

// Lấy dữ liệu Phòng (Ảnh, Tiện ích) từ MongoDB
$mongo_db = mongo_get_db();
$details_cursor = $mongo_db->room_details->find([]);
$mongo_images = [];
foreach ($details_cursor as $doc) {
    $mongo_images[$doc['type_id']] = [
        'base64' => $doc['image_base64'] ?? '',
        'mime' => $doc['mime_type'] ?? 'image/jpeg',
        'amenities' => isset($doc['amenities']) ? iterator_to_array($doc['amenities']) : []
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

$pricing_config = get_pricing_config();

$dynamic_multiplier_sum = 0;
$duration_days = 0;

if ($can_book) {
    $current_date = strtotime($search_in);
    $end_date = strtotime($search_out);
    while ($current_date < $end_date) {
        $day_of_week = date('N', $current_date);
        $date_md = date('d-m', $current_date);
        if (in_array($date_md, $pricing_config['holidays'])) {
            $dynamic_multiplier_sum += $pricing_config['holiday_multiplier'];
        } elseif ($day_of_week == 6 || $day_of_week == 7) {
            $dynamic_multiplier_sum += $pricing_config['weekend_multiplier'];
        } else {
            $dynamic_multiplier_sum += 1;
        }
        $duration_days++;
        $current_date = strtotime('+1 day', $current_date);
    }
}

// Lấy danh sách Tiện ích từ MongoDB để hiển thị checkbox
$all_amenities = amenity_get_all();
?>
<!DOCTYPE html>
<html lang="vi" class="scroll-smooth">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Grand Horizon - Trang chủ Khách hàng</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Italianno&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap"
        rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Italianno&family=Viaoda+Libre&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" type="text/css" href="https://npmcdn.com/flatpickr/dist/themes/airbnb.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['"Plus Jakarta Sans"', 'sans-serif'],
                        italianno: ['"Italianno"', 'cursive'],
                        viaoda: ['"Viaoda Libre"', 'serif']
                    },
                    keyframes: {
                        shine: {
                            '100%': {
                                left: '200%'
                            },
                        }
                    }
                }
            }
        }
    </script>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        ::-webkit-scrollbar {
            width: 8px;
        }

        ::-webkit-scrollbar-track {
            background: #f8fafc;
        }

        ::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 10px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: #818cf8;
        }
    </style>
</head>

<body class="bg-slate-50 text-slate-800">

    <?php include 'navbar_customer.php'; ?>

    <!-- Hero Section -->
    <div id="heroContainer" class="relative h-[80vh] bg-slate-900 flex items-center justify-center overflow-hidden">
        <img id="heroImage"
            src="https://images.unsplash.com/photo-1611892440504-42a792e24d32?q=80&w=2070&auto=format&fit=crop"
            class="absolute inset-0 w-full h-full object-cover opacity-40 scale-110 transition-transform duration-100 ease-out will-change-transform">

        <div class="relative z-10 text-center px-4 pointer-events-none min-h-[150px]">
            <h1 id="heroTitle"
                class="font-italianno text-7xl md:text-9xl text-white mb-2 tracking-wide opacity-90 drop-shadow-lg min-h-[100px]">
            </h1>
            <p id="heroDesc"
                class="font-italianno text-3xl md:text-5xl text-slate-200 max-w-3xl mx-auto drop-shadow-lg min-h-[50px]">
            </p>
        </div>
    </div>

    <!-- Bộ lọc Tìm kiếm Nhanh (Search Bar) -->
    <div class="relative w-full h-0 z-30 flex justify-center">
        <div class="absolute top-0 -translate-y-1/2 w-full max-w-4xl px-4">

            <form id="searchBar" action="#rooms" method="GET"
                class="bg-white p-4 rounded-2xl shadow-xl grid grid-cols-1 md:grid-cols-4 gap-4 border border-slate-100">
                <div class="w-full relative">
                    <i
                        class="fa-regular fa-calendar-check absolute left-4 top-1/2 -translate-y-1/2 text-indigo-500 z-10"></i>
                    <input type="text" id="checkInDate" name="check_in" value="<?= htmlspecialchars($search_in) ?>"
                        placeholder="Ngày nhận phòng"
                        class="w-full pl-11 pr-4 py-3.5 bg-slate-50 border border-slate-200 rounded-xl text-sm font-bold focus:ring-2 focus:ring-indigo-500 outline-none text-slate-700 bg-white">
                </div>
                <div class="w-full relative">
                    <i
                        class="fa-regular fa-calendar-xmark absolute left-4 top-1/2 -translate-y-1/2 text-rose-500 z-10"></i>
                    <input type="text" id="checkOutDate" name="check_out" value="<?= htmlspecialchars($search_out) ?>"
                        placeholder="Ngày trả phòng"
                        class="w-full pl-11 pr-4 py-3.5 bg-slate-50 border border-slate-200 rounded-xl text-sm font-bold focus:ring-2 focus:ring-indigo-500 outline-none text-slate-700 bg-white">
                </div>
                <div class="w-full relative">
                    <i class="fa-solid fa-users absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                    <input type="number" name="guests" min="1" placeholder="Số người ở (Tùy chọn)"
                        value="<?= $search_guests ? htmlspecialchars($search_guests) : '' ?>"
                        class="w-full pl-11 pr-4 py-3.5 bg-slate-50 border border-slate-200 rounded-xl text-sm font-bold focus:ring-2 focus:ring-indigo-500 outline-none text-slate-700">
                </div>
                <button type="submit"
                    class="w-full bg-indigo-600 text-white px-8 py-3.5 rounded-xl font-bold shadow-lg shadow-indigo-200 hover:bg-indigo-700 transition active:scale-95 whitespace-nowrap relative overflow-hidden group">
                    Tìm kiếm phòng
                    <span
                        class="absolute top-0 left-[-100%] w-[50%] h-full bg-gradient-to-r from-transparent via-white/30 to-transparent skew-x-[-20deg] group-hover:animate-[shine_1s_ease-in-out]"></span>
                </button>

                <!-- Chọn tiện ích -->
                <div class="md:col-span-4 border-t border-slate-100 pt-5 flex flex-wrap gap-3 items-center">
                    <span class="text-xs font-bold text-slate-500 uppercase tracking-widest mr-2">
                        <i class="fa-solid fa-sparkles text-amber-500 mr-1"></i> Tiện ích nổi bật:
                    </span>
                    <?php
                    foreach ($all_amenities as $amn):
                        $checked = in_array($amn, $search_amenities) ? 'checked' : '';
                    ?>
                        <label class="relative cursor-pointer group">
                            <input type="checkbox" name="amenities[]" value="<?= $amn ?>" <?= $checked ?>
                                class="peer sr-only">
                            <span
                                class="px-4 py-2 bg-white border border-slate-200 text-slate-600 rounded-full text-xs font-bold transition-all duration-300 hover:border-indigo-300 peer-checked:bg-indigo-600 peer-checked:text-white peer-checked:border-indigo-600 peer-checked:shadow-md peer-checked:shadow-indigo-200 flex items-center gap-1.5">
                                <?= $amn ?>
                            </span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </form>
        </div>
    </div>

    <!-- Danh sách Hạng phòng (Room Types) -->
    <div id="rooms" class="relative w-full pt-40 pb-24 bg-slate-900 overflow-hidden">

        <div
            class="absolute inset-0 bg-[url('https://images.unsplash.com/photo-1578683010236-d716f9a3f461?q=80&w=2070&auto=format&fit=crop')] bg-cover bg-center bg-fixed opacity-30">
        </div>
        <div class="absolute inset-0 bg-gradient-to-b from-slate-900 via-slate-900/80 to-slate-900 z-0"></div>

        <div
            class="absolute top-0 left-0 w-32 h-32 border-t-2 border-l-2 border-amber-500/40 m-6 lg:m-12 z-0 pointer-events-none">
        </div>
        <div
            class="absolute bottom-0 right-0 w-32 h-32 border-b-2 border-r-2 border-amber-500/40 m-6 lg:m-12 z-0 pointer-events-none">
        </div>

        <div class="relative z-10 max-w-7xl mx-auto px-6">

            <div class="text-center mb-16">
                <?php if ($is_searched): ?>
                    <h2 class="text-5xl md:text-6xl font-viaoda text-white tracking-wide c">Phòng trống cho bạn
                    </h2>
                    <p
                        class="text-indigo-300 mt-4 font-bold bg-indigo-900/40 border border-indigo-500/30 inline-block px-4 py-1.5 rounded-full backdrop-blur-sm shadow-lg">
                        <i class="fa-solid fa-check mr-1"></i> Có <?= count($room_types) ?> hạng phòng phù hợp với yêu cầu
                        của bạn
                    </p>
                <?php else: ?>
                    <h2 class="text-5xl md:text-6xl font-viaoda text-white tracking-wide drop-shadow-md">Lựa chọn của bạn
                    </h2>
                    <p class="text-slate-400 mt-4 font-medium font-sans">Các hạng phòng được thiết kế chuyên biệt để mang
                        lại sự thoải mái tối đa.</p>
                <?php endif; ?>
            </div>

            <?php if (empty($room_types)): ?>
                <div
                    class="text-center bg-slate-800/80 backdrop-blur-md p-12 rounded-3xl border border-slate-700 shadow-2xl max-w-2xl mx-auto">
                    <i class="fa-solid fa-bed-pulse text-6xl text-slate-500 mb-4"></i>
                    <h3 class="text-xl font-bold text-white">Rất tiếc, đã hết phòng!</h3>
                    <p class="text-slate-400 mt-2">Không có hạng phòng nào trống hoặc đủ sức chứa cho ngày bạn chọn. Vui
                        lòng thay đổi thông tin tìm kiếm.</p>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-10">
                    <?php foreach ($room_types as $rt): ?>
                        <div
                            class="bg-white rounded-[2rem] overflow-hidden shadow-2xl shadow-black/50 border border-slate-800 group hover:-translate-y-2 transition-all duration-500 flex flex-col opacity-0 translate-y-10 reveal-card">
                            <div class="relative h-72 overflow-hidden bg-slate-100">
                                <div
                                    class="absolute inset-0 bg-gradient-to-t from-slate-900/60 to-transparent z-10 pointer-events-none">
                                </div>

                                <?php if (isset($mongo_images[$rt['type_id']])): ?>
                                    <img src="data:<?php echo $mongo_images[$rt['type_id']]['mime']; ?>;base64,<?php echo $mongo_images[$rt['type_id']]['base64']; ?>"
                                        class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-700">
                                <?php else: ?>
                                    <div class="flex items-center justify-center h-full text-slate-300"><i
                                            class="fa-solid fa-image text-5xl"></i></div>
                                <?php endif; ?>

                                <div class="absolute bottom-4 left-4 z-20">
                                    <span
                                        class="bg-white/90 backdrop-blur-sm text-slate-800 text-[10px] font-black uppercase tracking-widest px-3 py-1.5 rounded-lg shadow-sm">
                                        <?= $rt['capacity'] ?> Người lớn
                                    </span>
                                </div>
                            </div>
                            <div class="p-8 flex-1 flex flex-col">
                                <h3 class="text-2xl font-black text-slate-800 mb-2 uppercase tracking-tight">
                                    <?php echo htmlspecialchars($rt['name']); ?></h3>
                                <p class="text-slate-500 text-sm line-clamp-3 mb-6 flex-1">
                                    <?php echo htmlspecialchars($rt['description']); ?></p>
                                <div
                                    class="flex items-center gap-4 text-xs font-bold text-slate-400 uppercase tracking-widest mb-4">
                                    <span><i class="fa-solid fa-user-group text-indigo-400 mr-1.5"></i>Tối đa
                                        <?php echo $rt['capacity']; ?> người</span>
                                </div>

                                <?php if (isset($mongo_images[$rt['type_id']]) && !empty($mongo_images[$rt['type_id']]['amenities'])): ?>
                                    <div class="flex flex-wrap gap-2 mb-6">
                                        <?php foreach ($mongo_images[$rt['type_id']]['amenities'] as $amn): ?>
                                            <span
                                                class="px-2.5 py-1 bg-slate-50 border border-slate-200 text-slate-600 rounded-lg text-[10px] font-bold"><i
                                                    class="fa-solid fa-check text-emerald-500 mr-1"></i> <?= $amn ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>

                                <?php if (!$can_book): ?>
                                    <a href="#searchBar"
                                        onclick="document.getElementById('searchBar').classList.add('ring-4', 'ring-indigo-200', 'scale-[1.02]'); setTimeout(()=>document.getElementById('searchBar').classList.remove('ring-4', 'ring-indigo-200', 'scale-[1.02]'), 500);"
                                        class="mt-auto text-center w-full bg-slate-800 text-white py-4 rounded-xl font-bold shadow-lg hover:bg-slate-900 transition active:scale-95">Chọn
                                        ngày để Đặt phòng</a>
                                <?php else: ?>
                                    <?php
                                    $total_dynamic_price = $rt['price_per_day'] * $dynamic_multiplier_sum;
                                    $price_fmt = number_format($total_dynamic_price, 0, ',', '.');
                                    ?>
                                    <div class="mt-auto pt-6 border-t border-slate-100 flex items-end justify-between">
                                        <div>
                                            <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest mb-1">Giá từ
                                            </p>
                                            <p class="text-2xl font-black text-indigo-600 leading-none">
                                                <?= number_format($total_dynamic_price ?? $rt['price_per_day'], 0, ',', '.') ?><span
                                                    class="text-sm text-slate-400 font-bold">đ</span>
                                                <?php if (!$can_book): ?><span class="text-xs text-slate-400 font-medium font-sans">
                                                        /
                                                        đêm</span><?php endif; ?>
                                            </p>
                                        </div>
                                        <button
                                            onclick="openBookingModal(<?= $rt['type_id'] ?>, '<?= htmlspecialchars(addslashes($rt['name'])) ?>', <?= $rt['price_per_day'] ?>, <?= $rt['capacity'] ?>)"
                                            class="bg-slate-900 text-white px-6 py-3 rounded-xl text-sm font-bold shadow-lg shadow-slate-200 hover:bg-indigo-600 transition duration-300 hover:-translate-y-1">
                                            Đặt ngay
                                        </button>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Review section -->
    <?php
    include 'review_section.php';
    ?>

    <!-- Modal Điền thông tin cá nhân -->
    <?php
    include 'Modals/customerBooking_modal.php';
    ?>

    <!-- Modal Xác nhận & Đặt phòng -->
    <?php
    include 'Modals/confirmBooking_modal.php';
    ?>

    <!-- Footer -->
    <?php
    include 'footer.php';
    ?>

    <!-- CHAT WIDGET KÉO THẢ -->
    <div id="chatWidget" class="fixed z-[100] bottom-6 right-6 flex flex-col items-end">
        <!-- Cửa sổ Chat -->
        <div id="chatWindow"
            class="hidden flex-col w-80 md:w-96 max-h-[80vh] bg-white rounded-2xl shadow-2xl border border-slate-200 overflow-hidden mb-4 transform transition-all">
            <div class="bg-indigo-600 p-4 flex justify-between items-center text-white shrink-0">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 bg-white/20 rounded-full flex items-center justify-center"><i
                            class="fa-solid fa-headset text-sm"></i></div>
                    <div>
                        <h3 class="font-bold text-sm">Hỗ trợ trực tuyến</h3>
                        <p class="text-[10px] text-indigo-200">Chúng tôi luôn sẵn sàng hỗ trợ</p>
                    </div>
                </div>
                <button onclick="toggleChat()" class="text-indigo-200 hover:text-white transition"><i
                        class="fa-solid fa-xmark"></i></button>
            </div>
            <div id="chatMessages"
                class="flex-1 p-4 h-[400px] overflow-y-auto bg-slate-50 space-y-3 flex flex-col shrink"
                style="scrollbar-width: thin;">
                <div class="text-center text-[10px] text-slate-400 font-bold uppercase my-2">Bắt đầu cuộc trò chuyện
                </div>
            </div>
            <div class="p-3 bg-white border-t border-slate-100 flex gap-2 shrink-0">
                <input type="text" id="chatInput"
                    class="flex-1 bg-slate-100 border-none rounded-xl px-4 py-2 text-sm focus:ring-2 focus:ring-indigo-500 outline-none"
                    placeholder="Nhập tin nhắn...">
                <button id="sendChatBtn"
                    class="w-10 h-10 bg-indigo-600 text-white rounded-xl flex items-center justify-center hover:bg-indigo-700 transition shrink-0"><i
                        class="fa-solid fa-paper-plane text-xs"></i></button>
            </div>
        </div>

        <!-- Bong bóng Chat -->
        <div id="chatBubble"
            class="w-14 h-14 bg-indigo-600 rounded-full shadow-2xl flex items-center justify-center cursor-pointer hover:bg-indigo-700 transition-colors relative group">
            <i
                class="fa-solid fa-comments text-white text-2xl group-hover:scale-110 transition-transform pointer-events-none"></i>
            <span id="chatNotifBadge"
                class="absolute top-0 right-0 w-3 h-3 bg-red-500 rounded-full border-2 border-white hidden pointer-events-none"></span>
        </div>
    </div>

    <script src="../assets/js/toast.js"></script>
    <script src="../assets/js/customer_index.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://npmcdn.com/flatpickr/dist/l10n/vn.js"></script>

    <script>
        // Lấy cấu hình giá từ Backend
        const pricingConfig = <?php echo json_encode($pricing_config); ?>;

        let currentRoomName = '';
        let baseTotalPrice = 0;

        document.addEventListener('DOMContentLoaded', () => {
            let hasParams = false;
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('error') === 'no_room_available') {
                showToast('Xin lỗi, không còn phòng trống trong khoảng thời gian này!', 'error');
                hasParams = true;
            } else if (urlParams.get('msg') === 'booking_success') {
                showToast('Đặt phòng thành công! Hãy đợi nhân viên xác nhận.', 'success');
                hasParams = true;
            } else if (urlParams.get('error') === 'not_approved') {
                showToast('Tài khoản chưa được phê duyệt. Vui lòng cập nhật hồ sơ và chờ Admin duyệt!', 'warning');
                hasParams = true;
            }

            // Tự động xóa tham số URL rác đi để làm sạch thanh địa chỉ
            if (hasParams) {
                window.history.replaceState({}, document.title, window.location.pathname);
            }

            // Kiểm tra tính hợp lệ của Ngày nhận / Trả phòng
            const checkInInput = document.querySelector('form#searchBar input[name="check_in"]');
            const checkOutInput = document.querySelector('form#searchBar input[name="check_out"]');

            if (checkInInput && checkOutInput) {
                checkInInput.addEventListener('change', function() {
                    if (!this.value) return;

                    const today = new Date();
                    const todayStr = today.getFullYear() + '-' + String(today.getMonth() + 1).padStart(2,
                        '0') + '-' + String(today.getDate()).padStart(2, '0');

                    if (this.value < todayStr) {
                        showToast('Lỗi: Ngày nhận phòng không được chọn trong quá khứ!', 'error');
                        this.value = '';
                        return;
                    }

                    const [y, m, d] = this.value.split('-').map(Number);
                    const nextDay = new Date(y, m - 1, d + 1);
                    const nextDayStr = nextDay.getFullYear() + '-' + String(nextDay.getMonth() + 1)
                        .padStart(2, '0') + '-' + String(nextDay.getDate()).padStart(2, '0');

                    checkOutInput.min = nextDayStr; // Cập nhật ngày tối thiểu cho ô Trả phòng

                    if (checkOutInput.value && checkOutInput.value <= this.value) {
                        showToast('Lỗi: Ngày trả phòng phải sau ngày nhận phòng ít nhất 1 ngày!', 'error');
                        checkOutInput.value = '';
                    }
                });

                checkOutInput.addEventListener('change', function() {
                    if (!this.value) return;

                    if (!checkInInput.value) {
                        showToast('Vui lòng chọn ngày nhận phòng trước!', 'warning');
                        this.value = '';
                        return;
                    }

                    if (this.value <= checkInInput.value) {
                        showToast('Lỗi: Ngày trả phòng phải sau ngày nhận phòng ít nhất 1 ngày!', 'error');
                        this.value = '';
                    }
                });
            }
        });

        let currentRoomCapacity = 0;

        function openBookingModal(typeId, name, priceDay, capacity) {
            <?php if (!isset($_SESSION['user_id'])): ?>
                window.location.href = 'login.php';
                return;
            <?php endif; ?>

            document.getElementById('cb_type_id').value = typeId;
            document.getElementById('cb_price_per_day').value = priceDay;
            currentRoomName = name;
            currentRoomCapacity = capacity;

            const guestList = document.getElementById('customerGuestList');
            if (guestList) guestList.innerHTML = '';

            document.getElementById('customerBookingModal').classList.remove('hidden');
            document.getElementById('customerBookingModal').classList.add('flex');
        }

        function closeBookingModal() {
            document.getElementById('customerBookingModal').classList.add('hidden');
            document.getElementById('customerBookingModal').classList.remove('flex');
        }

        function checkCCCD_Customer(input, index) {
            const cccd = input.value.trim();
            const nameInput = document.getElementById(`g_name_${index}`);

            if (cccd.length <= 5) {
                nameInput.readOnly = false;
                nameInput.classList.remove('bg-indigo-50', 'text-indigo-700', 'cursor-not-allowed');
                return;
            }

            fetch(`../actions/process_booking.php?action=check_cccd&cccd=${cccd}`)
                .then(res => res.json())
                .then(data => {
                    if (data.status === 'found') {
                        nameInput.value = data.name || '';
                        nameInput.readOnly = true;
                        nameInput.classList.add('bg-indigo-50', 'text-indigo-700', 'cursor-not-allowed');
                    } else {
                        nameInput.readOnly = false;
                        nameInput.classList.remove('bg-indigo-50', 'text-indigo-700', 'cursor-not-allowed');
                    }
                });
        }

        function addCustomerGuestRow() {
            const list = document.getElementById('customerGuestList');
            if (list.children.length >= currentRoomCapacity - 1) {
                showToast('Đã đạt giới hạn số người tối đa cho hạng phòng này!', 'warning');
                return;
            }

            const index = Date.now();

            const html = `
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3 p-4 bg-slate-50 border border-slate-200 rounded-2xl relative" id="cb_g_row_${index}">
                <button type="button" onclick="document.getElementById('cb_g_row_${index}').remove()" class="absolute -top-2 -right-2 bg-rose-100 text-rose-500 w-6 h-6 rounded-full flex items-center justify-center hover:bg-rose-500 hover:text-white transition shadow-sm"><i class="fa-solid fa-xmark text-[10px]"></i></button>
                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase mb-1.5">Số CCCD *</label>
                    <input type="text" name="guests[${index}][cccd]" required pattern="\\d{12}" title="Vui lòng nhập 12 số CCCD" onchange="checkCCCD_Customer(this, ${index})" placeholder="12 chữ số" class="w-full px-4 py-2.5 bg-white border border-slate-200 rounded-xl text-sm font-semibold focus:ring-2 focus:ring-indigo-500 outline-none">
                </div>
                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase mb-1.5">Họ và Tên *</label>
                    <input type="text" name="guests[${index}][name]" id="g_name_${index}" required class="w-full px-4 py-2.5 bg-white border border-slate-200 rounded-xl text-sm font-semibold focus:ring-2 focus:ring-indigo-500 outline-none transition">
                </div>
            </div>
        `;
            list.insertAdjacentHTML('beforeend', html);
        }

        // Chuyển sang Bước 2 (Kiểm tra form trước)
        function goToStep2() {
            const form = document.getElementById('customerBookingForm');
            if (!form.reportValidity()) return; // Kích hoạt popup báo lỗi HTML5 nếu thiếu tên/cccd

            // Tính toán tổng tiền
            const checkInDate = new Date('<?= $search_in ?>');
            const checkOutDate = new Date('<?= $search_out ?>');
            const priceDay = parseInt(document.getElementById('cb_price_per_day').value);

            baseTotalPrice = 0;
            let currentDate = new Date(checkInDate);

            while (currentDate < checkOutDate) {
                let dayOfWeek = currentDate.getDay(); // 0: Chủ Nhật, 6: Thứ Bảy
                let dateMD = String(currentDate.getDate()).padStart(2, '0') + '-' + String(currentDate.getMonth() + 1)
                    .padStart(2, '0');

                if (pricingConfig.holidays.includes(dateMD)) {
                    baseTotalPrice += priceDay * pricingConfig.holiday_multiplier;
                } else if (dayOfWeek === 0 || dayOfWeek === 6) {
                    baseTotalPrice += priceDay * pricingConfig.weekend_multiplier;
                } else {
                    baseTotalPrice += priceDay;
                }
                currentDate.setDate(currentDate.getDate() + 1);
            }
            if (baseTotalPrice === 0) baseTotalPrice = priceDay;

            document.getElementById('conf_room_name').innerText = currentRoomName;
            document.getElementById('conf_total_price').innerText = new Intl.NumberFormat('vi-VN').format(baseTotalPrice) +
                'đ';

            // Hiển thị đúng số lượng khách hàng (Bao gồm người đặt chính + số khách đi cùng)
            const totalGuests = 1 + document.getElementById('customerGuestList').children.length;
            if (document.getElementById('conf_guests')) {
                document.getElementById('conf_guests').innerText = totalGuests + ' người';
            }

            // Tính và Hiển thị tiền cọc
            const depositPercent = pricingConfig.deposit_percent || 30;
            const depositAmount = baseTotalPrice * (depositPercent / 100);
            document.getElementById('conf_deposit_percent').innerText = depositPercent;
            document.getElementById('conf_deposit_amount').innerText = new Intl.NumberFormat('vi-VN').format(
                depositAmount) + 'đ';

            // Reset trạng thái Promo
            document.getElementById('cb_promo_code_hidden').value = '';
            document.getElementById('promo_input').value = '';
            document.getElementById('promo_input').readOnly = false;
            document.getElementById('promo_msg').classList.add('hidden');
            document.getElementById('discount_row').classList.add('hidden');
            document.getElementById('btnApplyPromo').innerText = 'Áp dụng';

            document.getElementById('customerBookingModal').classList.replace('flex', 'hidden');
            document.getElementById('confirmBookingModal').classList.replace('hidden', 'flex');
        }

        // Xử lý áp dụng mã giảm giá
        function applyPromoCode() {
            const code = document.getElementById('promo_input').value.trim().toUpperCase();
            const msgLabel = document.getElementById('promo_msg');
            const btn = document.getElementById('btnApplyPromo');

            if (!code) return;

            btn.innerText = '...';
            btn.disabled = true;

            fetch(`../actions/process_booking.php?action=check_promo&code=${code}`)
                .then(res => res.json())
                .then(data => {
                    btn.innerText = 'Áp dụng';
                    btn.disabled = false;
                    msgLabel.classList.remove('hidden');

                    if (data.status === 'success') {
                        msgLabel.className = 'text-xs font-bold mt-2 text-emerald-600';
                        msgLabel.innerHTML = `<i class="fa-solid fa-circle-check mr-1"></i> Áp dụng mã thành công!`;
                        document.getElementById('cb_promo_code_hidden').value = code;
                        document.getElementById('promo_input').readOnly = true;

                        const discountAmount = baseTotalPrice * (data.discount / 100);
                        const finalPrice = baseTotalPrice - discountAmount;

                        const depositPercent = pricingConfig.deposit_percent || 30;
                        const depositAmount = finalPrice * (depositPercent / 100);

                        document.getElementById('discount_row').classList.remove('hidden');
                        document.getElementById('discount_row').classList.add('flex');
                        document.getElementById('discount_percent_label').innerText = `-${data.discount}%`;
                        document.getElementById('discount_amount_label').innerText = '-' + new Intl.NumberFormat(
                            'vi-VN').format(discountAmount) + 'đ';
                        document.getElementById('conf_total_price').innerText = new Intl.NumberFormat('vi-VN').format(
                            finalPrice) + 'đ';
                        document.getElementById('conf_deposit_amount').innerText = new Intl.NumberFormat('vi-VN')
                            .format(depositAmount) + 'đ';
                    } else {
                        msgLabel.className = 'text-xs font-bold mt-2 text-rose-500';
                        msgLabel.innerHTML = `<i class="fa-solid fa-circle-xmark mr-1"></i> ${data.message}`;
                        document.getElementById('cb_promo_code_hidden').value = '';
                        document.getElementById('discount_row').classList.add('hidden');
                        document.getElementById('discount_row').classList.remove('flex');
                        document.getElementById('conf_total_price').innerText = new Intl.NumberFormat('vi-VN').format(
                            baseTotalPrice) + 'đ';

                        const depositPercent = pricingConfig.deposit_percent || 30;
                        const depositAmount = baseTotalPrice * (depositPercent / 100);
                        document.getElementById('conf_deposit_amount').innerText = new Intl.NumberFormat('vi-VN')
                            .format(depositAmount) + 'đ';
                    }
                })
                .catch(err => {
                    btn.innerText = 'Áp dụng';
                    btn.disabled = false;
                    showToast('Lỗi kết nối máy chủ!', 'error');
                });
        }

        // Submit Form thật sự lên máy chủ
        function submitFinalBooking() {
            const btnText = document.getElementById('btnSubmitText');
            const btnSpinner = document.getElementById('btnSubmitSpinner');
            const submitBtn = document.getElementById('btnSubmitBooking');

            submitBtn.disabled = true;
            btnText.innerText = "Đang xử lý tạo đơn...";
            btnSpinner.classList.remove('hidden');

            const formData = new FormData(document.getElementById('customerBookingForm'));

            fetch('../actions/process_booking.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    submitBtn.disabled = false;
                    btnText.innerText = "Gửi Yêu Cầu Đặt Phòng";
                    btnSpinner.classList.add('hidden');

                    showToast(data.message, data.status);
                    if (data.status === 'success') {
                        document.getElementById('confirmBookingModal').classList.replace('flex', 'hidden');
                    }
                })
                .catch(error => {
                    showToast('Lỗi kết nối đến máy chủ!', 'error');
                    submitBtn.disabled = false;
                    btnText.innerText = "Gửi Yêu Cầu Đặt Phòng";
                    btnSpinner.classList.add('hidden');
                });
        }
    </script>
</body>

</html>