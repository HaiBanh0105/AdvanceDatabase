// Logic chuyển đổi Tab mượt mà
function switchTab(tabId, btn) {
  // Ẩn tất cả nội dung tab
  document
    .querySelectorAll(".tab-content")
    .forEach((tab) => tab.classList.add("hidden"));
  // Hiện tab được chọn
  document.getElementById(tabId).classList.remove("hidden");

  // Cập nhật style nút bấm
  document.querySelectorAll(".tab-btn").forEach((b) => {
    b.classList.remove(
      "text-indigo-600",
      "border-indigo-600",
      "border-b-2",
      "font-bold",
    );
    b.classList.add("text-slate-400", "font-medium");
  });
  btn.classList.add(
    "text-indigo-600",
    "border-indigo-600",
    "border-b-2",
    "font-bold",
  );
  btn.classList.remove("text-slate-400", "font-medium");
}

//Mở modal thêm phòng
function toggleModal(modalId) {
  const modal = document.getElementById(modalId);
  modal.classList.toggle("hidden");
}

function toggleModal(modalId) {
  const modal = document.getElementById(modalId);
  if (modal.classList.contains("hidden")) {
    modal.classList.remove("hidden");
    modal.classList.add("flex"); // Đảm bảo căn giữa
  } else {
    modal.classList.add("hidden");
    modal.classList.remove("flex");
  }
}

// Tự động đóng khi nhấn ra ngoài vùng modal
window.onclick = function (event) {
  if (event.target.classList.contains("fixed")) {
    event.target.classList.add("hidden");
    event.target.classList.remove("flex");
  }
};

//Script Bổ trợ cho Logic tìm kiếm và Toggle

// Kiểm tra URL parameters và hiển thị Toast tương ứng
    document.addEventListener('DOMContentLoaded', () => {
        const urlParams = new URLSearchParams(window.location.search);

        if (urlParams.get('error') === 'has_bookings') {
            showToast('Đã có đơn đặt phòng cho hạng phòng này, không thể xóa!', 'error');
        } else if (urlParams.get('error') === 'room_has_bookings') {
            showToast('Phòng này đã từng được đặt, không thể xóa!', 'error');
        } else if (urlParams.get('error') === 'duplicate_room') {
            showToast('Lỗi: Số phòng này đã tồn tại!', 'error');
        } else if (urlParams.get('error') === 'duplicate_type') {
            showToast('Lỗi: Tên hạng phòng này đã tồn tại!', 'error');
        } else if (urlParams.has('msg')) {
            showToast('Thao tác thành công!', 'success');
        }
    });

    // Mở Modal Sửa hạng phòng
    function openEditTypeModal(id, name, p_hr, p_day, capacity, desc) {
        document.getElementById('edit_type_id').value = id;
        document.getElementById('edit_type_name').value = name;
        document.getElementById('edit_type_price_hr').value = p_hr;
        document.getElementById('edit_type_price_day').value = p_day;
        document.getElementById('edit_type_capacity').value = capacity;
        document.getElementById('edit_type_desc').value = desc;
        toggleModal('editTypeModal');
    }

    // Mở Modal Sửa phòng thực tế
    function openEditRoomModal(id, num, typeId, status) {
        document.getElementById('edit_room_id').value = id;
        document.getElementById('edit_room_number').value = num;
        document.getElementById('edit_room_type_id').value = typeId;
        document.getElementById('edit_room_status').value = status;
        toggleModal('editRoomModal');
    }

    // Bộ lọc thời gian thực cho danh sách Phòng (roomTab)
    const searchInput = document.getElementById('searchRoomInput');
    const filterType = document.getElementById('filterRoomType');
    const filterStatus = document.getElementById('filterRoomStatus');
    const tableBody = document.getElementById('roomsTableBody');

    function filterRooms() {
        if (!tableBody) return;
        const term = searchInput.value.toLowerCase().trim();
        const typeText = filterType.options[filterType.selectedIndex].text.toLowerCase().trim();
        const typeVal = filterType.value;
        const status = filterStatus.value.toLowerCase().trim();
        tableBody.querySelectorAll('tr').forEach(row => {
            if (row.cells.length < 4) return;
            const rNum = row.cells[0].textContent.toLowerCase().trim();
            const rType = row.cells[1].textContent.toLowerCase().trim();
            const rStatus = row.cells[2].textContent.toLowerCase().trim();
            row.style.display = (rNum.includes(term) && (typeVal === "" || rType === typeText) && (status ===
                "" || rStatus.includes(status))) ? '' : 'none';
        });
    }
    if (searchInput) searchInput.addEventListener('input', filterRooms);
    if (filterType) filterType.addEventListener('change', filterRooms);
    if (filterStatus) filterStatus.addEventListener('change', filterRooms);

    // Nếu URL có tham số tab=types thì tự động switch (phòng khi reload sau khi thêm/sửa)
    if (new URLSearchParams(window.location.search).get('tab') === 'types') {
        const tabBtns = document.querySelectorAll('.tab-btn');
        if (tabBtns.length > 1) {
            try {
                switchTab('roomTypesTab', tabBtns[1]);
            } catch (e) {}
        }
    }