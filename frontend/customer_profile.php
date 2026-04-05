<!DOCTYPE html>
<!-- Giao diện Hồ sơ cá nhân -->
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tài khoản của tôi - Grand Horizon</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-slate-50">

    <?php include 'navbar_customer.php'; ?>

    <main class="max-w-7xl mx-auto px-4 py-24">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            <div class="space-y-6">
                <div class="bg-indigo-600 rounded-[2.5rem] p-8 text-white shadow-xl shadow-indigo-200 relative overflow-hidden">
                    <div class="relative z-10">
                        <p class="text-indigo-100 text-xs font-bold uppercase tracking-widest mb-2">Số dư hiện tại</p>
                        <h2 class="text-4xl font-black mb-6">5.450.000đ</h2>
                        <button class="bg-white/20 hover:bg-white/30 backdrop-blur-md text-white w-full py-3 rounded-2xl font-bold transition">
                            <i class="fa-solid fa-plus-circle mr-2"></i> Nạp thêm tiền
                        </button>
                    </div>
                    <div class="absolute -right-10 -bottom-10 w-40 h-40 bg-white/10 rounded-full"></div>
                </div>

                <div class="bg-white rounded-[2.5rem] p-8 border border-slate-100 shadow-sm">
                    <h3 class="font-bold text-slate-800 mb-6 flex justify-between items-center">
                        Thẻ liên kết 
                        <button class="text-indigo-600 text-xs"><i class="fa-solid fa-plus"></i> Thêm</button>
                    </h3>
                    <div class="space-y-4">
                        <div class="flex items-center gap-4 p-4 bg-slate-50 rounded-2xl border border-dashed border-slate-200">
                            <div class="w-12 h-8 bg-slate-800 rounded flex items-center justify-center text-[10px] text-white font-bold italic">VISA</div>
                            <div class="flex-1">
                                <p class="text-xs font-bold text-slate-700">**** **** **** 8802</p>
                                <p class="text-[10px] text-slate-400 uppercase">Hết hạn: 12/28</p>
                            </div>
                            <i class="fa-solid fa-ellipsis-vertical text-slate-300"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="lg:col-span-2">
                <div class="bg-white rounded-[2.5rem] p-8 md:p-10 border border-slate-100 shadow-sm">
                    <div class="flex justify-between items-center mb-8">
                        <h2 class="text-2xl font-black text-slate-800 tracking-tight">Thông tin cá nhân</h2>
                        <span class="px-3 py-1 bg-emerald-100 text-emerald-600 rounded-full text-[10px] font-bold uppercase">Tài khoản xác thực</span>
                    </div>

                    <form action="process_update_profile.php" method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-xs font-bold text-slate-400 uppercase mb-2 ml-1">Họ và tên</label>
                            <input type="text" name="full_name" value="Nguyễn Văn A" 
                                   class="w-full px-5 py-3.5 bg-slate-50 border border-slate-100 rounded-2xl focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:bg-white transition-all text-sm font-semibold text-slate-700">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-400 uppercase mb-2 ml-1">Email</label>
                            <input type="email" name="email" value="user@example.com" readonly
                                   class="w-full px-5 py-3.5 bg-slate-100 border border-slate-100 rounded-2xl text-slate-400 text-sm font-medium cursor-not-allowed">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-400 uppercase mb-2 ml-1">Số điện thoại</label>
                            <input type="text" name="phone" value="0901234567" 
                                   class="w-full px-5 py-3.5 bg-slate-50 border border-slate-100 rounded-2xl focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:bg-white transition-all text-sm font-semibold text-slate-700">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-400 uppercase mb-2 ml-1">Ngày sinh</label>
                            <input type="date" name="dob" value="1995-01-01" 
                                   class="w-full px-5 py-3.5 bg-slate-50 border border-slate-100 rounded-2xl focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:bg-white transition-all text-sm font-semibold text-slate-700">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-400 uppercase mb-2 ml-1">Số CCCD/Passport</label>
                            <input type="text" name="id_number" value="123456789" 
                                   class="w-full px-5 py-3.5 bg-slate-50 border border-slate-100 rounded-2xl focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:bg-white transition-all text-sm font-semibold text-slate-700">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-400 uppercase mb-2 ml-1">Quốc tịch</label>
                            <input type="text" name="nation" value="Việt Nam" 
                                   class="w-full px-5 py-3.5 bg-slate-50 border border-slate-100 rounded-2xl focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:bg-white transition-all text-sm font-semibold text-slate-700">
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-xs font-bold text-slate-400 uppercase mb-2 ml-1">Địa chỉ thường trú</label>
                            <textarea name="address" rows="2" 
                                      class="w-full px-5 py-3.5 bg-slate-50 border border-slate-100 rounded-2xl focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:bg-white transition-all text-sm font-semibold text-slate-700">123 Đường ABC, Quận 1, TP.HCM</textarea>
                        </div>

                        <div class="md:col-span-2 pt-4">
                            <button type="submit" 
                                    class="bg-indigo-600 text-white px-8 py-4 rounded-2xl font-bold hover:bg-indigo-700 transition shadow-lg shadow-indigo-100">
                                Lưu thay đổi thông tin
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </main>

</body>
</html>