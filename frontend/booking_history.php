<!DOCTYPE html>
<!-- Giao diện Lịch sử đặt phòng -->
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lịch sử đặt phòng - Grand Horizon</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-slate-50">

    <?php include 'navbar_customer.php'; ?>

    <main class="max-w-7xl mx-auto px-4 py-24">
        <div class="mb-10">
            <h1 class="text-3xl font-black text-slate-800 tracking-tight">Lịch sử đặt phòng</h1>
            <p class="text-slate-500 mt-2 text-sm">Xem và quản lý tất cả các đơn đặt phòng của bạn tại Grand Horizon.</p>
        </div>

        <div class="flex gap-4 mb-8 border-b border-slate-200 overflow-x-auto pb-1">
            <button class="px-4 py-2 text-sm font-bold text-indigo-600 border-b-2 border-indigo-600 whitespace-nowrap">Tất cả đơn</button>
            <button class="px-4 py-2 text-sm font-medium text-slate-500 hover:text-indigo-600 transition whitespace-nowrap">Sắp tới</button>
            <button class="px-4 py-2 text-sm font-medium text-slate-500 hover:text-indigo-600 transition whitespace-nowrap">Đã hoàn thành</button>
            <button class="px-4 py-2 text-sm font-medium text-slate-500 hover:text-indigo-600 transition whitespace-nowrap">Đã hủy</button>
        </div>

        <div class="space-y-6">
            
            <div class="bg-white rounded-[2rem] border border-slate-100 shadow-sm overflow-hidden hover:shadow-md transition">
                <div class="p-6 md:p-8 flex flex-col md:flex-row gap-6 items-center">
                    <div class="w-full md:w-48 h-32 rounded-2xl bg-slate-100 overflow-hidden shrink-0">
                        <img src="https://images.unsplash.com/photo-1611892440504-42a792e24d32?auto=format&fit=crop&w=400" class="w-full h-full object-cover">
                    </div>

                    <div class="flex-1 space-y-2 text-center md:text-left">
                        <div class="flex flex-wrap justify-center md:justify-start items-center gap-2 mb-1">
                            <span class="text-[10px] font-black uppercase tracking-widest text-indigo-600">Mã đơn: #BK-<?php echo "8802"; ?></span>
                            <span class="px-2 py-0.5 rounded-full text-[9px] font-bold bg-blue-100 text-blue-600 uppercase">Đã xác nhận</span>
                        </div>
                        <h3 class="text-xl font-bold text-slate-800 uppercase tracking-wide">Deluxe Ocean View</h3>
                        <div class="flex flex-wrap justify-center md:justify-start gap-x-6 gap-y-1 text-sm text-slate-500 font-medium">
                            <p><i class="fa-solid fa-calendar-day mr-2 text-indigo-400"></i>Nhận: <span class="text-slate-700">2026-03-25</span></p>
                            <p><i class="fa-solid fa-calendar-check mr-2 text-indigo-400"></i>Trả: <span class="text-slate-700">2026-03-27</span></p>
                        </div>
                    </div>

                    <div class="w-full md:w-auto text-center md:text-right border-t md:border-t-0 md:border-l border-slate-100 pt-6 md:pt-0 md:pl-10 space-y-3">
                        <div>
                            <p class="text-[10px] font-bold text-slate-400 uppercase leading-none mb-1">Tổng thanh toán</p>
                            <p class="text-2xl font-black text-slate-900 uppercase">5.000.000đ</p>
                            <p class="text-[10px] font-bold text-emerald-500 uppercase mt-1">Đã thanh toán qua Ví</p>
                        </div>
                        <div class="flex flex-col gap-2">
                            <button class="bg-indigo-600 text-white px-6 py-2.5 rounded-xl text-xs font-bold hover:bg-indigo-700 transition shadow-lg shadow-indigo-100">Xem chi tiết</button>
                            <button onclick="confirmCancel('8802')" class="text-slate-400 hover:text-red-600 text-[10px] font-bold uppercase transition">Hủy đặt phòng</button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-[2rem] border border-slate-100 shadow-sm overflow-hidden opacity-75 grayscale-[0.5]">
                <div class="p-6 md:p-8 flex flex-col md:flex-row gap-6 items-center">
                    <div class="w-full md:w-48 h-32 rounded-2xl bg-slate-100 overflow-hidden shrink-0">
                        <img src="https://images.unsplash.com/photo-1566665797739-1674de7a421a?auto=format&fit=crop&w=400" class="w-full h-full object-cover">
                    </div>
                    <div class="flex-1 space-y-2 text-center md:text-left">
                        <div class="flex flex-wrap justify-center md:justify-start items-center gap-2 mb-1">
                            <span class="text-[10px] font-black uppercase tracking-widest text-slate-400">Mã đơn: #BK-7750</span>
                            <span class="px-2 py-0.5 rounded-full text-[9px] font-bold bg-slate-100 text-slate-400 uppercase">Đã hủy</span>
                        </div>
                        <h3 class="text-xl font-bold text-slate-400 uppercase tracking-wide">Standard City View</h3>
                        <p class="text-xs text-red-400 italic">Lý do: Thay đổi lịch trình cá nhân.</p>
                    </div>
                    <div class="w-full md:w-auto text-center md:text-right border-t md:border-t-0 md:border-l border-slate-100 pt-6 md:pt-0 md:pl-10">
                        <p class="text-[10px] font-bold text-slate-300 uppercase leading-none mb-1">Tổng tiền</p>
                        <p class="text-2xl font-black text-slate-300 uppercase">1.200.000đ</p>
                        <button class="mt-4 bg-slate-100 text-slate-500 px-6 py-2.5 rounded-xl text-xs font-bold hover:bg-indigo-600 hover:text-white transition">Đặt lại phòng này</button>
                    </div>
                </div>
            </div>

        </div>
    </main>

    <script>
        function confirmCancel(id) {
            if (confirm("Bạn có chắc chắn muốn hủy đơn đặt phòng #" + id + " không? Số tiền sẽ được hoàn về ví dựa trên chính sách của chúng tôi.")) {
                // Logic xử lý hủy phòng tại đây
                alert("Yêu cầu hủy đơn #" + id + " đã được gửi đi.");
            }
        }
    </script>

</body>
</html>