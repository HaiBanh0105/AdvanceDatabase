<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản trị Khách sạn - Grand Horizon</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-slate-50 flex">

    <?php include 'sidebar_admin.php'; ?>
    
    <main class="flex-1 p-4 md:p-8">
        <div class="flex items-center gap-8 border-b border-slate-200 mb-8">
            <button onclick="switchTab('roomsTab', this)" class="tab-btn pb-4 text-sm font-bold text-indigo-600 border-b-2 border-indigo-600 transition-all">
                <i class="fa-solid fa-bed mr-2"></i>Quản lý danh sách phòng
            </button>
            <button onclick="switchTab('roomTypesTab', this)" class="tab-btn pb-4 text-sm font-medium text-slate-400 hover:text-indigo-600 transition-all">
                <i class="fa-solid fa-gears mr-2"></i>Cấu hình Loại phòng
            </button>
        </div>

        <div id="roomsTab" class="tab-content block animate-in fade-in duration-500">
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

            <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
                <h2 class="text-xl font-bold text-slate-800">Danh sách thực thể phòng</h2>
                <button onclick="toggleModal('addRoomModal')" class="bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-2.5 rounded-xl font-bold shadow-lg shadow-indigo-200 transition flex items-center gap-2">
                    <i class="fa-solid fa-plus"></i> Thêm phòng mới
                </button>
            </div>
            
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                <table class="w-full text-left">
                    <thead class="bg-slate-50 border-b border-slate-100">
                        <tr class="text-slate-500 text-[10px] uppercase tracking-widest font-bold">
                            <th class="px-6 py-4">Số phòng</th>
                            <th class="px-6 py-4">Loại phòng</th>
                            <th class="px-6 py-4">Trạng thái</th>
                            <th class="px-6 py-4 text-right">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-sm">
                        <tr class="hover:bg-slate-50/50 transition">
                            <td class="px-6 py-4 font-bold text-slate-700">P.101</td>
                            <td class="px-6 py-4 text-slate-600">Deluxe Ocean View</td>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-600">ACTIVE</span>
                            </td>
                            <td class="px-6 py-4 text-right space-x-2">
                                <button class="text-slate-400 hover:text-indigo-600"><i class="fa-solid fa-pen-to-square"></i></button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div id="roomTypesTab" class="tab-content hidden animate-in fade-in duration-500">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
                <div>
                    <h2 class="text-xl font-bold text-slate-800">Thiết lập Catalog & Giá</h2>
                    <p class="text-xs text-slate-400 italic mt-1">* Lưu ý: Thay đổi giá > 50% sẽ kích hoạt Audit Trigger.</p>
                </div>
                <button onclick="toggleModal('addTypeModal')" class="bg-slate-800 hover:bg-slate-900 text-white px-6 py-2.5 rounded-xl font-bold transition flex items-center gap-2">
                    <i class="fa-solid fa-layer-group"></i> Định nghĩa hạng phòng
                </button>
            </div>

            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                <table class="w-full text-left">
                    <thead class="bg-slate-50 border-b border-slate-100">
                        <tr class="text-slate-500 text-[10px] uppercase tracking-widest font-bold">
                            <th class="px-6 py-4">Hạng phòng</th>
                            <th class="px-6 py-4">Giá (SQL ACID)</th>
                            <th class="px-6 py-4">Sức chứa</th>
                            <th class="px-6 py-4">Tiện nghi (NoSQL)</th>
                            <th class="px-6 py-4 text-right">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-sm">
                        <tr class="hover:bg-slate-50/50 transition">
                            <td class="px-6 py-4 font-bold text-slate-700">Deluxe Ocean View</td>
                            <td class="px-6 py-4 text-indigo-600 font-bold">2.500.000đ</td>
                            <td class="px-6 py-4 text-slate-600">02 Người lớn</td>
                            <td class="px-6 py-4">
                                <div class="flex gap-1">
                                    <span class="px-2 py-0.5 bg-slate-100 rounded text-[10px]">Wifi</span>
                                    <span class="px-2 py-0.5 bg-slate-100 rounded text-[10px]">Pool</span>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-right space-x-2">
                                <button class="text-slate-400 hover:text-indigo-600"><i class="fa-solid fa-wrench"></i></button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <div id="addRoomModal" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-[2.5rem] w-full max-w-md shadow-2xl overflow-hidden animate-in zoom-in duration-300">
        <div class="p-6 border-b border-slate-100 flex justify-between items-center">
            <h3 class="font-bold text-lg text-slate-800 uppercase tracking-tight">Thêm phòng mới</h3>
            <button onclick="toggleModal('addRoomModal')" class="text-slate-400 hover:text-slate-600"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <form id="addRoomForm" class="p-8 space-y-5">
            <div>
                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Số phòng (room_number)</label>
                <input type="text" name="room_number" required class="w-full px-5 py-3 bg-slate-50 border border-slate-200 rounded-2xl focus:ring-2 focus:ring-indigo-500 outline-none transition-all" placeholder="VD: P.101">
            </div>
            <div>
                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Hạng phòng (type_id)</label>
                <select name="type_id" class="w-full px-5 py-3 bg-slate-50 border border-slate-200 rounded-2xl focus:ring-2 focus:ring-indigo-500 outline-none transition-all">
                    <option value="1">Deluxe Ocean View</option>
                    <option value="2">Standard City View</option>
                </select>
            </div>
            <div>
                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Trạng thái khởi tạo</label>
                <select name="status" class="w-full px-5 py-3 bg-slate-50 border border-slate-200 rounded-2xl focus:ring-2 focus:ring-indigo-500 outline-none transition-all">
                    <option value="active">Đang hoạt động (Active)</option>
                    <option value="maintenance">Bảo trì (Maintenance)</option>
                </select>
            </div>
            <div class="pt-4 flex gap-3">
                <button type="button" onclick="toggleModal('addRoomModal')" class="flex-1 px-4 py-4 text-slate-500 font-bold hover:bg-slate-50 rounded-2xl transition">Hủy</button>
                <button type="submit" class="flex-1 px-4 py-4 bg-indigo-600 text-white rounded-2xl font-bold shadow-lg shadow-indigo-100 hover:bg-indigo-700 transition">Lưu phòng</button>
            </div>
        </form>
    </div>
</div>

<div id="addTypeModal" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-[2.5rem] w-full max-w-lg shadow-2xl overflow-hidden animate-in zoom-in duration-300">
        <div class="p-6 border-b border-slate-100 flex justify-between items-center bg-slate-50/50">
            <h3 class="font-bold text-lg text-slate-800 uppercase tracking-tight">Định nghĩa Hạng phòng</h3>
            <button onclick="toggleModal('addTypeModal')" class="text-slate-400 hover:text-slate-600"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <form id="addTypeForm" class="p-8 space-y-4">
            <div class="grid grid-cols-2 gap-4">
                <div class="col-span-2">
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Tên hạng phòng</label>
                    <input type="text" name="name" required class="w-full px-5 py-3 bg-slate-50 border border-slate-200 rounded-2xl focus:ring-2 focus:ring-indigo-500 outline-none transition-all">
                </div>
                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Đơn giá (ACID SQL)</label>
                    <input type="number" name="price" required class="w-full px-5 py-3 bg-slate-50 border border-slate-200 rounded-2xl focus:ring-2 focus:ring-indigo-500 outline-none transition-all">
                </div>
                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Sức chứa</label>
                    <input type="number" name="capacity" required class="w-full px-5 py-3 bg-slate-50 border border-slate-200 rounded-2xl focus:ring-2 focus:ring-indigo-500 outline-none transition-all">
                </div>
                <div class="col-span-2">
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Tiện nghi (NoSQL Amenities)</label>
                    <div class="flex flex-wrap gap-2 p-3 bg-slate-50 border border-slate-100 rounded-2xl">
                        <label class="flex items-center gap-2 text-xs font-bold text-slate-600"><input type="checkbox" name="amenities[]" value="wifi"> Wifi</label>
                        <label class="flex items-center gap-2 text-xs font-bold text-slate-600 ml-4"><input type="checkbox" name="amenities[]" value="pool"> Hồ bơi</label>
                        <label class="flex items-center gap-2 text-xs font-bold text-slate-600 ml-4"><input type="checkbox" name="amenities[]" value="gym"> Gym</label>
                    </div>
                </div>
            </div>
            <div class="pt-4 flex gap-3">
                <button type="button" onclick="toggleModal('addTypeModal')" class="flex-1 px-4 py-4 text-slate-500 font-bold hover:bg-slate-50 rounded-2xl transition">Đóng</button>
                <button type="submit" class="flex-1 px-4 py-4 bg-slate-800 text-white rounded-2xl font-bold shadow-lg shadow-slate-200 hover:bg-slate-900 transition">Cập nhật Catalog</button>
            </div>
        </form>
    </div>
</div>

  <script src="../assets/js/rooms_management.js"></script>  
    
</body>
</html>