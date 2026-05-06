<?php
session_start();
require_once '../dao/room_dao.php';
require_once '../config/mongodb.php';
require_once '../dao/booking_dao.php';

// Xử lý Bộ lọc Tìm kiếm
$search_in = $_GET['check_in'] ?? '';
$search_out = $_GET['check_out'] ?? '';
$search_guests = (int)($_GET['guests'] ?? 0);
$is_searched = ($search_in && $search_out && $search_guests > 0);

if ($is_searched) {
    $in_time = $search_in . ' 14:00:00';
    $out_time = $search_out . ' 12:00:00';
    // Chỉ lấy Hạng phòng Đủ sức chứa VÀ Còn phòng trống trong khoảng thời gian đó
    $sql = "SELECT rt.* FROM Room_types rt
            WHERE rt.capacity >= ?
            AND EXISTS (
                SELECT 1 FROM Room r
                WHERE r.type_id = rt.type_id AND r.status = 'available'
                AND r.room_id NOT IN (
                    SELECT bd.room_id FROM Booking_detail bd
                    JOIN Booking b ON bd.booking_id = b.booking_id
                    WHERE b.booking_status NOT IN ('cancelled', 'completed')
                    AND (b.check_in_planned < ? AND b.check_out_planned > ?)
                )
            ) ORDER BY rt.price_per_day ASC";
    $room_types = db_query($sql, $search_guests, $out_time, $in_time);
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

$pricing_config = get_pricing_config();

$dynamic_multiplier_sum = 0;
$duration_days = 0;

if ($is_searched) {
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
        <img src="https://images.unsplash.com/photo-1542314831-c6a4d14d8373?auto=format&fit=crop&q=80"
            class="absolute inset-0 w-full h-full object-cover opacity-40">
        <div class="relative z-10 text-center px-4">
            <h1 class="text-5xl md:text-7xl font-black text-white mb-6 tracking-tight drop-shadow-lg">Nơi nghỉ dưỡng
                hoàn hảo</h1>
            <p class="text-xl text-slate-200 font-medium max-w-2xl mx-auto drop-shadow">Trải nghiệm dịch vụ đẳng cấp 5
                sao với không gian sang trọng và tiện nghi hiện đại.</p>
        </div>
    </div>

    <!-- Bộ lọc Tìm kiếm Nhanh (Search Bar) -->
    <div class="max-w-4xl mx-auto px-4 relative z-20 -mt-12">
        <form id="searchBar" action="#rooms" method="GET"
            class="bg-white p-3 md:p-4 rounded-2xl shadow-xl flex flex-col md:flex-row items-center gap-3 border border-slate-100">
            <div class="flex-1 w-full relative">
                <i class="fa-regular fa-calendar-check absolute left-4 top-1/2 -translate-y-1/2 text-indigo-500"></i>
                <input type="date" name="check_in" value="<?= htmlspecialchars($search_in) ?>"
                    min="<?= date('Y-m-d') ?>" required
                    class="w-full pl-11 pr-4 py-3.5 bg-slate-50 border border-slate-200 rounded-xl text-sm font-bold focus:ring-2 focus:ring-indigo-500 outline-none text-slate-700"
                    title="Ngày nhận phòng">
            </div>
            <div class="flex-1 w-full relative">
                <i class="fa-regular fa-calendar-xmark absolute left-4 top-1/2 -translate-y-1/2 text-rose-500"></i>
                <input type="date" name="check_out" value="<?= htmlspecialchars($search_out) ?>"
                    min="<?= date('Y-m-d', strtotime('+1 day')) ?>" required
                    class="w-full pl-11 pr-4 py-3.5 bg-slate-50 border border-slate-200 rounded-xl text-sm font-bold focus:ring-2 focus:ring-indigo-500 outline-none text-slate-700"
                    title="Ngày trả phòng">
            </div>
            <div class="flex-1 w-full relative">
                <i class="fa-solid fa-users absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                <select name="guests" required
                    class="w-full pl-11 pr-4 py-3.5 bg-slate-50 border border-slate-200 rounded-xl text-sm font-bold focus:ring-2 focus:ring-indigo-500 outline-none text-slate-700">
                    <option value="" disabled <?= !$search_guests ? 'selected' : '' ?>>Số người ở...</option>
                    <?php for ($i = 1; $i <= 10; $i++): ?>
                        <option value="<?= $i ?>" <?= $search_guests == $i ? 'selected' : '' ?>><?= $i ?> Người lớn</option>
                    <?php endfor; ?>
                </select>
            </div>
            <button type="submit"
                class="w-full md:w-auto bg-indigo-600 text-white px-8 py-3.5 rounded-xl font-bold shadow-lg shadow-indigo-200 hover:bg-indigo-700 transition active:scale-95 shrink-0 whitespace-nowrap">
                Tìm phòng trống
            </button>
        </form>
    </div>

    <!-- Danh sách Hạng phòng (Room Types) -->
    <div class="max-w-7xl mx-auto px-6 py-24" id="rooms">
        <div class="text-center mb-16">
            <?php if ($is_searched): ?>
                <h2 class="text-4xl font-black text-slate-800 tracking-tight">Phòng trống cho bạn</h2>
                <p class="text-indigo-600 mt-4 font-bold bg-indigo-50 inline-block px-4 py-1.5 rounded-full">
                    <i class="fa-solid fa-check mr-1"></i> Có <?= count($room_types) ?> hạng phòng phù hợp với yêu cầu của
                    bạn
                </p>
            <?php else: ?>
                <h2 class="text-4xl font-black text-slate-800 tracking-tight">Lựa chọn của bạn</h2>
                <p class="text-slate-500 mt-4 font-medium">Các hạng phòng được thiết kế chuyên biệt để mang lại sự thoải mái
                    tối đa.</p>
            <?php endif; ?>
        </div>

        <?php if (empty($room_types)): ?>
            <div class="text-center bg-white p-12 rounded-3xl border border-slate-100 shadow-sm max-w-2xl mx-auto">
                <i class="fa-solid fa-bed-pulse text-6xl text-slate-200 mb-4"></i>
                <h3 class="text-xl font-bold text-slate-700">Rất tiếc, đã hết phòng!</h3>
                <p class="text-slate-500 mt-2">Không có hạng phòng nào trống hoặc đủ sức chứa cho ngày bạn chọn. Vui lòng
                    thay đổi thông tin tìm kiếm.</p>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-10">
                <?php foreach ($room_types as $rt): ?>
                    <div
                        class="bg-white rounded-[2rem] overflow-hidden shadow-lg border border-slate-100 group hover:-translate-y-2 transition-all duration-500 flex flex-col">
                        <div class="relative h-64 overflow-hidden bg-slate-100">
                            <?php if (isset($mongo_images[$rt['type_id']])): ?>
                                <img src="data:<?php echo $mongo_images[$rt['type_id']]['mime']; ?>;base64,<?php echo $mongo_images[$rt['type_id']]['base64']; ?>"
                                    class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-700">
                            <?php else: ?>
                                <div class="flex items-center justify-center h-full text-slate-300"><i
                                        class="fa-solid fa-image text-5xl"></i></div>
                            <?php endif; ?>
                        </div>
                        <div class="p-8 flex-1 flex flex-col">
                            <h3 class="text-2xl font-black text-slate-800 mb-2 uppercase tracking-tight">
                                <?php echo htmlspecialchars($rt['name']); ?></h3>
                            <p class="text-slate-500 text-sm line-clamp-3 mb-6 flex-1">
                                <?php echo htmlspecialchars($rt['description']); ?></p>
                            <div
                                class="flex items-center gap-4 text-xs font-bold text-slate-400 uppercase tracking-widest mb-8">
                                <span><i class="fa-solid fa-user-group text-indigo-400 mr-1.5"></i>Tối đa
                                    <?php echo $rt['capacity']; ?> người</span>
                            </div>

                            <?php if (!$is_searched): ?>
                                <a href="#searchBar"
                                    onclick="document.getElementById('searchBar').classList.add('ring-4', 'ring-indigo-200', 'scale-[1.02]'); setTimeout(()=>document.getElementById('searchBar').classList.remove('ring-4', 'ring-indigo-200', 'scale-[1.02]'), 500);"
                                    class="mt-auto text-center w-full bg-slate-800 text-white py-4 rounded-xl font-bold shadow-lg hover:bg-slate-900 transition active:scale-95">Chọn
                                    ngày để xem giá</a>
                            <?php else: ?>
                                <?php
                                $total_dynamic_price = $rt['price_per_day'] * $dynamic_multiplier_sum;
                                $price_fmt = number_format($total_dynamic_price, 0, ',', '.');
                                ?>
                                <button
                                    onclick="openBookingModal(<?= $rt['type_id'] ?>, '<?= htmlspecialchars(addslashes($rt['name'])) ?>', <?= $rt['price_per_day'] ?>, <?= $rt['capacity'] ?>)"
                                    class="mt-auto w-full bg-indigo-600 text-white py-4 rounded-xl font-bold shadow-lg shadow-indigo-200 hover:bg-indigo-700 transition active:scale-95">Đặt
                                    ngay • <?= $price_fmt ?>đ / <?= $duration_days ?> đêm</button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
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

    <script src="../assets/js/toast.js"></script>
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

                        document.getElementById('discount_row').classList.remove('hidden');
                        document.getElementById('discount_row').classList.add('flex');
                        document.getElementById('discount_percent_label').innerText = `-${data.discount}%`;
                        document.getElementById('discount_amount_label').innerText = '-' + new Intl.NumberFormat(
                            'vi-VN').format(discountAmount) + 'đ';
                        document.getElementById('conf_total_price').innerText = new Intl.NumberFormat('vi-VN').format(
                            finalPrice) + 'đ';
                    } else {
                        msgLabel.className = 'text-xs font-bold mt-2 text-rose-500';
                        msgLabel.innerHTML = `<i class="fa-solid fa-circle-xmark mr-1"></i> ${data.message}`;
                        document.getElementById('cb_promo_code_hidden').value = '';
                        document.getElementById('discount_row').classList.add('hidden');
                        document.getElementById('discount_row').classList.remove('flex');
                        document.getElementById('conf_total_price').innerText = new Intl.NumberFormat('vi-VN').format(
                            baseTotalPrice) + 'đ';
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