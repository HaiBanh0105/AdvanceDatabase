<div id="editRoomModal"
    class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-[2.5rem] w-full max-w-md shadow-2xl overflow-hidden animate-in zoom-in duration-300">
        <div class="p-6 border-b border-slate-100 flex justify-between items-center bg-slate-50/50">
            <h3 class="font-bold text-lg text-slate-800 uppercase tracking-tight">Sửa Thông tin Phòng</h3>
            <button onclick="toggleModal('editRoomModal')" class="text-slate-400 hover:text-slate-600"><i
                    class="fa-solid fa-xmark"></i></button>
        </div>
        <form action="../actions/process_add_room.php" method="POST" class="p-8 space-y-5">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="room_id" id="edit_room_id">
            <div>
                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Số
                    phòng</label>
                <input type="text" name="room_number" id="edit_room_number" required
                    class="w-full px-5 py-3 bg-slate-50 border border-slate-200 rounded-2xl focus:ring-2 focus:ring-indigo-500 outline-none transition-all">
            </div>
            <div>
                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Hạng
                    phòng</label>
                <select name="type_id" id="edit_room_type_id"
                    class="w-full px-5 py-3 bg-slate-50 border border-slate-200 rounded-2xl focus:ring-2 focus:ring-indigo-500 outline-none transition-all">
                    <?php foreach ($room_types as $rt): ?>
                    <option value="<?php echo $rt['type_id']; ?>"><?php echo htmlspecialchars($rt['name']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Trạng
                    thái</label>
                <select name="status" id="edit_room_status"
                    class="w-full px-5 py-3 bg-slate-50 border border-slate-200 rounded-2xl focus:ring-2 focus:ring-indigo-500 outline-none transition-all">
                    <option value="available">Sẵn sàng (Available)</option>
                    <option value="maintenance">Bảo trì (Maintenance)</option>
                    <option value="cleaning">Dọn dẹp (Cleaning)</option>
                </select>
            </div>
            <div class="pt-4 flex gap-3">
                <button type="button" onclick="toggleModal('editRoomModal')"
                    class="flex-1 px-4 py-4 text-slate-500 font-bold hover:bg-slate-50 rounded-2xl transition">Hủy</button>
                <button type="submit"
                    class="flex-1 px-4 py-4 bg-amber-500 text-white rounded-2xl font-bold shadow-lg shadow-amber-200 hover:bg-amber-600 transition">Lưu
                    thay đổi</button>
            </div>
        </form>
    </div>
</div>