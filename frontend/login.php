<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập - Grand Horizon</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;600;700&display=swap');
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
    </style>
</head>
<body class="bg-slate-50 h-screen flex items-center justify-center p-4">

    <div class="max-w-md w-full">
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center bg-indigo-600 p-3 rounded-2xl shadow-lg shadow-indigo-200 mb-4">
                <i class="fa-solid fa-crown text-white text-2xl"></i>
            </div>
            <h1 class="text-3xl font-black text-slate-800 tracking-tight">Chào mừng trở lại!</h1>
            <p class="text-slate-500 mt-2">Vui lòng đăng nhập để tiếp tục.</p>
        </div>

        <div class="bg-white p-8 rounded-[2.5rem] shadow-xl shadow-slate-200/60 border border-slate-100">
            
            <div id="alertBox" class="hidden mb-6 p-4 rounded-2xl text-sm font-bold flex items-center gap-3 border shadow-sm transition-all duration-500">
                <i id="alertIcon" class="fa-solid"></i>
                <span id="alertMessage"></span>
            </div>

            <form id="loginForm" class="space-y-6">
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-2 ml-1">Email</label>
                    <div class="relative">
                        <i class="fa-solid fa-envelope absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                        <input type="email" id="email" name="email" required
                               class="w-full pl-12 pr-4 py-4 bg-slate-50 border border-slate-200 rounded-2xl focus:ring-2 focus:ring-indigo-500 outline-none transition-all text-sm"
                               placeholder="name@example.com">
                    </div>
                </div>

                <div>
                    <div class="flex justify-between mb-2 ml-1">
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest">Mật khẩu</label>
                    </div>
                    <div class="relative">
                        <i class="fa-solid fa-lock absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                        <input type="password" id="password" name="password" required
                               class="w-full pl-12 pr-4 py-4 bg-slate-50 border border-slate-200 rounded-2xl focus:ring-2 focus:ring-indigo-500 outline-none transition-all text-sm"
                               placeholder="••••••••">

                               <button type="button" id="togglePassword" class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 hover:text-indigo-600 transition-colors focus:outline-none">
            <i id="eyeIcon" class="fa-solid fa-eye"></i>
        </button>
                    </div>
                </div>

                <button type="submit" id="submitBtn" 
                        class="w-full bg-indigo-600 text-white py-4 rounded-2xl font-bold flex items-center justify-center gap-2 hover:bg-indigo-700 transition-all active:scale-95 shadow-lg shadow-indigo-100">
                    <span id="btnText">Đăng nhập ngay</span>
                    <i id="btnSpinner" class="fa-solid fa-spinner fa-spin hidden"></i>
                </button>
            </form>

            <div class="mt-8 pt-6 border-t border-slate-100 text-center text-sm text-slate-500">
                Chưa có tài khoản? <a href="register.php" class="font-bold text-indigo-600 hover:underline">Đăng ký ngay</a>
            </div>
        </div>
    </div>

    <script src="../assets/js/login.js"></script>
</body>
</html>