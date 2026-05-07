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
            <div class="flex justify-between items-center pt-2 pb-3 border-b border-dashed border-slate-200">
                <span class="text-slate-500">Tiền phòng</span>
                <span class="font-bold text-slate-800" id="co_base_price"></span>
            </div>
            <div id="co_deposit_box"
                class="justify-between items-center pt-2 pb-2 border-b border-dashed border-slate-200 bg-emerald-50/50 px-3 rounded-lg mt-2 mb-2 flex">
                <span class="text-emerald-700 font-bold"><i class="fa-solid fa-minus-circle mr-1"></i> Trừ tiền cọc
                    (Đã thanh toán trước)</span>
                <span class="font-bold text-emerald-600" id="co_deposit_amount"></span>
            </div>
            <div id="co_overtime_box" class="hidden flex-col gap-2 pt-3">
                <div class="flex justify-between items-center text-rose-600">
                    <span class="font-bold">Phụ phí phát sinh</span>
                    <span class="font-bold text-lg" id="co_overtime_fee"></span>
                </div>
                <div class="text-[11px] text-slate-500 bg-rose-50 p-3 rounded-xl border border-rose-100">
                    <p class="font-bold text-slate-700 mb-1"><i class="fa-solid fa-circle-info text-rose-500 mr-1"></i>
                        Chi tiết: Khách trả phòng lố <span id="co_overtime_hrs" class="text-rose-600"></span> tiếng.</p>
                    <p class="italic text-[10px]">* Phụ phí được hệ thống tính tự động dựa trên đơn giá theo giờ của
                        phòng.</p>
                </div>
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

<script>
    // Ghi đè hàm proceedToCheckout để xử lý việc hiển thị tiền cọc
    function proceedToCheckout() {
        const bookingId = document.getElementById('v_booking_id').innerText.replace('#BK-', '');

        // Đóng modal view
        toggleModal('viewBookingModal');

        // Mở modal checkout
        document.getElementById('checkoutModal').classList.remove('hidden');
        document.getElementById('checkoutModal').classList.add('flex');

        document.getElementById('co_booking_id').value = parseInt(bookingId);

        fetch(`../actions/process_admin_booking.php?action=calculate_bill&booking_id=${parseInt(bookingId)}`)
            .then(res => res.json())
            .then(data => {
                if (data.error) return alert("Lỗi tải dữ liệu");

                document.getElementById('co_customer').innerText = data.guest_name + (data.guest_phone ?
                    ` - ${data.guest_phone}` : '');
                document.getElementById('co_in').innerText = data.actual_check_in || data.check_in;
                document.getElementById('co_out').innerText = data.actual_checkout;

                document.getElementById('co_base_price').innerText = new Intl.NumberFormat('vi-VN').format(data
                    .base_price) + 'đ';

                let depositAmount = parseFloat(data.deposit_amount) || 0;
                document.getElementById('co_deposit_amount').innerText = '-' + new Intl.NumberFormat('vi-VN').format(depositAmount) + 'đ';

                if (data.overtime_fee > 0) {
                    document.getElementById('co_overtime_box').classList.remove('hidden');
                    document.getElementById('co_overtime_box').classList.add('flex');
                    document.getElementById('co_overtime_fee').innerText = '+' + new Intl.NumberFormat('vi-VN').format(
                        data.overtime_fee) + 'đ';
                    document.getElementById('co_overtime_hrs').innerText = data.overtime_hours;
                } else {
                    document.getElementById('co_overtime_box').classList.add('hidden');
                    document.getElementById('co_overtime_box').classList.remove('flex');
                }

                let finalTotal = parseFloat(data.final_total ?? data.total_price ?? 0);
                if (isNaN(finalTotal)) finalTotal = 0;

                document.getElementById('co_final_total').innerText = new Intl.NumberFormat('vi-VN').format(finalTotal) + 'đ';
                document.getElementById('co_total_paid').value = finalTotal;
            });
    }
</script>