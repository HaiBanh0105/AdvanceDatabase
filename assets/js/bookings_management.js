/** File xử lý logic giao diện cho trang Quản lý Đơn đặt phòng */

// ===================== TAB SWITCHING =====================
function switchTab(tabId, btn) {
    document.querySelectorAll(".tab-content").forEach((tab) => {
        tab.classList.add("hidden");
        tab.classList.remove("block");
    });
    document.getElementById(tabId).classList.remove("hidden");
    document.getElementById(tabId).classList.add("block");

    document.querySelectorAll(".tab-btn").forEach((b) => {
        b.classList.remove("text-indigo-600", "border-indigo-600", "border-b-2", "font-bold");
        b.classList.add("text-slate-400", "font-medium");
    });
    btn.classList.add("text-indigo-600", "border-indigo-600", "border-b-2", "font-bold");
    btn.classList.remove("text-slate-400", "font-medium");
}

// ===================== MODAL TOGGLE =====================
function toggleBookingModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal.classList.contains("hidden")) {
        modal.classList.remove("hidden");
        modal.classList.add("flex");
    } else {
        modal.classList.add("hidden");
        modal.classList.remove("flex");
    }
}

// ===================== MỞ MODAL TẠO ĐƠN TỪ Ô PHÒNG XANH =====================
/**
 * @param {number} type_id   - ID hạng phòng
 * @param {string} room_number - Số phòng hiển thị trên tiêu đề modal
 * @param {number} room_id   - ID phòng cụ thể (truyền vào hidden input)
 */
function openCreateBookingModal(type_id, room_number, room_id) {
    const selectType = document.getElementById('modal_type_id');
    const title = document.getElementById('modalCreateTitle');
    const hiddenRoomId = document.getElementById('modal_room_id');

    if (type_id) {
        selectType.value = type_id;
    }
    if (room_id) {
        hiddenRoomId.value = room_id;
    } else {
        hiddenRoomId.value = '';
    }

    title.innerText = room_number ? "Tạo Đơn — Phòng " + room_number : "Tạo Đơn Thủ Công";

    toggleBookingModal('manualBookingModal');
}

// ===================== MỞ MODAL XEM ĐƠN TỪ Ô PHÒNG ĐỎ =====================
/**
 * @param {Object} dataObj - { booking_id, customer_name, check_in, check_out, room_number, type_name }
 */
function openViewBookingModal(dataObj) {
    document.getElementById('v_booking_id').innerText  = "#BK-" + String(dataObj.booking_id).padStart(4, '0');
    document.getElementById('v_customer').innerText    = dataObj.customer_name || 'Không xác định';
    document.getElementById('v_room').innerText        = dataObj.room_number || '—';
    document.getElementById('v_type').innerText        = dataObj.type_name   || '—';
    document.getElementById('v_checkin').innerText     = new Date(dataObj.check_in).toLocaleString('vi-VN');
    document.getElementById('v_checkout').innerText    = new Date(dataObj.check_out).toLocaleString('vi-VN');

    toggleBookingModal('viewBookingModal');
}

// ===================== TÌM KIẾM TRONG TAB 2 =====================
function filterBookings() {
    const keyword = document.getElementById('searchInput').value.toLowerCase().trim();
    const rows = document.querySelectorAll('#bookingTableBody tr');
    let found = 0;

    rows.forEach(row => {
        const text = row.innerText.toLowerCase();
        if (text.includes(keyword)) {
            row.style.display = '';
            found++;
        } else {
            row.style.display = 'none';
        }
    });

    const emptyMsg = document.getElementById('emptySearch');
    if (emptyMsg) {
        emptyMsg.style.display = found === 0 ? '' : 'none';
    }
}