<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header("Location: login.php");
    exit();
}
require_once '../config/mongodb.php';

$db = mongo_get_db();
$search_code = trim($_GET['search_code'] ?? '');

// Lọc theo mã giảm giá
$filter = [];
if ($search_code !== '') {
    $filter['code'] = new MongoDB\BSON\Regex($search_code, 'i');
}

$promotions = $db->promotions->find($filter, ['sort' => ['created_at' => -1]])->toArray();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý Khuyến mãi - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-slate-50 flex h-screen overflow-hidden">

    <!-- Sidebar -->
    <?php include 'sidebar_admin.php'; ?>

    <main class="flex-1 overflow-y-auto p-4 md:p-8">
        <div class="mb-8 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-slate-800">Quản lý Khuyến mãi</h1>
                <p class="text-slate-500 text-sm">Xem, tìm kiếm và quản lý các chương trình giảm giá.</p>
            </div>
            
            <button onclick="openAddModal()" class="bg-indigo-600 text-white px-6 py-2.5 rounded-xl font-bold hover:bg-indigo-700 transition shadow-lg shadow-indigo-100 flex items-center gap-2">
                <i class="fa-solid fa-plus"></i> Thêm mã mới
            </button>
        </div>

        <!-- Thanh tìm kiếm duy nhất -->
        <div class="mb-6">
            <form action="" method="GET" class="flex items-center">
                <div class="relative">
                    <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                    <input type="text" name="search_code" value="<?= htmlspecialchars($search_code) ?>" placeholder="Tìm theo Mã giảm giá..." class="pl-9 pr-4 py-2 border border-slate-200 rounded-l-xl focus:ring-2 focus:ring-indigo-500 outline-none text-sm w-48 focus:w-64 transition-all">
                </div>
                <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-r-xl font-bold hover:bg-indigo-700 transition text-sm border border-indigo-600 border-l-0">Tìm</button>
            </form>
        </div>

        <!-- Danh sách Khuyến mãi -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
            <table class="w-full text-left">
                <thead class="bg-slate-50 border-b border-slate-100">
                    <tr class="text-slate-500 text-[10px] uppercase tracking-widest font-bold">
                        <th class="px-6 py-4">Mã Khuyến Mãi</th>
                        <th class="px-6 py-4">Mô tả</th>
                        <th class="px-6 py-4">Số lượng</th>
                        <th class="px-6 py-4">Ngày tạo</th>
                        <th class="px-6 py-4">Hết hạn</th>
                        <th class="px-6 py-4 text-right">Thao tác</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-sm">
                    <?php if (empty($promotions)): ?>
                        <tr><td colspan="6" class="px-6 py-8 text-center text-slate-500 italic">Không có mã giảm giá nào.</td></tr>
                    <?php else: ?>
                        <?php foreach ($promotions as $promo): 
                            $created_at = isset($promo['created_at']) ? $promo['created_at']->toDateTime()->setTimezone(new DateTimeZone('Asia/Ho_Chi_Minh'))->format('d/m/Y') : 'N/A';
                            $expires_at = isset($promo['expires_at']) ? $promo['expires_at']->toDateTime()->setTimezone(new DateTimeZone('Asia/Ho_Chi_Minh'))->format('d/m/Y H:i') : 'N/A';
                            $expires_iso = isset($promo['expires_at']) ? $promo['expires_at']->toDateTime()->setTimezone(new DateTimeZone('Asia/Ho_Chi_Minh'))->format('Y-m-d\TH:i') : '';
                        ?>
                    <tr class="hover:bg-slate-50/50 transition">
                        <td class="px-6 py-4">
                            <p class="font-bold text-indigo-600 text-lg uppercase"><?= htmlspecialchars($promo['code']) ?></p>
                            <span class="inline-block bg-red-100 text-red-600 px-2 py-0.5 rounded text-[10px] font-bold mt-1">-<?= $promo['discount_percent'] ?>%</span>
                        </td>
                        <td class="px-6 py-4 text-slate-600 max-w-xs truncate" title="<?= htmlspecialchars($promo['description']) ?>">
                            <?= htmlspecialchars($promo['description']) ?>
                        </td>
                        <td class="px-6 py-4">
                            <span class="font-bold text-slate-700"><?= $promo['quantity'] ?? 0 ?></span>
                        </td>
                        <td class="px-6 py-4 text-slate-500 text-xs">
                            <?= $created_at ?>
                        </td>
                        <td class="px-6 py-4 text-slate-500 text-xs">
                            <?= $expires_at ?>
                        </td>
                        <td class="px-6 py-4 text-right space-x-2">
                            <!-- Nút Sửa -->
                            <button type="button" onclick="openEditModal('<?= $promo['_id'] ?>', '<?= htmlspecialchars($promo['code']) ?>', <?= $promo['discount_percent'] ?>, '<?= htmlspecialchars($promo['description']) ?>', <?= $promo['quantity'] ?? 0 ?>, '<?= $expires_iso ?>')" class="bg-amber-100 text-amber-600 px-3 py-1.5 rounded-lg text-[10px] font-bold hover:bg-amber-200 transition" title="Chỉnh sửa"><i class="fa-solid fa-pen"></i></button>
                            <!-- Nút Xóa -->
                            <form action="../actions/process_promotion.php" method="POST" class="inline">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= $promo['_id'] ?>">
                                <button type="submit" onclick="return confirm('Bạn có chắc chắn muốn xóa mã này không?')" class="bg-red-100 text-red-600 px-3 py-1.5 rounded-lg text-[10px] font-bold hover:bg-red-200 transition"><i class="fa-solid fa-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>

    <!-- Modal Thêm mới Khuyến Mãi -->
    <div id="addPromoModal" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-[100] hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-[2.5rem] w-full max-w-md shadow-2xl overflow-hidden animate-in zoom-in duration-300">
            <div class="p-6 border-b border-slate-100 flex justify-between items-center bg-slate-50/50">
                <h3 class="font-bold text-lg text-slate-800 uppercase tracking-tight">Thêm mã giảm giá</h3>
                <button onclick="closeAddModal()" class="text-slate-400 hover:text-slate-600"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <form action="../actions/process_promotion.php" method="POST" class="p-8 space-y-4">
                <input type="hidden" name="action" value="add">
                <div class="grid grid-cols-2 gap-4">
                    <div class="col-span-2">
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Mã Code (Tùy chọn ghi hoa)</label>
                        <input type="text" name="code" required class="w-full px-5 py-3 bg-slate-50 border border-slate-200 rounded-2xl focus:ring-2 focus:ring-indigo-500 outline-none uppercase font-bold text-indigo-600 tracking-wider">
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">% Giảm giá</label>
                        <input type="number" name="discount" min="1" max="100" required class="w-full px-5 py-3 bg-slate-50 border border-slate-200 rounded-2xl focus:ring-2 focus:ring-indigo-500 outline-none font-bold text-slate-700">
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Số lượng mã</label>
                        <input type="number" name="quantity" min="1" required class="w-full px-5 py-3 bg-slate-50 border border-slate-200 rounded-2xl focus:ring-2 focus:ring-indigo-500 outline-none font-bold text-slate-700">
                    </div>
                    <div class="col-span-2">
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Ngày hết hạn</label>
                        <input type="datetime-local" name="expires_at" required class="w-full px-5 py-3 bg-slate-50 border border-slate-200 rounded-2xl focus:ring-2 focus:ring-indigo-500 outline-none text-slate-700">
                    </div>
                    <div class="col-span-2">
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Mô tả chi tiết</label>
                        <textarea name="description" rows="2" required class="w-full px-5 py-3 bg-slate-50 border border-slate-200 rounded-2xl focus:ring-2 focus:ring-indigo-500 outline-none text-slate-700 text-sm"></textarea>
                    </div>
                </div>
                <button type="submit" class="w-full mt-4 px-4 py-4 bg-indigo-600 text-white rounded-2xl font-bold shadow-lg shadow-indigo-100 hover:bg-indigo-700 transition">Thêm mới Khuyến mãi</button>
            </form>
        </div>
    </div>

    <!-- Modal Sửa Khuyến Mãi -->
    <div id="editPromoModal" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-[100] hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-[2.5rem] w-full max-w-md shadow-2xl overflow-hidden animate-in zoom-in duration-300">
            <div class="p-6 border-b border-slate-100 flex justify-between items-center bg-slate-50/50">
                <h3 class="font-bold text-lg text-slate-800 uppercase tracking-tight">Chỉnh sửa Khuyến Mãi</h3>
                <button onclick="closeEditModal()" class="text-slate-400 hover:text-slate-600"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <form action="../actions/process_promotion.php" method="POST" class="p-8 space-y-4">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" id="edit_promo_id">
                <div class="grid grid-cols-2 gap-4">
                    <div class="col-span-2">
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Mã giảm giá</label>
                        <input type="text" name="code" id="edit_promo_code" required class="w-full px-5 py-3 bg-slate-50 border border-slate-200 rounded-2xl focus:ring-2 focus:ring-indigo-500 outline-none uppercase font-bold text-indigo-600">
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">% Giảm</label>
                        <input type="number" name="discount" id="edit_promo_discount" min="1" max="100" required class="w-full px-5 py-3 bg-slate-50 border border-slate-200 rounded-2xl focus:ring-2 focus:ring-indigo-500 outline-none">
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Số lượng</label>
                        <input type="number" name="quantity" id="edit_promo_quantity" min="0" required class="w-full px-5 py-3 bg-slate-50 border border-slate-200 rounded-2xl focus:ring-2 focus:ring-indigo-500 outline-none">
                    </div>
                    <div class="col-span-2">
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Ngày hết hạn</label>
                        <input type="datetime-local" name="expires_at" id="edit_promo_expires" required class="w-full px-5 py-3 bg-slate-50 border border-slate-200 rounded-2xl focus:ring-2 focus:ring-indigo-500 outline-none">
                    </div>
                    <div class="col-span-2">
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Mô tả</label>
                        <textarea name="description" id="edit_promo_description" rows="2" required class="w-full px-5 py-3 bg-slate-50 border border-slate-200 rounded-2xl focus:ring-2 focus:ring-indigo-500 outline-none"></textarea>
                    </div>
                </div>
                <div class="flex justify-end gap-3 mt-6">
                    <button type="button" onclick="closeEditModal()" class="flex-1 px-4 py-4 text-slate-500 font-bold hover:bg-slate-50 rounded-2xl transition">Hủy</button>
                    <button type="submit" class="flex-1 px-4 py-4 bg-indigo-600 text-white rounded-2xl font-bold shadow-lg shadow-indigo-100 hover:bg-indigo-700 transition">Lưu thay đổi</button>
                </div>
            </form>
        </div>
    </div>

    <script src="../assets/js/toast.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const urlParams = new URLSearchParams(window.location.search);
            let hasParams = false;
            
            if (urlParams.get('error') === 'invalid_date') {
                showToast('Lỗi: Ngày hết hạn không được nhỏ hơn thời gian hiện tại!', 'error');
                hasParams = true;
            } else if (urlParams.get('msg') === 'success') {
                showToast('Thao tác thành công!', 'success');
                hasParams = true;
            }
            
            if (hasParams) {
                window.history.replaceState({}, document.title, window.location.pathname);
            }
        });

        function openAddModal() { document.getElementById('addPromoModal').classList.remove('hidden'); }
        function closeAddModal() { document.getElementById('addPromoModal').classList.add('hidden'); }
        
        function openEditModal(id, code, discount, desc, quantity, expires_iso) { 
            document.getElementById('edit_promo_id').value = id; 
            document.getElementById('edit_promo_code').value = code; 
            document.getElementById('edit_promo_discount').value = discount; 
            document.getElementById('edit_promo_description').value = desc; 
            document.getElementById('edit_promo_quantity').value = quantity;
            document.getElementById('edit_promo_expires').value = expires_iso;
            document.getElementById('editPromoModal').classList.remove('hidden'); 
        }
        function closeEditModal() { document.getElementById('editPromoModal').classList.add('hidden'); }
    </script>
</body>
</html>
