<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../frontend/login.php");
    exit();
}
require_once '../dao/booking_dao.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'update_pricing') {
        $weekend = (float)$_POST['weekend_multiplier'];
        $holiday = (float)$_POST['holiday_multiplier'];
        $holidays_str = trim($_POST['holidays_list']);

        $holidays_arr = array_map('trim', explode(',', $holidays_str));
        $holidays_arr = array_filter($holidays_arr, function ($v) {
            return !empty($v);
        });

        $data = [
            'weekend_multiplier' => $weekend,
            'holiday_multiplier' => $holiday,
            'holidays' => array_values($holidays_arr)
        ];

        save_pricing_config($data);
        header("Location: ../frontend/admin_pricing.php?msg=success");
        exit();
    }
}
