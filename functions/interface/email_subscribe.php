<?php
require_once(__DIR__ . "/../functions.php");

require_once(base_path("functions/email/email_includes.php"));

$output = json_encode(['success'=> false, 'status'=>'not found']);

$post = file_get_contents('php://input');
$post = json_decode($post, true);

if (isset($post['email']) && $post['email'] != '') {
    $db_result = addEmailToDB($post['email'], $post['name'], $db );
    if (!$db_result['success']) {
        echo json_encode($db_result);
        exit();
    }
    $email_result = sendConfirmationEmail(['email'=>$post['email'], 'name'=>$post['name'], 'email_id'=>$db_result['insert_id']]);
    if ($email_result["success"]) {
        echo json_encode(($email_result));
        exit();
    }
}
$output = ["success"=>true];

echo json_encode($output);
