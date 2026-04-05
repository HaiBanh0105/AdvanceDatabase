<?php
session_start();
require_once '../config/pdo.php';
require_once '../dao/auth_dao.php';
require_once '../config/mail_config.php';

// Import các class của PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Load file PHPMailer (nếu không dùng Composer)
require '../PHPMailer/Exception.php';
require '../PHPMailer/PHPMailer.php';
require '../PHPMailer/SMTP.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action == 'send_otp') {
        $email = trim($_POST['email'] ?? '');
        
        if (!user_check_email_exists($email)) {
            echo json_encode(['status' => 'error', 'message' => 'Email không tồn tại trong hệ thống!']);
            exit();
        }

        // Tạo OTP 6 số ngẫu nhiên
        $otp = rand(100000, 999999);
        $_SESSION['reset_otp'] = $otp;
        $_SESSION['reset_email'] = $email;
        $_SESSION['otp_time'] = time();

        $mail = new PHPMailer(true);
        try {
            // Cấu hình Server sử dụng các hằng số từ mail_config.php
            $mail->isSMTP();
            $mail->Host       = MAIL_HOST;
            $mail->SMTPAuth   = true;
            $mail->Username   = MAIL_USERNAME; 
            $mail->Password   = MAIL_PASSWORD; 
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port       = MAIL_PORT;
            $mail->CharSet    = 'UTF-8';

            // Cấu hình người gửi và người nhận
            $mail->setFrom(MAIL_USERNAME, MAIL_FROM_NAME);
            $mail->addAddress($email);

            $mail->isHTML(true);
            $mail->Subject = 'Mã OTP khôi phục mật khẩu';
            $mail->Body    = "Xin chào,<br><br>Mã xác nhận OTP để khôi phục mật khẩu của bạn là: <b style='font-size: 20px; color: #4F46E5;'>$otp</b>.<br><br>Mã này có hiệu lực trong 5 phút. Vui lòng không chia sẻ mã này cho bất kỳ ai.";

            $mail->send();
            echo json_encode(['status' => 'success', 'message' => 'Mã OTP đã được gửi đến email!']);
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => 'Không thể gửi email: ' . $mail->ErrorInfo]);
        }
        exit();
    }

    if ($action == 'reset_pass') {
        $otp_input = trim($_POST['otp'] ?? '');
        $new_password = trim($_POST['new_password'] ?? '');

        if (!isset($_SESSION['reset_otp']) || $otp_input != $_SESSION['reset_otp']) {
            echo json_encode(['status' => 'error', 'message' => 'Mã OTP không chính xác!']);
            exit();
        }

        if (time() - $_SESSION['otp_time'] > 300) {
            echo json_encode(['status' => 'error', 'message' => 'Mã OTP đã hết hạn!']);
            exit();
        }

        $email = $_SESSION['reset_email'];
        
        // Lấy mật khẩu hiện tại trong CSDL để đối chiếu
        $current_password = user_get_password($email);
        if ($current_password && (password_verify($new_password, $current_password) || $new_password === $current_password)) {
            echo json_encode(['status' => 'error', 'message' => 'Mật khẩu mới phải khác mật khẩu cũ!']);
            exit();
        }

        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        if (user_update_password($email, $hashed_password)) {
            unset($_SESSION['reset_otp'], $_SESSION['reset_email'], $_SESSION['otp_time']);
            echo json_encode(['status' => 'success', 'message' => 'Đổi mật khẩu thành công! Đang chuyển hướng...']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Mật khẩu mới phải khác mật khẩu cũ!']);
        }
        exit();
    }
}