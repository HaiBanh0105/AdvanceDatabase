<!DOCTYPE html>
<!-- Giao diện Khôi phục Mật khẩu -->
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quên mật khẩu - Grand Horizon</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style> body { font-family: 'Plus Jakarta Sans', sans-serif; } </style>
</head>
<body class="bg-slate-50 h-screen flex items-center justify-center p-4">

    <div class="max-w-md w-full">
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center bg-indigo-600 p-3 rounded-2xl shadow-lg shadow-indigo-200 mb-4">
                <i class="fa-solid fa-key text-white text-2xl"></i>
            </div>
            <h1 class="text-3xl font-black text-slate-800 tracking-tight">Khôi phục mật khẩu</h1>
            <p class="text-slate-500 mt-2" id="descText">Nhập email để nhận mã xác nhận OTP.</p>
        </div>

        <div class="bg-white p-8 rounded-[2.5rem] shadow-xl shadow-slate-200/60 border border-slate-100">
            <div id="alertBox" class="hidden mb-6 p-4 rounded-2xl text-sm font-bold flex items-center gap-3 border shadow-sm transition-all duration-500">
                <i id="alertIcon" class="fa-solid"></i>
                <span id="alertMessage"></span>
            </div>

            <form id="step1Form" class="space-y-6">
                <input type="hidden" name="action" value="send_otp">
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-2 ml-1">Email của bạn</label>
                    <div class="relative">
                        <i class="fa-solid fa-envelope absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                        <input type="email" id="email" name="email" required
                               class="w-full pl-12 pr-4 py-4 bg-slate-50 border border-slate-200 rounded-2xl focus:ring-2 focus:ring-indigo-500 outline-none text-sm"
                               placeholder="name@example.com">
                    </div>
                </div>
                <button type="submit" id="btnStep1" class="w-full bg-indigo-600 text-white py-4 rounded-2xl font-bold hover:bg-indigo-700 shadow-lg shadow-indigo-100 flex justify-center items-center gap-2">
                    <span id="textStep1">Gửi mã OTP</span>
                    <i id="spinStep1" class="fa-solid fa-spinner fa-spin hidden"></i>
                </button>
            </form>

            <form id="step2Form" class="space-y-6 hidden">
                <input type="hidden" name="action" value="reset_pass">
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-2 ml-1">Mã OTP (6 số)</label>
                    <div class="relative">
                        <i class="fa-solid fa-shield-halved absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                        <input type="text" name="otp" required maxlength="6"
                               class="w-full pl-12 pr-4 py-4 bg-slate-50 border border-slate-200 rounded-2xl tracking-widest text-center font-bold text-lg focus:ring-2 focus:ring-indigo-500 outline-none"
                               placeholder="------">
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-2 ml-1">Mật khẩu mới</label>
                    <div class="relative">
                        <i class="fa-solid fa-lock absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                        <input type="password" name="new_password" required
                               class="w-full pl-12 pr-4 py-4 bg-slate-50 border border-slate-200 rounded-2xl focus:ring-2 focus:ring-indigo-500 outline-none text-sm"
                               placeholder="••••••••">
                    </div>
                </div>
                <button type="submit" id="btnStep2" class="w-full bg-indigo-600 text-white py-4 rounded-2xl font-bold hover:bg-indigo-700 shadow-lg shadow-indigo-100 flex justify-center items-center gap-2">
                    <span id="textStep2">Đổi mật khẩu</span>
                    <i id="spinStep2" class="fa-solid fa-spinner fa-spin hidden"></i>
                </button>
            </form>

            <div class="mt-8 pt-6 border-t border-slate-100 text-center text-sm text-slate-500">
                Nhớ ra rồi? <a href="login.php" class="font-bold text-indigo-600 hover:underline">Quay lại đăng nhập</a>
            </div>
        </div>
    </div>

    <script src="../assets/js/reset_password.js"></script>
</body>
</html>