<?php
require_once '../config/mongodb.php';

function chat_send_message($session_id, $user_name, $message, $is_admin = false)
{
    $db = mongo_get_db();
    return $db->chats->insertOne([
        'session_id' => (string)$session_id,
        'user_name' => $user_name,
        'message' => $message,
        'is_admin' => $is_admin,
        'is_read' => false,
        'created_at' => new MongoDB\BSON\UTCDateTime()
    ]);
}

function chat_get_messages($session_id)
{
    $db = mongo_get_db();
    return $db->chats->find(
        ['session_id' => (string)$session_id],
        ['sort' => ['created_at' => 1]]
    )->toArray();
}

function chat_get_recent_users()
{
    $db = mongo_get_db();
    // Dùng Aggregate để gom nhóm tin nhắn theo từng người dùng và lấy tin nhắn cuối cùng
    $pipeline = [
        ['$sort' => ['created_at' => -1]],
        ['$group' => [
            '_id' => '$session_id',
            'user_names' => ['$push' => '$user_name'],
            'last_message' => ['$first' => '$message'],
            'last_time' => ['$first' => '$created_at'],
            'unread_count' => [
                '$sum' => [
                    '$cond' => [
                        'if' => [
                            '$and' => [
                                ['$eq' => ['$is_admin', false]],
                                ['$ne' => ['$is_read', true]]
                            ]
                        ],
                        'then' => 1,
                        'else' => 0
                    ]
                ]
            ]
        ]],
        ['$sort' => ['last_time' => -1]]
    ];

    $results = $db->chats->aggregate($pipeline)->toArray();

    // Lọc ra tên Khách hàng (Bỏ qua tên 'Admin' nếu Admin là người nhắn cuối cùng)
    foreach ($results as &$r) {
        $r['user_name'] = 'Khách hàng';
        foreach ($r['user_names'] as $name) {
            if ($name !== 'Admin') {
                $r['user_name'] = $name;
                break; // Dừng lại ngay khi tìm thấy tên Khách hàng mới nhất
            }
        }
        // Dọn dẹp mảng tạm
        unset($r['user_names']);
    }

    return $results;
}

function chat_mark_read_by_admin($session_id)
{
    $db = mongo_get_db();
    // Cập nhật trạng thái "đã đọc" cho tất cả tin nhắn của Khách hàng trong Session này
    $db->chats->updateMany(
        ['session_id' => (string)$session_id, 'is_admin' => false],
        ['$set' => ['is_read' => true]]
    );
}

function chat_mark_read_by_customer($session_id)
{
    $db = mongo_get_db();
    // Cập nhật trạng thái "đã đọc" cho tất cả tin nhắn của Admin trong Session này
    $db->chats->updateMany(
        ['session_id' => (string)$session_id, 'is_admin' => true],
        ['$set' => ['is_read' => true]]
    );
}
