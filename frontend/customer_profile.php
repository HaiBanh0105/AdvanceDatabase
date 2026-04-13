<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
require_once '../dao/DAO.php';

$user_id = $_SESSION['user_id'];

// 1. Lấy thông tin cá nhân và số dư
$sql_profile = "SELECT a.email, c.phone, c.full_name, c.cccd as ID_number, c.nation, c.address, a.status
                FROM Account a JOIN Customer c ON a.customer_id = c.customer_id WHERE a.account_id = ?";
$profile = db_query_one($sql_profile, $user_id);

// 2. Lấy thông tin tài khoản ngân hàng (nếu có)
$sql_bank = "SELECT provider, card_id, expiry_date, cardholder_name FROM Bank_account WHERE account_id = ?";
$bank = db_query_one($sql_bank, $user_id);
?>
<!DOCTYPE html>
<!-- Giao diện Hồ sơ cá nhân -->
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tài khoản của tôi - Grand Horizon</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body class="bg-slate-50">

    <?php include 'navbar_customer.php'; ?>

    <main class="max-w-7xl mx-auto px-4 py-24">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

            <div class="space-y-6">
                <div
                    class="bg-indigo-600 rounded-[2.5rem] p-8 text-white shadow-xl shadow-indigo-200 relative overflow-hidden">
                    <div class="relative z-10">
                        <p class="text-indigo-100 text-xs font-bold uppercase tracking-widest mb-2">Số dư hiện tại</p>
                        <h2 class="text-4xl font-black mb-6">
                            <?php echo number_format($profile['balance'] ?? 0, 0, ',', '.'); ?>đ</h2>
                        <button onclick="checkBankAndDeposit(<?php echo $bank ? 'true' : 'false'; ?>)"
                            class="bg-white/20 hover:bg-white/30 backdrop-blur-md text-white w-full py-3 rounded-2xl font-bold transition">
                            <i class="fa-solid fa-plus-circle mr-2"></i> Nạp thêm tiền
                        </button>
                    </div>
                    <div class="absolute -right-10 -bottom-10 w-40 h-40 bg-white/10 rounded-full"></div>
                </div>

                <div class="bg-white rounded-[2.5rem] p-8 border border-slate-100 shadow-sm">
                    <h3 class="font-bold text-slate-800 mb-6 flex justify-between items-center">
                        Thẻ liên kết
                        <button onclick="toggleModal('linkBankModal')"
                            class="text-indigo-600 text-xs font-bold hover:underline"><i class="fa-solid fa-plus"></i>
                            Thêm/Đổi</button>
                    </h3>
                    <div class="space-y-4">
                        <?php if ($bank): ?>
                        <!-- Đã liên kết thẻ -->
                        <div
                            class="flex items-center gap-4 p-4 bg-slate-50 rounded-2xl border border-dashed border-slate-200">
                            <div
                                class="w-12 h-8 bg-slate-800 rounded flex items-center justify-center text-[10px] text-white font-bold italic">
                                <?php echo htmlspecialchars(strtoupper($bank['provider'])); ?></div>
                            <div class="flex-1">
                                <p class="text-xs font-bold text-slate-700">**** **** ****
                                    <?php echo htmlspecialchars(substr($bank['card_id'], -4)); ?></p>
                                <p class="text-[10px] text-slate-400 uppercase">Hết hạn:
                                    <?php echo date('m/y', strtotime($bank['expiry_date'])); ?></p>
                            </div>
                            <form action="../actions/process_delete_bank.php" method="POST"
                                onsubmit="return confirm('Bạn có chắc chắn muốn hủy liên kết thẻ ngân hàng này?');">
                                <button type="submit" title="Hủy liên kết"
                                    class="text-slate-300 hover:text-red-500 transition"><i
                                        class="fa-solid fa-trash"></i></button>
                            </form>
                        </div>
                        <?php else: ?>
                        <!-- Chưa có thẻ -->
                        <div class="text-center p-6 bg-slate-50 rounded-2xl border border-dashed border-slate-200">
                            <i class="fa-solid fa-credit-card text-slate-300 text-3xl mb-3"></i>
                            <p class="text-xs text-slate-500 mb-4">Bạn chưa liên kết thẻ ngân hàng nào.</p>
                            <button onclick="toggleModal('linkBankModal')"
                                class="bg-indigo-100 text-indigo-600 px-4 py-2 rounded-xl text-xs font-bold hover:bg-indigo-200 transition">
                                <i class="fa-solid fa-plus mr-1"></i> Liên kết ngay
                            </button>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="lg:col-span-2">
                <div class="bg-white rounded-[2.5rem] p-8 md:p-10 border border-slate-100 shadow-sm">
                    <div class="flex justify-between items-center mb-8">
                        <h2 class="text-2xl font-black text-slate-800 tracking-tight">Thông tin cá nhân</h2>
                        <?php if (empty($profile['ID_number'])): ?>
                        <span
                            class="px-3 py-1 bg-slate-100 text-slate-600 rounded-full text-[10px] font-bold uppercase border border-slate-200"
                            title="Vui lòng cập nhật số CCCD để gửi yêu cầu phê duyệt">Chưa phê duyệt</span>
                        <?php elseif (isset($profile['status']) && $profile['status'] === 'active'): ?>
                        <span
                            class="px-3 py-1 bg-emerald-100 text-emerald-600 rounded-full text-[10px] font-bold uppercase">Đã
                            xác thực</span>
                        <?php else: ?>
                        <span class="px-3 py-1 bg-amber-100 text-amber-600 rounded-full text-[10px] font-bold uppercase"
                            title="Vui lòng chờ Admin duyệt để có thể đặt phòng">Chờ phê duyệt</span>
                        <?php endif; ?>
                    </div>

                    <form action="../actions/process_update_profile.php" method="POST"
                        class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-xs font-bold text-slate-400 uppercase mb-2 ml-1">Họ và tên</label>
                            <input type="text" name="full_name"
                                value="<?php echo htmlspecialchars($profile['full_name'] ?? ''); ?>" required
                                class="w-full px-5 py-3.5 bg-slate-50 border border-slate-100 rounded-2xl focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:bg-white transition-all text-sm font-semibold text-slate-700">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-400 uppercase mb-2 ml-1">Email</label>
                            <input type="email" name="email"
                                value="<?php echo htmlspecialchars($profile['email'] ?? ''); ?>" readonly
                                class="w-full px-5 py-3.5 bg-slate-100 border border-slate-100 rounded-2xl text-slate-400 text-sm font-medium cursor-not-allowed">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-400 uppercase mb-2 ml-1">Số điện
                                thoại</label>
                            <input type="tel" name="phone"
                                value="<?php echo htmlspecialchars($profile['phone'] ?? ''); ?>" required
                                class="w-full px-5 py-3.5 bg-slate-50 border border-slate-100 rounded-2xl focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:bg-white transition-all text-sm font-semibold text-slate-700">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-400 uppercase mb-2 ml-1">Ngày sinh</label>
                            <input type="date" name="dob" value="<?php echo htmlspecialchars($profile['dob'] ?? ''); ?>"
                                required
                                class="w-full px-5 py-3.5 bg-slate-50 border border-slate-100 rounded-2xl focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:bg-white transition-all text-sm font-semibold text-slate-700">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-400 uppercase mb-2 ml-1">Số
                                CCCD/Passport</label>
                            <input type="text" name="id_number"
                                value="<?php echo htmlspecialchars($profile['ID_number'] ?? ''); ?>" required
                                pattern="\d{12}" maxlength="12" title="Vui lòng nhập chính xác 12 chữ số"
                                class="w-full px-5 py-3.5 bg-slate-50 border border-slate-100 rounded-2xl focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:bg-white transition-all text-sm font-semibold text-slate-700"
                                placeholder="Vui lòng nhập đúng 12 chữ số CCCD">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-400 uppercase mb-2 ml-1">Quốc tịch</label>
                            <?php $current_nation = $profile['nation'] ?? 'Việt Nam'; ?>
                            <select name="nation"
                                class="w-full px-5 py-3.5 bg-slate-50 border border-slate-100 rounded-2xl focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:bg-white transition-all text-sm font-semibold text-slate-700">
                                <option value="Việt Nam" <?php echo $current_nation == 'Việt Nam' ? 'selected' : ''; ?>>
                                    Việt Nam</option>
                                <option value="Hoa Kỳ" <?php echo $current_nation == 'Hoa Kỳ' ? 'selected' : ''; ?>>Hoa
                                    Kỳ</option>
                                <option value="Nga" <?php echo $current_nation == 'Nga' ? 'selected' : ''; ?>>Nga
                                </option>
                                <option value="Hàn Quốc" <?php echo $current_nation == 'Hàn Quốc' ? 'selected' : ''; ?>>
                                    Hàn Quốc</option>
                                <option value="Nhật Bản" <?php echo $current_nation == 'Nhật Bản' ? 'selected' : ''; ?>>
                                    Nhật Bản</option>
                                <option value="Trung Quốc"
                                    <?php echo $current_nation == 'Trung Quốc' ? 'selected' : ''; ?>>Trung Quốc</option>
                                <option value="Anh Quốc" <?php echo $current_nation == 'Anh Quốc' ? 'selected' : ''; ?>>
                                    Anh Quốc</option>
                                <option value="Pháp" <?php echo $current_nation == 'Pháp' ? 'selected' : ''; ?>>Pháp
                                </option>
                                <option value="Khác" <?php echo $current_nation == 'Khác' ? 'selected' : ''; ?>>Khác...
                                </option>
                            </select>
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-xs font-bold text-slate-400 uppercase mb-2 ml-1">Địa chỉ thường
                                trú</label>
                            <textarea name="address" rows="2" required
                                class="w-full px-5 py-3.5 bg-slate-50 border border-slate-100 rounded-2xl focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:bg-white transition-all text-sm font-semibold text-slate-700"><?php echo htmlspecialchars($profile['address'] ?? ''); ?></textarea>
                        </div>

                        <div class="md:col-span-2 pt-4">
                            <?php if (isset($profile['status']) && $profile['status'] === 'active'): ?>
                            <div
                                class="p-4 bg-emerald-50 text-emerald-600 rounded-2xl text-sm font-bold text-center border border-emerald-100">
                                <i class="fa-solid fa-shield-check mr-2"></i> Hồ sơ của bạn đã được Admin phê duyệt. Bạn
                                không thể tự thay đổi thông tin.
                            </div>
                            <?php else: ?>
                            <button type="submit"
                                class="bg-indigo-600 text-white px-8 py-4 rounded-2xl font-bold hover:bg-indigo-700 transition shadow-lg shadow-indigo-100 w-full md:w-auto">
                                <?php echo empty($profile['ID_number']) ? 'Yêu cầu phê duyệt' : 'Lưu thay đổi thông tin'; ?>
                            </button>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </main>

    <!-- Modal Liên kết ngân hàng -->
    <div id="linkBankModal"
        class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-[100] hidden flex items-center justify-center p-4">
        <div
            class="bg-white rounded-[2.5rem] w-full max-w-md shadow-2xl overflow-hidden animate-in zoom-in duration-300">
            <div class="p-6 border-b border-slate-100 flex justify-between items-center bg-slate-50/50">
                <h3 class="font-bold text-lg text-slate-800 uppercase tracking-tight">Liên kết ngân hàng</h3>
                <button onclick="toggleModal('linkBankModal')" class="text-slate-400 hover:text-slate-600"><i
                        class="fa-solid fa-xmark"></i></button>
            </div>
            <form action="../actions/process_link_bank.php" method="POST" class="p-8 space-y-5">
                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Ngân hàng
                        / Loại thẻ</label>
                    <select name="provider" required
                        class="w-full px-5 py-3 bg-slate-50 border border-slate-200 rounded-2xl focus:ring-2 focus:ring-indigo-500 outline-none text-sm font-semibold text-slate-700">
                        <option value="vietcombank">Vietcombank</option>
                        <option value="techcombank">Techcombank</option>
                        <option value="mb">MB Bank</option>
                        <option value="visa">Thẻ VISA Quốc tế</option>
                        <option value="mastercard">Thẻ MasterCard</option>
                    </select>
                </div>
                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Tên in
                        trên thẻ (Không dấu)</label>
                    <input type="text" name="cardholder_name" required autocomplete="off" placeholder="NGUYEN VAN A"
                        class="w-full px-5 py-3 bg-slate-50 border border-slate-200 rounded-2xl focus:ring-2 focus:ring-indigo-500 outline-none text-sm uppercase">
                </div>
                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Số thẻ (16
                        số)</label>
                    <input type="text" name="card_id" required pattern="\d{16}" maxlength="16"
                        placeholder="1234 5678 1234 5678"
                        class="w-full px-5 py-3 bg-slate-50 border border-slate-200 rounded-2xl focus:ring-2 focus:ring-indigo-500 outline-none text-sm tracking-widest font-mono">
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Ngày
                            hết hạn</label>
                        <input type="month" name="expiry_date" required
                            class="w-full px-5 py-3 bg-slate-50 border border-slate-200 rounded-2xl focus:ring-2 focus:ring-indigo-500 outline-none text-sm">
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Mã CVV
                            (3 số)</label>
                        <input type="text" name="cvv" required pattern="\d{3}" maxlength="3" placeholder="123"
                            class="w-full px-5 py-3 bg-slate-50 border border-slate-200 rounded-2xl focus:ring-2 focus:ring-indigo-500 outline-none text-sm tracking-widest text-center font-mono">
                    </div>
                </div>
                <button type="submit"
                    class="w-full mt-4 px-4 py-4 bg-indigo-600 text-white rounded-2xl font-bold shadow-lg shadow-indigo-100 hover:bg-indigo-700 transition">Xác
                    nhận liên kết</button>
            </form>
        </div>
    </div>

    <script src="../assets/js/toast.js"></script>
    <script>
    function toggleModal(modalId) {
        const modal = document.getElementById(modalId);
        modal.classList.toggle('hidden');
    }

    function checkBankAndDeposit(hasBank) {
        if (!hasBank) {
            showToast("Vui lòng liên kết tài khoản ngân hàng trước khi tiến hành nạp tiền!", "warning");
            toggleModal('linkBankModal');
        } else {
            showToast("Hệ thống cổng thanh toán đang được bảo trì. Vui lòng quay lại sau!", "warning");
        }
    }

    document.addEventListener("DOMContentLoaded", function() {
        const urlParams = new URLSearchParams(window.location.search);

        if (urlParams.get('update') === 'success') {
            showToast('Cập nhật thông tin cá nhân thành công!', 'success');
        } else if (urlParams.get('update') === 'bank_success') {
            showToast('Liên kết tài khoản ngân hàng thành công!', 'success');
        } else if (urlParams.get('error') === 'invalid_id') {
            showToast('Lỗi: Căn cước công dân phải đủ 12 số!', 'error');
        } else if (urlParams.get('error') === 'bank_duplicate') {
            showToast('Lỗi: Số thẻ đã được liên kết với tài khoản khác!', 'error');
        } else if (urlParams.get('update') === 'bank_deleted') {
            showToast('Đã hủy liên kết thẻ ngân hàng!', 'info');
        }
    });
    </script>
</body>

</html>