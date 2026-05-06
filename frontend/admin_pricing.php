<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header("Location: login.php");
    exit();
}
require_once '../dao/booking_dao.php';
$pricing_config = get_pricing_config();
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cấu hình Giá Phòng - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body class="bg-slate-50 text-slate-800 flex h-screen overflow-hidden">

    <?php include 'sidebar_admin.php'; ?>

    <main class="flex-1 overflow-y-auto p-4 md:p-8">

        <div class="mb-8 flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-slate-800 flex items-center gap-3">
                    Cấu hình Giá phòng động
                </h1>
                <p class="text-slate-500 text-sm mt-1 ml-13">Quản lý hệ số phụ thu cho ngày cuối tuần và các dịp Lễ,
                    Tết.</p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

            <div class="lg:col-span-2">
                <form action="../actions/process_pricing.php" method="POST"
                    class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                    <input type="hidden" name="action" value="update_pricing">

                    <div class="p-6 md:p-8 space-y-8">
                        <div>
                            <h2 class="text-lg font-bold text-slate-800 mb-4 flex items-center gap-2">
                                <i class="fa-solid fa-calculator text-indigo-500"></i> Thiết lập Hệ số
                            </h2>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-bold text-slate-700 mb-2">Phụ thu Cuối tuần (T7,
                                        CN)</label>
                                    <div class="relative">
                                        <div
                                            class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                            <i class="fa-solid fa-calendar-week text-slate-400"></i>
                                        </div>
                                        <input type="number" step="0.1" name="weekend_multiplier"
                                            value="<?= htmlspecialchars($pricing_config['weekend_multiplier']) ?>"
                                            class="w-full pl-11 pr-4 py-3 bg-slate-50 rounded-xl border border-slate-200 focus:bg-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-all font-semibold text-slate-700"
                                            required>
                                        <div
                                            class="absolute inset-y-0 right-0 pr-4 flex items-center pointer-events-none text-slate-400 text-sm font-medium">
                                            x Giá gốc
                                        </div>
                                    </div>
                                    <p class="text-[11px] text-slate-500 mt-2 font-medium">
                                        <span class="text-indigo-500 font-bold">Mẹo:</span> Nhập <code
                                            class="bg-slate-100 px-1 py-0.5 rounded">1.2</code> để tăng 20%, <code
                                            class="bg-slate-100 px-1 py-0.5 rounded">1.0</code> để giữ nguyên giá.
                                    </p>
                                </div>

                                <div>
                                    <label class="block text-sm font-bold text-slate-700 mb-2">Phụ thu Ngày Lễ</label>
                                    <div class="relative">
                                        <div
                                            class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                            <i class="fa-solid fa-calendar-week text-slate-400"></i>
                                        </div>
                                        <input type="number" step="0.1" name="holiday_multiplier"
                                            value="<?= htmlspecialchars($pricing_config['holiday_multiplier']) ?>"
                                            class="w-full pl-11 pr-4 py-3 bg-slate-50 rounded-xl border border-slate-200 focus:bg-white focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none transition-all font-semibold text-slate-700"
                                            required>
                                        <div
                                            class="absolute inset-y-0 right-0 pr-4 flex items-center pointer-events-none text-slate-400 text-sm font-medium">
                                            x Giá gốc
                                        </div>
                                    </div>
                                    <p class="text-[11px] text-slate-500 mt-2 font-medium">
                                        <span class="text-indigo-500 font-bold">Mẹo:</span> Nhập <code
                                            class="bg-slate-100 px-1 py-0.5 rounded">1.5</code> để tăng 50% so với giá
                                        ngày thường.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <hr class="border-slate-100">

                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <label class="block text-sm font-bold text-slate-700">Danh sách Ngày Lễ trong
                                    năm</label>
                                <span class="text-[10px] uppercase tracking-wider font-bold text-slate-400">Định dạng:
                                    DD-MM</span>
                            </div>

                            <div class="relative">
                                <div class="absolute top-4 left-4 pointer-events-none">
                                    <i class="fa-solid fa-calendar-days text-slate-400"></i>
                                </div>
                                <textarea name="holidays_list" rows="3"
                                    class="w-full pl-11 pr-4 py-3 bg-slate-50 rounded-xl border border-slate-200 focus:bg-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-all text-sm leading-relaxed"
                                    placeholder="Ví dụ: 01-01, 14-02, 30-04, 01-05, 02-09..."
                                    required><?= htmlspecialchars(is_array($pricing_config['holidays']) ? implode(', ', $pricing_config['holidays']) : $pricing_config['holidays']) ?></textarea>
                            </div>
                            <div class="flex flex-wrap gap-2 mt-3">
                                <span class="text-xs text-slate-500 flex items-center">Phân cách các ngày bằng dấu phẩy
                                    (,).</span>
                            </div>
                        </div>
                    </div>

                    <div class="px-6 py-4 bg-slate-50 border-t border-slate-100 flex justify-end">
                        <button type="submit"
                            class="bg-indigo-600 text-white px-6 py-2.5 rounded-xl font-bold shadow-md shadow-indigo-200 hover:bg-indigo-700 hover:shadow-lg transition-all flex items-center gap-2">
                            <i class="fa-solid fa-floppy-disk"></i> Lưu Cấu Hình
                        </button>
                    </div>
                </form>
            </div>

            <!-- <div class="lg:col-span-1">
                <div
                    class="bg-gradient-to-br from-indigo-500 to-purple-600 rounded-2xl shadow-md p-6 text-white sticky top-4">
                    <div class="flex items-center gap-3 mb-4">
                        <div
                            class="w-10 h-10 bg-white/20 rounded-full flex items-center justify-center backdrop-blur-sm">
                            <i class="fa-solid fa-lightbulb text-yellow-300"></i>
                        </div>
                        <h3 class="font-bold text-lg">Cách tính giá động</h3>
                    </div>

                    <p class="text-sm text-indigo-100 mb-6 leading-relaxed">
                        Hệ thống sẽ tự động nhân giá phòng gốc với hệ số bạn cấu hình tùy thuộc vào thời điểm khách hàng
                        đặt phòng. Ưu tiên Ngày Lễ trước, sau đó mới đến Cuối tuần.
                    </p>

                    <div class="space-y-4">
                        <div class="bg-white/10 rounded-xl p-4 backdrop-blur-sm border border-white/20">
                            <p class="text-xs text-indigo-200 uppercase tracking-wider font-bold mb-1">Ví dụ minh họa
                            </p>
                            <p class="text-sm">Phòng Standard có giá gốc: <span
                                    class="font-black text-yellow-300">500.000đ</span></p>

                            <ul class="mt-3 space-y-2 text-sm text-indigo-50">
                                <li class="flex items-start gap-2">
                                    <i class="fa-solid fa-check text-emerald-400 mt-1"></i>
                                    <div>
                                        <span class="font-bold">Ngày thường:</span><br>
                                        500.000đ <span class="text-indigo-200 text-xs">(Không đổi)</span>
                                    </div>
                                </li>
                                <li class="flex items-start gap-2">
                                    <i class="fa-solid fa-check text-emerald-400 mt-1"></i>
                                    <div>
                                        <span class="font-bold">Cuối tuần (Hệ số 1.2):</span><br>
                                        500.000 x 1.2 = <span class="font-bold text-white">600.000đ</span>
                                    </div>
                                </li>
                                <li class="flex items-start gap-2">
                                    <i class="fa-solid fa-check text-emerald-400 mt-1"></i>
                                    <div>
                                        <span class="font-bold">Ngày Lễ (Hệ số 1.5):</span><br>
                                        500.000 x 1.5 = <span class="font-bold text-white">750.000đ</span>
                                    </div>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div> -->

        </div>
    </main>

    <script src="../assets/js/toast.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', () => {
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('msg') === 'success') {
            if (typeof showToast === 'function') {
                showToast('Đã cập nhật cấu hình giá thành công!', 'success');
            } else {
                alert('Đã cập nhật cấu hình giá thành công!');
            }
            // Xóa parameter trên URL để khi f5 không hiện lại thông báo
            window.history.replaceState({}, document.title, window.location.pathname);
        }
    });
    </script>
</body>

</html>