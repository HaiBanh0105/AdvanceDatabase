<div id="viewBookingModal"
    class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 hidden items-center justify-center p-4">
    <div class="bg-white rounded-[2.5rem] w-full max-w-md shadow-2xl overflow-hidden animate-in zoom-in duration-200">
        <div class="p-6 border-b border-slate-100 flex justify-between items-center bg-rose-50">
            <h3 class="font-bold text-lg text-rose-800 uppercase tracking-tight"><i class="fa-solid fa-bed mr-2"></i>Chi
                tiết đang lưu trú</h3>
            <button onclick="toggleModal('viewBookingModal')" class="text-slate-400 hover:text-slate-600"><i
                    class="fa-solid fa-xmark text-lg"></i></button>
        </div>
        <div class="p-8 space-y-4 text-sm relative">
            <div id="v_loader" class="absolute inset-0 bg-white/80 flex items-center justify-center hidden z-10">
                <i class="fa-solid fa-circle-notch fa-spin text-3xl text-indigo-600"></i>
            </div>

            <div class="flex justify-between items-center pb-4 border-b border-slate-100">
                <span class="text-[10px] font-black text-slate-400 uppercase">Mã Đơn</span>
                <span class="font-black text-indigo-600" id="v_booking_id"></span>
            </div>
            <div class="flex justify-between items-center pb-4 border-b border-slate-100">
                <span class="text-[10px] font-black text-slate-400 uppercase">Phòng</span>
                <span class="font-bold text-slate-800" id="v_room"></span>
            </div>
            <div class="flex justify-between items-center pb-4 border-b border-slate-100">
                <span class="text-[10px] font-black text-slate-400 uppercase">Ngày nhận (IN)</span>
                <span class="font-medium text-slate-600" id="v_checkin"></span>
            </div>
            <div class="flex justify-between items-center pb-4 border-b border-slate-100">
                <span class="text-[10px] font-black text-slate-400 uppercase">Ngày trả (OUT)</span>
                <span class="font-medium text-slate-600" id="v_checkout"></span>
            </div>
            <div class="flex justify-between items-center pb-2">
                <span class="text-[10px] font-black text-slate-400 uppercase">Tạm tính (Dự kiến)</span>
                <span class="font-black text-lg text-emerald-600" id="v_price"></span>
            </div>

            <button onclick="proceedToCheckout()"
                class="w-full mt-4 px-4 py-4 bg-emerald-500 text-white rounded-2xl font-bold shadow-lg shadow-emerald-200 hover:bg-emerald-600 transition">
                <i class="fa-solid fa-money-bill-wave mr-2"></i>Thanh toán & Trả phòng
            </button>
        </div>
    </div>
</div>