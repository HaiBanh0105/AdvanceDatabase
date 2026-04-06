<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header("Location: login.php");
    exit();
}
require_once '../dao/DAO.php';
require_once '../config/mongodb.php';

// Lấy tất cả ảnh từ MongoDB cho các hạng phòng
$mongo_db = mongo_get_db();
$images_cursor = $mongo_db->room_images->find([]);
$mongo_images = [];
foreach ($images_cursor as $img) {
    $mongo_images[$img['type_id']] = [
        'base64' => $img['image_base64'],
        'mime' => $img['mime_type'] ?? 'image/jpeg'
    ];
}

// Lấy danh sách Hạng phòng (Room Types)
$room_types = db_query("SELECT * FROM Room_types ORDER BY type_id DESC");

// Lấy danh sách Phòng cụ thể kèm thông tin Hạng phòng (Rooms JOIN Room_types)
$rooms = db_query("SELECT r.*, rt.name as type_name FROM Room r LEFT JOIN Room_types rt ON r.type_id = rt.type_id ORDER BY r.room_id DESC");
?>
<!DOCTYPE html>
<!-- Giao diện Quản lý Phòng (Admin) -->
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản trị Khách sạn - Grand Horizon</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-slate-50 flex">

    <!-- Cảnh báo Nổi (Toast Messages) -->
    <div id="toast-container" class="fixed top-24 right-6 z-[100] flex flex-col gap-3 pointer-events-none">
        <?php if (isset($_GET['error']) && $_GET['error'] == 'has_bookings'): ?>
            <div class="toast-alert p-4 bg-red-100 text-red-600 border border-red-200 rounded-2xl text-sm font-bold flex items-center gap-3 shadow-lg transition-all duration-500">
                <i class="fa-solid fa-circle-exclamation text-lg"></i> Lỗi: Đã có đơn đặt phòng cho hạng phòng này, không thể xóa!
            </div>
        <?php elseif (isset($_GET['error']) && $_GET['error'] == 'room_has_bookings'): ?>
            <div class="toast-alert p-4 bg-red-100 text-red-600 border border-red-200 rounded-2xl text-sm font-bold flex items-center gap-3 shadow-lg transition-all duration-500">
                <i class="fa-solid fa-circle-exclamation text-lg"></i> Lỗi: Phòng này đã từng được đặt, không thể xóa!
            </div>
        <?php elseif (isset($_GET['msg'])): ?>
            <div class="toast-alert p-4 bg-emerald-100 text-emerald-600 border border-emerald-200 rounded-2xl text-sm font-bold flex items-center gap-3 shadow-lg transition-all duration-500">
                <i class="fa-solid fa-circle-check text-lg"></i> Thao tác thành công!
            </div>
        <?php endif; ?>
    </div>

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
                <input type="text" id="searchRoomInput" placeholder="Tìm số phòng..." class="w-full pl-10 pr-4 py-2 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>
            <select id="filterRoomType" class="px-4 py-2 border border-slate-200 rounded-lg focus:outline-none text-slate-600">
                <option value="">Tất cả loại phòng</option>
                <?php foreach ($room_types as $rt): ?>
                    <option value="<?php echo $rt['type_id']; ?>"><?php echo htmlspecialchars($rt['name']); ?></option>
                <?php endforeach; ?>
            </select>
            <select id="filterRoomStatus" class="px-4 py-2 border border-slate-200 rounded-lg focus:outline-none text-slate-600">
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
                    <tbody id="roomsTableBody" class="divide-y divide-slate-100 text-sm">
                        <?php if (empty($rooms)): ?>
                            <tr><td colspan="4" class="px-6 py-8 text-center text-slate-500">Chưa có dữ liệu phòng.</td></tr>
                        <?php else: ?>
                            <?php foreach ($rooms as $room): ?>
                            <tr class="hover:bg-slate-50/50 transition">
                                <td class="px-6 py-4 font-bold text-slate-700"><?php echo htmlspecialchars($room['room_number']); ?></td>
                                <td class="px-6 py-4 text-slate-600"><?php echo htmlspecialchars($room['type_name'] ?? 'Không xác định'); ?></td>
                                <td class="px-6 py-4">
                                    <?php if ($room['status'] === 'active'): ?>
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-600">ACTIVE</span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[10px] font-bold bg-amber-100 text-amber-600 uppercase"><?php echo htmlspecialchars($room['status']); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 text-right space-x-2">
                                    <button onclick="openEditRoomModal(<?php echo $room['room_id']; ?>, '<?php echo htmlspecialchars($room['room_number']); ?>', <?php echo $room['type_id']; ?>, '<?php echo $room['status']; ?>')" class="text-amber-500 hover:text-amber-700 bg-amber-50 p-2 rounded-lg transition" title="Sửa"><i class="fa-solid fa-pen-to-square"></i></button>
                                    <form action="../actions/process_add_room.php" method="POST" class="inline" onsubmit="return confirm('Bạn có chắc chắn muốn xóa phòng này không?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="room_id" value="<?php echo $room['room_id']; ?>">
                                        <button type="submit" class="text-red-500 hover:text-red-700 bg-red-50 p-2 rounded-lg transition" title="Xóa"><i class="fa-solid fa-trash-can"></i></button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
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
                            <th class="px-6 py-4">Hình ảnh</th>
                            <th class="px-6 py-4">Hạng phòng</th>
                            <th class="px-6 py-4">Giá (SQL ACID)</th>
                            <th class="px-6 py-4">Sức chứa</th>
                            <th class="px-6 py-4">Tiện nghi (NoSQL)</th>
                            <th class="px-6 py-4 text-right">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-sm">
                        <?php if (empty($room_types)): ?>
                            <tr><td colspan="5" class="px-6 py-8 text-center text-slate-500">Chưa có hạng phòng nào.</td></tr>
                        <?php else: ?>
                            <?php foreach ($room_types as $rt): ?>
                            <tr class="hover:bg-slate-50/50 transition">
                                <td class="px-6 py-4">
                                    <?php if (isset($mongo_images[$rt['type_id']])): ?>
                                        <img src="data:<?php echo $mongo_images[$rt['type_id']]['mime']; ?>;base64,<?php echo $mongo_images[$rt['type_id']]['base64']; ?>" class="w-12 h-12 rounded-lg object-cover shadow-sm">
                                    <?php else: ?>
                                        <div class="w-12 h-12 rounded-lg bg-slate-100 flex items-center justify-center text-slate-400"><i class="fa-solid fa-image"></i></div>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 font-bold text-slate-700"><?php echo htmlspecialchars($rt['name']); ?></td>
                                <td class="px-6 py-4 text-indigo-600 font-bold"><?php echo number_format($rt['price'], 0, ',', '.'); ?>đ</td>
                                <td class="px-6 py-4 text-slate-600"><?php echo str_pad($rt['capacity'], 2, '0', STR_PAD_LEFT); ?> Người</td>
                                <td class="px-6 py-4">
                                    <div class="text-xs text-slate-500 max-w-xs truncate" title="<?php echo htmlspecialchars($rt['description'] ?? ''); ?>">
                                        <?php echo htmlspecialchars($rt['description'] ?? 'Chưa có mô tả'); ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-right space-x-2">
                                    <button onclick="openEditTypeModal(<?php echo $rt['type_id']; ?>, '<?php echo htmlspecialchars(addslashes($rt['name'])); ?>', <?php echo $rt['price']; ?>, <?php echo $rt['capacity']; ?>, '<?php echo htmlspecialchars(addslashes($rt['description'] ?? '')); ?>')" class="text-amber-500 hover:text-amber-700 bg-amber-50 p-2 rounded-lg transition" title="Sửa"><i class="fa-solid fa-pen-to-square"></i></button>
                                    <form action="../actions/process_add_room_type.php" method="POST" class="inline" onsubmit="return confirm('Bạn có chắc chắn muốn xóa hạng phòng này không? Các phòng thuộc hạng này cũng có thể bị ảnh hưởng.');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="type_id" value="<?php echo $rt['type_id']; ?>">
                                        <button type="submit" class="text-red-500 hover:text-red-700 bg-red-50 p-2 rounded-lg transition" title="Xóa"><i class="fa-solid fa-trash-can"></i></button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
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
        <form id="addRoomForm" action="../actions/process_add_room.php" method="POST" class="p-8 space-y-5">
            <input type="hidden" name="action" value="add">
            <div>
                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Số phòng (room_number)</label>
                <input type="text" name="room_number" required class="w-full px-5 py-3 bg-slate-50 border border-slate-200 rounded-2xl focus:ring-2 focus:ring-indigo-500 outline-none transition-all" placeholder="VD: P.101">
            </div>
            <div>
                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Hạng phòng (type_id)</label>
                <select name="type_id" class="w-full px-5 py-3 bg-slate-50 border border-slate-200 rounded-2xl focus:ring-2 focus:ring-indigo-500 outline-none transition-all">
                    <?php foreach ($room_types as $rt): ?>
                        <option value="<?php echo $rt['type_id']; ?>"><?php echo htmlspecialchars($rt['name']); ?></option>
                    <?php endforeach; ?>
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

<!-- Modal Sửa Phòng Cụ Thể -->
<div id="editRoomModal" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-[2.5rem] w-full max-w-md shadow-2xl overflow-hidden animate-in zoom-in duration-300">
        <div class="p-6 border-b border-slate-100 flex justify-between items-center bg-slate-50/50">
            <h3 class="font-bold text-lg text-slate-800 uppercase tracking-tight">Sửa Thông tin Phòng</h3>
            <button onclick="toggleModal('editRoomModal')" class="text-slate-400 hover:text-slate-600"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <form action="../actions/process_add_room.php" method="POST" class="p-8 space-y-5">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="room_id" id="edit_room_id">
            <div>
                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Số phòng</label>
                <input type="text" name="room_number" id="edit_room_number" required class="w-full px-5 py-3 bg-slate-50 border border-slate-200 rounded-2xl focus:ring-2 focus:ring-indigo-500 outline-none transition-all">
            </div>
            <div>
                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Hạng phòng</label>
                <select name="type_id" id="edit_room_type_id" class="w-full px-5 py-3 bg-slate-50 border border-slate-200 rounded-2xl focus:ring-2 focus:ring-indigo-500 outline-none transition-all">
                    <?php foreach ($room_types as $rt): ?>
                        <option value="<?php echo $rt['type_id']; ?>"><?php echo htmlspecialchars($rt['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Trạng thái</label>
                <select name="status" id="edit_room_status" class="w-full px-5 py-3 bg-slate-50 border border-slate-200 rounded-2xl focus:ring-2 focus:ring-indigo-500 outline-none transition-all">
                    <option value="active">Đang hoạt động (Active)</option>
                    <option value="maintenance">Bảo trì (Maintenance)</option>
                </select>
            </div>
            <div class="pt-4 flex gap-3">
                <button type="button" onclick="toggleModal('editRoomModal')" class="flex-1 px-4 py-4 text-slate-500 font-bold hover:bg-slate-50 rounded-2xl transition">Hủy</button>
                <button type="submit" class="flex-1 px-4 py-4 bg-amber-500 text-white rounded-2xl font-bold shadow-lg shadow-amber-200 hover:bg-amber-600 transition">Lưu thay đổi</button>
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
        <form id="addTypeForm" action="../actions/process_add_room_type.php" method="POST" enctype="multipart/form-data" class="p-8 space-y-4">
            <input type="hidden" name="action" value="add">
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
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Mô tả / Tiện nghi</label>
                    <textarea name="description" rows="3" class="w-full px-5 py-3 bg-slate-50 border border-slate-200 rounded-2xl focus:ring-2 focus:ring-indigo-500 outline-none transition-all" placeholder="Ví dụ: Nội thất gỗ, view biển, có bồn tắm..."></textarea>
                </div>
                <div class="col-span-2">
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Tải ảnh lên (Lưu MongoDB)</label>
                    <input type="file" name="image" accept="image/*" class="w-full px-5 py-3 bg-slate-50 border border-slate-200 rounded-2xl outline-none transition-all text-sm file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-indigo-50 file:text-indigo-600 hover:file:bg-indigo-100">
                </div>
            </div>
            <div class="pt-4 flex gap-3">
                <button type="button" onclick="toggleModal('addTypeModal')" class="flex-1 px-4 py-4 text-slate-500 font-bold hover:bg-slate-50 rounded-2xl transition">Đóng</button>
                <button type="submit" class="flex-1 px-4 py-4 bg-slate-800 text-white rounded-2xl font-bold shadow-lg shadow-slate-200 hover:bg-slate-900 transition">Cập nhật Catalog</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Sửa Hạng Phòng -->
<div id="editTypeModal" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-[100] hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-[2.5rem] w-full max-w-lg shadow-2xl overflow-hidden animate-in zoom-in duration-300">
        <div class="p-6 border-b border-slate-100 flex justify-between items-center bg-slate-50/50">
            <h3 class="font-bold text-lg text-slate-800 uppercase tracking-tight">Sửa Hạng phòng</h3>
            <button onclick="toggleModal('editTypeModal')" class="text-slate-400 hover:text-slate-600"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <form id="editTypeForm" action="../actions/process_add_room_type.php" method="POST" enctype="multipart/form-data" class="p-8 space-y-4">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="type_id" id="edit_type_id">
            <div class="grid grid-cols-2 gap-4">
                <div class="col-span-2">
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Tên hạng phòng</label>
                    <input type="text" name="name" id="edit_type_name" required class="w-full px-5 py-3 bg-slate-50 border border-slate-200 rounded-2xl focus:ring-2 focus:ring-indigo-500 outline-none transition-all">
                </div>
                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Đơn giá</label>
                    <input type="number" name="price" id="edit_type_price" required class="w-full px-5 py-3 bg-slate-50 border border-slate-200 rounded-2xl focus:ring-2 focus:ring-indigo-500 outline-none transition-all">
                </div>
                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Sức chứa</label>
                    <input type="number" name="capacity" id="edit_type_capacity" required class="w-full px-5 py-3 bg-slate-50 border border-slate-200 rounded-2xl focus:ring-2 focus:ring-indigo-500 outline-none transition-all">
                </div>
                <div class="col-span-2">
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Mô tả / Tiện nghi</label>
                    <textarea name="description" id="edit_type_desc" rows="2" class="w-full px-5 py-3 bg-slate-50 border border-slate-200 rounded-2xl focus:ring-2 focus:ring-indigo-500 outline-none transition-all"></textarea>
                </div>
                <div class="col-span-2">
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Hình ảnh mới (Để trống nếu không đổi)</label>
                    <input type="file" name="image" accept="image/*" class="w-full px-5 py-3 bg-slate-50 border border-slate-200 rounded-2xl outline-none transition-all text-sm file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-indigo-50 file:text-indigo-600 hover:file:bg-indigo-100">
                </div>
            </div>
            <div class="pt-4 flex gap-3">
                <button type="button" onclick="toggleModal('editTypeModal')" class="flex-1 px-4 py-4 text-slate-500 font-bold hover:bg-slate-50 rounded-2xl transition">Hủy</button>
                <button type="submit" class="flex-1 px-4 py-4 bg-amber-500 text-white rounded-2xl font-bold shadow-lg shadow-amber-200 hover:bg-amber-600 transition">Lưu thay đổi</button>
            </div>
        </form>
    </div>
</div>

  <script src="../assets/js/rooms_management.js"></script>  
  
  <!-- Script Bổ trợ cho Logic tìm kiếm và Toggle -->
  <script>
        // Auto ẩn thông báo (Toast)
        setTimeout(() => { document.querySelectorAll('.toast-alert').forEach(el => { el.style.opacity = '0'; setTimeout(() => el.remove(), 500); }); }, 4000);

        // Mở Modal Sửa hạng phòng
        function openEditTypeModal(id, name, price, capacity, desc) { document.getElementById('edit_type_id').value = id; document.getElementById('edit_type_name').value = name; document.getElementById('edit_type_price').value = price; document.getElementById('edit_type_capacity').value = capacity; document.getElementById('edit_type_desc').value = desc; toggleModal('editTypeModal'); }

        // Mở Modal Sửa phòng thực tế
        function openEditRoomModal(id, num, typeId, status) { document.getElementById('edit_room_id').value = id; document.getElementById('edit_room_number').value = num; document.getElementById('edit_room_type_id').value = typeId; document.getElementById('edit_room_status').value = status; toggleModal('editRoomModal'); }

        // Bộ lọc thời gian thực cho danh sách Phòng (roomTab)
        const searchInput = document.getElementById('searchRoomInput'); const filterType = document.getElementById('filterRoomType'); const filterStatus = document.getElementById('filterRoomStatus'); const tableBody = document.getElementById('roomsTableBody');
        function filterRooms() {
            if (!tableBody) return;
            const term = searchInput.value.toLowerCase(); const typeText = filterType.options[filterType.selectedIndex].text.toLowerCase(); const typeVal = filterType.value; const status = filterStatus.value.toLowerCase();
            tableBody.querySelectorAll('tr').forEach(row => {
                if (row.cells.length < 4) return;
                const rNum = row.cells[0].innerText.toLowerCase(); const rType = row.cells[1].innerText.toLowerCase(); const rStatus = row.cells[2].innerText.toLowerCase();
                row.style.display = (rNum.includes(term) && (typeVal === "" || rType === typeText) && (status === "" || rStatus.includes(status))) ? '' : 'none';
            });
        }
        if (searchInput) searchInput.addEventListener('input', filterRooms);
        if (filterType) filterType.addEventListener('change', filterRooms);
        if (filterStatus) filterStatus.addEventListener('change', filterRooms);

        // Nếu URL có tham số tab=types thì tự động switch (phòng khi reload sau khi thêm/sửa)
        if (new URLSearchParams(window.location.search).get('tab') === 'types') { const tabBtns = document.querySelectorAll('.tab-btn'); if (tabBtns.length > 1) { try { switchTab('roomTypesTab', tabBtns[1]); } catch(e) {} } }
  </script>

</body>
</html>