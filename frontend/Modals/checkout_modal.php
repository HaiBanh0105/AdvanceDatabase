<div id="checkoutModal"
    class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-[60] hidden items-center justify-center p-4">
    <div class="bg-white rounded-[2.5rem] w-full max-w-lg shadow-2xl overflow-hidden animate-in zoom-in duration-200">
        <div class="p-6 border-b border-slate-100 flex justify-between items-center bg-emerald-50">
            <h3 class="font-bold text-lg text-emerald-800 uppercase tracking-tight">Hóa Đơn Thanh Toán</h3>
            <button onclick="toggleModal('checkoutModal')" class="text-slate-400 hover:text-slate-600"><i
                    class="fa-solid fa-xmark text-lg"></i></button>
        </div>
        <form action="../actions/process_admin_booking.php" method="POST" class="p-8 space-y-4 text-sm">
            <input type="hidden" name="action" value="checkout">
            <input type="hidden" name="booking_id" id="co_booking_id">
            <input type="hidden" name="total_paid" id="co_total_paid">

            <div class="bg-slate-50 p-4 rounded-xl border border-slate-200 mb-4">
                <p class="text-xs text-slate-500 mb-1">Khách hàng đại diện</p>
                <p class="font-bold text-slate-800 text-base" id="co_customer"></p>
            </div>

            <div class="flex justify-between items-center pb-3 border-b border-dashed border-slate-200">
                <span class="text-slate-500">Giờ vào thực tế</span>
                <span class="font-bold text-slate-800" id="co_in"></span>
            </div>
            <div class="flex justify-between items-center pb-3 border-b border-dashed border-slate-200">
                <span class="text-slate-500">Giờ ra thực tế</span>
                <span class="font-bold text-slate-800" id="co_out"></span>
            </div>
            <div class="flex justify-between items-center pt-2">
                <span class="text-slate-500">Tiền phòng (Dự kiến)</span>
                <span class="font-bold text-slate-800" id="co_base_price"></span>
            </div>
            <div id="co_overtime_box" class="flex justify-between items-center text-rose-600 hidden">
                <span>Phụ thu lố giờ (<span id="co_overtime_hrs"></span> tiếng)</span>
                <span class="font-bold" id="co_overtime_fee"></span>
            </div>

            <div class="flex justify-between items-center pt-4 border-t border-slate-200 mt-4">
                <span class="text-xs font-black text-slate-400 uppercase">TỔNG THANH TOÁN</span>
                <span class="text-3xl font-black text-indigo-600" id="co_final_total"></span>
            </div>

            <button type="submit"
                class="w-full mt-6 px-4 py-4 bg-indigo-600 text-white rounded-2xl font-bold shadow-lg shadow-indigo-100 hover:bg-indigo-700 transition">
                <i class="fa-solid fa-check-double mr-2"></i>Xác nhận Đã Thu Tiền
            </button>
        </form>
    </div>
</div>