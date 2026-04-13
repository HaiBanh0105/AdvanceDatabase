<div id="invoiceModal"
    class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-[70] hidden items-center justify-center p-4">
    <div
        class="bg-white rounded-[2.5rem] w-full max-w-2xl shadow-2xl overflow-hidden animate-in zoom-in duration-200 flex flex-col max-h-[90vh]">
        <div class="p-6 border-b border-slate-100 flex justify-between items-center bg-indigo-50">
            <h3 class="font-bold text-lg text-indigo-800 uppercase tracking-tight"><i
                    class="fa-solid fa-file-invoice mr-2"></i>Chi tiết Đơn Đặt Phòng</h3>
            <button onclick="toggleModal('invoiceModal')" class="text-slate-400 hover:text-slate-600"><i
                    class="fa-solid fa-xmark text-lg"></i></button>
        </div>
        <div class="p-8 overflow-y-auto text-sm space-y-6 relative">
            <div id="inv_loader" class="absolute inset-0 bg-white/80 flex items-center justify-center hidden z-10">
                <i class="fa-solid fa-circle-notch fa-spin text-3xl text-indigo-600"></i>
            </div>
            <div class="grid grid-cols-2 gap-6 bg-slate-50 p-6 rounded-2xl border border-slate-100">
                <div>
                    <p class="text-[10px] font-black text-slate-400 uppercase mb-1">Mã Đơn</p>
                    <p class="font-black text-indigo-600 text-lg" id="inv_id"></p>
                </div>
                <div class="text-right">
                    <p class="text-[10px] font-black text-slate-400 uppercase mb-1">Trạng thái</p>
                    <p class="font-bold text-slate-800" id="inv_status"></p>
                </div>
            </div>

            <!-- BỔ SUNG CHI TIẾT THANH TOÁN -->
            <div>
                <h4 class="text-xs font-black text-slate-400 uppercase tracking-widest mb-3">Thông tin Lưu trú &
                    Thanh toán</h4>
                <div class="bg-white border border-slate-200 rounded-xl p-5 space-y-3">
                    <div class="flex justify-between items-center border-b border-dashed border-slate-200 pb-2">
                        <span class="text-slate-500">Giờ nhận phòng (IN)</span>
                        <span class="font-bold text-slate-800" id="inv_in"></span>
                    </div>
                    <div class="flex justify-between items-center border-b border-dashed border-slate-200 pb-2">
                        <span class="text-slate-500">Giờ trả phòng (OUT)</span>
                        <span class="font-bold text-slate-800" id="inv_out"></span>
                    </div>
                    <div class="flex justify-between items-center border-b border-dashed border-slate-200 pb-2">
                        <span class="text-slate-500" id="inv_base_price_label">Tiền phòng dự kiến</span>
                        <span class="font-bold text-slate-800" id="inv_base_price"></span>
                    </div>
                    <div class="flex justify-between items-center border-b border-dashed border-slate-200 pb-2">
                        <span class="text-slate-500">Phụ thu phát sinh <span id="inv_overtime_note"
                                class="text-[10px] text-slate-400 font-normal"></span></span>
                        <span class="font-bold text-rose-600" id="inv_overtime_fee"></span>
                    </div>
                    <div class="flex justify-between items-center border-b border-dashed border-slate-200 pb-2">
                        <span class="text-slate-500">Trạng thái thanh toán</span>
                        <span class="font-bold uppercase text-[10px] px-2 py-1 rounded" id="inv_payment_status"></span>
                    </div>
                    <div class="flex justify-between items-center pt-2">
                        <span class="text-xs font-black text-slate-400 uppercase tracking-widest">TỔNG CỘNG</span>
                        <span class="text-2xl font-black text-indigo-600" id="inv_total"></span>
                    </div>
                </div>
            </div>

            <div>
                <h4 class="text-xs font-black text-slate-400 uppercase tracking-widest mb-3">Danh sách Khách lưu trú
                </h4>
                <div class="bg-white border border-slate-200 rounded-xl overflow-hidden">
                    <table class="w-full text-left">
                        <thead class="bg-slate-50 border-b border-slate-100">
                            <tr class="text-[10px] text-slate-500 uppercase tracking-wider font-bold">
                                <th class="px-4 py-2">Họ Tên</th>
                                <th class="px-4 py-2">CCCD</th>
                                <th class="px-4 py-2 text-center">Vai trò</th>
                            </tr>
                        </thead>
                        <tbody id="inv_guests" class="divide-y divide-slate-100"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>