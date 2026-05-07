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

            <!-- LỊCH TRỐNG PHÒNG (TIMELINE) -->
            <div>
                <h4
                    class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-3 flex items-center gap-1.5">
                    <i class="fa-solid fa-timeline"></i> Lịch đặt trước của phòng này
                </h4>
                <div id="wi_timeline_container" class="flex gap-3 overflow-x-auto pb-2"
                    style="scrollbar-width: thin; scrollbar-color: #cbd5e1 transparent;">
                    <div class="text-xs text-slate-400 italic">Đang tải dữ liệu...</div>
                </div>
            </div>

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
                    <input type="tel" name="guest_phone" id="wi_guest_phone" oninput="checkDuplicatePhone()"
                        class="w-full px-5 py-3 bg-white border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none transition"
                        placeholder="Nhập SĐT để nhận hóa đơn...">
                    <p id="wi_phone_msg" class="text-xs font-bold mt-2 hidden"></p>
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

            <button type="submit" id="wi_submit_btn"
                class="w-full px-4 py-4 bg-indigo-600 text-white rounded-2xl font-bold shadow-lg shadow-indigo-100 hover:bg-indigo-700 transition">
                <i class="fa-solid fa-key mr-2"></i>Xác nhận Giao Phòng
            </button>
        </form>
    </div>
</div>

<script>
    function loadRoomTimeline(roomId) {
        const container = document.getElementById('wi_timeline_container');
        container.innerHTML =
            '<div class="text-xs text-slate-400 italic"><i class="fa-solid fa-spinner fa-spin mr-1"></i> Đang tải dữ liệu...</div>';

        fetch(`../actions/process_admin_booking.php?action=get_room_timeline&room_id=${roomId}`)
            .then(res => res.json())
            .then(data => {
                if (data.error || !data || data.length === 0) {
                    container.innerHTML =
                        '<div class="px-3 py-2.5 bg-emerald-50 text-emerald-600 rounded-xl text-xs font-bold border border-emerald-100 w-full"><i class="fa-solid fa-check-circle mr-1"></i> Phòng hiện đang hoàn toàn trống, không có lịch hẹn trước.</div>';
                    return;
                }

                let html = '';
                data.forEach(item => {
                    const checkIn = new Date(item.check_in);
                    const checkOut = new Date(item.check_out);

                    const inStr = checkIn.toLocaleTimeString('vi-VN', {
                        hour: '2-digit',
                        minute: '2-digit'
                    }) + ' ' + checkIn.toLocaleDateString('vi-VN', {
                        day: '2-digit',
                        month: '2-digit'
                    });
                    const outStr = checkOut.toLocaleTimeString('vi-VN', {
                        hour: '2-digit',
                        minute: '2-digit'
                    }) + ' ' + checkOut.toLocaleDateString('vi-VN', {
                        day: '2-digit',
                        month: '2-digit'
                    });

                    let statusColor = item.status === 'checked-in' ?
                        'bg-indigo-100 text-indigo-700 border-indigo-200' :
                        'bg-amber-100 text-amber-700 border-amber-200';
                    let statusIcon = item.status === 'checked-in' ? 'fa-bed' : 'fa-calendar-check';
                    let statusText = item.status === 'checked-in' ? 'Đang lưu trú' : 'Khách hẹn trước';

                    html += `
                <div class="flex-shrink-0 w-48 p-3 rounded-xl border ${statusColor} relative shadow-sm">
                    <p class="text-[10px] font-black uppercase mb-1.5 flex items-center gap-1.5"><i class="fa-solid ${statusIcon}"></i> ${statusText}</p>
                    <p class="text-xs font-bold truncate mb-2 text-slate-800" title="${item.full_name || 'Khách hàng'}">${item.full_name || 'Khách vãng lai'}</p>
                    <div class="text-[10px] font-medium space-y-1 text-slate-600">
                        <p class="bg-white/50 px-2 py-1 rounded"><span class="opacity-75">IN:</span> <b class="float-right">${inStr}</b></p>
                        <p class="bg-white/50 px-2 py-1 rounded"><span class="opacity-75">OUT:</span> <b class="float-right">${outStr}</b></p>
                    </div>
                </div>`;
                });
                container.innerHTML = html;
            })
            .catch(err => {
                container.innerHTML =
                    '<div class="text-xs text-rose-500 italic"><i class="fa-solid fa-circle-exclamation mr-1"></i> Không thể tải dữ liệu lịch phòng</div>';
            });
    }

    function checkDuplicatePhone() {
        const phoneInput = document.getElementById('wi_guest_phone');
        const phoneMsg = document.getElementById('wi_phone_msg');
        const submitBtn = document.getElementById('wi_submit_btn');
        const phone = phoneInput.value.trim();

        const walkinForm = document.querySelector('#walkinModal form');
        const repRadio = walkinForm.querySelector('input[name="rep_index"]:checked') || walkinForm.querySelector(
            'input[name="rep_index"]');
        const repIndex = repRadio ? repRadio.value : 0;
        const cccdInput = walkinForm.querySelector(`input[name="guests[${repIndex}][cccd]"]`);
        const cccd = cccdInput ? cccdInput.value.trim() : '';

        if (!phone) {
            phoneMsg.classList.add('hidden');
            submitBtn.disabled = false;
            submitBtn.classList.remove('opacity-50', 'cursor-not-allowed');
            return;
        }

        fetch(`../actions/process_admin_booking.php?action=check_phone&phone=${phone}&cccd=${cccd}`)
            .then(res => res.json())
            .then(data => {
                phoneMsg.classList.remove('hidden');
                if (data.status === 'duplicate') {
                    phoneMsg.innerHTML = `<i class="fa-solid fa-circle-xmark mr-1"></i> ${data.message}`;
                    phoneMsg.className = 'text-xs font-bold mt-2 text-rose-500';
                    submitBtn.disabled = true;
                    submitBtn.classList.add('opacity-50', 'cursor-not-allowed');
                } else {
                    phoneMsg.innerHTML = `<i class="fa-solid fa-circle-check mr-1"></i> Số điện thoại hợp lệ`;
                    phoneMsg.className = 'text-xs font-bold mt-2 text-emerald-600';
                    submitBtn.disabled = false;
                    submitBtn.classList.remove('opacity-50', 'cursor-not-allowed');
                }
            })
            .catch(err => console.error(err));
    }

    // Ghi đè thuộc tính 'value' của thẻ input hidden để tự động kích hoạt loadRoomTimeline mỗi khi JS đổ room_id vào
    document.addEventListener('DOMContentLoaded', () => {
        const walkinForm = document.querySelector('#walkinModal form');
        if (walkinForm) {
            walkinForm.addEventListener('submit', function(e) {
                if (e.defaultPrevented) return; // Nếu form bị chặn bởi validate gốc thì bỏ qua

                const phone = this.querySelector('input[name="guest_phone"]')?.value.trim();
                const repRadio = this.querySelector('input[name="rep_index"]:checked') || this
                    .querySelector('input[name="rep_index"]');
                const repIndex = repRadio ? repRadio.value : 0;
                const cccd = this.querySelector(`input[name="guests[${repIndex}][cccd]"]`)?.value.trim();

                if (this.querySelector('input[name="confirm_phone"]')) return; // Đã xác nhận rồi

                if (phone && cccd) {
                    e.preventDefault(); // Tạm dừng submit để check trùng
                    fetch(`../actions/process_admin_booking.php?action=check_cccd&cccd=${cccd}`)
                        .then(res => res.json())
                        .then(data => {
                            if (data.status === 'found' && data.phone && data.phone !== phone) {
                                if (confirm(
                                        `Khách hàng này đã có số điện thoại trong hệ thống: ${data.phone}.\nBạn có chắc chắn muốn ghi đè thành số mới: ${phone} không?`
                                    )) {
                                    submitWithConfirm(this);
                                }
                            } else {
                                submitWithConfirm(this);
                            }
                        }).catch(() => this.submit());
                }
            });

            function submitWithConfirm(form) {
                const hidden = document.createElement('input');
                hidden.type = 'hidden';
                hidden.name = 'confirm_phone';
                hidden.value = '1';
                form.appendChild(hidden);
                form.submit();
            }

            // Kiểm tra lại điện thoại nếu Lễ tân thay đổi CCCD hoặc chọn người đại diện khác
            walkinForm.addEventListener('change', function(e) {
                if (e.target.name && (e.target.name.includes('[cccd]') || e.target.name === 'rep_index')) {
                    if (document.getElementById('wi_guest_phone').value.trim() !== '') {
                        checkDuplicatePhone();
                    }
                }
            });
        }

        const roomIdInput = document.getElementById('wi_room_id');
        if (roomIdInput) {
            const originalSet = Object.getOwnPropertyDescriptor(window.HTMLInputElement.prototype, 'value').set;
            Object.defineProperty(roomIdInput, 'value', {
                set(val) {
                    originalSet.call(this, val);
                    if (val) loadRoomTimeline(val);
                },
                get() {
                    return Object.getOwnPropertyDescriptor(window.HTMLInputElement.prototype, 'value')
                        .get.call(this);
                }
            });
        }
    });
</script>