let currentRoomData = {};
let currentBookingData = null;

function filterBookings() {
    const searchName = document.getElementById('searchName').value.toLowerCase().trim();
    const searchCCCD = document.getElementById('searchCCCD').value.toLowerCase().trim();
    const rows = document.querySelectorAll('#bookingTableBody tr');

    rows.forEach(row => {
        if (row.cells.length < 2) return; // Bỏ qua dòng thông báo trống
        
        // Ô cells[1] chứa cột "Khách hàng" (Bao gồm Tên, SĐT, CCCD)
        const customerData = row.cells[1].innerText.toLowerCase();
        
        const matchName = searchName === '' || customerData.includes(searchName);
        const matchCCCD = searchCCCD === '' || customerData.includes(searchCCCD);

        row.style.display = (matchName && matchCCCD) ? '' : 'none';
    });
}

function toggleModal(modalId) {
    document.getElementById(modalId).classList.toggle('hidden');
    document.getElementById(modalId).classList.toggle('flex');
}

function switchTab(tabId, btnElement) {
    document.querySelectorAll('.tab-content').forEach(el => el.classList.add('hidden'));
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.classList.remove('text-indigo-600', 'border-b-2', 'border-indigo-600', 'font-bold');
        btn.classList.add('text-slate-400', 'hover:text-indigo-600');
    });
    document.getElementById(tabId).classList.remove('hidden');
    btnElement.classList.remove('text-slate-400', 'hover:text-indigo-600');
    btnElement.classList.add('text-indigo-600', 'border-b-2', 'border-indigo-600', 'font-bold');
}

function openWalkinModal(roomId, roomNumber, typeName, priceHr, priceDay, capacity) {
    currentRoomData = { id: roomId, num: roomNumber, type: typeName, p_hr: priceHr, p_day: priceDay, capacity: capacity };

    document.getElementById('wi_room_id').value = roomId;
    document.getElementById('wi_room_number').innerText = roomNumber;
    document.getElementById('wi_price_hr').innerText = new Intl.NumberFormat('vi-VN').format(priceHr) + 'đ/h';
    document.getElementById('wi_price_day').innerText = new Intl.NumberFormat('vi-VN').format(priceDay) + 'đ/ngày';

    document.getElementById('guestList').innerHTML = '';
    document.getElementById('wi_duration').value = 1;
    document.querySelector('input[name="rental_type"][value="daily"]').checked = true;

    addGuestRow(true);
    updateCalc();
    toggleModal('walkinModal');
}

function addGuestRow(isFirst = false) {
    const list = document.getElementById('guestList');
    if (list.children.length >= currentRoomData.capacity) {
        alert('Phòng này có giới hạn sức chứa tối đa là ' + currentRoomData.capacity + ' người!');
        return;
    }

    const index = list.children.length;
    const checked = isFirst ? 'checked' : '';

    const html = `
        <div class="flex items-center gap-3 p-3 bg-slate-50 border border-slate-200 rounded-xl relative" id="g_row_${index}">
            <label class="flex items-center gap-2 text-sm text-indigo-600 font-bold cursor-pointer px-2" title="Chọn làm người đại diện phòng">
                <input type="radio" name="rep_index" value="${index}" ${checked} class="focus:ring-indigo-500 w-4 h-4 text-indigo-600">
                <span>Đại diện</span>
            </label>
            <input type="text" name="guests[${index}][name]" placeholder="Họ và tên khách" required class="flex-1 px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm outline-none focus:border-indigo-500">
            <input type="text" name="guests[${index}][cccd]" onchange="checkCCCD(this, ${index})" placeholder="Số CCCD" required class="w-1/3 px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm outline-none focus:border-indigo-500">
            ${!isFirst ? `<button type="button" onclick="document.getElementById('g_row_${index}').remove()" class="text-slate-300 hover:text-red-500 p-2"><i class="fa-solid fa-trash"></i></button>` : '<div class="w-8"></div>'}
        </div>
    `;
    list.insertAdjacentHTML('beforeend', html);
}

function checkCCCD(input, index) {
    const cccd = input.value.trim();
    const nameInput = document.querySelector(`input[name="guests[${index}][name]"]`);

    // Nếu CCCD quá ngắn, mở khóa lại ô nhập Tên
    if (cccd.length <= 5) {
        nameInput.readOnly = false;
        nameInput.classList.remove('bg-indigo-50', 'text-indigo-700', 'font-bold');
        return;
    }

    fetch(`../actions/process_admin_booking.php?action=check_cccd&cccd=${cccd}`)
        .then(res => res.json())
        .then(data => {
            if (data.status === 'found') {
                const currentName = nameInput.value.trim();
                if (currentName !== '' && currentName.toLowerCase() !== data.name.toLowerCase()) {
                    alert(`Cảnh báo: CCCD này đã thuộc về khách "${data.name}". Hệ thống tự động cập nhật tên chuẩn và khóa ô nhập.`);
                }
                nameInput.value = data.name;
                nameInput.classList.add('bg-indigo-50', 'text-indigo-700', 'font-bold');
                nameInput.readOnly = true; // Khóa không cho sửa tên
            } else {
                nameInput.readOnly = false;
                nameInput.classList.remove('bg-indigo-50', 'text-indigo-700', 'font-bold');
            }
        });
}

function validateWalkinForm(e) {
    const cccdInputs = document.querySelectorAll('#guestList input[name$="[cccd]"]');
    const cccdValues = [];

    for (let input of cccdInputs) {
        const val = input.value.trim();
        if (val) {
            if (cccdValues.includes(val)) {
                alert('Lỗi: Số CCCD "' + val + '" bị trùng lặp trong danh sách khách!');
                e.preventDefault();
                return false;
            }
            cccdValues.push(val);
        }
    }
    return true;
}

function updateCalc() {
    const type     = document.querySelector('input[name="rental_type"]:checked').value;
    const duration = parseInt(document.getElementById('wi_duration').value) || 1;

    let outStr = '';
    let price  = 0;

    const tzOptions = { timeZone: 'Asia/Ho_Chi_Minh' };
    const now       = new Date();

    if (type === 'hourly') {
        price = duration * currentRoomData.p_hr;
        const outDate = new Date(now.getTime() + duration * 60 * 60 * 1000);
        outStr = new Intl.DateTimeFormat('vi-VN', {
            ...tzOptions, hour: '2-digit', minute: '2-digit', day: '2-digit', month: '2-digit'
        }).format(outDate);
    } else {
        price = duration * currentRoomData.p_day;
        const outDate = new Date(now.getTime() + duration * 24 * 60 * 60 * 1000);
        const dateStr = new Intl.DateTimeFormat('vi-VN', {
            ...tzOptions, day: '2-digit', month: '2-digit', year: 'numeric'
        }).format(outDate);
        outStr = '12:00 Trưa, ngày ' + dateStr;
    }

    document.getElementById('wi_calc_out').innerText   = outStr;
    document.getElementById('wi_calc_price').innerText = new Intl.NumberFormat('vi-VN').format(price) + 'đ';
}

function openViewBookingModal(bookingData) {
    document.getElementById('v_loader').classList.remove('hidden');
    toggleModal('viewBookingModal');

    fetch(`../actions/process_admin_booking.php?action=calculate_bill&booking_id=${bookingData.booking_id}`)
        .then(res => res.text()) // Lấy dữ liệu Text thô trước
        .then(text => {
            try {
                return JSON.parse(text); // Cố gắng chuyển sang JSON
            } catch (e) {
                console.error("Lỗi Parsing JSON. Dữ liệu server trả về bị hỏng:", text);
                throw new Error("Invalid JSON");
            }
        })
        .then(data => {
            if (data.error) {
                console.error("Lỗi từ server:", data);
                alert('Lỗi từ Server: ' + (data.message || 'Không tìm thấy đơn đặt phòng! Vui lòng F12 xem Console.'));
                toggleModal('viewBookingModal');
                return;
            }
            currentBookingData = data;
            document.getElementById('v_loader').classList.add('hidden');
            document.getElementById('v_booking_id').innerText = '#BK-' + String(data.booking_id).padStart(4, '0');
            document.getElementById('v_room').innerText       = data.room_number + ' (' + (data.rental_type == 'hourly' ? 'Thuê giờ' : 'Thuê ngày') + ')';
            document.getElementById('v_checkin').innerText    = data.check_in;
            document.getElementById('v_checkout').innerText   = data.check_out;
            document.getElementById('v_price').innerText      = new Intl.NumberFormat('vi-VN').format(data.final_total) + 'đ';
        })
        .catch((err) => {
            console.error("Lỗi fetch/catch:", err);
            alert('Lỗi kết nối khi tải thông tin đơn phòng. Vui lòng bấm F12 -> Tab Console để xem chi tiết.');
            toggleModal('viewBookingModal');
        });
}