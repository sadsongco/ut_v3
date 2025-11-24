<?php

function addEmailToDB($email, $name, $db) {
    try {
        $query = "INSERT INTO ut_mailing_list
        (email, domain, name, last_sent, subscribed, date_added)
        VALUES
        (?, SUBSTRING_INDEX(?, '@', -1), ?, ?, ?, NOW())";
        $db->query($query, [$email, $email, $name, 0, 1]);
        return ['success'=>true, 'insert_id'=>$db->lastInsertId()];
    }
    catch (PDOException $e) {
        if ($e->getCode() == 23000) {
            error_log($e);
            return ['success'=> false, 'status'=>'exists'];
        } else {
            error_log($e);
            return ['success'=>false, 'status'=>'db_error'];
        }
    }
}