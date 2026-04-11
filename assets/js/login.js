/** File xử lý Giao diện Đăng nhập */
document.addEventListener("DOMContentLoaded", function () {
  // Gọi các phần tử HTML cho tính năng ẩn/hiện
  const passwordInput = document.getElementById("password");
  const togglePasswordBtn = document.getElementById("togglePassword");
  const eyeIcon = document.getElementById("eyeIcon");

  // Lắng nghe sự kiện click vào nút con mắt
  togglePasswordBtn.addEventListener("click", function () {
    // Kiểm tra loại input hiện tại
    const isPassword = passwordInput.getAttribute("type") === "password";

    // Thay đổi loại input
    passwordInput.setAttribute("type", isPassword ? "text" : "password");

    // Thay đổi icon tương ứng (eye <-> eye-slash)
    eyeIcon.classList.toggle("fa-eye");
    eyeIcon.classList.toggle("fa-eye-slash");

    // Giữ focus vào ô input sau khi click
    passwordInput.focus();
  });
  const loginForm = document.getElementById("loginForm");
  const alertBox = document.getElementById("alertBox");
  const alertMessage = document.getElementById("alertMessage");
  const alertIcon = document.getElementById("alertIcon");
  const submitBtn = document.getElementById("submitBtn");
  const btnText = document.getElementById("btnText");
  const btnSpinner = document.getElementById("btnSpinner");

  // 2. Lắng nghe sự kiện Submit
  loginForm.addEventListener("submit", function (e) {
    e.preventDefault(); // Chặn tải lại trang

    // Trạng thái Loading
    submitBtn.disabled = true;
    btnText.innerText = "Đang xử lý...";
    btnSpinner.classList.remove("hidden");
    alertBox.classList.add("hidden");

    // 3. Lấy dữ liệu từ các phần tử input
    const formData = new FormData(this);

    // 4. Gửi dữ liệu đến PHP (Backend)
    fetch("../actions/process_login.php", {
      method: "POST",
      body: formData,
    })
      .then((response) => response.json())
      .then((data) => {
        // Khôi phục nút bấm
        submitBtn.disabled = false;
        btnText.innerText = "Đăng nhập ngay";
        btnSpinner.classList.add("hidden");

        // Xóa các class màu cũ
        alertBox.classList.remove(
          "hidden",
          "bg-red-100",
          "text-red-600",
          "border-red-200",
          "bg-emerald-100",
          "text-emerald-600",
          "border-emerald-200",
        );

        if (data.status === "success") {
          // Hiển thị thông báo THÀNH CÔNG (Màu xanh) [cite: 4]
          alertBox.classList.add(
            "bg-emerald-100",
            "text-emerald-600",
            "border-emerald-200",
          );
          alertIcon.className = "fa-solid fa-circle-check animate-bounce";
          alertMessage.innerText = data.message;

          // Điều hướng sau 2 giây dựa trên vai trò
          setTimeout(() => {
            window.location.href =
              data.role === "Admin"
                ? "admin_dashboard.php"
                : "customer_index.php";
          }, 2000);
        } else {
          // Hiển thị thông báo LỖI (Màu đỏ)
          alertBox.classList.add(
            "bg-red-100",
            "text-red-600",
            "border-red-200",
          );
          alertIcon.className = "fa-solid fa-circle-exclamation";
          alertMessage.innerText = data.message;

          // Tự ẩn thông báo lỗi sau 4 giây
          setTimeout(() => {
            alertBox.classList.add("hidden");
          }, 4000);
        }
      })
      .catch((error) => {
        console.error("Lỗi:", error);
        submitBtn.disabled = false;
        btnText.innerText = "Đăng nhập ngay";
        btnSpinner.classList.add("hidden");
      });
  });
});
