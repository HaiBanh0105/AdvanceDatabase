<!-- Modal Xem Chi tiết Đơn đặt phòng (Customer) -->
<div id="viewBookingHistoryModal"
    class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-[100] hidden items-center justify-center p-4">
    <div class="bg-white rounded-[2rem] w-full max-w-lg shadow-2xl overflow-hidden animate-in zoom-in duration-200">
        <div class="p-6 border-b border-slate-100 flex justify-between items-center bg-slate-50">
            <div>
                <h3 class="font-black text-lg text-slate-800 tracking-tight">Chi tiết Đặt phòng</h3>
                <p class="text-xs text-indigo-600 font-bold mt-1" id="vh_booking_id"></p>
            </div>
            <button onclick="closeViewHistoryModal()" class="text-slate-400 hover:text-slate-600"><i
                    class="fa-solid fa-xmark text-lg"></i></button>
        </div>

        <div class="p-6 space-y-4 max-h-[60vh] overflow-y-auto">
            <div class="flex justify-between items-center border-b border-dashed border-slate-200 pb-3">
                <span class="text-sm font-medium text-slate-500">Trạng thái</span>
                <span class="text-sm font-bold" id="vh_status"></span>
            </div>
            <div class="flex justify-between items-center border-b border-dashed border-slate-200 pb-3">
                <span class="text-sm font-medium text-slate-500">Phòng</span>
                <span class="text-sm font-bold text-slate-800" id="vh_room"></span>
            </div>
            <div class="flex justify-between items-center border-b border-dashed border-slate-200 pb-3">
                <span class="text-sm font-medium text-slate-500">Nhận phòng (Dự kiến)</span>
                <span class="text-sm font-bold text-indigo-600" id="vh_checkin"></span>
            </div>
            <div class="flex justify-between items-center border-b border-dashed border-slate-200 pb-3">
                <span class="text-sm font-medium text-slate-500">Trả phòng (Dự kiến)</span>
                <span class="text-sm font-bold text-rose-600" id="vh_checkout"></span>
            </div>
            <div class="border-b border-dashed border-slate-200 pb-3">
                <span class="text-sm font-medium text-slate-500 mb-2 block">Khách lưu trú</span>
                <div class="bg-slate-50 rounded-xl p-3 border border-slate-100">
                    <table class="w-full text-sm text-left">
                        <tbody id="vh_guests" class="divide-y divide-slate-200/50">
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="bg-indigo-50 p-4 rounded-xl flex justify-between items-center mt-2">
                <span class="text-sm font-bold text-indigo-800 uppercase tracking-wider">Tổng tiền</span>
                <span class="text-2xl font-black text-indigo-600" id="vh_total_price"></span>
            </div>
        </div>

        <div class="p-6 bg-slate-50 border-t border-slate-100 flex justify-end">
            <button onclick="closeViewHistoryModal()"
                class="bg-slate-800 text-white px-6 py-2.5 rounded-xl font-bold hover:bg-slate-900 transition active:scale-95 shadow-md shadow-slate-200">Đóng</button>
        </div>
    </div>
</div>

<script>
    function openViewHistoryModal(bookingId) {
        document.getElementById('viewBookingHistoryModal').classList.remove('hidden');
        document.getElementById('viewBookingHistoryModal').classList.add('flex');

        document.getElementById('vh_booking_id').innerText = 'Đang tải...';
        document.getElementById('vh_status').innerText = '...';
        document.getElementById('vh_room').innerText = '...';
        document.getElementById('vh_checkin').innerText = '...';
        document.getElementById('vh_checkout').innerText = '...';
        document.getElementById('vh_guests').innerHTML =
            '<tr><td class="py-2 text-center text-slate-400"><i class="fa-solid fa-spinner fa-spin"></i> Đang tải...</td></tr>';
        document.getElementById('vh_total_price').innerText = '...';

        fetch(`../actions/process_booking.php?action=get_details&booking_id=${bookingId}`)
            .then(res => res.json())
            .then(data => {
                if (data.error) {
                    alert('Không thể tải dữ liệu!');
                    closeViewHistoryModal();
                    return;
                }

                document.getElementById('vh_booking_id').innerText = '#BK-' + String(data.booking_id).padStart(4, '0');

                let statusVi = '';
                let statusClass = '';
                switch (data.status) {
                    case 'pending':
                        statusVi = 'Chờ xử lý';
                        statusClass = 'text-amber-600 bg-amber-100';
                        break;
                    case 'confirmed':
                        statusVi = 'Đã xác nhận';
                        statusClass = 'text-blue-600 bg-blue-100';
                        break;
                    case 'checked-in':
                        statusVi = 'Đang ở';
                        statusClass = 'text-indigo-600 bg-indigo-100';
                        break;
                    case 'completed':
                        statusVi = 'Đã hoàn thành';
                        statusClass = 'text-emerald-600 bg-emerald-100';
                        break;
                    case 'cancelled':
                        statusVi = 'Đã hủy';
                        statusClass = 'text-red-600 bg-red-100';
                        break;
                    default:
                        statusVi = data.status;
                        statusClass = 'text-slate-600 bg-slate-100';
                }
                document.getElementById('vh_status').innerText = statusVi.toUpperCase();
                document.getElementById('vh_status').className =
                    `text-[10px] font-bold uppercase px-2 py-1 rounded ${statusClass}`;

                document.getElementById('vh_room').innerText = data.room_number ? `Phòng ${data.room_number}` :
                    'Đang xếp phòng';
                document.getElementById('vh_checkin').innerText = data.check_in;
                document.getElementById('vh_checkout').innerText = data.check_out;

                const tbody = document.getElementById('vh_guests');
                tbody.innerHTML = '';
                if (data.guests && data.guests.length > 0) {
                    data.guests.forEach(g => {
                        const role = g.is_representative == 1 ?
                            '<span class="text-[9px] bg-indigo-100 text-indigo-600 px-1.5 py-0.5 rounded font-bold border border-indigo-200">ĐẠI DIỆN</span>' :
                            '<span class="text-[9px] text-slate-400 border border-slate-200 bg-white shadow-sm px-1.5 py-0.5 rounded font-bold">ĐI CÙNG</span>';
                        tbody.innerHTML +=
                            `<tr><td class="py-2.5 font-bold text-slate-700">${g.full_name}</td><td class="py-2.5 text-right">${role}</td></tr>`;
                    });
                } else {
                    tbody.innerHTML =
                        '<tr><td class="py-2 text-slate-500 italic">Không có dữ liệu khách lưu trú</td></tr>';
                }

                document.getElementById('vh_total_price').innerText = new Intl.NumberFormat('vi-VN').format(data
                    .total_price) + 'đ';
            })
            .catch(err => {
                console.error(err);
                alert('Lỗi kết nối mạng!');
                closeViewHistoryModal();
            });
    }

    function closeViewHistoryModal() {
        document.getElementById('viewBookingHistoryModal').classList.add('hidden');
        document.getElementById('viewBookingHistoryModal').classList.remove('flex');
    }
</script>