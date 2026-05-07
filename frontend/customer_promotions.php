<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
require_once '../config/mongodb.php';

try {
    $mongo_db = mongo_get_db();

    // 1. Lấy tất cả mã đang active và còn lượt
    $all_promotions = $mongo_db->promotions->find([
        'status' => 'active',
        'quantity' => ['$gt' => 0]
    ])->toArray();

    // 2. Lọc bằng PHP (Vì cần tính toán expires_at từ created_at + duration_day)
    $promotions = [];
    $now_timestamp = time();
    foreach ($all_promotions as $promo) {
        $created_ts = isset($promo['created_at']) ? $promo['created_at']->toDateTime()->getTimestamp() : $now_timestamp;
        $duration_seconds = (isset($promo['duration_day']) ? (int)$promo['duration_day'] : 0) * 86400;
        $expires_at = $created_ts + $duration_seconds;

        if ($expires_at > $now_timestamp) {
            $promo['expires_at'] = $expires_at;
            $promotions[] = $promo;
        }
    }
} catch (Exception $e) {
    $promotions = [];
}
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Khuyến mãi - Grand Horizon</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body class="bg-slate-50">
    <?php include 'navbar_customer.php'; ?>

    <main class="max-w-7xl mx-auto px-4 py-32">
        <div class="text-center mb-16">
            <h1 class="text-4xl font-black text-slate-800 tracking-tight mb-4">Mã Khuyến Mãi & Ưu Đãi</h1>
            <p class="text-slate-500">Thu thập mã giảm giá để tận hưởng kỳ nghỉ với mức giá tốt nhất.</p>
        </div>

        <?php if (empty($promotions)): ?>
            <div class="bg-white rounded-3xl p-12 text-center border border-slate-100 shadow-sm max-w-2xl mx-auto">
                <i class="fa-solid fa-ticket text-6xl text-slate-200 mb-4"></i>
                <h2 class="text-xl font-bold text-slate-700">Hiện tại chưa có khuyến mãi nào</h2>
                <p class="text-slate-500 mt-2">Vui lòng quay lại sau để cập nhật các ưu đãi mới nhất từ Grand Horizon.</p>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <?php foreach ($promotions as $promo): ?>
                    <div
                        class="bg-white rounded-3xl border border-slate-100 shadow-sm hover:shadow-xl transition-all duration-300 overflow-hidden group">
                        <div class="bg-gradient-to-r from-indigo-500 to-purple-600 p-8 text-center relative overflow-hidden">
                            <div class="absolute -right-6 -top-6 w-24 h-24 bg-white/20 rounded-full blur-xl"></div>
                            <div class="absolute -left-6 -bottom-6 w-24 h-24 bg-white/20 rounded-full blur-xl"></div>
                            <h3 class="text-5xl font-black text-white mb-2">-<?= $promo['discount_percent'] ?>%</h3>
                            <p class="text-indigo-100 font-medium">Giảm giá trực tiếp vào hóa đơn</p>
                        </div>
                        <div class="p-8 relative">
                            <div
                                class="absolute -top-4 left-1/2 -translate-x-1/2 w-8 h-8 bg-white rounded-full border-t border-slate-100">
                            </div>
                            <h4 class="text-lg font-bold text-slate-800 mb-3"><?= htmlspecialchars($promo['description']) ?>
                            </h4>
                            <p class="text-sm text-slate-500 font-medium">
                                <i class="fa-regular fa-clock text-slate-400 mr-1"></i> Hết hạn: <span
                                    class="text-rose-500"><?= date('d/m/Y', $promo['expires_at']) ?></span>
                            </p>
                            <div
                                class="flex items-center justify-between mt-6 bg-slate-50 rounded-2xl p-2 border border-slate-100">
                                <span
                                    class="font-mono font-bold text-indigo-600 tracking-widest pl-4"><?= htmlspecialchars($promo['code']) ?></span>
                                <button
                                    onclick="navigator.clipboard.writeText('<?= htmlspecialchars($promo['code']) ?>'); showToast('Đã sao chép mã giảm giá!', 'success');"
                                    class="bg-indigo-600 text-white px-4 py-2 rounded-xl text-sm font-bold hover:bg-indigo-700 transition">Sao
                                    chép</button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>

    <script src="../assets/js/toast.js"></script>
</body>

</html>