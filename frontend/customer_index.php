<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Grand Horizon Hotel - Hệ thống đặt phòng trực tuyến</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;600;700&display=swap');
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
    </style>
</head>
<body class="bg-gray-50">

<?php include 'navbar_customer.php'; ?>

<section class="pt-32 pb-20 px-6">
    <div class="max-w-7xl mx-auto grid lg:grid-cols-2 gap-12 items-center">
        <div>
            <span class="text-indigo-600 font-bold tracking-widest text-xs uppercase mb-4 block underline decoration-2 underline-offset-4">Chào mừng đến với Grand Horizon</span>
            <h1 class="text-5xl lg:text-6xl font-extrabold text-gray-900 leading-tight mb-6">
                Trải nghiệm kỳ nghỉ <span class="text-transparent bg-clip-text bg-gradient-to-r from-indigo-600 to-purple-600">đẳng cấp 5 sao</span>
            </h1>
            <p class="text-gray-500 text-lg mb-8 leading-relaxed">
                Nằm giữa lòng thành phố, khách sạn chúng tôi mang đến không gian nghỉ dưỡng sang trọng với đầy đủ tiện nghi hiện đại nhất.
            </p>
            <div class="flex gap-4">
                <a href="#rooms" class="bg-gray-900 text-white px-8 py-4 rounded-2xl font-bold hover:bg-gray-800 transition">Xem loại phòng</a>
                <button class="flex items-center gap-3 font-bold text-gray-700 px-8 py-4">
                    <i class="fa-solid fa-play bg-indigo-100 text-indigo-600 p-3 rounded-full text-xs"></i> Xem video
                </button>
            </div>
        </div>
        <div class="relative">
            <img src="https://images.unsplash.com/photo-1542314831-068cd1dbfeeb?auto=format&fit=crop&w=800" class="rounded-[2rem] shadow-2xl">
            <div class="absolute -bottom-10 -left-10 bg-white p-6 rounded-2xl shadow-xl border border-gray-100 hidden md:block">
                <div class="flex items-center gap-4">
                    <div class="flex -space-x-3">
                        <img src="https://i.pravatar.cc/40?img=1" class="w-10 h-10 rounded-full border-2 border-white">
                        <img src="https://i.pravatar.cc/40?img=2" class="w-10 h-10 rounded-full border-2 border-white">
                        <img src="https://i.pravatar.cc/40?img=3" class="w-10 h-10 rounded-full border-2 border-white">
                    </div>
                    <div>
                        <p class="text-xs font-bold text-gray-800">1,200+ khách hàng</p>
                        <p class="text-[10px] text-yellow-500 font-bold">★★★★★ 4.9/5</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<section id="rooms" class="py-24 bg-white">
    <div class="max-w-7xl mx-auto px-6">
        <div class="text-center mb-16">
            <h2 class="text-3xl font-bold text-gray-900 mb-4">Các loại phòng của chúng tôi</h2> [cite: 9]
            <div class="h-1 w-20 bg-indigo-600 mx-auto rounded-full"></div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            <div class="group border border-gray-100 rounded-3xl overflow-hidden hover:shadow-2xl transition duration-500">
                <div class="h-64 overflow-hidden relative">
                    <img src="https://images.unsplash.com/photo-1611892440504-42a792e24d32?auto=format&fit=crop&w=600" class="w-full h-full object-cover group-hover:scale-110 transition duration-700"> [cite: 10]
                    <div class="absolute top-4 left-4 bg-white/90 backdrop-blur px-3 py-1 rounded-full text-[10px] font-bold text-indigo-600 uppercase">
                        Sức chứa: 2 người [cite: 10]
                    </div>
                </div>
                <div class="p-8">
                    <h3 class="text-xl font-bold text-gray-900 mb-2 uppercase tracking-wide">Phòng Deluxe hướng biển</h3> [cite: 10]
                    <p class="text-gray-500 text-sm mb-6 line-clamp-2 italic">Mô tả: Nội thất gỗ sang trọng, ban công rộng hướng trực diện ra biển Đông.</p> [cite: 10]
                    <div class="flex items-center justify-between pt-6 border-t border-dashed border-gray-200">
                        <div>
                            <span class="text-2xl font-extrabold text-indigo-600">2.500.000đ</span> [cite: 10]
                            <span class="text-xs text-gray-400 font-bold">/đêm</span>
                        </div>
                        <button onclick="openBookingModal('Deluxe')" class="bg-gray-900 text-white px-5 py-2.5 rounded-xl text-xs font-bold hover:bg-indigo-600 transition shadow-lg">
                            Đặt phòng
                        </button> [cite: 21]
                    </div>
                </div>
            </div>
            </div>
    </div>
</section>

<footer class="bg-gray-50 py-12 border-t border-gray-200">
    <div class="max-w-7xl mx-auto px-6 text-center">
        <p class="text-gray-400 text-xs font-medium">© 2026 Grand Horizon Hotel. Tất cả quyền được bảo lưu.</p>
    </div>
</footer>

<script>
    function openBookingModal(roomType) {
        alert("Chức năng đặt phòng cho loại: " + roomType + " đang được xử lý!");
    }
</script>

</body>
</html>