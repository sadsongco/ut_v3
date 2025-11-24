<?php

require_once('includes/mailout_includes.php');

function getCompletedEmails($db, $table, $current_mailout) {
    $all = null;
    $sent = null;
    try {
        $query = "SELECT COUNT(*) AS `total` FROM `$table`";
        $result = $db->query($query)->fetchAll();
        $all = $result[0]['total'];
        $query = "SELECT COUNT(*) AS `sent` FROM `$table` WHERE `last_sent` = ?";

        $result = $db->query($query, [$current_mailout])->fetch();
        $sent = $result['sent'];
        $query = "SELECT COUNT(*) AS `errors`, IF(COUNT(*) > 0, 1, NULL) AS `error_flag` FROM `$table` WHERE `error`=?";
        $errors = $db->query($query, [1])->fetch();
    }
    catch (EXCEPTION $e) {
        echo "Error retrieving completed emails: ".$e->getMessage();
        return;
    }
    return ["total"=>$all, "sent"=>$sent, "errors"=>$errors];
}


$current_mailout_contents = file_get_contents(base_path(WEB_ASSET_PATH . CURRENT_MAILOUT_PATH));

$sent = null;
$dd_sent = null;
$current_mailout = false;
$test = false;

if ($current_mailout_contents != "") {
    $mailout_arr = explode(":", $current_mailout_contents);
    if ($mailout_arr[0] == "test") {
        $test = true;
        $current_mailout_id = $mailout_arr[1];
    } else {
        $current_mailout_id = $mailout_arr[0];
    }
    $mailing_list = $test ? "test_mailing_list" : "ut_mailing_list";
    $current_mailout = getCurrentMailout($db, $current_mailout_id);
    $sent = getCompletedEmails($db, $mailing_list, $current_mailout);
    $sent['mailing_list'] = $mailing_list;
}

echo $m->render("selectMailout", ["current_mailout"=>$current_mailout, "test"=>$test, "sent"=>$sent]);