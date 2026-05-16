<?php

function setLastMailoutSent($email_id, $last_mailout, $db) {
    try {
        $query = "UPDATE ut_mailing_list SET last_sent = ? WHERE email_id = ?";
        $db->query($query, [$last_mailout, $email_id]);
    }
    catch (PDOException $e) {
        error_log($e);
    }
}