/** File xử lý Giao diện Đăng ký & OTP */
document.addEventListener("DOMContentLoaded", function () {
    const step1Form = document.getElementById("step1Form");
    const step2Form = document.getElementById("step2Form");
    const alertBox = document.getElementById("alertBox");
    const alertIcon = document.getElementById("alertIcon");
    const alertMessage = document.getElementById("alertMessage");

    function showAlert(type, message) {
        alertBox.className = `mb-6 p-4 rounded-2xl text-sm font-bold flex items-center gap-3 border shadow-sm transition-all duration-500 ${type === 'success' ? 'bg-emerald-100 text-emerald-600 border-emerald-200' : 'bg-red-100 text-red-600 border-red-200'}`;
        alertIcon.className = type === 'success' ? 'fa-solid fa-circle-check' : 'fa-solid fa-circle-exclamation';
        alertMessage.innerText = message;
        alertBox.classList.remove("hidden");
    }
  
    if (step1Form) {
        step1Form.addEventListener("submit", function (e) {
            e.preventDefault();
            document.getElementById("btnStep1").disabled = true;
            document.getElementById("textStep1").innerText = "Đang gửi email...";
            document.getElementById("spinStep1").classList.remove("hidden");
            alertBox.classList.add("hidden");

            fetch("../actions/process_register.php", {
                method: "POST",
                body: new FormData(this),
            }).then(res => res.json()).then(data => {
                document.getElementById("btnStep1").disabled = false;
                document.getElementById("textStep1").innerText = "Đăng ký & Nhận mã OTP";
                document.getElementById("spinStep1").classList.add("hidden");

                if (data.status === "success") {
                    showAlert('success', data.message);
                    step1Form.classList.add("hidden");
                    step2Form.classList.remove("hidden");
                } else {
                    showAlert('error', data.message);
                }
            }).catch(err => {
                document.getElementById("btnStep1").disabled = false;
                document.getElementById("textStep1").innerText = "Đăng ký & Nhận mã OTP";
                document.getElementById("spinStep1").classList.add("hidden");
                showAlert('error', 'Lỗi kết nối máy chủ!');
            });
        });
    }

    if (step2Form) {
        step2Form.addEventListener("submit", function (e) {
            e.preventDefault();
            document.getElementById("btnStep2").disabled = true;
            document.getElementById("textStep2").innerText = "Đang xử lý...";
            document.getElementById("spinStep2").classList.remove("hidden");
            alertBox.classList.add("hidden");

            fetch("../actions/process_register.php", {
                method: "POST",
                body: new FormData(this),
            }).then(res => res.json()).then(data => {
                document.getElementById("btnStep2").disabled = false;
                document.getElementById("textStep2").innerText = "Xác nhận đăng ký";
                document.getElementById("spinStep2").classList.add("hidden");

                if (data.status === "success") {
                    showAlert('success', data.message);
                    setTimeout(() => window.location.href = "login.php", 2000);
                } else {
                    showAlert('error', data.message);
                }
            }).catch(err => {
                document.getElementById("btnStep2").disabled = false;
                document.getElementById("textStep2").innerText = "Xác nhận đăng ký";
                document.getElementById("spinStep2").classList.add("hidden");
                showAlert('error', 'Lỗi kết nối máy chủ!');
            });
        });
    }
});