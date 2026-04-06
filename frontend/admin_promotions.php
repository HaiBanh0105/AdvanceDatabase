<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header("Location: login.php");
    exit();
}
require_once '../config/mongo.php';

$db = mongo_get_db();
$promotions = $db->promotions->find([])->toArray();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Quản lý Khuyến mãi - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-slate-50 p-10">
    <div class="max-w-5xl mx-auto">
        <div class="flex justify-between items-center mb-8">
            <h1 class="text-3xl font-bold text-slate-800"><i class="fa-solid fa-tags text-indigo-500"></i> Quản lý Mã Khuyến Mãi (MongoDB)</h1>
            <a href="admin_dashboard.php" class="text-indigo-600 font-bold hover:underline">Quay lại Dashboard</a>
        </div>

        <!-- Form Thêm mới Khuyến mãi -->
        <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200 mb-8">
            <h2 class="text-lg font-bold mb-4">Thêm Mã Khuyến Mãi Mới</h2>
            <form action="../actions/process_promotion.php" method="POST" class="flex gap-4 items-start">
                <input type="hidden" name="action" value="add">
                <div class="flex-1">
                    <input type="text" name="code" placeholder="Mã giảm giá (VD: TET2024)" required class="w-full px-4 py-2 border rounded-xl focus:ring-2 focus:ring-indigo-500 uppercase">
                </div>
                <div class="w-32">
                    <input type="number" name="discount" placeholder="% Giảm" min="1" max="100" required class="w-full px-4 py-2 border rounded-xl focus:ring-2 focus:ring-indigo-500">
                </div>
                <div class="flex-2">
                    <input type="text" name="description" placeholder="Mô tả khuyến mãi" required class="w-full px-4 py-2 border rounded-xl focus:ring-2 focus:ring-indigo-500">
                </div>
                <button type="submit" class="bg-indigo-600 text-white px-6 py-2 rounded-xl font-bold hover:bg-indigo-700">Thêm</button>
            </form>
        </div>

        <!-- Danh sách Khuyến mãi -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
            <table class="w-full text-left">
                <thead class="bg-slate-100 text-slate-500 text-sm uppercase">
                    <tr>
                        <th class="p-4">Mã Khuyến Mãi</th>
                        <th class="p-4">Giảm giá</th>
                        <th class="p-4">Mô tả</th>
                        <th class="p-4">Trạng thái</th>
                        <th class="p-4 text-right">Thao tác</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php foreach ($promotions as $promo): ?>
                    <tr class="hover:bg-slate-50">
                        <td class="p-4 font-bold text-indigo-600"><?= htmlspecialchars($promo['code']) ?></td>
                        <td class="p-4 font-bold text-red-500">-<?= $promo['discount_percent'] ?>%</td>
                        <td class="p-4 text-slate-600 text-sm"><?= htmlspecialchars($promo['description']) ?></td>
                        <td class="p-4">
                            <span class="px-3 py-1 bg-emerald-100 text-emerald-600 rounded-full text-xs font-bold">Hoạt động</span>
                        </td>
                        <td class="p-4 text-right">
                            <!-- Nút Sửa -->
                            <button type="button" onclick="openEditModal('<?= $promo['_id'] ?>', '<?= htmlspecialchars($promo['code']) ?>', <?= $promo['discount_percent'] ?>, '<?= htmlspecialchars($promo['description']) ?>')" class="text-amber-500 hover:text-amber-700 bg-amber-50 p-2 rounded-lg" title="Chỉnh sửa"><i class="fa-solid fa-pen"></i></button>
                            <!-- Nút Xóa -->
                            <form action="../actions/process_promotion.php" method="POST" class="inline">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= $promo['_id'] ?>">
                                <button type="submit" onclick="return confirm('Xóa mã này?')" class="text-red-500 hover:text-red-700 bg-red-50 p-2 rounded-lg"><i class="fa-solid fa-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal Sửa Khuyến Mãi -->
    <div id="editPromoModal" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-[100] hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl w-full max-w-lg shadow-2xl overflow-hidden">
            <div class="p-6 border-b border-slate-100 flex justify-between items-center">
                <h3 class="font-bold text-lg text-slate-800">Chỉnh sửa Khuyến Mãi</h3>
                <button onclick="closeEditModal()" class="text-slate-400 hover:text-slate-600"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <form action="../actions/process_promotion.php" method="POST" class="p-6 space-y-4">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" id="edit_promo_id">
                <div>
                    <label class="block text-xs font-bold text-slate-500 mb-2">Mã giảm giá</label>
                    <input type="text" name="code" id="edit_promo_code" required class="w-full px-4 py-2 border rounded-xl focus:ring-2 focus:ring-indigo-500 uppercase">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 mb-2">% Giảm giá</label>
                    <input type="number" name="discount" id="edit_promo_discount" min="1" max="100" required class="w-full px-4 py-2 border rounded-xl focus:ring-2 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 mb-2">Mô tả</label>
                    <textarea name="description" id="edit_promo_description" rows="3" required class="w-full px-4 py-2 border rounded-xl focus:ring-2 focus:ring-indigo-500"></textarea>
                </div>
                <div class="flex justify-end gap-3 mt-6">
                    <button type="button" onclick="closeEditModal()" class="px-5 py-2 text-slate-500 font-bold bg-slate-100 rounded-xl hover:bg-slate-200 transition">Hủy</button>
                    <button type="submit" class="px-5 py-2 bg-indigo-600 text-white font-bold rounded-xl hover:bg-indigo-700 transition">Lưu thay đổi</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openEditModal(id, code, discount, desc) { document.getElementById('edit_promo_id').value = id; document.getElementById('edit_promo_code').value = code; document.getElementById('edit_promo_discount').value = discount; document.getElementById('edit_promo_description').value = desc; document.getElementById('editPromoModal').classList.remove('hidden'); }
        function closeEditModal() { document.getElementById('editPromoModal').classList.add('hidden'); }
    </script>
</body>
</html>
