<!DOCTYPE html>
<!-- Giao diện Đăng ký -->
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng ký - Grand Horizon</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
    @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;600;700&display=swap');

    body {
        font-family: 'Plus Jakarta Sans', sans-serif;
    }
    </style>
</head>

<body class="bg-slate-50 min-h-screen flex items-center justify-center p-4">

    <div class="max-w-md w-full my-8">
        <div class="text-center mb-8">
            <div
                class="inline-flex items-center justify-center bg-indigo-600 p-3 rounded-2xl shadow-lg shadow-indigo-200 mb-4">
                <i class="fa-solid fa-user-plus text-white text-2xl"></i>
            </div>
            <h1 class="text-3xl font-black text-slate-800 tracking-tight">Tạo tài khoản</h1>
            <p class="text-slate-500 mt-2">Đăng ký để bắt đầu trải nghiệm dịch vụ.</p>
        </div>

        <div class="bg-white p-8 rounded-[2.5rem] shadow-xl shadow-slate-200/60 border border-slate-100">

            <div id="alertBox"
                class="hidden mb-6 p-4 rounded-2xl text-sm font-bold flex items-center gap-3 border shadow-sm transition-all duration-500">
                <i id="alertIcon" class="fa-solid"></i>
                <span id="alertMessage"></span>
            </div>

            <form id="step1Form" class="space-y-5" onsubmit="event.preventDefault();">
                <input type="hidden" name="action" value="send_otp">
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-2 ml-1">Email</label>
                    <div class="relative">
                        <i class="fa-solid fa-envelope absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                        <input type="email" name="email" required
                            class="w-full pl-12 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-2xl focus:ring-2 focus:ring-indigo-500 outline-none transition-all text-sm"
                            placeholder="name@example.com">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-2 ml-1">Mật khẩu</label>
                    <div class="relative">
                        <i class="fa-solid fa-lock absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                        <input type="password" name="password" required
                            class="w-full pl-12 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-2xl focus:ring-2 focus:ring-indigo-500 outline-none transition-all text-sm"
                            placeholder="••••••••">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-2 ml-1">Xác nhận mật khẩu</label>
                    <div class="relative">
                        <i class="fa-solid fa-lock absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                        <input type="password" name="confirm_password" required
                            class="w-full pl-12 pr-10 py-3 bg-slate-50 border border-slate-200 rounded-2xl focus:ring-2 focus:ring-indigo-500 outline-none transition-all text-sm"
                            placeholder="••••••••">
                        <span id="confirmPasswordStatus"
                            class="absolute right-4 top-1/2 -translate-y-1/2 flex items-center justify-center"></span>
                    </div>
                </div>

                <button type="submit" id="btnStep1"
                    class="w-full bg-indigo-600 text-white py-4 rounded-2xl font-bold flex items-center justify-center gap-2 hover:bg-indigo-700 transition-all active:scale-95 shadow-lg shadow-indigo-100 mt-2">
                    <span id="textStep1">Đăng ký & Nhận mã OTP</span>
                    <i id="spinStep1" class="fa-solid fa-spinner fa-spin hidden"></i>
                </button>
            </form>

            <form id="step2Form" class="space-y-6 hidden" onsubmit="event.preventDefault();">
                <input type="hidden" name="action" value="verify_otp">
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-2 ml-1">Mã OTP (6 số)</label>
                    <div class="relative">
                        <i
                            class="fa-solid fa-shield-halved absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                        <input type="text" name="otp" required maxlength="6"
                            class="w-full pl-12 pr-4 py-4 bg-slate-50 border border-slate-200 rounded-2xl tracking-widest text-center font-bold text-lg focus:ring-2 focus:ring-indigo-500 outline-none"
                            placeholder="------">
                    </div>
                </div>
                <button type="submit" id="btnStep2"
                    class="w-full bg-indigo-600 text-white py-4 rounded-2xl font-bold hover:bg-indigo-700 shadow-lg shadow-indigo-100 flex justify-center items-center gap-2">
                    <span id="textStep2">Xác nhận đăng ký</span>
                    <i id="spinStep2" class="fa-solid fa-spinner fa-spin hidden"></i>
                </button>
            </form>

            <div class="mt-6 pt-6 border-t border-slate-100 text-center text-sm text-slate-500">
                Đã có tài khoản? <a href="login.php" class="font-bold text-indigo-600 hover:underline">Đăng nhập</a>
            </div>
        </div>
    </div>

    <script src="../assets/js/register.js?v=<?php echo time(); ?>"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const passwordInput = document.querySelector('input[name="password"]');
        const confirmPasswordInput = document.querySelector('input[name="confirm_password"]');
        const statusIcon = document.getElementById('confirmPasswordStatus');

        function validatePasswords() {
            const password = passwordInput.value;
            const confirmPassword = confirmPasswordInput.value;

            // Don't show anything if the confirm password field is empty
            if (confirmPassword.length === 0) {
                statusIcon.innerHTML = '';
                return;
            }

            if (password === confirmPassword) {
                statusIcon.innerHTML = '<i class="fa-solid fa-check text-emerald-500"></i>';
            } else {
                statusIcon.innerHTML = '<i class="fa-solid fa-xmark text-red-500"></i>';
            }
        }

        confirmPasswordInput.addEventListener('input', validatePasswords);
        passwordInput.addEventListener('input', validatePasswords);
    });
    </script>
</body>

</html>