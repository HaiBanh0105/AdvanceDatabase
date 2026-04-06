<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../frontend/login.php");
    exit();
}

require_once '../config/mongo.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db = mongo_get_db();
    $collection = $db->promotions;
    
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $code = strtoupper(trim($_POST['code']));
        $discount = (int)$_POST['discount'];
        $description = trim($_POST['description']);

        if (!empty($code) && $discount > 0) {
            $collection->insertOne([
                'code' => $code,
                'discount_percent' => $discount,
                'description' => $description,
                'status' => 'active'
            ]);
        }
    } 
    elseif ($action === 'edit') {
        $id = $_POST['id'] ?? '';
        $code = strtoupper(trim($_POST['code']));
        $discount = (int)$_POST['discount'];
        $description = trim($_POST['description']);

        if (!empty($id) && !empty($code) && $discount > 0) {
            $collection->updateOne(
                ['_id' => new MongoDB\BSON\ObjectId($id)],
                ['$set' => [
                    'code' => $code,
                    'discount_percent' => $discount,
                    'description' => $description
                ]]
            );
        }
    }
    elseif ($action === 'delete') {
        $id = $_POST['id'] ?? '';
        if (!empty($id)) {
            $collection->deleteOne(['_id' => new MongoDB\BSON\ObjectId($id)]);
        }
    }

    header("Location: ../frontend/admin_promotions.php");
    exit();
}
