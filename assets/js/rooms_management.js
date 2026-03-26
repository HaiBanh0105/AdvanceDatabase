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
