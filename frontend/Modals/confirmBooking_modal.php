<div id="confirmBookingModal"
    class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-[70] hidden items-center justify-center p-4">
    <div
        class="bg-white rounded-[2rem] w-full max-w-sm shadow-2xl overflow-hidden animate-in slide-in-from-bottom-10 duration-300">
        <div class="p-6 border-b border-slate-100 flex justify-between items-center bg-slate-50">
            <div>
                <h3 class="font-black text-lg text-slate-800 tracking-tight">Xác nhận Đặt phòng</h3>
                <p class="text-xs text-emerald-600 font-bold mt-1">Bước 2/2: Hoàn tất</p>
            </div>
            <button onclick="document.getElementById('confirmBookingModal').classList.replace('flex', 'hidden')"
                class="text-slate-400 hover:text-slate-600"><i class="fa-solid fa-arrow-left text-lg"></i></button>
        </div>

        <div class="p-6 space-y-4">
            <div class="flex justify-between items-center border-b border-dashed border-slate-200 pb-3">
                <span class="text-sm font-medium text-slate-500">Hạng phòng</span>
                <span class="text-sm font-bold text-slate-800" id="conf_room_name"></span>
            </div>
            <div class="flex justify-between items-center border-b border-dashed border-slate-200 pb-3">
                <span class="text-sm font-medium text-slate-500">Nhận phòng</span>
                <span class="text-sm font-bold text-indigo-600">14:00,
                    <?= date('d/m/Y', strtotime($search_in ?: 'now')) ?></span>
            </div>
            <div class="flex justify-between items-center border-b border-dashed border-slate-200 pb-3">
                <span class="text-sm font-medium text-slate-500">Trả phòng</span>
                <span class="text-sm font-bold text-rose-600">12:00,
                    <?= date('d/m/Y', strtotime($search_out ?: 'now')) ?></span>
            </div>
            <div class="flex justify-between items-center border-b border-dashed border-slate-200 pb-3">
                <span class="text-sm font-medium text-slate-500">Khách lưu trú</span>
                <span class="text-sm font-bold text-slate-800"><?= $search_guests ?> Người</span>
            </div>

            <!-- Khung nhập mã giảm giá -->
            <div class="border-b border-dashed border-slate-200 pb-3">
                <div class="flex gap-2">
                    <input type="text" id="promo_input" placeholder="Mã khuyến mãi (Nếu có)"
                        class="w-full px-4 py-2 bg-white border border-slate-200 rounded-xl text-sm font-bold uppercase focus:ring-2 focus:ring-indigo-500 outline-none">
                    <button type="button" onclick="applyPromoCode()" id="btnApplyPromo"
                        class="bg-slate-800 text-white px-4 py-2 rounded-xl text-sm font-bold hover:bg-slate-900 transition whitespace-nowrap">Áp
                        dụng</button>
                </div>
                <p id="promo_msg" class="text-xs font-bold mt-2 hidden"></p>
            </div>
            <div class="flex justify-between items-center border-b border-dashed border-slate-200 pb-3 mt-3 hidden"
                id="discount_row">
                <span class="text-sm font-medium text-slate-500">Giảm giá (<span
                        id="discount_percent_label"></span>)</span>
                <span class="text-sm font-bold text-emerald-600" id="discount_amount_label"></span>
            </div>

            <div class="bg-indigo-50 p-4 rounded-xl flex justify-between items-center mt-2">
                <span class="text-sm font-bold text-indigo-800 uppercase tracking-wider">Tổng tiền</span>
                <span class="text-2xl font-black text-indigo-600" id="conf_total_price"></span>
            </div>
        </div>

        <div class="p-6 pt-0">
            <button type="button" id="btnSubmitBooking" onclick="submitFinalBooking()"
                class="w-full bg-indigo-600 text-white py-4 rounded-2xl font-bold shadow-lg shadow-indigo-200 hover:bg-indigo-700 transition flex justify-center items-center gap-2 active:scale-95">
                <span id="btnSubmitText">Gửi Yêu Cầu Đặt Phòng</span>
                <i id="btnSubmitSpinner" class="fa-solid fa-spinner fa-spin hidden"></i>
            </button>
        </div>
    </div>
</div>