<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../frontend/login.php");
    exit();
}
require_once '../config/pdo.php';
require_once '../dao/DAO.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_id = $_SESSION['user_id'];

    $full_name = trim($_POST['full_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $id_number = trim($_POST['id_number'] ?? '');
    $nation = trim($_POST['nation'] ?? '');
    $address = trim($_POST['address'] ?? '');

    if (!preg_match('/^\d{12}$/', $id_number)) {
        header("Location: ../frontend/customer_profile.php?error=invalid_id");
        exit();
    }

    // Find if a customer record with the submitted CCCD exists.
    $customer_with_cccd = db_query_one("SELECT customer_id FROM Customer WHERE cccd = ?", $id_number);

    if ($customer_with_cccd) {
        // A customer with this CCCD exists. Now, check if it's linked to another account.
        $linked_account_id = db_query_value("SELECT account_id FROM Account WHERE customer_id = ?", $customer_with_cccd['customer_id']);

        // It's a conflict if an account is linked AND that account is not the current user's account.
        if ($linked_account_id && $linked_account_id != $user_id) {
            header("Location: ../frontend/customer_profile.php?error=duplicate_cccd");
            exit();
        }
    }

    try {
        // Does the current user's account already point to a customer?
        $my_customer_id = db_query_value("SELECT customer_id FROM Account WHERE account_id = ?", $user_id);

        // Does the submitted CCCD belong to an existing (unlinked) customer?
        $existing_walkin_customer_id = $customer_with_cccd ? $customer_with_cccd['customer_id'] : null;

        if ($my_customer_id) {
            // Case 1: The user's account is already linked to a customer record. Update it.
            db_execute(
                "UPDATE Customer SET full_name = ?, phone = ?, cccd = ?, nation = ?, address = ? WHERE customer_id = ?",
                $full_name,
                $phone,
                $id_number,
                $nation,
                $address,
                $my_customer_id
            );
        } else if ($existing_walkin_customer_id) {
            // Case 2: New account, but CCCD matches an existing walk-in customer.
            // Update the walk-in customer's info and link the account to it.
            db_execute(
                "UPDATE Customer SET full_name = ?, phone = ?, nation = ?, address = ?, email = (SELECT email FROM Account WHERE account_id = ?) WHERE customer_id = ?",
                $full_name,
                $phone,
                $nation,
                $address,
                $user_id,
                $existing_walkin_customer_id
            );
            // Link the account
            db_execute("UPDATE Account SET customer_id = ? WHERE account_id = ?", $existing_walkin_customer_id, $user_id);
        } else {
            // Case 3: New account and new CCCD. Insert a new customer record.
            db_execute(
                "INSERT INTO Customer (full_name, phone, cccd, nation, address, email) VALUES (?, ?, ?, ?, ?, (SELECT email FROM Account WHERE account_id = ?))",
                $full_name,
                $phone,
                $id_number,
                $nation,
                $address,
                $user_id
            );
            // Get the new customer_id and link the account to it.
            $new_customer_id = db_query_value("SELECT customer_id FROM Customer WHERE cccd = ?", $id_number);
            db_execute("UPDATE Account SET customer_id = ? WHERE account_id = ?", $new_customer_id, $user_id);
        }

        // In all cases, set the account status to 'pending' for admin approval.
        db_execute("UPDATE Account SET status = 'pending' WHERE account_id = ?", $user_id);

        $_SESSION['full_name'] = $full_name;

        header("Location: ../frontend/customer_profile.php?update=success_pending");
        exit();
    } catch (Exception $e) {
        error_log("Profile Update Error: " . $e->getMessage());
        header("Location: ../frontend/customer_profile.php?error=system_error");
        exit();
    }
}
