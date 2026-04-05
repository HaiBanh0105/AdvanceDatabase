<?php
session_start();
require_once '../config/pdo.php';
require_once '../dao/auth_dao.php';
require_once '../config/mail_config.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../PHPMailer/Exception.php';
require '../PHPMailer/PHPMailer.php';
require '../PHPMailer/SMTP.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action == 'send_otp') {
        $full_name = trim($_POST['full_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $password = trim($_POST['password'] ?? '');

        // Kiểm tra email đã tồn tại chưa
        if (user_check_email_exists($email)) {
            echo json_encode(['status' => 'error', 'message' => 'Email này đã được sử dụng!']);
            exit();
        }

        // Tạo OTP 6 số ngẫu nhiên
        $otp = rand(100000, 999999);
        $_SESSION['reg_otp'] = $otp;
        $_SESSION['reg_data'] = [
            'full_name' => $full_name,
            'email' => $email,
            'phone' => $phone,
            // Mã hóa mật khẩu ngay từ bước này để bảo mật thông tin trong Session
            'password' => password_hash($password, PASSWORD_DEFAULT)
        ];
        $_SESSION['reg_otp_time'] = time();

        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host       = MAIL_HOST;
            $mail->SMTPAuth   = true;
            $mail->Username   = MAIL_USERNAME;
            $mail->Password   = MAIL_PASSWORD;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port       = MAIL_PORT;
            $mail->CharSet    = 'UTF-8';

            $mail->setFrom(MAIL_USERNAME, MAIL_FROM_NAME);
            $mail->addAddress($email);

            $mail->isHTML(true);
            $mail->Subject = 'Mã OTP xác nhận đăng ký tài khoản';
            $mail->Body    = "Xin chào $full_name,<br><br>Mã xác nhận OTP để hoàn tất đăng ký tài khoản của bạn là: <b style='font-size: 20px; color: #4F46E5;'>$otp</b>.<br><br>Mã này có hiệu lực trong 5 phút. Vui lòng không chia sẻ mã này cho bất kỳ ai.";

            $mail->send();
            echo json_encode(['status' => 'success', 'message' => 'Mã OTP đã được gửi đến email! Vui lòng kiểm tra hộp thư.']);
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => 'Không thể gửi email: ' . $mail->ErrorInfo]);
        }
        exit();
    }

    if ($action == 'verify_otp') {
        $otp_input = trim($_POST['otp'] ?? '');

        if (!isset($_SESSION['reg_otp']) || $otp_input != $_SESSION['reg_otp']) {
            echo json_encode(['status' => 'error', 'message' => 'Mã OTP không chính xác!']);
            exit();
        }

        if (time() - $_SESSION['reg_otp_time'] > 300) {
            echo json_encode(['status' => 'error', 'message' => 'Mã OTP đã hết hạn!']);
            exit();
        }

        $data = $_SESSION['reg_data'];
        if (user_register($data['email'], $data['phone'], $data['password'], $data['full_name'])) {
            unset($_SESSION['reg_otp'], $_SESSION['reg_data'], $_SESSION['reg_otp_time']);
            echo json_encode(['status' => 'success', 'message' => 'Đăng ký thành công! Đang chuyển hướng...']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Lỗi hệ thống, vui lòng thử lại sau!']);
        }
        exit();
    }
}