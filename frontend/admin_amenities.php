<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header("Location: login.php");
    exit();
}
require_once '../dao/room_dao.php';

$amenities = amenity_get_all();
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý Tiện ích - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body class="bg-slate-50 flex h-screen overflow-hidden">

    <!-- Sidebar -->
    <?php include 'sidebar_admin.php'; ?>

    <main class="flex-1 overflow-y-auto p-4 md:p-8">
        <div class="mb-8 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-slate-800">Quản lý Tiện ích phòng</h1>
                <p class="text-slate-500 text-sm">Thêm và xóa các tiện ích (Amenities) được lưu trữ trên MongoDB.</p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Form Thêm Tiện Ích -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 sticky top-4">
                    <h2 class="text-lg font-bold text-slate-800 mb-4"><i
                            class="fa-solid fa-plus text-indigo-500 mr-2"></i>Thêm Tiện ích mới</h2>
                    <form action="../actions/process_amenity.php" method="POST" class="space-y-4">
                        <input type="hidden" name="action" value="add">
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">Tên Tiện Ích</label>
                            <input type="text" name="name" required placeholder="Nhập tên tiện ích"
                                class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none transition-all">
                        </div>
                        <button type="submit"
                            class="w-full bg-indigo-600 text-white px-4 py-3 rounded-xl font-bold shadow-lg shadow-indigo-100 hover:bg-indigo-700 transition">
                            Lưu Tiện Ích
                        </button>
                    </form>
                </div>
            </div>

            <!-- Danh sách Tiện Ích -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                    <table class="w-full text-left">
                        <thead class="bg-slate-50 border-b border-slate-100">
                            <tr class="text-slate-500 text-[10px] uppercase tracking-widest font-bold">
                                <th class="px-6 py-4">Danh sách Tiện ích</th>
                                <th class="px-6 py-4 text-right">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-sm">
                            <?php if (empty($amenities)): ?>
                            <tr>
                                <td colspan="2" class="px-6 py-8 text-center text-slate-500 italic">Chưa có tiện ích
                                    nào.</td>
                            </tr>
                            <?php else: ?>
                            <?php foreach ($amenities as $amn): ?>
                            <tr class="hover:bg-slate-50/50 transition">
                                <td class="px-6 py-4 font-bold text-slate-700">
                                    <i class="fa-solid fa-check text-emerald-500 mr-2"></i>
                                    <?= htmlspecialchars($amn) ?>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <form action="../actions/process_amenity.php" method="POST" class="inline">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="name" value="<?= htmlspecialchars($amn) ?>">
                                        <button type="submit"
                                            onclick="return confirm('Xóa tiện ích này sẽ gỡ nó khỏi tất cả các hạng phòng đang có. Bạn chắc chắn chứ?')"
                                            class="bg-red-100 text-red-600 px-3 py-1.5 rounded-lg text-[10px] font-bold hover:bg-red-200 transition">
                                            <i class="fa-solid fa-trash"></i> Xóa
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <script src="../assets/js/toast.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', () => {
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('msg') === 'added') showToast('Thêm tiện ích thành công!', 'success');
        if (urlParams.get('msg') === 'deleted') showToast('Xóa tiện ích thành công!', 'success');
        if (urlParams.get('error') === 'exists') showToast('Tiện ích này đã tồn tại!', 'error');

        if (window.history.replaceState) {
            window.history.replaceState({}, document.title, window.location.pathname);
        }
    });
    </script>
</body>

</html>