<div id="walkinModal"
    class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 hidden items-center justify-center p-4">
    <div
        class="bg-white rounded-[2.5rem] w-full max-w-2xl shadow-2xl overflow-hidden max-h-[90vh] flex flex-col animate-in zoom-in duration-200">
        <div class="p-6 border-b border-slate-100 flex justify-between items-center bg-slate-50">
            <h3 class="font-bold text-lg text-slate-800 uppercase tracking-tight">Đăng ký Lưu trú — Phòng <span
                    id="wi_room_number" class="text-indigo-600"></span></h3>
            <button onclick="toggleModal('walkinModal')" class="text-slate-400 hover:text-slate-600"><i
                    class="fa-solid fa-xmark text-lg"></i></button>
        </div>
        <form action="../actions/process_admin_booking.php" method="POST" class="p-8 space-y-6 overflow-y-auto"
            onsubmit="return validateWalkinForm(event)">
            <input type="hidden" name="action" value="create_walkin">
            <input type="hidden" name="room_id" id="wi_room_id">

            <div class="p-4 bg-indigo-50/50 rounded-2xl border border-indigo-100 grid grid-cols-2 gap-4">
                <div class="col-span-2">
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-3">Hình
                        thức thuê</label>
                    <div class="flex gap-4">
                        <label
                            class="flex-1 flex items-center gap-2 p-3 bg-white border border-slate-200 rounded-xl cursor-pointer hover:border-indigo-500 transition">
                            <input type="radio" name="rental_type" value="hourly" checked
                                class="text-indigo-600 focus:ring-indigo-500" onchange="updateCalc()">
                            <span class="text-sm font-bold text-slate-700">Theo giờ <span id="wi_price_hr"
                                    class="text-xs font-normal text-slate-400 block"></span></span>
                        </label>
                        <label
                            class="flex-1 flex items-center gap-2 p-3 bg-white border border-slate-200 rounded-xl cursor-pointer hover:border-indigo-500 transition">
                            <input type="radio" name="rental_type" value="daily"
                                class="text-indigo-600 focus:ring-indigo-500" onchange="updateCalc()">
                            <span class="text-sm font-bold text-slate-700">Theo ngày <span id="wi_price_day"
                                    class="text-xs font-normal text-slate-400 block"></span></span>
                        </label>
                    </div>
                </div>
                <div class="col-span-2">
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Thời
                        gian lưu trú (Giờ / Ngày)</label>
                    <input type="number" name="duration" id="wi_duration" min="1" value="1" required
                        class="w-full px-5 py-3 bg-white border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none transition"
                        oninput="updateCalc()">
                </div>
                <div
                    class="col-span-2 bg-white p-4 rounded-xl border border-dashed border-indigo-200 text-sm text-indigo-800">
                    <p><i class="fa-regular fa-clock mr-1"></i> Giờ nhận phòng: <b id="wi_calc_in">Lập tức</b>
                    </p>
                    <p class="mt-1"><i class="fa-solid fa-person-walking-luggage mr-1"></i> Giờ trả dự kiến: <b
                            id="wi_calc_out">...</b></p>
                    <p class="mt-1"><i class="fa-solid fa-money-bill-wave mr-1"></i> Tạm tính: <b id="wi_calc_price"
                            class="text-lg">0đ</b></p>
                </div>
                <div class="col-span-2">
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Số
                        điện thoại liên hệ (Đại diện)</label>
                    <input type="tel" name="guest_phone"
                        class="w-full px-5 py-3 bg-white border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none transition"
                        placeholder="Nhập SĐT để nhận hóa đơn...">
                </div>
            </div>

            <div>
                <div class="flex justify-between items-center mb-3">
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest">Khách
                        lưu
                        trú</label>
                    <button type="button" onclick="addGuestRow()"
                        class="text-xs font-bold text-indigo-600 hover:underline"><i
                            class="fa-solid fa-plus mr-1"></i>Thêm khách</button>
                </div>
                <div id="guestList" class="space-y-3"></div>
            </div>

            <button type="submit"
                class="w-full px-4 py-4 bg-indigo-600 text-white rounded-2xl font-bold shadow-lg shadow-indigo-100 hover:bg-indigo-700 transition">
                <i class="fa-solid fa-key mr-2"></i>Xác nhận Giao Phòng
            </button>
        </form>
    </div>
</div>