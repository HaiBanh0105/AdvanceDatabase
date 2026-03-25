<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý Phòng - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-slate-50 flex">

    <?php include 'sidebar_admin.php'; ?>
    
    <main class="flex-1 p-4 md:p-8">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-4">
            <div>
                <h1 class="text-2xl font-bold text-slate-800">Quản lý danh sách phòng</h1>
                <p class="text-slate-500 text-sm">Cập nhật trạng thái và thông tin chi tiết từng phòng trong hệ thống.</p>
            </div>
            <button onclick="toggleModal('addRoomModal')" class="bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-2.5 rounded-xl font-bold shadow-lg shadow-indigo-200 transition flex items-center gap-2">
                <i class="fa-solid fa-plus"></i> Thêm phòng mới
            </button>
        </div>

        <div class="bg-white p-4 rounded-2xl shadow-sm border border-slate-200 mb-6 flex flex-wrap gap-4 items-center">
            <div class="flex-1 min-w-[200px] relative">
                <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                <input type="text" placeholder="Tìm số phòng..." class="w-full pl-10 pr-4 py-2 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>
            <select class="px-4 py-2 border border-slate-200 rounded-lg focus:outline-none text-slate-600">
                <option value="">Tất cả loại phòng</option>
                <option value="1">Deluxe Ocean View</option>
                <option value="2">Standard City View</option>
            </select>
            <select class="px-4 py-2 border border-slate-200 rounded-lg focus:outline-none text-slate-600">
                <option value="">Tất cả trạng thái</option>
                <option value="active">Đang hoạt động (Active)</option>
                <option value="maintenance">Bảo trì (Maintenance)</option>
            </select>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <table class="w-full text-left">
                <thead class="bg-slate-50 border-b border-slate-100">
                    <tr class="text-slate-500 text-[10px] uppercase tracking-widest font-bold">
                        <th class="px-6 py-4">Số phòng</th>
                        <th class="px-6 py-4">Loại phòng</th>
                        <th class="px-6 py-4">Trạng thái</th>
                        <th class="px-6 py-4">Ghi chú</th>
                        <th class="px-6 py-4 text-right">Thao tác</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-sm">
                    <tr class="hover:bg-slate-50/50 transition">
                        <td class="px-6 py-4 font-bold text-slate-700">P.101</td>
                        <td class="px-6 py-4 text-slate-600">Deluxe Ocean View</td>
                        <td class="px-6 py-4">
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-600">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-600 animate-pulse"></span> ACTIVE
                            </span>
                        </td>
                        <td class="px-6 py-4 text-slate-400 italic max-w-xs truncate">Phòng view đẹp...</td>
                        <td class="px-6 py-4 text-right space-x-2">
                            <button class="text-slate-400 hover:text-indigo-600 transition"><i class="fa-solid fa-pen-to-square"></i></button>
                            <button class="text-slate-400 hover:text-red-600 transition"><i class="fa-solid fa-trash"></i></button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </main>

    <!-- <?php include 'includes/modal_add_room.php'; ?> -->

    <script>
        function toggleModal(modalId) {
            const modal = document.getElementById(modalId);
            modal.classList.toggle('hidden');
        }
    </script>
</body>
</html>