<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header("Location: login.php");
    exit();
}
require_once '../dao/DAO.php';

$search_cccd = trim($_GET['search_cccd'] ?? '');

// Lấy danh sách khách hàng
if ($search_cccd !== '') {
    $sql = "SELECT u.user_id, u.email, u.phone, ud.full_name, ud.ID_number, ud.status, ud.balance, ud.address
            FROM `User` u 
            JOIN User_detail ud ON u.user_id = ud.user_id 
            WHERE u.role = 'Customer' AND ud.ID_number LIKE ?
            ORDER BY u.user_id DESC";
    $customers = db_query($sql, '%' . $search_cccd . '%');
} else {
    $sql = "SELECT u.user_id, u.email, u.phone, ud.full_name, ud.ID_number, ud.status, ud.balance, ud.address
            FROM `User` u 
            JOIN User_detail ud ON u.user_id = ud.user_id 
            WHERE u.role = 'Customer'
            ORDER BY u.user_id DESC";
    $customers = db_query($sql);
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Quản lý Khách hàng - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-slate-50 flex">

    <?php include 'sidebar_admin.php'; ?>

    <main class="flex-1 p-4 md:p-8">
        <div class="mb-8 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-slate-800">Quản lý Khách hàng</h1>
                <p class="text-slate-500 text-sm">Phê duyệt và chỉnh sửa thông tin khách hàng.</p>
            </div>
            
            <form action="" method="GET" class="flex items-center">
                <div class="relative">
                    <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                    <input type="text" name="search_cccd" value="<?php echo htmlspecialchars($search_cccd); ?>" placeholder="Tìm theo CCCD..." class="pl-9 pr-4 py-2 border border-slate-200 rounded-l-xl focus:ring-2 focus:ring-indigo-500 outline-none text-sm w-48 focus:w-64 transition-all">
                </div>
                <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-r-xl font-bold hover:bg-indigo-700 transition text-sm border border-indigo-600 border-l-0">Tìm</button>
            </form>
        </div>

        <?php if (isset($_GET['msg']) && $_GET['msg'] == 'approved'): ?>
            <div class="mb-6 p-3 bg-emerald-100 text-emerald-600 rounded-lg text-sm font-bold shadow-sm"><i class="fa-solid fa-check mr-2"></i>Đã duyệt hồ sơ thành công!</div>
        <?php elseif (isset($_GET['msg']) && $_GET['msg'] == 'deposited'): ?>
            <div class="mb-6 p-3 bg-indigo-100 text-indigo-600 rounded-lg text-sm font-bold shadow-sm"><i class="fa-solid fa-money-bill mr-2"></i>Đã nạp tiền thành công!</div>
        <?php endif; ?>

        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <table class="w-full text-left">
                <thead class="bg-slate-50 border-b border-slate-100">
                    <tr class="text-slate-500 text-[10px] uppercase tracking-widest font-bold">
                        <th class="px-6 py-4">Khách hàng</th>
                        <th class="px-6 py-4">Liên hệ</th>
                        <th class="px-6 py-4">Địa chỉ / CCCD</th>
                        <th class="px-6 py-4">Số dư ví</th>
                        <th class="px-6 py-4">Trạng thái</th>
                        <th class="px-6 py-4 text-right">Thao tác</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-sm">
                    <?php foreach ($customers as $c): ?>
                    <tr class="hover:bg-slate-50/50 transition">
                        <td class="px-6 py-4">
                            <p class="font-bold text-slate-700"><?php echo htmlspecialchars($c['full_name'] ?: 'Chưa cập nhật'); ?></p>
                        </td>
                        <td class="px-6 py-4">
                            <p class="text-slate-600"><?php echo htmlspecialchars($c['email']); ?></p>
                            <p class="text-[10px] text-slate-400"><?php echo htmlspecialchars($c['phone']); ?></p>
                        </td>
                        <td class="px-6 py-4">
                            <p class="text-slate-600 text-xs truncate max-w-[150px]" title="<?php echo htmlspecialchars($c['address'] ?: 'Chưa cập nhật địa chỉ'); ?>"><?php echo htmlspecialchars($c['address'] ?: 'Chưa cập nhật địa chỉ'); ?></p>
                            <p class="font-mono text-[10px] tracking-wider text-slate-400 mt-1">CCCD: <?php echo htmlspecialchars($c['ID_number'] ?: 'Trống'); ?></p>
                        </td>
                        <td class="px-6 py-4 font-bold text-indigo-600">
                            <?php echo number_format($c['balance'], 0, ',', '.'); ?>đ
                        </td>
                        <td class="px-6 py-4">
                            <?php if ($c['status'] === 'active'): ?>
                                <span class="bg-emerald-100 text-emerald-600 px-2 py-1 rounded text-[10px] font-bold uppercase">Đã duyệt</span>
                            <?php else: ?>
                                <span class="bg-amber-100 text-amber-600 px-2 py-1 rounded text-[10px] font-bold uppercase">Chờ duyệt</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 text-right space-x-2">
                            <?php if ($c['status'] !== 'active' && !empty($c['ID_number'])): ?>
                                <form action="../actions/process_approve_customer.php" method="POST" class="inline">
                                    <input type="hidden" name="user_id" value="<?php echo $c['user_id']; ?>">
                                    <button type="submit" onclick="return confirm('Xác nhận duyệt tài khoản này?');" class="bg-emerald-500 text-white px-3 py-1.5 rounded-lg text-[10px] font-bold hover:bg-emerald-600 transition mb-1">Duyệt</button>
                                </form>
                            <?php endif; ?>
                            <a href="admin_bookings.php?user_id=<?php echo $c['user_id']; ?>" class="bg-slate-100 text-slate-600 px-3 py-1.5 rounded-lg text-[10px] font-bold hover:bg-slate-200 transition inline-block mb-1"><i class="fa-solid fa-clock-rotate-left mr-1"></i> Xem lịch sử đặt phòng</a>
                            <button onclick="openDepositModal(<?php echo $c['user_id']; ?>, '<?php echo htmlspecialchars($c['full_name']); ?>')" class="bg-indigo-100 text-indigo-600 px-3 py-1.5 rounded-lg text-[10px] font-bold hover:bg-indigo-200 transition mb-1">Nạp tiền</button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>

    <!-- Modal Nạp Tiền -->
    <div id="depositModal" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-[2.5rem] w-full max-w-sm shadow-2xl overflow-hidden">
            <div class="p-6 border-b border-slate-100 flex justify-between items-center">
                <h3 class="font-bold text-lg text-slate-800">Nạp tiền vào ví</h3>
                <button onclick="closeDepositModal()" class="text-slate-400 hover:text-slate-600"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <form action="../actions/process_deposit_admin.php" method="POST" class="p-6 space-y-4">
                <input type="hidden" name="user_id" id="deposit_user_id">
                <div>
                    <p class="text-xs font-bold text-slate-400 uppercase mb-1">Khách hàng</p>
                    <p id="deposit_user_name" class="font-bold text-slate-700 mb-4"></p>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-400 uppercase mb-2">Số tiền nạp (VNĐ)</label>
                    <input type="number" name="amount" required min="10000" step="10000" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none font-bold text-lg text-indigo-600">
                </div>
                <button type="submit" class="w-full mt-2 bg-indigo-600 text-white py-3 rounded-xl font-bold hover:bg-indigo-700 transition">Xác nhận nạp</button>
            </form>
        </div>
    </div>

    <script>
        function openDepositModal(userId, userName) {
            document.getElementById('deposit_user_id').value = userId;
            document.getElementById('deposit_user_name').innerText = userName;
            document.getElementById('depositModal').classList.remove('hidden');
        }
        function closeDepositModal() {
            document.getElementById('depositModal').classList.add('hidden');
        }
    </script>
</body>
</html>