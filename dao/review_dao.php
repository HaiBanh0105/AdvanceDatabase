<?php
require_once '../config/mongodb.php';

function review_delete_by_user($user_id) {
    $mongo_db = mongo_get_db();
    return $mongo_db->reviews->deleteOne(['user_id' => (int)$user_id]);
}

function review_check_exists($user_id) {
    $mongo_db = mongo_get_db();
    return $mongo_db->reviews->findOne(['user_id' => (int)$user_id]);
}

function review_insert($user_id, $user_name, $rating, $comment) {
    $mongo_db = mongo_get_db();
    return $mongo_db->reviews->insertOne([
        'user_id' => (int)$user_id,
        'user_name' => $user_name,
        'rating' => (int)$rating,
        'comment' => $comment,
        'created_at' => new MongoDB\BSON\UTCDateTime()
    ]);
}

function review_update($user_id, $rating, $comment) {
    $mongo_db = mongo_get_db();
    return $mongo_db->reviews->updateOne(
        ['user_id' => (int)$user_id],
        ['$set' => ['rating' => (int)$rating, 'comment' => $comment, 'updated_at' => new MongoDB\BSON\UTCDateTime()]]
    );
}
?>