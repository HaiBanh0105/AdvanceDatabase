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
                        <span class="text-slate-500" id="inv_base_price_label">Tiền phòng</span>
                        <span class="font-bold text-slate-800" id="inv_base_price"></span>
                    </div>
                    <div id="inv_deposit_box"
                        class="flex justify-between items-center border-b border-dashed border-slate-200 pb-2 bg-emerald-50/50 px-3 pt-2 mt-2 mb-2 rounded-lg">
                        <span class="text-emerald-700 font-bold"><i class="fa-solid fa-minus-circle mr-1"></i> Trừ tiền
                            cọc (Đã thanh toán trước)</span>
                        <span class="font-bold text-emerald-600" id="inv_deposit_amount"></span>
                    </div>
                    <div id="inv_overtime_box"
                        class="hidden flex-col border-b border-dashed border-slate-200 pb-3 pt-1 gap-2">
                        <div class="flex justify-between items-center">
                            <span class="text-slate-500 font-bold text-rose-600">Phụ phí phát sinh</span>
                            <span class="font-bold text-rose-600" id="inv_overtime_fee"></span>
                        </div>
                        <div class="text-[11px] text-slate-500 bg-rose-50 p-3 rounded-xl border border-rose-100">
                            <p class="font-bold text-slate-700 mb-1"><i
                                    class="fa-solid fa-circle-info text-rose-500 mr-1"></i> Chi tiết: Khách trả phòng lố
                                giờ <span id="inv_overtime_note" class="text-rose-600"></span></p>
                            <p class="italic text-[10px]">* Phụ phí được hệ thống tự động cộng dồn vào tổng hóa đơn.</p>
                        </div>
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

        <div class="p-6 bg-slate-50 border-t border-slate-100 flex justify-end gap-3 shrink-0">
            <button type="button" onclick="alert('Chức năng đang được phát triển!');"
                class="bg-indigo-600 text-white px-6 py-2.5 rounded-xl font-bold hover:bg-indigo-700 transition shadow-md shadow-indigo-200 flex items-center gap-2">
                <i class="fa-solid fa-print"></i> In Hóa Đơn
            </button>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const invOvertimeFee = document.getElementById('inv_overtime_fee');
    const invOvertimeBox = document.getElementById('inv_overtime_box');

    if (invOvertimeFee && invOvertimeBox) {
        const observer = new MutationObserver(() => {
            const feeText = invOvertimeFee.innerText.trim();
            // Ẩn cảnh báo đỏ nếu giá trị là trống, 0, hoặc 0đ
            if (feeText === '' || feeText === '0' || feeText === '0đ') {
                invOvertimeBox.classList.add('hidden');
                invOvertimeBox.classList.remove('flex');
            } else {
                invOvertimeBox.classList.remove('hidden');
                invOvertimeBox.classList.add('flex');
            }
        });
        observer.observe(invOvertimeFee, {
            childList: true,
            characterData: true,
            subtree: true
        });
    }
});

// Ghi đè hàm openInvoiceModal để xử lý hiển thị tiền cọc
function openInvoiceModal(bookingId) {
    document.getElementById('invoiceModal').classList.remove('hidden');
    document.getElementById('invoiceModal').classList.add('flex');
    document.getElementById('inv_loader').classList.remove('hidden');

    fetch(`../actions/process_admin_booking.php?action=get_invoice&booking_id=${bookingId}`)
        .then(res => res.json())
        .then(data => {
            document.getElementById('inv_loader').classList.add('hidden');
            if (data.error) return alert("Lỗi tải dữ liệu");

            document.getElementById('inv_id').innerText = '#BK-' + String(data.booking_id).padStart(4, '0');
            document.getElementById('inv_status').innerText = data.status.toUpperCase();
            document.getElementById('inv_in').innerText = data.check_in;
            document.getElementById('inv_out').innerText = data.actual_check_out || data.check_out;

            if (data.status === 'cancelled') {
                document.getElementById('inv_base_price_label').innerText = 'Phí phạt hủy đơn';
            } else {
                document.getElementById('inv_base_price_label').innerText = data.is_estimated ?
                    'Tiền phòng dự kiến' : 'Tiền phòng';
            }

            document.getElementById('inv_base_price').innerText = new Intl.NumberFormat('vi-VN').format(data
                .base_price) + 'đ';

            let depositAmount = parseFloat(data.deposit_amount) || 0;
            document.getElementById('inv_deposit_amount').innerText = '-' + new Intl.NumberFormat('vi-VN').format(
                depositAmount) + 'đ';

            if (data.overtime_fee > 0) {
                document.getElementById('inv_overtime_fee').innerText = '+' + new Intl.NumberFormat('vi-VN').format(
                    data.overtime_fee) + 'đ';
                document.getElementById('inv_overtime_note').innerText = data.overtime_hours;
            } else {
                document.getElementById('inv_overtime_fee').innerText = ''; // This triggers the observer to hide it
            }

            document.getElementById('inv_payment_status').innerText = data.payment_status === 'paid' ?
                'Đã Thanh Toán' : 'Chưa Thanh Toán';

            let finalAmount = parseFloat(data.amount_to_pay ?? data.final_total ?? data.total_price ?? 0);
            if (isNaN(finalAmount)) finalAmount = 0;
            document.getElementById('inv_total').innerText = new Intl.NumberFormat('vi-VN').format(finalAmount) +
                'đ';

            let guestsHtml = '';
            if (data.guests) {
                data.guests.forEach(g => {
                    const role = g.is_representative == 1 ?
                        '<span class="text-[9px] bg-indigo-100 text-indigo-600 px-1.5 py-0.5 rounded font-bold border border-indigo-200">ĐẠI DIỆN</span>' :
                        '<span class="text-[9px] text-slate-400 border border-slate-200 bg-white shadow-sm px-1.5 py-0.5 rounded font-bold">ĐI CÙNG</span>';
                    guestsHtml +=
                        `<tr><td class="px-4 py-2 font-bold text-slate-700">${g.full_name}</td><td class="px-4 py-2 text-slate-500">${g.cccd}</td><td class="px-4 py-2 text-center">${role}</td></tr>`;
                });
            }
            document.getElementById('inv_guests').innerHTML = guestsHtml;
        });
}
</script>