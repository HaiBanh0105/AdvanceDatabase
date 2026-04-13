<div id="addTypeModal"
    class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4">
    <div
        class="bg-white rounded-[2.5rem] w-full max-w-lg shadow-2xl overflow-hidden animate-in zoom-in duration-300">
        <div class="p-6 border-b border-slate-100 flex justify-between items-center bg-slate-50/50">
            <h3 class="font-bold text-lg text-slate-800 uppercase tracking-tight">Định nghĩa Hạng phòng</h3>
            <button onclick="toggleModal('addTypeModal')" class="text-slate-400 hover:text-slate-600"><i
                    class="fa-solid fa-xmark"></i></button>
        </div>
        <form id="addTypeForm" action="../actions/process_add_room_type.php" method="POST"
            enctype="multipart/form-data" class="p-8 space-y-4">
            <input type="hidden" name="action" value="add">
            <div class="grid grid-cols-2 gap-4">
                <div class="col-span-2">
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Tên
                        hạng phòng</label>
                    <input type="text" name="name" required
                        class="w-full px-5 py-3 bg-slate-50 border border-slate-200 rounded-2xl focus:ring-2 focus:ring-indigo-500 outline-none transition-all">
                </div>
                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Giá /
                        Giờ</label>
                    <input type="number" name="price_per_hour" required
                        class="w-full px-5 py-3 bg-slate-50 border border-slate-200 rounded-2xl focus:ring-2 focus:ring-indigo-500 outline-none transition-all">
                </div>
                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Giá /
                        Ngày</label>
                    <input type="number" name="price_per_day" required
                        class="w-full px-5 py-3 bg-slate-50 border border-slate-200 rounded-2xl focus:ring-2 focus:ring-indigo-500 outline-none transition-all">
                </div>
                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Sức
                        chứa</label>
                    <input type="number" name="capacity" required
                        class="w-full px-5 py-3 bg-slate-50 border border-slate-200 rounded-2xl focus:ring-2 focus:ring-indigo-500 outline-none transition-all">
                </div>
                <div class="col-span-2">
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Mô tả
                        / Tiện nghi</label>
                    <textarea name="description" rows="3"
                        class="w-full px-5 py-3 bg-slate-50 border border-slate-200 rounded-2xl focus:ring-2 focus:ring-indigo-500 outline-none transition-all"
                        placeholder="Ví dụ: Nội thất gỗ, view biển, có bồn tắm..."></textarea>
                </div>
                <div class="col-span-2">
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Tải
                        ảnh lên (Lưu MongoDB)</label>
                    <input type="file" name="image" accept="image/*"
                        class="w-full px-5 py-3 bg-slate-50 border border-slate-200 rounded-2xl outline-none transition-all text-sm file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-indigo-50 file:text-indigo-600 hover:file:bg-indigo-100">
                </div>
            </div>
            <div class="pt-4 flex gap-3">
                <button type="button" onclick="toggleModal('addTypeModal')"
                    class="flex-1 px-4 py-4 text-slate-500 font-bold hover:bg-slate-50 rounded-2xl transition">Đóng</button>
                <button type="submit"
                    class="flex-1 px-4 py-4 bg-slate-800 text-white rounded-2xl font-bold shadow-lg shadow-slate-200 hover:bg-slate-900 transition">Cập
                    nhật Catalog</button>
            </div>
        </form>
    </div>
</div>