<?php
function confirmEmailInDB($email_id, $db) {
    try {
        $query = 'UPDATE ut_mailing_list SET confirmed = 1 WHERE email_id = ?';
        $db->query($query, [$email_id]);
        return ["success"=>true];
    }
    catch (PDOException $e) {
        error_log($e);
        return ['success'=>false, 'status'=>'db_error'];
    }
}