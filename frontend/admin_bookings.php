<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý Đặt phòng - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-slate-50 flex">

    <?php include 'sidebar_admin.php'; ?>

    <main class="flex-1 p-4 md:p-8">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-4">
            <div>
                <h1 class="text-2xl font-bold text-slate-800">Danh sách đơn đặt phòng</h1>
                <p class="text-slate-500 text-sm">Theo dõi, xác nhận và quản lý các yêu cầu đặt phòng từ khách hàng.</p>
            </div>
            <div class="flex gap-3">
                <button class="bg-white border border-slate-200 px-4 py-2 rounded-xl text-sm font-bold text-slate-600 hover:bg-slate-50 transition">
                    <i class="fa-solid fa-filter mr-2"></i> Bộ lọc nâng cao
                </button>
            </div>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
            <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm">
                <p class="text-slate-400 text-[10px] font-bold uppercase tracking-wider">Chờ xác nhận</p>
                <p class="text-xl font-black text-amber-500">12</p>
            </div>
            <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm">
                <p class="text-slate-400 text-[10px] font-bold uppercase tracking-wider">Sắp check-in</p>
                <p class="text-xl font-black text-indigo-600">08</p>
            </div>
            <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm">
                <p class="text-slate-400 text-[10px] font-bold uppercase tracking-wider">Đang lưu trú</p>
                <p class="text-xl font-black text-emerald-600">25</p>
            </div>
            <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm">
                <p class="text-slate-400 text-[10px] font-bold uppercase tracking-wider">Hủy gần đây</p>
                <p class="text-xl font-black text-red-500">03</p>
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead class="bg-slate-50 border-b border-slate-100">
                        <tr class="text-slate-500 text-[10px] uppercase tracking-widest font-bold">
                            <th class="px-6 py-4">ID / Ngày đặt</th>
                            <th class="px-6 py-4">Khách hàng</th>
                            <th class="px-6 py-4">Check-in / Out</th>
                            <th class="px-6 py-4">Tổng tiền</th>
                            <th class="px-6 py-4">Thanh toán</th>
                            <th class="px-6 py-4 text-center">Trạng thái</th>
                            <th class="px-6 py-4 text-right">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-sm">
                        <tr class="hover:bg-slate-50/50 transition">
                            <td class="px-6 py-4">
                                <span class="font-bold text-slate-700 block">#BK-8802</span>
                                <span class="text-[10px] text-slate-400">2026-03-20 14:30</span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-600 font-bold text-xs">NL</div>
                                    <div>
                                        <p class="font-semibold text-slate-700">Nguyễn Văn Lợi</p>
                                        <p class="text-[10px] text-slate-400">090xxxx123</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-slate-600">
                                    <p><span class="text-[10px] font-bold text-slate-400 mr-1">VÀO:</span> 2026-03-25</p>
                                    <p><span class="text-[10px] font-bold text-slate-400 mr-1">RA:</span> 2026-03-27</p>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="font-bold text-slate-900 uppercase">5.000.000đ</span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-[10px] font-bold px-2 py-0.5 rounded bg-emerald-100 text-emerald-600 uppercase">Đã thanh toán</span>
                                <p class="text-[10px] text-slate-400 mt-1 italic">Ví hệ thống</p>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <span class="inline-block px-3 py-1 rounded-full text-[10px] font-bold bg-blue-100 text-blue-600 uppercase tracking-tighter">Đã xác nhận</span>
                            </td>
                            <td class="px-6 py-4 text-right space-x-2">
                                <button title="Xem chi tiết" class="text-slate-400 hover:text-indigo-600 transition"><i class="fa-solid fa-eye"></i></button>
                                <button title="Xử lý" class="text-slate-400 hover:text-emerald-600 transition"><i class="fa-solid fa-check-to-slot"></i></button>
                            </td>
                        </tr>
                        </tbody>
                </table>
            </div>
            
            <div class="p-4 border-t border-slate-100 flex justify-between items-center bg-slate-50/30">
                <p class="text-xs text-slate-400 font-medium">Hiển thị 1 - 10 trên tổng số 128 đơn</p>
                <div class="flex gap-1">
                    <button class="w-8 h-8 flex items-center justify-center rounded bg-white border border-slate-200 text-slate-400 hover:bg-indigo-600 hover:text-white transition"><i class="fa-solid fa-chevron-left text-[10px]"></i></button>
                    <button class="w-8 h-8 flex items-center justify-center rounded bg-indigo-600 text-white font-bold text-xs">1</button>
                    <button class="w-8 h-8 flex items-center justify-center rounded bg-white border border-slate-200 text-slate-600 font-bold text-xs hover:bg-slate-50">2</button>
                    <button class="w-8 h-8 flex items-center justify-center rounded bg-white border border-slate-200 text-slate-400 hover:bg-indigo-600 hover:text-white transition"><i class="fa-solid fa-chevron-right text-[10px]"></i></button>
                </div>
            </div>
        </div>
    </main>

</body>
</html>