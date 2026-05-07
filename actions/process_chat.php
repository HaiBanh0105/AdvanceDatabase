<?php
session_start();
require_once '../dao/chat_dao.php';

header('Content-Type: application/json');

$action = $_GET['action'] ?? $_POST['action'] ?? '';

// Phân loại người dùng: Khách đã đăng nhập hoặc Khách vãng lai
$is_logged_in = isset($_SESSION['user_id']);
$session_id = $is_logged_in ? 'user_' . $_SESSION['user_id'] : 'guest_' . session_id();
$user_name = $is_logged_in ? ($_SESSION['full_name'] ?? 'Khách hàng') : 'Khách vãng lai';

if ($action === 'send') {
    $message = trim($_POST['message'] ?? '');
    if ($message) {
        chat_send_message($session_id, $user_name, $message, false);
        echo json_encode(['status' => 'success']);
    }
} elseif ($action === 'fetch') {
    $is_open = isset($_GET['is_open']) ? (int)$_GET['is_open'] : 0;

    // Nếu khách hàng đang mở khung chat, đánh dấu các tin nhắn của Admin là đã đọc
    if ($is_open === 1) {
        chat_mark_read_by_customer($session_id);
    }

    $messages = chat_get_messages($session_id);
    $res = [];
    $unread_admin_count = 0;
    foreach ($messages as $m) {
        if ($m['is_admin'] && empty($m['is_read'])) {
            $unread_admin_count++;
        }
        $res[] = [
            'is_admin' => $m['is_admin'],
            'message' => $m['message'],
            'time' => $m['created_at']->toDateTime()->setTimezone(new DateTimeZone('Asia/Ho_Chi_Minh'))->format('H:i')
        ];
    }
    echo json_encode(['status' => 'success', 'data' => $res, 'unread_admin_count' => $unread_admin_count]);
} elseif ($action === 'admin_send') {
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
        echo json_encode(['status' => 'error']);
        exit;
    }
    $target_session = $_POST['session_id'] ?? '';
    $message = trim($_POST['message'] ?? '');
    if ($target_session && $message) {
        chat_send_message($target_session, 'Admin', $message, true);
        echo json_encode(['status' => 'success']);
    }
} elseif ($action === 'admin_fetch_messages') {
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
        echo json_encode(['status' => 'error']);
        exit;
    }
    $target_session = $_GET['session_id'] ?? '';

    // Đánh dấu các tin nhắn của user trong phiên này là Admin đã đọc
    chat_mark_read_by_admin($target_session);

    $messages = chat_get_messages($target_session);
    $res = [];
    foreach ($messages as $m) {
        $res[] = [
            'is_admin' => $m['is_admin'],
            'message' => $m['message'],
            'time' => $m['created_at']->toDateTime()->setTimezone(new DateTimeZone('Asia/Ho_Chi_Minh'))->format('H:i d/m')
        ];
    }
    echo json_encode(['status' => 'success', 'data' => $res]);
} elseif ($action === 'admin_fetch_users') {
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
        echo json_encode(['status' => 'error']);
        exit;
    }
    $users = chat_get_recent_users();
    $res = [];
    foreach ($users as $u) {
        $res[] = [
            'session_id' => $u['_id'],
            'user_name' => $u['user_name'],
            'last_message' => $u['last_message'],
            'unread' => $u['unread_count'] ?? 0,
            'time' => $u['last_time']->toDateTime()->setTimezone(new DateTimeZone('Asia/Ho_Chi_Minh'))->format('H:i d/m')
        ];
    }
    echo json_encode(['status' => 'success', 'data' => $res]);
}
