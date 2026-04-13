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
    $sql = "SELECT c.customer_id, a.account_id, a.email, c.phone, c.full_name, c.cccd as ID_number, a.status as account_status, c.address, c.created_at
            FROM Customer c 
            LEFT JOIN Account a ON c.customer_id = a.customer_id 
            WHERE c.cccd LIKE ?
            ORDER BY c.customer_id DESC";
    $customers = db_query($sql, '%' . $search_cccd . '%');
} else {
    $sql = "SELECT c.customer_id, a.account_id, a.email, c.phone, c.full_name, c.cccd as ID_number, a.status as account_status, c.address, c.created_at
            FROM Customer c 
            LEFT JOIN Account a ON c.customer_id = a.customer_id 
            ORDER BY c.customer_id DESC";
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
            </div>

            <form action="" method="GET" class="flex items-center">
                <div class="relative">
                    <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                    <input type="text" name="search_cccd" value="<?php echo htmlspecialchars($search_cccd); ?>"
                        placeholder="Tìm theo CCCD..."
                        class="pl-9 pr-4 py-2 border border-slate-200 rounded-l-xl focus:ring-2 focus:ring-indigo-500 outline-none text-sm w-48 focus:w-64 transition-all">
                </div>
                <button type="submit"
                    class="bg-indigo-600 text-white px-4 py-2 rounded-r-xl font-bold hover:bg-indigo-700 transition text-sm border border-indigo-600 border-l-0">Tìm</button>
            </form>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <table class="w-full text-left">
                <thead class="bg-slate-50 border-b border-slate-100">
                    <tr class="text-slate-500 text-[10px] uppercase tracking-widest font-bold">
                        <th class="px-6 py-4">Khách hàng</th>
                        <th class="px-6 py-4">Liên hệ</th>
                        <th class="px-6 py-4">CCCD</th>
                        <th class="px-6 py-4">Số dư ví</th>
                        <th class="px-6 py-4">Ngày tạo</th>
                        <th class="px-6 py-4">Trạng thái</th>
                        <th class="px-6 py-4 text-right">Thao tác</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-sm">
                    <?php foreach ($customers as $c): ?>
                    <tr class="hover:bg-slate-50/50 transition">
                        <td class="px-6 py-4">
                            <p class="font-bold text-slate-700">
                                <?php echo htmlspecialchars($c['full_name']); ?></p>
                            <?php if (empty($c['account_id'])): ?>
                            <p class="text-[10px] text-slate-400 uppercase font-bold mt-1">Khách vãng lai</p>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4">
                            <?php if (!empty($c['account_id'])): ?>
                            <p class="text-slate-600">Email:
                                <?php echo htmlspecialchars($c['email']); ?></p>
                            <p class="text-slate-600">SDT:
                                <?php echo htmlspecialchars($c['phone']); ?></p>
                            <p class="text-slate-600">Địa chỉ:
                                <?php echo htmlspecialchars($c['address']); ?></p>
                            <?php else: ?>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4">
                            <p class="font-bold text-slate-700">
                                <?php echo htmlspecialchars($c['ID_number'] ?: 'Trống'); ?></p>
                        </td>
                        <td class="px-6 py-4 font-bold text-indigo-600">
                            <?php echo !empty($c['account_id']) ? '0 đ' : '<span class="text-slate-300"></span>'; ?>
                        </td>
                        <td class="px-6 py-4 text-slate-500">
                            <span class="text-xs font-medium">
                                <?php echo !empty($c['created_at']) ? date('d/m/Y H:i', strtotime($c['created_at'])) : 'Không xác định'; ?>
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <?php if (empty($c['account_id'])): ?>
                            <span class="text-slate-300"></span>
                            <?php elseif (empty($c['ID_number'])): ?>
                            <span
                                class="bg-red-100 text-red-600 px-2 py-1 rounded text-[10px] font-bold uppercase whitespace-nowrap">Chưa
                                cập nhật thông tin</span>
                            <?php elseif ($c['account_status'] === 'active'): ?>
                            <span
                                class="bg-emerald-100 text-emerald-600 px-2 py-1 rounded text-[10px] font-bold uppercase whitespace-nowrap">Đã
                                duyệt</span>
                            <?php else: ?>
                            <span
                                class="bg-amber-100 text-amber-600 px-2 py-1 rounded text-[10px] font-bold uppercase whitespace-nowrap">Chờ
                                duyệt</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 text-right space-x-2">
                            <?php if (!empty($c['account_id'])): ?>
                            <?php if ($c['account_status'] === 'active'): ?>
                            <a href="admin_bookings.php?search=<?php echo urlencode($c['ID_number'] ?: $c['full_name']); ?>"
                                class="bg-slate-100 text-slate-600 px-3 py-1.5 rounded-lg text-[10px] font-bold hover:bg-slate-200 transition inline-block mb-1">
                                <i class="fa-solid fa-clock-rotate-left mr-1"></i> Xem lịch sử đặt phòng
                            </a>
                            <button
                                onclick="openDepositModal(<?php echo $c['account_id']; ?>, '<?php echo htmlspecialchars($c['full_name']); ?>')"
                                class="bg-indigo-100 text-indigo-600 px-3 py-1.5 rounded-lg text-[10px] font-bold hover:bg-indigo-200 transition mb-1">
                                Nạp tiền
                            </button>
                            <?php elseif (!empty($c['ID_number'])): ?>
                            <form action="../actions/process_approve_customer.php" method="POST" class="inline">
                                <input type="hidden" name="user_id" value="<?php echo $c['account_id']; ?>">
                                <button type="submit" onclick="return confirm('Xác nhận duyệt tài khoản này?');"
                                    class="bg-emerald-500 text-white px-3 py-1.5 rounded-lg text-[10px] font-bold hover:bg-emerald-600 transition mb-1">
                                    Duyệt ngay
                                </button>
                            </form>
                            <?php else: ?>
                            <span class="text-slate-400 text-[10px] italic block">Chờ khách cập nhật hồ sơ</span>
                            <?php endif; ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>

    <!-- Modal Nạp Tiền -->
    <?php
    include 'Modals/deposit_modal.php';
    ?>

    <script src="../assets/js/toast.js"></script>
    <script>
    function openDepositModal(userId, userName) {
        document.getElementById('deposit_user_id').value = userId;
        document.getElementById('deposit_user_name').innerText = userName;
        document.getElementById('depositModal').classList.remove('hidden');
    }

    function closeDepositModal() {
        document.getElementById('depositModal').classList.add('hidden');
    }

    document.addEventListener('DOMContentLoaded', () => {
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('msg') === 'approved') {
            showToast('Đã duyệt hồ sơ thành công!', 'success');
        } else if (urlParams.get('msg') === 'deposited') {
            showToast('Đã nạp tiền thành công!', 'success');
        }
    });
    </script>
</body>

</html>