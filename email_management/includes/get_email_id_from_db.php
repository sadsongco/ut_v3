<?php

function getEmailIdFromDB($email, $db) {
    try {
    $query = 'SELECT email_id, email, last_sent name FROM ut_mailing_list WHERE email=?';
    $result = $db->query($query, [$_GET['email']])->fetch();
    if (!$result || sizeof($result) == 0) throw new PDOException('Email not found in database');
    return ["success"=>true, "email_id"=>$result['email_id']];
    }
    catch (PDOException $e) {
        error_log($e);
        return ['success'=>false, 'status'=>'db_error'];
    }

}