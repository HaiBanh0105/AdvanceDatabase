<?php
session_start();
require_once '../dao/room_dao.php';
require_once '../config/mongodb.php';

// Lấy dữ liệu Hạng phòng
$room_types = room_type_get_all('price ASC');

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

    <!-- Danh sách Hạng phòng (Room Types) -->
    <div class="max-w-7xl mx-auto px-6 py-24" id="rooms">
        <div class="text-center mb-16">
            <h2 class="text-4xl font-black text-slate-800 tracking-tight">Lựa chọn của bạn</h2>
            <p class="text-slate-500 mt-4 font-medium">Các hạng phòng được thiết kế chuyên biệt để mang lại sự thoải mái
                tối đa.</p>
        </div>

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
                    <!-- Nút mở modal đặt phòng -->
                    <button
                        onclick="openBookingModal(<?php echo $rt['type_id']; ?>, '<?php echo htmlspecialchars(addslashes($rt['name'])); ?>', <?php echo $rt['price_per_hour']; ?>, <?php echo $rt['price_per_day']; ?>)"
                        class="mt-auto w-full bg-indigo-600 text-white py-4 rounded-xl font-bold shadow-lg shadow-indigo-200 hover:bg-indigo-700 transition active:scale-95">Xem
                        giá & Đặt phòng</button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>


    <?php
    include 'review_section.php';
    ?>

    <?php
    include 'footer.php';
    ?>

    <!-- Modal Đặt phòng Khách hàng -->
    <div id="customerBookingModal"
        class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 hidden items-center justify-center p-4">
        <div
            class="bg-white rounded-[2.5rem] w-full max-w-md shadow-2xl overflow-hidden animate-in zoom-in duration-200">
            <div class="p-6 border-b border-slate-100 flex justify-between items-center bg-slate-50">
                <h3 class="font-bold text-lg text-slate-800 uppercase tracking-tight" id="cb_modal_title">Đặt phòng</h3>
                <button onclick="closeBookingModal()" class="text-slate-400 hover:text-slate-600"><i
                        class="fa-solid fa-xmark text-lg"></i></button>
            </div>
            <div class="p-6 bg-indigo-50/50 border-b border-indigo-100 flex justify-between text-sm">
                <div>
                    <p class="text-[10px] font-black text-indigo-400 uppercase tracking-widest">Giá thuê giờ</p>
                    <p class="font-bold text-indigo-600 text-lg" id="cb_price_hr"></p>
                </div>
                <div class="text-right">
                    <p class="text-[10px] font-black text-rose-400 uppercase tracking-widest">Giá thuê ngày</p>
                    <p class="font-bold text-rose-600 text-lg" id="cb_price_day"></p>
                </div>
            </div>
            <form id="customerBookingForm" class="p-8 space-y-5">
                <input type="hidden" name="type_id" id="cb_type_id">
                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Nhận phòng
                        (Check-in)</label>
                    <input type="datetime-local" name="check_in" required
                        class="w-full px-5 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm font-bold focus:ring-2 focus:ring-indigo-500 outline-none transition">
                </div>
                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Trả phòng
                        (Check-out)</label>
                    <input type="datetime-local" name="check_out" required
                        class="w-full px-5 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm font-bold focus:ring-2 focus:ring-indigo-500 outline-none transition">
                </div>
                <button type="submit" id="btnSubmitBooking"
                    class="w-full mt-4 bg-indigo-600 text-white py-4 rounded-2xl font-bold shadow-lg shadow-indigo-200 hover:bg-indigo-700 transition flex justify-center items-center gap-2 active:scale-95">
                    <span id="btnSubmitText">Xác nhận Đặt phòng</span>
                    <i id="btnSubmitSpinner" class="fa-solid fa-spinner fa-spin hidden"></i>
                </button>
            </form>
        </div>
    </div>

    <script src="../assets/js/toast.js"></script>
    <script>
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
    });

    function openBookingModal(typeId, name, priceHr, priceDay) {
        document.getElementById('cb_type_id').value = typeId;
        document.getElementById('cb_modal_title').innerText = 'Đặt ' + name;
        document.getElementById('cb_price_hr').innerText = new Intl.NumberFormat('vi-VN').format(priceHr) + 'đ/h';
        document.getElementById('cb_price_day').innerText = new Intl.NumberFormat('vi-VN').format(priceDay) + 'đ/ngày';

        document.getElementById('customerBookingModal').classList.remove('hidden');
        document.getElementById('customerBookingModal').classList.add('flex');
    }

    function closeBookingModal() {
        document.getElementById('customerBookingModal').classList.add('hidden');
        document.getElementById('customerBookingModal').classList.remove('flex');
    }

    // Xử lý gửi Form Đặt phòng bằng AJAX (Không reload trang)
    document.getElementById('customerBookingForm').addEventListener('submit', function(e) {
        e.preventDefault();

        const btnText = document.getElementById('btnSubmitText');
        const btnSpinner = document.getElementById('btnSubmitSpinner');
        const submitBtn = document.getElementById('btnSubmitBooking');

        submitBtn.disabled = true;
        btnText.innerText = "Đang xử lý...";
        btnSpinner.classList.remove('hidden');

        const formData = new FormData(this);

        fetch('../actions/process_booking.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                submitBtn.disabled = false;
                btnText.innerText = "Xác nhận Đặt phòng";
                btnSpinner.classList.add('hidden');

                showToast(data.message, data.status);
                if (data.status === 'success') {
                    closeBookingModal();
                }
            })
            .catch(error => {
                showToast('Lỗi kết nối đến máy chủ!', 'error');
            });
    });
    </script>
</body>

</html>